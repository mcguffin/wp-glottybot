<?php


/**
 *	HTML templates
 */
if ( ! class_exists( 'GlottyBotTemplate' ) ):
class GlottyBotTemplate {

	/**
	 * Private constructor. No Instances!
	 */
	private function __construct() {}
	
	static function print_language_switcher( $args = array() ) {
		echo self::language_switcher( $args );
	}
	
	static function language_switcher( $args = array() ) {
		$args = wp_parse_args( $args , array(
			'container_format' => '<nav class="language-switcher"><ul>%items%</ul></nav>',
			'item_format' => '<li class="language-item %classnames% %active_item%"><a rel="alternate" href="%href%">%language_name%</a></li>',// %language_name%, %language_native_name%, %country_name%, %language_code%, %country_code%
			'active_item' => 'active',
		) );
		
		$outer = array(
			'items'	=> '',
			'current_locale' => GlottyBot()->get_locale(),
		);
		foreach ( GlottyBot()->get_locale_objects() as $locale => $loc_obj ) {
			$tpl_params = array(
				'locale'				=> $locale,
				'language_tag'			=> strtolower(str_replace('_','-',$locale)),
				'language_name'			=> $loc_obj->language->name,
				'language_native_name'	=> $loc_obj->language->native_name,
				'language_code'			=> $loc_obj->language->code,
				'country_name'			=> $loc_obj->country ? $loc_obj->country->name : '',
				'country_native_name'	=> GlottyBotLocales::get_country_native_name($locale),
				'country_code'			=> $loc_obj->country ? $loc_obj->country->code : '',
				'classnames'			=> '',
				'href'					=> GlottyBotPermastruct::instance()->get_current_item_translation_url( $locale ),
				'active_item'			=> $locale == $outer['current_locale'] ? $args['active_item'] : '',
			);
			$tpl_params['classnames'] .= 'language-'.$tpl_params['language_tag'];
			
			$outer['items'] .= self::parse_template( $args['item_format'] , $tpl_params );
		}
		return self::parse_template( $args['container_format'] , $outer );
	}
	
	static function parse_template( $template , $values ) {
		foreach ( $values as $key => $value )
			$template = str_replace( "%{$key}%" , $value , $template );
		return $template;
	}
	
	/**
	 *	Language icon (flag+language code)
	 *	@param $code string language code
	 *	@return string `'<span class="i18n-item"  data-language="xx" data-country="XX"></span>'`
	 */
	static function i18n_item( $locale , $country = true , $language = true ) {
		if ( ! is_array( $locale ) )
			$locale = GlottyBotLocales::parse_locale($locale);
		@list($language_code,$country_code) = $locale;
		
		if ( ! ( $language_code || $country_code ) )
			return '';
		$attr = '';
		if ( $language && $language_code )
			$attr .= sprintf( ' data-language="%s"' , $language_code );
		if ( $country && $country_code )
			$attr .= sprintf( ' data-country="%s"' , $country_code );
		return sprintf('<span class="i18n-item" %s></span>',$attr);
	}
	
	
	/**
	 *	Will return HTML containing two <select> elements.
	 *	@param $args	array(
	 *						'id'		string default 'locale'	ID prefix for <select> elements
	 *						'name'		string default 'locale'	name prefix for <select> elements
	 *						'languages'	array 
	 *						'countries'	array 
	 *						'selected'	string selected locale
	 *						'disabled'	array 
	 *					)
	 *	@return string HTML
	 */
	static function glottybot_select_locale( $args ) {
		$args = wp_parse_args( $args, array(
			'id'			=> 'locale',
			'name'			=> 'locale',
			'languages'		=> GlottyBotLocales::get_languages(),
			'countries'		=> GlottyBotLocales::get_countries(),
			'selected'		=> '',
		) );
		$args['languages'] = apply_filters( 'glottybot_supported_languages' , $args['languages'] );
		extract( $args );
		if ( ! is_object( $selected ) )
			$selected = GlottyBotLocales::get_locale_object( $selected );
		
		$output = sprintf( '<div class="glottybot-select-locale" id="%s">' , esc_attr( $args['id'] ) );
// 		$output .= '<div class="chosen-container chosen-container-single">';
		$output .= sprintf( '<select autocomplete="off" name="%s[language]" data-sync-value="#%s-language-code" id="%s-language" class="glottybot-select-language">' , esc_attr( $args['name'] ) , esc_attr( $args['id'] ) , esc_attr( $args['id'] ) );
		$output .= sprintf( '<option value="">%s</option>' , __('— Select Language —','wp-glottybot') );
		foreach ( $languages as $lang_code => $language ) {
			$output .= sprintf( '<option value="%s" %s data-countries="%s" >%s</option>' , 
					$lang_code, 
					selected($selected->language , $lang_code ),
					implode( ' ' , $language->country_codes ),
					$language->name
				);
		}
		$output .= '</select>';
// 		$output .= '</div>';
// 		
// 		$output .= '<div class="chosen-container chosen-container-single">';
		$output .= sprintf( '<select autocomplete="off" name="%s[country]" data-sync-value="#%s-country-code" id="%s-country" class="glottybot-select-country">' , esc_attr( $args['name'] ) , esc_attr( $args['id'] ), esc_attr( $args['id'] ) );
		$output .= sprintf( '<option value="">%s</option>' , __('— Select Country —','wp-glottybot') );
		foreach ( $countries as $country_code => $country ) {
			$output .= sprintf( '<option class="i18n-item" data-country="%s" value="%s" %s>%s %s</option>' , 
					$country_code, $country_code, 
					selected($selected->country , $country_code ),
					self::i18n_item( 'xx_'.$country_code , true , false ),
					$country->name
				);
		}
		$output .= '</select>';
		
		$output .= sprintf('<input type="text" id="%s-language-code" data-sync-value="#%s-language" class="code code-input glottybot-language-code-input" />' , esc_attr( $args['id'] ) , esc_attr( $args['id'] ) );
		$output .= sprintf('<input type="text" id="%s-country-code" data-sync-value="#%s-country" class="code code-input glottybot-country-code-input" />' , esc_attr( $args['id'] ) , esc_attr( $args['id'] ) );
// 		$output .= '</div>';
		
		$output .= '</div>';
		return $output;
	}
	
	

}
endif;