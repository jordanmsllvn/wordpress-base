<?php
/**
 * Site Core page builder registries.
 */

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Register a page builder component definition.
 *
 * @param string $name Component key.
 * @param array  $definition Component definition.
 */
function site_core_register_page_builder_component($name, array $definition)
{
    $name = sanitize_key($name);

    if ('' === $name || ! isset($definition['label'], $definition['fields']) || ! is_callable($definition['fields'])) {
        return;
    }

    $components = site_core_get_registered_page_builder_components();
    $components[$name] = $definition;

    $GLOBALS['site_core_page_builder_components'] = $components;
}

/**
 * Return all registered page builder components.
 *
 * @return array<string, array<string, mixed>>
 */
function site_core_get_registered_page_builder_components()
{
    $components = $GLOBALS['site_core_page_builder_components'] ?? array();

    return is_array($components) ? $components : array();
}

/**
 * Return a registered page builder component definition.
 *
 * @return array<string, mixed>|null
 */
function site_core_get_page_builder_component($name)
{
    $components = site_core_get_registered_page_builder_components();
    $name = sanitize_key($name);

    return $components[$name] ?? null;
}

/**
 * Register allowed page builder components for a post type.
 *
 * @param string        $post_type Post type key.
 * @param array<string> $component_names Allowed component keys.
 */
function site_core_register_page_builder_post_type($post_type, array $component_names)
{
    $post_type = sanitize_key($post_type);

    if ('' === $post_type) {
        return;
    }

    $normalized_names = array();

    foreach ($component_names as $component_name) {
        $component_name = sanitize_key((string) $component_name);

        if ('' !== $component_name) {
            $normalized_names[] = $component_name;
        }
    }

    $post_types = site_core_get_page_builder_post_types();
    $post_types[$post_type] = array_values(array_unique($normalized_names));

    $GLOBALS['site_core_page_builder_post_types'] = $post_types;
}

/**
 * Return all post types configured for the page builder.
 *
 * @return array<string, array<int, string>>
 */
function site_core_get_page_builder_post_types()
{
    $post_types = $GLOBALS['site_core_page_builder_post_types'] ?? array();

    return is_array($post_types) ? $post_types : array();
}

/**
 * Return allowed page builder component keys for a post type.
 *
 * @param string $post_type Post type key.
 * @return array<int, string>
 */
function site_core_get_page_builder_components_for_post_type($post_type)
{
    $post_types = site_core_get_page_builder_post_types();
    $post_type = sanitize_key($post_type);

    return $post_types[$post_type] ?? array();
}

/**
 * Return whether a post type uses the Site Core page builder.
 */
function site_core_is_page_builder_post_type($post_type)
{
    $post_type = sanitize_key((string) $post_type);

    if ('' === $post_type) {
        return false;
    }

    return array_key_exists($post_type, site_core_get_page_builder_post_types());
}

/**
 * Build a reusable sub-unit field for a page builder component.
 *
 * @param string               $field_name Complex field name.
 * @param string               $label      Visible group label.
 * @param array<int, mixed>    $fields     Nested component fields.
 * @param string               $group_name Group key used inside the complex field.
 * @return \Carbon_Fields\Field\Complex_Field
 */
function site_core_page_builder_reusable_component_field($field_name, $label, array $fields, $group_name = 'item')
{
    return \Carbon_Fields\Field::make('complex', $field_name, $label)
        ->set_layout('tabbed-vertical')
        ->set_max(1)
        ->add_fields(
            sanitize_key((string) $group_name),
            $label,
            $fields
        );
}
