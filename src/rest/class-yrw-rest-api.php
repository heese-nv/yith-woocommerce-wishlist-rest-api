<?php
/**
 * REST API.
 *
 * @package YITH\Wishlist
 */

if ( ! class_exists( '\YITH\Wishlist\Rest' ) ) {

	require_once 'class-yrw-mapper.php';
	require_once 'class-yrw-wishlist-mapper.php';
	require_once 'class-yrw-wishlist-item-mapper.php';

	/**
	 * Class Rest
	 *
	 * @package YITH\Wishlist
	 */
	final class Yrw_Rest_API {
		const REST_NAMESPACE = 'yith/wishlist';

		const REST_VERSION = 'v2';

		/**
		 * API definition.
		 *
		 * @var array[]
		 */
		protected static $routes = array(
			'export'            => array( // get list of wishlist for given user.
				'route'               => '/wishlists/export',
				'methods'             => 'GET',
				'callback'            => array( __CLASS__, 'export' ),
				'permission_callback' => array( __CLASS__, 'check_api_read_cap' ),
			),
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

			add_filter( 'woocommerce_is_rest_api_request', array( __CLASS__, 'simulate_as_not_rest' ) );
		}

		/**
		 * Register routes of REST API.
		 */
		protected static function register_routes() {
			do_action( 'yith_rest_wishlist_before_register_route' );

			$routes = apply_filters( 'yith_rest_wishlist_routes', self::$routes );

			$prefix = self::REST_NAMESPACE . '/' . self::REST_VERSION;
			foreach ( $routes as $args ) {
				$route = $args['route'];
				unset( $args['route'] );
				register_rest_route( $prefix, $route, $args );
			}

			do_action( 'yith_rest_wishlist_after_register_route' );
		}

		/**
		 * Returns true if the request is a non-legacy REST API request.
		 *
		 * We have to tell WC that this should not be handled as a REST request.
		 * Otherwise we can't use the product loop template contents properly.
		 *
		 * Taken from: https://wordpress.org/support/topic/wc-cart-is-null-in-custom-rest-api.
		 *
		 * @param bool $is_rest_api_request whether this is a REST request.
		 * @return bool
		 */
		public static function simulate_as_not_rest( $is_rest_api_request ) {
			$server = wp_unslash( $_SERVER );
			if ( empty( $server['REQUEST_URI'] ) ) {
				return $is_rest_api_request;
			}

			// Bail early if this is not our request.
			if ( false === strpos( $server['REQUEST_URI'], self::REST_NAMESPACE ) ) {
				return $is_rest_api_request;
			}

			return false;
		}

		/**
		 * Export all wishlists.
		 */
		public static function export() {
			$wishlists = YITH_WCWL_Wishlist_Factory::get_wishlists();
			if ( ! $wishlists ) {
				$wishlists = array();
			}

			return self::to_rest_response( $wishlists, new YRW_Wishlist_Mapper() );
		}

		/**
		 * Get array of wishlists for current user.
		 */
		public static function get_list() {
			$user_id   = get_current_user_id();
			$wishlists = YITH_WCWL_Wishlist_Factory::get_wishlists( array( 'user_id' => $user_id ) );
			if ( ! $wishlists ) {
				$wishlists = array();
			}

			return self::to_rest_response( $wishlists, new YRW_Wishlist_Mapper() );
		}

		/**
		 * Creates a wishlist for current user.
		 */
		public static function post() {
			// TODO Implement.
			self::finalise();

			return new WP_REST_Response();
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

			$wishlist = self::load_wish_list( $id );
			if ( ! $wishlist ) {
				return self::response_not_found( $id );
			}

			return self::to_rest_response( $wishlist, new YRW_Wishlist_Mapper() );
		}

		/**
		 * Retrieve a single wishlist item by ID.
		 *
		 * @param array $request HTTP request.
		 * @return WP_REST_Response
		 */
		public static function get( $request ) {
			$id       = self::get_sanitised_param( $request, 'id' );
			$wishlist = self::load_wish_list( $id );
			if ( ! $wishlist ) {
				return self::response_not_found( $id );
			}

			return self::to_rest_response( $wishlist, new YRW_Wishlist_Mapper() );
		}

		/**
		 * Deletes a wishlist
		 *
		 * @param array $request HTTP request.
		 * @return WP_REST_Response
		 */
		public static function delete( $request ) {
			$id = isset( $request['id'] ) ? intval( $request['id'] ) : 0;
			self::finalise();

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

			$wishlist = self::load_wish_list( $id );
			if ( ! $wishlist ) {
				return self::response_not_found( $id );
			}

			return self::to_rest_response( $wishlist->get_items(), new YRW_Wishlist_Item_Mapper() );
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

			$wishlist = self::load_wish_list( $wish_list_id );
			if ( $product_id ) {
				$wishlist->add_product( $product_id );
				$wishlist->save();
			}

			return self::to_rest_response( $wishlist->get_items(), new YRW_Wishlist_Item_Mapper() );
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

			$wishlist = self::load_wish_list( $wish_list_id );
			if ( $product_id ) {
				$can_remove = true;
				if ( $add_to_cart ) {
					$item       = $wishlist->get_product( $product_id );
					$quantity   = $item ? $item->get_quantity( 'edit' ) : 1;
					$can_remove = self::add_to_cart( $product_id, $quantity );
				}

				if ( $can_remove ) {
					$wishlist->remove_product( $product_id );
					$wishlist->save();
				}
			}

			return self::to_rest_response( $wishlist->get_items(), new YRW_Wishlist_Item_Mapper() );
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
		 * Generate the REST response. This method calls finalise().
		 *
		 * @param YITH_WCWL_Wishlist|YITH_WCWL_Wishlist[]|YITH_WCWL_Wishlist_Item $obj    response object.
		 * @param YRW_Mapper                                                      $mapper mapper to generate DTO.
		 * @return WP_REST_Response
		 */
		private static function to_rest_response( $obj, YRW_Mapper $mapper ) {
			self::finalise();

			return new WP_REST_Response( $mapper->to_rest( $obj ) );
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
		 * Load the wish list with the specified ID or the default list, if no ID present.
		 *
		 * @param int|null|false $id ID of a wish list.
		 * @return YITH_WCWL_Wishlist|WP_REST_Response
		 */
		private static function load_wish_list( $id ) {
			return YITH_WCWL_Wishlist_Factory::get_wishlist( $id );
		}

		/**
		 * Clean up. Otherwise the subsequent call might use the previously logged in user (e.g., in Insomnia).
		 * - Destroy session
		 * - Logout
		 */
		private static function finalise() {
			wp_destroy_current_session();
			wp_logout();
		}

		/**
		 * Checks if user is logged in. Used in REST API permission check.
		 * Calls finalise() on error.
		 *
		 * @param array $request HTTP request.
		 * @return true|WP_Error
		 * @noinspection PhpUnusedParameterInspection
		 */
		public static function check_auth( $request ) {
			if ( is_user_logged_in() ) {
				return true;
			}

			self::finalise();

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
		 * Checks users read permission for all wish lists.
		 * Used in rest api permission check.
		 *
		 * @param array $request HTTP request.
		 * @return bool|true|WP_Error
		 */
		public static function check_api_read_cap( $request ) {
			$res = self::check_auth( $request );
			if ( is_wp_error( $res ) ) {
				return $res;
			}

			if ( ! current_user_can( 'api_wishlist_export' ) ) {
				return self::permission_not_authorized( 'read' );
			}

			return true;
		}

		/**
		 * Checks users read permission for given wish list.
		 * Used in rest api permission check.
		 *
		 * @param array $request HTTP request.
		 * @return bool|true|WP_Error
		 */
		public static function check_read_cap( $request ) {
			$res = self::check_auth( $request );
			if ( is_wp_error( $res ) ) {
				return $res;
			}

			$id = isset( $request['id'] ) ? (int) $request['id'] : 0;

			if ( ! $id ) {
				return self::permission_not_found( $id );
			}

			$wish_list = self::load_wish_list( $id );
			if ( ! $wish_list ) {
				// A caught exception is logged. This cannot be easily avoided.
				return self::permission_not_found( $id );
			}

			if ( ! $wish_list->current_user_can( 'view' ) ) {
				return self::permission_not_authorized( 'read' );
			}

			return true;
		}

		/**
		 * Checks users read permission for given wish list.
		 * Used in rest api permission check.
		 *
		 * @param array $request HTTP request.
		 * @return bool|WP_Error
		 */
		public static function check_write_cap( $request ) {
			$id = isset( $request['id'] ) ? intval( $request['id'] ) : 0;
			if ( ! $id ) {
				return self::permission_not_found( $id );
			}

			$wish_list = self::load_wish_list( $id );
			if ( ! $wish_list->current_user_can( 'write' ) ) {
				return self::permission_not_authorized( 'write' );
			}

			return true;
		}

		/**
		 * Get a unauthorised response used in the context of 'permission_callback'.
		 *
		 * @param string $operation operation not permitted.
		 * @return WP_Error
		 */
		public static function permission_not_authorized( $operation ) {
			self::finalise();

			return new WP_Error(
				403,
				__( 'Access denied', 'yith-rest-wishlist' ),
				array(
					'status' => rest_authorization_required_code(),
					// translators: 1) operation name.
					'error'  => sprintf( __( 'You do not have permission to %s', 'yith-rest-wishlist' ), $operation ),
				),
			);
		}

		/**
		 * Get a unauthorised response used in the context of 'permission_callback'.
		 *
		 * @param string $id wishlist ID.
		 * @return WP_Error
		 */
		public static function permission_not_found( $id ) {
			self::finalise();

			return new WP_Error(
				403,
				__( 'Not found', 'yith-rest-wishlist' ),
				array(
					'status' => rest_authorization_required_code(),
					// translators: 1) wishlist ID.
					'error'  => sprintf( __( 'Wishlist %s was not found', 'yith-rest-wishlist' ), $id ),
				),
			);
		}

		/**
		 * Get a not-found response.
		 *
		 * @param string $id wishlist ID.
		 * @return WP_REST_Response
		 */
		public static function response_not_found( $id ) {
			self::finalise();

			return new WP_REST_Response(
				array(
					'status' => 404,
					// translators: 1) wishlist ID.
					'error'  => sprintf( __( 'Wishlist %s was not found', 'yith-rest-wishlist' ), $id ),
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
			self::finalise();

			return new WP_REST_Response(
				array(
					'status' => rest_authorization_required_code(),
					'error'  => "You do not have permission to $operation.",
				),
				rest_authorization_required_code()
			);
		}
	}
}
