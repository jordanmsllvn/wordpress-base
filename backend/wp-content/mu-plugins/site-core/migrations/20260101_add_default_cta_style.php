<?php
/**
 * Example migration:
 * Adds a default CTA style option used by frontend consumers.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

return array(
	'up' => static function () {
		$option_name = 'site_core_default_cta_style';

		if ( false === get_option( $option_name, false ) ) {
			add_option( $option_name, 'primary', '', false );
		}
	},
	'down' => static function () {
		delete_option( 'site_core_default_cta_style' );
	},
);
