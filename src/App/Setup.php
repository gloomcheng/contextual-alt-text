<?php

namespace ContextualAltText\App;


use ContextualAltText\App\AIProviders\HuggingFace\HuggingFaceVision;
use ContextualAltText\App\AIProviders\HuggingFace\HuggingFaceText;

use ContextualAltText\App\Admin\PluginOptions;
use ContextualAltText\App\Logging\DBLogger;
use ContextualAltText\Config\Constants;

class Setup
{
    private static $instance;

    public static function getInstance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct()
    {
        // Primary hook for when attachment metadata is generated
        add_filter('wp_generate_attachment_metadata', [$this, 'altText'], 10, 2);
        
        // Secondary hook for when new attachments are added (backup)
        add_action('add_attachment', [$this, 'generateAltTextForNewAttachment'], 10, 1);
        
        // Hook for when images are inserted into posts/pages
        add_action('wp_insert_attachment', [$this, 'generateAltTextForNewAttachment'], 10, 1);
        
        // Additional hooks for block editor compatibility
        add_action('wp_ajax_upload-attachment', [$this, 'handleBlockEditorUpload'], 5);
        add_action('wp_ajax_nopriv_upload-attachment', [$this, 'handleBlockEditorUpload'], 5);
        
        // Hook for REST API uploads (used by block editor)
        add_action('rest_after_insert_attachment', [$this, 'handleRestApiUpload'], 10, 3);
        
        // Ensure alt text is generated after upload completion
        add_action('wp_update_attachment_metadata', [$this, 'ensureAltTextAfterUpload'], 10, 2);
        
        // Register cron hook for scheduled alt text generation
        add_action('contextual_alt_text_generate', [$this, 'scheduledAltTextGeneration']);
    }

    /**
     * Main function to generate alt text for an attachment.
     * Follows a 3-stage process:
     * 1. Generate a description from the image (Vision Provider).
     * 2. Refine the description into alt text (Text Provider).
     * 3. Translate the alt text if needed.
     *
     * @param  array $metadata      An array of attachment metadata.
     * @param  int   $attachment_id The attachment ID.
     * @return array The updated attachment metadata.
     */
    public function altText($metadata, $attachment_id)
    {
        // 調試日誌：記錄方法被調用
        DBLogger::make()->writeLog('info', 'altText method called', [
            'attachment_id' => $attachment_id,
            'metadata_exists' => !empty($metadata)
        ], $attachment_id);
        
        // Early return if plugin is disabled
        if (!get_option(Constants::CONTEXTUAL_ALT_TEXT_OPTION_FIELD_ENABLE_PLUGIN, false)) {
            DBLogger::make()->writeLog('warning', 'Plugin is disabled, skipping alt text generation', [], $attachment_id);
            return $metadata;
        }

        // Skip if the attachment is not an image
        if (!wp_attachment_is_image($attachment_id)) {
            DBLogger::make()->writeLog('info', 'Attachment is not an image, skipping', [], $attachment_id);
            return $metadata;
        }

        // Skip if alt text already exists and preserve setting is enabled
        $existing_alt = get_post_meta($attachment_id, '_wp_attachment_image_alt', true);
        if (!empty($existing_alt) && get_option(Constants::CONTEXTUAL_ALT_TEXT_OPTION_FIELD_PRESERVE_EXISTING_ALT_TEXT, false)) {
            DBLogger::make()->writeLog('info', 'Skipping: Alt text already exists and preserve setting is enabled', [], $attachment_id);
            return $metadata;
        }

        // Get the image URL
        $image_url = wp_get_attachment_url($attachment_id);
        if (!$image_url) {
            DBLogger::make()->writeLog('error', 'Could not get attachment URL', [], $attachment_id);
            return $metadata;
        }

        DBLogger::make()->writeLog('info', 'Starting alt text generation', ['image_url' => $image_url], $attachment_id);

        try {
            // Stage 1: Generate image description using vision model
            $vision_provider = new HuggingFaceVision();
            
            // Ensure vision model is set
            $vision_model = get_option(Constants::CONTEXTUAL_ALT_TEXT_OPTION_FIELD_VISION_HUGGINGFACE_MODEL);
            if (empty($vision_model)) {
                update_option(Constants::CONTEXTUAL_ALT_TEXT_OPTION_FIELD_VISION_HUGGINGFACE_MODEL, Constants::CONTEXTUAL_ALT_TEXT_HF_JOY_CAPTION_BETA);
                DBLogger::make()->writeLog('info', 'Set default vision model to Joy Caption Beta One', [], $attachment_id);
            }
            
            $image_description = $vision_provider->response($image_url);
            
            if (empty($image_description)) {
                DBLogger::make()->writeLog('error', 'Failed to generate image description', [], $attachment_id);
                return $metadata;
            }
            
            DBLogger::make()->writeLog('success', 'Generated image description', ['description' => $image_description], $attachment_id);
            
            // Stage 2: Generate contextual alt text using text model
            $context = $this->getImageContext($attachment_id);
            
            // Log context information for debugging
            DBLogger::make()->writeLog('debug', 'Context information collected', [
                'attachment_id' => $attachment_id,
                'post_title' => $context['post_title'] ?? 'none',
                'post_content_length' => isset($context['post_content']) ? strlen($context['post_content']) : 0,
                'categories' => $context['categories'] ?? [],
                'tags' => $context['tags'] ?? [],
                'post_type' => $context['post_type'] ?? 'none'
            ], $attachment_id);
            
            $final_alt_text = $image_description; // Default to image description
            
            // Check if contextual awareness is enabled and context is available
            if (PluginOptions::is_contextual_awareness_enabled() && 
                (!empty($context['post_title']) || !empty($context['post_content']))) {
                $text_model = $this->getTextProvider();
                if ($text_model) {
                    $final_alt_text = $text_model->generateContextualAltText($image_description, $context);
                    DBLogger::make()->writeLog('success', 'Generated contextual alt text', [
                        'alt_text' => $final_alt_text,
                        'context_available' => !empty($context['post_title']) || !empty($context['post_content'])
                    ], $attachment_id);
                } else {
                    DBLogger::make()->writeLog('warning', 'No text provider available, using image description', [], $attachment_id);
                }
            } else {
                if (!PluginOptions::is_contextual_awareness_enabled()) {
                    DBLogger::make()->writeLog('info', 'Contextual awareness disabled, using image description', [], $attachment_id);
                } else {
                    DBLogger::make()->writeLog('info', 'No context available, using image description', [], $attachment_id);
                }
            }
            
            // Save alt text
            update_post_meta($attachment_id, '_wp_attachment_image_alt', $final_alt_text);
            DBLogger::make()->writeLog('success', 'Alt text saved successfully', ['final_alt_text' => $final_alt_text], $attachment_id);
            
        } catch (\Exception $e) {
            DBLogger::make()->writeLog('error', 'Exception during alt text generation: ' . $e->getMessage(), [], $attachment_id);
        }

        return $metadata;
    }

