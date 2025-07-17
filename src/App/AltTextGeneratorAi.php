<?php

namespace ContextualAltText\App;

use ContextualAltText\App\AIProviders\AIProviderInterface;
use ContextualAltText\Config\Constants;
use ContextualAltText\App\Admin\PluginOptions;

class AltTextGeneratorAi implements AltTextGeneratorInterface
{
    private AIProviderInterface $AIProvider;

    /**
     * @param AIProviderInterface $AIProvider
     */
    private function __construct(AIProviderInterface $AIProvider)
    {
        $this->AIProvider = $AIProvider;
    }

    /**
     * Factory to create AltTextGeneratorAi based on Hugging Face model selection.
     * Note: This is for vision models only. Text models are handled directly in Setup.php
     *
     * @return AltTextGeneratorAi
     */
    public static function makeFromHuggingFaceModel(): AltTextGeneratorAi
    {
        // This method is no longer used since we restructured to use 
        // vision + text model pipeline in Setup.php
        throw new \Exception('Use Setup.php pipeline instead of AltTextGeneratorAi for contextual alt text generation.');
    }

    /**
     * @param  AIProviderInterface $aiProvider
     * @return AltTextGeneratorAi
     */
    public static function make(AIProviderInterface $aiProvider): AltTextGeneratorAi
    {
        return new self($aiProvider);
    }

    /**
     * Get the alt text of the image
     *
     * @param  int        $imageId
     * @param  array|null $context
     * @return string
     */
    public function altText(int $imageId, ?array $context = null): string
    {
        $imageUrl = \wp_get_attachment_url($imageId);
        $prompt = $this->buildPromptWithContext($context);

        return $this->AIProvider->response($imageUrl, $prompt);
    }

    /**
     * Build the prompt with context information.
     *
     * @param  array|null $context
     * @return string|null
     */
    private function buildPromptWithContext(?array $context): ?string
    {
        // For now, we just get the default prompt from the provider,
        // which might be null if the user hasn't set one.
        $basePrompt = $this->AIProvider->getPrompt();

        if (empty($context) || !PluginOptions::is_contextual_awareness_enabled()) {
            return $basePrompt;
        }

        $context_summary = "For context, this image is part of a post titled '{$context['post_title']}'.";

        if (!empty($context['terms'])) {
            $term_parts = [];
            foreach ($context['terms'] as $taxonomy_label => $terms_list) {
                $term_parts[] = "{$taxonomy_label}: {$terms_list}";
            }
            $context_summary .= ' The post has the following terms: ' . implode('; ', $term_parts) . '.';
        }

        if (!empty($context['post_content'])) {
            $context_summary .= " Here is a snippet from the post content: \"{$context['post_content']}\".";
        }

        // Prepend the context to the base prompt.
        return $context_summary . "\n\n" . $basePrompt;
    }
}
