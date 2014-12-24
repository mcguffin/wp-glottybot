<?php

/*
Links
√ apply_filters( 'pre_post_link', $permalink, $post, $leavename );
apply_filters( 'post_link_category', $cats[0], $cats, $post );
// apply_filters( 'post_link', $permalink, $post, $leavename );
// 	apply_filters( 'the_permalink', get_permalink() );
apply_filters( 'post_type_link', $post_link, $post, $leavename, $sample );
// apply_filters( 'page_link', $link, $post->ID, $sample );
√ apply_filters( '_get_page_link', $link, $post->ID );
apply_filters( 'attachment_link', $link, $post->ID );
apply_filters( 'year_link', $yearlink, $year );
apply_filters( 'month_link', $monthlink, $year, $month );
apply_filters( 'day_link', $daylink, $year, $month, $day );
apply_filters( 'the_feed_link', $link, $feed );
apply_filters( 'feed_link', $output, $feed );
apply_filters( 'post_comments_feed_link', $url );
apply_filters( 'post_comments_feed_link_html', "<a href='$url'>$link_text</a>", $post_id, $feed );
apply_filters( 'author_feed_link', $link, $feed );
apply_filters( 'category_feed_link', $link, $feed );
apply_filters( 'tag_feed_link', $link, $feed );
apply_filters( 'taxonomy_feed_link', $link, $feed, $taxonomy );
// apply_filters( 'get_edit_tag_link', get_edit_term_link( $tag_id, $taxonomy ) );
// apply_filters( 'edit_tag_link', $link );
// apply_filters( 'get_edit_term_link', $location, $term_id, $taxonomy, $object_type );
// apply_filters( 'edit_term_link', $link, $term->term_id )
apply_filters( 'search_link', $link, $search );
apply_filters( 'search_feed_link', $link, $feed, 'posts' );
apply_filters('search_feed_link', $link, $feed, 'comments');
apply_filters( 'post_type_archive_link', $link, $post_type );
apply_filters( 'post_type_archive_feed_link', $link, $feed );
// apply_filters( 'get_edit_post_link', admin_url( sprintf( $post_type_object->_edit_link . $action, $post->ID ) ), $post->ID, $context );
// apply_filters( 'edit_post_link', $link, $post->ID, $text )
// apply_filters( 'get_delete_post_link', wp_nonce_url( $delete_link, "$action-post_{$post->ID}" ), $post->ID, $force_delete );
// apply_filters( 'get_edit_comment_link', $location );
// apply_filters( 'edit_comment_link', $link, $comment->comment_ID, $text )
// apply_filters( 'get_edit_bookmark_link', $location, $link->link_id );
// apply_filters( 'edit_bookmark_link', $link, $bookmark->link_id )
// apply_filters( 'get_edit_user_link', $link, $user->ID );
apply_filters( "{$adjacent}_post_rel_link", $link );
apply_filters( "{$adjacent}_post_link", $output, $format, $link, $post );
apply_filters( 'get_pagenum_link', $result );
apply_filters( 'get_comments_pagenum_link', $result );
apply_filters( 'home_url', $url, $path, $orig_scheme, $blog_id );
apply_filters( 'site_url', $url, $path, $scheme, $blog_id );
apply_filters( 'admin_url', $url, $path, $blog_id );
// apply_filters( 'includes_url', $url, $path );
// apply_filters( 'content_url', $url, $path);
// apply_filters( 'plugins_url', $url, $path, $plugin );
// apply_filters( 'network_site_url', $url, $path, $scheme );
// apply_filters( 'network_home_url', $url, $path, $orig_scheme);
// apply_filters( 'network_admin_url', $url, $path );
// apply_filters( 'user_admin_url', $url, $path );
// apply_filters( 'set_url_scheme', $url, $scheme, $orig_scheme );
// apply_filters( 'user_dashboard_url', $url, $user_id, $path, $scheme);
// apply_filters( 'edit_profile_url', $url, $user_id, $scheme);
// apply_filters( 'pre_get_shortlink', false, $id, $context, $allow_slugs );
apply_filters( 'get_shortlink', $shortlink, $id, $context, $allow_slugs );
	apply_filters( 'the_shortlink', $link, $shortlink, $text, $title );

apply_filters( 'term_link', $termlink, $term, $taxonomy );
*/


/**
 * A helper class for registering and handling a custom rewrite tag for a custom taxonomy.
 *
 * @version 1.1.0
 */
