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
	
}

endif;