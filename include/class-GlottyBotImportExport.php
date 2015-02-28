<?php


/**
 *	Allow import / export of translated posts
 */
if ( ! class_exists( 'GlottyBotImportExport' ) ):
class GlottyBotImportExport {
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
	 *	Prevent cloning
	 */
	private function __clone() {}

	/**
	 * Private constructor
	 */
	private function __construct() {
		add_action( 'export_wp' , array( &$this , 'prepare_export_wp' ) );
		add_filter('wp_import_post_data_processed' , array( &$this , 'prepare_import_data' ) , 10 , 2 );
		
		add_filter('import_post_meta_key' , array(&$this,'import_postmeta_keys') );
	}
	/**
	 *	Prepare Export.
	 *	Put all language information into postmeta.
	 */
	function prepare_export_wp( ) {
		global $wpdb; 
		// set all postmeta
		$post_types = get_post_types( array( 'can_export' => true ) );
		$esses = array_fill( 0, count($post_types), '%s' );
		$where = $wpdb->prepare( "WHERE post_type IN (" . implode( ',', $esses ) . ')', $post_types );
		$where .= " AND post_status != 'auto-draft';";
		$query = "SELECT ID , post_language , post_translation_group FROM $wpdb->posts $where";
		$posts = $wpdb->get_results($query);
		foreach ( $posts as $post ) {
			update_post_meta( $post->ID , '_glottybot_export_post_language' , $post->post_language );
			update_post_meta( $post->ID , '_glottybot_export_post_translation_group' , $post->post_translation_group );
		}
	}
	/**
	 *	Setup postdata on Import.
	 *	Set post properties from imported postmeta.
	 *
	 *	@see wp_import plugin filer `wp_import_post_data_processed`
	 */
	function prepare_import_data( $postdata, $post ) {
		if ( $post['postmeta'] ) {
			foreach ( $post['postmeta'] as $meta ) {
				if ( $meta['key'] == '_glottybot_export_post_language' )
					$postdata['post_language'] = $meta['value'];
				if ( $meta['key'] == '_glottybot_export_post_translation_group' )
					$postdata['post_translation_group'] = $meta['value'];
			}
		}
		return $postdata;
	}
	/**
	 *	Prevent language postmeta from being imported 
	 *	We alread have these informations through prepare_import_data().
	 *
	 *	@see wp_import plugin filer `import_post_meta_key`
	 */
	function import_postmeta_keys( $key ) {
		if ( in_array( $key , array('_glottybot_export_post_language','_glottybot_export_post_translation_group') ) )
			return false;
		return $key;
	}
}

endif;