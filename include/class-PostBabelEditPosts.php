<?php
/**
* @package WP_AccessAreas
* @version 1.0.0
*/ 

// ----------------------------------------
//	This class provides an UI for assining 
//	WP-Roles and user-labels to posts.
// ----------------------------------------

if ( ! class_exists('PostBabelEditPosts') ) :
class PostBabelEditPosts {
	private static $_instance = null;
	
	private $optionset = 'post_babel_options'; // writing | reading | discussion | media | permalink

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
			add_action( 'wp_ajax_postbabel_copy_post', array( &$this , 'ajax_copy_post' ) );
// 			
			add_action( 'admin_init' , array( &$this , 'admin_register_scripts' ) );
			add_action( 'admin_init' , array( &$this , 'add_post_type_columns' ) );
		}
// 		add_action( 'load-edit.php' , array( __CLASS__ , 'enqueue_script_style' ) );
// 		add_action( 'load-edit.php' , array( __CLASS__ , 'enqueue_style' ) );
// 		add_action( 'load-upload.php' , array( __CLASS__ , 'enqueue_style' ) );
// 		
		add_action( 'load-post.php' , array( &$this , 'enqueue_script_style' ) );
		add_action( 'load-post-new.php' , array( &$this , 'enqueue_script_style' ) );
		
		add_filter( 'wp_insert_post_data', array( &$this , 'filter_insert_post_data' ) , 10 , 2 );
		add_filter( 'wp_insert_attachment_data', array( &$this , 'filter_insert_post_data' ), 10 , 2 );
	}
	
	function admin_register_scripts() {
		wp_register_style( 'postbabel-editpost' , plugins_url('css/post_babel-editpost.css', dirname(__FILE__)) );
		wp_register_script( 'postbabel-editpost' , plugins_url('js/post_babel-editpost.js', dirname(__FILE__)) , array( 'jquery' ) );
	}
	
	static function add_post_type_columns() {
		// posts
		add_filter('manage_posts_columns' , array( __CLASS__ , 'add_language_column') );
		// posts and CPT
		add_action('manage_posts_custom_column' , array( __CLASS__ , 'manage_language_column') , 10 ,2 );
		
		// page
		add_filter('manage_pages_columns' , array( __CLASS__ , 'add_language_column') );
		add_action('manage_pages_custom_column' , array( __CLASS__ , 'manage_language_column') , 10 ,2 );
		
		// media
		add_filter('manage_media_columns' , array( __CLASS__ , 'add_language_column') );
		add_action('manage_media_custom_column' , array( __CLASS__ , 'manage_language_column') , 10 ,2 );
		
		// CPT
		$post_types = get_post_types(array(
			'show_ui' => true,
			'_builtin' => false,
		));
		
		foreach ( $post_types as $post_type ) {
			add_filter( "manage_{$post_type}_posts_columns" , array( __CLASS__ , 'add_language_column'));
		}
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
			$nonce_name = sprintf('postbabel_copy_post-%s-%d' , $_POST['post_language'] , $_POST['post_id'] );
			if ( isset( $_POST['ajax_nonce'] ) && wp_verify_nonce( $_POST['ajax_nonce'], $nonce_name ) ) {
				if ( current_user_can( 'edit_post' , $_POST['post_id'] ) ) {
					$new_post_id = $this->clone_post_for_translation( intval($_POST['post_id']) , postbabel_sanitize_language_code( $_POST['post_language'] ) ) ;
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
	
	function clone_post_for_translation( $post_id , $target_language ) {
		if ( $post = get_post($post_id) ) {
			if ( ! $translated_post = postbabel_get_translated_post( $post , $target_language ) ) {
				$new_post_parent = postbabel_get_translated_post($master_post->post_parent , $target_language );
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
			
			$postarr = apply_filters( 'postbabel_post_clone_data' , $postarr , $post );
			
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
						if ( $thumb_translation = postbabel_get_translated_post( $post_thumbnail_id , $postarr['post_language'] ) ) {
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
				do_action( 'postbabel_post_cloned' , $post , $new_post );
			}
			return $new_post_id;
		}
	
	}
	
	function enqueue_script_style() {
		wp_enqueue_script( 'postbabel-editpost' );
		wp_enqueue_style( 'postbabel-editpost' );
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
				add_meta_box( 'post-wpaa-behavior' , __('Multilingual','wp-post-babel') , array(__CLASS__,'language_metabox') , $post_type , 'side' , 'high' );
		}
	}
	// --------------------------------------------------
	// saving posts
	// --------------------------------------------------
// 	function edit_post( $data, $postarr ) {
// 	
// 		$post_type = $data["post_type"];
// 		$post_type_object 	= get_post_type_object( $post_type );
// 		
// 		// set default values
// 		if ( ! $postarr['ID'] ) {
// 			$translation = array( 
// 				'post_language' => get_bloginfo('languauge'),
// 				'post_translation_group' => isset($_REQUEST['translate_from']) ? intval( $_REQUEST['translate_from'] ) : 0,
// 			);
// 			$data = wp_parse_args( $data , $caps );
// 		}
// 		if ( $data['post_status'] == 'auto-draft' )
// 			return $data;
// 		
// 		// process user input. 
// 		if ( isset($_REQUEST['post_language']) )
// 			$data['post_language'] = postbabel_sanitize_language_code( $_REQUEST['post_language'] );
// 		
// 		return $data;
// 	}
// 	
	
	// --------------------------------------------------
	// edit post - the meta box
	// --------------------------------------------------
	static function language_metabox() {
		global $wp_roles;
		$post 				= get_post(get_the_ID());
		$master_post 		= postbabel_get_master_post( $post );
		$post_type_object 	= get_post_type_object($post->post_type);
		$translations		= postbabel_get_translated_posts($post);

		$system_langs		= postbabel_available_languages();
		$system_langs		= postbabel_language_code_sep($system_langs, '-' );
		
		?><div class="set-post_language misc-pub-section"><?php
			?><label for="post_language"><strong><?php _e( 'Language:' , 'wp-post-babel') ?></strong></label><br /><?php
			postbabel_dropdown_languages( array(
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
				
				?><thead><caption><?php _e('Translations:','wp-post-babel') ?></caption></thead><?php
				foreach ( $additional_langs as $lang ) {
					?><tr data-language="<?php echo $lang ?>"><?php
						?><th><?php 
							echo postbabel_get_language_name( $lang )
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
								$nonce_name = sprintf('postbabel_copy_post-%s-%d' , $lang , $post->ID );

								?><span class="spinner"></span><button class="button-secondary copy-post" data-ajax-nonce="<?php echo wp_create_nonce( $nonce_name ) ?>" data-post-language="<?php echo $lang ?>" data-post-id="<?php echo $post->ID ?>" name="copy-to-language" value="<?php echo $lang ?>"><?php 
									printf( _x('Copy this %s','%s post type','wp-post-babel') , $post_type_object->labels->singular_name );
								?></button><?php
							}
						?></td><?php
					?></tr><?php
				}
				?></table><?php
			?></div><?php
		}
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
				$cols[$column_name] = __('Language','wp-post-babel');
				// add lang columns
			}
		}
		$columns = $cols;
		return $columns;
	}
	function manage_language_column($column, $post_ID) {
		$post = get_post($post_ID);
		if ( 'language' == $column ) {
			echo postbabel_get_language_name( $post->post_language );
		}
	}
}
endif;
