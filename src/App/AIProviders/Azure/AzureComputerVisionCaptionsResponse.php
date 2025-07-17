<?php

namespace ContextualAltText\App\AIProviders\Azure;

use ContextualAltText\App\Admin\PluginOptions;
use ContextualAltText\App\AIProviders\AIProviderInterface;
use ContextualAltText\App\Exceptions\Azure\AzureComputerVisionException;
use ContextualAltText\App\Exceptions\Azure\AzureTranslateInstanceException;
use ContextualAltText\Config\Constants;

class AzureComputerVisionCaptionsResponse implements AIProviderInterface
{
    private function __construct()
    {
    }

    public static function make(): AzureComputerVisionCaptionsResponse
    {
        return new self();
    }

    /**
     * Make a request to Azure Computer Vision APIs to retrieve the contents of the uploaded image
     * Uses the selected language directly or falls back to translation if needed
     *
     * @param  string      $imageUrl
     * @param  string|null $prompt
     * @return string
     * @throws AzureComputerVisionException
     * @throws AzureTranslateInstanceException
     */
    public function response(string $imageUrl, ?string $prompt = null): string
    {
        // Azure Computer Vision does not use a dynamic prompt.
        // The $prompt parameter is ignored, but included for interface compatibility.

        // Get the selected language from the new language settings
        $selectedLanguage = PluginOptions::selectedLanguage();

        // Map language codes to Azure-supported languages
        $azureLanguageMapping = [
            'en' => 'en',
            'zh' => 'zh-Hans',
            'zh-tw' => 'zh-Hant',
            'ja' => 'ja',
            'ko' => 'ko',
            'es' => 'es',
            'fr' => 'fr',
            'de' => 'de',
            'it' => 'it',
            'pt' => 'pt',
            'ru' => 'ru',
            'ar' => 'ar',
            'hi' => 'hi',
            'th' => 'th',
            'vi' => 'vi',
        ];

        // Use the mapped language or fallback to English
        $azureLanguage = $azureLanguageMapping[$selectedLanguage] ?? 'en';

        $response = wp_remote_post(
            PluginOptions::endpointAzureComputerVision() . "computervision/imageanalysis:analyze?api-version=2023-02-01-preview&features=caption&language={$azureLanguage}&gender-neutral-caption=False",
            [
                'headers' => [
                    'content-type' => 'application/json',
                    'Ocp-Apim-Subscription-Key' => PluginOptions::apiKeyAzureComputerVision(),
                ],
                'body' => json_encode(
                    [
                    'url' => $imageUrl,
                    ]
                ),
                'method' => 'POST',
            ]
        );

        $responseBody = wp_remote_retrieve_body($response);
        if (empty($responseBody)) {
            if (is_object($response) && property_exists($response, 'errors') && array_key_exists('http_request_failed', $response->errors)) {
                throw new AzureTranslateInstanceException("Error: " . $response->errors['http_request_failed'][0]);
            }
            if (is_array($response) && array_key_exists('response', $response)) {
                throw new AzureTranslateInstanceException("Code: " . $response['response']['code'] . " - " . $response['response']['message']);
            }
            throw new AzureComputerVisionException("Error: please check if the Azure endpoint in plugin options is right");
        }

        $bodyResult = json_decode($responseBody, true);
        if (array_key_exists('error', $bodyResult)) {
            throw new AzureComputerVisionException("Error code: " . $bodyResult['error']['code'] . " - " . $bodyResult['error']['message']);
        }

        $altText = $bodyResult['captionResult']['text'];

        // If Azure doesn't support the selected language directly, use translation as fallback
        if ($azureLanguage === 'en' && $selectedLanguage !== 'en') {
            $legacySelectedLanguage = PluginOptions::languageAzureTranslateInstance();
            if (!empty($legacySelectedLanguage) && $legacySelectedLanguage !== 'en') {
                return (AzureTranslator::make())->translate($altText, $legacySelectedLanguage);
            }
        }

        return $altText;
    }

    /**
     * @return string|null
     */
    public function getPrompt(): ?string
    {
        return get_option(Constants::CONTEXTUAL_ALT_TEXT_OPTION_FIELD_PROMPT_AZURE);
    }
}
