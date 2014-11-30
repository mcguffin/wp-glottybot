<?php



function postbabel_sanitize_language_code( $language_code ) {
	$language_code = postbabel_language_code_sep( $language_code , '-' );
	if ( in_array( $language_code , postbabel_language_code_sep( postbabel_available_languages() , '-' ) ) )
		return $language_code;
	return get_bloginfo( 'language' );
}


function postbabel_get_master_post( $post ) {
	if ( is_numeric( $post ) )
		$post = get_post($post);
	$language = get_bloginfo( 'language' );
		
	if ( $master_post = postbabel_get_translated_post( $post , $language ) )
		return $master_post;

	return $post;
}


function postbabel_get_translated_post( $post , $language ) {
	if ( is_numeric( $post ) )
		$post = get_post($post);

	if ( $post->post_language == $language )
		return $post;

	global $wpdb;
	$query = $wpdb->prepare(
		"SELECT * FROM $wpdb->posts WHERE post_language=%s AND post_translation_group=%d",
		postbabel_language_code_sep( $language , '-' ),
		$post->post_translation_group
	);
	$result = $wpdb->get_row(  $query , OBJECT );
	return $result;
}

function postbabel_get_translated_posts( $post ) {
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
function postbabel_wp_get_available_languages() {
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
function postbabel_available_languages() {
	$blog_lang = get_option( 'WPLANG' );
	$active_langs = (array) get_option( 'post_babel_additional_languages' );
	$active_langs[] = $blog_lang ? $blog_lang : 'en_US';
	$langs = array_intersect( postbabel_wp_get_available_languages() , $active_langs );
	return $langs;
}

/**
 *	Will return wp_get_available_translations() with 'en_US' added. 
 *  Return value represents all installed or available WP admin languages. 
 *	
 *	@return array containing language codes
 */
function postbabel_wp_get_available_translations() {
	if ( ! $translations = get_transient('_postbabel_available_translations') ) {
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
		set_transient('_postbabel_available_translations' , $translations , WEEK_IN_SECONDS * 4 );
	}
	return $translations;
}

function postbabel_current_language( $separator = '-' ) {
	$code = PostBabelPermastruct::instance()->get_language();
	return postbabel_language_code_sep( $code , $separator );
}

function postbabel_get_language_name( $code ) {
	$code = postbabel_language_code_sep( $code , $separator = '_' );
	$translations = postbabel_wp_get_available_translations();
	if ( isset( $translations[$code] , $translations[$code]['english_name'] ) )
		return __( $translations[$code]['english_name'] , 'language_names' );
	return $code;

}

function postbabel_language_code_sep( $code , $separator = '_' ) {
	if ( is_array( $code ) ) {
		foreach ( $code as $i => $v )
			$code[$i] = postbabel_language_code_sep( $v , $separator );
	} else {
		$code = preg_replace( '/(-|_)/' , $separator , $code );
	}
	return $code;
}

function postbabel_dropdown_languages( $args , $echo = true ) {
	$args = wp_parse_args( $args, array(
		'id'			=> '',
		'name'			=> '',
		'languages'		=> get_available_languages(),
		'selected'		=> '',
		'disabled'		=> array(),
	) );

	$translations = postbabel_wp_get_available_translations();
	$languages = array();

	$ret = sprintf( '<select name="%s" id="%s">', esc_attr( $args['name'] ), esc_attr( $args['id'] ) );

	foreach ( $args['languages'] as $locale ) {
		$lookup_locale = postbabel_language_code_sep($locale, '_' );
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