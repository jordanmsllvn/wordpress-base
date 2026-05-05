<?php
/**
 * Site Core Pages API
 *
 * Exposes normalized page builder payloads for frontend clients.
 */

if (! defined('ABSPATH')) {
    exit;
}

add_action('rest_api_init', 'site_core_register_hydrate_route');
add_action('save_post', 'site_core_invalidate_page_cache', 10, 3);

/**
 * Register page hydrate endpoint.
 */
function site_core_register_hydrate_route()
{
    register_rest_route(
        'site/v1',
        '/page/(?P<slug>[a-zA-Z0-9-]+)',
        array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => 'site_core_get_hydrated_page',
            'permission_callback' => '__return_true',
            'args'                => array(
                'slug' => array(
                    'required'          => true,
                    'sanitize_callback' => 'sanitize_title',
                ),
            ),
        )
    );
}

/**
 * Return hydrated page content by slug.
 *
 * @param WP_REST_Request $request Request object.
 * @return WP_REST_Response|WP_Error
 */
function site_core_get_hydrated_page(WP_REST_Request $request)
{
    $slug = sanitize_title((string) $request->get_param('slug'));

    if ('' === $slug) {
        return new WP_Error('site_core_invalid_slug', 'Invalid page slug.', array('status' => 400));
    }

    $cache_key = site_core_get_page_cache_key($slug);
    $cached    = get_transient($cache_key);

    if (is_array($cached)) {
        return rest_ensure_response($cached);
    }

    $post = get_page_by_path($slug, OBJECT, 'page');

    if (! ($post instanceof WP_Post) || 'publish' !== $post->post_status) {
        return new WP_Error('site_core_page_not_found', 'Page not found.', array('status' => 404));
    }

    $raw_sections = array();

    if (function_exists('carbon_get_post_meta')) {
        $raw_sections = carbon_get_post_meta($post->ID, 'page_sections');
    }

    $data = array(
        'title'    => get_the_title($post),
        'slug'     => $post->post_name,
        'sections' => site_core_normalize_sections($raw_sections),
    );

    set_transient($cache_key, $data, 60);

    return rest_ensure_response($data);
}

/**
 * Invalidate cached page payload when page content updates.
 *
 * @param int     $post_id Post ID.
 * @param WP_Post $post    Post object.
 * @param bool    $update  Whether this is an update.
 */
function site_core_invalidate_page_cache($post_id, $post, $update)
{
    if (wp_is_post_revision($post_id) || wp_is_post_autosave($post_id)) {
        return;
    }

    if (! ($post instanceof WP_Post) || 'page' !== $post->post_type) {
        return;
    }

    $slug = sanitize_title($post->post_name);

    if ('' !== $slug) {
        delete_transient(site_core_get_page_cache_key($slug));
    }

    $old_slug = get_post_meta($post_id, '_wp_old_slug', true);

    if (is_string($old_slug) && '' !== $old_slug) {
        delete_transient(site_core_get_page_cache_key($old_slug));
    }
}
