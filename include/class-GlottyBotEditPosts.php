<?php
/**
* @package WP_AccessAreas
* @version 1.0.0
*/ 

// ----------------------------------------
//	This class provides an UI for assining 
//	WP-Roles and user-labels to posts.
// ----------------------------------------

if ( ! class_exists('GlottyBotEditPosts') ) :
class GlottyBotEditPosts {
	private static $_instance = null;
	
	private $optionset = 'glottybot_options'; // writing | reading | discussion | media | permalink

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
	 * Private constructor
	 */
	private function __construct() {
		if ( is_admin() ) {
			// edit post
// 			add_filter('wp_insert_post_data', array(&$this , 'edit_post') , 10 , 2 );
// 			add_filter('save_post', array(__CLASS__ , 'set_post_behavior') , 10 , 3 );
// 			add_action('edit_attachment',array(__CLASS__ , 'edit_attachment') );
// 			add_action('add_attachment',array(__CLASS__ , 'edit_attachment') );
// 			
			add_action('add_meta_boxes' , array( &$this , 'add_meta_boxes' ) , 10 , 2 );
// 
// 			add_action('bulk_edit_custom_box' , array(__CLASS__,'bulk_edit_fields') , 10 , 2 );
// 			add_action('quick_edit_custom_box' , array(__CLASS__,'quick_edit_fields') , 10 , 2 );
// 
			add_action( 'wp_ajax_glottybot_copy_post', array( &$this , 'ajax_copy_post' ) );
// 			
			add_action( 'admin_init' , array( &$this , 'admin_register_scripts' ) );
			add_action( 'admin_init' , array( &$this , 'add_post_type_columns' ) );
		}
		add_action( 'load-edit.php' , array( &$this , 'check_clone_post' ) );
// 		add_action( 'load-edit.php' , array( __CLASS__ , 'enqueue_style' ) );
// 		add_action( 'load-upload.php' , array( __CLASS__ , 'enqueue_style' ) );
// 		
		add_action( 'load-post.php' , array( &$this , 'enqueue_script_style' ) );
		add_action( 'load-post-new.php' , array( &$this , 'enqueue_script_style' ) );
		
		add_filter( 'wp_insert_post_data', array( &$this , 'filter_insert_post_data' ) , 10 , 2 );
		add_filter( 'wp_insert_attachment_data', array( &$this , 'filter_insert_post_data' ), 10 , 2 );
		
		add_action( 'page_row_actions' , array( &$this , 'row_actions' ) , 10 , 2 );
		add_action( 'post_row_actions' , array( &$this , 'row_actions' ) , 10 , 2 );
	}
	function row_actions( $actions , $post ) {
		if ( $post->post_language != glottybot_current_language() ) {
			$edit_post_uri = glottybot_get_clone_post_link( $post->ID , glottybot_current_language() );
			$edit_post_uri = add_query_arg( 'language' , $post->post_language , $edit_post_uri );
			$edit_post_link = sprintf( '<a href="%s">%s</a>' , 
				$edit_post_uri , 
				sprintf( __('Clone Post to %s','wp-glottybot') , glottybot_get_language_name(glottybot_current_language() ) )
			);

			$actions = array(
				'edit' => $edit_post_link,
			);
			
		}
		return $actions;
	}
	function check_clone_post() {
		if ( isset( $_REQUEST['action'] ) && $_REQUEST['action'] == 'glottybot_copy_post' ) {
			if ( isset( $_REQUEST['post_language'] , $_REQUEST['post_id'] ) ) {
				$post_id = intval($_REQUEST['post_id']);
				$language = glottybot_sanitize_language_code( $_REQUEST['post_language'] );
				$nonce_name = sprintf('glottybot_copy_post-%s-%d' , $language , $post_id );
				
				check_admin_referer( $nonce_name, 'ajax_nonce' );

				if ( current_user_can( 'edit_post' , $_REQUEST['post_id'] ) ) {
					if ( ! $translated_post = glottybot_get_translated_post( $post_id , $language ) ) {
						$translated_post_id = $this->clone_post_for_translation( $post_id , $language ) ;
						$translated_post = get_post( $translated_post_id );
					} else {
						// translation exists
						$translated_post->id;
					}
					$redirect = get_edit_post_link( $translated_post_id );
					$redirect = add_query_arg( 'language' , $translated_post->post_language , $redirect );
					wp_redirect( $redirect );
					exit();
				} else {
					// 
					wp_die('Insuficient permission');
				}
			} else {
				// bad request
				wp_die('Bad Request.');
			}
		}
	}
	function admin_register_scripts() {
		wp_register_style( 'glottybot-editpost' , plugins_url('css/glottybot-editpost.css', dirname(__FILE__)) );
		wp_register_script( 'glottybot-editpost' , plugins_url('js/glottybot-editpost.js', dirname(__FILE__)) , array( 'jquery' ) );
	}
	
	
	
