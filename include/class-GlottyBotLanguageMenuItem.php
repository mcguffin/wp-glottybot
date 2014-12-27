<?php


if ( ! class_exists( 'GlottyBotAdminMenu' ) ) :

class GlottyBotAdminMenu {
	private static $_instance = null;
	
	/**
	 * Getting a singleton.
	 *
	 * @return object single instance of GlottyBotAdmin
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) )
			self::$_instance = new self();
		return self::$_instance;
	}

	/**
	 *	Prevent cloning
	 */
	private function __clone() {}

	/**
	 * Private constructor
	 */
	private function __construct() {
	}
	
}

endif;