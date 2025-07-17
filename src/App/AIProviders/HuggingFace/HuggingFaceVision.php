<?php

declare(strict_types=1);

namespace ContextualAltText\App\AIProviders\HuggingFace;

use ContextualAltText\App\Admin\PluginOptions;
use ContextualAltText\App\AIProviders\AIProviderInterface;
use ContextualAltText\App\Logging\DBLogger;
use ContextualAltText\Config\Constants;

final class HuggingFaceVision implements AIProviderInterface
{
    public function response(string $imageUrl, ?string $prompt = null): string
    {
        $apiKey = PluginOptions::apiKeyHuggingFace();
        if (empty($apiKey)) {
            DBLogger::make()->writeLog('error', 'HuggingFace API key is empty', [], null);
            return '';
        }

        // Get model from settings
        $selectedModel = get_option('cat_vision_huggingface_model', Constants::CONTEXTUAL_ALT_TEXT_HF_JOY_CAPTION_BETA);
        
        // Use appropriate API based on model
        if ($selectedModel === Constants::CONTEXTUAL_ALT_TEXT_HF_JOY_CAPTION_BETA) {
            return $this->useJoyCaptionAPI($imageUrl, $apiKey, $prompt);
        }
        
        // For other HuggingFace models, use the Inference API
        return $this->useInferenceAPI($selectedModel, $imageUrl, $apiKey, $prompt);
    }
    
    /**
     * Use Joy Caption Beta One via simplified Gradio API
     */
    private function useJoyCaptionAPI(string $imageUrl, string $apiKey, ?string $prompt): string
    {
        DBLogger::make()->writeLog('info', 'Using Joy Caption Beta One API', ['imageUrl' => $imageUrl], null);
        
        // Get custom prompt from settings or use provided prompt
        $finalPrompt = $prompt ?: $this->getPrompt();
        
        // Add language preference to prompt
        $selectedLanguage = PluginOptions::selectedLanguage();
        $languageInstruction = $this->getLanguageInstruction($selectedLanguage);
        
        if (!empty($languageInstruction)) {
            $finalPrompt = $finalPrompt . ' ' . $languageInstruction;
        }
        
        // Step 1: POST to get EVENT_ID
        $callEndpoint = 'https://fancyfeast-joy-caption-beta-one.hf.space/gradio_api/call/chat_joycaption';
        
        // Prepare payload according to cURL API documentation
        $payload = [
            'data' => [
                // Image parameter with correct format
                [
                    'path' => $imageUrl,
                    'meta' => ['_type' => 'gradio.FileData']
                ],
                $finalPrompt, // prompt: string
                0.6, // temperature: number
                0.9, // top_p: number
                256, // max_new_tokens: number
                true // log_prompt: boolean
            ]
        ];
        
        $eventId = $this->makeGradioCallRequest($callEndpoint, $payload, $apiKey);
        
        if (empty($eventId)) {
            DBLogger::make()->writeLog('error', 'Failed to get EVENT_ID from Joy Caption API', [], null);
            // Fallback to BLIP model
            return $this->useInferenceAPI(Constants::CONTEXTUAL_ALT_TEXT_HF_BLIP_BASE, $imageUrl, $apiKey, $finalPrompt);
        }
        
        // Step 2: GET the result using EVENT_ID
        $resultEndpoint = "https://fancyfeast-joy-caption-beta-one.hf.space/gradio_api/call/chat_joycaption/{$eventId}";
        $result = $this->getGradioResult($resultEndpoint, $apiKey);
        
        // Apply length limitations
        if (!empty($result)) {
            $result = $this->limitAltTextLength($result, $selectedLanguage);
            
            DBLogger::make()->writeLog('success', 'Joy Caption API successful', [
                'resultLength' => strlen($result),
                'language' => $selectedLanguage
            ], null);
            return $result;
        }
        
        DBLogger::make()->writeLog('warning', 'Joy Caption API failed, falling back to BLIP model', [], null);
        
        // Fallback to BLIP model
        return $this->useInferenceAPI(Constants::CONTEXTUAL_ALT_TEXT_HF_BLIP_BASE, $imageUrl, $apiKey, $finalPrompt);
    }
    
