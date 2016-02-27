<?php
/**
 * @package WPCD
 * @version 0.5.0
 * @author Felix Arntz <felix-arntz@leaves-and-love.net>
 */

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

if ( ! function_exists( 'wpcd_get_customizer_settings' ) ) {
	function wpcd_get_customizer_settings( $panel_slug, $formatted = false ) {
		$_options = array();
		$_thememods = array();
		if ( 'general' !== $panel_slug ) {
			$_options = get_option( $panel_slug, array() );
			$_thememods = get_theme_mod( $panel_slug, array() );
		}

		if ( doing_action( 'wpcd' ) || ! did_action( 'wpcd' ) ) {
			return array_merge( $_options, $_thememods );
		}

		$settings = array();

		$panel = \WPDLib\Components\Manager::get( $panel_slug, 'WPCD\Components\Panel', true );
		if ( $panel ) {
			if ( 'general' === $panel_slug ) {
				foreach ( $panel->get_children() as $section ) {
					foreach ( $section->get_children() as $field ) {
						$settings[ $field->slug ] = \WPCD\Utility::parse_setting( ( 'option' === $field->mode ? get_option( $field->slug, null ) : get_theme_mod( $field->slug, null ) ), $field, $formatted );
					}
				}
			} else {
				foreach ( $panel->get_children() as $section ) {
					foreach ( $section->get_children() as $field ) {
						$settings[ $field->slug ] = \WPCD\Utility::parse_setting( ( isset( $_options[ $field->slug ] ) ? $_options[ $field->slug ] : ( isset( $_thememods[ $field->slug ] ) ? $_thememods[ $field->slug ] : null ) ), $field, $formatted );
					}
				}
			}
		}

		return $settings;
	}
}

if ( ! function_exists( 'wpcd_get_customizer_setting' ) ) {
	function wpcd_get_customizer_setting( $panel_slug, $field_slug, $formatted = false ) {
		if ( doing_action( 'wpcd' ) || ! did_action( 'wpcd' ) ) {
			if ( 'general' === $panel_slug ) {
				$setting = get_theme_mod( $field_slug, null );
				if ( ! $setting ) {
					$setting = get_option( $field_slug, null );
				}
				return $setting;
			}

			$settings = get_theme_mod( $panel_slug, array() );
			if ( ! isset( $settings[ $field_slug ] ) ) {
				$settings = get_option( $panel_slug, array() );
			}
			return isset( $settings[ $field_slug ] ) ? $settings[ $field_slug ] : null;
		}

		$setting = null;

		$field = \WPDLib\Components\Manager::get( $panel_slug . '.*.' . $field_slug, 'WPCD\Components\Panel', true );
		if ( $field ) {
			if ( 'general' === $panel_slug ) {
				$setting = \WPCD\Utility::parse_setting( ( 'option' === $field->mode ? get_option( $field->slug, null ) : get_theme_mod( $field->slug, null ) ), $field, $formatted );
			} else {
				if ( 'option' === $field->mode ) {
					$_options = get_option( $panel_slug, array() );
					$setting = \WPCD\Utility::parse_setting( ( isset( $_options[ $field->slug ] ) ? $_options[ $field->slug ] : null ), $field, $formatted );
				} else {
					$_thememods = get_theme_mod( $panel_slug, array() );
					$setting = \WPCD\Utility::parse_setting( ( isset( $_thememods[ $field->slug ] ) ? $_thememods[ $field->slug ] : null ), $field, $formatted );
				}
			}
		}

		return $setting;
	}
}
