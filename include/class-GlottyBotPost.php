<?php
/**
* @package WP_AccessAreas
* @version 1.0.0
*/ 

/**
 *	Filter Posts selection
 */
if ( ! class_exists('GlottyBotPost') ) :
class GlottyBotPost {
	private $_post;
	
	/**
	 * 	Constructor
	 *
	 *	@param $post	int or post object	WP post represented by this GlottyBotPost
	 */
	function __construct( $post ) {
		if ( is_numeric( $post ) )
			$post = get_post($post);
		$this->_post = $post;
	}
	
	/**
	 *	Magic getter.
	 *	Maps to WP_Post properties.
	 *
	 *	@return assoc containing all translated posts with the post locale as key.
	 */
	function __get( $key ) {
		if ( isset( $this->_post->$key ) )
			return $this->_post->$key;
	}

	/**
	 *	Will return available translations
	 *
	 *	@return assoc containing all translated posts with the post locale as key.
	 */
	function get_translations( ) {
		global $wpdb;
		$query = $wpdb->prepare(
			"SELECT * FROM $wpdb->posts WHERE post_locale != %s AND post_translation_group=%d AND post_type=%s",
			$this->_post->post_locale,
			$this->_post->post_translation_group,
			$this->_post->post_type
		);
		$results = $wpdb->get_results(  $query , OBJECT );
		$return = array();
		foreach( $results as $translated_post )
			$return[$translated_post->post_locale] = $translated_post;
		return $return;
	}
	
	/**
	 *	Get translation of post
	 *
	 *	@param $locale	string Locale of translated post
	 *	@return GlottyBotPost or null
	 */
	function get_translation( $locale ) {
		global $wpdb;
		if ( ! $this->_post )
			return null;
			
		if ( $this->_post->post_locale == $locale )
			return $this;
		$query = $wpdb->prepare(
			"SELECT ID FROM $wpdb->posts WHERE post_locale = %s AND post_translation_group=%d AND post_type=%s",
			$locale,
			$this->_post->post_translation_group,
			$this->_post->post_type
		);
		$results = $wpdb->get_results(  $query , OBJECT );
		$return = array();
		foreach( $results as $translated_post )
			return GlottyBotPost( $translated_post->ID );
		return null;
	}
	
	/**
	 *	Get Link to clone a post.
	 *
	 *	@param $target_locale	string Locale of translated post
	 *	@param $deep	bool whether to clone all children (not implemented yet)
	 *	@return int or WP_Error
	 */
	function clone_for_translation( $target_locale , $deep = true ) {
		if ( ! $translated_post = $this->get_translation( $target_locale ) ) {
			$new_post_parent = GlottyBotPost( $this->_post->post_parent )->get_translation( $target_locale );
			/*
				post_content > lookup translations
				post_title > lookup translations
				post_excerpt > lookup translations
			*/
			$post_status = $this->_post->post_status;
			if ( in_array( $post_status , array( 'future' , 'public' ) ) )
				$post_status = 'draft';
			
			$diffs = array(
				'post_locale' => $target_locale,
				'post_status' => $post_status, // bring to options.
				'post_parent' => $new_post_parent ? $new_post_parent : $this->_post->post_parent,
				'comment_count' => 0,
			);
			return $this->clone_post( $diffs );
		} else {
			// translation exists
			return new WP_Error( '' , sprintf( 'Translation exists at ID: ',$translated_post->ID ) );
		}
	}
	
	/**
	 *	Clone this post and attachments to post
	 *
	 *	@param $differences array differences to original post 
	 *	@return new post id or WP_Error
	 */
	function clone_post( $differences = array() ) {
		$postarr = get_object_vars( $this->_post );
		$postarr['ID'] = 0;
		
		// copy taxonomies
		$postarr['tax_input'] = array();
		if ( $taxonomies = get_post_taxonomies( $this->_post ) ) {
			foreach ($taxonomies as $taxo ) 
			$postarr['tax_input'][$taxo] = wp_get_post_terms($this->_post->ID, $taxo, array("fields" => "names") );
		}
		
		// insert post
		$postarr = wp_parse_args( $differences , $postarr );
		$postarr = apply_filters( 'glottybot_post_clone_data' , $postarr , $this->_post );
		$new_post_id = wp_insert_post( $postarr );
		
		if ( ! is_wp_error( $new_post_id ) ) {
			// 
			// lookup attachment children, clone them
			$attachments = get_children( array( 'post_parent' => $this->_post->ID , 'post_type'   => 'attachment' ) );
		
			foreach ( $attachments as $attachment ) {
				$attachment = GlottyBotPost( $attachment );
				if ( ! GlottyBotPost( $attachment->ID )->get_translation( $postarr['post_locale'] ) ) 
					$attachment->clone_post( array( 'post_parent' => $new_post_id , 'post_locale' => $postarr['post_locale'] ) );
			}
		
			// clone post thumbnail
			$post_thumbnail_id = get_post_meta( $this->_post->ID , '_thumbnail_id' , true );
			if ( $post_thumbnail_id ) {
				$post_thumbnail = get_post( $post_thumbnail_id );
				if ( $post_thumbnail && ( $post_thumbnail->post_locale != $postarr['post_locale'] ) ) {
					$post_thumbnail = GlottyBotPost( $post_thumbnail_id );
					if ( $thumb_translation = $post_thumbnail->get_translation( $postarr['post_locale'] ) ) {
						$new_thumb_id = $thumb_translation->ID;
					} else {
						$new_thumb_id = $post_thumbnail->clone_post( $post_thumbnail_id , array( 'post_locale' => $postarr['post_locale'] ) );
					}
					update_post_meta( $new_post_id , '_thumbnail_id' , $new_thumb_id );
				}
			}
		
			// clone postmeta
			$ignore_meta_keys = array( '_edit_lock' , '_edit_last' , '_thumbnail_id' );
			$meta = get_post_meta( $this->_post->ID );
		
			foreach ( $meta as $meta_key => $values ) {
				if ( in_array( $meta_key , $ignore_meta_keys ) )
					continue;
				foreach ( $values as $value ) 
					add_post_meta( $new_post_id , $meta_key , $value );
			}
			
			$new_post = get_post( $new_post_id );
			do_action( 'glottybot_post_cloned' , $this->_post , $new_post );
		}
		return $new_post_id;
	}
}

endif;
