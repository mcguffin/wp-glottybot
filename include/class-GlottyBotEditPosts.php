<?php

/**
 *	Edit Posts translations
 */
if ( ! class_exists('GlottyBotEditPosts') ) :
class GlottyBotEditPosts {
	private static $_instance = null;
	
	private static $lang_col_prefix = 'glottybot_translation-';
	
	private $optionset = 'glottybot_options'; // writing | reading | discussion | media | permalink

	private $clone_post_action_name = 'glottybot_clone_post';

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
		// edit post
		add_action('add_meta_boxes' , array( &$this , 'add_meta_boxes' ) , 10 , 2 );
		add_action( 'wp_ajax_' . $this->clone_post_action_name , array( &$this , 'ajax_clone_post' ) );
		add_action( 'load-edit.php' , array( &$this , 'maybe_clone_post' ) );

		add_action( 'admin_init' , array( &$this , 'admin_register_scripts' ) );
		add_action( 'load-edit.php' , array( &$this , 'add_post_type_columns' ) );
		add_action( 'load-upload.php' , array( &$this , 'add_post_type_columns' ) );

		add_action( 'load-post.php' , array( &$this , 'enqueue_script_style' ) );
		add_action( 'load-post-new.php' , array( &$this , 'enqueue_script_style' ) );
		
		add_filter( 'wp_insert_post_data', array( &$this , 'filter_insert_post_data' ) , 10 , 2 );
		add_filter( 'wp_insert_attachment_data', array( &$this , 'filter_insert_post_data' ), 10 , 2 );
		
		add_action( 'page_row_actions' , array( &$this , 'row_actions' ) , 10 , 2 );
		add_action( 'post_row_actions' , array( &$this , 'row_actions' ) , 10 , 2 );
		
