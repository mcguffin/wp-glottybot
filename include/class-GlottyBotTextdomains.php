<?php


if ( ! class_exists( 'GlottyBotTextdomains' ) ):
class GlottyBotTextdomains extends GlottyBotAdminPomo {
	private static $_instance = null;
	protected $textdomain_prefix;
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
		add_action( 'plugins_loaded' , array( &$this , 'plugins_loaded') );
	}
	
	/**
	 *	Filter nav menu items.
	 *
	 *	@see WP filter wp_get_nav_menu_items
	 */
	function filter_nav_menu($items, $menu, $args){
		//  $item->object == "page"
		// $item->post_title
		$textdomain = $this->get_textdomain( $menu->term_id , 'menu' );
		foreach ( $items as $i=>$item) {
			// rewrite custom menu item names
			if ( $item->post_title !== "" ) {
				$item->title = __( $item->title , $textdomain );
				$item->post_title = __( $item->post_title , $textdomain );
			}
			// rewrite menu item target
			if ( $item->type == "post_type" ) {
				$target = get_post( $item->object_id );
				if ( $target && $target->post_language != glottybot_current_language( ) ) {
					if ( $new_target = glottybot_get_translated_post( $target ) ) {
						$item->object_id = $new_target->ID;
						$item->title = $new_target->post_title;
						$item->url = get_permalink( $new_target->ID );
					}
				}
				// try if there is a translation for $object_id
			} else if ( $item->type == 'glottybot_language' ) {
				$lang = glottybot_normalize_language_code( $item->object );
				$item->url = glottybot_get_current_page_url( $lang );//get_permalink( $new_target->ID );
			}
			$items[$i] = $item;
		}
		return $items;
	}
	
	/**
	 *	Load taxonomy and manu items textdomains.
	 *	hooks into 'plugins_loaded'
	 */
	function plugins_loaded( ) {
		$language = glottybot_current_language( '_' );
		$has_taxonomy_translation = false;
		foreach ( get_taxonomies( array( 'public' => true ) , 'names' ) as $taxonomy ) {
			add_action( "after-{$taxonomy}-table", array( &$this , 'show_taxo_translate_link' ) );
			$textdomain = $this->get_textdomain( $taxonomy , 'taxonomy' );
			if ( $mofile = $this->get_mo_file( $taxonomy , $language  , 'taxonomy' ) ) {
				load_textdomain( $textdomain , $mofile );
				$has_taxonomy_translation = true;
			}
		}

		$has_menu_translation = false;
		foreach ( wp_get_nav_menus() as $menu ) {
			$textdomain = $this->get_textdomain( $menu->term_id , 'menu' );
			
			if ( $mofile = $this->get_mo_file( $menu->term_id , $language , 'menu' ) ) {
				load_textdomain( $textdomain , $mofile );
				$has_menu_translation = true;
			}
		}
		
		if ( $has_menu_translation ) {
			add_filter( 'wp_get_nav_menu_items', array( &$this , 'filter_nav_menu')  , 10 , 3 );
		}
		if ( $has_taxonomy_translation ) {
			add_filter( 'get_term' , array( &$this , 'filter_term' ) , 10 , 2 );
			add_filter( 'get_terms' , array( &$this , 'filter_terms' ) , 10 , 3 );
		}
	}
	/**
	 *	Set term name and description according to current locale
	 *
	 *	@see WP filter get_term
	 */
	function filter_term( $term , $taxonomy ) {
		if ( ! is_object( $term ) ) 
			return $term;
		$textdomain = $this->get_textdomain( $taxonomy , 'taxonomy' );
		$term->name = __( $term->name , $textdomain );
		$term->description = __( $term->description , $textdomain );
		return $term;
	}
	/**
	 *	Set terms names and descriptions according to current locale
	 *
	 *	@see WP filter get_terms
	 */
	function filter_terms( $terms , $taxonomies , $args ) {
		foreach( $terms as $i => $term ) {
			$terms[$i] = $this->filter_term( $term , $term->taxonomy );
		}
		return $terms;
	}	
}

endif;