    /**
     * Backup method to generate alt text for new attachments
     * Called when add_attachment or wp_insert_attachment hooks are triggered
     *
     * @param  int $attachment_id The attachment ID.
     * @return void
     */
    public function generateAltTextForNewAttachment($attachment_id)
    {
        // Skip if plugin is disabled
        if (!get_option(Constants::CONTEXTUAL_ALT_TEXT_OPTION_FIELD_ENABLE_PLUGIN, false)) {
            return;
        }

        // Skip if the attachment is not an image
        if (!wp_attachment_is_image($attachment_id)) {
            return;
        }

        // Skip if alt text already exists
        $existing_alt = get_post_meta($attachment_id, '_wp_attachment_image_alt', true);
        if (!empty($existing_alt)) {
            return;
        }

        // Get current metadata and call main altText method
        $metadata = wp_get_attachment_metadata($attachment_id);
        if (empty($metadata)) {
            $metadata = []; // Provide empty array if no metadata exists yet
        }
        
        // Call the main altText method
        $this->altText($metadata, $attachment_id);
    }

    /**
     * Get the selected text provider based on user settings
     *
     * @return \ContextualAltText\App\AIProviders\AITextProviderInterface|null
     */
    private function getTextProvider(): ?object
    {
        // First check if text provider is set to HuggingFace
        $text_provider = get_option(Constants::CONTEXTUAL_ALT_TEXT_OPTION_FIELD_TEXT_PROVIDER, '');
        
        if ($text_provider === Constants::CONTEXTUAL_ALT_TEXT_PROVIDER_HUGGINGFACE) {
            // Get the specific model
            $selected_model = get_option(Constants::CONTEXTUAL_ALT_TEXT_OPTION_FIELD_TEXT_HUGGINGFACE_MODEL, '');
            
            DBLogger::make()->writeLog('info', 'Text provider check', [
                'text_provider' => $text_provider,
                'selected_model' => $selected_model
            ], null);
            
            // Support both specific models and generic HuggingFace setting
            if ($selected_model === Constants::CONTEXTUAL_ALT_TEXT_HF_LLAMA31_8B ||
                !empty($selected_model)) { // Allow any HuggingFace model
                
                try {
                    $textProvider = new HuggingFaceText();
                    DBLogger::make()->writeLog('success', 'Text provider created successfully', [
                        'provider_class' => get_class($textProvider),
                        'model' => $selected_model
                    ], null);
                    return $textProvider;
                } catch (\Exception $e) {
                    DBLogger::make()->writeLog('error', 'Failed to create text provider', [
                        'error' => $e->getMessage(),
                        'model' => $selected_model
                    ], null);
                    return null;
                }
            } else {
                // If no model is set, default to Llama 3.1 8B
                update_option(Constants::CONTEXTUAL_ALT_TEXT_OPTION_FIELD_TEXT_HUGGINGFACE_MODEL, Constants::CONTEXTUAL_ALT_TEXT_HF_LLAMA31_8B);
                DBLogger::make()->writeLog('info', 'Auto-set text model to Llama 3.1 8B', [], null);
                
                try {
                    return new HuggingFaceText();
                } catch (\Exception $e) {
                    DBLogger::make()->writeLog('error', 'Failed to create default text provider', [
                        'error' => $e->getMessage()
                    ], null);
                    return null;
                }
            }
        }
        
        DBLogger::make()->writeLog('warning', 'No valid text provider configured', [
            'text_provider' => $text_provider
        ], null);
        return null;
    }

