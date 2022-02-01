<?php
/**
 * File YRW_Capability.php
 *
 * @package YITH_REST_WISHLIST\Classes
 */

/**
 * Configure capabilities.
 */
class YRW_Capability {
	private const GROUP_NAME = 'plugin-yith-wishlist';

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'members_register_cap_groups', array( $this, 'members_register_cap_group' ), 5 );
		add_action( 'init', array( $this, 'members_register_caps' ), 5 );
	}

	/**
	 * Register a capability group for Aciem.
	 */
	public function members_register_cap_group() {
		$definition = $this->get_definition();
		members_register_cap_group(
			self::GROUP_NAME,
			array(
				'label'    => esc_html__( 'Wishlist', 'yith-rest-wishlist' ),
				'icon'     => 'dashicons-list-view',
				'priority' => 12,
				'caps'     => array_keys( $definition['capabilities'] ),
			)
		);

	}

	/**
	 * Register the capabilities.
	 */
	public function members_register_caps() {
		$definition = $this->get_definition();

		foreach ( $definition['capabilities'] as $key => $args ) {
			members_register_cap( $key, $args );
		}
	}

	/**
	 * Get the capabilities defined in this plugin.
	 *
	 * @return array
	 */
	private function get_definition() {
		// @codingStandardsIgnoreStart
		return array(
			'capabilities' => array(
				'api_wishlist_export' =>
					array(
						'label' => 'Export the wishlists of all users',
						'group' => self::GROUP_NAME,
					),
			),
		);
		// @codingStandardsIgnoreEnd
	}
}

return new YRW_Capability();
