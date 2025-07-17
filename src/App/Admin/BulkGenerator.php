<?php

namespace ContextualAltText\App\Admin;

use ContextualAltText\App\Setup;
use ContextualAltText\App\Logging\DBLogger;
use ContextualAltText\Config\Constants;

class BulkGenerator
{
    private static ?self $instance = null;

    private function __construct()
    {
        //
    }

    public static function register(): void
    {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }

        // Add admin menu
        add_action('admin_menu', [self::$instance, 'addAdminMenu']);
        
        // Handle AJAX requests
        add_action('wp_ajax_bulk_generate_alt_text', [self::$instance, 'handleBulkGenerate']);
        add_action('wp_ajax_get_post_images', [self::$instance, 'getPostImages']);
    }

    public function addAdminMenu(): void
    {
        add_submenu_page(
            'contextual-alt-text-options',
            __('Bulk Alt Text Generator', 'contextual-alt-text'),
            __('Bulk Generator', 'contextual-alt-text'),
            'manage_options',
            'contextual-alt-text-bulk',
            [self::$instance, 'renderBulkPage']
        );
    }

    public function renderBulkPage(): void
    {
        $plugin_enabled = get_option(Constants::CONTEXTUAL_ALT_TEXT_OPTION_FIELD_ENABLE_PLUGIN, false);
        
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Bulk Alt Text Generator', 'contextual-alt-text'); ?></h1>
            
            <?php if (!$plugin_enabled): ?>
                <div class="notice notice-warning">
                    <p><?php esc_html_e('Plugin is disabled. Please enable it in the settings to use bulk generation.', 'contextual-alt-text'); ?></p>
                    <p><a href="<?php echo admin_url('admin.php?page=contextual-alt-text-options'); ?>" class="button"><?php esc_html_e('Go to Settings', 'contextual-alt-text'); ?></a></p>
                </div>
            <?php else: ?>
                
                <div style="margin: 1em 0; padding: 1em; background-color: #f7f7f7; border-left: 4px solid #7e8993;">
                    <p><strong><?php esc_html_e('Bulk Generation Process:', 'contextual-alt-text'); ?></strong></p>
                    <ol>
                        <li><?php esc_html_e('Enter a Post ID to generate alt text for all images in that specific post.', 'contextual-alt-text'); ?></li>
                        <li><?php esc_html_e('The system will analyze each image with context from the post (title, content, categories, tags).', 'contextual-alt-text'); ?></li>
                        <li><?php esc_html_e('Alt text will be generated only for images that don\'t have existing alt text (unless forced).', 'contextual-alt-text'); ?></li>
                        <li><?php esc_html_e('Process runs in background to avoid timeouts.', 'contextual-alt-text'); ?></li>
                    </ol>
                    <p><strong><?php esc_html_e('Note:', 'contextual-alt-text'); ?></strong> <?php esc_html_e('This process may take several minutes for posts with many images.', 'contextual-alt-text'); ?></p>
                </div>

                <div class="postbox" style="margin-top: 20px;">
                    <div class="inside">
                        <h3><?php esc_html_e('Generate Alt Text for Post Images', 'contextual-alt-text'); ?></h3>
                        
                        <table class="form-table">
                            <tr>
                                <th scope="row">
                                    <label for="post_id"><?php esc_html_e('Post ID', 'contextual-alt-text'); ?></label>
                                </th>
                                <td>
                                    <input type="number" id="post_id" name="post_id" min="1" placeholder="<?php esc_attr_e('Enter Post ID', 'contextual-alt-text'); ?>" style="width: 200px;" />
                                    <button type="button" id="preview_post" class="button" style="margin-left: 10px;"><?php esc_html_e('Preview Images', 'contextual-alt-text'); ?></button>
                                    <p class="description"><?php esc_html_e('Enter the ID of the post whose images you want to process.', 'contextual-alt-text'); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="force_regenerate"><?php esc_html_e('Force Regenerate', 'contextual-alt-text'); ?></label>
                                </th>
                                <td>
                                    <input type="checkbox" id="force_regenerate" name="force_regenerate" />
                                    <label for="force_regenerate"><?php esc_html_e('Overwrite existing alt text', 'contextual-alt-text'); ?></label>
                                    <p class="description"><?php esc_html_e('Check this to regenerate alt text even for images that already have alt text.', 'contextual-alt-text'); ?></p>
                                </td>
                            </tr>
                        </table>

                        <div id="post_preview" style="display: none; margin: 20px 0; padding: 15px; background: #f9f9f9; border: 1px solid #ddd;">
                            <h4><?php esc_html_e('Post Information', 'contextual-alt-text'); ?></h4>
                            <div id="post_info"></div>
                            
                            <h4><?php esc_html_e('Images Found', 'contextual-alt-text'); ?></h4>
                            <div id="images_list"></div>
                        </div>

                        <p class="submit">
                            <button type="button" id="start_bulk_generate" class="button button-primary" disabled>
                                <?php esc_html_e('Start Bulk Generation', 'contextual-alt-text'); ?>
                            </button>
                            <span id="bulk_spinner" class="spinner" style="float: none; margin-left: 10px;"></span>
                        </p>

                        <div id="progress_container" style="display: none; margin: 20px 0;">
                            <h4><?php esc_html_e('Progress', 'contextual-alt-text'); ?></h4>
                            <div id="progress_bar" style="width: 100%; background: #f0f0f0; border: 1px solid #ddd; height: 20px; margin: 10px 0;">
                                <div id="progress_fill" style="height: 100%; background: #0073aa; width: 0%; transition: width 0.3s;"></div>
                            </div>
                            <div id="progress_text">0 / 0</div>
                            <div id="progress_log" style="max-height: 300px; overflow-y: auto; background: white; border: 1px solid #ddd; padding: 10px; margin: 10px 0;"></div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <script>
        jQuery(document).ready(function($) {
            let currentImages = [];
            
            $('#preview_post').on('click', function() {
                const postId = $('#post_id').val();
                if (!postId) {
                    alert('<?php esc_js_e('Please enter a Post ID', 'contextual-alt-text'); ?>');
                    return;
                }
                
                const button = $(this);
                button.prop('disabled', true).text('<?php esc_js_e('Loading...', 'contextual-alt-text'); ?>');
                
                $.post(ajaxurl, {
                    action: 'get_post_images',
                    post_id: postId,
                    nonce: '<?php echo wp_create_nonce('bulk_alt_text_nonce'); ?>'
                }, function(response) {
                    if (response.success) {
                        currentImages = response.data.images;
                        $('#post_info').html(response.data.post_info);
                        $('#images_list').html(response.data.images_html);
                        $('#post_preview').show();
                        $('#start_bulk_generate').prop('disabled', false);
                    } else {
                        alert('<?php esc_js_e('Error:', 'contextual-alt-text'); ?> ' + response.data);
                    }
                }).fail(function() {
                    alert('<?php esc_js_e('Network error occurred', 'contextual-alt-text'); ?>');
                }).always(function() {
                    button.prop('disabled', false).text('<?php esc_js_e('Preview Images', 'contextual-alt-text'); ?>');
                });
            });
            
            $('#start_bulk_generate').on('click', function() {
                const postId = $('#post_id').val();
                const forceRegenerate = $('#force_regenerate').is(':checked');
                
                if (!postId || currentImages.length === 0) {
                    alert('<?php esc_js_e('Please preview images first', 'contextual-alt-text'); ?>');
                    return;
                }
                
                if (!confirm('<?php esc_js_e('Start bulk generation? This may take several minutes.', 'contextual-alt-text'); ?>')) {
                    return;
                }
                
                startBulkGeneration(postId, forceRegenerate);
            });
            
            function startBulkGeneration(postId, forceRegenerate) {
                $('#bulk_spinner').addClass('is-active');
                $('#start_bulk_generate').prop('disabled', true);
                $('#progress_container').show();
                $('#progress_log').empty();
                
                let processed = 0;
                const total = currentImages.length;
                
                updateProgress(0, total, '<?php esc_js_e('Starting...', 'contextual-alt-text'); ?>');
                
                function processNextImage(index) {
                    if (index >= currentImages.length) {
                        // All done
                        $('#bulk_spinner').removeClass('is-active');
                        $('#start_bulk_generate').prop('disabled', false);
                        updateProgress(total, total, '<?php esc_js_e('Completed!', 'contextual-alt-text'); ?>');
                        return;
                    }
                    
                    const image = currentImages[index];
                    updateProgress(index, total, '<?php esc_js_e('Processing:', 'contextual-alt-text'); ?> ' + image.filename);
                    
                    $.post(ajaxurl, {
                        action: 'bulk_generate_alt_text',
                        post_id: postId,
                        attachment_id: image.id,
                        force_regenerate: forceRegenerate,
                        nonce: '<?php echo wp_create_nonce('bulk_alt_text_nonce'); ?>'
                    }, function(response) {
                        processed++;
                        
                        if (response.success) {
                            addToLog('✅ ' + image.filename + ': ' + response.data.message, 'success');
                        } else {
                            addToLog('❌ ' + image.filename + ': ' + response.data, 'error');
                        }
                        
                        updateProgress(processed, total);
                        
                        // Process next image after a short delay
                        setTimeout(() => processNextImage(index + 1), 1000);
                        
                    }).fail(function() {
                        processed++;
                        addToLog('❌ ' + image.filename + ': <?php esc_js_e('Network error', 'contextual-alt-text'); ?>', 'error');
                        updateProgress(processed, total);
                        
                        // Continue to next image even on error
                        setTimeout(() => processNextImage(index + 1), 1000);
                    });
                }
                
                processNextImage(0);
            }
            
            function updateProgress(current, total, message) {
                const percentage = total > 0 ? (current / total * 100) : 0;
                $('#progress_fill').css('width', percentage + '%');
                $('#progress_text').text(current + ' / ' + total + (message ? ' - ' + message : ''));
            }
            
            function addToLog(message, type) {
                const timestamp = new Date().toLocaleTimeString();
                const logEntry = $('<div>').addClass('log-entry ' + (type || 'info'))
                    .text('[' + timestamp + '] ' + message);
                $('#progress_log').append(logEntry).scrollTop($('#progress_log')[0].scrollHeight);
            }
        });
        </script>

        <style>
        .log-entry {
            padding: 2px 0;
            font-family: monospace;
            font-size: 12px;
        }
        .log-entry.success {
            color: #046b02;
        }
        .log-entry.error {
            color: #d63638;
        }
        </style>
        <?php
    }

    public function getPostImages(): void
    {
        check_ajax_referer('bulk_alt_text_nonce', 'nonce');

        $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
        if (!$post_id) {
            wp_send_json_error('Invalid Post ID');
            return;
        }

        $post = get_post($post_id);
        if (!$post) {
            wp_send_json_error('Post not found');
            return;
        }

        // Get all images attached to this post
        $attachments = get_attached_media('image', $post_id);
        
        // Also get images referenced in post content
        $content_images = $this->getImagesFromContent($post->post_content);
        
        // Merge and deduplicate
        $all_images = array_merge($attachments, $content_images);
        $unique_images = [];
        $image_ids = [];
        
        foreach ($all_images as $image) {
            if (!in_array($image->ID, $image_ids)) {
                $unique_images[] = $image;
                $image_ids[] = $image->ID;
            }
        }

        // Prepare post information
        $post_info = sprintf(
            '<p><strong>%s:</strong> %s</p><p><strong>%s:</strong> %s</p><p><strong>%s:</strong> %d %s</p>',
            __('Title', 'contextual-alt-text'),
            esc_html($post->post_title),
            __('Type', 'contextual-alt-text'),
            esc_html($post->post_type),
            __('Images Found', 'contextual-alt-text'),
            count($unique_images),
            __('images', 'contextual-alt-text')
        );

        // Prepare images HTML
        $images_html = '<div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 15px;">';
        $images_data = [];
        
        foreach ($unique_images as $image) {
            $thumb_url = wp_get_attachment_image_url($image->ID, 'thumbnail');
            $filename = basename(get_attached_file($image->ID));
            $existing_alt = get_post_meta($image->ID, '_wp_attachment_image_alt', true);
            
            $images_data[] = [
                'id' => $image->ID,
                'filename' => $filename,
                'has_alt' => !empty($existing_alt)
            ];
            
            $alt_status = !empty($existing_alt) ? 
                '<span style="color: green;">✅ ' . __('Has alt text', 'contextual-alt-text') . '</span>' : 
                '<span style="color: red;">❌ ' . __('No alt text', 'contextual-alt-text') . '</span>';
            
            $images_html .= sprintf(
                '<div style="border: 1px solid #ddd; padding: 10px; text-align: center;">
                    <img src="%s" style="width: 100%%; height: 100px; object-fit: cover;" />
                    <p style="margin: 5px 0; font-size: 12px; word-break: break-all;">%s</p>
                    <p style="margin: 0; font-size: 11px;">%s</p>
                </div>',
                esc_url($thumb_url),
                esc_html($filename),
                $alt_status
            );
        }
        $images_html .= '</div>';

        wp_send_json_success([
            'post_info' => $post_info,
            'images_html' => $images_html,
            'images' => $images_data
        ]);
    }

    public function handleBulkGenerate(): void
    {
        check_ajax_referer('bulk_alt_text_nonce', 'nonce');

        // Check if plugin is enabled
        if (!get_option(Constants::CONTEXTUAL_ALT_TEXT_OPTION_FIELD_ENABLE_PLUGIN, false)) {
            wp_send_json_error('Plugin is disabled');
            return;
        }

        $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
        $attachment_id = isset($_POST['attachment_id']) ? intval($_POST['attachment_id']) : 0;
        $force_regenerate = isset($_POST['force_regenerate']) && $_POST['force_regenerate'] === 'true';

        if (!$post_id || !$attachment_id) {
            wp_send_json_error('Invalid parameters');
            return;
        }

        // Check if image has alt text already
        $existing_alt = get_post_meta($attachment_id, '_wp_attachment_image_alt', true);
        if (!empty($existing_alt) && !$force_regenerate) {
            wp_send_json_success(['message' => 'Skipped (already has alt text)']);
            return;
        }

        // Set the attachment parent to the post for context
        wp_update_post([
            'ID' => $attachment_id,
            'post_parent' => $post_id
        ]);

        try {
            // Use the Setup class to generate alt text
            $setup = Setup::getInstance();
            $metadata = wp_get_attachment_metadata($attachment_id);
            if (empty($metadata)) {
                $metadata = [];
            }
            
            // Log the bulk generation attempt
            DBLogger::make()->writeLog('info', 'Bulk generation started', [
                'post_id' => $post_id,
                'attachment_id' => $attachment_id,
                'force_regenerate' => $force_regenerate
            ], $attachment_id);
            
            $setup->altText($metadata, $attachment_id);
            
            // Check if alt text was generated
            $new_alt = get_post_meta($attachment_id, '_wp_attachment_image_alt', true);
            if (!empty($new_alt)) {
                wp_send_json_success(['message' => 'Alt text generated successfully']);
            } else {
                wp_send_json_error('Failed to generate alt text');
            }
            
        } catch (\Exception $e) {
            DBLogger::make()->writeLog('error', 'Bulk generation failed: ' . $e->getMessage(), [
                'post_id' => $post_id,
                'attachment_id' => $attachment_id
            ], $attachment_id);
            
            wp_send_json_error('Exception: ' . $e->getMessage());
        }
    }

    private function getImagesFromContent(string $content): array
    {
        $images = [];
        
        // Find WordPress image blocks and shortcodes
        preg_match_all('/wp:image[^}]*"id":(\d+)/', $content, $block_matches);
        preg_match_all('/\[gallery[^]]*ids="([^"]+)"/', $content, $gallery_matches);
        preg_match_all('/wp-image-(\d+)/', $content, $class_matches);
        
        $image_ids = [];
        
        // From blocks
        if (!empty($block_matches[1])) {
            $image_ids = array_merge($image_ids, $block_matches[1]);
        }
        
        // From galleries
        if (!empty($gallery_matches[1])) {
            foreach ($gallery_matches[1] as $gallery_ids) {
                $ids = explode(',', $gallery_ids);
                $image_ids = array_merge($image_ids, array_map('trim', $ids));
            }
        }
        
        // From CSS classes
        if (!empty($class_matches[1])) {
            $image_ids = array_merge($image_ids, $class_matches[1]);
        }
        
        // Get post objects
        $image_ids = array_unique(array_filter($image_ids));
        foreach ($image_ids as $id) {
            $post = get_post($id);
            if ($post && wp_attachment_is_image($id)) {
                $images[] = $post;
            }
        }
        
        return $images;
    }
} 