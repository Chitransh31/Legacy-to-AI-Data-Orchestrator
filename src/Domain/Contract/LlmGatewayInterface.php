<?php

declare(strict_types=1);

namespace App\Domain\Contract;

use App\Domain\Entity\AnalysisResult;
use App\Domain\Entity\TransformedPayload;

interface LlmGatewayInterface
{
    public function analyze(TransformedPayload $payload, string $prompt): AnalysisResult;
}
