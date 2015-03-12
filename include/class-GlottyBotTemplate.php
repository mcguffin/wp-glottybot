<?php


/**
 *	HTML templates
 */
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
	static function i18n_item( $locale , $country = true , $language = true ) {
		@list($language_code,$country_code) = GlottyBotLocales::parse_locale($locale);
		
		if ( ! ( $language_code || $country_code ) )
			return '';
		$attr = '';
		if ( $language && $language_code )
			$attr .= sprintf( ' data-language="%s"' , $language_code );
		if ( $country && $country_code )
			$attr .= sprintf( ' data-country="%s"' , $country_code );
		return sprintf('<span class="i18n-item" %s></span>',$attr);
	}
	
	
	
	static function glottybot_select_locale( $args ) {
		$args = wp_parse_args( $args, array(
			'id'			=> 'locale',
			'name'			=> 'locale',
			'languages'		=> GlottyBotLocales::get_languages(),
			'countries'		=> GlottyBotLocales::get_countries(),
			'selected'		=> '',
			'disabled'		=> array(),
			'add_select'	=> false,
		) );
		
		extract( $args );
		if ( ! is_object( $selected ) )
			$selected = GlottyBotLocales::get_locale_object( $selected );
		
		$output = sprintf( '<div class="glottybot-select-locale" id="%s">' , esc_attr( $args['id'] ) );
		
		$output .= sprintf( '<select autocomplete="off" name="%s[language]" id="%s-language" class="glottybot-select-language">', esc_attr( $args['name'] ) , esc_attr( $args['id'] ) );
		$output .= sprintf( '<option value="">%s</option>' , __('— Select —') );
		foreach ( $languages as $lang_code => $language ) {
			$output .= sprintf( '<option value="%s" %s data-countries="%s">%s</option>' , 
					$lang_code, 
					selected($selected->language , $lang_code ),
					implode( ' ' , $language->country_codes ),
					$language->name
				);
		}
		$output .= '</select>';
		
		$output .= sprintf( '<select autocomplete="off" name="%s[country]" id="%s-country" class="glottybot-select-country">', esc_attr( $args['name'] ) , esc_attr( $args['id'] ) );
		$output .= sprintf( '<option value="">%s</option>' , __('— Select —') );
		foreach ( $countries as $country_code => $country ) {
			$output .= sprintf( '<option value="%s" %s>%s</option>' , 
					$country_code, 
					selected($selected->country , $country_code ),
					$country->name
				);
		}
		$output .= '</select>';
		
		$output .= '</div>';
		return $output;
	}
	
	

}
endif;