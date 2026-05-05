<?php
/**
 * Site Core Page Builder
 *
 * Defines the Carbon Fields schema for page sections.
 */

if (! defined('ABSPATH')) {
    exit;
}

add_action('carbon_fields_register_fields', 'site_core_register_page_builder_fields');

/**
 * Register the Complex page builder field for pages.
 */
function site_core_register_page_builder_fields()
{
    if (! class_exists('\Carbon_Fields\Container') || ! class_exists('\Carbon_Fields\Field') || ! function_exists('site_core_get_page_builder_post_types')) {
        return;
    }

    $max_depth = absint(apply_filters('site_core_page_builder_max_depth', 3));

    if ($max_depth < 1) {
        $max_depth = 1;
    }

    foreach (site_core_get_page_builder_post_types() as $post_type => $component_names) {
        if (empty($component_names)) {
            continue;
        }

        $sections = \Carbon_Fields\Field::make('complex', 'page_sections', __('Page Sections', 'site-core'))
            ->set_layout('tabbed-vertical');

        $sections = site_core_page_builder_add_component_types($sections, $max_depth, $post_type);

        \Carbon_Fields\Container::make('post_meta', __('Page Builder', 'site-core'))
            ->where('post_type', '=', $post_type)
            ->add_fields(
                array(
                    $sections,
                )
            );
    }
}

/**
 * Add supported component types to a Complex field.
 *
 * @param \Carbon_Fields\Field\Complex_Field $complex_field Complex field instance.
 * @param int                                $depth         Remaining nested depth.
 * @param string                             $post_type     Post type key.
 * @return \Carbon_Fields\Field\Complex_Field
 */
function site_core_page_builder_add_component_types($complex_field, $depth, $post_type)
{
    foreach (site_core_get_page_builder_components_for_post_type($post_type) as $component_name) {
        $component = site_core_get_page_builder_component($component_name);

        if (! is_array($component) || ! isset($component['label'], $component['fields']) || ! is_callable($component['fields'])) {
            continue;
        }

        $complex_field = $complex_field->add_fields(
            $component_name,
            $component['label'],
            call_user_func($component['fields'], $depth, $post_type)
        );
    }

    return $complex_field;
}

/**
 * Build optional nested children field.
 *
 * @param string $post_type Post type key.
 * @param int    $depth     Remaining nested depth.
 * @return \Carbon_Fields\Field\Complex_Field|null
 */
function site_core_page_builder_children_field($post_type, $depth)
{
    if ($depth <= 1) {
        return null;
    }

    $children = \Carbon_Fields\Field::make('complex', 'children', __('Nested Components', 'site-core'))
        ->set_layout('tabbed-vertical');

    return site_core_page_builder_add_component_types($children, $depth - 1, $post_type);
}
