<?php

namespace ContextualAltText\App\AIProviders\OpenAI;

use OpenAI;
use OpenAI\Client;
use OpenAI\Exceptions\ErrorException;
use ContextualAltText\App\Admin\PluginOptions;
use ContextualAltText\App\Exceptions\OpenAI\OpenAIException;
use ContextualAltText\Config\Constants;

class Fallback extends OpenAIResponse
{
    public static function make(): Fallback
    {
        return new self();
    }

    /**
     *  Make a request to OpenAI Chat APIs to retrieve a description for the image file name passed
     *
     * @param  string      $imageUrl
     * @param  string|null $prompt
     * @return string
     * @throws OpenAIException
     */
    public function response(string $imageUrl, ?string $prompt = null): string
    {
        $final_prompt = $prompt ?? parent::prompt();
        $requestBody = parent::prepareRequestBody(Constants::CONTEXTUAL_ALT_TEXT_OPENAI_FALLBACK_MODEL, $final_prompt, $imageUrl);
        $decodedBody = parent::decodedResponseBody($requestBody, Constants::CONTEXTUAL_ALT_TEXT_OPENAI_CHAT_COMPLETION_ENDPOINT);
        return $this->cleanString($decodedBody['choices'][0]['message']['content']);
    }

    /**
     * @return string|null
     */
    public function getPrompt(): ?string
    {
        return parent::prompt();
    }
}
