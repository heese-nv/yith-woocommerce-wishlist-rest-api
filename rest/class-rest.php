<?php
/**
 * REST API.
 *
 * @package YITH\Wishlist
 */

namespace YITH\Wishlist;

use Exception;
use WC_Cart;
use WC_Customer;
use WC_Session_Handler;
use WP_Error;
use WP_REST_Response;
use YITH_WCWL_Wishlist;
use YITH_WCWL_Wishlist_Factory;
use YITH_WCWL_Wishlist_Item;

if ( ! class_exists( '\YITH\Wishlist\Rest' ) ) {
	/**
	 * Class Rest
	 *
	 * @package YITH\Wishlist
	 */
	final class Rest {
		const REST_NAMESPACE = 'yith/wishlist';

		const REST_VERSION = 'v2';

		/**
		 * API definition.
		 *
		 * @var array[]
		 */
		protected static $wishlist_routes = array(
			'get_list'          => array( // get list of wishlist for given user.
				'route'               => '/wishlists',
				'methods'             => 'GET',
				'callback'            => array( __CLASS__, 'get_list' ),
				'permission_callback' => array( __CLASS__, 'check_auth' ),
			),
			'post'              => array( // create/update a wish list.
				'route'               => '/wishlists',
				'methods'             => 'POST',
				'callback'            => array( __CLASS__, 'post' ),
				'permission_callback' => array( __CLASS__, 'check_write_cap' ),
			),
			'get'               => array( // get single wishlist for given user.
				'route'               => '/wishlists/(?P<id>\d+)',
				'methods'             => 'GET',
				'callback'            => array( __CLASS__, 'get' ),
				'permission_callback' => array( __CLASS__, 'check_read_cap' ),
			),
			'put'               => array( // update single wishlist for given user.
				'route'               => '/wishlists/(?P<id>\d+)',
				'methods'             => 'PUT',
				'callback'            => array( __CLASS__, 'put' ),
				'permission_callback' => array( __CLASS__, 'check_write_cap' ),
			),
			'delete'            => array( // delete a wishlist.
				'route'               => '/wishlists/(?P<id>\d+)',
				'methods'             => 'DELETE',
				'callback'            => array( __CLASS__, 'delete' ),
				'permission_callback' => array( __CLASS__, 'check_write_cap' ),
			),
			'get_list_products' => array( // list products of a wishlist.
				'route'               => '/wishlists/(?P<id>\d+)/products',
				'methods'             => 'GET',
				'callback'            => array( __CLASS__, 'get_list_products' ),
				'permission_callback' => array( __CLASS__, 'check_read_cap' ),
			),
			'post_product'      => array( // add a product to wishlist.
				'route'               => '/wishlists/(?P<id>\d+)/products/(?P<product_id>\d+)',
				'methods'             => 'POST',
				'callback'            => array( __CLASS__, 'post_product' ),
				'permission_callback' => array( __CLASS__, 'check_write_cap' ),
			),
			'delete_product'    => array( // remove a product from wishlist.
				'route'               => '/wishlists/(?P<id>\d+)/products/(?P<product_id>\d+)',
				'methods'             => 'DELETE',
				'callback'            => array( __CLASS__, 'delete_product' ),
				'permission_callback' => array( __CLASS__, 'check_write_cap' ),
			),
		);

		/**
		 * Initialise REST API.
		 */
		public static function init() {
			self::register_routes();

			add_filter( 'woocommerce_is_rest_api_request', [ __CLASS__, 'simulate_as_not_rest' ] );
		}

		/**
		 * Register routes of REST API.
		 */
		protected static function register_routes() {
			do_action( 'yith_rest_wishlist_before_register_route' );

			$wishlist_routes = apply_filters( 'yith_rest_wishlist_routes', self::$wishlist_routes );

			$prefix = self::REST_NAMESPACE . '/' . self::REST_VERSION;
			foreach ( $wishlist_routes as $args ) {
				$route = $args['route'];
				unset( $args['route'] );
				register_rest_route( $prefix, $route, $args );
			}

			do_action( 'yith_rest_wishlist_after_register_route' );
		}

		/**
		 * We have to tell WC that this should not be handled as a REST request.
		 * Otherwise we can't use the product loop template contents properly.
		 * Since WooCommerce 3.6
		 *
		 * @param bool $is_rest_api_request
		 * @return bool
		 */
		public static function simulate_as_not_rest( $is_rest_api_request ) {
			if ( empty( $_SERVER['REQUEST_URI'] ) ) {
				return $is_rest_api_request;
			}

			// Bail early if this is not our request.

			// TODO INCLUDE
//			if ( false === strpos( $_SERVER['REQUEST_URI'], self::REST_NAMESPACE ) ) {
//				return $is_rest_api_request;
//			}

			return false;
		}

		/**
		 * Get array of wishlists for current user.
		 */
		public static function get_list() {
			$user_id    = get_current_user_id();
			$wish_lists = YITH_WCWL_Wishlist_Factory::get_wishlists( array( 'user_id' => $user_id ) );
			if ( ! $wish_lists ) {
				$wish_lists = array();
			}

			$dtos = array();
			foreach ( $wish_lists as $wish_list ) {
				$dtos[] = self::to_rest_wish_list( $wish_list );
			}

			return new WP_REST_Response( $dtos );

		}

		/**
		 * Creates a wishlist for current user.
		 */
		public static function post() {
			// TODO Implement.
		}

		/**
		 * Updates a wishlist for current user.
		 *
		 * @param array $request HTTP request.
		 * @return WP_REST_Response
		 */
		public static function put( $request ) {
			// TODO Implement.
			$id          = isset( $request['id'] ) ? $request['id'] : 0;
			$product_ids = isset( $request['product_ids'] ) ? $request['product_ids'] : 0;

			$id = isset( $request['id'] ) ? $request['id'] : 0;

			if ( ! $id || ! $product_ids ) {
				return new WP_REST_Response(
					array(
						'status' => 422,
						'error'  => 'Invalid id',
					),
					422
				);
			}

			$wish_list = self::load_wish_list( $id );
			if ( ! $wish_list ) {
				return self::response_not_found();
			}

			return new WP_REST_Response( self::to_rest_wish_list( $wish_list ) );
		}

		/**
		 * Retrieve a single wishlist item by ID.
		 *
		 * @param array $request HTTP request.
		 * @return WP_REST_Response
		 */
		public static function get( $request ) {
			$id        = self::get_sanitised_param( $request, 'id' );
			$wish_list = self::load_wish_list( $id );
			if ( ! $wish_list ) {
				return self::response_not_found();
			}

			return new WP_REST_Response( self::to_rest_wish_list( $wish_list ) );
		}

		/**
		 * Deletes a wishlist
		 *
		 * @param array $request HTTP request.
		 * @return WP_REST_Response
		 */
		public static function delete( $request ) {
			$id = isset( $request['id'] ) ? intval( $request['id'] ) : 0;

			return new WP_REST_Response( array( 'id' => $id ) );
		}

		/**
		 * List the products contained in a wish list.
		 *
		 * @param array $request HTTP request.
		 * @return WP_REST_Response
		 */
		public static function get_list_products( $request ) {
			$id = self::get_sanitised_param( $request, 'id' );

			$wish_list = self::load_wish_list( $id );
			if ( ! $wish_list ) {
				return self::response_not_found();
			}


			return new WP_REST_Response( self::to_rest_wish_list_items( $wish_list->get_items() ) );
		}

		/**
		 * Adds a product to given wish list
		 *
		 * @param array $request HTTP request.
		 * @return WP_REST_Response
		 */
		public static function post_product( $request ) {
			$wish_list_id = self::get_sanitised_param( $request, 'id' );
			$product_id   = self::get_sanitised_param( $request, 'product_id' );

			$wish_list = self::load_wish_list( $wish_list_id );
			if ( $product_id ) {
				$wish_list->add_product( $product_id );
				$wish_list->save();
			}

			return new WP_REST_Response( self::to_rest_wish_list_items( $wish_list->get_items() ) );
		}

		/**
		 * Removes a product from given wish list
		 *
		 * @param array $request HTTP request.
		 * @return WP_REST_Response
		 */
		public static function delete_product( $request ) {
			$wish_list_id = self::get_sanitised_param( $request, 'id' );
			$product_id   = self::get_sanitised_param( $request, 'product_id' );
			$add_to_cart  = self::get_sanitised_param( $request, 'add_to_cart' );

			$wish_list = self::load_wish_list( $wish_list_id );
			if ( $product_id ) {
				$can_remove = true;
				if ( $add_to_cart ) {
					$item       = $wish_list->get_product( $product_id );
					$quantity   = $item ? $item->get_quantity( 'edit' ) : 1;
					$can_remove = self::add_to_cart( $product_id, $quantity );
				}

				if ( $can_remove ) {
					$wish_list->remove_product( $product_id );
					$wish_list->save();
				}
			}

			return new WP_REST_Response( self::to_rest_wish_list_items( $wish_list->get_items() ) );
		}

		/**
		 * Add product to the user's cart.
		 *
		 * @param int $product_id product ID.
		 * @param int $quantity   quantity of product.
		 * @return bool whether or not the product was successfully added to the cart
		 */
		private static function add_to_cart( int $product_id, int $quantity ): bool {
			try {
				// Taken from: https://wordpress.org/support/topic/wc-cart-is-null-in-custom-rest-api.
				WC()->frontend_includes();

				WC()->session = new WC_Session_Handler();
				WC()->session->init();
				WC()->customer = new WC_Customer( get_current_user_id(), true );
				WC()->cart     = new WC_Cart();
				WC()->cart->add_to_cart( $product_id );

				return true;
			} catch ( Exception $e ) { // @codingStandardsIgnoreLine
				return false; // Tough luck.
			}
		}

		/**
		 * Get a sanitised request parameter.
		 *
		 * @param array  $request HTTP request.
		 * @param string $name    name of the parameter.
		 * @return int|null
		 */
		public static function get_sanitised_param( $request, string $name ) {
			return isset( $request[ $name ] ) && ! empty( $request[ $name ] ) ?
				intval( sanitize_text_field( wp_unslash( $request[ $name ] ) ) ) : null;
		}

		/**
		 * Convert a wish list to a DTO.
		 *
		 * @param YITH_WCWL_Wishlist|null $wish_list Wish list.
		 * @return array
		 */
		private static function to_rest_wish_list( $wish_list ) {
			if ( ! $wish_list ) {
				return null;
			}

			return array(
				'id'         => $wish_list->get_id(),
				'user_id'    => $wish_list->get_user_id( 'edit' ),
				'date_added' => $wish_list->get_date_added_formatted( 'c' ),
				'default'    => $wish_list->get_is_default( 'view' ),
			);
		}

		/**
		 * Convert an array of wish list items to DTOs.
		 *
		 * @param YITH_WCWL_Wishlist_Item[]|null $items Wish list.
		 * @return array
		 */
		private static function to_rest_wish_list_items( $items ) {
			if ( ! $items ) {
				return array();
			}

			$dtos = array();
			foreach ( $items as $item ) {
				$product = wc_get_product( $item->get_product_id( 'edit' ) );

				$product_dto = null;
				if ( $product ) {
					$image_id  = $product->get_image_id();
					$image_url = wp_get_attachment_image_url( $image_id, 'full' );

					$product_dto = array(
						'id'          => $product->get_id(),
						'name'        => $product->get_name(),
						'description' => $product->get_description( 'edit' ),
						'sku'         => $product->get_sku( 'edit' ),
						'price'       => $item->get_product_price( 'edit' ),
						'currency'    => get_woocommerce_currency(),
						'permalink'   => $product->get_permalink(),
						'image_url'   => $image_url,
					);
				}

				$dtos[] = array(
					'id'          => $item->get_id(),
					'wishlist_id' => $item->get_wishlist_id( 'edit' ),
					'date_added'  => $item->get_date_added_formatted( 'c' ),
					'quantity'    => $item->get_quantity( 'edit' ),
					'product'     => $product_dto,
				);
			}

			return array(
				'items' => $dtos,
				'size'  => count( $dtos ),
			);
		}

		/**
		 * Checks if user is logged in. Used in rest api permission check.
		 *
		 * @param array $request HTTP request.
		 * @return true|WP_Error
		 * @noinspection PhpUnusedParameterInspection
		 */
		public static function check_auth( $request ) {
			if ( is_user_logged_in() ) {
				return true;
			}

			return new WP_Error(
				'unauthorized',
				'Authentication Required',
				array(
					'code'    => 401,
					'message' => 'Authentication Required',
					'data'    => array(),
				)
			);
		}

		/**
		 * Checks users read permission for given wish list.
		 * Used in rest api permission check.
		 *
		 * @param array $request HTTP request.
		 * @return bool|true|WP_Error|WP_REST_Response
		 */
		public static function check_read_cap( $request ) {
			$res = self::check_auth( $request );
			if ( is_wp_error( $res ) ) {
				return $res;
			}

			$id = isset( $request['id'] ) ? (int) $request['id'] : 0;

			if ( ! $id ) {
				return self::response_not_found();
			}

			$wish_list = self::load_wish_list( $id );
			if ( ! $wish_list->current_user_can( 'view' ) ) {
				return self::response_not_authorized( 'read' );
			}

			return true;
		}

		/**
		 * Checks users read permission for given wish list.
		 * Used in rest api permission check.
		 *
		 * @param array $request HTTP request.
		 * @return bool|WP_REST_Response
		 */
		public static function check_write_cap( $request ) {
			$id = isset( $request['id'] ) ? intval( $request['id'] ) : 0;
			if ( ! $id ) {
				return self::response_not_found();
			}

			$wish_list = self::load_wish_list( $id );
			if ( ! $wish_list->current_user_can( 'write' ) ) {
				return self::response_not_authorized( 'write' );
			}

			return true;
		}

		/**
		 * Load the wish list with the specified ID or the default list, if no ID present.
		 *
		 * @param int|null|false $id ID of a wish list.
		 * @return YITH_WCWL_Wishlist|WP_REST_Response
		 */
		private static function load_wish_list( $id ) {
			return YITH_WCWL_Wishlist_Factory::get_wishlist( $id );
		}

		/**
		 * Get a not-found response.
		 *
		 * @return WP_REST_Response
		 */
		public static function response_not_found() {
			return new WP_REST_Response(
				array(
					'status' => 404,
					'error'  => 'Wishlist not found!',
				),
				404
			);
		}

		/**
		 * Get a unauthorised response.
		 *
		 * @param string $operation operation not permitted.
		 * @return WP_REST_Response
		 */
		protected static function response_not_authorized( $operation ) {
			return new WP_REST_Response(
				array(
					'status' => 403,
					'error'  => "You do not have permission to $operation.",
				),
				403
			);
		}
	}
}