	function filter_insert_post_data( $data , $postarr ) {
		if ( isset( $postarr['post_language'] ) && ! isset( $data['post_language'] ) )
			$data['post_language'] = $postarr['post_language'];
		else if ( ! isset( $data['post_language'] ) )
			$data['post_language'] = get_bloginfo('language'); // get admin language!
		
		if ( isset( $postarr['post_translation_group'] ) && ! isset( $data['post_translation_group'] ) ) {
			$data['post_translation_group'] = $postarr['post_translation_group'];
		} else if ( ! isset( $data['post_translation_group'] ) ) {
			$data['post_translation_group'] = 0; // get admin language!
			add_action( 'add_attachment' , array( &$this , 'set_post_translation_group' ) ); // $post_ID alw
			add_action( 'wp_insert_post' , array( &$this , 'set_post_translation_group' ) , 10 , 3 ); // $post_ID alw
		}
		
		return $data;
	}
	
	function set_post_translation_group( $post_ID , $post = null , $update = false ) {
		global $wpdb;
		if ( ! $update ) {
			$wpdb->update( $wpdb->posts , array( 'post_translation_group' => $post_ID ) , array( 'ID' => $post_ID ) );
		}
	}
	
	function ajax_copy_post() {
		header('Content-Type: application/json');
		$response = false;
		if ( isset( $_POST['post_language'] , $_POST['post_id'] ) ) {
			$nonce_name = sprintf('glottybot_copy_post-%s-%d' , $_POST['post_language'] , $_POST['post_id'] );
			if ( isset( $_POST['ajax_nonce'] ) && wp_verify_nonce( $_POST['ajax_nonce'], $nonce_name ) ) {
				if ( current_user_can( 'edit_post' , $_POST['post_id'] ) ) {
					$new_post_id = $this->clone_post_for_translation( intval($_POST['post_id']) , glottybot_sanitize_language_code( $_POST['post_language'] ) ) ;
					if ( ! is_wp_error( $new_post_id ) ) {
						$new_post = get_post( $new_post_id );
						$post_edit_uri = get_edit_post_link( $new_post->ID , '' );
						$post_edit_uri = add_query_arg( 'language' , $new_post->post_language , $post_edit_uri );
						$response = array(
							'success' 			=> true,
							'post_id'			=> $new_post->ID,
							'post_edit_uri'		=> $post_edit_uri,
							'post_edit_link'	=> sprintf( '<a href="%s">%s</a>' , $post_edit_uri , $new_post->post_title ),
							'post_status'		=> $new_post->post_status,
						);
						
					} else {
						$response = array(
							'success' => false,
							'message' => __( 'Error creating post: ' . $new_post->get_error_message() ),
						);
					}
				} else {
					$response = array(
						'success' => false,
						'message' => __("Insufficient permission"),
					);
				}
			} else {
				$response = array(
					'success' => false,
					'message' => __("Nonce didn't verify"),
				);
			}
		} else {
			$response = array(
				'success' => false,
				'message' => __("Incorrect data"),
			);
		}
		echo json_encode( $response );
		die;
	}
	
