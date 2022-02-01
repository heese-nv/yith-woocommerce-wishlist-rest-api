<?php
/**
 * File YRW_Wishlist_Item_Mapper.php
 *
 * @package YITH\Wishlist
 */

/**
 * Class YRW_Wishlist_Item_Mapper
 */
class YRW_Wishlist_Item_Mapper implements YRW_Mapper {

	/**
	 * Generate DTOs for wishlist items.
	 *
	 * @param YITH_WCWL_Wishlist_Item[]|null $obj items to be mapped.
	 * @return array
	 */
	public function to_rest( $obj ) {
		if ( ! $obj || ! is_array( $obj ) ) {
			return array();
		}

		$dtos = array();
		foreach ( $obj as $item ) {
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
}
