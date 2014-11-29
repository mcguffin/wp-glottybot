<?php

/*
Plugin Name: WP Post Babylon
Plugin URI: http://wordpress.org/
Description: Enter description here.
Author: Jörn Lund
Version: 1.0.0
Author URI: 
License: GPL3

Text Domain: wp-post-babylon
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


if ( ! class_exists( 'PostBabylon' ) ):
class PostBabylon {
	private static $_instance = null;

	/**
	 * Getting a singleton.
	 *
	 * @return object single instance of PostBabylon
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
		add_action( 'plugins_loaded' , array( &$this , 'load_textdomain' ) );
		add_action( 'init' , array( &$this , 'init' ) );
		register_activation_hook( __FILE__ , array( __CLASS__ , 'activate' ) );
		register_deactivation_hook( __FILE__ , array( __CLASS__ , 'deactivate' ) );
		register_uninstall_hook( __FILE__ , array( __CLASS__ , 'uninstall' ) );
	}

	/**
	 * Load text domain
	 */
	public function load_textdomain() {
		load_plugin_textdomain( 'wp-post-babylon' , false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
	}
	/**
	 * Init hook.
	 * 
	 *  - Register assets
	 */
	function init() {
	}



	/**
	 *	Fired on plugin activation
	 */
	public static function activate() {
	
	
	}

	/**
	 *	Fired on plugin deactivation
	 */
	public static function deactivate() {
	}
	/**
	 *
	 */
	public static function uninstall(){
		delete_option( 'post_babylon_setting_1' );



	}

}
PostBabylon::get_instance();

endif;

if ( is_admin() ) {
	require_once plugin_dir_path(__FILE__) . 'include/class-PostBabylonAdmin.php';
	require_once plugin_dir_path(__FILE__) . 'include/class-PostBabylonSettings.php';
}
