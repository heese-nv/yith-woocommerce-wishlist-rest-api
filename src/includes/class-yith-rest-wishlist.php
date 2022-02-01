<?php
/**
 * YITH wish list REST plugin.
 *
 * @package YITH\Wishlist
 */

if ( ! class_exists( '\YITH\Wishlist\Rest_Plugin' ) ) {
	/**
	 * Class RestPlugin
	 */
	class Yith_Rest_Wishlist {

		/**
		 * Plugin version.
		 *
		 * @var string
		 */
		public $version = '1.0.0';


		/**
		 * Constructor.
		 */
		public function __construct() {
			$this->define_constants();
			$this->load_dependencies();

			$this->init_hooks();
		}

		/**
		 * Hook into actions and filters.
		 */
		private function init_hooks() {
			add_action( 'init', array( Yrw_Rest_API::class, 'init' ) );

			// YITH Wishlist loads its data stores at priority 20.
			add_action( 'yith_wcwl_init', array( $this, 'load_yith_wishlist_dependencies' ), 99 );
		}

		/**
		 * Load the classes and register hooks and filters depending on the presence of YITH Wishlist.
		 *
		 * @noinspection PhpUndefinedConstantInspection
		 * @noinspection PhpIncludeInspection
		 */
		public function load_yith_wishlist_dependencies() {
			require_once YITH_REST_WISHLIST_ABSPATH . '/includes/datastores/class-yrw-wishlist-data-store.php';

			add_action( 'woocommerce_data_stores', array( $this, 'register_data_stores' ), 9999 );
		}

		/**
		 * Register our own data store for wishlists.
		 *
		 * @param array $data_stores Array of registered data stores.
		 * @return array Array of filtered data store
		 */
		public function register_data_stores( $data_stores ) {
			$data_stores['wishlist'] = 'YRW_Wishlist_Data_Store';

			return $data_stores;
		}

		/**
		 * Define WC Constants.
		 */
		private function define_constants() {
			$this->define( 'YITH_REST_WISHLIST_ABSPATH', dirname( YITH_WISHLIST_REST_PLUGIN_FILE ) );
			$this->define( 'YITH_REST_WISHLIST_PLUGIN_BASENAME', plugin_basename( YITH_WISHLIST_REST_PLUGIN_FILE ) );
			$this->define( 'YITH_REST_WISHLIST_VERSION', $this->version );
		}

		/**
		 * Define constant if not already set.
		 *
		 * @param string      $name  Constant name.
		 * @param string|bool $value Constant value.
		 */
		private function define( string $name, $value ) {
			if ( ! defined( $name ) ) {
				define( $name, $value );
			}
		}

		/**
		 * Include required core files used in admin and on the frontend.
		 *
		 * @noinspection PhpUndefinedConstantInspection
		 * @noinspection PhpIncludeInspection
		 */
		public function load_dependencies() {
			include_once YITH_REST_WISHLIST_ABSPATH . '/includes/class-yrw-capability.php';
			include_once YITH_REST_WISHLIST_ABSPATH . '/includes/functions.php';
			include_once YITH_REST_WISHLIST_ABSPATH . '/rest/class-yrw-rest-api.php';
		}

		/**
		 * Init the plugin.
		 */
		public static function init() {
		}

		/**
		 * Run the loader to execute all of the hooks with WordPress.
		 *
		 * @since    1.0.0
		 */
		public function run() {
			// empty.
		}
	}
}
