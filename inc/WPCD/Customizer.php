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
			$panels = ComponentManager::get( '*', 'WPCD\Components\Panel' );
			foreach ( $panels as $panel ) {
				$panel->register( $wp_customize );
				foreach ( $panel->get_children() as $section ) {
					$section->register( $wp_customize, $panel );
					foreach ( $section->get_children() as $field ) {
						$field->register( $wp_customize, $section, $panel );
					}
				}
			}
		}

		public function enqueue_preview_assets( $wp_customize ) {
			wp_enqueue_script( 'wpcd-functions', App::get_url( 'assets/functions.js' ), array( 'jQuery' ), App::get_info( 'version' ), true );
			wp_localize_script( 'wpcd-functions', 'wpcd_customizer', array(
				'settings'				=> $this->get_settings(),
				'callbacks'				=> new \stdClass(),
				'util'					=> new \stdClass();
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

		public function validate_preview_args( $args ) {
			if ( ! is_array( $args ) ) {
				$args = array();
			}

			$default_timeout = ( isset( $args['callback'] ) && 'update_style' === $args['callback'] ) ? 1000 : 0;

			$args = wp_parse_args( $args, array(
				'callback'		=> '',
				'timeout'		=> $default_timeout,
				'data'			=> array(),
			) );

			if ( $args['callback'] ) {
				switch ( $args['callback'] ) {
					case 'update_attr':
					case 'update_content':
						if ( ! is_array( $args['data'] ) ) {
							if ( ! empty( $args['data'] ) ) {
								$args['data'] = array( $args['data'] );
							} else {
								$args['data'] = array();
							}
						}
						for ( $i = 0; $i < count( $args['data'] ); $i++ ) {
							$args['data'][ $i ] = wp_parse_args( $args['data'][ $i ], array(
								'selectors'		=> array(),
								'property'		=> '',
								'prefix'		=> '',
								'suffix'		=> '',
							) );
						}
						break;
					case 'update_style':
						if ( ! is_array( $args['data'] ) ) {
							if ( ! empty( $args['data'] ) ) {
								$args['data'] = array( $args['data'] );
							} else {
								$args['data'] = array();
							}
						}
						for ( $i = 0; $i < count( $args['data'] ); $i++ ) {
							$args['data'][ $i ] = wp_parse_args( $args['data'][ $i ], array(
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
							if ( empty( $args['data'][ $i ]['media_query'] ) ) {
								$media_query_parts = array();

								if ( in_array( $args['data'][ $i ]['media_type'], array( 'all', 'print', 'screen', 'speech' ) ) ) {
									$media_query_parts[] = 'only ' . $args['data'][ $i ]['media_type'];
								}
								if ( ! empty( $args['data'][ $i ]['min_width'] ) ) {
									if ( is_numeric( $args['data'][ $i ]['min_width'] ) ) {
										$args['data'][ $i ]['min_width'] .= 'px';
									}
									$media_query_parts[] = '(min-width: ' . $args['data'][ $i ]['min_width'] . ')';
								}
								if ( ! empty( $args['data'][ $i ]['max_width'] ) ) {
									if ( is_numeric( $args['data'][ $i ]['max_width'] ) ) {
										$args['data'][ $i ]['max_width'] .= 'px';
									}
									$media_query_parts[] = '(max-width: ' . $args['data'][ $i ]['max_width'] . ')';
								}

								if ( count( $media_query_parts ) > 0 ) {
									$args['data'][ $i ]['media_query'] = '@media ' . implode( ' and ', $media_query_parts );
								}
							}
							unset( $args['data'][ $i ]['media_type'] );
							unset( $args['data'][ $i ]['min_width'] );
							unset( $args['data'][ $i ]['max_width'] );
						}
						break;
					default:
						$args['data'] = apply_filters( 'wpcd_validate_' . $args['callback'] . '_data', $args['data'] );
				}
			}

			return $args;
		}
	}
}
