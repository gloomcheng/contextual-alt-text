<?php

declare(strict_types=1);

namespace ContextualAltText\App\AIProviders\HuggingFace;

use ContextualAltText\App\Admin\PluginOptions;
use ContextualAltText\App\AIProviders\AITextProviderInterface;
use ContextualAltText\App\Logging\DBLogger;
use ContextualAltText\Config\Constants;

final class HuggingFaceText implements AITextProviderInterface
{
    /**
     * Process text content using HuggingFace text models
     *
     * @param  string $text The text content to process
     * @return string
     */
    public function response(string $text): string
    {
        $apiKey = PluginOptions::apiKeyHuggingFace();
        if (empty($apiKey)) {
            DBLogger::make()->writeLog('error', 'HuggingFace Text API key is empty', [], null);
            return '';
        }

        // Get model from settings, fallback to Llama 3.1 8B
        $selectedModel = get_option('cat_text_huggingface_model', Constants::CONTEXTUAL_ALT_TEXT_HF_LLAMA31_8B);
        
        // Use HuggingFace Inference Providers Router API
        $endpoint = 'https://router.huggingface.co/v1/chat/completions';
        
        DBLogger::make()->writeLog('info', 'Using text model for response: ' . $selectedModel, [], null);
        
        // Use OpenAI-compatible chat completions format
        $payload = [
            'model' => $selectedModel,
            'messages' => [
                [
                    'role' => 'user',
                    'content' => $text
                ]
            ],
            'max_tokens' => 100,
            'temperature' => 0.3
        ];

        $headers = [
            'Authorization: Bearer ' . $apiKey,
            'Content-Type: application/json'
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $endpoint);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            DBLogger::make()->writeLog('error', 'cURL error: ' . $curlError, [], null);
            return '';
        }

        if ($httpCode !== 200) {
            DBLogger::make()->writeLog('error', 'HuggingFace Text API error', [
                'httpCode' => $httpCode, 
                'response' => substr($response ?: '', 0, 500)
            ], null);
            return '';
        }

