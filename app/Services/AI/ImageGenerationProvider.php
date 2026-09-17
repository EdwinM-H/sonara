<?php

namespace App\Services\AI;

use App\Models\AIGeneration;

interface ImageGenerationProvider
{
    /**
     * Genera una imagen a partir de un prompt de texto.
     *
     * @return array{image_path: string, reference: ?string, cost: ?float}
     *
     * @throws \RuntimeException
     */
    public function generate(string $prompt, string $style = null): array;

    public function name(): string;
}