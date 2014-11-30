<?php
/**
* @package WP_AccessAreas
* @version 1.0.0
*/ 

// ----------------------------------------
//	Frontend Post Filters
// ----------------------------------------

if ( ! class_exists('PostBabelPosts') ):
class PostBabelPosts {

	private static $_instance = null;
	
	// should be 'permalink', but saving doesn't work there.
	private $language = ''; // general | writing | reading | discussion | media | permalink

	/**
	 * Getting a singleton.
	 *
	 * @return object single instance of PostBabelSettings
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) )
			self::$_instance = new self();
		return self::$_instance;
	}
	
	private function __construct() {

		// viewing restrictions on posts lists
		add_filter( 'posts_where' , array( __CLASS__ , 'get_posts_where' ) , 10 , 2 );
		add_filter( 'getarchives_where' , array( __CLASS__ , 'get_archiveposts_where' ) , 10 , 2 );

		add_filter( 'get_next_post_where' , array( __CLASS__ , 'get_adjacent_post_where' ) , 10 , 3 );
		add_filter( 'get_previous_post_where' , array( __CLASS__ , 'get_adjacent_post_where' ) , 10 , 3 );
		
		//misc
		add_filter( 'post_class' , array( __CLASS__ , 'post_class' ) , 10 , 3 );

		// caps
	}
	
	
	// --------------------------------------------------
	// Post class
	// --------------------------------------------------
	static function post_class( $classes , $class , $post_ID ) {
		$post = get_post( $post_ID );
		$classes[] = $post->post_language;
		return array_unique($classes);
	}
	
	
	// --------------------------------------------------
	// viewing restrictions
	// --------------------------------------------------
	
	static function get_archiveposts_where( $where , $args = null ) {
		$where = self::_get_where( $where , '' );
		return $where;
	}
	static function get_posts_where( $where , &$wp_query ) {
		global $wpdb;
		$where = self::_get_where( $where , $wpdb->posts );
		return $where;
	}
	
	static function get_adjacent_post_where( $where , $in_same_cat, $excluded_categories ) {
		return self::_get_where($where);
	}


	private static function _get_where( $where , $table_name = 'p' ) {
		global $wpdb;
		// disable filtering: on queries for single posts/pages
		if ( ( is_singular() && preg_match( "/{$wpdb->posts}.(post_name|ID)\s?=/" , $where ) ) ) {
			return $where;
		}
		if ( $table_name && substr($table_name,-1) !== '.' )
			$table_name .= '.';
		
		$where .= $wpdb->prepare(" AND {$table_name}post_language = %s ",postbabel_current_language());
		return $where . $add_where;
	}

}
endif;
