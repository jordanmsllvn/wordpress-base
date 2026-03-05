<?php
/**
 * Site Core Hydrate API
 *
 * Exposes normalized page builder payloads for frontend clients.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'rest_api_init', 'site_core_register_hydrate_route' );
add_action( 'save_post', 'site_core_invalidate_page_cache', 10, 3 );

/**
 * Register page hydrate endpoint.
 */
function site_core_register_hydrate_route() {
	register_rest_route(
		'site/v1',
		'/page/(?P<slug>[a-zA-Z0-9-]+)',
		array(
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => 'site_core_get_hydrated_page',
			'permission_callback' => '__return_true',
			'args'                => array(
				'slug' => array(
					'required'          => true,
					'sanitize_callback' => 'sanitize_title',
				),
			),
		)
	);
}

/**
 * Return hydrated page content by slug.
 *
 * @param WP_REST_Request $request Request object.
 * @return WP_REST_Response|WP_Error
 */
function site_core_get_hydrated_page( WP_REST_Request $request ) {
	$slug = sanitize_title( (string) $request->get_param( 'slug' ) );

	if ( '' === $slug ) {
		return new WP_Error( 'site_core_invalid_slug', 'Invalid page slug.', array( 'status' => 400 ) );
	}

	$cache_key = site_core_get_page_cache_key( $slug );
	$cached    = get_transient( $cache_key );

	if ( is_array( $cached ) ) {
		return rest_ensure_response( $cached );
	}

	$post = get_page_by_path( $slug, OBJECT, 'page' );

	if ( ! ( $post instanceof WP_Post ) || 'publish' !== $post->post_status ) {
		return new WP_Error( 'site_core_page_not_found', 'Page not found.', array( 'status' => 404 ) );
	}

	$raw_sections = array();

	if ( function_exists( 'carbon_get_post_meta' ) ) {
		$raw_sections = carbon_get_post_meta( $post->ID, 'page_sections' );
	}

	$data = array(
		'title'    => get_the_title( $post ),
		'slug'     => $post->post_name,
		'sections' => site_core_normalize_sections( $raw_sections ),
	);

	set_transient( $cache_key, $data, 60 );

	return rest_ensure_response( $data );
}

/**
 * Build transient key for page cache.
 *
 * @param string $slug Page slug.
 * @return string
 */
function site_core_get_page_cache_key( $slug ) {
	return 'page_' . sanitize_key( $slug );
}

/**
 * Normalize a set of Carbon Fields sections recursively.
 *
 * @param mixed $sections Raw section data.
 * @return array<int, array<string, mixed>>
 */
function site_core_normalize_sections( $sections ) {
	if ( ! is_array( $sections ) ) {
		return array();
	}

	$normalized = array();

	foreach ( $sections as $section ) {
		$normalized_section = site_core_normalize_single_section( $section );

		if ( is_array( $normalized_section ) ) {
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
function site_core_normalize_single_section( $section ) {
	if ( ! is_array( $section ) ) {
		return null;
	}

	$type = isset( $section['_type'] ) ? sanitize_key( (string) $section['_type'] ) : '';

	if ( '' === $type ) {
		return null;
	}

	$children = array();

	if ( isset( $section['children'] ) && is_array( $section['children'] ) ) {
		$children = site_core_normalize_sections( $section['children'] );
	}

	switch ( $type ) {
		case 'hero':
			$image_id = isset( $section['background_image'] ) ? absint( $section['background_image'] ) : 0;
			$data     = array(
				'headline'        => isset( $section['headline'] ) ? sanitize_text_field( $section['headline'] ) : '',
				'subhead'         => isset( $section['subhead'] ) ? sanitize_textarea_field( $section['subhead'] ) : '',
				'backgroundImage' => site_core_hydrate_media( $image_id ),
				'ctaLabel'        => isset( $section['cta_label'] ) ? sanitize_text_field( $section['cta_label'] ) : '',
				'ctaHref'         => isset( $section['cta_href'] ) ? esc_url_raw( $section['cta_href'] ) : '',
			);
			break;

		case 'rich_text':
			$data = array(
				'content' => isset( $section['content'] ) ? wp_kses_post( (string) $section['content'] ) : '',
			);
			break;

		case 'form':
			$data = array(
				'formId' => isset( $section['form_id'] ) ? absint( $section['form_id'] ) : 0,
			);
			break;

		default:
			$data = site_core_normalize_untyped_data( $section );
			unset( $data['_type'], $data['children'] );
			break;
	}

	$normalized = array(
		'type' => $type,
		'data' => $data,
	);

	if ( ! empty( $children ) ) {
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
function site_core_hydrate_media( $attachment_id ) {
	$attachment_id = absint( $attachment_id );

	if ( $attachment_id < 1 ) {
		return null;
	}

	$url = wp_get_attachment_image_url( $attachment_id, 'full' );

	if ( ! is_string( $url ) || '' === $url ) {
		return null;
	}

	$metadata = wp_get_attachment_metadata( $attachment_id );

	return array(
		'url'    => $url,
		'width'  => is_array( $metadata ) && isset( $metadata['width'] ) ? (int) $metadata['width'] : null,
		'height' => is_array( $metadata ) && isset( $metadata['height'] ) ? (int) $metadata['height'] : null,
	);
}

/**
 * Normalize unknown section data defensively.
 *
 * @param array<string, mixed> $data Raw data.
 * @return array<string, mixed>
 */
function site_core_normalize_untyped_data( array $data ) {
	foreach ( $data as $key => $value ) {
		if ( is_array( $value ) ) {
			$data[ $key ] = site_core_normalize_untyped_data( $value );
			continue;
		}

		if ( is_bool( $value ) || is_numeric( $value ) || is_null( $value ) ) {
			continue;
		}

		$data[ $key ] = sanitize_text_field( (string) $value );
	}

	return $data;
}

/**
 * Invalidate cached page payload when page content updates.
 *
 * @param int     $post_id Post ID.
 * @param WP_Post $post    Post object.
 * @param bool    $update  Whether this is an update.
 */
function site_core_invalidate_page_cache( $post_id, $post, $update ) {
	if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
		return;
	}

	if ( ! ( $post instanceof WP_Post ) || 'page' !== $post->post_type ) {
		return;
	}

	$slug = sanitize_title( $post->post_name );

	if ( '' !== $slug ) {
		delete_transient( site_core_get_page_cache_key( $slug ) );
	}

	$old_slug = get_post_meta( $post_id, '_wp_old_slug', true );

	if ( is_string( $old_slug ) && '' !== $old_slug ) {
		delete_transient( site_core_get_page_cache_key( $old_slug ) );
	}
}
