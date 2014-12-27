<?php


if ( ! class_exists( 'GlottyBotPermalinkSettings' ) ):
class GlottyBotPermalinkSettings {
	private static $_instance = null;
	
	// should be 'permalink', but saving doesn't work there.
	private $optionset = 'general'; // general | writing | reading | discussion | media | permalink

	/**
	 * Getting a singleton.
	 *
	 * @return object single instance of GlottyBotSettings
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) )
			self::$_instance = new self();
		return self::$_instance;
	}

	/**
	 *	Prevent cloning
	 */
	private function __clone() {}

	/**
	 * Private constructor
	 */
	private function __construct() {
		add_action( 'admin_init' , array( &$this , 'register_settings' ) );
		add_option( "glottybot_permalink_structure" , '' , '' , true );
	}
	
	/**
	 * Setup options page.
	 */
	function register_settings() {
		$settings_section = 'glottybot_permalink_settings';
		// more settings go here ...
		register_setting( $this->optionset , 'glottybot_permalink_structure' , array( GlottyBotPermastruct::instance() , 'sanitize_glottybot_permalink_structure' ) );

		add_settings_section( $settings_section, __( 'Multilingual Permalinks',  'wp-glottybot' ), array( &$this, 'permalink_structure_description' ), $this->optionset );
		// ... and here
		$active_langs = glottybot_language_code_sep( get_option('glottybot_additional_languages') , '-' );
		
		foreach( $active_langs as $lang ) 
			add_settings_field(
				'glottybot_permalink_structure_'.$lang,
				glottybot_get_language_name( $lang ),
				array( $this, 'permalink_structure_ui' ),
				$this->optionset,
				$settings_section,
				$lang
			);
	}

	/**
	 * Print some documentation for the optionset
	 */
	public function permalink_structure_description() {
		?>
		<div class="inside">
			<p><?php _e( 'Enter an URL slug for each language.' , 'wp-glottybot' ); ?></p>
		</div>
		<?php
	}
	
	/**
	 * Output Theme selectbox
	 */
	public function permalink_structure_ui( $lang ) {
		$setting_name = 'glottybot_permalink_structure';
		$setting_value = (array) get_option( $setting_name );
		$post_lang = glottybot_language_code_sep( $lang , '-' );
		$value = $post_lang;
		if ( isset( $setting_value[$lang] ) )
			$value = $setting_value[$lang];
		
		?><input name="<?php echo $setting_name ?>[<?php echo $post_lang ?>]" id="tag_base" value="<?php echo $value ?>" class="regular-text code" type="text" /><?php
	}
	

}

endif;