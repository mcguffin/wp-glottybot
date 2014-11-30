<?php



/**
 * A helper class for registering and handling a custom rewrite tag for a custom taxonomy.
 *
 * @version 1.1.0
 */
class PostBabelPermastruct {
 
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
		if ( '' != get_option('permalink_structure') )
			add_filter( 'pre_post_link' , array( &$this , 'post_permalink' )  , 10 , 3 );
		else 
			add_filter( 'post_link' ,  array( &$this , 'post_permalink_get' ) , 10 , 3 );
    }
    
    public function get_language() {
		return $this->language;
    }
    
	function post_permalink( $permalink, $post, $leavename ) {
		if ( '' != $permalink && $post->post_language != get_bloginfo( 'language' ) ) {
			$struct = get_option('post_babel_permalink_structure');
			$slug = $post->post_language;
			if ( isset( $struct[$slug] ) )
				$slug = $struct[$slug];
			return "/{$slug}$permalink";
		}
		return $permalink;
	}
	
	function post_permalink_get( $permalink, $post, $leavename ) {
		if ( $post->post_language != get_bloginfo( 'language' ) ) {
			// add lang URL param
		}
		return $permalink;
	}
    
	function rewrite_server_request( ) {
		$this->language = get_bloginfo('language');
		
		$struct = $this->sanitize_postbabel_permalink_structure( get_option('post_babel_permalink_structure') );
		
		
		
		foreach ( $struct as $code => $rewrite ) {
			if ( $in_req_uri = ( 0 === strpos( $_SERVER['REQUEST_URI'] , "/$rewrite" ) ) ||
				$in_qv = ( isset( $_REQUEST['language'] ) && in_array( postbabel_language_code_sep( $_REQUEST['language'] , '-' ) , array( $code , $rewrite ) ) )
				) {
				if ( $in_req_uri )
					$_SERVER['REQUEST_URI'] = str_replace("/$rewrite",'',$_SERVER['REQUEST_URI']);
				$this->language = $code;
				break;
			}
		}
	}
	/**
	 * Sanitize value of setting_1
	 *
	 * @return string sanitized value
	 */
	function sanitize_postbabel_permalink_structure( $value ) {
		$value = (array) $value;
		$active_langs = postbabel_language_code_sep( get_option('post_babel_additional_languages') , '-' );
		/* SANITATION
		- make sure all slugs are set and sluggish
		*/
		foreach ( $active_langs as $lang ) {
			$value[$lang] = sanitize_title(isset($value[$lang]) ? $value[$lang] : $lang );
		}
		return $value;
	}

}