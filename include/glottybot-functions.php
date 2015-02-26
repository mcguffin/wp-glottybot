<?php


/*
WordPress localizations
=======================

WordPress localizations are pretty weird.
Some only consist of a language code (like 'fi'). 
Some have a language and country code (like de-DE). 
en-US as default langugage is not encoded at all and falls back to an empty string.

WordPress `get_available_languages()` and `wp_get_available_translations()` return 
languages and countries separated with an underscore `en_GB`

WordPress `get_locale()` returns language and country separated with a dash `en-GB`

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

Table:
------
Languages known for beiong spoken in than one country
(there are likely more, will researching this later)
	Language	Country codes
	'de'		'CH','AT','BE'
	'es'		'ES','GQ','CR','DO','SV','GT','HN','CU','MX','NI','PA','PR','AR','BO','CL','EC','CO','PY','PE','UY','VE'
	'pt'		'PT','AO','BR','GQ','GW','CV','MZ','ST','MO','TL'
	'fr'		'FR','CA',



*/


/**
 *	sanitize language code input.
 *	Will check if the input parameter is covered by `glottybot_available_languages()`
 *
 *	@param $language_code string the language code to sanitize
 *	@param $separator string either '-' or '_', to select format of the language code returned
 *	@param $false_on_fail boolean Whether to return false on failure or fall back to blog language
 *	@return mixed bool or sanitized language code o blog language
 */
function glottybot_sanitize_language_code( $language_code , $separator = '-' , $false_on_fail = false ) {
	$language_code = glottybot_language_code_sep( $language_code , $separator );
	if ( in_array( $language_code , glottybot_language_code_sep( glottybot_available_languages() , $separator ) ) )
		return $language_code;
	return ! $false_on_fail ? get_bloginfo( 'language' ) : false;
}


/**
 *	Get master of $post
 *  Return value represent the blog-langauge version of $post
 *
 *	@param $post mixed Post object or post ID
 *	@return mixed post object or null.
 */
function glottybot_get_master_post( $post ) {
	if ( is_numeric( $post ) )
		$post = get_post($post);
	$language = get_bloginfo( 'language' );
		
	if ( $master_post = glottybot_get_translated_post( $post , $language ) )
		return $master_post;

	return $post;
}


/**
 *	Get translation of $post
 *
 *	@param $post mixed Post object or post ID
 *	@param $language string desired post language. If omitted `glottybot_current_language( )` will be used
 *	@return mixed post object or null.
 */
function glottybot_get_translated_post( $post , $language = null ) {
	if ( is_numeric( $post ) )
		$post = get_post($post);
	if ( is_null( $language ) )
		$language = glottybot_current_language( );
	if ( $post->post_language == $language )
		return $post;

	global $wpdb;
	$query = $wpdb->prepare(
		"SELECT * FROM $wpdb->posts WHERE post_language=%s AND post_translation_group=%d AND post_type=%s",
		glottybot_language_code_sep( $language , '-' ),
		$post->post_translation_group,
		$post->post_type
	);
	$result = $wpdb->get_row(  $query , OBJECT );
	return $result;
}

/**
 *	Will return available translations of $post
 *  Return value represents all installed WP admin languages. 
 *
 *	@param $post mixed Post object or post ID
 *	@return assoc containing all translated posts with the post language as key.
 */
function glottybot_get_translated_posts( $post ) {
	if ( is_numeric( $post ) )
		$post = get_post($post);

	global $wpdb;
	$query = $wpdb->prepare(
		"SELECT * FROM $wpdb->posts WHERE post_language != %s AND post_translation_group=%d",
		$post->post_language,
		$post->post_translation_group
	);
	$results = $wpdb->get_results(  $query , OBJECT );
	$return = array();
	foreach( $results as $translated_post )
		$return[$translated_post->post_language] = $translated_post;
	return $return;
}

/**
 *	Will return get_available_languages() with 'en_US' added. 
 *  Return value represents all installed WP admin languages. 
 *	
 *	@return array containing language codes
 */
function glottybot_wp_get_available_languages() {
	$langs = get_available_languages();
	if ( ! in_array( 'en_US' , $langs ) )
		array_unshift($langs,'en_US');
	return $langs;
}

/**
 *	Will return languages activated for translation including the master language.
 *	
 *	@return array with language codes
 */
function glottybot_available_languages() {
	$blog_lang = get_option( 'WPLANG' );
	$active_langs = (array) get_option( 'glottybot_additional_languages' );
	$active_langs[] = $blog_lang ? $blog_lang : 'en_US';
	$langs = array_intersect( glottybot_wp_get_available_languages() , $active_langs );
	return $langs;
}

/**
 *	Will return wp_get_available_translations() with 'en_US' added. 
 *  Return value represents all installed or available WP admin languages. 
 *	
 *	@return array containing language codes
 */
