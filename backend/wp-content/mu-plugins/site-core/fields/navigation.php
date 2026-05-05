<?php
/**
 * Site Core Navigation Fields
 *
 * Defines global header and footer navigation as Carbon Fields options.
 */

if (! defined('ABSPATH')) {
    exit;
}

add_action('carbon_fields_register_fields', 'site_core_register_navigation_link_fields');

/**
 * Register global header/footer navigation fields.
 */
function site_core_register_navigation_link_fields()
{
    if (! class_exists('\Carbon_Fields\Container') || ! class_exists('\Carbon_Fields\Field')) {
        return;
    }

    $max_depth = absint(apply_filters('site_core_navigation_link_depth', 4));

    if ($max_depth < 1) {
        $max_depth = 1;
    }

    $header_links = \Carbon_Fields\Field::make('complex', 'site_core_header_links', __('Header Links', 'site-core'))
        ->set_layout('tabbed-vertical');
    $header_links = site_core_navigation_add_link_type_fields($header_links, $max_depth);

    $footer_links = \Carbon_Fields\Field::make('complex', 'site_core_footer_links', __('Footer Links', 'site-core'))
        ->set_layout('tabbed-vertical');
    $footer_links = site_core_navigation_add_link_type_fields($footer_links, $max_depth);

    \Carbon_Fields\Container::make('theme_options', __('Site Menu', 'site-core'))
        ->set_page_menu_title(__('Site Menu', 'site-core'))
        ->add_fields(
            array(
                $header_links,
                $footer_links,
            )
        );
}

/**
 * Add link fields to a complex field.
 *
 * @param \Carbon_Fields\Field\Complex_Field $complex_field Complex field instance.
 * @param int                                $depth         Remaining link nesting depth.
 * @return \Carbon_Fields\Field\Complex_Field
 */
function site_core_navigation_add_link_type_fields($complex_field, $depth)
{
    $fields = array(
        \Carbon_Fields\Field::make('text', 'label', __('Label', 'site-core')),
        \Carbon_Fields\Field::make('text', 'url', __('URL', 'site-core')),
    );

    $children = site_core_navigation_children_field($depth);

    if ($children) {
        $fields[] = $children;
    }

    return $complex_field->add_fields('link', __('Link', 'site-core'), $fields);
}

/**
 * Build optional children field with decreasing remaining depth.
 *
 * @param int $depth Remaining link nesting depth.
 * @return \Carbon_Fields\Field\Complex_Field|null
 */
function site_core_navigation_children_field($depth)
{
    if ($depth <= 1) {
        return null;
    }

    $children = \Carbon_Fields\Field::make('complex', 'children', __('Nested Links', 'site-core'))
        ->set_layout('tabbed-vertical');

    return site_core_navigation_add_link_type_fields($children, $depth - 1);
}
