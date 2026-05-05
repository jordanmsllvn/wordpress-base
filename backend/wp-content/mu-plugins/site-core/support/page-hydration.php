<?php
/**
 * Site Core Page hydration helpers.
 */

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Build transient key for page cache.
 *
 * @param string $slug Page slug.
 * @return string
 */
function site_core_get_page_cache_key($slug)
{
    return 'page_' . sanitize_key($slug);
}

/**
 * Normalize a set of Carbon Fields sections recursively.
 *
 * @param mixed $sections Raw section data.
 * @return array<int, array<string, mixed>>
 */
function site_core_normalize_sections($sections)
{
    if (! is_array($sections)) {
        return array();
    }

    $normalized = array();

    foreach ($sections as $section) {
        $normalized_section = site_core_normalize_single_section($section);

        if (is_array($normalized_section)) {
            $normalized[] = $normalized_section;
        }
    }

    return $normalized;
}

/**
 * Normalize one component item.
 *
 * @param mixed $section Raw section data.
 * @return array<string, mixed>|null
 */
function site_core_normalize_single_section($section)
{
    if (! is_array($section)) {
        return null;
    }

    $type = isset($section['_type']) ? sanitize_key((string) $section['_type']) : '';

    if ('' === $type) {
        return null;
    }

    $children = array();

    if (isset($section['children']) && is_array($section['children'])) {
        $children = site_core_normalize_sections($section['children']);
    }

    switch ($type) {
        case 'hero':
            $cta_link = function_exists('site_core_normalize_link_component_value')
                ? site_core_normalize_link_component_value(
                    site_core_get_reusable_component_value($section, 'cta'),
                    '',
                    true
                )
                : array(
                    'href'   => isset($section['cta_href']) ? esc_url_raw($section['cta_href']) : '',
                    'target' => 'self',
                    'label'  => isset($section['cta_label']) ? sanitize_text_field((string) $section['cta_label']) : '',
                );
            $image_id = isset($section['background_image']) ? absint($section['background_image']) : 0;
            $data     = array(
                'headline'        => isset($section['headline']) ? sanitize_text_field($section['headline']) : '',
                'subhead'         => isset($section['subhead']) ? sanitize_textarea_field($section['subhead']) : '',
                'backgroundImage' => site_core_hydrate_media($image_id),
                'ctaLabel'        => $cta_link['label'] ?? '',
                'ctaHref'         => $cta_link['href'],
                'ctaTarget'       => $cta_link['target'],
            );
            break;

        case 'link':
            if (function_exists('site_core_normalize_link_component_value')) {
                $data = site_core_normalize_link_component_value($section, '', true);
            } else {
                $data = array(
                    'label'  => isset($section['label']) ? sanitize_text_field((string) $section['label']) : '',
                    'href'   => isset($section['url']) ? esc_url_raw((string) $section['url']) : '',
                    'target' => 'self',
                );
            }
            break;

        case 'rich_text':
            $data = array(
                'content' => isset($section['content']) ? wp_kses_post((string) $section['content']) : '',
            );
            break;

        case 'form':
            $data = array(
                'formId' => isset($section['form_id']) ? absint($section['form_id']) : 0,
            );
            break;

        default:
            $data = site_core_normalize_untyped_data($section);
            unset($data['_type'], $data['children']);
            break;
    }

    $normalized = array(
        'type' => $type,
        'data' => $data,
    );

    if (! empty($children)) {
        $normalized['children'] = $children;
    }

    return $normalized;
}

/**
 * Hydrate attachment ID to API-safe media payload.
 *
 * @param int $attachment_id Attachment ID.
 * @return array<string, mixed>|null
 */
function site_core_hydrate_media($attachment_id)
{
    $attachment_id = absint($attachment_id);

    if ($attachment_id < 1) {
        return null;
    }

    $url = wp_get_attachment_image_url($attachment_id, 'full');

    if (! is_string($url) || '' === $url) {
        return null;
    }

    $metadata = wp_get_attachment_metadata($attachment_id);

    return array(
        'url'    => $url,
        'width'  => is_array($metadata) && isset($metadata['width']) ? (int) $metadata['width'] : null,
        'height' => is_array($metadata) && isset($metadata['height']) ? (int) $metadata['height'] : null,
    );
}

/**
 * Normalize unknown section data defensively.
 *
 * @param array<string, mixed> $data Raw data.
 * @return array<string, mixed>
 */
function site_core_normalize_untyped_data(array $data)
{
    foreach ($data as $key => $value) {
        if (is_array($value)) {
            $data[$key] = site_core_normalize_untyped_data($value);
            continue;
        }

        if (is_bool($value) || is_numeric($value) || is_null($value)) {
            continue;
        }

        $data[$key] = sanitize_text_field((string) $value);
    }

    return $data;
}

/**
 * Return the first value from a reusable component complex field.
 *
 * @param array<string, mixed> $data      Raw component data.
 * @param string               $field_name Complex field name.
 * @return array<string, mixed>
 */
function site_core_get_reusable_component_value(array $data, $field_name)
{
    if (! isset($data[$field_name]) || ! is_array($data[$field_name]) || empty($data[$field_name])) {
        return array();
    }

    $value = $data[$field_name][0];

    return is_array($value) ? $value : array();
}