class GlottyBotPermastruct {

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
     * Initializes the class by calling Add_Taxonomy_To_Post_Permalinks::register()
     * as well as registering a filter that runs in get_permalink().
     *
     * @since 1.0.0
     *
     * @param string $taxonomy A taxonomy slug. Use the same one that you used with register_taxonomy().
     * @return array $optional_args Optional configuration parameters. See Add_Taxonomy_To_Post_Permalinks::register().
     */
    private function __construct( ) {
        add_action( 'plugins_loaded' , array( &$this , 'rewrite_server_request' ) , 10 , 2 );
        
		if ( '' != get_option('permalink_structure') ) {
			add_filter( 'pre_post_link' , array( &$this , 'post_permalink' )  , 10 , 3 );
			add_filter( '_get_page_link' , array( &$this , 'page_permalink' )  , 10 , 2 );
			add_filter( 'term_link' ,  array( &$this , 'term_permalink' ) , 10 , 3 );
// 			add_filter( 'home_url' ,  array( &$this , 'home_url' )  , 10 , 4 );
			/* 
				filter attachment_url, page_url, archive_url, ....
			*/
		} else {
			add_filter( 'post_link' ,  array( &$this , 'post_permalink_get' ) , 10 , 3 );
			add_filter( 'term_link' ,  array( &$this , 'post_permalink_get' ) , 10 , 3 );
		}
		add_filter( 'theme_locale', '_glottybot_current_language' );
    }
    
    public function get_language($locale='') {
		return glottybot_language_code_sep( $this->language , '_' );
    }
    
//     function home_url( $url, $path, $orig_scheme, $blog_id ) {
//     	if ( $path == '' )
//     		return $url;
//     	$language = glottybot_current_language( );
//     	$h = home_url();
//     	$add_slug = $h.'/'.$this->get_language_slug($language);
//     	if ( strpos( $url , $add_slug ) === false )
// 	    	return str_replace($h,$add_slug,$url);
// 	    return $url;
//     }
//     
    function page_permalink( $permalink, $post_id ) {
    	if ( $post_id && strpos( $permlink, '?page_id=' ) === false ) {
    		$permalink = $this->_prepend_language_slug( $permalink );
    	}
    	return $permalink;
    }
	function post_permalink( $permalink, $post, $leavename ) {
		if ( '' != $permalink && $post->post_language != glottybot_default_language( ) ) {
			if ( $slug = $this->get_language_slug($post->post_language) )
				return "/{$slug}$permalink";
		}
		return $permalink;
	}
    
	function term_permalink( $permalink, $post, $leavename ) {
		if ( '' != $permalink && get_locale() != glottybot_default_language('_' ) ) {	
			$permalink = $this->_prepend_language_slug( $permalink );
		}
		return $permalink;
	}
    
    
    
    private function _prepend_language_slug( $url ) {
    	$language = glottybot_current_language( );
    	if ( $language != glottybot_default_language() ) {
			$h = home_url();
			$add_slug = $h.'/'.$this->get_language_slug($language);
			if ( strpos( $url , $add_slug ) === false )
				return str_replace($h,$add_slug,$url);
		}
    	return $url;
    }
    
	
	function get_language_slug( $language ) {
		if ( glottybot_default_language('-') == glottybot_language_code_sep( $language , '-' ) )
			return '';
		$struct = get_option('glottybot_permalink_structure');
		$slug = $language;
		if ( isset( $struct[$slug] ) )
			return $struct[$slug];
		return $language;
	}
	
	
	
	function post_permalink_get( $permalink, $post, $leavename ) {
		if ( $post->post_language != get_bloginfo( 'language' ) ) {
			// add lang URL param
		}
		return $permalink;
	}
    
	function rewrite_server_request( ) {
		$this->language = get_bloginfo('language');
		
		$struct = $this->sanitize_glottybot_permalink_structure( get_option('glottybot_permalink_structure') );

		foreach ( $struct as $code => $rewrite ) {
			if ( $in_req_uri = ( 0 === strpos( $_SERVER['REQUEST_URI'] , "/$rewrite" ) ) ||
				$in_qv = ( isset( $_REQUEST['language'] ) && in_array( glottybot_language_code_sep( $_REQUEST['language'] , '-' ) , array( $code , $rewrite ) ) )
				) {
				if ( $in_req_uri )
					$_SERVER['REQUEST_URI'] = str_replace("/$rewrite",'',$_SERVER['REQUEST_URI']);
				$this->language = $code;
				break;
			}
		}
		
		global $pagenow , $locale;
		if ( ! is_admin() /*|| $pagenow !== 'options-general.php' */) {
			add_filter( 'locale' , array( &$this , 'get_language' ) );
			$locale = glottybot_language_code_sep( $this->language , '_' );
		}
	}
	/**
	 * Sanitize value of setting_1
	 *
	 * @return string sanitized value
	 */
	function sanitize_glottybot_permalink_structure( $value ) {
		$value = (array) $value;
		$active_langs = glottybot_language_code_sep( get_option('glottybot_additional_languages') , '-' );
		/* SANITATION
		- make sure all slugs are set and sluggish
		*/
		foreach ( $active_langs as $lang ) {
			$value[$lang] = sanitize_title(isset($value[$lang]) ? $value[$lang] : $lang );
		}
		return $value;
	}

}