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
		$filter_posts = true;
		if ( is_admin() ) {
			global $pagenow, $post_type;
			if ( isset( $_REQUEST['post_type'] ) ) {
				$post_type =  $_REQUEST['post_type'];
				$post_type_object = get_post_type_object( $post_type );
				if ( ! $post_type_object->public ) {
					$filter_posts = false;
				}
			}
			
			if ( isset( $_REQUEST['post_status'] ) && 'trash' == $_REQUEST['post_status'] )
				$filter_posts = false;
				
			if ( isset( $_REQUEST['language'] ) && 'any' == $_REQUEST['language'] )
				$filter_posts = false;
		}
		if ( $filter_posts ) {
			if ( is_admin() ) {
				add_filter( 'posts_where' , array( &$this , 'get_admin_posts_where' ) , 10 , 2 );
				add_filter( 'posts_join' , array( &$this , 'get_admin_posts_join' ) , 10 , 2 );
				add_filter( 'posts_groupby' , array( &$this , 'get_admin_posts_groupby' ) , 10 , 2 );
			} else {
				add_filter( 'posts_where' , array( &$this , 'get_posts_where' ) , 10 , 2 );
			}
			
		}
		
		add_filter( 'getarchives_where' , array( &$this , 'get_archiveposts_where' ) , 10 , 2 );

		add_filter( 'get_next_post_where' , array( &$this , 'get_adjacent_post_where' ) , 10 , 3 );
		add_filter( 'get_previous_post_where' , array( &$this , 'get_adjacent_post_where' ) , 10 , 3 );
		
		//misc
		add_filter( 'post_class' , array( &$this , 'post_class' ) , 10 , 3 );

		// caps
	}
	
	
	// --------------------------------------------------
	// Post class
	// --------------------------------------------------
	function post_class( $classes , $class , $post_ID ) {
		$post = get_post( $post_ID );
		$classes[] = $post->post_language;
		return array_unique($classes);
	}
	
	
	// --------------------------------------------------
	// viewing restrictions
	// --------------------------------------------------
	
	function get_archiveposts_where( $where , $args = null ) {
		$where = self::_get_where( $where , '' );
		return $where;
	}
	function get_posts_where( $where , &$wp_query ) {
		global $wpdb;
		$where = self::_get_where( $where , $wpdb->posts );
		return $where;
	}
	
	
	function get_admin_posts_where( $where , &$wp_query ) {
		global $wpdb;
		$where .= $wpdb->prepare(" AND ({$wpdb->posts}.post_language = %s OR babelposts.post_language != %s OR babelposts.post_language IS NULL )" , 
			postbabel_current_language() , postbabel_current_language() 
			);
		return $where;
	}
	function get_admin_posts_join( $join , &$wp_query ) {
		global $wpdb;
		$join .= " LEFT JOIN {$wpdb->posts} AS babelposts ON 
			{$wpdb->posts}.post_translation_group = babelposts.post_translation_group
			AND {$wpdb->posts}.post_language != babelposts.post_language 
			AND babelposts.post_status != 'auto-draft'";
		return $join;
	}
	function get_admin_posts_groupby( $groupby , &$wp_query ){
		return $groupby . " babelposts.post_translation_group ";
	}
	
	
	function get_adjacent_post_where( $where , $in_same_cat, $excluded_categories ) {
		return self::_get_where($where);
	}

	private function _get_admin_where( $where , $table_name = 'p' ) {
		"p.post_language = 'de-DE' OR p2.post_language != 'de-DE' OR p2.post_language IS NULL";		
	}
	private function _get_where( $where , $table_name = 'p' ) {
		global $wpdb;
		// disable filtering: on queries for single posts/pages
		if ( ( is_singular() && preg_match( "/{$wpdb->posts}.(post_name|ID)\s?=/" , $where ) ) ) {
			return $where;
		}
		if ( $table_name && substr($table_name,-1) !== '.' )
			$table_name .= '.';
		
		$where .= $wpdb->prepare(" AND {$table_name}post_language = %s " , postbabel_current_language() );
		return $where . $add_where;
	}

}
endif;
