<?php
/**
 * Plugin Name: Metaboxes
 * Plugin URI: https://github.com/wolfthemes/wolf-metaboxes
 * Description: Add metaboxes to your theme.
 * Version: 1.0.5
 * Author: WolfThemes
 * Author URI: http://wolfthemes.com
 * Requires at least: 5.0
 * Tested up to: 5.5
 *
 * Text Domain: wolf-metaboxes
 * Domain Path: /languages/
 *
 * @package WolfMetaboxes
 * @category Core
 * @author WolfThemes
 *
 * Verified customers who have purchased a premium theme at https://wlfthm.es/tf/
 * will have access to support for this plugin in the forums
 * https://wlfthm.es/help/
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Wolf_Metaboxes_Plugin' ) ) {
	/**
	 * Main Wolf_Metaboxes_Plugin Class
	 *
	 * Contains the main functions for Wolf_Metaboxes_Plugin
	 *
	 * @class Wolf_Metaboxes_Plugin
	 * @version 1.0.5
	 * @since 1.0.0
	 */
	class Wolf_Metaboxes_Plugin {

		/**
		 * @var string
		 */
		public $version = '1.0.5';

		/**
		 * @var Wolf Metaboxes The single instance of the class
		 */
		protected static $_instance = null;



		/**
		 * @var the support forum URL
		 */
		private $support_url = 'https://help.wolfthemes.com/';

		/**
		 * @var string
		 */
		public $template_url;

		/**
		 * Main Wolf Metaboxes Instance
		 *
		 * Ensures only one instance of Wolf Metaboxes is loaded or can be loaded.
		 *
		 * @static
		 * @see WVCCB()
		 * @return Wolf Metaboxes - Main instance
		 */
		public static function instance() {
			if ( is_null( self::$_instance ) ) {
				self::$_instance = new self();
			}
			return self::$_instance;
		}

		/**
		 * Wolf Metaboxes Constructor.
		 */
		public function __construct() {
			$this->define_constants();
			$this->includes();
			$this->init_hooks();

			// Plugin update notifications
			add_action( 'admin_init', array( $this, 'plugin_update' ) );
		}

		/**
		 * Hook into actions and filters
		 */
		private function init_hooks() {
			add_action( 'init', array( $this, 'init' ), 0 );
		}

		/**
		 * Define WR Constants
		 */
		private function define_constants() {

			$constants = array(
				'WMBOX_DEV' => false,
				'WMBOX_DIR' => $this->plugin_path(),
				'WMBOX_URI' => $this->plugin_url(),
				'WMBOX_CSS' => $this->plugin_url() . '/assets/css',
				'WMBOX_JS' => $this->plugin_url() . '/assets/js',
				'WMBOX_SLUG' => plugin_basename( dirname( __FILE__ ) ),
				'WMBOX_PATH' => plugin_basename( __FILE__ ),
				'WMBOX_VERSION' => $this->version,
				'WMBOX_SUPPORT_URL' => $this->support_url,
			);

			foreach ( $constants as $name => $value ) {
				$this->define( $name, $value );
			}
		}

		/**
		 * Define constant if not already set
		 * @param  string $name
		 * @param  string|bool $value
		 */
		private function define( $name, $value ) {
			if ( ! defined( $name ) ) {
				define( $name, $value );
			}
		}

		/**
		 * Include required core files used in admin and on the frontend.
		 */
		public function includes() {

			if ( is_admin() ) {
				include_once( 'inc/admin/lib/class-metabox-tabs.php' );
				include_once( 'inc/admin/admin-functions.php' );
			}
		}

		/**
		 * Init Wolf Metaboxes when WordPress Initialises.
		 */
		public function init() {

			// Set up localisation
			$this->load_plugin_textdomain();
		}

		/**
		 * Loads the plugin text domain for translation
		 */
		public function load_plugin_textdomain() {

			$domain = 'wolf-metaboxes';
			$locale = apply_filters( 'wolf-metaboxes', get_locale(), $domain );
			load_textdomain( $domain, WP_LANG_DIR . '/' . $domain . '/' . $domain . '-' . $locale . '.mo' );
			load_plugin_textdomain( $domain, FALSE, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
		}

		/**
		 * Plugin update
		 */
		public function plugin_update() {

			if ( ! class_exists( 'WP_GitHub_Updater' ) ) {
				include_once 'inc/admin/updater.php';
			}

			$repo = 'wolfthemes/wolf-metaboxes';

			$config = array(
				'slug' => plugin_basename( __FILE__ ),
				'proper_folder_name' => 'wolf-metaboxes',
				'api_url' => 'https://api.github.com/repos/' . $repo . '',
				'raw_url' => 'https://raw.github.com/' . $repo . '/master/',
				'github_url' => 'https://github.com/' . $repo . '',
				'zip_url' => 'https://github.com/' . $repo . '/archive/master.zip',
				'sslverify' => true,
				'requires' => '5.0',
				'tested' => '5.5',
				'readme' => 'README.md',
				'access_token' => '',
			);

			new WP_GitHub_Updater( $config );
		}

		/**
		 * Get the plugin url.
		 * @return string
		 */
		public function plugin_url() {
			return untrailingslashit( plugins_url( '/', __FILE__ ) );
		}

		/**
		 * Get the plugin path.
		 * @return string
		 */
		public function plugin_path() {
			return untrailingslashit( plugin_dir_path( __FILE__ ) );
		}
	} // end class
} // end class check

/**
 * Returns the main instance of Wolf_Metaboxes to prevent the need to use globals.
 *
 * @return Wolf_Metaboxes
 */
function WOLFMETABOXES() {
	return Wolf_Metaboxes_Plugin::instance();
}

WOLFMETABOXES(); // Go
