<?php
/**
 * Plugin Name: YITH WooCommerce Wishlist REST API
 * Plugin URI: https://github.com/prionkor/
 * Description: Provides REST API Interface for <code><strong>YITH WooCommerce Wishlist</strong></code> plugin.
 * Version: 0.1.0
 * Author: Sisir K. Adhikari
 * Author URI: https://codeware.io/
 * Text Domain: yith-woocommerce-wishlist-rest-api
 * Domain Path: /languages/
 * WC requires at least: 4.2.0
 * WC tested up to: 5.7.2
 *
 * @author  Sisir
 * @package YITH WooCommerce Wishlist REST API
 * @version 0.1.0
 */

/*
	Copyright 2020  Sisir Adhikari  (email : sisir@codeware.io)

	This program is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License, version 2, as
	published by the Free Software Foundation.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program; if not, write to the Free Software
	Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301 USA
*/

use YITH\Wishlist\Rest_Plugin;

defined( 'ABSPATH' ) || exit;

/**
 * Load the plugin.
 */
function yith_rest_load() {
	if ( defined( 'YITH_WCWL' ) && YITH_WCWL ) {
		require_once __DIR__ . '/rest/includes.php';
		Rest_Plugin::init();
	}
}

add_action( 'init', 'yith_rest_load' );

