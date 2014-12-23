<?php


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
	 * Enqueue options Assets
	 */
	function enqueue_assets() {
		wp_register_style( 'glottybot-taxonomy' , plugins_url('css/glottybot-taxonomy.css', dirname(__FILE__)) );
		wp_enqueue_style( 'glottybot-taxonomy' );
	}
	
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
	
	function translate_taxonomy( $taxonomy , $language ) {
		if ( ! is_object( $taxonomy ) )
			$taxonomy = get_taxonomy( $taxonomy );
		
		$language = glottybot_language_code_sep( $language , '_' );

		$textdomain = $this->get_textdomain( $taxonomy->name );
		$plugin_name = basename(dirname(dirname(__FILE__))).'/index.php';
		$this->create_pot_from_taxonomy( $taxonomy );
// 		if ( ! $this->has_po( $textdomain , $language ) )
// 			$this->init_po( $textdomain , $language );
		if ( ! $this->has_po( $taxonomy->name , $language ) ) {
			$redirect = admin_url( 'admin.php' );
			$redirect = add_query_arg( array(
				'page' => 'loco-translate',
				'custom-locale' => $language,
				'name' => $textdomain,
				'msginit' => $textdomain,
				'type' => 'core',
			) , $redirect );
			/*
√			page=loco-translate
			&msginit=$this->textdomain_prefix / $textdomain
			&name=say-cheese%2Fsay-cheese.php
			&type=core
#			&common-locale=$language
√			&custom-locale=$language
			&gforce=1
			*/
		} else {
			$redirect = admin_url( 'admin.php' );
			$redirect = add_query_arg( array(
				'page' => 'loco-translate',
				'poedit' => $this->get_po_file_path( $taxonomy->name , $language , "languages" ),
				'name' => $textdomain,
				'type' => 'core',
			) , $redirect );
			/*
			name=say-cheese/say-cheese.php
			&type=plugin
			&poedit=languages/plugins/taxonomy/cheese-fr_FR.po
			&page=loco-translate
			*/
		}
		wp_redirect($redirect);
		
		// Loco create: admin.php?page=loco-translate&msginit=taxo-{$taxonomie}&name=taxo-{$taxonomie}&type=core&custom-locale={$language}
		// Loco Edit: http://wordpress-trunk.local/wp-admin/admin.php?page=loco-translate&poedit=languages/taxo-{$taxonomie}-{$language}.po&name=taxo-{$taxonomie}&type=core
	}
	
	function create_pot_from_taxonomy( $taxonomy ) {
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
		
		$header_template = 'msgid ""
msgstr ""
"Project-Id-Version: Taxonomy %taxonomy_name%\n"
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
		$entry_template = '# Term %d Slug: %s %s 
msgid "%s"
msgstr ""

';
		$template_vars = array( 
			'%taxonomy_name%' => $taxonomy->labels->name , 
			'%current_user%' => sprintf( '%s <%s>' , $current_user->display_name, $current_user->user_email ),
		);
		$pot = strtr( $header_template , $template_vars );
		
		foreach ( $terms as $term ) {
			$name = $this->wrap_multiline_messages( trim( $term->name) );
			$desc = $this->wrap_multiline_messages( trim( $term->description) );

			$pot .= sprintf( $entry_template , $term->term_id , $term->slug , 'Name' , $name );
			if ( ! empty( $desc ) )
				$pot .= sprintf( $entry_template , $term->term_id , $term->slug , 'Description' , $desc );
		}
		
		file_put_contents( $save_pot_file , $pot );
	}
	function init_po( $textdomain , $language ) {
		$pot_file = $this->get_pot_file_path( $textdomain );
		$po_file = $this->get_po_file_path( $textdomain , $language );
		copy($pot_file,$po_file);
		
	}
}

endif;