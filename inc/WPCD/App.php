<?php
/**
 * @package WPCD
 * @version 0.5.0
 * @author Felix Arntz <felix-arntz@leaves-and-love.net>
 */

namespace WPCD;

use WPCD\Customizer as Customizer;
use WPCD\Components\Panel as Panel;
use WPCD\Components\Section as Section;
use WPCD\Components\Field as Field;
use WPDLib\Components\Manager as ComponentManager;
use LaL_WP_Plugin as Plugin;

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

if ( ! class_exists( 'WPCD\App' ) ) {
	/**
	 * This class initializes the plugin.
	 *
	 * It also triggers the action and filter to hook into and contains all API functions of the plugin.
	 *
	 * @since 0.5.0
	 */
	class App extends Plugin {

		/**
		 * @since 0.5.0
		 * @var boolean Holds the status whether the initialization function has been called yet.
		 */
		private $initialization_triggered = false;

		/**
		 * @since 0.5.0
		 * @var boolean Holds the status whether the app has been initialized yet.
		 */
		private $initialized = false;

		/**
		 * @since 0.5.0
		 * @var array Holds the plugin data.
		 */
		protected static $_args = array();

		/**
		 * Class constructor.
		 *
		 * @since 0.5.0
		 */
		protected function __construct( $args ) {
			parent::__construct( $args );
		}

		/**
		 * The run() method.
		 *
		 * This will initialize the plugin on the 'after_setup_theme' action.
		 * If we are currently in the WordPress admin area, the WPCD\Admin class will be instantiated.
		 *
		 * @since 0.5.0
		 */
		protected function run() {
			Customizer::instance();

			// use after_setup_theme action so it is initialized as soon as possible, but also so that both plugins and themes can use the action
			add_action( 'after_setup_theme', array( $this, 'init' ), 3 );

			add_filter( 'wpcd_panel_validated', array( $this, 'panel_validated' ), 10, 2 );
			add_filter( 'wpcd_section_validated', array( $this, 'section_validated' ), 10, 2 );
		}

		public function set_scope( $scope ) {
			ComponentManager::set_scope( $scope );
		}

		public function add( $component ) {
			return ComponentManager::add( $component );
		}

		public function add_components( $components, $scope = '' ) {
			$this->set_scope( $scope );

			if ( is_array( $components ) ) {
				foreach ( $components as $panel_slug => $panel_args ) {
					$panel = ComponentManager::add( new Panel( $panel_slug, $panel_args ) );
					if ( is_wp_error( $panel ) ) {
						self::doing_it_wrong( __METHOD__, $panel->get_error_message(), '0.5.0' );
					} else {
						if ( isset( $panel_args['sections'] ) && is_array( $panel_args['sections'] ) ) {
							foreach ( $panel_args['sections'] as $section_slug => $section_args ) {
								$section = $panel->add( new Section( $section_slug, $section_args ) );
								if ( is_wp_error( $section ) ) {
									self::doing_it_wrong( __METHOD__, $section->get_error_message(), '0.5.0' );
								} else {
									if  ( isset( $section_args['fields'] ) && is_array( $section_args['fields'] ) ) {
										foreach ( $section_args['fields'] as $field_slug => $field_args ) {
											$field = $section->add( new Field( $field_slug, $field_args ) );
											if ( is_wp_error( $field ) ) {
												self::doing_it_wrong( __METHOD__, $field->get_error_message(), '0.5.0' );
											}
										}
									}
								}
							}
						}
					}
				}
			}
		}

		/**
		 * Initializes the plugin framework.
		 *
		 * This function adds all components to the plugin. It is executed on the 'after_setup_theme' hook with priority 1.
		 * The action 'wpcd' should be used to add all the components.
		 *
		 * @internal
		 * @see WPCD\App::add_components()
		 * @see WPCD\App::add()
		 * @since 0.5.0
		 */
		public function init() {
			if ( ! $this->initialization_triggered ) {
				$this->initialization_triggered = true;

				ComponentManager::register_hierarchy( apply_filters( 'wpcd_class_hierarchy', array(
					'WPCD\Components\Panel'			=> array(
						'WPCD\Components\Section'		=> array(
							'WPCD\Components\Field'			=> array(),
						),
					),
				) ) );

				do_action( 'wpcd', $this );

				//TODO: hook into WPOD to create customizer panels from tabs

				$this->initialized = true;
			} else {
				self::doing_it_wrong( __METHOD__, __( 'This function should never be called manually.', 'wpcd' ), '0.5.0' );
			}
		}

		public function panel_validated( $args, $panel ) {
			if ( isset( $args['sections'] ) ) {
				unset( $args['sections'] );
			}
			return $args;
		}

		public function section_validated( $args, $section ) {
			if ( isset( $args['fields'] ) ) {
				unset( $args['fields'] );
			}
			return $args;
		}
	}
}
