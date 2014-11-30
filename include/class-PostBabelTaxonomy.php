<?php


if ( ! class_exists( 'PostBabelTaxonomy' ) ):
class PostBabelTaxonomy {
	private static $_instance = null;
	
	/**
	 * Getting a singleton.
	 *
	 * @return object single instance of PostBabelAdmin
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) )
			self::$_instance = new self();
		return self::$_instance;
	}

	/**
	 * Private constructor
	 */
	private function __construct() {
		add_action('plugins_loaded',array( &$this , 'plugins_loaded') );
	}
	
	function plugins_loaded( ) {
		foreach ( get_taxonomies( array( 'public' => true ) , 'names' ) as $taxonomy ) {
			add_action( "after-{$taxonomy}-table", array( &$this , 'show_taxo_translate_link' ) );
			$language = postbabel_current_language( '_' );
			$textdomain = "taxonomy-{$taxonomy}";
			$mofile = WP_LANG_DIR . "/$textdomain-{$language}.mo";
			if ( file_exists( $mofile ) ) {
				load_textdomain( $textdomain , $mofile );
			}
			add_filter( 'get_term' , array( &$this , 'filter_term' ) , 10 , 2 );
			add_filter( 'get_terms' , array( &$this , 'filter_terms' ) , 10 , 3 );
		}
	}
	function filter_term( $term , $taxonomy ) {
		if ( ! is_object( $term ) ) 
			return $term;
		$textdomain = "taxonomy-{$taxonomy}";
		$term->name = __( $term->name , $textdomain );
		$term->description = __( $term->description , $textdomain );
		return $term;
	}
	function filter_terms( $terms , $taxonomies , $args ) {
		foreach( $terms as $i => $term ) {
			$terms[$i] = $this->filter_term( $term , $term->taxonomy );
		}
		return $terms;
	}	
}

endif;