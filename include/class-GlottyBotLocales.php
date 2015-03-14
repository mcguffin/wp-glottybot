<?php



/*
WordPress localizations
=======================

WordPress localizations are pretty weird.
Some only consist of a language code (like 'fi'). 
Some have a language and country code (like de_DE). 
en-US as default langugage is not encoded at all and falls back to an empty string ''.

WP internally handles language/country codes seprated with an underscore 'en_GB'.

get_bloginfo('language') returns lang/coutnry separated with a dash 'en-GB'


Listing:
--------
Countries denoted in WordPress localizations
'bg','ba','dk','de','ca','gb','au','es','pe','cl','ir','fr','es','il','hu','id','it','kr','mm','no','nl','pl','pt','br','ru','sk','rs','se','tr','cn','tw','us'


Table:
------
Languages covered by the available WP localizations having no country code and their corresponding country codes.
Coutries for the arab language taken from http://en.wikipedia.org/wiki/Arabic_language#mediaviewer/File:Arabic_speaking_world.svg

lang code	Lang name		Possible Country code(s)
	'ar'	Arabic			'EG','DZ','BH','DJ','ER','IQ','IL','YE','JO','QA','KM','KW','LB','LY','MA','MR','OM','SA','SO','SD','SY','TD','TN','AE'
	'az'	Azerbaijani		'AZ'
	'ca'	Catalan	ES, 	'FR'
	'cy'	Welsh			'GB'
	'eu'	Basque			'ES'
	'fi'	Finnish			'FI'
	'gd'	Gaelic			'GB'
	'hr'	Croatian		'HR'
	'ja'	Japanese		'JP'
	'th'	Thai			'TH'
*/

class GlottyBotLocales extends GlottyBotLocalesData {
	// languages
	// countries
	
	static function get_languages( $language_codes = null ) {
		if ( is_null( $language_codes ) ) {
			$language_list = self::$_languages;
		} else {
			$language_list = array();
			foreach ( (array) $language_codes as $locale ) {
				$code = self::get_language_code( $locale );
				if ( isset(self::$_languages[ $code ]) )
					$language_list[$locale] = self::$_languages[ $code ];
			}
		}
		return array_map( array( __CLASS__ , '_objectify' ) , $language_list );
	}
	static function get_countries( $country_codes = null ) {
		if ( is_null( $country_codes ) ) {
			$countries_list = self::$_countries;
		} else {
			$countries_list = array();
			foreach ( (array) $country_codes as $locale ) {
				$code = self::get_country_code( $locale );
				if ( isset(self::$_countries[ $code ]) )
					$countries_list[$locale] = self::$_countries[ $code ];
			}
		}
		return array_map( array( __CLASS__ , '_objectify' ) , $countries_list );
	}
	private static function _objectify( $arr ) {
		return (object) $arr;
	}
	
	
	
	public static function get_locale_names( $locales ) {
		$ret = array();
		foreach ( (array) $locales as $locale ) {
			$ret[$locale] = self::get_locale_name( $locale );
		}
		return $ret;
	}
	public static function get_locale_name( $locale ) {
		list( $language_code , $country_code ) = self::parse_locale($locale);
		$name = false;
		if ( isset( self::$_languages[$language_code] ) ) {
			$name = __( self::$_languages[$language_code]['name'] );
			if ( isset( self::$_countries[$country_code] ) )
				$name .= ' (' . __( self::$_countries[$country_code]['name'] ) . ')';
		}
		return $name;
	}

	public static function get_locale_objects( $locales ) {
		$ret = array();
		foreach ( (array) $locales as $locale ) {
			$ret[$locale] = self::get_language_country( $locale );
		}
		return $ret;
	}
	static function get_locale_object( $locale ) {
		list( $language_code , $country_code ) = self::parse_locale($locale);
		return (object) array( 
			'language' => $language_code ? $language_code : false , 
			'country' => $country_code ? $country_code : '' 
		);
	}
	
	

	static function parse_locale($locale) {
		$ret = preg_split('/[-_]+/',$locale);
		if ( ! isset( $ret[1] ) )
			$ret[1] =  false;
		return $ret;
	}
	
	
	static function get_language_code( $locale ) {
		list( $language_code , $country_code ) = self::parse_locale($locale);
		return $language_code;
	}
	static function get_country_code( $locale ) {
		list( $language_code , $country_code ) = self::parse_locale($locale);
		return $country_code;
	}
	
	static function get_language_country( $locale ) {
		if ( ! is_object( $locale ) ) 
			$locale = self::get_locale_object( $locale );
		
		$ret = (object) array( 'language' => false , 'country' => false );
		
		if ( isset( self::$_languages[ $locale->language ] ) )
			$ret->language = self::get_language_object( $locale->language );
			
		if ( isset( self::$_countries[ $locale->country ] ) )
			$ret->country = self::get_country_object( $locale->country );
		return $ret;
	}
	
	
	static function get_country_object( $country_code ) {
		if ( isset( self::$_countries[ $country_code ] ) ) {
			$obj = self::_objectify( self::$_countries[ $country_code ] );
			$obj->languages = array();
			foreach ( $obj->language_codes as $language_code ) {
				if ( isset( self::$_languages[$language_code] ) ) {
					$obj->languages[$language_code] = (object) self::$_languages[$language_code];
				}
			}
			return $obj;
		}
		return false;
	}
	static function get_language_object( $language_code ) {
		if ( isset( self::$_languages[ $language_code ] ) ) {
			$obj = self::_objectify( self::$_languages[ $language_code ] );
			$obj->countries = array();
			foreach ( $obj->country_codes as $country_code ) {
				if ( isset( self::$_countries[$country_code] ) ) {
					$obj->countries[ $country_code] = (object) self::$_countries[$country_code];
				}
			}
			return $obj;
		}
		return false;
	}
	
}