    /**
     * Get context information from the current post/page for the given attachment
     * Made public for testing purposes
     *
     * @param  int $attachment_id
     * @return array
     */
    public function getImageContext(int $attachment_id): array
    {
        $context = [
            'post_title' => '',
            'post_content' => '',
            'post_type' => '',
            'categories' => [],
            'tags' => [],
            'surrounding_text' => ''
        ];

        // Try to get context from the post the image is attached to
        $post = get_post($attachment_id);
        if ($post && $post->post_parent) {
            $parent_post = get_post($post->post_parent);
            if ($parent_post) {
                $context['post_title'] = $parent_post->post_title;
                $context['post_content'] = $parent_post->post_content;
                $context['post_type'] = $parent_post->post_type;
                
                $categories = get_the_category($parent_post->ID);
                if ($categories) {
                    $context['categories'] = wp_list_pluck($categories, 'name');
                }
                
                $tags = get_the_tags($parent_post->ID);
                if ($tags) {
                    $context['tags'] = wp_list_pluck($tags, 'name');
                }
                
                // Try to extract surrounding text context from image caption or description
                if (!empty($post->post_excerpt)) {
                    $context['surrounding_text'] = $post->post_excerpt;
                } elseif (!empty($post->post_content)) {
                    $context['surrounding_text'] = wp_strip_all_tags($post->post_content);
                }
            }
        }

        // If no parent post, try to get context from current page/post being edited
        if (empty($context['post_title']) && isset($_POST['post_ID'])) {
            $current_post = get_post(intval($_POST['post_ID']));
            if ($current_post) {
                $context['post_title'] = $current_post->post_title;
                $context['post_content'] = $current_post->post_content;
                $context['post_type'] = $current_post->post_type;
                
                $categories = get_the_category($current_post->ID);
                if ($categories) {
                    $context['categories'] = wp_list_pluck($categories, 'name');
                }
                
                $tags = get_the_tags($current_post->ID);
                if ($tags) {
                    $context['tags'] = wp_list_pluck($tags, 'name');
                }
            }
        }

        // Also check for modern Block Editor context (for Gutenberg)
        if (empty($context['post_title']) && isset($_SERVER['HTTP_REFERER'])) {
            $referer = $_SERVER['HTTP_REFERER'];
            if (preg_match('/[?&]post=(\d+)/', $referer, $matches)) {
                $post_id = intval($matches[1]);
                $current_post = get_post($post_id);
                if ($current_post) {
                    $context['post_title'] = $current_post->post_title;
                    $context['post_content'] = $current_post->post_content;
                    $context['post_type'] = $current_post->post_type;
                    
                    $categories = get_the_category($current_post->ID);
                    if ($categories) {
                        $context['categories'] = wp_list_pluck($categories, 'name');
                    }
                    
                    $tags = get_the_tags($current_post->ID);
                    if ($tags) {
                        $context['tags'] = wp_list_pluck($tags, 'name');
                    }
                }
            }
        }

        return $context;
    }

    /**
     * Handle block editor upload via AJAX
     */
    public function handleBlockEditorUpload()
    {
        // This hook runs early in the upload process
        // We'll use a different approach - schedule the alt text generation
        add_action('shutdown', [$this, 'processRecentUploads']);
    }

