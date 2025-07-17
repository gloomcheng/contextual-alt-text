<?php

namespace ContextualAltText\App\AIProviders;

interface AITextProviderInterface
{
    /**
     * Process text context and generate contextual information
     *
     * @param  string $text The text content to process
     * @return string
     */
    public function response(string $text): string;

    /**
     * Generate contextual alt text based on image description and article context
     *
     * @param  string $imageDescription Description of the image from vision model
     * @param  array  $context Article context information
     * @return string
     */
    public function generateContextualAltText(string $imageDescription, array $context): string;
} 