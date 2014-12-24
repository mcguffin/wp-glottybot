<?php

/*
Plugin Name: WP Glottybot
Plugin URI: https://github.com/mcguffin/wp-glottybot
Description: An easy to use multilingual plugin for WordPress.
Author: Jörn Lund
Author URI: https://github.com/mcguffin
Version: 0.0.1
License: GPLv3

Text Domain: wp-glottybot
Domain Path: /languages/
*/

/*  Copyright 2014  Jörn Lund

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as 
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/


/**
 *	The plugin main Class
 */
if ( ! class_exists( 'GlottyBot' ) ):
class GlottyBot {
	private static $_instance = null;

	/**
	 * Getting a singleton.
	 *
	 * @return object single instance of GlottyBot
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
		add_action( 'plugins_loaded' , array( &$this , 'load_textdomain' ) );
		add_action( 'init' , array( &$this , 'init' ) );
		register_activation_hook( __FILE__ , array( __CLASS__ , 'activate' ) );
		register_deactivation_hook( __FILE__ , array( __CLASS__ , 'deactivate' ) );
		register_uninstall_hook( __FILE__ , array( __CLASS__ , 'uninstall' ) );
	}

	/**
	 * Hooked on 'plugins_loaded' 
	 * Load text domain
	 */
	public function load_textdomain() {
		load_plugin_textdomain( 'wp-glottybot' , false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
	}
	/**
	 * Init hook.
	 */
	function init() {
	}



	/**
	 *	Fired on plugin activation
	 */
	public static function activate() {
		global $wpdb;

		if ( ! current_user_can( 'activate_plugins' ) )
			return;
		
		if ( is_multisite() && is_network_admin() ) {
			$blogids = $wpdb->get_col("SELECT blog_id FROM $wpdb->blogs");
			foreach ( $blogids as $blog_id) {
				switch_to_blog($blog_id);
				self::_install_posts_table( );
			}
		} else {
			self::_install_posts_table( );
			
		}
	}

	/**
	 *	Add post_language column to wp posts table
	 */
	private static function _install_posts_table( ) {
		global $wpdb;
//		$cols = array( 'post_languages'=>'post_language' , 'master_IDs' => 'master_ID' );
		$c = $wpdb->get_results("SHOW COLUMNS FROM $wpdb->posts LIKE 'post_language'");
		if ( empty( $c ) )
			$wpdb->query("ALTER TABLE $wpdb->posts ADD COLUMN post_language varchar(8) NOT NULL DEFAULT '' AFTER `post_status`;");
		
		$c = $wpdb->get_results("SHOW COLUMNS FROM $wpdb->posts LIKE 'post_translation_group'") ;
		if ( empty( $c ) )
			$wpdb->query("ALTER TABLE $wpdb->posts ADD COLUMN post_translation_group bigint(20) NOT NULL DEFAULT 0 AFTER `post_language`;");
		
		$i =  $wpdb->query("SHOW INDEX FROM $wpdb->posts WHERE Key_name = 'post_language'") ;
		if ( empty( $i ) )
			$wpdb->query("ALTER TABLE $wpdb->posts ADD INDEX `post_language` (`post_language`);");
		$i =  $wpdb->query("SHOW INDEX FROM $wpdb->posts WHERE Key_name = 'post_translation_group'") ;
		if ( empty( $i ) )
			$wpdb->query("ALTER TABLE $wpdb->posts ADD INDEX `post_translation_group` (`post_translation_group`);");
		
		// set to default language
		$wpdb->query( $wpdb->prepare( 
			"UPDATE $wpdb->posts SET post_language='%s' WHERE post_language=''" , 
			get_bloginfo('language' ) 
		) );
		// set missing master IDs
		$wpdb->query( "UPDATE $wpdb->posts SET post_translation_group=ID WHERE post_translation_group=0  AND post_status!='auto-draft' AND post_type!='revision'" );
		$wpdb->query( "UPDATE $wpdb->posts SET post_translation_group=post_parent WHERE post_translation_group=0 AND post_type='revision'" );
	}
	private static function _uninstall_posts_table( ) {
		global $wpdb;
		$cols = array( 'post_language'=>'post_language' , 'post_translation_group' => 'post_translation_group' );
		foreach ( $cols as $idx => $col ) {
			$c = $wpdb->get_results("SHOW COLUMNS FROM $wpdb->posts LIKE '$col'");
			if (!empty($c))
				$wpdb->query("ALTER TABLE $wpdb->posts DROP COLUMN $col;");
				
			$i = $wpdb->query("SHOW INDEX FROM $wpdb->posts WHERE Key_name = '$idx'");
			if (!empty($i))
				$wpdb->query("ALTER TABLE $wpdb->posts DROP INDEX ('$idx');");
		}
	}

	/**
	 *	Fired on plugin deactivation
	 */
	public static function deactivate() {
	}
	/**
	 * unistalling
	 */
	public static function uninstall(){
		delete_option( 'glottybot_additional_languages' );
		
		if (function_exists('is_multisite') && is_multisite() && is_network_admin() ) {
			$blogids = $wpdb->get_col("SELECT blog_id FROM $wpdb->blogs");
			foreach ( $blogids as $blog_id) {
				switch_to_blog($blog_id);
				self::_uninstall_posts_table( );
				restore_current_blog();
			}
		} else {
			self::_uninstall_posts_table( );
		}
	}

}

GlottyBot::instance();

endif;

/**
 * Autoload GlottyBot Classes
 *
 * @param string $classname
 */
function glottybot_autoload( $classname ) {
	$class_path = dirname(__FILE__). sprintf('/include/class-%s.php' , $classname ) ; 
	if ( file_exists($class_path) )
		require_once $class_path;
}
spl_autoload_register( 'glottybot_autoload' );

require_once( dirname(__FILE__). '/include/glottybot-functions.php' );


/**
 *	Init permastruct and posts selection
 */
GlottyBotPermastruct::instance();
GlottyBotPosts::instance();
if ( is_admin() ) {
	/**
	 *	Init Admin tools
	 */
	GlottyBotAdmin::instance();
	GlottyBotGeneralSettings::instance();
	GlottyBotPermalinkSettings::instance();
	GlottyBotEditPosts::instance();
	GlottyBotImportExport::instance();
	GlottyBotAdminTaxonomy::instance();
	GlottyBotAdminMenus::instance();
} else {
	/**
	 *	Init Frontend textdomain loading
	 */
	GlottyBotTextdomains::instance();
}
