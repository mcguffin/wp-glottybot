<?php


if ( ! class_exists( 'PostBabylonSettings' ) ):
class PostBabylonSettings {
	private static $_instance = null;
	
	private $optionset = 'post_babylon_options'; // writing | reading | discussion | media | permalink

	/**
	 * Getting a singleton.
	 *
	 * @return object single instance of PostBabylonSettings
	 */
	public static function get_instance() {
		if ( is_null( self::$_instance ) )
			self::$_instance = new self();
		return self::$_instance;
	}

	/**
	 * Private constructor
	 */
	private function __construct() {
		add_action( 'admin_init' , array( &$this , 'register_settings' ) );

		add_action( "settings_page_{$this->optionset}" , array( &$this , 'enqueue_assets' ) );
		
		add_option( 'post_babylon_setting_1' , 'Default Value' , '' , False );
		add_action( 'admin_menu', array( &$this, 'admin_menu' ) );
	}
	function admin_menu() {
		add_options_page( __('WP Post Babylon Settings' , 'wp-post-babylon' ),__('WP Post Babylon' , 'wp-post-babylon'),'manage_options',$this->optionset, array( $this, 'settings_page' ) );
	}
	function settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
		}
		?>
		<div class="wrap">
			<h2><?php _e('WP Post Babylon Settings', 'wp-post-babylon') ?></h2>

			<form action="options.php" method="post">
				<?php
				settings_fields(  $this->optionset );
				do_settings_sections( $this->optionset );
				submit_button( __('Save Settings' , 'wp-post-babylon' ) );
				?>
			</form>
		</div><?php
	}

	/**
	 * Enqueue options Assets
	 */
	function enqueue_assets() {
		wp_enqueue_style( 'post_babylon-settings' , plugins_url( '/css/post_babylon-settings.css' , dirname(__FILE__) ));

		wp_enqueue_script( 'post_babylon-settings' , plugins_url( 'js/post_babylon-settings.js' , dirname(__FILE__) ) );
		wp_localize_script('post_babylon-settings' , 'post_babylon_settings' , array(
		) );
	}
	


	/**
	 * Setup options page.
	 */
	function register_settings() {
		$settings_section = 'post_babylon_settings';
		// more settings go here ...
		register_setting( $this->optionset , 'post_babylon_setting_1' , array( &$this , 'sanitize_setting_1' ) );

		add_settings_section( $settings_section, __( 'Section #1',  'wp-post-babylon' ), array( &$this, 'section_1_description' ), $this->optionset );
		// ... and here
		add_settings_field(
			'post_babylon_setting_1',
			__( 'Setting #1',  'wp-post-babylon' ),
			array( $this, 'setting_1_ui' ),
			$this->optionset,
			$settings_section
		);
	}

	/**
	 * Print some documentation for the optionset
	 */
	public function section_1_description() {
		?>
		<div class="inside">
			<p><?php _e( 'Section 1 Description.' , 'wp-post-babylon' ); ?></p>
		</div>
		<?php
	}
	
	/**
	 * Output Theme selectbox
	 */
	public function setting_1_ui(){
		$setting_name = 'post_babylon_setting_1';
		$setting = get_option($setting_name);
		?><input type="text" name="<?php echo $setting_name ?>" value="<?php esc_attr_e( $setting ) ?>" /><?php
	}
	

	/**
	 * Sanitize value of setting_1
	 *
	 * @return string sanitized value
	 */
	function sanitize_setting_1( $value ) {	
		// do sanitation here!
		return $value;
	}
}

PostBabylonSettings::get_instance();
endif;