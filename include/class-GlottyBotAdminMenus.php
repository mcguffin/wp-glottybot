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
	 *	Prevent cloning
	 */
	private function __clone() {}

	/**
	 * Private constructor
	 */
	private function __construct() {
		add_action( 'load-admin.php' , array( &$this , 'admin_translate_menu' ) );
		add_action( 'after_menu_locations_table' , array( &$this , 'show_menu_translate_link' ) );
		add_action( 'load-nav-menus.php' , array( &$this , 'load_show_menu_translate_link' ) );

		add_action( 'load-nav-menus.php' , array( &$this , 'load_menu_editor' ) );
		add_action( 'wp_ajax_glottybot-add-menu-item' , array( &$this , 'ajax_add_menu_items' )  );
	}

	
	/**
	 *	Init Language menu items
	 */
	function load_menu_editor() {
		add_meta_box( 'glottybot-languages', __('Languages','wp-glottybot'), array(&$this,'languages_metabox'), null, 'side', 'default', null );
		wp_enqueue_script( 'glottybot-editmenu' , plugins_url('js/glottybot-editmenu.js', dirname(__FILE__)) , array( 'jquery' ) );
		wp_localize_script( 'glottybot-editmenu' , 'glottybot' , array(
			'ajaxurl'	=> remove_query_arg('language',admin_url( 'admin-ajax.php' )),
			'ajax'		=> array(
				'_wpnonce'	=> wp_create_nonce( 'glottybot-add-menu-item' ),
				'action'	=> 'glottybot-add-menu-item',
			),
		));
	}
	
	/**
	 *	Language menu items Metabox
	 */
	function languages_metabox() {
		$langs = glottybot_available_languages();
		?><ul id="glottybot-languages-menu-items"><?php
		foreach ( $langs as $lang ) {
			?><li><label><input type="checkbox" value="<?php echo $lang ?>">&nbsp;<?php
				echo glottybot_get_language_name($lang);
			?></label></li><?php
		}
		?></ul><?php
		
		?><p class="button-controls"><span class="add-to-menu"><?php
		?><button class="button-secondary submit-add-to-menu right" 
			  name="glottybot-add-menu-item" id="glottybot-add-menu-item"><?php _e( 'Add to Menu' ) ?></button><?php
		?><span class="spinner"></span><?php
		?></span></p><?php
	}
	function ajax_add_menu_items() {
		// check nonce, permission
		! current_user_can( 'edit_theme_options' ) AND die( '-1' );
		
		check_ajax_referer( 'glottybot-add-menu-item' , '_wpnonce' );

		$item_ids = array();
		$menu_items = array();
		if ( isset( $_POST['languages'] ) && count( $_POST['languages'] ) ) {
			foreach( $_POST['languages'] as $language_code ) {
				$menu_item_data = array(
					'menu-item-title'  => glottybot_get_language_name( $language_code ),
					'menu-item-type'   => 'glottybot_language' ,
					'menu-item-object' => $language_code ,
					'menu-item-url'    => '---glottybot-language---' ,
				);
				
				$item_ids[] = wp_update_nav_menu_item( 0, 0, $menu_item_data );
			}

			is_wp_error( $item_ids ) AND die( '-1' );
			
			// Set up menu items
			foreach ( (array) $item_ids as $menu_item_id ) {
				$menu_obj = get_post( $menu_item_id );
				if ( ! empty( $menu_obj->ID ) ) {
					$menu_obj = wp_setup_nav_menu_item( $menu_obj );
					// don't show "(pending)" in ajax-added items
					$menu_obj->label = $menu_obj->title;

					$menu_items[] = $menu_obj;
				}
			}

			// Needed to get the Walker up and running
			require_once ABSPATH.'wp-admin/includes/nav-menu.php';


			// This gets the HTML to returns it to the menu
			if ( ! empty( $menu_items ) ) {
				$args = array(
					'after'       => '',
					'before'      => '',
					'link_after'  => '',
					'link_before' => '',
					'walker'      => new Walker_Nav_Menu_Edit
				);

				echo walk_nav_menu_tree(
					$menu_items,
					0,
					(object) $args
				);
			}
		}
		
		exit;
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
		
		if ( $created_pot = $this->create_pot( $menu_id ) ) {
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
	function create_pot( $menu_id ) {
		global $current_user;
		get_currentuserinfo();
		
		if ( ! wp_is_writable(WP_LANG_DIR) )
			return;
		
		$save_pot_file = $this->get_pot_file_name( $menu_id );

		$menu = wp_get_nav_menu_object( $menu_id );
		$menu_items = wp_get_nav_menu_items( $menu_id , array(
			'nopaging'	=> true,
		) );

		$po = $this->get_po( );
		$po->set_header('Project-Id-Version' , "Nav Menu {$menu->name}");
		
		foreach ( $menu_items as $item ) {
			$entry = new Translation_Entry(array(
				'singular' => trim( $item->post_title) ,
				'translator_comments' => sprintf('# Nav Menu %d Entry %d' , $menu_id , $item->id )
			));
			$po->add_entry( $entry );
		}
		$po->export_to_file( $save_pot_file );
		return $save_pot_file;
	}

}

endif;