		// y?
// 		add_filter( 'redirect_post_location', array( &$this , 'redirect_post_location' ) , 10 , 2 );
	}
	
	/**
	 *	@action 'admin_init'
	 */
	function admin_register_scripts() {
		wp_register_style( 'glottybot-editpost' , plugins_url('css/glottybot-editpost.css', dirname(__FILE__)) );
		wp_register_script( 'glottybot-editpost' , plugins_url('js/glottybot-editpost.js', dirname(__FILE__)) , array( 'jquery' ) );
	}
	
	//
	//	Cloning
	//

	/**
	 *	URL to post cloning
	 *
	 *	@param $source_id
	 *	@param $target_locale
	 *	@return string URL
	 */
	function clone_post_url( $source_id , $target_locale ){
		return add_query_arg( 
			$this->clone_post_url_args(  $source_id , $target_locale ),
			admin_url('edit.php')
		);
		
	}

	/**
	 *	Ajax URL to post cloning
	 *
	 *	@param $source_id
	 *	@param $target_locale
	 *	@return string URL
	 */
	function ajax_clone_post_url( $source_id , $target_locale ) {
		return add_query_arg( 
			$this->clone_post_url_args(  $source_id , $target_locale ),
			admin_url('admin-ajax.php')
		);
	}
	
	/**
	 *	@param $source_id
	 *	@param $target_locale
	 *	@return array
	 */
	function clone_post_url_args(  $source_id , $target_locale ) {
		if ( ! intval( $source_id ) )
			return false;
		if ( ! $target_locale )
			return false;
		if ( ! current_user_can( 'edit_post' , $source_id ) )
			return false;
		
		$action		= $this->clone_post_action_name;
		$nonce_name	= sprintf('%s-%s-%d' , $action , $target_locale , $source_id );
		$nonce		= wp_create_nonce( $nonce_name );
		return array(
			'action' => $action,
			'_wpnonce' => $nonce,
			'source_id' => intval( $source_id ),
			'target_locale'	=> $target_locale,
		);
	}
	
	
	/**
	 *	@param $source_id
	 *	@param $target_locale
	 *	@return object GlottyBotPost | object WP_Error | bool false
	 */
	function get_post_to_clone( ) {
		if ( isset( $_REQUEST['action'] ) && $_REQUEST['action'] == $this->clone_post_action_name ) {
			if ( isset( $_REQUEST['target_locale'] , $_REQUEST['source_id'] , $_REQUEST['_wpnonce'] ) ) {
				
				// $source_id set?
				$source_id		= intval($_REQUEST['source_id']);
				if ( ! $source_id )
					return new WP_Error( __('Bad request') );

				// $target_locale installed?
				$target_locale	= $_REQUEST['target_locale'];
				if ( ! in_array( $target_locale , GlottyBot()->get_locales() ) )
					return new WP_Error( __('Requested Locale inactive') );
				
				// permissions okay?
				$nonce_name		= sprintf('%s-%s-%d' , $this->clone_post_action_name , $target_locale , $source_id );
				if ( ! wp_verify_nonce( $_REQUEST['_wpnonce'] , $nonce_name ) || ! current_user_can( 'edit_post' , $source_id ) )
					return new WP_Error( __('Insufficient permission') );
				
				$post = GlottyBotPost( $source_id );
				if ( $post->ID && $target_locale == $post->post_locale )
					return new WP_Error( __( 'Post translation exists' ) );
				
				// post exists
				return $post;
			} else {
				return new WP_Error( __('Bad request') );
			}
		}
		return false;
	}

	/**
	 *	@action load-edit.php
	 */
	function maybe_clone_post() {
		$post = $this->get_post_to_clone( );
		if ( $post !== false ) {
			if ( is_wp_error( $post ) ) {
				wp_die( $post );
			} else if ( $post instanceof GlottyBotPost ) {
				// do clone
				$translated_post_id = $post->clone_for_translation( $_REQUEST['target_locale'] );
				if ( is_wp_error( $translated_post_id ) )
					wp_die( $translated_post_id );
				
				$redirect = get_edit_post_link( $translated_post_id , 'redirect' );
				wp_redirect( $redirect );
				exit();
			}
		}
	}

	
	/**
	 *	@action 'wp_ajax_'.$this->clone_post_action_name
	 */
	function ajax_clone_post() {
		header('Content-Type: application/json');
		$post = $this->get_post_to_clone( );
		$response = array(
			'success' 			=> false,
			'error'				=> '',
			'post_id'			=> 0,
			'post_edit_uri'		=> '',
			'post_edit_link'	=> '',
			'post_status'		=> '',
		);
		
		if ( $post !== false ) {
			if ( is_wp_error( $post ) ) {	
				$response['error'] = $post;
			} else if ( $post instanceof GlottyBotPost ) {
				// do clone
				$translated_post_id = $post->clone_for_translation( $_REQUEST['target_locale'] );
				if ( is_wp_error( $translated_post_id ) ) {
					
				} else {
					$post_edit_uri = get_edit_post_link( $translated_post_id , '' );
					$response = array(
						'success' 			=> true,
						'post_id'			=> $new_post->ID,
						'post_edit_uri'		=> $post_edit_uri,
						'post_edit_link'	=> sprintf( '<a href="%s">%s</a>' , $post_edit_uri , $new_post->post_title ),
						'post_status'		=> $new_post->post_status,
					);
				}
			}
		}
		echo json_encode( $response );
		die;
	}
	
	
	
	/**
	 *	@filter 'wp_insert_post_data'
	 *	@filter 'wp_insert_attachment_data'
	 */
	function filter_insert_post_data( $data , $postarr ) {
		if ( isset( $postarr['post_locale'] ) && ! isset( $data['post_locale'] ) )
			$data['post_locale'] = $postarr['post_locale'];
		else if ( ! isset( $data['post_locale'] ) )
			$data['post_locale'] = GlottyBotAdmin()->get_locale(); // get admin language!
		
		if ( isset( $postarr['post_translation_group'] ) && ! isset( $data['post_translation_group'] ) ) {
			$data['post_translation_group'] = $postarr['post_translation_group'];
		} else if ( ! isset( $data['post_translation_group'] ) ) {
			$data['post_translation_group'] = 0; // get admin language!
			add_action( 'add_attachment' , array( &$this , 'set_post_translation_group' ) ); // $post_ID alw
			add_action( 'wp_insert_post' , array( &$this , 'set_post_translation_group' ) , 10 , 3 ); // $post_ID alw
		}
		return $data;
	}
	
	/**
	 *	@action 'add_attachment'
	 *	@action 'wp_insert_post'
	 */
	function set_post_translation_group( $post_ID , $post = null , $update = false ) {
		global $wpdb;
		if ( ! $update ) {
			$wpdb->update( $wpdb->posts , array( 'post_translation_group' => $post_ID ) , array( 'ID' => $post_ID ) );
		}
	}
	

	/**
	 *	@action 'load-post.php'
	 *	@action 'load-post-new.php'
	 */
	function enqueue_script_style() {
		wp_enqueue_script( 'glottybot-editpost' );
		wp_enqueue_style( 'glottybot-editpost' );
	}
	
	// --------------------------------------------------
	// add meta boxes to all post content
	// --------------------------------------------------

	/**
	 *	@action 'add_meta_boxes'
	 */
	function add_meta_boxes( $post_type , $post ) {
		global $wp_post_types;
// 		if ( $post->post_status == 'auto-draft' ) 
// 			return;
		foreach ( array_keys($wp_post_types) as $post_type ) {
			$post_type_object = get_post_type_object( $post_type );
			if ( $post_type_object->public )
				add_meta_box( 'glottybot-post-language' , __('Multilingual','wp-glottybot') , array(&$this,'language_metabox') , $post_type , 'side' , 'high' );
		}
	}

	// --------------------------------------------------
	// edit post - the meta box
	// --------------------------------------------------
	/**
	 *	@callback_arg add_meta_box()
	 */
	function language_metabox() {
		global $wp_roles;
		$post 				= GlottyBotPost( get_the_ID() );
		$post_type_object 	= get_post_type_object($post->post_type);
		$translations		= $post->get_translations();

		$system_langs		= GlottyBot()->get_locale_names();
		$locale = GlottyBotLocales::get_locale_names( $post->post_locale );
		
		?><div class="post_locale misc-pub-section"><?php
			?><strong><?php _e( 'Language:' , 'wp-glottybot' ); ?> </strong><?php
			echo GlottyBotTemplate::i18n_item( $post->post_locale );
			echo $locale[$post->post_locale];
			
		?></div><?php
		// show translations here.
		if ( $post->post_status != 'auto-draft' ) {
			$translatable_langs	= $system_langs;
			unset( $translatable_langs[ $post->post_locale ] );

			if ( $translatable_langs ) {
				?><div class="add-post_locales misc-pub-section"><?php
				
					?><h4><?php _e('Translations:','wp-glottybot') ?></h4><?php
					?><table><?php
						foreach ( $translatable_langs as $locale => $language_name ) {
							?><tr><td><?php
							if ( isset( $translations[$locale] ) && $translations[$locale] ) {
								// edit translation
								// icons: @private dashicons-lock | @trash dashicons-trash | @public dashicons-edit | @pending dashicons-backup | @draft dashicons-hammer

								$edit_post_uri = get_edit_post_link( $translations[$locale]->ID );
								switch ( $translations[$locale]->post_status ) {
									case 'private':
										$dashicon = 'lock';
										$title = __('Privately Published');
										break;
									case 'trash':
										$dashicon = 'trash';
										$title = __('Trashed');
										// untrash action
										break;
									case 'future':
										$dashicon = 'backup';
										$title = __('Pending');
										break;
									case 'draft':
										$dashicon = 'hammer';
										$title = __('Draft');
										break;
									case 'pending':
										$dashicon = 'clock';
										$title = __('Pending Review');
										break;
									case 'publish':
									default:
										$dashicon = 'edit';
										$title = __('Edit');
										break;
								}
								$edit_post_link = sprintf( '<a class="lang-action edit translated" href="%s">%s<span title="%s" class="dashicons dashicons-%s"></span></a>' , 
									$edit_post_uri , 
// 										$translations[$locale]->post_title,
									GlottyBotTemplate::i18n_item( $locale ),
									$title , 
									$dashicon 
								);
								?><?php echo $edit_post_link ?><?php
//									echo $translations[$locale]->post_title;
							echo edit_post_link( $translations[$locale]->post_title , null , null, $translations[$locale]->ID ); 
						} else {
							// clone
							$nonce_name = sprintf('glottybot_copy_post-%s-%d' , $locale , $post->ID );
// 									$this->get_clone_link( $post->ID , $locale );
							
							?><span class="spinner"></span>
							<button class="lang-action add untranslated copy-post" 
								data-ajax-nonce="<?php echo wp_create_nonce( $nonce_name ) ?>" 
								data-post-language="<?php echo $lang ?>" 
								data-source-language="<?php echo $post->post_locale ?>" 
								data-post-id="<?php echo $post->ID ?>" 
								name="copy-to-language" 
								value="<?php echo $lang ?>"><?php 

//								printf( _x('Copy this %s','%s post type','wp-glottybot') , $post_type_object->labels->singular_name );
								echo GlottyBotTemplate::i18n_item( $locale );
							
							?>
							<span class="dashicons dashicons-plus"></span> 
							</button><?php

								printf( _x('Copy this %s','%s post type','wp-glottybot') , $post_type_object->labels->singular_name );
						}


/*
<a class="lang-action add untranslated" href="http://shan-fan.local/wp-admin/edit.php?action=glottybot_copy_post&amp;post_id=1&amp;post_locale=de_DE&amp;ajax_nonce=1bd5efa326" title="Add new translation">
<span class="i18n-item" data-language="de" data-country="DE"></span>

</a>
*/
						?></td></tr><?php
					}
					?></table><?php
				?></div><?php
			}
		}
	}
	
