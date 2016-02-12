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
use WPDLib\FieldTypes\Manager as FieldManager;
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
			FieldManager::init();

			Customizer::instance();

			// use after_setup_theme action so it is initialized as soon as possible, but also so that both plugins and themes can use the action
			add_action( 'after_setup_theme', array( $this, 'init' ), 3 );

			add_filter( 'wpcd_panel_validated', array( $this, 'panel_validated' ), 10, 2 );
			add_filter( 'wpcd_section_validated', array( $this, 'section_validated' ), 10, 2 );

			//TODO: generate wpcd-customizer.css file dynamically from all 'update_style' fields, auto-include like a normal stylesheet (use rewrites)
		}

		/**
		 * Sets the current scope.
		 *
		 * The scope is an internal identifier. When adding a component, it will be added to the currently active scope.
		 * Therefore every plugin or theme should define its own unique scope to prevent conflicts.
		 *
		 * @since 0.5.0
		 * @param string $scope the current scope to set
		 */
		public function set_scope( $scope ) {
			ComponentManager::set_scope( $scope );
		}

		/**
		 * Adds a toplevel component.
		 *
		 * This function should be utilized when using the plugin manually.
		 * Every component has an `add()` method to add subcomponents to it, however if you want to add toplevel components, use this function.
		 *
		 * @since 0.5.0
		 * @param WPDLib\Components\Base $component the component object to add
		 * @return WPDLib\Components\Base|WP_Error either the component added or a WP_Error object if an error occurred
		 */
		public function add( $component ) {
			return ComponentManager::add( $component );
		}

		/**
		 * Takes an array of hierarchically nested components and adds them.
		 *
		 * This function is the general function to add an array of components.
		 * You should call it from your plugin or theme within the 'wpcd' action.
		 *
		 * @since 0.5.0
		 * @param array $components the components to add
		 * @param string $scope the scope to add the components to
		 */
		public function add_components( $components, $scope = '' ) {
			$this->set_scope( $scope );

			if ( is_array( $components ) ) {
				$this->add_panels( $components );
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

		/**
		 * Callback function to run after a panel has been validated.
		 *
		 * @internal
		 * @since 0.5.0
		 * @param array $args the panel arguments
		 * @param WPCD\Components\Panel $panel the current panel object
		 * @return array the adjusted panel arguments
		 */
		public function panel_validated( $args, $panel ) {
			if ( isset( $args['sections'] ) ) {
				unset( $args['sections'] );
			}
			return $args;
		}

		/**
		 * Callback function to run after a section has been validated.
		 *
		 * @internal
		 * @since 0.5.0
		 * @param array $args the section arguments
		 * @param WPCD\Components\Section $section the current section object
		 * @return array the adjusted section arguments
		 */
		public function section_validated( $args, $section ) {
			if ( isset( $args['fields'] ) ) {
				unset( $args['fields'] );
			}
			return $args;
		}

		/**
		 * Adds panels and their subcomponents.
		 *
		 * @internal
		 * @since 0.5.0
		 * @param array $panels the panels to add as $panel_slug => $panel_args
		 */
		protected function add_panels( $panels ) {
			foreach ( $panels as $panel_slug => $panel_args ) {
				$panel = $this->add( new Panel( $panel_slug, $panel_args ) );
				if ( is_wp_error( $panel ) ) {
					self::doing_it_wrong( __METHOD__, $panel->get_error_message(), '0.5.0' );
				} elseif ( isset( $panel_args['sections'] ) && is_array( $panel_args['sections'] ) ) {
					$this->add_sections( $panel_args['sections'], $panel );
				}
			}
		}

		/**
		 * Adds sections and their subcomponents.
		 *
		 * @internal
		 * @since 0.5.0
		 * @param array $sections the sections to add as $section_slug => $section_args
		 * @param WPCD\Components\Panel $panel the post type to add the sections to
		 */
		protected function add_sections( $sections, $panel ) {
			foreach ( $sections as $section_slug => $section_args ) {
				$section = $panel->add( new Section( $section_slug, $section_args ) );
				if ( is_wp_error( $section ) ) {
					self::doing_it_wrong( __METHOD__, $section->get_error_message(), '0.5.0' );
				} elseif ( isset( $section_args['fields'] ) && is_array( $section_args['fields'] ) ) {
					$this->add_fields( $section_args['fields'], $section );
				}
			}
		}

		/**
		 * Adds fields.
		 *
		 * @internal
		 * @since 0.5.0
		 * @param array $fields the fields to add as $field_slug => $field_args
		 * @param WPCD\Components\Section $section the metabox to add the fields to
		 */
		protected function add_fields( $fields, $section ) {
			foreach ( $fields as $field_slug => $field_args ) {
				$field = $section->add( new Field( $field_slug, $field_args ) );
				if ( is_wp_error( $field ) ) {
					self::doing_it_wrong( __METHOD__, $field->get_error_message(), '0.5.0' );
				}
			}
		}

		/**
		 * Adds a link to the framework guide to the plugins table.
		 *
		 * @internal
		 * @since 0.5.0
		 * @param array $links the original links
		 * @return array the modified links
		 */
		public static function filter_plugin_links( $links = array() ) {
			$custom_links = array(
				'<a href="' . 'https://github.com/felixarntz/customizer-definitely/wiki' . '">' . __( 'Guide', 'customizer-definitely' ) . '</a>',
			);

			return array_merge( $custom_links, $links );
		}

		/**
		 * Adds a link to the framework guide to the network plugins table.
		 *
		 * @internal
		 * @since 0.5.0
		 * @param array $links the original links
		 * @return array the modified links
		 */
		public static function filter_network_plugin_links( $links = array() ) {
			return self::filter_plugin_links( $links );
		}

		/**
		 * Renders a plugin information message.
		 *
		 * @internal
		 * @since 0.5.0
		 * @param string $status either 'activated' or 'active'
		 * @param string $context either 'site' or 'network'
		 */
		public static function render_status_message( $status, $context = 'site' ) {
			?>
			<p>
				<?php if ( 'activated' === $status ) : ?>
					<?php printf( __( 'You have just activated %s.', 'customizer-definitely' ), '<strong>' . self::get_info( 'name' ) . '</strong>' ); ?>
				<?php elseif ( 'network' === $context ) : ?>
					<?php printf( __( 'You are running the plugin %s on your network.', 'customizer-definitely' ), '<strong>' . self::get_info( 'name' ) . '</strong>' ); ?>
				<?php else : ?>
					<?php printf( __( 'You are running the plugin %s on your site.', 'customizer-definitely' ), '<strong>' . self::get_info( 'name' ) . '</strong>' ); ?>
				<?php endif; ?>
				<?php _e( 'This plugin is a framework that developers can leverage to quickly add panels, sections and fields to the customizer, including actual live previewing capabilities.', 'customizer-definitely' ); ?>
			</p>
			<p>
				<?php printf( __( 'For a guide on how to use the framework please read the <a href="%s">Wiki</a>.', 'customizer-definitely' ), 'https://github.com/felixarntz/customizer-definitely/wiki' ); ?>
			</p>
			<?php
		}

		/**
		 * Renders a network plugin information message.
		 *
		 * @internal
		 * @since 0.5.0
		 * @param string $status either 'activated' or 'active'
		 * @param string $context either 'site' or 'network'
		 */
		public static function render_network_status_message( $status, $context = 'network' ) {
			self::render_status_message( $status, $context );
		}
	}
}
