<?php
/**
 * Plugin Name: Carkeek Site Blocks
 * Plugin URI: https://carkeekstudios.com/
 * Description: Series of blocks custom built for this site
 * Author: pattyok
 * Version: 1.0
 * Author URI https://carkeekstudios.com/
 * Text Domain: carkeek-blocks
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'CarkeekSiteBlocks' ) ) :
	/**
	 * Main CarkeekSiteBlocks Class.
	 *
	 * @since 1.0
	 */
	final class CarkeekSiteBlocks {
		/**
		 * The plugin's instance
		 *
		 * @var CarkeekSiteBlocks the main var
		 * @since 1.0
		 */

		private static $instance;

		/**
		 * Main CarkeekSiteBlocks instance
		 *
		 * Insures only one instance exists. Also prevents needing to define globals all around.
		 *
		 * @since 1.0.0
		 * @static
		 * @return object|CarkeekSiteBlocks
		 */

		public static function instance() {
			if ( ! isset( self::$instance ) && ! ( self::$instance instanceof CarkeekSiteBlocks ) ) {
				self::$instance = new CarkeekSiteBlocks();
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
			_doing_it_wrong( __FUNCTION__, esc_html__( 'Cheating huh?', 'carkeek-blocks' ), '1.0' );
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
			_doing_it_wrong( __FUNCTION__, esc_html__( 'Cheating huh?', 'carkeek-blocks' ), '1.0' );
		}


		/**
		 * Setup plugin constants.
		 *
		 * @access private
		 * @since 1.0.0
		 * @return void
		 */
		private function setup_constants() {

			$this->define( 'CARKEEKSITEBLOCKS_DEBUG', true );
			$this->define( 'CARKEEKSITEBLOCKS_VERSION', '1.2' );
			$this->define( 'CARKEEKSITEBLOCKS_HAS_PRO', false );
			$this->define( 'CARKEEKSITEBLOCKS_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
			$this->define( 'CARKEEKSITEBLOCKS_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
			$this->define( 'CARKEEKSITEBLOCKS_PLUGIN_FILE', __FILE__ );
			$this->define( 'CARKEEKSITEBLOCKS_PLUGIN_BASE', plugin_basename( __FILE__ ) );
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

			//require_once CARKEEKSITEBLOCKS_PLUGIN_DIR . 'includes/class-carkeeksiteblocks-block-assets.php';
			require_once CARKEEKSITEBLOCKS_PLUGIN_DIR . 'includes/class-carkeeksiteblocks-block-register.php';
			require_once CARKEEKSITEBLOCKS_PLUGIN_DIR . 'includes/class-carkeeksiteblocks-gauge-readings.php';


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
			if ( true === CARKEEKSITEBLOCKS_DEBUG ) {
				define( 'CARKEEKSITEBLOCKS_ASSET_SUFFIX', null );
			} else {
				define( 'CARKEEKSITEBLOCKS_ASSET_SUFFIX', '.min' );
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
				return CARKEEKSITEBLOCKS_PLUGIN_URL . 'build/css/' . $directory;
			}

			if ( true === CARKEEKSITEBLOCKS_DEBUG ) {
				return CARKEEKSITEBLOCKS_PLUGIN_URL . 'src/' . $type . '/' . $directory;
			}

			return CARKEEKSITEBLOCKS_PLUGIN_URL . 'build/' . $type . '/' . $directory;
		}

		/**
		 * Loads the plugin language files.
		 *
		 * @access public
		 * @since 1.0.0
		 * @return void
		 */
		public function load_textdomain() {
			load_plugin_textdomain( 'block-options', false, dirname( plugin_basename( CARKEEKSITEBLOCKS_PLUGIN_DIR ) ) . '/languages/' );
		}

		/**
		 * Enqueue localization data for our blocks.
		 *
		 * @access public
		 */
		public function block_localization() {
			if ( function_exists( 'wp_set_script_translations' ) ) {
				wp_set_script_translations( 'carkeeksiteblocks-editor', 'carkeeksiteblocks' );
			}
		}



	}

endif;


/**
 * The main function for that returns the main class
 *
 * The main function responsible for returning the one true version
 * Instance to functions everywhere.
 *
 * Use this function like you would a global variable, except without needing
 * to declare the global.
 *
 * Example: <?php $blockopts = CarkeekSiteBlocks(); ?>
 *
 * @since 1.0
 * @return object|CarkeekSiteBlocks The one true EditorsKit Instance.
 */
function carkeeksiteblocks() {
	return CarkeekSiteBlocks::instance();
}

// Get Plugin Running.
if ( function_exists( 'is_multisite' ) && is_multisite() ) {
	// Get Plugin Running. Load on plugins_loaded action to avoid issue on multisite.
	add_action( 'plugins_loaded', 'carkeeksiteblocks' );
} else {
	carkeeksiteblocks();
}
