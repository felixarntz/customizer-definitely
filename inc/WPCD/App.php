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

			//TODO: generate wpcd-customizer.css file dynamically from all 'update_style' fields, auto-include like a normal stylesheet (use rewrites)

			add_filter( 'plugin_action_links_' . plugin_basename( self::get_info( 'main_file' ) ), array( $this, 'add_action_link' ) );
			add_action( 'admin_notices', array( $this, 'display_admin_notice' ) );
			add_action( 'wp_ajax_wpcd_dismiss_notice', array( $this, 'ajax_dismiss_notice' ) );
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
		 * This function adds all components to the plugin. It is executed on the 'after_setup_theme' hook with priority 3.
		 * The action 'wpcd' should be used to add all the components.
		 *
		 * @internal
		 * @see WPCD\App::add_components()
		 * @see WPCD\App::add()
		 * @since 0.5.0
		 */
		public function init() {
			if ( ! did_action( 'wpcd' ) ) {
				ComponentManager::register_hierarchy( apply_filters( 'wpcd_class_hierarchy', array(
					'WPCD\Components\Panel'			=> array(
						'WPCD\Components\Section'		=> array(
							'WPCD\Components\Field'			=> array(),
						),
					),
				) ) );

				do_action( 'wpcd', $this );

				//TODO: hook into WPOD to create customizer panels from tabs
			} else {
				self::doing_it_wrong( __METHOD__, __( 'This function should never be called manually.', 'customizer-definitely' ), '0.5.0' );
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

		/**
		 * This filter adds a link to the framework guide to the plugins table.
		 *
		 * @internal
		 * @since 0.5.0
		 * @param array $links the original links
		 * @return array the modified links
		 */
		public function add_action_link( $links = array() ) {
			$custom_links = array(
				'<a href="' . 'https://github.com/felixarntz/customizer-definitely/wiki' . '" target="_blank">' . __( 'Guide', 'customizer-definitely' ) . '</a>',
			);

			return array_merge( $custom_links, $links );
		}

		/**
		 * This function displays and admin notice that the framework is active.
		 *
		 * The notice will only be shown if the corresponding option is set.
		 * Once the notice is hidden, it will not show again until the plugin is deactivated and then activated again.
		 *
		 * @internal
		 * @since 0.5.0
		 */
		public function display_admin_notice() {
			$setting = get_option( 'customizer_definitely_notice' );
			if ( ! $setting ) {
				return;
			}

			if ( ! current_user_can( 'manage_options' ) ) {
				return;
			}

			?>
			<script type="text/javascript">
				jQuery( document ).ready( function( $ ) {
					$( document ).on( 'click', '#customizer-definitely-notice .notice-dismiss', function( e ) {
						$.ajax( '<?php echo admin_url( "admin-ajax.php" ); ?>', {
							data: {
								action: 'wpcd_dismiss_notice'
							},
							dataType: 'json',
							method: 'POST'
						});
					});
				});
			</script>

			</script>
			<div id="customizer-definitely-notice" class="notice updated is-dismissible hide-if-no-js">
				<p>
					<?php if ( 'activated' === $setting ) : ?>
						<?php printf( __( 'You have just activated %s.', 'customizer-definitely' ), '<strong>' . self::get_info( 'name' ) . '</strong>' ); ?>
					<?php else : ?>
						<?php printf( __( 'You are running the plugin %s on your site.', 'customizer-definitely' ), '<strong>' . self::get_info( 'name' ) . '</strong>' ); ?>
					<?php endif; ?>
					<?php _e( 'This plugin is a framework that developers can leverage to quickly add panels, sections and fields to the customizer, including actual live previewing capabilities.', 'customizer-definitely' ); ?>
				</p>
				<p>
					<?php printf( __( 'For a guide on how to use the framework please read the <a href="%s" target="_blank">Wiki</a>.', 'customizer-definitely' ), 'https://github.com/felixarntz/customizer-definitely/wiki' ); ?>
				</p>
			</div>
			<?php

			if ( 'activated' === $setting ) {
				update_option( 'customizer_definitely_notice', 'active' );
			}
		}

		/**
		 * This function is an AJAX function that is run when the plugin's admin notice is dismissed.
		 *
		 * The function ensures that the notice is dismissed permanently.
		 *
		 * @internal
		 * @since 0.5.0
		 */
		public function ajax_dismiss_notice() {
			delete_option( 'customizer_definitely_notice' );

			wp_send_json_success();
		}

		/**
		 * Activation function.
		 *
		 * This function is run automatically when the plugin is activated.
		 *
		 * @internal
		 * @since 0.5.0
		 */
		public static function activate() {
			add_option( 'customizer_definitely_notice', 'activated' );
		}
	}
}
