<?php
/**
 * File YRW_Mapper.php
 *
 * @package YITH\Wishlist
 */

interface YRW_Mapper {

	/**
	 * Map an object to a DTO.
	 *
	 * @param array|object|null $obj object to be mapped.
	 * @return array
	 */
	public function to_rest( $obj );
}
