<?php


if ( ! class_exists( 'GlottyBotAdminMenus' ) ):
class GlottyBotAdminMenus extends GlottyBotAdminPomo {
	private static $_instance = null;
	protected $textdomain_prefix = 'menu';
	
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
		add_action( 'load-admin.php' , array( &$this , 'admin_translate_menu' ) );
		add_action( 'after_menu_locations_table' , array( &$this , 'show_menu_translate_link' ) );
		add_action( 'load-nav-menus.php' , array( &$this , 'load_show_menu_translate_link' ) );
		/*
		Menu items title:
			- 
		*/
	}
	
	/**
	 *	Add translate menu links in nav menu edit.
	 *	Hooked into `load-nav-menus.php`.
	 */
	function load_show_menu_translate_link() {
		add_action( 'in_admin_footer' , array( &$this , 'show_menu_translate_link' ) );
	}

	/**
	 *	Redirect to menu translation UI, if URL params are properly set.
	 *	Hooked into `load-admin.php`.
	 */
	function admin_translate_menu( ) {
		if ( isset( $_REQUEST['action'] ) && $_REQUEST['action'] == 'translate-menu' ) {
			if ( isset( $_REQUEST['menu'] , $_REQUEST['target_language'] ) ) {
				$menu = $_REQUEST['menu'];
				$target_language = glottybot_sanitize_language_code( $_REQUEST['target_language'] , '_' , true );
				
				if ( is_nav_menu($menu) && $target_language ) {
					$nonce_name = "translate-menu-$menu";
					check_admin_referer( $nonce_name );
					
					// same as in wp-admin/nav-menus.php
					if ( ! current_user_can( 'edit_theme_options' ) )
						wp_die( 'Insufficient Privileges' );
					
 					$this->translate_menu( $menu , $target_language );
				}
			}
		}
	}

	
	/**
	 *	Translate Menu UI elements.
	 *	Hooked into `load-admin.php` > `in_admin_footer`.
	 */
	function show_menu_translate_link( ) {
		global $nav_menu_selected_id;
		if  ( ! is_nav_menu($nav_menu_selected_id) ) {
			return;
		}
		$languages = glottybot_language_code_sep( get_option( 'glottybot_additional_languages' ) , '_' );

		?><div id="glottybot-translate-links"><?php
		?><dl class="add-translations"><?php
			?><dt class="howto"><?php _e( 'Multilingual' , 'wp-glottybot' ); ?></dt><?php
				?><dd class="checkbox-input"><?php
		
		if ( $languages ) {
			foreach ( $languages as $language_code ) {
				$nonce_name = "translate-menu-$nav_menu_selected_id";
				$href = add_query_arg(array(
					'menu' => $nav_menu_selected_id,
					'action' => 'translate-menu', 
					'target_language' => $language_code,
					'_wpnonce' => wp_create_nonce( $nonce_name ),
				),admin_url('admin.php'));
				
				$langname = glottybot_get_language_name( $language_code );
				if ( $this->has_po( $nav_menu_selected_id , $language_code ) ) {
					$label = sprintf(_x('Edit %s Translation' , 'language' , 'wp-glottybot' ), $langname );
					printf( '<a href="%s" class="button-primary">%s</a>' , $href , $label );
				} else {
					$label = sprintf(_x('Translate to %s' , 'language' , 'wp-glottybot' ), $langname );
					printf( '<a href="%s" class="button-secondary">%s</a>' , $href , $label );
				}
			}
		}
				?></dd><?php
			?></dl><?php
		?></div><?php
		?><script type="text/javascript">
		(function($){
			$('#glottybot-translate-links').appendTo('.menu-settings');
		})(jQuery);
		</script><?php
	}
	
	/**
	 *	Redirect to menu translation UI.
	 *	
	 *	@param $menu_id int ID of the menu to translate
	 *	@param $language string target language
	 */
	function translate_menu( $menu_id , $language ) {
		if ( ! is_nav_menu( $menu_id ) )
			return false;
		
		if ( $created_pot = $this->create_pot_from_menu( $menu_id ) ) {
			$textdomain = $this->get_textdomain( $menu_id );
			if ( ! $this->has_po( $menu_id , $language ) ) {
				$redirect = admin_url( 'admin.php' );
				$redirect = add_query_arg( array(
					'page' => 'loco-translate',
					'custom-locale' => $language,
					'name' => $textdomain,
					'msginit' => $textdomain,
					'type' => 'core',
				) , $redirect );
			} else {
				$redirect = admin_url( 'admin.php' );
				$redirect = add_query_arg( array(
					'page' => 'loco-translate',
					'poedit' => "languages/$textdomain-{$language}.po",
					'name' => $textdomain,
					'type' => 'core',
				) , $redirect );
			}
		} else {
			$redirect = admin_url( 'nav-menus.php' );
			
		}
		
		wp_redirect($redirect);
	}
	
	/**
	 *	Create a pot file from menu entries.
	 *	
	 *	@param $menu_id int ID of the menu to translate
	 *	@return string file path to generated pot file.
	 */
	function create_pot_from_menu( $menu_id ) {
		global $current_user;
		get_currentuserinfo();
		
		if ( ! wp_is_writable(WP_LANG_DIR) )
			return;
		
		$pot_file = $this->get_pot_file_name( $menu_id );
		
		$menu = wp_get_nav_menu_object( $menu_id );
		$menu_items = wp_get_nav_menu_items( $menu_id , array(
			'nopaging'	=> true,
		) );
			
		$header_template = 'msgid ""
msgstr ""
"Project-Id-Version: Nav Menu %menu_name%\n"
"Report-Msgid-Bugs-To: \n"
"POT-Creation-Date: Sun Nov 30 2014 21:55:56 GMT+0100 (CET)\n"
"PO-Revision-Date: Sun Nov 30 2014 21:57:45 GMT+0100 (CET)\n"
"Last-Translator: %current_user%\n"
"Language-Team: \n"
"Language: \n"
"MIME-Version: 1.0\n"
"Content-Type: text/plain; charset=UTF-8\n"
"Content-Transfer-Encoding: 8bit\n"

';
		$entry_template = '# Nav Menu %d Entry %d
msgid "%s"
msgstr ""

';
		$template_vars = array( 
			'%menu_name%' => $menu->name , 
			'%current_user%' => sprintf( '%s <%s>' , $current_user->display_name, $current_user->user_email ),
		);
		$save_pot = false;
		$pot = strtr( $header_template , $template_vars );
		header('Content-Type: text/plain');
		foreach ( $menu_items as $item ) {
			if ($item->post_title != '') {
				$msg = $this->wrap_multiline_messages( trim( $item->post_title) );

				$pot .= sprintf( $entry_template , $menu_id , $item->id , $msg );
				$save_pot = true;
			}
		}
		if ( $save_pot )
			file_put_contents( $pot_file , $pot );
		return $save_pot;
	}

}

endif;