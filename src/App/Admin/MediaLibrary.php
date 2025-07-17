<?php

namespace ContextualAltText\App\Admin;

use ContextualAltText\App\Setup;
use ContextualAltText\App\Utilities\AssetsManager;
use ContextualAltText\Config\Constants;

class MediaLibrary
{
    private static ?self $instance = null;
    private static AssetsManager $assetsManager;

    private function __construct()
    {
        //
    }

    public static function register(): void
    {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        self::$assetsManager = AssetsManager::make();

        add_action('admin_enqueue_scripts', [self::$instance, 'enqueue'], 1);

        // Render custom template in media modal
        add_action('print_media_templates', [self::$instance, 'renderGenerateButtonTemplate']);

        // Add button to generate alt text in media library
        add_filter('attachment_fields_to_edit', [self::$instance, 'addGenerateAltTextButton'], 10, 2);

        // Handle AJAX request to generate alt text
        add_action('wp_ajax_generate_alt_text', [self::$instance, 'generateAltText']);
    }

    public function enqueue(): void
    {
        $screen = get_current_screen();

        // Load script in Media Library and in any post editing/modal (all CPTs)
        if (! $screen || in_array($screen->base, ['upload', 'post'], true)) {
            $mediaLibraryJs = self::$assetsManager->getAssetUrl('assets/js/media-library.js', false);
            wp_enqueue_script(
                Constants::CONTEXTUAL_ALT_TEXT_PLUGIN_MEDIA_LIBRARY_HANDLE,
                $mediaLibraryJs,
                ['jquery'],
                false,
                true
            );

            wp_localize_script(
                Constants::CONTEXTUAL_ALT_TEXT_PLUGIN_MEDIA_LIBRARY_HANDLE,
                'CONTEXTUAL_ALT_TEXT',
                [
                    'altTextNonce' => wp_create_nonce(Constants::CONTEXTUAL_ALT_TEXT_AJAX_GENERATE_ALT_TEXT_NONCE),
                    'ajaxUrl'      => admin_url('admin-ajax.php'),
                ]
            );
        }
    }

    public function renderGenerateButtonTemplate(): void
    {
        ?>
        <script type="text/html" id="tmpl-contextual-alt-text-generate-alt-text">
            <# if ( data.type === 'image' ) { #>
            <button class="button contextual-alt-text-generate-alt-text" data-post-id="{{ data.id }}">
                <?php esc_html_e('Generate Alt Text', 'contextual-alt-text'); ?>
            </button>
            <span class="spinner"></span>
            <# } #>
        </script>

        <?php
    }

    public function addGenerateAltTextButton(array $form_fields, \WP_Post $post): array
    {
        if (! wp_attachment_is_image($post->ID)) {
            return $form_fields;
        }

        $mimeType = get_post_mime_type($post->ID);
        $altTextGenerationTypology = PluginOptions::typology();

        if (
            $altTextGenerationTypology === Constants::CONTEXTUAL_ALT_TEXT_OPTION_TYPOLOGY_CHOICE_OPENAI
            && ! in_array($mimeType, Constants::CONTEXTUAL_ALT_TEXT_OPENAI_ALLOWED_MIME_TYPES, true)
        ) {
            return $form_fields;
        }

        if (
            $altTextGenerationTypology === Constants::CONTEXTUAL_ALT_TEXT_OPTION_TYPOLOGY_CHOICE_AZURE
            && ! in_array($mimeType, Constants::CONTEXTUAL_ALT_TEXT_AZURE_ALLOWED_MIME_TYPES, true)
        ) {
            return $form_fields;
        }

        $form_fields['generate_alt_text'] = [
            'label' => get_post_mime_type($post->ID),
            'input' => 'html',
            'html'  => '<button type="button" class="button" id="generate-alt-text-button" data-post-id="' . $post->ID . '">'
                . esc_html__('Generate Alt Text', 'contextual-alt-text') .
                '</button><span id="loading-spinner" class="spinner" style="float:none; margin-left:5px; display:none;"></span>',
            'helps' => '',
        ];

        return $form_fields;
    }

    public function generateAltText(): void
    {
        check_ajax_referer(Constants::CONTEXTUAL_ALT_TEXT_AJAX_GENERATE_ALT_TEXT_NONCE, 'nonce');

        $postId = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
        if (! $postId) {
            wp_send_json_error('Invalid Post ID');
            return;
        }

        $mediaUrl = wp_get_attachment_url($postId);
        if (! $mediaUrl) {
            wp_send_json_error('Media not found');
            return;
        }

        // Call the altText method of the Setup instance
        $setup = Setup::getInstance();
        $metadata = wp_get_attachment_metadata($postId);
        $setup->altText($metadata, $postId);
        
        // Get the generated alt text
        $generatedAltText = get_post_meta($postId, '_wp_attachment_image_alt', true);
        
        if (empty($generatedAltText)) {
            wp_send_json_error('Failed to generate alt text');
            return;
        }
        
        wp_send_json_success(['alt_text' => $generatedAltText]);
    }
}
