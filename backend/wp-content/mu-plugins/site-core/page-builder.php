<?php
/**
 * Site Core Page Builder
 *
 * Defines the Carbon Fields schema for page sections.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'carbon_fields_register_fields', 'site_core_register_page_builder_fields' );

/**
 * Register the Complex page builder field for pages.
 */
function site_core_register_page_builder_fields() {
	if ( ! class_exists( '\Carbon_Fields\Container' ) || ! class_exists( '\Carbon_Fields\Field' ) ) {
		return;
	}

	$max_depth = absint( apply_filters( 'site_core_page_builder_max_depth', 3 ) );

	if ( $max_depth < 1 ) {
		$max_depth = 1;
	}

	$sections = \Carbon_Fields\Field::make( 'complex', 'page_sections', __( 'Page Sections', 'site-core' ) )
		->set_layout( 'tabbed-vertical' );

	$sections = site_core_page_builder_add_component_types( $sections, $max_depth );

	\Carbon_Fields\Container::make( 'post_meta', __( 'Page Builder', 'site-core' ) )
		->where( 'post_type', '=', 'page' )
		->add_fields(
			array(
				$sections,
			)
		);
}

/**
 * Add supported component types to a Complex field.
 *
 * @param \Carbon_Fields\Field\Complex_Field $complex_field Complex field instance.
 * @param int                                $depth         Remaining nested depth.
 * @return \Carbon_Fields\Field\Complex_Field
 */
function site_core_page_builder_add_component_types( $complex_field, $depth ) {
	return $complex_field
		->add_fields(
			'hero',
			__( 'Hero', 'site-core' ),
			site_core_page_builder_hero_fields( $depth )
		)
		->add_fields(
			'rich_text',
			__( 'Rich Text', 'site-core' ),
			site_core_page_builder_rich_text_fields( $depth )
		)
		->add_fields(
			'form',
			__( 'Form', 'site-core' ),
			site_core_page_builder_form_fields( $depth )
		);
}

/**
 * Build Hero fields.
 *
 * @param int $depth Remaining nested depth.
 * @return array
 */
function site_core_page_builder_hero_fields( $depth ) {
	$fields = array(
		\Carbon_Fields\Field::make( 'text', 'headline', __( 'Headline', 'site-core' ) ),
		\Carbon_Fields\Field::make( 'textarea', 'subhead', __( 'Subhead', 'site-core' ) ),
		\Carbon_Fields\Field::make( 'image', 'background_image', __( 'Background Image', 'site-core' ) )->set_value_type( 'id' ),
		\Carbon_Fields\Field::make( 'text', 'cta_label', __( 'CTA Label', 'site-core' ) ),
		\Carbon_Fields\Field::make( 'text', 'cta_href', __( 'CTA Href', 'site-core' ) ),
	);

	$children_field = site_core_page_builder_children_field( $depth );

	if ( $children_field ) {
		$fields[] = $children_field;
	}

	return $fields;
}

/**
 * Build Rich Text fields.
 *
 * @param int $depth Remaining nested depth.
 * @return array
 */
function site_core_page_builder_rich_text_fields( $depth ) {
	$fields = array(
		\Carbon_Fields\Field::make( 'rich_text', 'content', __( 'Content', 'site-core' ) ),
	);

	$children_field = site_core_page_builder_children_field( $depth );

	if ( $children_field ) {
		$fields[] = $children_field;
	}

	return $fields;
}

/**
 * Build Form fields.
 *
 * @param int $depth Remaining nested depth.
 * @return array
 */
function site_core_page_builder_form_fields( $depth ) {
	$fields = array(
		\Carbon_Fields\Field::make( 'select', 'form_id', __( 'Contact Form', 'site-core' ) )
			->set_options(
				function () {
					$forms = get_posts(
						array(
							'post_type'      => 'wpcf7_contact_form',
							'post_status'    => 'publish',
							'numberposts'    => -1,
							'orderby'        => 'title',
							'order'          => 'ASC',
							'suppress_filters' => false,
						)
					);

					$options = array(
						'' => __( 'Select a form', 'site-core' ),
					);

					foreach ( $forms as $form ) {
						$title = get_the_title( $form->ID );
						$options[ (string) $form->ID ] = '' !== $title ? $title : __( '(Untitled form)', 'site-core' );
					}

					return $options;
				}
			)
			->set_help_text( __( 'Choose a Contact Form 7 form to render.', 'site-core' ) ),
	);

	$children_field = site_core_page_builder_children_field( $depth );

	if ( $children_field ) {
		$fields[] = $children_field;
	}

	return $fields;
}

/**
 * Build optional nested children field.
 *
 * @param int $depth Remaining nested depth.
 * @return \Carbon_Fields\Field\Complex_Field|null
 */
function site_core_page_builder_children_field( $depth ) {
	if ( $depth <= 1 ) {
		return null;
	}

	$children = \Carbon_Fields\Field::make( 'complex', 'children', __( 'Nested Components', 'site-core' ) )
		->set_layout( 'tabbed-vertical' );

	return site_core_page_builder_add_component_types( $children, $depth - 1 );
}