/*
ajax:
	check nonce, check caps, clone.
*/
	
	
	/**
	 * 	Custom columns
	 *	@action 'load-edit.php'
	 *	@action 'load-upload.php'
	 */
	function add_post_type_columns() {
		$current_post_type = isset( $_REQUEST['post_type'] ) ? $_REQUEST['post_type'] : ( GlottyBotAdmin()->is_admin_page( 'upload.php' ) ? 'attachment' : 'post' );
		switch ( $current_post_type ) {
			case 'post':
				// posts
				add_filter('manage_posts_columns' , array( &$this , 'add_language_column') );
				add_action('manage_posts_custom_column' , array( &$this , 'manage_language_column') , 10 ,2 );
				break;
			case 'page':
				add_filter('manage_pages_columns' , array( &$this , 'add_language_column') );
				add_action('manage_pages_custom_column' , array( &$this , 'manage_language_column') , 10 ,2 );
				break;
			case 'attachment':
				add_filter('manage_media_columns' , array( &$this , 'add_language_column') );
				add_action('manage_media_custom_column' , array( &$this , 'manage_language_column') , 10 ,2 );
				break;
			default:
				if ( GlottyBot()->is_post_type_translatable( $current_post_type ) ) {
					add_filter( "manage_{$current_post_type}_posts_columns" , array( &$this , 'add_language_column'));
					add_filter( "nav_menu_items_{$current_post_type}", array(&$this,'nav_menu_items_posts') , 10 , 3 );
				}
		}
		if ( GlottyBot()->is_post_type_translatable( $current_post_type ) ) {
			add_filter( "nav_menu_items_post", array(&$this,'nav_menu_items_posts') , 10 , 3 );
			add_filter( "nav_menu_items_page", array(&$this,'nav_menu_items_posts') , 10 , 3 );
			add_filter( 'get_edit_post_link' , array(&$this,'edit_post_link') , 10 , 3 );
			add_filter( 'post_class', array(&$this , 'post_class' ) , 10 , 3 );
		}
	}
	
	/**
	 *	@filter 'post_class'
	 */
	function post_class( $classes, $class, $post_ID ) {
		$post = GlottyBotPost($post_ID);
		if ( ! $translated_post = $post->get_translation( GlottyBotAdmin()->get_locale() ) ) {
			$classes[] = 'untranslated';
		}
		return $classes;
	}
	
	/**
	 *	@filter 'the_title'
	 */
	function edit_post_link( $link , $post_ID , $context ) {
		$locale = GlottyBotAdmin()->get_locale();
		$post = GlottyBotPost( $post_ID );
		if ( $post->post_locale == $locale ) {
			return $link;
		} else if ( $translated_post = $post->get_translation( $locale ) ) {
			return get_edit_post_link( $translated_post->ID );
		} else {
			return $this->clone_post_url( $post_ID , GlottyBotAdmin()->get_locale() ); 
		}
		return $link;
	}
	/**
	 *	@filter 'the_title'
	 */
	function post_title( $title , $post_ID ) {
		$post = GlottyBotPost($post_ID);
		if ( $translated_post = $post->get_translation( GlottyBotAdmin()->get_locale() ) ) {
			return $title;
		} else {
			return sprintf( __( 'Clone "%s"' , 'wp-glottybot' ) , $title );
		}
	}
	
	/**
	 *	@filter 'post_row_actions'
	 *	@filter 'page_row_actions'
	 */
	function row_actions( $actions , $post ) {
		$post = GlottyBotPost( $post );
		if ( ! $post->get_translation( GlottyBotAdmin()->get_locale() ) ) {
			$edit_post_uri = $this->clone_post_url( $post->ID , GlottyBotAdmin()->get_locale() ); 
			$edit_post_uri = add_query_arg( 'language' , $post->post_locale , $edit_post_uri );
			$edit_post_link = sprintf( '<a href="%s">%s</a>' , 
				$edit_post_uri , 
				sprintf( __('Clone Post to %s','wp-glottybot') , GlottyBotLocales::get_locale_name( GlottyBotAdmin()->get_locale() )  )
			);

			$actions = array(
				'edit' => $edit_post_link,
			);
			
		}
		return $actions;
	}
	
	function nav_menu_items_posts( $posts, $args, $post_type ) {
		$args['suppress_filters'] = false;
		
		$get_posts = new WP_Query;
		$new_posts = $get_posts->query( $args );
		
		if ( 'page' == $post_type ) {
			$front_page = 'page' == get_option('show_on_front') ? (int) get_option( 'page_on_front' ) : 0;
			if ( ! empty( $front_page ) ) {
				$front_page_obj = get_post( $front_page );
				$front_page_obj->front_or_home = true;
				array_unshift( $new_posts, $front_page_obj );
			} else {
				$_nav_menu_placeholder = ( 0 > $_nav_menu_placeholder ) ? intval($_nav_menu_placeholder) - 1 : -1;
				array_unshift( $new_posts, (object) array(
					'front_or_home' => true,
					'ID' => 0,
					'object_id' => $_nav_menu_placeholder,
					'post_content' => '',
					'post_excerpt' => '',
					'post_parent' => '',
					'post_title' => _x('Home', 'nav menu home label'),
					'post_type' => 'nav_menu_item',
					'type' => 'custom',
					'url' => home_url('/'),
				) );
			}
		}
		return $new_posts;
	}
	function add_language_column( $columns ) {
		global $post_type;
		$post_type_object = get_post_type_object( $post_type );
		
		$locales = GlottyBot()->get_locales();
		usort($locales,array( $this , '_sort_locales_current_first' ) );
		$cols = array();
		// check after which column to insert access col
		$afters = array('title','cb');
	
		foreach ( $afters as $after )
			if ( isset($columns[$after] ) )
				break;
		$column_name = 'language';
		foreach ($columns as $k=>$v) {
			$cols[$k] = $v;
			if ($k == $after ) {
				foreach ( $locales as $locale )
					$cols[self::$lang_col_prefix.$locale] = '';//GlottyBotTemplate::i18n_item( $lang );//__('Translations','wp-glottybot');
			}
		}
		$columns = $cols;
		return $columns;
	}
	
	private function _sort_locales_current_first( $a , $b ) {
		$loc = GlottyBotAdmin()->get_locale();
		if ( $a == $loc )
			return -1;
		else if ( $b == $loc )
			return 1;
		else 
			return 0;
	}
	
	function manage_language_column($column, $post_ID) {
		remove_filter( 'get_edit_post_link' , array(&$this,'edit_post_link') , 10 );
		$post = GlottyBotPost($post_ID);
		self::$lang_col_prefix = 'glottybot_translation-';

		if ( strpos( $column , self::$lang_col_prefix ) !== false ) {
			$locale = str_replace( self::$lang_col_prefix , '' , $column );
			$class = array('lang-action');
			if ( $translated_post = $post->get_translation( $locale ) ) {
				$edit_post_uri = get_edit_post_link( $translated_post->ID );
				$dashicon = 'edit';
				$edit_post_uri = add_query_arg( 'language' , $translated_post->post_locale , $edit_post_uri );
				$edit_post_title = $translated_post->post_title;
				$class[] = 'edit';
				$class[] = 'translated';
			} else {
				$edit_post_uri = $this->clone_post_url( $post_ID , $locale );
				$dashicon = 'plus';
				$edit_post_title = __('Add new translation');
				$class[] = 'add';
				$class[] = 'untranslated';
			}
			printf( '<a class="%s" href="%s" title="%s">%s <span class="dashicons dashicons-%s"></span> </a>' , 
				implode(' ',$class),
				$edit_post_uri, 
				$edit_post_title,
				GlottyBotTemplate::i18n_item( $locale ),
				$dashicon
			);
			
		}
		add_filter( 'get_edit_post_link' , array(&$this,'edit_post_link') , 10 , 3 );
	}
}
endif;
