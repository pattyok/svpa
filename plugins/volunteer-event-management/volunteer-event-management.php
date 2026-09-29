<?php
/**
 * Plugin Name:       Volunteer Event Management
 * Description:       Manage volunteer events and registrations from Salesforce Volunteers.
 * Requires at least: 5.9
 * Requires PHP:      7.0
 * Version:           2.0.0
 * Author:            Patty O&#39;Hara
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       volunteer-event-management
 *
 * @package           volunteer-event-management
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'VEMgmt' ) ) {
	/**
	 * Main VEMgmt Class.
	 *
	 * @since 1.0
	 */
	final class VEMgmt {
		/**
		 * The plugin's instance
		 *
		 * @var VEMgmt the main var
		 * @since 1.0
		 */

		private static $instance;

		/**
		 * Main VEMgmt instance
		 *
		 * Insures only one instance exists. Also prevents needing to define globals all around.
		 *
		 * @since 1.0.0
		 * @static
		 * @return object|VEMgmt
		 */

		public static function instance() {
			if ( ! isset( self::$instance ) && ! ( self::$instance instanceof VEMgmt ) ) {
				self::$instance = new VEMgmt();
				self::$instance->init();
				self::$instance->setup_constants();
				self::$instance->asset_suffix();
				self::$instance->includes();
			}
			return self::$instance;
		}

		/**
		 * Throw error on object clone.
		 *
		 * The whole idea of the singleton design pattern is that there is a single
		 * object therefore, we don't want the object to be cloned.
		 *
		 * @since 1.0.0
		 * @access protected
		 * @return void
		 */
		public function __clone() {
			// Cloning instances of the class is forbidden.
			_doing_it_wrong( __FUNCTION__, esc_html__( 'Cheating huh?', 'volunteer-event-management' ), '1.0' );
		}

		/**
		 * Disable unserializing of the class.
		 *
		 * @since 1.0.0
		 * @access protected
		 * @return void
		 */
		public function __wakeup() {
			// Unserializing instances of the class is forbidden.
			_doing_it_wrong( __FUNCTION__, esc_html__( 'Cheating huh?', 'volunteer-event-management' ), '1.0' );
		}


		/**
		 * Setup plugin constants.
		 *
		 * @access private
		 * @since 1.0.0
		 * @return void
		 */
		private function setup_constants() {

			$this->define( 'VEMGMT_DEBUG', true );
			$this->define( 'VEMGMT_VERSION', '1.2' );
			$this->define( 'VEMGMT_HAS_PRO', false );
			$this->define( 'VEMGMT_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
			$this->define( 'VEMGMT_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
			$this->define( 'VEMGMT_PLUGIN_FILE', __FILE__ );
			$this->define( 'VEMGMT_PLUGIN_BASE', plugin_basename( __FILE__ ) );
		}

		/**
		 * Define constant if not already set.
		 *
		 * @param  string|string $name Name of the definition.
		 * @param  string|bool   $value Default value.
		 */
		private function define( $name, $value ) {
			if ( ! defined( $name ) ) {
				define( $name, $value );
			}
		}

		/**
		 * Include required files.
		 *
		 * @access private
		 * @since 4.1
		 * @return void
		 */
		private function includes() {
			require_once VEMGMT_PLUGIN_DIR . 'includes/vemgmt-class-block-register.php';
			require_once VEMGMT_PLUGIN_DIR . 'includes/vemgmt-class-post-types.php';
			require_once VEMGMT_PLUGIN_DIR . 'includes/vemgmt-class-object-sync.php';
			require_once VEMGMT_PLUGIN_DIR . 'includes/vemgmt-class-helpers.php';
			// Loaded before the settings class, which references VEMgmt_Cleanup constants.
			require_once VEMGMT_PLUGIN_DIR . 'includes/vemgmt-class-cleanup.php';
			require_once VEMGMT_PLUGIN_DIR . 'includes/vemgmt-class-settings.php';
			require_once VEMGMT_PLUGIN_DIR . 'includes/vemgmt-class-form-validation.php';
		}

		/**
		 * Load actions
		 *
		 * @return void
		 */
		private function init() {
			add_action( 'plugins_loaded', array( $this, 'load_textdomain' ), 99 );
			add_action( 'enqueue_block_editor_assets', array( $this, 'block_localization' ) );
		}

		/**
		 * Change the plugin's minified or src file name, based on debug mode.
		 *
		 * @since 1.0.0
		 */
		public function asset_suffix() {
			if ( true === VEMGMT_DEBUG ) {
				define( 'VEMGMT_ASSET_SUFFIX', null );
			} else {
				define( 'VEMGMT_ASSET_SUFFIX', '.min' );
			}
		}

		/**
		 * If debug is on, serve unminified source assets.
		 *
		 * @since 1.0.0
		 * @param string|string $type The type of resource.
		 * @param string|string $directory Any extra directories needed.
		 */
		public function asset_source( $type = 'js', $directory = null ) {
			if ( 'js' !== $type ) {
				return VEMGMT_PLUGIN_URL . 'build/css/' . $directory;
			}

			if ( true === VEMGMT_DEBUG ) {
				return VEMGMT_PLUGIN_URL . 'src/' . $type . '/' . $directory;
			}

			return VEMGMT_PLUGIN_URL . 'build/' . $type . '/' . $directory;
		}

		/**
		 * Loads the plugin language files.
		 *
		 * @access public
		 * @since 1.0.0
		 * @return void
		 */
		public function load_textdomain() {
			load_plugin_textdomain( 'block-options', false, dirname( plugin_basename( VEMGMT_PLUGIN_DIR ) ) . '/languages/' );
		}

		/**
		 * Enqueue localization data for our blocks.
		 *
		 * @access public
		 */
		public function block_localization() {
			if ( function_exists( 'wp_set_script_translations' ) ) {
				wp_set_script_translations( 'mappedposts-editor', 'mappedposts' );
			}
		}



	}
}


/**
 * The main function for that returns the main class
 *
 * The main function responsible for returning the one true version
 * Instance to functions everywhere.
 *
 * Use this function like you would a global variable, except without needing
 * to declare the global.
 *
 * Example: <?php $blockopts = VEMgmt(); ?>
 *
 * @since 1.0
 * @return object|VEMgmt The one true EditorsKit Instance.
 */
function volunteer_event_management() {
	return VEMgmt::instance();
}

// Get Plugin Running.
if ( function_exists( 'is_multisite' ) && is_multisite() ) {
	// Get Plugin Running. Load on plugins_loaded action to avoid issue on multisite.
	add_action( 'plugins_loaded', 'volunteer_event_management' );
} else {
	volunteer_event_management();
}

