<?php

namespace ContextualAltText\App\AIProviders\OpenAI;

use ContextualAltText\App\Admin\PluginOptions;
use ContextualAltText\App\AIProviders\AIProviderInterface;
use ContextualAltText\App\Exceptions\OpenAI\OpenAIException;
use ContextualAltText\Config\Constants;

abstract class OpenAIResponse implements AIProviderInterface
{
    abstract public function response(string $imageUrl): string;

    /**
     * Send the request to the OpenAI APIs and return the decoded response
     *
     * @param  array  $requestBody
     * @param  string $endpoint
     * @return array
     * @throws OpenAIException
     */
    protected function decodedResponseBody(array $requestBody, string $endpoint): array
    {

        $apiKey = PluginOptions::apiKeyOpenAI();

        $headers = [
            'Authorization' => 'Bearer ' . $apiKey,
            'Content-Type' => 'application/json; charset=utf-8',
        ];

        $args = [
            'headers' => $headers,
            'timeout' => 90,
            'body'    => json_encode($requestBody),
            'method'  => 'POST',
            'data_format' => 'body',
        ];

        $response = wp_remote_post($endpoint, $args);

        if (is_wp_error($response)) {
            $error_message = $response->get_error_message();
            throw new OpenAIException("Something went wrong: $error_message");
        }

        $responseBody = wp_remote_retrieve_body($response);
        $decodedBody = json_decode($responseBody, true);

        if (isset($decodedBody['error'])) {
            throw new OpenAIException('Error type: ' . $decodedBody['error']['type'] . ' - Error code: ' . $decodedBody['error']['code'] . ' - ' . $decodedBody['error']['message']);
        }

        return $decodedBody;
    }

    /**
     * Return the main OpenAI prompt with language support
     *
     * @return string
     */
    protected function prompt(): string
    {
        $customPrompt = PluginOptions::prompt();
        if (!empty($customPrompt)) {
            return $customPrompt;
        }

        // Get selected language and generate appropriate prompt
        $selectedLanguage = PluginOptions::selectedLanguage();
        $languageName = PluginOptions::selectedLanguageName();

        return $this->generateLanguageSpecificPrompt($selectedLanguage, $languageName);
    }

    /**
     * Generate language-specific prompt for alt text generation
     *
     * @param  string $languageCode
     * @param  string $languageName
     * @return string
     */
    protected function generateLanguageSpecificPrompt(string $languageCode, string $languageName): string
    {
        $basePrompt = "Act like an SEO expert and write an alt text of up to 125 characters for this image.";

        // Language-specific prompts
        $languagePrompts = [
            'en' => $basePrompt,
            'zh' => "作为 SEO 专家，为这张图片写一个不超过 125 个字符的中文 alt 文字描述。",
            'zh-tw' => "作為 SEO 專家，為這張圖片寫一個不超過 125 個字符的繁體中文 alt 文字描述。",
            'ja' => "SEOエキスパートとして、この画像に対して125文字以内の日本語のalt文字を書いてください。",
            'ko' => "SEO 전문가로서 이 이미지에 대해 125자 이내의 한국어 alt 텍스트를 작성해주세요.",
            'es' => "Actúa como un experto en SEO y escribe un texto alt de hasta 125 caracteres en español para esta imagen.",
            'fr' => "Agissez comme un expert SEO et rédigez un texte alt de maximum 125 caractères en français pour cette image.",
            'de' => "Handeln Sie als SEO-Experte und schreiben Sie einen Alt-Text von bis zu 125 Zeichen auf Deutsch für dieses Bild.",
            'it' => "Agisci come un esperto SEO e scrivi un testo alt di massimo 125 caratteri in italiano per questa immagine.",
            'pt' => "Atue como um especialista em SEO e escreva um texto alt de até 125 caracteres em português para esta imagem.",
            'ru' => "Действуйте как эксперт по SEO и напишите alt-текст до 125 символов на русском языке для этого изображения.",
            'ar' => "تصرف كخبير SEO واكتب نص alt لا يزيد عن 125 حرفًا باللغة العربية لهذه الصورة.",
            'hi' => "SEO विशेषज्ञ के रूप में कार्य करें और इस छवि के लिए 125 अक्षरों तक का हिंदी alt टेक्स्ट लिखें।",
            'th' => "ทำหน้าที่เป็นผู้เชี่ยวชาญ SEO และเขียนข้อความ alt ไม่เกิน 125 ตัวอักษรเป็นภาษาไทยสำหรับรูปภาพนี้",
            'vi' => "Hãy hoạt động như một chuyên gia SEO và viết văn bản alt tối đa 125 ký tự bằng tiếng Việt cho hình ảnh này.",
        ];

        // Return language-specific prompt or fallback to English with language instruction
        if (isset($languagePrompts[$languageCode])) {
            return $languagePrompts[$languageCode];
        }

        // Fallback: English prompt with language instruction
        return "Act like an SEO expert and write an alt text of up to 125 characters for this image in {$languageName}.";
    }

