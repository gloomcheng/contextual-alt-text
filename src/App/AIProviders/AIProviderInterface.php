<?php

namespace ContextualAltText\App\AIProviders;

interface AIProviderInterface
{
    /**
     * @param  string      $imageUrl
     * @param  string|null $prompt
     * @return string
     */
    public function response(string $imageUrl, ?string $prompt = null): string;

    /**
     * Get the base prompt from options.
     *
     * @return string|null
     */
    public function getPrompt(): ?string;
}
