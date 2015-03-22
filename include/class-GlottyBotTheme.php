<?php

if ( ! class_exists( 'GlottyBotTheme' ) ):
class GlottyBotTheme {

	private static $_instance = null;

	/**
	 * Getting a singleton.
	 *
	 * @return object single instance of GlottyBotSettings
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
		add_action('wp_head',array(&$this,'wp_head'));
		add_filter( 'get_search_form' , array( &$this , 'filter_search_form' ) , 10 , 1 );
	}
	
	/**
	 *	
	 *	@action 'init'
	 */
	function wp_head() {
		$current_locale = GlottyBot()->get_locale();
		foreach ( GlottyBot()->get_locales() as $locale ) {
			if ( $current_locale == $locale )
				continue;
			$href = GlottyBotPermastruct::instance()->get_current_item_translation_url( $locale );
			if ( $href )
				echo '<link rel="alternate" hreflang="'.$locale.'" href="' . esc_url( $href ) . '" />' . "\n";
		}
		
	}
	
	/**
	 *	Replace searchform action attribute with translated action
	 *
	 *	@filter 'get_search_form'
	 */
	function filter_search_form( $form ) {
		$attr = 'action="%s"';
		$base = home_url( '/' );
		$search = sprintf($attr , esc_url( $base ) );
		$replace = sprintf($attr , GlottyBotPermastruct::instance()->prepend_language_slug( $base ) );
		return str_replace( $search , $replace , $form );
	}

}
endif;