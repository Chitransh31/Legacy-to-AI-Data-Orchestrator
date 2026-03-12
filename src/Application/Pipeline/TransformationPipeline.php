<?php

declare(strict_types=1);

namespace App\Application\Pipeline;

use App\Domain\Contract\TransformerInterface;
use App\Domain\Entity\TransformedPayload;
use App\Domain\ValueObject\TokenBudget;

final class TransformationPipeline
{
    /** @var TransformerInterface[] */
    private array $stages;

    public function __construct(TransformerInterface ...$stages)
    {
        $this->stages = $stages;
    }

    /**
     * @param array<int, array<string, mixed>> $rawRecords
     */
    public function process(array $rawRecords, string $schemaVersion = '1.0'): TransformedPayload
    {
        $data = $rawRecords;

        foreach ($this->stages as $stage) {
            $data = $stage->transform($data);
        }

        $json = (string) json_encode($data, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        $estimatedTokens = TokenBudget::estimateTokens($json);

        return new TransformedPayload($data, $estimatedTokens, $schemaVersion);
    }
}
