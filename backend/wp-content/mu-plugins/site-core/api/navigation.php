<?php
/**
 * Site Core Navigation API
 *
 * Exposes global navigation data for headless consumers.
 */

if (! defined('ABSPATH')) {
    exit;
}

add_action('rest_api_init', 'site_core_register_navigation_route');

/**
 * Register navigation endpoint for headless consumers.
 */
function site_core_register_navigation_route()
{
    register_rest_route(
        'site/v1',
        '/navigation',
        array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => 'site_core_get_navigation_payload',
            'permission_callback' => '__return_true',
        )
    );
}

/**
 * Return global header/footer links from options.
 *
 * @return WP_REST_Response
 */
function site_core_get_navigation_payload()
{
    $header_links = array();
    $footer_links = array();

    if (function_exists('carbon_get_theme_option')) {
        $header_links = carbon_get_theme_option('site_core_header_links');
        $footer_links = carbon_get_theme_option('site_core_footer_links');
    }

    return rest_ensure_response(
        array(
            'headerLinks' => site_core_normalize_navigation_links($header_links),
            'footerLinks' => site_core_normalize_navigation_links($footer_links),
        )
    );
}

/**
 * Normalize a recursive navigation payload.
 *
 * @param mixed $links Raw data from Carbon Fields.
 * @return array<int, array<string, mixed>>
 */
function site_core_normalize_navigation_links($links)
{
    if (! is_array($links)) {
        return array();
    }

    $normalized = array();

    foreach ($links as $link) {
        if (! is_array($link)) {
            continue;
        }

        $item = array(
            'label' => isset($link['label']) ? sanitize_text_field($link['label']) : '',
            'url'   => isset($link['url']) ? esc_url_raw($link['url']) : '',
        );

        if (isset($link['children'])) {
            $item['children'] = site_core_normalize_navigation_links($link['children']);
        }

        $normalized[] = $item;
    }

    return $normalized;
}
