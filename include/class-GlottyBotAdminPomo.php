<?php


if ( ! class_exists( 'GlottyBotAdminPomo' ) ):
class GlottyBotAdminPomo {
	/**
	 *	string taxonomy | menu
	 */
	protected $textdomain_prefix;
	
	
	
	/**
	 *	@obsolote
	 */
	protected function get_textdomain( $object_identifier ){
		return "{$this->textdomain_prefix}-{$object_identifier}";
	}

	/**
	 *	@param textdomain string 
	 *	@param language string language code
	 *	@return string Path to po file relative to wp-content/languages
	 */
	protected function get_po_file_name( $object_identifier , $language ) {
		$language = glottybot_language_code_sep( $language , '_' );
		$textdomain = $this->get_textdomain( $object_identifier );
		return "{$textdomain}-{$language}.po";
	}
	/**
	 *	@param textdomain string 
	 *	@param language string language code
	 *	@return string Absolute Path to po file
	 */
	protected function get_po_file_path( $object_identifier , $language , $in_path = WP_LANG_DIR ) {
		return $in_path . DIRECTORY_SEPARATOR . $this->get_po_file_name( $object_identifier , $language );
	}
	
	/**
	 *	@param textdomain string 
	 *	@param language string language code
	 *	@return string Path to po file relative to wp-content/languages
	 */
	protected function get_mo_file_name( $object_identifier , $language ) {
		$language = glottybot_language_code_sep( $language , '_' );
		$textdomain = $this->get_textdomain( $object_identifier );
		return "{$textdomain}-{$language}.mo";
	}
	/**
	 *	@param textdomain string 
	 *	@param language string language code
	 *	@return string Absolute Path to po file
	 */
	protected function get_mo_file_path( $object_identifier , $language , $in_path = WP_LANG_DIR ) {
		return $in_path . DIRECTORY_SEPARATOR . $this->get_mo_file_name( $object_identifier , $language );
	}
	
	/**
	 *	@param textdomain string 
	 *	@return string Path to po file relative to wp-content/languages
	 */
	protected function get_pot_file_name( $object_identifier ) {
		$textdomain = $this->get_textdomain( $object_identifier );
		return "{$textdomain}.pot";
	}
	/**
	 *	@param textdomain string 
	 *	@return string Absolute Path to pot file
	 */
	protected function get_pot_file_path( $object_identifier ) {
		return WP_LANG_DIR . DIRECTORY_SEPARATOR . $this->get_pot_file_name($object_identifier);
	}

	/**
	 *	@param textdomain string 
	 *	@param language string language code
	 *	@return bool whether a po file exists for the given textdomain and language
	 */
	protected function has_po( $object_identifier , $language ) {
		return file_exists( $this->get_po_file_path( $object_identifier , $language ) );
	}
	
	protected function wrap_multiline_messages( $msg ) {
		return preg_replace('/(\n\r|\r|\n)/',"\\n\"\r\"",$msg);
	}
	
	
	
	/**
	 *	Get Editor URL for taxonomy or menu translations.
	 *  Currently returns URLs as used by the loco translate plugin.
	 *
	 *	@param string $object_identifier taxonomy | menu
	 *	@param string $language language code
	 *	@return string url to the po editor
	 */
	public function get_po_edit_url( $object_identifier , $language ){
		// Loco create: wp-admin/admin.php?page=loco-translate&msginit=taxonomy-{$taxonomy}&name=taxonomy-{$taxonomie}&type=core&custom-locale={$language}
		// Loco Edit: wp-admin/admin.php?page=loco-translate&poedit=languages/taxonomy-{$object_identifier}-{$language}.po&name=taxonomy-{$taxonomie}&type=core
		$language = glottybot_language_code_sep( $language , '_' );
		$textdomain = $this->get_textdomain( $object_identifier );
		$edit_url = admin_url( 'admin.php' );
		if ( ! $this->has_po( $object_identifier , $language ) ) {
			$edit_url = add_query_arg( array(
				'page' => 'loco-translate',
				'custom-locale' => $language,
				'name' => $textdomain,
				'msginit' => $textdomain,
				'type' => 'core',
			) , $edit_url );
		} else {
			$edit_url = add_query_arg( array(
				'page' => 'loco-translate',
				'poedit' => $this->get_po_file_path( $object_identifier , $language , "languages" ),
				'name' => $textdomain,
				'type' => 'core',
			) , $edit_url );
		}
		/**
		 * Filter the Edito URL for po file.
		 *
		 * @param string $edit_url          URL for editing the po file
		 * @param string $textdomain_prefix taxonomy | menu
		 * @param string $object_identifier Taxonomy slug or menu ID
		 * @param string $language          language
		 */
		$edit_url = apply_filters( "glottybot_edit_po_url" , $edit_url , $this->textdomain_prefix , $object_identifier , $language );
		return $edit_url;
	}
	
}

endif;