	function clone_post_for_translation( $post_id , $target_language , $source_language = null ) {
		if ( $post = get_post($post_id) ) {
			if ( ! is_null($source_language) && $post->post_language != $source_language )
				if ( $source_post = glottybot_get_translated_post( $post , $source_language ) ) 
					$post = $source_post;
			
			if ( ! $translated_post = glottybot_get_translated_post( $post , $target_language ) ) {
				$new_post_parent = glottybot_get_translated_post($master_post->post_parent , $target_language );
				/*
					post_content > lookup translations
					post_title > lookup translations
					post_excerpt > lookup translations
				*/
				$post_status = $master_post->post_status;
				if ( in_array( $post_status , array( 'future' , 'public' ) ) )
					$post_status = 'draft';
				
				$diffs = array(
					'post_language' => $target_language,
					'post_status' => $post_status, // bring to options.
					'post_parent' => $new_post_parent ? $new_post_parent : $post->post_parent,
					'comment_count' => 0,
				);
				return $this->clone_post($post_id,$diffs);
			} else {
				// translation exists
				return new WP_Error( '' , sprintf( 'Translation exists at ID: ',$translated_post->ID ) );
			}
		} else {
			// no such agency
			return new WP_Error( '' , sprintf('No such post: %s' , $post_id ) );
		}
		
	}
	function clone_post( $post_id , $differences = array() ) {
		if ( $post = get_post($post_id) ) {
			$postarr = get_object_vars( $post );
			unset($postarr['ID']);
			// copy taxonomies
			$postarr['tax_input'] = array();
			if ( $taxonomies = get_post_taxonomies( $post ) ) {
				foreach ($taxonomies as $taxo ) 
				$postarr['tax_input'][$taxo] = wp_get_post_terms($post_id, $taxo, array("fields" => "names") );
			}
			
			$postarr = wp_parse_args( $differences , $postarr );
			
			$postarr = apply_filters( 'glottybot_post_clone_data' , $postarr , $post );
			
			$new_post_id = wp_insert_post( $postarr );
			
			if ( ! is_wp_error( $new_post_id ) ) {
				// lookup attachment children, clone them
				$attachments = get_children( array( 'post_parent' => $post->ID , 'post_type'   => 'attachment' ) );
			
				foreach ( $attachments as $attachment ) {
					$this->clone_post( $attachments->ID , array( 'post_parent' => $new_post_id , 'post_language' => $postarr['post_language'] ) );
				}
			
				// _thumbnail_id
					
				$post_thumbnail_id = get_post_meta( $post_id , '_thumbnail_id' , true );
				if ( $post_thumbnail_id ) {
					$post_thumbnail = get_post( $post_thumbnail_id );
					if ( $post_thumbnail && ( $post_thumbnail->post_language != $postarr['post_language'] ) ) {
						if ( $thumb_translation = glottybot_get_translated_post( $post_thumbnail_id , $postarr['post_language'] ) ) {
							$new_thumb_id = $thumb_translation->ID;
						} else {
							$new_thumb_id = $this->clone_post( $post_thumbnail_id , array( 'post_language' => $postarr['post_language'] ) );
						}
						update_post_meta( $new_post_id , '_thumbnail_id' , $new_thumb_id );
					}
					
				}
			
				// clone postmeta
				$ignore_meta_keys = array( '_edit_lock' , '_edit_last' , '_thumbnail_id' );
				$meta = get_post_meta( $post_id );
			
				foreach ( $meta as $meta_key => $values ) {
					if ( in_array( $meta_key , $ignore_meta_keys ) )
						continue;
					foreach ( $values as $value ) 
						add_post_meta( $new_post_id , $meta_key , $value );
				}
				$new_post = get_post( $new_post_id );
			
				// fin
				do_action( 'glottybot_post_cloned' , $post , $new_post );
			}
			return $new_post_id;
		}
	
	}
	
