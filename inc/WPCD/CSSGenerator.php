<?php
/**
 * @package WPCD
 * @version 0.5.0
 * @author Felix Arntz <felix-arntz@leaves-and-love.net>
 */

namespace WPCD;

use WPDLib\Components\Manager as ComponentManager;

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

if ( ! class_exists( 'WPCD\CSSGenerator' ) ) {
	/**
	 * This class performs the necessary actions in the WordPress customizer.
	 *
	 * This includes both registering and displaying customizer panels, sections and fields, as well as including the required scripts.
	 *
	 * @internal
	 * @since 0.5.0
	 */
	class CSSGenerator {

		/**
		 * @since 0.5.0
		 * @var WPCD\CSSGenerator|null Holds the instance of this class.
		 */
		private static $instance = null;

		/**
		 * Gets the instance of this class. If it does not exist, it will be created.
		 *
		 * @since 0.5.0
		 * @return WPCD\CSSGenerator
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
			add_action( 'after_setup_theme', array( $this, 'add_hooks' ), 99 );
		}

		public function add_hooks() {
			$this->reduce_query_load();

			add_action( 'init', array( $this, 'add_query_var' ), 1 );
			add_action( 'init', array( $this, 'add_rewrite_rule' ), 1 );
			add_action( 'pre_get_posts', array( $this, 'maybe_show_customizer_stylesheet' ), 1, 1 );
			add_filter( 'redirect_canonical', array( $this, 'fix_canonical' ), 10, 1 );

			if ( is_customize_preview() ) {
				add_action( 'wp_head', array( $this, 'print_customizer_styles' ) );
			} else {
				add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_customizer_styles' ) );
			}
		}

		/**
		 * Reduces query load on a request for the customizer stylesheet by removing unnecessary actions.
		 *
		 * This function was basically taken from the Yoast SEO plugin.
		 *
		 * @since 0.5.0
		 */
		public function reduce_query_load() {
			if ( ! isset( $_SERVER['REQUEST_URI'] ) ) {
				return;
			}

			$filename = $this->get_customizer_file_name();

			if ( false !== stripos( $_SERVER['REQUEST_URI'], $filename ) ) {
				remove_all_actions( 'widgets_init' );
			}
		}

		/**
		 * Adds a query var to check for the customizer stylesheet.
		 *
		 * @since 0.5.0
		 */
		public function add_query_var() {
			global $wp;

			if ( ! is_object( $wp ) ) {
				return;
			}

			$wp->add_query_var( 'wpcd_customizerstylesheet' );
		}

		/**
		 * Adds a rewrite rule for the customizer stylesheet.
		 *
		 * @since 0.5.0
		 */
		public function add_rewrite_rule() {
			$filename = $this->get_customizer_file_name();

			add_rewrite_rule( str_replace( '.', '\.', $filename ) . '$', 'index.php?wpcd_customizerstylesheet=1', 'top' );
		}

		/**
		 * Fixes the canonical redirect for the customizer stylesheet by preventing a trailing slash.
		 *
		 * @since 0.5.0
		 * @param mixed $redirect argument passed by WordPress
		 * @return mixed returns false on a customizer stylesheet request
		 */
		public function fix_canonical( $redirect ) {
			$stylesheet = get_query_var( 'wpcd_customizerstylesheet' );
			if ( empty( $stylesheet ) ) {
				return $redirect;
			}

			return false;
		}

		/**
		 * Shows the customizer stylesheet if the query var is set.
		 *
		 * @since 0.5.0
		 * @param WP_Query $query the query object to check
		 */
		public function maybe_show_customizer_stylesheet( $query ) {
			if ( ! $query->is_main_query() ) {
				return;
			}

			$stylesheet = get_query_var( 'wpcd_customizerstylesheet' );
			if ( empty( $stylesheet ) ) {
				return;
			}

			$this->show_customizer_stylesheet();
		}

		public function show_customizer_stylesheet() {
			if ( ! headers_sent() ) {
				$filename = $this->get_customizer_file_name();

				header( ( ( isset( $_SERVER['SERVER_PROTOCOL'] ) && $_SERVER['SERVER_PROTOCOL'] !== '' ) ? sanitize_text_field( $_SERVER['SERVER_PROTOCOL'] ) : 'HTTP/1.1' ) . ' 200 OK', true, 200 );
				header( 'X-Robots-Tag: noindex, follow', true );
				header( 'Content-Type: text/css' );
				header( 'Content-Disposition: inline; filename="' . $filename . '"' );
			}

			$this->print_customizer_styles( false );

			remove_all_actions( 'wp_footer' );
			die();
		}

		public function print_customizer_styles( $wrap_style_tags = true ) {
			$panels = ComponentManager::get( '*', 'WPCD\Components\Panel' );
			foreach ( $panels as $panel ) {
				foreach ( $panel->get_children() as $section ) {
					foreach ( $section->get_children() as $field ) {
						$preview_args = $field->preview_args;
						if ( 'update_style' === $preview_args['update_callback'] ) {
							$value = wpcd_get_customizer_setting( $panel->slug, $field->slug );
							if ( $wrap_style_tags ) {
								echo '<style type="text/css" id="wpcd-customizer-style-' . $this->sanitize_id( $field->_id ) . '">';
							}
							$this->print_field_styles( $value, $preview_args['update_args'] );
							if ( $wrap_style_tags ) {
								echo '</style>' . "\n";
							}
						}
					}
				}
			}
		}

		public function enqueue_customizer_styles() {
			wp_enqueue_style( 'wpcd-customizer-styles', $this->get_customizer_file_url() );
		}

		private function print_field_styles( $value, $args ) {
			if ( $value ) {
				//TODO: preprocess value
				foreach ( $args as $data ) {
					$this->print_single_style( $value, $data );
				}
			}
		}

		private function print_single_style( $value, $data ) {
			if ( ! empty( $data['media_query'] ) ) {
?>
<?php echo $data['media_query']; ?> {
	<?php echo implode( ', ', $data['selectors'] ); ?> {
		<?php echo $data['property']; ?>: <?php echo $data['prefix'] . $value . $data['suffix']; ?>
	}
}
<?php
			} else {
?>
<?php echo implode( ', ', $data['selectors'] ); ?> {
	<?php echo $data['property']; ?>: <?php echo $data['prefix'] . $value . $data['suffix']; ?>
}
<?php
			}
		}

		private function get_customizer_file_url() {
			global $wp_rewrite;

			$base = $wp_rewrite->using_index_permalinks() ? 'index.php/' : '/';

			return home_url( $base . $this->get_customizer_file_name() );
		}

		private function get_customizer_file_name() {
			return 'wpcd-customizer-styles.css';
		}

		private function sanitize_id( $id ) {
			return implode( '-', explode( '[', str_replace( ']', '', $id ) ) );
		}
	}
}
