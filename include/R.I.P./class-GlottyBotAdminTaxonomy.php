<?php


/**
 *	Edit Taxonomy translations
 */
if ( ! class_exists( 'GlottyBotAdminTaxonomy' ) ):
class GlottyBotAdminTaxonomy extends GlottyBotAdminPomo {

	private static $_instance = null;

	protected $textdomain_prefix = 'taxonomy';

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
		if ( wp_is_writable(WP_LANG_DIR) )
			foreach ( get_taxonomies( array( 'public' => true ) , 'names' ) as $taxonomy )
				add_action( "after-{$taxonomy}-table", array( &$this , 'show_taxo_translate_link' ) );
		
		add_action( 'load-admin.php' , array( &$this , 'admin_translate_taxonomy' ) );
		add_action( "load-edit-tags.php" , array( &$this , 'enqueue_assets' ) );
		
	}
	/**
	 *	Enqueue options Assets.
	 *	Hooks into 'load-edit-tags.php'
	 */
	function enqueue_assets() {
		wp_register_style( 'glottybot-taxonomy' , plugins_url('css/glottybot-taxonomy.css', dirname(__FILE__)) );
		wp_enqueue_style( 'glottybot-taxonomy' );
	}
	
	/**
	 *	Go to taxonomy editor UI page if necessary.
	 *	Hooks into 'load-admin.php'
	 */
	function admin_translate_taxonomy( ) {
		if ( isset( $_REQUEST['action'] ) && $_REQUEST['action'] == 'translate-taxonomy' ) {
			if ( isset( $_REQUEST['taxonomy'] , $_REQUEST['target_language'] ) ) {
				$taxonomy = $_REQUEST['taxonomy'];
				$target_language = glottybot_sanitize_language_code( $_REQUEST['target_language'] , '_' , true );
				
				if ( taxonomy_exists($taxonomy) && $target_language ) {
					$nonce_name = "translate-taxonomy-$taxonomy";
					check_admin_referer( $nonce_name );
					
					$taxonomy_object = get_taxonomy($taxonomy);
					if ( ! current_user_can( $taxonomy_object->cap->manage_terms ) )
						wp_die( 'Insufficient Privileges' );
					
					$this->translate_taxonomy( $taxonomy , $target_language );
				}
			}
		}
	}

	
	/**
	 *	Show translate-taxonomy links.
	 *	Hooks into 'after-{$taxonomy}-table'
	 */
	function show_taxo_translate_link( $taxonomy ) {
		$languages = glottybot_language_code_sep( get_option( 'glottybot_additional_languages' ) , '_' );

		?><div id="glottybot-translate-links" class="postbox col-wrap"><?php
		?><div class="inside"><?php
		?><h3><?php _e( 'Multilingual' , 'wp-glottybot' ); ?></h3><?php

		if ( $languages ) {
			foreach ( $languages as $language_code ) {
				$nonce_name = "translate-taxonomy-$taxonomy";
				$href = add_query_arg(array(
					'taxonomy' => $taxonomy,
					'action' => 'translate-taxonomy', 
					'target_language' => $language_code,
					'_wpnonce' => wp_create_nonce( $nonce_name ),
				),admin_url('admin.php'));
				
				$langname = glottybot_get_language_name( $language_code );
				if ( $this->has_po( $taxonomy , $language_code ) ) {
					$label = sprintf(_x('Edit %s Translation' , 'language' , 'wp-glottybot' ), $langname );
					printf( '<a href="%s" class="button button-primary">%s</a>' , $href , $label );
				} else {
					$label = sprintf(_x('Translate to %s' , 'language' , 'wp-glottybot' ), $langname );
					printf( '<a href="%s" class="button button-secondary">%s</a>' , $href , $label );
				}
			}
		}
		?></div><?php
		?></div><?php
		?><script type="text/javascript">
		(function($){
			$('#glottybot-translate-links').insertBefore('#col-container');
		})(jQuery);
		</script><?php
	}
	
	/**
	 *	Prepare taxonomy translation (by creating a pot file) and redirect to editor.
	 *
	 *	@param $taxonomy mixed taxonomy object or slug
	 *	@param $language string target language
	 */
	function translate_taxonomy( $taxonomy , $language ) {
		// make sure we have the taxonomy object
		if ( ! is_object( $taxonomy ) )
			$taxonomy = get_taxonomy( $taxonomy );
		
		// (re-)create pot file
		$this->create_pot( $taxonomy );
		
		// redirect to editor url.
		$redirect = $this->get_po_edit_url( $taxonomy->name , $language );
		wp_redirect($redirect);
	}
	
	/**
	 *	Create a pot file from taxonomy
	 *
	 *	@param $taxonomy object taxonomy object
	 */
	function create_pot( $taxonomy ) {
		global $current_user;
		get_currentuserinfo();
		
		if ( ! wp_is_writable(WP_LANG_DIR) )
			return;
		
		if ( ! is_object( $taxonomy ) )
			$taxonomy = get_taxonomy( $taxonomy );
		
		$save_pot_file = $this->get_pot_file_path( $taxonomy->name );

		if ( ! wp_mkdir_p( dirname( $save_pot_file ) ) )
			return false;
		
		$terms = get_terms( $taxonomy->name , array(
			'hide_empty' => false,
			'child_of' => 0,
		));
		
		$po = $this->get_po( );
		$po->set_header('Project-Id-Version' , "Taxonomy {$taxonomy->name}");
		
		foreach ( $terms as $term ) {
			$entry = new Translation_Entry(array(
				'singular' => $this->prepare_string( $term->name ),
				'translator_comments' => sprintf('Term %s (%d) Name' , $term->slug , $term->term_id )
			));
			$po->add_entry( $entry );
			
// 			'\x0A' : NO
// 			'\x0D' : YES!
			
			$entry = new Translation_Entry(array(
				'singular' => $this->prepare_string($term->description ),
				'translator_comments' => sprintf('Term %s (%d) Description' , $term->slug , $term->term_id )
			));
			$po->add_entry( $entry );
		}
		$po->export_to_file( $save_pot_file );
		//*/
	}

}

endif;