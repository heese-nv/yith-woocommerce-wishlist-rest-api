<?php
/**
 * YITH wish list REST plugin.
 *
 * @package YITH\Wishlist
 */

namespace YITH\Wishlist;

if ( ! class_exists( '\YITH\Wishlist\Rest_Plugin' ) ) {
	/**
	 * Class RestPlugin
	 */
	class Rest_Plugin {
		/**
		 * Init the plugin.
		 */
		public static function init() {
			add_action( 'rest_api_init', array( Rest::class, 'init' ) );
		}
	}
}
