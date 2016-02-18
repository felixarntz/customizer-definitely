<?php
/**
 * @package WPCD
 * @version 0.5.0
 * @author Felix Arntz <felix-arntz@leaves-and-love.net>
 */

namespace WPCD;

use WPCD\App as App;
use WPDLib\Components\Manager as ComponentManager;

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

if ( ! class_exists( 'WPCD\Customizer' ) ) {
	/**
	 * This class performs the necessary actions in the WordPress customizer.
	 *
	 * This includes both registering and displaying customizer panels, sections and fields, as well as including the required scripts.
	 *
	 * @internal
	 * @since 0.5.0
	 */
	class Customizer {

		/**
		 * @since 0.5.0
		 * @var WPCD\Customizer|null Holds the instance of this class.
		 */
		private static $instance = null;

		/**
		 * Gets the instance of this class. If it does not exist, it will be created.
		 *
		 * @since 0.5.0
		 * @return WPCD\Customizer
		 */
		public static function instance() {
			if ( null == self::$instance ) {
				self::$instance = new self;
			}

			return self::$instance;
		}

		/**
		 * Class constructor.
		 *
		 * This will hook functions into the 'customize_register' and 'customize_preview_init' actions.
		 *
		 * @since 0.5.0
		 */
		private function __construct() {
			add_action( 'after_setup_theme', array( $this, 'add_hooks' ) );
		}

		public function add_hooks() {
			add_action( 'customize_register', array( $this, 'register_components' ), 10, 1 );
			add_action( 'customize_preview_init', array( $this, 'enqueue_preview_assets' ), 10, 1 );
		}

		public function register_components( $wp_customize ) {
			$css_generator = CSSGenerator::instance();

			$panels = ComponentManager::get( '*', 'WPCD\Components\Panel' );
			foreach ( $panels as $panel ) {
				$panel->register( $wp_customize );
				if ( 'general' !== $panel->slug ) {
					add_action( 'update_option_' . $panel->_id, array( $css_generator, 'set_last_modified' ) );
				}
				foreach ( $panel->get_children() as $section ) {
					$section->register( $wp_customize, $panel );
					foreach ( $section->get_children() as $field ) {
						$field->register( $wp_customize, $section, $panel );
						if ( 'general' === $panel->slug ) {
							add_action( 'update_option_' . $field->_id, array( $css_generator, 'set_last_modified' ) );
						}
					}
				}
			}

			$theme = get_option( 'stylesheet' );
			add_action( 'update_option_theme_mods_' . $theme, array( $css_generator, 'set_last_modified' ) );
		}

		public function enqueue_preview_assets( $wp_customize ) {
			wp_enqueue_script( 'wpcd-functions', App::get_url( 'assets/functions.js' ), array( 'jquery', 'wp-util' ), App::get_info( 'version' ), true );
			wp_localize_script( 'wpcd-functions', 'wpcd_customizer', array(
				'settings'				=> $this->get_settings(),
				'update_callbacks'		=> new \stdClass(),
				'preprocess_callbacks'	=> new \stdClass(),
				'util'					=> array(
					'update_nonce'			=> wp_create_nonce( 'wpcd-customize-update' ),
					'preprocess_nonce'		=> wp_create_nonce( 'wpcd-customize-preprocess' ),
				);
			) );

			$main_dependencies = array( 'customize-base', 'wpcd-functions' );

			$custom_callback_function_scripts = apply_filters( 'wpcd_custom_callback_function_scripts', array() );

			foreach ( $custom_callback_function_scripts as $script_handle => $script_url ) {
				wp_enqueue_script( $script_handle, $script_url, array( 'wpcd-functions' ), false, true );
				$main_dependencies[] = $script_handle;
			}

			wp_enqueue_script( 'wpcd-framework', App::get_url( 'assets/framework.js' ), $main_dependencies, App::get_info( 'version' ), true );
		}

		public function get_settings() {
			$fields = ComponentManager::get( '*.*.*', 'WPCD\Components\Panel' );
			if ( count( $fields ) < 1 ) {
				return new \stdClass();
			}

			$settings = array();
			foreach ( $fields as $field ) {
				if ( 'postMessage' == $field->transport ) {
					$settings[ $field->_id ] = $field->preview_args;
				}
			}

			return $settings;
		}

		public function validate_preview_args( $args, $field_type, $field ) {
			if ( ! is_array( $args ) ) {
				$args = array();
			}

			$default_timeout = ( isset( $args['update_callback'] ) && 'update_style' === $args['update_callback'] ) ? 1000 : 0;

			$default_preprocess_callback = '';
			switch ( $field_type ) {
				case 'media':
					$default_preprocess_callback = 'get_attachment_url';
					break;
				case 'number':
				case 'range':
					$default_preprocess_callback = 'number_format_i18n';
					break;
				case 'datetime':
				case 'date':
				case 'time':
					$default_preprocess_callback = 'date_i18n';
					break;
				case 'wysiwyg':
					$default_preprocess_callback = 'content_format';
					break;
				case 'multibox':
				case 'multiselect':
				case 'radio':
				case 'select':
					$default_preprocess_callback = 'value_to_label';
					break;
				default:
					$default_preprocess_callback = 'do_not_process';
			}

			$args = wp_parse_args( $args, array(
				'update_callback'		=> '',
				'update_args'			=> array(),
				'timeout'				=> $default_timeout,
				'preprocess_callback'	=> '',
				'preprocess_args'		=> array(),
			) );

			if ( $args['update_callback'] ) {
				switch ( $args['update_callback'] ) {
					case 'update_attr':
					case 'update_content':
						if ( ! is_array( $args['update_args'] ) ) {
							$args['update_args'] = array();
						} elseif ( 0 < count( $args['update_args'] ) && ! isset( $args['update_args'][0] ) ) {
							$args['update_args'] = array( $args['update_args'] );
						}
						for ( $i = 0; $i < count( $args['update_args'] ); $i++ ) {
							$args['update_args'][ $i ] = wp_parse_args( $args['update_args'][ $i ], array(
								'selectors'		=> array(),
								'property'		=> '',
								'prefix'		=> '',
								'suffix'		=> '',
							) );
						}
						break;
					case 'update_style':
						if ( ! is_array( $args['update_args'] ) ) {
							$args['update_args'] = array();
						} elseif ( 0 < count( $args['update_args'] ) && ! isset( $args['update_args'][0] ) ) {
							$args['update_args'] = array( $args['update_args'] );
						}
						for ( $i = 0; $i < count( $args['update_args'] ); $i++ ) {
							$args['update_args'][ $i ] = wp_parse_args( $args['update_args'][ $i ], array(
								'selectors'		=> array(),
								'property'		=> '',
								'prefix'		=> '',
								'suffix'		=> '',
								// either use 'media_query'...
								'media_query'	=> '',
								// ...or the following fields
								'media_type'	=> '',
								'min_width'		=> '',
								'max_width'		=> '',
							) );
							if ( empty( $args['update_args'][ $i ]['media_query'] ) ) {
								$media_query_parts = array();

								if ( in_array( $args['update_args'][ $i ]['media_type'], array( 'all', 'print', 'screen', 'speech' ) ) ) {
									$media_query_parts[] = 'only ' . $args['update_args'][ $i ]['media_type'];
								}
								if ( ! empty( $args['update_args'][ $i ]['min_width'] ) ) {
									if ( is_numeric( $args['update_args'][ $i ]['min_width'] ) ) {
										$args['update_args'][ $i ]['min_width'] .= 'px';
									}
									$media_query_parts[] = '(min-width: ' . $args['update_args'][ $i ]['min_width'] . ')';
								}
								if ( ! empty( $args['update_args'][ $i ]['max_width'] ) ) {
									if ( is_numeric( $args['update_args'][ $i ]['max_width'] ) ) {
										$args['update_args'][ $i ]['max_width'] .= 'px';
									}
									$media_query_parts[] = '(max-width: ' . $args['update_args'][ $i ]['max_width'] . ')';
								}

								if ( count( $media_query_parts ) > 0 ) {
									$args['update_args'][ $i ]['media_query'] = '@media ' . implode( ' and ', $media_query_parts );
								}
							}
							unset( $args['update_args'][ $i ]['media_type'] );
							unset( $args['update_args'][ $i ]['min_width'] );
							unset( $args['update_args'][ $i ]['max_width'] );
						}
						break;
					default:
						$args['update_args'] = apply_filters( 'wpcd_validate_' . $args['update_callback'] . '_update_args', $args['update_args'] );
				}
			}

			if ( $args['preprocess_callback'] ) {
				switch ( $args['preprocess_callback'] ) {
					case 'get_attachment_url':
						$args['preprocess_args'] = wp_parse_args( $args['preprocess_args'], array(
							'size'		=> 'full',
						) );
						break;
					case 'number_format_i18n':
						$default_decimals = 0;
						if ( is_float( $field->step ) ) {
							$default_decimals = strlen( explode( '.', '' . $field->step )[1] );
						}
						$args['preprocess_args'] = wp_parse_args( $args['preprocess_args'], array(
							'decimals'		=> $default_decimals,
						) );
						break;
					case 'date_i18n':
						$default_datetime_format = '';
						if ( 'time' === $field_type ) {
							$default_datetime_format = get_option( 'time_format' );
						} elseif ( 'date' === $field_type ) {
							$default_datetime_format = get_option( 'date_format' );
						} else {
							$default_datetime_format = get_option( 'date_format' ) . ' ' . get_option( 'time_format' );
						}
						$args['preprocess_args'] = wp_parse_args( $args['preprocess_args'], array(
							'format'		=> $default_datetime_format,
						) );
						break;
					case 'content_format':
						$args['preprocess_args'] = wp_parse_args( $args['preprocess_args'], array(
							'filters'	=> array( 'wpautop', 'do_shortcode' ),
						) );
						break;
					case 'value_to_label':
						$args['preprocess_args'] = wp_parse_args( $args['preprocess_args'], array(
							'mode'		=> 'label',
							'labels'	=> $field->options,
						) );
						break;
					case 'do_not_process':
						$args['preprocess_args'] = array();
						break;
					default:
						$args['preprocess_args'] = apply_filters( 'wpcd_validate_' . $args['preprocess_callback'] . '_preprocess_args', $args['preprocess_args'] );
				}
			}

			return $args;
		}
	}
}
