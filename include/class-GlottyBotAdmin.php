<?php


/**
 *	Glottybot Admin Backend.
 *	Translator menu: add arg 'set_glotty_language'
 *	isset($_REQUEST['set_glotty_language']) -> set_cookie('glotty_language',$_REQUEST['set_glotty_language'])
 *	- Add Language selector to admin bar.
 *	- ~~Filter admin url~~
 */
if ( ! class_exists( 'GlottyBotAdmin' ) ):
class GlottyBotAdmin {
	private static $_instance = null;
	private $cookie_name = 'glotty_admin_locale';
	private $locale;
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
		add_action( 'admin_init' , array( &$this , 'admin_init' ) );
		add_action( 'admin_bar_menu', array( &$this , 'add_admin_bar_language_links' ) ,100);
		add_action( 'load-post.php' , array( &$this , 'set_locale_from_post') );
	}
	
	
	function set_locale_from_post() {
		if ( isset($_REQUEST['post']) ) {
			$post = get_post($_REQUEST['post']);
			$this->set_locale($post->post_locale);
		}
	}

	/**
	 * Admin init
	 */
	function admin_init() {
		if ( isset( $_GET['set_admin_locale'] ) ) {
			$this->set_locale( $_GET['set_admin_locale'] );
			wp_redirect( remove_query_arg('set_admin_locale') );
			exit();
		}
		wp_enqueue_style( 'glottybot-admin' , plugins_url('css/glottybot-admin.css', dirname(__FILE__)) );
		wp_enqueue_style( 'glottybot-flags' );

		wp_enqueue_script( 'rangyinputs-jquery' , plugins_url('js/rangyinputs-jquery.js', dirname(__FILE__)) , array('jquery',));
	}
	
	
	/**
	 *	Setup WP Admin bar
	 *
	 *	@param $wp_admin_bar string the language code to sanitize
	 */
	function add_admin_bar_language_links( $wp_admin_bar ) {
		
		$locales = GlottyBot()->get_locale_names();
		$parent = 'glottybot_language';
		$curr_locale = false;

		$is_edit_page = $this->is_admin_page( 'post.php' );
		
		if ( $is_edit_page && $post = GlottyBotPost( $_REQUEST['post'] ) ) {
			$curr_locale = $post->post_locale;
		}
		if ( ! $curr_locale )
			$curr_locale = $this->get_locale( );

		$add_menu_args = array(
			'id' => $parent,
			'title' => GlottyBotTemplate::i18n_item( $curr_locale )
				.'<strong>'.$locales[$curr_locale] .'</strong>' ,
			'href' => false,
			'meta' => array(
				'class' => 'dashicons-translation',
			),
		);
		$wp_admin_bar->add_menu( $add_menu_args );
		foreach ( $locales as $locale => $locale_name ) {
			$title = sprintf('%s<strong>%s</strong>' , GlottyBotTemplate::i18n_item( $locale ) , $locale_name );
			$href = add_query_arg('set_admin_locale' , $locale );
			$meta = array();
			
			if ( $is_edit_page  ) {
				if ( $translation = $post->get_translation($locale) ) {
					$href = get_edit_post_link( $translation->ID , '' );
					$href = add_query_arg('set_admin_locale' , $locale , $href );
				}
			}
			
			$add_submenu_args = array(
				'id' => "{$parent}-{$locale}",
				'parent' => $parent,
				'title' => $title,
				'href' => $href,//admin_url(),
				'meta' => $meta,
			);
			$wp_admin_bar->add_menu( $add_submenu_args );
		}
	}
	
	function set_locale( $locale ) {
		$avail_langs = GlottyBot()->get_locales();
		if ( in_array( $locale , $avail_langs ) ) {
			setcookie( $this->cookie_name , $locale , time()+60*60*24*365 ,'/' );
		}
	}
	
	function get_locale() {
		$avail_langs = GlottyBot()->get_locales();
		if ( isset( $_COOKIE[ $this->cookie_name ] ) && in_array( $_COOKIE[ $this->cookie_name ] , $avail_langs ) )
			return $_COOKIE[ $this->cookie_name ];
		else if ( $locale = array_shift( $avail_langs ) )
			return $locale;
		return false;
	}
	function get_locale_name() {
		if ( $locale = $this->get_locale() ) {
			$locales = GlottyBot()->get_locale_names();
			return $locales[$locale];
		}
	}
	
	
	/**
	 *	See we are on a specific admin page.
	 *	Usage:
	 *	```
	 *	$this->is_admin_page( 'plugins.php' , 'themes.php' , 'tools.php' , 'users.php' );
	 *	```
	 *
	 *	@params any admin page hooks
	 */
	function is_admin_page( ) {
		global $pagenow;
		
		$args = func_get_args();
		foreach ( $args as $is_page )
			if ( $pagenow == $is_page )
				return true;
		return false;
		
	}

}

function GlottyBotAdmin() {
	return GlottyBotAdmin::instance();
}

endif;