    /**
     * Use HuggingFace Inference API for standard vision models
     */
    private function useInferenceAPI(string $model, string $imageUrl, string $apiKey, ?string $prompt): string
    {
        DBLogger::make()->writeLog('info', 'Using HuggingFace Inference API', ['model' => $model], null);
        
        $imageData = $this->getImageData($imageUrl);
        if ($imageData === false) {
            DBLogger::make()->writeLog('error', 'Failed to read image data', ['imageUrl' => $imageUrl], null);
            return '';
        }
        
        $endpoint = "https://api-inference.huggingface.co/models/$model";
        
        // For image captioning models, send the image data directly
        $result = $this->makeImageCaptionRequest($endpoint, $imageData, $apiKey);
        
        if (!empty($result)) {
            // Apply language-specific length limitations
            $selectedLanguage = PluginOptions::selectedLanguage();
            $result = $this->limitAltTextLength($result, $selectedLanguage);
            
            DBLogger::make()->writeLog('success', 'Inference API successful', [
                'model' => $model,
                'resultLength' => strlen($result),
                'language' => $selectedLanguage
            ], null);
            return $result;
        }
        
        return '';
    }
    
    /**
     * Get image data from URL
     */
    private function getImageData(string $imageUrl): string|false
    {
        DBLogger::make()->writeLog('info', 'Reading image data', ['imageUrl' => $imageUrl], null);
        
        $context = stream_context_create([
            'http' => [
                'timeout' => 30,
                'user_agent' => 'WordPress/6.0 Contextual Alt Text Plugin'
            ]
        ]);
        
        return file_get_contents($imageUrl, false, $context);
    }
    
    /**
     * Get MIME type from image data
     */
    private function getMimeType(string $imageData): string
    {
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->buffer($imageData);
        return $mimeType ?: 'image/jpeg';
    }
    
    /**
     * Make Gradio call request to get EVENT_ID
     */
    private function makeGradioCallRequest(string $endpoint, array $payload, string $apiKey): string
    {
        $headers = [
            'Content-Type: application/json'
        ];
        
        // Add authorization if available
        if (!empty($apiKey)) {
            $headers[] = 'Authorization: Bearer ' . $apiKey;
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $endpoint);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        DBLogger::make()->writeLog('info', 'Gradio call request completed', [
            'endpoint' => $endpoint,
            'httpCode' => $httpCode,
            'hasError' => !empty($curlError),
            'responseLength' => strlen($response ?: '')
        ], null);

        if ($curlError) {
            DBLogger::make()->writeLog('error', "Gradio call cURL error: $curlError", [], null);
            return '';
        }

        if ($httpCode !== 200) {
            DBLogger::make()->writeLog('error', "Gradio call HTTP error", [
                'httpCode' => $httpCode,
                'response' => substr($response ?: '', 0, 500)
            ], null);
            return '';
        }

        // Parse the response to extract EVENT_ID
        $data = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE || !isset($data['event_id'])) {
            DBLogger::make()->writeLog('error', 'Failed to parse EVENT_ID from Gradio response', [
                'json_error' => json_last_error_msg(),
                'response_preview' => substr($response, 0, 200)
            ], null);
            return '';
        }

