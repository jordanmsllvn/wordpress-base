<?php
/**
 * Site Core page builder component: Hero.
 */

if (! defined('ABSPATH')) {
    exit;
}

site_core_register_page_builder_component(
    'hero',
    array(
        'label' => __('Hero', 'site-core'),
        'fields' => static function ($depth, $post_type) {
            $fields = array(
                \Carbon_Fields\Field::make('text', 'headline', __('Headline', 'site-core')),
                \Carbon_Fields\Field::make('textarea', 'subhead', __('Subhead', 'site-core')),
                \Carbon_Fields\Field::make('image', 'background_image', __('Background Image', 'site-core'))->set_value_type('id'),
                site_core_page_builder_reusable_component_field(
                    'cta',
                    __('CTA', 'site-core'),
                    site_core_link_component_fields('', true, false),
                    'link'
                ),
            );

            $children_field = site_core_page_builder_children_field($post_type, $depth);

            if ($children_field) {
                $fields[] = $children_field;
            }

            return $fields;
        },
    )
);
