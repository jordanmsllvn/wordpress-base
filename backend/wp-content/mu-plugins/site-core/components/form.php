<?php
/**
 * Site Core page builder component: Form.
 */

if (! defined('ABSPATH')) {
    exit;
}

site_core_register_page_builder_component(
    'form',
    array(
        'label' => __('Form', 'site-core'),
        'fields' => static function ($depth, $post_type) {
            $fields = array(
                \Carbon_Fields\Field::make('select', 'form_id', __('Contact Form', 'site-core'))
                    ->set_options(
                        function () {
                            $forms = get_posts(
                                array(
                                    'post_type'        => 'wpcf7_contact_form',
                                    'post_status'      => 'publish',
                                    'numberposts'      => -1,
                                    'orderby'          => 'title',
                                    'order'            => 'ASC',
                                    'suppress_filters' => false,
                                )
                            );

                            $options = array(
                                '' => __('Select a form', 'site-core'),
                            );

                            foreach ($forms as $form) {
                                $title = get_the_title($form->ID);
                                $options[(string) $form->ID] = '' !== $title ? $title : __('(Untitled form)', 'site-core');
                            }

                            return $options;
                        }
                    )
                    ->set_help_text(__('Choose a Contact Form 7 form to render.', 'site-core')),
            );

            $children_field = site_core_page_builder_children_field($post_type, $depth);

            if ($children_field) {
                $fields[] = $children_field;
            }

            return $fields;
        },
    )
);
