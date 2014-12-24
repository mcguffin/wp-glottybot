<?php


if ( ! class_exists( 'GlottyBotTemplate' ) ):
class GlottyBotTemplate {

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

}
endif;