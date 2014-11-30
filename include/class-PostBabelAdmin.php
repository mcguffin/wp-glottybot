<?php


if ( ! class_exists( 'PostBabelAdmin' ) ):
class PostBabelAdmin {
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
		add_action( 'admin_init' , array( &$this , 'admin_init' ) );
		add_action( 'admin_bar_menu', array( &$this , 'add_admin_bar_language_links' ) ,100);
		add_filter( 'admin_url' , array( &$this , 'filter_admin_url' ) );
	}

	/**
	 * Admin init
	 */
	function admin_init() {
	}
	
	function filter_admin_url( $url ) {
		parse_str(parse_url($url, PHP_URL_QUERY), $vars);
		if ( ! isset($vars['language']) )
			$url = add_query_arg( 'language' , postbabel_current_language() , $url );
		return $url;
	}
	
	function add_admin_bar_language_links( $wp_admin_bar ) {
		global $pagenow;
		
		$parent = 'postbabel_language';
		$curr_lang = postbabel_current_language( '-' );

		$add_menu_args = array(
			'id' => $parent,
			'title' => '<span class="ab-icon dashicons dashicons-translation"></span>' . 
				sprintf( __('Language: %s','wp-post-babel') , '<strong>'.postbabel_get_language_name( $curr_lang ) .'</strong>' ),
			'href' => false,
			'meta' => array(
				'class' => 'dashicons-translation',
			),
		);
		$wp_admin_bar->add_menu( $add_menu_args );
		$is_edit_page = 'post.php' == $pagenow;

		foreach ( postbabel_available_languages() as $code ) {
			$post_code = postbabel_language_code_sep( $code , '_' );
			$title = sprintf('<strong>%s</strong>', postbabel_get_language_name( $code ) );
			$href = add_query_arg('language' , $code );
			$meta = array();
			if ( $is_edit_page ) {
				if ( $translation = postbabel_get_translated_post( $_REQUEST['post'] , $code ) ) {
					$href = get_edit_post_link( $translation->ID , '' );
					$href = add_query_arg('language' , $post_code , $href);
				} else {
					$nonce_name = sprintf('postbabel_copy_post-%s-%d' , $code , $_REQUEST['post'] );
					$href = postbabel_get_clone_post_link( $_REQUEST['post'] , $post_code );
					$title = sprintf( _x( 'Add: %s' , 'language' , 'wp-post-babel' ) , $title );
					//	 '<span styl class="ab-icon dashicons dashicons-welcome-add-page"></span>' . 
				}
			}
				
			$add_submenu_args = array(
				'id' => "{$parent}-{$code}",
				'parent' => $parent,
				'title' => $title,
				'href' => $href,//admin_url(),
				'meta' => $meta,
			);
			$wp_admin_bar->add_menu( $add_submenu_args );
		}
	}

	/**
	 * Enqueue options Assets
	 */
	function enqueue_assets() {

	}
	

}

endif;