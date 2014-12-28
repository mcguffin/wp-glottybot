<?php


if ( ! class_exists( 'GlottyBotAdminPomo' ) ):
class GlottyBotAdminPomo {
	/**
	 *	string taxonomy | menu
	 */
	private $pomo_prefix = 'glottybot';
	
	/**
	 *	string taxonomy | menu
	 */
	protected $textdomain_prefix;
	
	
	/**
	 *	@param $object_identifier string category slug or menu id
	 *	@param $textdomain_prefix string category | menu
	 *	@return string texdomain
	 */
	protected function get_textdomain( $object_identifier , $textdomain_prefix = null ){
		if ( is_null( $textdomain_prefix ) )
			$textdomain_prefix = $this->textdomain_prefix;
		return "{$this->pomo_prefix}-{$textdomain_prefix}-{$object_identifier}";
	}

	/**
	 *	Get po or file name without suffix relative to WP_LANG_DIR
	 *
	 *	@param $object_identifier string category slug or menu id
	 *	@param $language string language code
	 *	@param $textdomain_prefix string category | menu
	 *	@return string Path to po or file without suffix relative to wp-content/languages
	 */
	private function get_pomo_file_name( $object_identifier , $language , $textdomain_prefix = null ) {
		$language = glottybot_language_code_sep( $language , '_' );
		$textdomain = $this->get_textdomain( $object_identifier , $textdomain_prefix );
		return "{$textdomain}-{$language}";
	}
	

	/**
	 *	Get po file name relative to WP_LANG_DIR
	 *
	 *	@param $object_identifier string category slug or menu id
	 *	@param $language string language code
	 *	@param $textdomain_prefix string category | menu
	 *	@return string Path to po file relative to wp-content/languages
	 */
	protected function get_po_file_name( $object_identifier , $language , $textdomain_prefix = null ) {
		return $this->get_pomo_file_name( $object_identifier , $language , $textdomain_prefix ).".po";
	}
	/**
	 *	Get absolute po file path
	 *
	 *	@param $object_identifier string category slug or menu id
	 *	@param $language string language code
	 *	@param $textdomain_prefix string category | menu
	 *	@param $in_path
	 *	@return string Absolute Path to po file
	 */
	protected function get_po_file_path( $object_identifier , $language , $textdomain_prefix = null , $in_path = WP_LANG_DIR ) {
		return $in_path . DIRECTORY_SEPARATOR . $this->get_po_file_name( $object_identifier , $language , $textdomain_prefix );
	}
	/**
	 *	Return true if a po file exists
	 *
	 *	@param $object_identifier string category slug or menu id
	 *	@param $language string language code
	 *	@param $textdomain_prefix string category | menu
	 *	@return bool whether a po file exists for the given textdomain and language
	 */
	protected function has_po( $object_identifier , $language , $textdomain_prefix = null ) {
		return file_exists( $this->get_po_file_path( $object_identifier , $language , $textdomain_prefix ) );
	}
	/**
	 *	Get path of existing pofile.
	 *	
	 *	@param $object_identifier string category slug or menu id
	 *	@param $language string language code
	 *	@param $textdomain_prefix string category | menu
	 *	@return bool | string path to po file, false if no po exists
	 */
	protected function get_po_file( $object_identifier , $language , $textdomain_prefix = null ) {
		$pofile = $this->get_po_file_path( $object_identifier , $language , $textdomain_prefix );
		return file_exists( $pofile ) ? $pofile : false;
	}
	
	
	
	/**
	 *	Get mo file name relative to WP_LANG_DIR
	 *
	 *	@param $object_identifier string category slug or menu id
	 *	@param $language string language code
	 *	@param $textdomain_prefix string category | menu
	 *	@return string Path to po file relative to wp-content/languages
	 */
	protected function get_mo_file_name( $object_identifier , $language , $textdomain_prefix = null ) {
		return $this->get_pomo_file_name( $object_identifier , $language , $textdomain_prefix ).".mo";
	}
	/**
	 *	Get absolute mo file path
	 *
	 *	@param $object_identifier string category slug or menu id
	 *	@param $language string language code
	 *	@param $textdomain_prefix string category | menu
	 *	@return string Absolute Path to po file
	 */
	protected function get_mo_file_path( $object_identifier , $language , $textdomain_prefix = null , $in_path = WP_LANG_DIR ) {
		return $in_path . DIRECTORY_SEPARATOR . $this->get_mo_file_name( $object_identifier , $language , $textdomain_prefix );
	}
	/**
	 *	Return true if a mo file exists
	 *
	 *	@param $object_identifier string category slug or menu id
	 *	@param $language string language code
	 *	@param $textdomain_prefix string category | menu
	 *	@return bool whether a mo file exists for the given textdomain and language
	 */
	protected function has_mo( $object_identifier , $language , $textdomain_prefix = null ) {
		return file_exists( $this->get_mo_file_path( $object_identifier , $language , $textdomain_prefix ) );
	}
	/**
	 *	Get path of existing mofile.
	 *	
	 *	@param $object_identifier string category slug or menu id
	 *	@param $language string language code
	 *	@param $textdomain_prefix string category | menu
	 *	@return bool | string path to mo file, false if no mo exists
	 */
	protected function get_mo_file( $object_identifier , $language , $textdomain_prefix = null ) {
		$mofile = $this->get_mo_file_path( $object_identifier , $language , $textdomain_prefix );
		return file_exists( $mofile ) ? $mofile : false;
	}

	
	
	/**
	 *	Get pot file name relative to WP_LANG_DIR
	 *
	 *	@param $object_identifier string category slug or menu id
	 *	@param $textdomain_prefix string category | menu
	 *	@return string Path to po file relative to wp-content/languages
	 */
	protected function get_pot_file_name( $object_identifier , $textdomain_prefix = null ) {
		$textdomain = $this->get_textdomain( $object_identifier , $textdomain_prefix );
		return "{$textdomain}.pot";
	}
	/**
	 *	Get absolute pot file path
	 *
	 *	@param $object_identifier string category slug or menu id
	 *	@param $textdomain_prefix string category | menu
	 *	@return string Absolute Path to pot file
	 */
	protected function get_pot_file_path( $object_identifier , $textdomain_prefix = null ) {
		return WP_LANG_DIR . DIRECTORY_SEPARATOR . $this->get_pot_file_name($object_identifier , $textdomain_prefix);
	}


	
	
	/**
	 *	Format multiline message string in po/pot file
	 *
	 *	@param $msg string The multiline message
	 *	@return string a properly wrapped multiline message.
	 */
	protected function wrap_multiline_messages( $msg ) {
		return preg_replace('/(\n\r|\r|\n)/',"\\n\"\r\"",$msg);
	}
	
	
	
	/**
	 *	Get Editor URL for taxonomy or menu translations.
	 *  Currently returns URLs as used by the loco translate plugin.
	 *
	 *	@param $object_identifier string category slug or menu id
	 *	@param $language string language code
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