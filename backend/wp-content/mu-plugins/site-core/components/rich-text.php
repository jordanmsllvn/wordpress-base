<?php
/**
 * Site Core page builder component: Rich Text.
 */

if (! defined('ABSPATH')) {
    exit;
}

site_core_register_page_builder_component(
    'rich_text',
    array(
        'label' => __('Rich Text', 'site-core'),
        'fields' => static function ($depth, $post_type) {
            $fields = array(
                \Carbon_Fields\Field::make('rich_text', 'content', __('Content', 'site-core')),
            );

            $children_field = site_core_page_builder_children_field($post_type, $depth);

            if ($children_field) {
                $fields[] = $children_field;
            }

            return $fields;
        },
    )
);
