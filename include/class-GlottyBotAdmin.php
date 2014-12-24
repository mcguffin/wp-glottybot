<?php


if ( ! class_exists( 'GlottyBotAdmin' ) ):
class GlottyBotAdmin {
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
		wp_enqueue_style( 'glottybot-flags' , plugins_url('css/flag-icon-css/css/l18n.css', dirname(__FILE__)) );
// 		wp_enqueue_script( 'glottybot-admin' , plugins_url('js/glottybot-editpost.js', dirname(__FILE__)) , array( 'jquery' ) );
	}
	
	function filter_admin_url( $url ) {
// 		foreach ( array_keys(glottybot_wp_get_available_translations()) as $k) {
// 			if ( strlen($k)==5 )
// 				printf( "'%s'," , strtolower(substr($k,-2) ) );
// 		}
// 		echo "\n\n\n";
// 		foreach ( array_keys(glottybot_wp_get_available_translations()) as $k) {
// 			if ( strlen($k)!=5 )
// 				printf( "'%s'," , $k );
// 		}
// 		exit();
		if ( $this->is_admin_page( 'plugins.php' , 'themes.php' , 'tools.php' , 'users.php' ) )
			return $url;
		parse_str(parse_url($url, PHP_URL_QUERY), $vars);
		if ( ! isset($vars['language']) )
			$url = add_query_arg( 'language' , glottybot_current_language() , $url );
		return $url;
	}
	
	function add_admin_bar_language_links( $wp_admin_bar ) {
		
		$parent = 'glottybot_language';
		$curr_lang = glottybot_current_language( '-' );

		$add_menu_args = array(
			'id' => $parent,
			'title' => GlottyBotTemplate::i18n_item( $curr_lang ). 
				sprintf( __('Language: %s','wp-glottybot') , '<strong>'.glottybot_get_language_name( $curr_lang ) .'</strong>' ),
			'href' => false,
			'meta' => array(
				'class' => 'dashicons-translation',
			),
		);
		$wp_admin_bar->add_menu( $add_menu_args );
		$is_edit_page = $this->is_admin_page( 'post.php' );

		foreach ( glottybot_available_languages() as $code ) {
			$post_code = glottybot_language_code_sep( $code , '_' );
			$title = sprintf('%s<strong>%s</strong>' , GlottyBotTemplate::i18n_item( $post_code ) , glottybot_get_language_name( $code ) );
			$href = add_query_arg('language' , $code );
			$meta = array();
			if ( $is_edit_page ) {
				if ( $translation = glottybot_get_translated_post( $_REQUEST['post'] , $code ) ) {
					$href = get_edit_post_link( $translation->ID , '' );
					$href = add_query_arg('language' , $post_code , $href);
				} else {
					$nonce_name = sprintf('glottybot_copy_post-%s-%d' , $code , $_REQUEST['post'] );
					$href = glottybot_get_clone_post_link( $_REQUEST['post'] , $post_code );
					$title = sprintf( _x( 'Add: %s' , 'language' , 'wp-glottybot' ) , $title );
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
	function is_admin_page( ) {
		global $pagenow;
		
		$args = func_get_args();
		foreach ( $args as $is_page )
			if ( $pagenow == $is_page )
				return true;
		return false;
		
	}

}

endif;