        $data = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            DBLogger::make()->writeLog('error', 'Invalid JSON response from HuggingFace Text API', [
                'json_error' => json_last_error_msg(),
                'response_sample' => substr($response ?: '', 0, 200)
            ], null);
            return '';
        }

        if (isset($data['error'])) {
            DBLogger::make()->writeLog('error', 'HuggingFace Text API returned error', ['error' => $data['error']], null);
            return '';
        }

        // Handle OpenAI-compatible response format from Router API
        if (isset($data['choices'][0]['message']['content'])) {
            $result = trim($data['choices'][0]['message']['content']);
            DBLogger::make()->writeLog('success', 'Text processing completed successfully', [
                'result_length' => strlen($result)
            ], null);
            return $result;
        }

        // Fallback for legacy format
        if (isset($data[0]['generated_text'])) {
            $result = trim($data[0]['generated_text']);
            DBLogger::make()->writeLog('success', 'Text processing completed successfully (legacy format)', [
                'result_length' => strlen($result)
            ], null);
            return $result;
        }

        DBLogger::make()->writeLog('error', 'Unexpected response format from HuggingFace Text API', [
            'response_structure' => array_keys($data ?? [])
        ], null);
        
        return '';
    }

    public function generateContextualAltText(string $imageDescription, array $context): string
    {
        $apiKey = PluginOptions::apiKeyHuggingFace();
        if (empty($apiKey)) {
            DBLogger::make()->writeLog('error', 'HuggingFace Text API key is empty', [], null);
            return '';
        }

        // Get model from settings, fallback to Llama 3.1 8B
        $selectedModel = get_option('cat_text_huggingface_model', Constants::CONTEXTUAL_ALT_TEXT_HF_LLAMA31_8B);
        
        // Use HuggingFace Inference Providers Router API
        $endpoint = 'https://router.huggingface.co/v1/chat/completions';
        
        DBLogger::make()->writeLog('info', 'Using text model: ' . $selectedModel, [], null);
        
        $selectedLanguage = PluginOptions::selectedLanguage();
        $prompt = $this->buildContextualPrompt($imageDescription, $context, $selectedLanguage);

        // Use OpenAI-compatible chat completions format
        $payload = [
            'model' => $selectedModel,
            'messages' => [
                [
                    'role' => 'user',
                    'content' => $prompt
                ]
            ],
            'max_tokens' => 100,
            'temperature' => 0.3
        ];

        $headers = [
            'Authorization: Bearer ' . $apiKey,
            'Content-Type: application/json'
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $endpoint);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        DBLogger::make()->writeLog('info', 'HuggingFace Text API request completed', [
            'httpCode' => $httpCode,
            'hasError' => !empty($curlError),
            'responseLength' => strlen($response ?: '')
        ], null);

        if ($curlError) {
            DBLogger::make()->writeLog('error', 'cURL error: ' . $curlError, [], null);
            return '';
        }

        if ($httpCode !== 200) {
            DBLogger::make()->writeLog('error', 'HuggingFace Text API error', [
                'httpCode' => $httpCode, 
                'response' => substr($response ?: '', 0, 500)
            ], null);
            return '';
        }

        $data = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            DBLogger::make()->writeLog('error', 'Invalid JSON response from HuggingFace Text API', [
                'json_error' => json_last_error_msg(),
                'response_sample' => substr($response ?: '', 0, 200)
            ], null);
            return '';
        }

        if (isset($data['error'])) {
            DBLogger::make()->writeLog('error', 'HuggingFace Text API returned error', ['error' => $data['error']], null);
            return '';
        }

        // Handle OpenAI-compatible response format from Router API
        if (isset($data['choices'][0]['message']['content'])) {
            $result = trim($data['choices'][0]['message']['content']);
            
            // Clean the response to remove unwanted prefixes and formatting
            $cleanedResult = $this->cleanAltTextResponse($result);
            
            DBLogger::make()->writeLog('success', 'Contextual alt text generated successfully', [
                'original_length' => strlen($result),
                'cleaned_length' => strlen($cleanedResult),
                'result_preview' => substr($cleanedResult, 0, 100)
            ], null);
            return $cleanedResult;
        }

        // Fallback: try direct response format
        if (isset($data[0]['generated_text'])) {
            $result = trim($data[0]['generated_text']);
            $cleanedResult = $this->cleanAltTextResponse($result);
            
            DBLogger::make()->writeLog('success', 'Contextual alt text generated (fallback format)', [
                'original_length' => strlen($result),
                'cleaned_length' => strlen($cleanedResult)
            ], null);
            return $cleanedResult;
        }

        DBLogger::make()->writeLog('error', 'Unexpected response format from HuggingFace Text API', [
            'response_structure' => array_keys($data ?? [])
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
            '根據上下文，',
            '基於文章內容，',
            
            // English
            'Here is the alt text:',
            'The alt text is:',
            'Alt text:',
            'Image description:',
            'Description:',
            'Caption:',
            'This image shows:',
            'The image shows:',
            'Based on the context:',
            'Given the context:',
            
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

    private function buildContextualPrompt(string $imageDescription, array $context, string $language): string
    {
        // Get user-defined prompt from settings
        $userPrompt = get_option(Constants::CONTEXTUAL_ALT_TEXT_OPTION_FIELD_TEXT_HUGGINGFACE_PROMPT, Constants::CONTEXTUAL_ALT_TEXT_HF_DEFAULT_TEXT_PROMPT);
        
        // Build context information
        $contextText = '';
        if (!empty($context['post_title'])) {
            $contextText .= "文章標題: " . $context['post_title'] . "\n";
        }
        if (!empty($context['post_content'])) {
            $contextText .= "文章內容摘要: " . substr(strip_tags($context['post_content']), 0, 200) . "...\n";
        }
        if (!empty($context['categories'])) {
            $contextText .= "分類: " . implode(', ', $context['categories']) . "\n";
        }
        if (!empty($context['tags'])) {
            $contextText .= "標籤: " . implode(', ', $context['tags']) . "\n";
        }
        if (!empty($context['post_type'])) {
            $contextText .= "內容類型: " . $context['post_type'] . "\n";
        }

        // Language mapping
        $languageNames = [
            'en' => 'English',
            'zh' => '繁體中文',
            'zh-tw' => '繁體中文',
            'zh-cn' => '简体中文',
            'ja' => '日本語',
            'ko' => '한국어',
            'es' => 'Español',
            'fr' => 'Français',
            'de' => 'Deutsch'
        ];
        
        $targetLanguage = $languageNames[$language] ?? 'English';

        // Character limits based on language
        $characterLimit = in_array($language, ['zh', 'zh-tw', 'zh-cn', 'ja', 'ko']) ? '50個字元' : '120個字元';
        
        // Improved prompt that emphasizes context usage and reduces repetitive prefixes
        $prompt = "你是一個專業的網頁無障礙專家。根據以下資訊，為圖片生成合適的alt文字：\n\n";
        $prompt .= "圖片描述: {$imageDescription}\n\n";
        
        if (!empty($contextText)) {
            $prompt .= "網頁上下文:\n{$contextText}\n";
            $prompt .= "請結合文章主題和內容，生成與上下文相關的alt文字。";
        } else {
            $prompt .= "沒有額外上下文資訊。";
        }
        
        $prompt .= "\n\n要求:\n";
        $prompt .= "- 語言: {$targetLanguage}\n";
        $prompt .= "- 字數限制: {$characterLimit}\n";
        $prompt .= "- 直接回傳alt文字內容，不要添加前綴或說明\n";
        $prompt .= "- 確保符合無障礙標準\n";
        
        if (!empty($contextText)) {
            $prompt .= "- 必須反映文章主題和內容的相關性\n";
        }
        
        return $prompt;
    }
} 