<?php

namespace ContextualAltText\App\Utilities;

class ContextHelper
{
    /**
     * Get the context for a given attachment.
     *
     * @param  int $attachment_id The ID of the attachment.
     * @return array|null An array containing context information or null if no context is found.
     */
    public static function getAttachmentContext($attachment_id)
    {
        $attachment = get_post($attachment_id);

        if (empty($attachment) || empty($attachment->post_parent)) {
            return null;
        }

        $parent_post = get_post($attachment->post_parent);

        if (empty($parent_post)) {
            return null;
        }

        $context = [
            'post_title' => $parent_post->post_title,
            'post_content' => wp_strip_all_tags(strip_shortcodes($parent_post->post_content)),
        ];

        $post_type = get_post_type($parent_post);
        $taxonomies = get_object_taxonomies($post_type, 'objects');
        $terms_context = [];

        foreach ($taxonomies as $taxonomy) {
            if (!$taxonomy->public) {
                continue;
            }

            $terms = get_the_terms($parent_post->ID, $taxonomy->name);
            if (!empty($terms) && !is_wp_error($terms)) {
                $term_names = wp_list_pluck($terms, 'name');
                if (!empty($term_names)) {
                    $terms_context[$taxonomy->label] = implode(', ', $term_names);
                }
            }
        }

        if (!empty($terms_context)) {
            $context['terms'] = $terms_context;
        }

        // To avoid overly long content, we'll truncate it.
        if (strlen($context['post_content']) > 1000) {
            $context['post_content'] = mb_substr($context['post_content'], 0, 1000) . '...';
        }

        return $context;
    }
}
