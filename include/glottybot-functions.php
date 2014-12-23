<?php


/*
WP-Languages:

WP-lang-codes without country codes:

'ar',	Arabic	EG, DZ, BH, DJ, ER, IQ, IL, YE, JO, QA, KM, KW, LB, LY, MA, MR, OM, SA, SO, SD, SY, TD, TN, AE
'az',	Azerbaijani	AZ
'ca',	Catalan	ES, FR
'cy',	Welsh		GB
'eu',	Basque		ES
'fi',	Finnish		FI
'gd',	Gaelic		GB
'hr',	Croatian	HR
'ja',	Japanese	JP
'th',	Thai		TH

*/


function glottybot_sanitize_language_code( $language_code , $separator = '-' , $false_on_fail = false ) {
	$language_code = glottybot_language_code_sep( $language_code , $separator );
	if ( in_array( $language_code , glottybot_language_code_sep( glottybot_available_languages() , $separator ) ) )
		return $language_code;
	return ! $false_on_fail ? get_bloginfo( 'language' ) : false;
}


function glottybot_get_master_post( $post ) {
	if ( is_numeric( $post ) )
		$post = get_post($post);
	$language = get_bloginfo( 'language' );
		
	if ( $master_post = glottybot_get_translated_post( $post , $language ) )
		return $master_post;

	return $post;
}


function glottybot_get_translated_post( $post , $language = null ) {
	if ( is_numeric( $post ) )
		$post = get_post($post);
	if ( is_null( $language ) )
		$language = glottybot_current_language( );
	if ( $post->post_language == $language )
		return $post;

	global $wpdb;
	$query = $wpdb->prepare(
		"SELECT * FROM $wpdb->posts WHERE post_language=%s AND post_translation_group=%d",
		glottybot_language_code_sep( $language , '-' ),
		$post->post_translation_group
	);
	$result = $wpdb->get_row(  $query , OBJECT );
	return $result;
}

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

function glottybot_current_language( $separator = '-' ) {
	$code = GlottyBotPermastruct::instance()->get_language();
	return glottybot_language_code_sep( $code , $separator );
}
function glottybot_default_language( $separator = '-' ) {
	$code = get_option('WPLANG');
	if ( ! $code )
		$code = 'en-US';
	return glottybot_language_code_sep( $code , $separator );
}
function _glottybot_default_language( $separator = '-' ) {
	return glottybot_default_language( '_' );
}
function _glottybot_current_language() {
	return glottybot_current_language( '_' );
}
function glottybot_get_language_name( $code ) {
	$code = glottybot_language_code_sep( $code , '_' );
	$translations = glottybot_wp_get_available_translations();
	if ( isset( $translations[$code] , $translations[$code]['english_name'] ) )
		return __( $translations[$code]['english_name'] , 'language_names' );
	return $code;
}

function glottybot_get_clone_post_link( $post_id , $language ) {
	if ( ! current_user_can( 'edit_post' , $post_id ) )
		return false;
	$language = glottybot_language_code_sep( $language , $separator = '-' );
	$nonce_name = sprintf('glottybot_copy_post-%s-%d' , $language , $post_id );
	
	$link = admin_url('edit.php');
	$link = add_query_arg( 'action' , 'glottybot_copy_post' , $link );
	$link = add_query_arg( 'post_id' , $post_id , $link );
	$link = add_query_arg( 'post_language' , $language , $link );
	$link = add_query_arg( 'ajax_nonce' , wp_create_nonce( $nonce_name ) , $link );
	return $link;
}

function glottybot_language_code_sep( $code , $separator = '_' ) {
	if ( is_array( $code ) ) {
		foreach ( $code as $i => $v )
			$code[$i] = glottybot_language_code_sep( $v , $separator );
	} else {
		$code = preg_replace( '/(-|_)/' , $separator , $code );
	}
	return $code;
}

function glottybot_dropdown_languages( $args , $echo = true ) {
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
	if ( ! $echo )
		return $ret;
	echo $ret;
}