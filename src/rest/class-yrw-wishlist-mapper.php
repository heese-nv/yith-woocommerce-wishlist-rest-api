<?php
/**
 * File YRW_Wishlist_Mapper.php
 *
 * @package
 */

/**
 * Class YRW_Wishlist_Mapper
 */
class YRW_Wishlist_Mapper implements YRW_Mapper {
	/**
	 * Generate DTOs for wishlist(s).
	 *
	 * @param YITH_WCWL_Wishlist[]|YITH_WCWL_Wishlist $obj wishlist(s) to be mapped.
	 * @return array
	 */
	public function to_rest( $obj ) {
		if ( is_null( $obj ) ) {
			return null;
		}

		if ( is_array( $obj ) ) {
			$mapped = array();
			foreach ( $obj as $wishlist ) {
				$mapped[] = $this->to_rest_single( $wishlist );
			}
		} else {
			$mapped = $this->to_rest_single( $obj );
		}

		return $mapped;
	}

	/**
	 * Map a single wishlist.
	 *
	 * @param YITH_WCWL_Wishlist $wishlist wishlist to be mapped.
	 * @return array
	 */
	private function to_rest_single( YITH_WCWL_Wishlist $wishlist ) {
		return array(
			'id'         => $wishlist->get_id(),
			'user_id'    => $wishlist->get_user_id( 'edit' ),
			'date_added' => $wishlist->get_date_added_formatted( 'c' ),
			'default'    => $wishlist->get_is_default( 'view' ),
		);
	}
}
