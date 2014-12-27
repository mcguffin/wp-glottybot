<?php
/**
* @package WP_AccessAreas
* @version 1.0.0
*/ 

// ----------------------------------------
//	Frontend Post Filters
// ----------------------------------------

if ( ! class_exists('GlottyBotPosts') ):
class GlottyBotPosts {

	private static $_instance = null;
	
	// should be 'permalink', but saving doesn't work there.
	private $language = ''; // general | writing | reading | discussion | media | permalink

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
				add_filter( 'posts_request_ids' , array( &$this , 'posts_request_ids' ) , 10 , 2 );
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
	
	
	/**
	 *	post class filter.
	 *
	 *	@see wp filter `post_class`
	 */
	function post_class( $classes , $class , $post_ID ) {
		$post = get_post( $post_ID );
		$classes[] = $post->post_language;
		return array_unique($classes);
	}
	
	
	/**
	 *	Where clause
	 *
	 *	@see wp filter `getarchives_where`
	 */
	function get_archiveposts_where( $where , $args = null ) {
		$where = self::_get_where( $where , '' );
		return $where;
	}
	/**
	 *	Where clause
	 *
	 *	@see wp filter `posts_where`
	 */
	function get_posts_where( $where , &$wp_query ) {
		global $wpdb;
		$where = self::_get_where( $where , $wpdb->posts );
		return $where;
	}
	/**
	 *	Where clause (admin)
	 *
	 *	@see wp filter `posts_request_ids`
	 */
	function posts_request_ids( $request , $wp_query ) {
		global $wpdb;
		$sel_ids = "{$wpdb->posts}.ID";
		$sel_tgs = "{$wpdb->posts}.post_translation_group";
		@list( $select , $tail ) = explode(' FROM ', $request , 2 );
		if ( $tail && false !== strpos( $select , $sel_ids ) && false === strpos( $select , $sel_tgs ) ) {
			$select = str_replace( $sel_ids , "$sel_ids , $sel_tgs" , $select );
			$request = "$select FROM $tail";
		}
		return $request;
	}
	
	/**
	 *	Where clause (admin)
	 *
	 *	@see wp filter `posts_where`
	 */
	function get_admin_posts_where( $where , &$wp_query ) {
		global $wpdb;
		$where .= $wpdb->prepare(" AND ({$wpdb->posts}.post_language = %s OR glottybotposts.post_language != %s OR glottybotposts.post_language IS NULL )" , 
			glottybot_current_language() , glottybot_current_language() 
			);
		return $where;
	}
	/**
	 *	Join clause (admin)
	 *
	 *	@see wp filter `posts_join`
	 */
	function get_admin_posts_join( $join , &$wp_query ) {
		global $wpdb;
		$join .= " LEFT JOIN {$wpdb->posts} AS glottybotposts ON 
			{$wpdb->posts}.post_translation_group = glottybotposts.post_translation_group
			AND {$wpdb->posts}.post_language != glottybotposts.post_language 
			AND glottybotposts.post_status != 'auto-draft'";
		return $join;
	}
	/**
	 *	Group by clause (admin)
	 *
	 *	@see wp filter `posts_groupby`
	 */
	function get_admin_posts_groupby( $groupby , &$wp_query ) {
		global $wpdb;
		return $groupby . " glottybotposts.post_translation_group ";
		return $groupby . " {$wpdb->posts}.post_translation_group ";
	}
	
	
	/**
	 *	Next/Previous Post link
	 *
	 *	@see wp filters `get_{$adjacent}_post_where`, 
	 */
	function get_adjacent_post_where( $where , $in_same_cat, $excluded_categories ) {
		return self::_get_where($where);
	}

// 	private function _get_admin_where( $where , $table_name = 'p' ) {
// 		"p.post_language = 'de-DE' OR p2.post_language != 'de-DE' OR p2.post_language IS NULL";		
// 	}
	
	/**
	 *	Generalized where clause
	 *
	 *	@param $where string SQL 
	 *	@param $table_name string table alias for the posts table
	 */
	private function _get_where( $where , $table_name = 'p' ) {
		global $wpdb;
		// disable filtering: on queries for single posts/pages
		if ( ( is_singular() && preg_match( "/{$wpdb->posts}.(post_name|ID)\s?=/" , $where ) ) ) {
			return $where;
		}
		if ( $table_name && substr($table_name,-1) !== '.' )
			$table_name .= '.';
		
		$where .= $wpdb->prepare(" AND {$table_name}post_language = %s " , glottybot_current_language() );
		return $where . $add_where;
	}

}
endif;
