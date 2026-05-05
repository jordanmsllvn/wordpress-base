<?php
/**
 * Site Core reusable page builder component: Link.
 */

if (! defined('ABSPATH')) {
    exit;
}

site_core_register_page_builder_component(
    'link',
    array(
        'label' => __('Link', 'site-core'),
        'fields' => static function ($depth, $post_type) {
            return site_core_link_component_fields('', true, true);
        },
    )
);

/**
 * Return reusable link component fields.
 *
 * @param string $prefix Field name prefix.
 * @param bool   $include_label Whether to include a label field.
 * @param bool   $include_title Whether to include a title field.
 * @return array<int, mixed>
 */
function site_core_link_component_fields($prefix = '', $include_label = false, $include_title = true)
{
    $fields = array();

    if ($include_title) {
        $fields[] = \Carbon_Fields\Field::make(
            'text',
            site_core_link_component_field_name($prefix, 'title'),
            __('Title', 'site-core')
        );
    }

    if ($include_label) {
        $fields[] = \Carbon_Fields\Field::make(
            'text',
            site_core_link_component_field_name($prefix, 'label'),
            __('Label', 'site-core')
        );
    }

    $fields[] = \Carbon_Fields\Field::make(
        'select',
        site_core_link_component_field_name($prefix, 'destination_type'),
        __('Destination Type', 'site-core')
    )
        ->set_default_value('url')
        ->set_options(
            array(
                'url'     => __('URL', 'site-core'),
                'content' => __('Content', 'site-core'),
            )
        );

    $fields[] = \Carbon_Fields\Field::make(
        'select',
        site_core_link_component_field_name($prefix, 'content_reference'),
        __('Content Reference', 'site-core')
    )
        ->set_options(
            static function () {
                $pages = get_posts(
                    array(
                        'post_type'        => 'page',
                        'post_status'      => 'publish',
                        'numberposts'      => -1,
                        'orderby'          => 'title',
                        'order'            => 'ASC',
                        'suppress_filters' => false,
                    )
                );

                $options = array(
                    '' => __('Select a page', 'site-core'),
                );

                foreach ($pages as $page) {
                    $options[(string) $page->ID] = get_the_title($page->ID);
                }

                return $options;
            }
        )
        ->set_conditional_logic(
            array(
                array(
                    'field' => site_core_link_component_field_name($prefix, 'destination_type'),
                    'value' => 'content',
                ),
            )
        )
        ->set_help_text(__('Choose a page directly. This overrides the URL when set.', 'site-core'));

    $fields[] = \Carbon_Fields\Field::make(
        'text',
        site_core_link_component_field_name($prefix, 'url'),
        __('URL', 'site-core')
    )
        ->set_conditional_logic(
            array(
                array(
                    'field' => site_core_link_component_field_name($prefix, 'destination_type'),
                    'value' => 'url',
                ),
            )
        );

    $fields[] = \Carbon_Fields\Field::make(
        'select',
        site_core_link_component_field_name($prefix, 'target'),
        __('Target', 'site-core')
    )
        ->set_default_value('self')
        ->set_options(
            array(
                'self'  => __('Self', 'site-core'),
                'blank' => __('Blank', 'site-core'),
            )
        );

    return $fields;
}

/**
 * Build a prefixed link field name.
 */
function site_core_link_component_field_name($prefix, $field_name)
{
    $prefix = (string) $prefix;

    return '' !== $prefix ? $prefix . $field_name : $field_name;
}

/**
 * Normalize a link payload from component data.
 *
 * @param array<string, mixed> $data Raw component data.
 * @param string               $prefix Field name prefix.
 * @param bool                 $include_label Whether to include label in output.
 * @return array<string, mixed>
 */
function site_core_normalize_link_component_value(array $data, $prefix = '', $include_label = false)
{
    $destination_type = isset($data[site_core_link_component_field_name($prefix, 'destination_type')])
        ? (string) $data[site_core_link_component_field_name($prefix, 'destination_type')]
        : '';

    $normalized = array(
        'href'   => '',
        'target' => site_core_normalize_link_target(
            $data[site_core_link_component_field_name($prefix, 'target')] ?? 'self'
        ),
    );

    if (isset($data[site_core_link_component_field_name($prefix, 'title')])) {
        $normalized['title'] = sanitize_text_field((string) $data[site_core_link_component_field_name($prefix, 'title')]);
    }

    if ($include_label) {
        $normalized['label'] = isset($data[site_core_link_component_field_name($prefix, 'label')])
            ? sanitize_text_field((string) $data[site_core_link_component_field_name($prefix, 'label')])
            : '';
    }

    if ('url' !== $destination_type) {
        $reference_id = site_core_get_link_component_reference_id(
            $data[site_core_link_component_field_name($prefix, 'content_reference')] ?? array()
        );

        if ($reference_id > 0) {
            $permalink = get_permalink($reference_id);

            if (is_string($permalink) && '' !== $permalink) {
                $normalized['href'] = esc_url_raw($permalink);
                $normalized['referenceId'] = $reference_id;

                return $normalized;
            }
        }
    }

    $url_key = site_core_link_component_field_name($prefix, 'url');
    $legacy_url_key = site_core_link_component_field_name($prefix, 'href');
    $fallback_legacy_key = '' === (string) $prefix ? 'href' : $prefix . 'href';
    $href = '';

    if (isset($data[$url_key])) {
        $href = (string) $data[$url_key];
    } elseif (isset($data[$legacy_url_key])) {
        $href = (string) $data[$legacy_url_key];
    } elseif (isset($data[$fallback_legacy_key])) {
        $href = (string) $data[$fallback_legacy_key];
    }

    $normalized['href'] = esc_url_raw($href);

    return $normalized;
}

/**
 * Normalize a link target value.
 */
function site_core_normalize_link_target($target)
{
    return 'blank' === (string) $target ? 'blank' : 'self';
}

/**
 * Extract a linked post ID from a Carbon Fields association value.
 */
function site_core_get_link_component_reference_id($association_value)
{
    if (is_numeric($association_value)) {
        return absint($association_value);
    }

    if (! is_array($association_value) || empty($association_value)) {
        return 0;
    }

    $item = $association_value[0];

    if (! is_array($item)) {
        return 0;
    }

    if (isset($item['id'])) {
        return absint($item['id']);
    }

    if (isset($item['value'])) {
        return absint($item['value']);
    }

    return 0;
}