function glottybot_wp_get_available_translations() {
	if ( ! $translations = get_transient('_glottybot_available_translations') ) {
		require_once( ABSPATH . 'wp-admin/includes/translation-install.php' );
		$translations = wp_get_available_translations();

		$translations['en_US'] = array(
			'language' => 'en_US',
			'english_name' => 'English (US)',
			'native_name' => 'English (US)',
			'iso' => array(
				1 => 'en'
			),
		);
		set_transient('_glottybot_available_translations' , $translations , WEEK_IN_SECONDS * 4 );
	}
	return $translations;
}

/**
 *	Get currently selected language
 *
 *	@param $separator string either '-' or '_', to select format of the language code returned
 *	@return string the currently selected language
 */
function glottybot_current_language( $separator = '-' ) {
	$code = GlottyBotPermastruct::instance()->get_language();
	return glottybot_language_code_sep( $code , $separator );
}
/**
 *	Get blog language
 *
 *	@param $separator string either '-' or '_', to select format of the language code returned
 *	@return string the default language. 
 */
function glottybot_default_language( $separator = '-' ) {
	$code = get_option('WPLANG');
	if ( ! $code )
		$code = 'en-US';
	return glottybot_language_code_sep( $code , $separator );
}
/**
 *	same as glottybot_default_language('_');
 *
 *	@return string the default language. 
 */
function _glottybot_default_language( ) {
	return glottybot_default_language( '_' );
}
/**
 *	same as glottybot_current_language('_');
 *
 *	@return string the currently selected language
 */
function _glottybot_current_language() {
	return glottybot_current_language( '_' );
}

/**
 *	Get the english name for a langauge code.
 *
 *	@param $code A language code
 *	@return string A human readable language name
 */
function glottybot_get_language_name( $code ) {
	$code = glottybot_language_code_sep( $code , '_' );
	$translations = glottybot_wp_get_available_translations();
	if ( isset( $translations[$code] , $translations[$code]['english_name'] ) )
		return __( $translations[$code]['english_name'] , 'language_names' );
	return $code;
}


/**
 *	Get the translated version of current page URL.
 *
 *	@param $code A language code
 *	@return string URL to current page in different language
 */
function glottybot_get_current_page_url( $language ) {
	global $wp;

	$url = home_url( $wp->request );// add_query_arg( $wp->query_string, '',  );

	if ( is_singular() ) {
		global $post;
		$translated = glottybot_get_translated_post( $post , $language );
		return get_permalink($translated);
	} else if ( is_404() ) {
	} else {
		$url = GlottyBotPermastruct::instance()->remove_language_slug( $url );
		if ( is_home() || is_front_page() )
			$url = GlottyBotPermastruct::instance()->add_language_slug( $url , $language );
		else 
			$url = GlottyBotPermastruct::instance()->prepend_language_slug( $url , $language );
	}
	
	/*
	is_404();
	is_search() > prepend
	is_front_page()
	is_home()
	is_archive() > prepend
		is_tax()
		is_tag()
		is_category()
		is_author()
		is_date()
		is_post_type_archive()
	is_singular() > get_permalink of translation
		is_single()
		is_page()
	is_comments_popup()
	*/
	
	return $url;
}


/**
 *	Safely set the language country separator.
 *
 *	@param $code A language code
 *	@param $separator string either '-' or '_', to select format of the language code returned
 *	@return string Language code with separator
 */
function glottybot_language_code_sep( $code , $separator = '_' ) {
	if ( is_array( $code ) ) {
		foreach ( $code as $i => $v )
			$code[$i] = glottybot_language_code_sep( $v , $separator );
	} else {
		$code = preg_replace( '/(-|_)/' , $separator , $code );
	}
	return $code;
}
/**
 *	Safely set the language country separator.
 *
 *	@param $code A language code
 *	@param $separator string either '-' or '_', to select format of the language code returned
 *	@return string Language code with separator
 */
function glottybot_normalize_language_code( $code , $separator = '_' ) {
	if ( is_array( $code ) ) {
		foreach ( $code as $i => $v )
			$code[$i] = glottybot_normalize_language_code( $v , $separator );
	} else {
		$code = preg_replace_callback( '/([a-z]{2})$/' , function($matches){ return strtoupper($matches[1]); } , $code );
	}
	return glottybot_language_code_sep( $code , $separator );
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
 *	@param $echo string either '-' or '_', to select format of the language code returned
 *	@return null | string Language Dropdown HTML
 */
function glottybot_dropdown_languages( $args , $echo = true ) {
	$ret = GlottyBotTemplate::dropdown_languages( $args , $echo = true );
	if ( ! $echo )
		return $ret;
	echo $ret;
}