	function enqueue_script_style() {
		wp_enqueue_script( 'glottybot-editpost' );
		wp_enqueue_style( 'glottybot-editpost' );
	}
	
	// --------------------------------------------------
	// add meta boxes to all post content
	// --------------------------------------------------
	function add_meta_boxes( $post_type , $post ) {
		global $wp_post_types;
		if ( $post->post_status == 'auto-draft' ) 
			return;
		foreach ( array_keys($wp_post_types) as $post_type ) {
			$post_type_object = get_post_type_object( $post_type );
			if ( $post_type_object->public )
				add_meta_box( 'post-wpaa-behavior' , __('Multilingual','wp-glottybot') , array(&$this,'language_metabox') , $post_type , 'side' , 'high' );
		}
	}

	// --------------------------------------------------
	// edit post - the meta box
	// --------------------------------------------------
	function language_metabox() {
		global $wp_roles;
		$post 				= get_post(get_the_ID());
		$master_post 		= glottybot_get_master_post( $post );
		$post_type_object 	= get_post_type_object($post->post_type);
		$translations		= glottybot_get_translated_posts($post);

		$system_langs		= glottybot_available_languages();
		$system_langs		= glottybot_language_code_sep($system_langs, '-' );
		
		?><div class="set-post_language misc-pub-section"><?php
			?><label for="post_language"><strong><?php _e( 'Language:' , 'wp-glottybot') ?></strong></label><br /><?php
			glottybot_dropdown_languages( array(
				'name'      	=> 'post_language',
				'id'        	=> 'post_language',
				'selected'  	=> $post->post_language,
				'languages' 	=> $system_langs,
				'disabled'		=> array_diff(array_keys($translations),array($post->post_language)),
			) );
		?></div><?php
		// show translations here.
		$additional_langs = array_diff( $system_langs , array( $post->post_language ) );
		if ( $additional_langs ) {
			?><div class="add-post_languages misc-pub-section"><?php
				?><table class="translations-table"><?php
				
				?><thead><caption><?php _e('Translations:','wp-glottybot') ?></caption></thead><?php
				foreach ( $additional_langs as $lang ) {
					?><tr data-language="<?php echo $lang ?>"><?php
						?><th><?php 
							echo GlottyBotTemplate::i18n_item( $lang );
							echo glottybot_get_language_name( $lang )
						?></th><?php
						?><td><?php
							if ( $translations[$lang] ) {
								// icons: @private dashicons-lock | @trash dashicons-trash | @public dashicons-edit | @pending dashicons-backup | @draft dashicons-hammer
								switch ( $translations[$lang]->post_status ) {
									case 'private':
										$dashicon = 'lock';
										$title = __('Privately Published');
										break;
									case 'trash':
										$dashicon = 'trash';
										$title = __('Trashed');
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
								$edit_post_uri = get_edit_post_link( $translations[$lang]->ID );
								$edit_post_uri = add_query_arg( 'language' , $lang , $edit_post_uri );
								$edit_post_link = sprintf( '<span title="%s" class="dashicons dashicons-%s"></span><a href="%s">%s</a>' , 
									$title , 
									$dashicon , 
									$edit_post_uri , 
									$translations[$lang]->post_title
								);
								?><?php echo $edit_post_link ?><?php
							} else {
								$nonce_name = sprintf('glottybot_copy_post-%s-%d' , $lang , $post->ID );

								?><span class="spinner"></span><button class="button-secondary copy-post" data-ajax-nonce="<?php echo wp_create_nonce( $nonce_name ) ?>" data-post-language="<?php echo $lang ?>" data-source-language="<?php echo $post->post_language ?>" data-post-id="<?php echo $post->ID ?>" name="copy-to-language" value="<?php echo $lang ?>"><?php 
									printf( _x('Copy this %s','%s post type','wp-glottybot') , $post_type_object->labels->singular_name );
								?></button><?php
							}
						?></td><?php
					?></tr><?php
				}
				?></table><?php
			?></div><?php
		}
	}
	
	
	
	/**
	 * 	Custom columns
	 */
	
	function add_post_type_columns() {
		// posts
		add_filter('manage_posts_columns' , array( &$this , 'add_language_column') );
		// posts and CPT
		add_action('manage_posts_custom_column' , array( &$this , 'manage_language_column') , 10 ,2 );
		
		// page
		add_filter('manage_pages_columns' , array( &$this , 'add_language_column') );
		add_action('manage_pages_custom_column' , array( &$this , 'manage_language_column') , 10 ,2 );
		
		// media
		add_filter('manage_media_columns' , array( &$this , 'add_language_column') );
		add_action('manage_media_custom_column' , array( &$this , 'manage_language_column') , 10 ,2 );
		
		// CPT
		$post_types = get_post_types(array(
			'show_ui' => true,
			'_builtin' => false,
		));
		
		add_filter( "nav_menu_items_post", array(&$this,'nav_menu_items_posts') , 10 , 3 );
		add_filter( "nav_menu_items_page", array(&$this,'nav_menu_items_posts') , 10 , 3 );
		foreach ( $post_types as $post_type ) {
			add_filter( "manage_{$post_type}_posts_columns" , array( &$this , 'add_language_column'));
			add_filter( "nav_menu_items_{$post_type}", array(&$this,'nav_menu_items_posts') , 10 , 3 );
		}
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
		
		$cols = array();
		// check after which column to insert access col
		$afters = array('tags','categories','author','title','cb');
	
		foreach ( $afters as $after )
			if ( isset($columns[$after] ) )
				break;
		$column_name = 'language';
		foreach ($columns as $k=>$v) {
			$cols[$k] = $v;
			if ($k == $after ) {
// 				$cols['glottybot_language'] = __('Language','wp-glottybot');
				$cols['glottybot_translations'] = __('Translations','wp-glottybot');
// 				$langs = glottybot_available_languages();
// 				$langs = array_diff(glottybot_language_code_sep($langs, '-' ),array( glottybot_current_language('-') ));
// 				foreach ( $langs as $code )
// 					$cols['glottybot_translation-'.$code] = __($code,'wp-glottybot');
				// add lang columns
			}
		}
		$columns = $cols;
		return $columns;
	}
	function manage_language_column($column, $post_ID) {
		$post = get_post($post_ID);
		$lang_col_prefix = 'glottybot_translation-';
		if ( 'glottybot_language' == $column ) {
			echo GlottyBotTemplate::i18n_item( $post->post_language );
			echo glottybot_get_language_name( $post->post_language );
		} else if ( 'glottybot_translations' == $column ) {
			$lang = str_replace($lang_col_prefix,'',$column);
			$langs = glottybot_available_languages();
// 			$langs = array_diff(glottybot_language_code_sep($langs, '-' ),array( glottybot_current_language('-') ));
			foreach ( $langs as $code ) {
				if ( $translated_post = glottybot_get_translated_post( $post , $code ) ) {
					$edit_post_uri = get_edit_post_link( $translated_post->ID );
					$dashicon = 'edit';
					$edit_post_uri = add_query_arg( 'language' , $translated_post->post_language , $edit_post_uri );
					$edit_post_title = $translated_post->post_title;
				} else {
					$edit_post_uri = glottybot_get_clone_post_link( $post->ID , $code );
					$dashicon = 'welcome-add-page';
					$edit_post_title = __('Add new…');
				}
				printf( '<div><a href="%s">%s <span class="dashicons dashicons-%s"></span> %s </a></div>' , 
					$edit_post_uri,
					GlottyBotTemplate::i18n_item( $code ),
					$dashicon, 
					$edit_post_title
				);
			}
		}
	}
}
endif;