    /**
     * @param  string $text
     * @return string
     */
    protected function cleanString(string $text): string
    {
        $patterns = [
            '/\"/',        // Double quotes
            '/\s\s+/',     // Double or more consecutive white spaces
            '/&quot;/'     // HTML sequence for double quotes
        ];

        return trim(preg_replace($patterns, '', $text));
    }

    protected function prepareRequestBody(string $model, string $prompt, string $imageUrl): array
    {
        // Get context information from the current post/page
        $context = $this->getImageContext();
        $contextualPrompt = $this->enhancePromptWithContext($prompt, $context);

        return [
            'model' => Constants::CONTEXTUAL_ALT_TEXT_OPENAI_VISION_MODEL,
            'messages' => [
                [
                    'role' => 'user',
                    'content' => [
                        [
                            "type" => "text",
                            "text" => $contextualPrompt
                        ],
                        [
                            "type" => "image_url",
                            "image_url" => [
                                "url" => $imageUrl
                            ]
                        ]
                    ]
                ],
            ],
            'max_tokens' => Constants::CONTEXTUAL_ALT_TEXT_OPENAI_MAX_TOKENS,
        ];
    }

    /**
     * Get context information from the current post/page
     *
     * @return array
     */
    protected function getImageContext(): array
    {
        $context = [
            'post_title' => '',
            'post_content' => '',
            'post_type' => '',
            'categories' => [],
            'tags' => []
        ];

        // Try to get post context from various sources
        $post_id = $this->getCurrentPostId();

        if ($post_id) {
            $post = get_post($post_id);
            if ($post) {
                $context['post_title'] = $post->post_title;
                $context['post_content'] = wp_strip_all_tags($post->post_content);
                $context['post_type'] = $post->post_type;

                // Get categories and tags
                if ($post->post_type === 'post') {
                    $categories = get_the_category($post_id);
                    $context['categories'] = array_map(
                        function ($cat) {
                            return $cat->name;
                        },
                        $categories
                    );

                    $tags = get_the_tags($post_id);
                    if ($tags) {
                        $context['tags'] = array_map(
                            function ($tag) {
                                return $tag->name;
                            },
                            $tags
                        );
                    }
                }
            }
        }

        return $context;
    }

    /**
     * Get current post ID from various sources
     *
     * @return int|null
     */
    protected function getCurrentPostId(): ?int
    {
        // Try to get post ID from various WordPress globals and contexts
        global $post, $wp_query;

        // Method 1: Current post in the loop
        if (isset($post) && $post instanceof \WP_Post) {
            return $post->ID;
        }

        // Method 2: From WP_Query
        if (isset($wp_query) && $wp_query->is_single()) {
            return $wp_query->get_queried_object_id();
        }

        // Method 3: From $_GET parameters (when editing)
        if (isset($_GET['post']) && is_numeric($_GET['post'])) {
            return intval($_GET['post']);
        }

        // Method 4: From $_POST parameters (when uploading via media library in post editor)
        if (isset($_POST['post_id']) && is_numeric($_POST['post_id'])) {
            return intval($_POST['post_id']);
        }

        // Method 5: From HTTP_REFERER (last resort)
        if (isset($_SERVER['HTTP_REFERER'])) {
            $referer_post_id = url_to_postid($_SERVER['HTTP_REFERER']);
            if ($referer_post_id) {
                return $referer_post_id;
            }
        }

        return null;
    }

    /**
     * Enhance prompt with contextual information
     *
     * @param  string $basePrompt
     * @param  array  $context
     * @return string
     */
    protected function enhancePromptWithContext(string $basePrompt, array $context): string
    {
        if (empty($context['post_title']) && empty($context['post_content'])) {
            return $basePrompt;
        }

        $selectedLanguage = PluginOptions::selectedLanguage();

        // Build context string
        $contextInfo = [];

        if (!empty($context['post_title'])) {
            $contextInfo[] = "Article title: " . $context['post_title'];
        }

        if (!empty($context['post_content'])) {
            // Limit content to first 500 characters to avoid token limits
            $content = substr($context['post_content'], 0, 500);
            if (strlen($context['post_content']) > 500) {
                $content .= '...';
            }
            $contextInfo[] = "Article content: " . $content;
        }

        if (!empty($context['categories'])) {
            $contextInfo[] = "Categories: " . implode(', ', $context['categories']);
        }

        if (!empty($context['tags'])) {
            $contextInfo[] = "Tags: " . implode(', ', $context['tags']);
        }

        $contextString = implode("\n", $contextInfo);

        // Enhance prompt based on language
        $enhancedPrompts = [
            'en' => "Context information:\n{$contextString}\n\nBased on the above context, {$basePrompt} Make sure the alt text is relevant to the article content and context.",
            'zh' => "上下文資訊：\n{$contextString}\n\n根據以上上下文，{$basePrompt} 確保 alt 文字與文章內容和上下文相關。",
            'zh-tw' => "上下文資訊：\n{$contextString}\n\n根據以上上下文，{$basePrompt} 確保 alt 文字與文章內容和上下文相關。",
        ];

        return $enhancedPrompts[$selectedLanguage] ?? $enhancedPrompts['en'];
    }
}
