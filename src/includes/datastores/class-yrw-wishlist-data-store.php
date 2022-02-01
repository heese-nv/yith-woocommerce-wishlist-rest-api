<?php
/**
 * Extended YITH wishlist data store.
 *
 * @package YITH\Wishlist
 */

if ( ! class_exists( 'YRW_Wishlist_Data_Store' ) ) {

	/**
	 * Class YRW_Wishlist_Data_Store
	 */
	class YRW_Wishlist_Data_Store extends YITH_WCWL_Wishlist_Data_Store {

		/**
		 * Query database to search. This implementation hides the original one to allow for special users to
		 * load any wishlist.
		 *
		 * @param array $args Array of parameters used for the query:<br/>
		 *                    [<br/>
		 *                    'id'                   // Wishlist id<br/>
		 *                    'user_id'              // User id<br/>
		 *                    'session_id'           // Session id<br/>
		 *                    'wishlist_slug'        // Wishlist slug, exact match<br/>
		 *                    'wishlist_name'        // Wishlist name, like<br/>
		 *                    'wishlist_token'       // Wishlist token, exact match<br/>
		 *                    'wishlist_visibility'  // all, visible, public, shared, private<br/>
		 *                    'user_search'          // String to search within user fields<br/>
		 *                    's'                    // String to search within wishlist fields<br/>
		 *                    'is_default'           // Whether searched wishlist is default<br/>
		 *                    'orderby'              // Any of the table columns<br/>
		 *                    'order'                // ASC, DESC<br/>
		 *                    'limit'                // Limit of items to retrieve<br/>
		 *                    'offset'               // Offset of items to retrieve<br/>
		 *                    'show_empty'           // Whether to show empty wishlists<br/>
		 *                    ].
		 *
		 * @return YITH_WCWL_Wishlist[] Array of matched wishlists.
		 */
		public function query( $args = array() ) {
			global $wpdb;

			// Whether the user can export all wishlists.
			$user_can_export = current_user_can( 'api_wishlist_export' );

			$default = array(
				'id'                  => false,
				'user_id'             => is_user_logged_in() && ! $user_can_export ? get_current_user_id() : false,
				'session_id'          => ! is_user_logged_in() ? YITH_WCWL_Session()->maybe_get_session_id() : false,
				'wishlist_slug'       => false,
				'wishlist_name'       => false,
				'wishlist_token'      => false,
				// Valid values for visibility: all, visible, public, shared, private.
				'wishlist_visibility' => apply_filters( 'yith_wcwl_wishlist_visibility_string_value', 'all' ),
				'user_search'         => false,
				's'                   => false,
				'is_default'          => false,
				'orderby'             => '',
				'order'               => 'DESC',
				'limit'               => false,
				'offset'              => 0,
				'show_empty'          => true,
			);

			// User has to be authenticated to retrieve data.
			if ( ! is_user_logged_in() && ! YITH_WCWL_Session()->has_session() ) {
				return array();
			}

			// The current user has either permission to access all wishlists or a user ID is present.
			if ( ! $user_can_export && ! isset( $args['user_id'] ) && ! isset( $args['session_id'] ) ) {
				return array();
			}

			$args = wp_parse_args( $args, $default );
			extract( $args ); // phpcs:ignore WordPress.PHP.DontExtract

			$sql = 'SELECT SQL_CALC_FOUND_ROWS l.ID'; // @codingStandardsIgnoreLine
			$sql .= " FROM `{$wpdb->yith_wcwl_wishlists}` AS l";

			if ( ! empty( $user_search ) || ! empty( $s ) || ( ! empty( $orderby ) && 'user_login' === $orderby ) ) {
				$sql .= " LEFT JOIN `{$wpdb->users}` AS u ON l.`user_id` = u.ID";
			}

			if ( ! empty( $user_search ) || ! empty( $s ) ) {
				$sql .= " LEFT JOIN `{$wpdb->usermeta}` AS umn ON umn.`user_id` = u.`ID`";
				$sql .= " LEFT JOIN `{$wpdb->usermeta}` AS ums ON ums.`user_id` = u.`ID`";
			}

			$sql      .= ' WHERE 1'; // @codingStandardsIgnoreLine
			$sql_args = array();

			if ( ! empty( $user_id ) ) {
				$sql .= ' AND l.`user_id` = %d';

				$sql_args[] = $user_id;
			}

			if ( ! empty( $session_id ) ) {
				$sql .= ' AND l.`session_id` = %s AND l.`expiration` > NOW()';

				$sql_args[] = $session_id;
			}

			if ( ! empty( $user_search ) && empty( $s ) ) {
				$sql .= ' AND (
							umn.`meta_key` = %s AND
							ums.`meta_key` = %s AND
							(
								u.`user_email` LIKE %s OR
								umn.`meta_value` LIKE %s OR
								ums.`meta_value` LIKE %s
							)
						)';

				$search_value = '%' . esc_sql( $user_search ) . '%';

				$sql_args[] = 'first_name';
				$sql_args[] = 'last_name';
				$sql_args[] = $search_value;
				$sql_args[] = $search_value;
				$sql_args[] = $search_value;
			}

			if ( ! empty( $s ) ) {
				$sql .= ' AND ( 
							( 
								umn.`meta_key` = %s AND 
								ums.`meta_key` = %s AND 
								( 
									u.`user_email` LIKE %s OR
									u.`user_login` LIKE %s OR
									umn.`meta_value` LIKE %s OR
									ums.`meta_value` LIKE %s
								) 
							) OR 
							l.wishlist_name LIKE %s OR 
							l.wishlist_slug LIKE %s OR 
							l.wishlist_token LIKE %s 
						)';

				$search_value = '%' . esc_sql( $s ) . '%';

				$sql_args[] = 'first_name';
				$sql_args[] = 'last_name';
				$sql_args[] = $search_value;
				$sql_args[] = $search_value;
				$sql_args[] = $search_value;
				$sql_args[] = $search_value;
				$sql_args[] = $search_value;
				$sql_args[] = $search_value;
				$sql_args[] = $search_value;
			}

			if ( ! empty( $is_default ) ) {
				$sql        .= ' AND l.`is_default` = %d'; // @codingStandardsIgnoreLine
				$sql_args[] = $is_default;
			}

			if ( ! empty( $id ) ) {
				$sql        .= ' AND l.`ID` = %d'; // @codingStandardsIgnoreLine
				$sql_args[] = $id;
			}

			if ( isset( $wishlist_slug ) && false !== $wishlist_slug ) {
				$sql        .= ' AND l.`wishlist_slug` = %s'; // @codingStandardsIgnoreLine
				$sql_args[] = sanitize_title_with_dashes( $wishlist_slug );
			}

			if ( ! empty( $wishlist_token ) ) {
				$sql        .= ' AND l.`wishlist_token` = %s'; // @codingStandardsIgnoreLine
				$sql_args[] = $wishlist_token;
			}

			if ( ! empty( $wishlist_name ) ) {
				$sql        .= ' AND l.`wishlist_name` LIKE %s'; // @codingStandardsIgnoreLine
				$sql_args[] = '%' . esc_sql( $wishlist_name ) . '%';
			}

			if ( isset( $wishlist_visibility ) && 'all' !== $wishlist_visibility ) {
				if ( ! is_int( $wishlist_visibility ) ) {
					$wishlist_visibility = yith_wcwl_get_privacy_value( $wishlist_visibility );
				}

				$sql        .= ' AND l.`wishlist_privacy` = %d'; // @codingStandardsIgnoreLine
				$sql_args[] = $wishlist_visibility;
			}

			if ( empty( $show_empty ) ) {
				$sql .= " AND l.`ID` IN ( SELECT wishlist_id FROM {$wpdb->yith_wcwl_items} )";
			}

			$sql .= ' GROUP BY l.ID';
			$sql .= ' ORDER BY';

			if ( ! empty( $orderby ) && isset( $order ) ) {
				$sql .= ' ' . esc_sql( $orderby ) . ' ' . esc_sql( $order ) . ', ';
			}

			$sql .= ' is_default DESC';

			if ( ! empty( $limit ) && isset( $offset ) ) {
				$sql        .= ' LIMIT %d, %d'; // @codingStandardsIgnoreLine
				$sql_args[] = $offset;
				$sql_args[] = $limit;
			}

			if ( ! empty( $sql_args ) ) {
				$sql = $wpdb->prepare( $sql, $sql_args ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			}

			$lists = $wpdb->get_col( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery

			if ( ! empty( $lists ) ) {
				$lists = array_map( array( 'YITH_WCWL_Wishlist_Factory', 'get_wishlist' ), $lists );
			} else {
				$lists = array();
			}

			return apply_filters( 'yith_wcwl_get_wishlists', $lists, $args );
		}
	}
}