    /**
     * Handle REST API uploads (used by block editor)
     */
    public function handleRestApiUpload($attachment, $request, $creating)
    {
        if (!$creating || !wp_attachment_is_image($attachment->ID)) {
            return;
        }

        DBLogger::make()->writeLog('info', 'REST API upload detected', [
            'attachment_id' => $attachment->ID,
            'attachment_title' => $attachment->post_title
        ], $attachment->ID);

        // Schedule alt text generation with a slight delay to ensure metadata is ready
        wp_schedule_single_event(time() + 5, 'contextual_alt_text_generate', [$attachment->ID]);
    }

    /**
     * Ensure alt text is generated after metadata update
     */
    public function ensureAltTextAfterUpload($metadata, $attachment_id)
    {
        // Skip if plugin is disabled
        if (!get_option(Constants::CONTEXTUAL_ALT_TEXT_OPTION_FIELD_ENABLE_PLUGIN, false)) {
            return $metadata;
        }

        // Skip if the attachment is not an image
        if (!wp_attachment_is_image($attachment_id)) {
            return $metadata;
        }

        // Check if alt text already exists
        $existing_alt = get_post_meta($attachment_id, '_wp_attachment_image_alt', true);
        if (!empty($existing_alt)) {
            return $metadata;
        }

        DBLogger::make()->writeLog('info', 'Metadata update detected, ensuring alt text generation', [
            'attachment_id' => $attachment_id
        ], $attachment_id);

        // Generate alt text asynchronously to avoid blocking the upload
        wp_schedule_single_event(time() + 2, 'contextual_alt_text_generate', [$attachment_id]);

        return $metadata;
    }

    /**
     * Process recent uploads for alt text generation
     */
    public function processRecentUploads()
    {
        // Skip if plugin is disabled
        if (!get_option(Constants::CONTEXTUAL_ALT_TEXT_OPTION_FIELD_ENABLE_PLUGIN, false)) {
            return;
        }

        // Get recent uploads from the last 5 minutes without alt text
        $recent_uploads = get_posts([
            'post_type' => 'attachment',
            'post_mime_type' => 'image',
            'meta_query' => [
                [
                    'key' => '_wp_attachment_image_alt',
                    'compare' => 'NOT EXISTS'
                ]
            ],
            'date_query' => [
                [
                    'after' => '5 minutes ago'
                ]
            ],
            'posts_per_page' => 10,
            'orderby' => 'date',
            'order' => 'DESC'
        ]);

        foreach ($recent_uploads as $upload) {
            DBLogger::make()->writeLog('info', 'Processing recent upload for alt text', [
                'attachment_id' => $upload->ID
            ], $upload->ID);

                         // Schedule alt text generation
             wp_schedule_single_event(time() + 1, 'contextual_alt_text_generate', [$upload->ID]);
         }
     }

    /**
     * Handle scheduled alt text generation
     */
    public function scheduledAltTextGeneration($attachment_id)
    {
        DBLogger::make()->writeLog('info', 'Scheduled alt text generation triggered', [
            'attachment_id' => $attachment_id
        ], $attachment_id);

        // Skip if plugin is disabled
        if (!get_option(Constants::CONTEXTUAL_ALT_TEXT_OPTION_FIELD_ENABLE_PLUGIN, false)) {
            DBLogger::make()->writeLog('warning', 'Plugin disabled, skipping scheduled generation', [], $attachment_id);
            return;
        }

        // Skip if the attachment is not an image
        if (!wp_attachment_is_image($attachment_id)) {
            DBLogger::make()->writeLog('info', 'Not an image, skipping scheduled generation', [], $attachment_id);
            return;
        }

        // Check if alt text already exists
        $existing_alt = get_post_meta($attachment_id, '_wp_attachment_image_alt', true);
        if (!empty($existing_alt)) {
            DBLogger::make()->writeLog('info', 'Alt text already exists, skipping scheduled generation', [], $attachment_id);
            return;
        }

        // Get metadata and generate alt text
        $metadata = wp_get_attachment_metadata($attachment_id);
        if (empty($metadata)) {
            $metadata = [];
        }

        DBLogger::make()->writeLog('info', 'Starting scheduled alt text generation', [
            'attachment_id' => $attachment_id
        ], $attachment_id);

        try {
            $this->altText($metadata, $attachment_id);
        } catch (\Exception $e) {
            DBLogger::make()->writeLog('error', 'Scheduled alt text generation failed: ' . $e->getMessage(), [
                'attachment_id' => $attachment_id
            ], $attachment_id);
        }
    }
}
