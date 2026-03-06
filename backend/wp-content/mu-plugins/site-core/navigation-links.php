<?php
/**
 * Site Core Navigation Links
 *
 * Defines global header and footer navigation as a global Carbon Fields theme option.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'carbon_fields_register_fields', 'site_core_register_navigation_link_fields' );
add_action( 'rest_api_init', 'site_core_register_navigation_route' );

/**
 * Register global header/footer navigation fields.
 */
function site_core_register_navigation_link_fields() {
	if ( ! class_exists( '\Carbon_Fields\Container' ) || ! class_exists( '\Carbon_Fields\Field' ) ) {
		return;
	}

	$max_depth = absint( apply_filters( 'site_core_navigation_link_depth', 4 ) );

	if ( $max_depth < 1 ) {
		$max_depth = 1;
	}

	$header_links = \Carbon_Fields\Field::make( 'complex', 'site_core_header_links', __( 'Header Links', 'site-core' ) )
		->set_layout( 'tabbed-vertical' );
	$header_links = site_core_navigation_add_link_type_fields( $header_links, $max_depth );

	$footer_links = \Carbon_Fields\Field::make( 'complex', 'site_core_footer_links', __( 'Footer Links', 'site-core' ) )
		->set_layout( 'tabbed-vertical' );
	$footer_links = site_core_navigation_add_link_type_fields( $footer_links, $max_depth );

	\Carbon_Fields\Container::make( 'theme_options', __( 'Site Menu', 'site-core' ) )
		->set_page_menu_title( __( 'Site Menu', 'site-core' ) )
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
function site_core_navigation_add_link_type_fields( $complex_field, $depth ) {
	$fields = array(
		\Carbon_Fields\Field::make( 'text', 'label', __( 'Label', 'site-core' ) ),
		\Carbon_Fields\Field::make( 'text', 'url', __( 'URL', 'site-core' ) ),
	);

	$children = site_core_navigation_children_field( $depth );

	if ( $children ) {
		$fields[] = $children;
	}

	return $complex_field->add_fields( 'link', __( 'Link', 'site-core' ), $fields );
}

/**
 * Build optional children field with decreasing remaining depth.
 *
 * @param int $depth Remaining link nesting depth.
 * @return \Carbon_Fields\Field\Complex_Field|null
 */
function site_core_navigation_children_field( $depth ) {
	if ( $depth <= 1 ) {
		return null;
	}

	$children = \Carbon_Fields\Field::make( 'complex', 'children', __( 'Nested Links', 'site-core' ) )
		->set_layout( 'tabbed-vertical' );

	return site_core_navigation_add_link_type_fields( $children, $depth - 1 );
}

/**
 * Register navigation endpoint for headless consumers.
 */
function site_core_register_navigation_route() {
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
function site_core_get_navigation_payload() {
	$header_links = array();
	$footer_links = array();

	if ( function_exists( 'carbon_get_theme_option' ) ) {
		$header_links = carbon_get_theme_option( 'site_core_header_links' );
		$footer_links = carbon_get_theme_option( 'site_core_footer_links' );
	}

	return rest_ensure_response(
		array(
			'headerLinks' => site_core_normalize_navigation_links( $header_links ),
			'footerLinks' => site_core_normalize_navigation_links( $footer_links ),
		)
	);
}

/**
 * Normalize a recursive navigation payload.
 *
 * @param mixed $links Raw data from Carbon Fields.
 * @return array<int, array<string, mixed>>
 */
function site_core_normalize_navigation_links( $links ) {
	if ( ! is_array( $links ) ) {
		return array();
	}

	$normalized = array();

	foreach ( $links as $link ) {
		if ( ! is_array( $link ) ) {
			continue;
		}

		$item = array(
			'label' => isset( $link['label'] ) ? sanitize_text_field( $link['label'] ) : '',
			'url'   => isset( $link['url'] ) ? esc_url_raw( $link['url'] ) : '',
		);

		if ( isset( $link['children'] ) ) {
			$item['children'] = site_core_normalize_navigation_links( $link['children'] );
		}

		$normalized[] = $item;
	}

	return $normalized;
}
