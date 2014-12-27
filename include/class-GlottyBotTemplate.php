<?php


if ( ! class_exists( 'GlottyBotTemplate' ) ):
class GlottyBotTemplate {

	/**
	 *	Prevent cloning
	 */
	private function __clone() {}

	/**
	 * Private constructor
	 */
	private function __construct() {}

	/**
	 *	Language icon (flag+language code)
	 *	@param $code string language code
	 *	@return string `'<span class="i18n-item"  data-language="xx" data-country="XX"></span>'`
	 */
	static function i18n_item( $code ) {
		$code = glottybot_language_code_sep( $code , '_' );
		@list($language_code,$country_code) = explode('_',$code);
		if ( ! ( $language_code || $country_code ) )
			return '';
		$attr = '';
		if ( $language_code )
			$attr .= sprintf( ' data-language="%s"' , $language_code );
		if ( $country_code )
			$attr .= sprintf( ' data-country="%s"' , $country_code );
		return sprintf('<span class="i18n-item" %s></span>',$attr);
	}
	
	
	/**
	 *	A language select dropdown.
	 *
	 *	@param $args array(
	 *		'id'			=> '',
	 *		'name'			=> '',
	 *		'languages'		=> get_available_languages(),
	 *		'selected'		=> '',
	 *		'disabled'		=> array(),
	 *		'add_select'	=> false,
	 *	)
	 *	@return string Language Dropdown HTML
	 */
	static function dropdown_languages( $args ) {
		$args = wp_parse_args( $args, array(
			'id'			=> '',
			'name'			=> '',
			'languages'		=> get_available_languages(),
			'selected'		=> '',
			'disabled'		=> array(),
			'add_select'	=> false,
		) );

		$translations = glottybot_wp_get_available_translations();
		$languages = array();

		$ret = sprintf( '<select name="%s" id="%s">', esc_attr( $args['name'] ), esc_attr( $args['id'] ) );
	
		if ( $args['add_select'] )
			$ret .= sprintf( '<option >%s</option>' , __('— Select —') );
	
		foreach ( $args['languages'] as $locale ) {
			$lookup_locale = glottybot_language_code_sep($locale, '_' );
			if ( isset( $translations[ $lookup_locale ] ) ) {
				$translation = $translations[ $lookup_locale ];
				$languages[$locale] = array(
					'language'		=> $translation['language'],
					'native_name'	=> $translation['native_name'],
					'english_name'	=> $translation['english_name'],
					'lang'			=> $translation['iso'][1],
				);

				// Remove installed language from available translations.
			}
		}
		foreach ( $languages as $locale => $language ) {
			$ret .= sprintf(
				'<option value="%s" lang="%s"%s%s data-installed="1">%s</option>',
				$locale,
				esc_attr( $language['lang'] ),
				selected( $locale, $args['selected'], false ),
				disabled( in_array( $locale , $args['disabled'] ), true , false ),
				$language['english_name'] != $language['native_name'] ? 
					esc_html( $language['english_name'] . ' / ' . $language['native_name'] ) :
					$language['native_name']
			);
		}
		$ret .= '</select>';
		return $ret;
	}
	
	static function languages_menu() {
		
	}

}
endif;