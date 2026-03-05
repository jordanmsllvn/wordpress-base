<?php
/**
 * Example migration:
 * Adds a default CTA style option used by frontend consumers.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

return static function () {
	$option_name = 'site_core_default_cta_style';

	if ( false === get_option( $option_name, false ) ) {
		add_option( $option_name, 'primary', '', false );
	}
};
