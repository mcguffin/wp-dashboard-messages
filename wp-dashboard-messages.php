<?php
/**
* @package DashboardMessages
*/

/*
Plugin Name: WP-Dashboard Messages
Plugin URI: https://github.com/mcguffin/wp-dashboard-messages
Description: Add messages to other Users dashboards.
Author: Jörn Lund
Version: 0.9.0
Author URI: https://github.com/mcguffin

Text Domain: dashboardmessages
Domain Path: /lang/
*/


if ( ! class_exists( 'DashboardMessages' ) ) :

class DashboardMessages {
	private static $_instance = null;
	private $_messages = null;
	
	/**
	 * Getting a singleton.
	 *
	 * @return object single instance of DashboardMessages
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) )
			self::$_instance = new self();
		return self::$_instance;
	}
	
	/**
	 * Prevent more than one instances
	 */
	private function __clone(){ }
	
	/**
	 * Private constructor
	 */
	private function __construct() {
		add_action( 'init' , array( &$this , 'register_post_type' ) );
		add_action( 'plugins_loaded' , array( &$this , 'load_plugin_textdomain' ) );
		
//		register_activation_hook();
	}
	/**
	 * Load plugin textdomain. Fired on 'plugins_loaded'
	 */
	public function load_plugin_textdomain( ) {
		load_plugin_textdomain( 'dashboardmessages' , false, dirname( plugin_basename( __FILE__ )) . '/lang');
	}
	
	/**
	 * Register Dashboard messages post type. Fired on 'init'
	 */
	public function register_post_type( ) {
		register_post_type( 'dashboard_message' , array( 
			'label' => __( 'Dashboard Messages' , 'dashboardmessages' ),
			'description' => __( 'With Dashboard Messages an Administrator can put up Messages to the WordPress dashboard.' , 'dashboardmessages' ),
			'public'			=> false,
			'has_archive'		=> false,

			'show_ui'			=> true,
			'show_in_menu'		=> true,
			'menu_position' 	=> 41,
			'menu_icon'			=> 'dashicons-megaphone',
			'capability_type'	=> 'post',
			'map_meta_cap'		=> true,
			'hierarchical'		=> false,
			'can_export' 		=> false,
			'supports' 			=> array(
				'title','editor','author'
			),
		) );
	}
}
DashboardMessages::instance();

endif;

if ( is_admin() )
	require_once( 'admin/class-DashboardMessagesAdmin.php' );


?>