        return $data['event_id'];
    }
    
    /**
     * Get result from Gradio using EVENT_ID
     */
    private function getGradioResult(string $endpoint, string $apiKey): string
    {
        $headers = [];
        
        // Add authorization if available
        if (!empty($apiKey)) {
            $headers[] = 'Authorization: Bearer ' . $apiKey;
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $endpoint);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 120); // Longer timeout for processing
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        DBLogger::make()->writeLog('info', 'Gradio result request completed', [
            'endpoint' => $endpoint,
            'httpCode' => $httpCode,
            'hasError' => !empty($curlError),
            'responseLength' => strlen($response ?: '')
        ], null);

        if ($curlError) {
            DBLogger::make()->writeLog('error', "Gradio result cURL error: $curlError", [], null);
            return '';
        }

        if ($httpCode !== 200) {
            DBLogger::make()->writeLog('error', "Gradio result HTTP error", [
                'httpCode' => $httpCode,
                'response' => substr($response ?: '', 0, 500)
            ], null);
            return '';
        }

        return $this->parseGradioResponse($response);
    }

    /**
     * Make API request to Joy Caption (Gradio API) - DEPRECATED
     */
    private function makeAPIRequest(string $endpoint, array $payload, string $apiKey): string
    {
        $headers = [
            'Content-Type: application/json'
        ];
        
        // Add authorization if available (may not be needed for public spaces)
        if (!empty($apiKey)) {
            $headers[] = 'Authorization: Bearer ' . $apiKey;
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $endpoint);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        DBLogger::make()->writeLog('info', 'API request completed', [
            'endpoint' => $endpoint,
            'httpCode' => $httpCode,
            'hasError' => !empty($curlError),
            'responseLength' => strlen($response ?: '')
        ], null);

        if ($curlError) {
            DBLogger::make()->writeLog('error', "cURL error: $curlError", [], null);
            return '';
        }

        if ($httpCode !== 200) {
            DBLogger::make()->writeLog('error', "HTTP error", [
                'httpCode' => $httpCode,
                'response' => substr($response ?: '', 0, 500)
            ], null);
            return '';
        }

        return $this->parseGradioResponse($response);
    }
    
    /**
     * Make image caption request to HuggingFace Inference API
     */
    private function makeImageCaptionRequest(string $endpoint, string $imageData, string $apiKey): string
    {
        $headers = [
            'Authorization: Bearer ' . $apiKey,
            'Content-Type: application/octet-stream'
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $endpoint);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $imageData);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError || $httpCode !== 200) {
            DBLogger::make()->writeLog('error', "Inference API error", [
                'httpCode' => $httpCode,
                'curlError' => $curlError,
                'response' => substr($response ?: '', 0, 300)
            ], null);
            return '';
        }

        return $this->parseInferenceResponse($response);
    }
    
    /**
     * Parse Gradio API response
     */
    private function parseGradioResponse(string $response): string
    {
        // Handle Server-Sent Events (SSE) format from Gradio
        if (strpos($response, 'event:') !== false && strpos($response, 'data:') !== false) {
            return $this->parseSSEResponse($response);
        }
        
        // Try parsing as regular JSON first
        $data = json_decode($response, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            DBLogger::make()->writeLog('warning', 'Failed to parse Gradio JSON response', [
                'json_error' => json_last_error_msg(),
                'response_preview' => substr($response, 0, 200)
            ], null);
            return '';
        }
        
        // Gradio API typically returns: {"data": [result], "is_generating": false, ...}
        if (isset($data['data']) && is_array($data['data']) && !empty($data['data'])) {
            $result = $data['data'][0];
            
            if (is_string($result)) {
                $altText = $this->cleanAltTextResponse(trim($result));
                if (!empty($altText) && strlen($altText) > 5) {
                    DBLogger::make()->writeLog('info', "Successfully parsed Gradio response", [
                        'altTextLength' => strlen($altText)
                    ], null);
                    return $altText;
                }
            }
        }
        
        DBLogger::make()->writeLog('warning', 'No usable text found in Gradio response', [
            'response_structure' => is_array($data) ? array_keys($data) : 'not_array',
            'response_preview' => substr($response, 0, 300)
        ], null);
        
        return '';
    }
    
    /**
     * Parse Server-Sent Events response from Gradio
     */
    private function parseSSEResponse(string $response): string
    {
        $lines = explode("\n", $response);
        $lastDataContent = '';
        
        foreach ($lines as $line) {
            $line = trim($line);
            if (strpos($line, 'data:') === 0) {
                $dataJson = trim(substr($line, 5)); // Remove 'data:' prefix
                
                // Try to parse the JSON data
                $data = json_decode($dataJson, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($data) && !empty($data)) {
                    // Get the first element which should be the caption
                    $content = $data[0];
                    if (is_string($content) && !empty(trim($content))) {
                        $lastDataContent = trim($content);
                    }
                }
            }
        }
        
        if (!empty($lastDataContent)) {
            $cleanedContent = $this->cleanAltTextResponse($lastDataContent);
            if (!empty($cleanedContent)) {
                DBLogger::make()->writeLog('info', "Successfully parsed SSE response", [
                    'originalLength' => strlen($lastDataContent),
                    'cleanedLength' => strlen($cleanedContent)
                ], null);
                return $cleanedContent;
            }
        }
        
        DBLogger::make()->writeLog('warning', 'No usable content found in SSE response', [
            'response_preview' => substr($response, 0, 300)
        ], null);
        
        return '';
    }
    
    /**
     * Clean alt text response by removing unwanted prefixes and formatting
     */
    private function cleanAltTextResponse(string $text): string
    {
        $text = trim($text);
        
        // Remove common prefixes in different languages
        $prefixes = [
            // Chinese
            '以下是符合無障礙標準的 alt 文字：',
            '以下是符合無障礙標準的alt文字：',
            '以下是alt文字：',
            '這張圖片的alt文字是：',
            '圖片描述：',
            'alt文字：',
            'Alt文字：',
            '描述：',
            
            // English
            'Here is the alt text:',
            'The alt text is:',
            'Alt text:',
            'Image description:',
            'Description:',
            'Caption:',
            'This image shows:',
            'The image shows:',
            
            // Japanese
            'この画像のalt文字は：',
            'alt文字：',
            '画像の説明：',
            
            // Korean
            'alt 텍스트:',
            '이미지 설명:',
        ];
        
        foreach ($prefixes as $prefix) {
            if (stripos($text, $prefix) === 0) {
                $text = trim(substr($text, strlen($prefix)));
                break;
            }
        }
        
        // Remove quotes if the entire text is wrapped in quotes
        if ((substr($text, 0, 1) === '"' && substr($text, -1) === '"') ||
            (substr($text, 0, 1) === "'" && substr($text, -1) === "'") ||
            (substr($text, 0, 1) === '「' && substr($text, -1) === '」') ||
            (substr($text, 0, 1) === '『' && substr($text, -1) === '』')) {
            $text = trim(substr($text, 1, -1));
        }
        
        // Remove multiple consecutive spaces
        $text = preg_replace('/\s+/', ' ', $text);
        
        return trim($text);
    }
    
    /**
     * Parse HuggingFace Inference API response
     */
    private function parseInferenceResponse(string $response): string
    {
        $data = json_decode($response, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            DBLogger::make()->writeLog('error', 'Failed to parse Inference API JSON response', [
                'json_error' => json_last_error_msg(),
                'response_preview' => substr($response, 0, 200)
            ], null);
            return '';
        }
        
        // Inference API returns: [{"generated_text": "..."}, ...]
        if (is_array($data) && !empty($data) && isset($data[0]['generated_text'])) {
            $altText = trim($data[0]['generated_text']);
            
            if (!empty($altText)) {
                DBLogger::make()->writeLog('info', "Successfully generated alt text using Inference API", [
                    'altTextLength' => strlen($altText)
                ], null);
                return $altText;
            }
        }
        
        DBLogger::make()->writeLog('error', 'No generated text found in Inference API response', [
            'response_structure' => is_array($data) ? array_keys($data) : 'not_array'
        ], null);
        
        return '';
    }

    /**
     * Get language-specific instruction for the prompt
     */
    private function getLanguageInstruction(string $language): string
    {
        $instructions = [
            'en' => 'Write the description in English, keep it under 120 characters.',
            'zh' => '請用繁體中文描述，不超過50個中文字。',
            'ja' => '日本語で説明してください。120文字以内でお願いします。',
            'ko' => '한국어로 설명해주세요. 120자 이내로 작성해주세요.',
            'fr' => 'Écrivez la description en français, en moins de 120 caractères.',
            'de' => 'Beschreiben Sie auf Deutsch, unter 120 Zeichen.',
            'es' => 'Escriba la descripción en español, menos de 120 caracteres.',
            'it' => 'Scrivi la descrizione in italiano, sotto 120 caratteri.',
            'pt' => 'Escreva a descrição em português, menos de 120 caracteres.',
            'ru' => 'Опишите на русском языке, менее 120 символов.',
            'ar' => 'اكتب الوصف بالعربية، أقل من 120 حرفاً.'
        ];
        
        return $instructions[$language] ?? $instructions['en'];
    }
    
    /**
     * Limit alt text length based on language
     */
    private function limitAltTextLength(string $altText, string $language): string
    {
        $altText = trim($altText);
        
        // For Chinese, Japanese, Korean - limit to 50 characters
        if (in_array($language, ['zh', 'ja', 'ko'])) {
            if (mb_strlen($altText, 'UTF-8') > 50) {
                $altText = mb_substr($altText, 0, 47, 'UTF-8') . '...';
            }
        } else {
            // For other languages - limit to 120 characters
            if (strlen($altText) > 120) {
                $altText = substr($altText, 0, 117) . '...';
            }
        }
        
        return $altText;
    }

    public function getPrompt(): string
    {
        return get_option(Constants::CONTEXTUAL_ALT_TEXT_OPTION_FIELD_VISION_PROMPT, Constants::CONTEXTUAL_ALT_TEXT_DEFAULT_VISION_PROMPT);
    }
} 