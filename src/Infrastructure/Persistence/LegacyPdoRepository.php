<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Domain\Contract\LegacyDataRepositoryInterface;
use App\Domain\Entity\LegacyRecord;
use App\Domain\Exception\LegacyConnectionException;
use App\Domain\ValueObject\DataSourceId;
use App\Infrastructure\Config\ConfigLoader;
use DateTimeImmutable;
use PDOException;

final class LegacyPdoRepository implements LegacyDataRepositoryInterface
{
    private int $consecutiveFailures = 0;
    private ?DateTimeImmutable $circuitOpenUntil = null;

    public function __construct(
        private ConnectionFactory $connectionFactory,
        private ConfigLoader $config,
    ) {
    }

    public function fetchById(DataSourceId $source, string $id): LegacyRecord
    {
        $this->checkCircuitBreaker($source);

        $schema = $this->getSchema($source);
        $table = $schema['table'];

        $primaryKey = array_key_first($schema['mappings']);

        try {
            $pdo = $this->connectionFactory->getConnection();
            $stmt = $pdo->prepare("SELECT * FROM {$table} WHERE {$primaryKey} = :id LIMIT 1");
            $stmt->execute(['id' => $id]);

            $row = $stmt->fetch();
            $this->consecutiveFailures = 0;

            return new LegacyRecord(
                $source,
                $row ?: [],
                new DateTimeImmutable()
            );
        } catch (PDOException $e) {
            $this->recordFailure();
            throw LegacyConnectionException::queryFailed($source->value(), $e->getMessage());
        }
    }

    public function fetchBatch(DataSourceId $source, array $criteria): array
    {
        $this->checkCircuitBreaker($source);

        $schema = $this->getSchema($source);
        $table = $schema['table'];

        try {
            $pdo = $this->connectionFactory->getConnection();

            $where = [];
            $params = [];
            foreach ($criteria as $column => $value) {
                $where[] = "{$column} = :{$column}";
                $params[$column] = $value;
            }

            $sql = "SELECT * FROM {$table}";
            if ($where !== []) {
                $sql .= ' WHERE ' . implode(' AND ', $where);
            }
            $sql .= ' LIMIT 1000';

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);

            $rows = $stmt->fetchAll();
            $this->consecutiveFailures = 0;

            $now = new DateTimeImmutable();
            return array_map(
                fn(array $row) => new LegacyRecord($source, $row, $now),
                $rows
            );
        } catch (PDOException $e) {
            $this->recordFailure();
            throw LegacyConnectionException::queryFailed($source->value(), $e->getMessage());
        }
    }

    /** @return array<string, mixed> */
    private function getSchema(DataSourceId $source): array
    {
        $schemaPath = dirname(__DIR__, 3) . '/config/schemas/' . $source->value() . '.php';

        if (!file_exists($schemaPath)) {
            throw LegacyConnectionException::unreachable(
                $source->value(),
                "No schema configuration found at {$schemaPath}"
            );
        }

        return require $schemaPath;
    }

    private function checkCircuitBreaker(DataSourceId $source): void
    {
        if ($this->circuitOpenUntil !== null && $this->circuitOpenUntil > new DateTimeImmutable()) {
            throw LegacyConnectionException::unreachable(
                $source->value(),
                'Circuit breaker is open. Retrying after cooldown.'
            );
        }

        $this->circuitOpenUntil = null;
    }

    private function recordFailure(): void
    {
        $this->consecutiveFailures++;

        if ($this->consecutiveFailures >= 5) {
            $this->circuitOpenUntil = new DateTimeImmutable('+30 seconds');
            $this->consecutiveFailures = 0;
        }
    }
}
