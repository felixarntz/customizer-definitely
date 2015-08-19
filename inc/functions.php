<?php
/**
 * @package WPPTD
 * @version 0.5.0
 * @author Felix Arntz <felix-arntz@leaves-and-love.net>
 */

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

if ( ! function_exists( 'wpcd_get_customizer_settings' ) ) {
	function wpcd_get_customizer_settings( $panel = 'general' ) {
		if ( doing_action( 'wpcd' ) || ! did_action( 'wpcd' ) ) {
			//TODO
		}
		//TODO
	}
}

if ( ! function_exists( 'wpcd_get_customizer_setting' ) ) {
	function wpcd_get_customizer_setting( $setting, $panel = 'general' ) {
		if ( doing_action( 'wpcd' ) || ! did_action( 'wpcd' ) ) {
			//TODO
		}
		//TODO
	}
}
