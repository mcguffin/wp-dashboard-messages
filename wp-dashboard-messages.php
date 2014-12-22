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

*/

/*  Copyright 2014  Jörn Lund  (email : joern AT podpirate DOT org)

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
		add_action('wp_dashboard_setup',array( &$this ,'show_messages' ) , 1 );
		add_action( 'load-index.php' , array( &$this  , 'admin_load_dashboard') , 10, 1 );
		
//		register_activation_hook();
	}
	/**
	 * Load plugin textdomain. Fired on 'plugins_loaded'
	 */
	public function load_plugin_textdomain( ) {
		load_plugin_textdomain( 'wp-dashboard-messages' , false, dirname( plugin_basename( __FILE__ )) . '/languages');
	}
	
	/**
	 * Register Dashboard messages post type. Fired on 'init'
	 */
	public function register_post_type( ) {
		register_post_type( 'dashboard_message' , array( 
			'label' => __( 'Dashboard Messages' , 'wp-dashboard-messages' ),
			'description' => __( 'With Dashboard Messages an Administrator can put up Messages to the WordPress dashboard.' , 'wp-dashboard-messages' ),
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
	// --------------------------------
	//	Showing the messages
	// --------------------------------

	/**
	 *	Get unique ID for Dashboard Message Metabox for HTML ID. 
	 *
	 *	@param object	$post	   Dashboard message Post
	 *	
	 *	@return	string	CSS selector safe 
	 */
	private function get_box_id( $post ) {
		$blog_id = get_current_blog_id();
		return "dashboard_message_{$blog_id}_{$post->ID}";
	}

	
	/**
	 *	Show Messages on dashboard. Fires on action hook 'wp_dashboard_setup'
	 *	Retreives Messages for every Blog and adds a metabox for each Message.
	 *	
	 *	@use private
	 */
	public function show_messages() {
		// if network switch to main blog, self::show_blog_messages();, switch back.
		$posts = $this->get_dashboard_messages( );
		foreach ( $posts as $post ) {
			$uid = $this->get_box_id( $post );
			$content = apply_filters( 'the_content' , $post->post_content );
			$post_icon = $post->dashboard_icon; 
			$before_title = '';
			if ( $post_icon )
				$before_title = '<span class="dashicons dashicons-'.$post_icon.'"></span>';
			add_meta_box( $post->dashboard_uid , $before_title . $post->post_title, array( &$this , 'print_message_content' ) , 'dashboard' , 'normal' , 'high' , $post );
		}
	}
	/**
	 *	Print dashboard Message content.
	 *	Callback on each Dashboard message Metabox.
	 *
	 *	@param	str		$str	Empty string.
	 *	@param	array	$param	Assoc passed from WP metabox handler. 
	 *							'id' 		=> Metabox ID,
	 *							'title'		=> $post_id,
	 *							'callback'	=> the callback,
	 *							'args'		=> Dashboard Message Post object,
	 */
	public function print_message_content( $str , $param ) {
		if ( $post = $param['args'] ) {
			if ( is_multisite() )
				switch_to_blog( $post->blog_id );
			echo apply_filters('the_content',$post->post_content);
		
			if (current_user_can_for_blog( $post->blog_id , 'edit_post' , $post->ID ) )
				edit_post_link( __('Edit this') , '<p>' , '</p>' , $post->ID );
		
		
			if ( is_multisite() )
				restore_current_blog( );
		}
	}
	
	/**
	 *	Get Dashboard Message Post objects
	 *	Will return local and network wide posts.
	 *
	 *	@return	array	Array containing Dashboard Message post objects.
	 */
	public function get_dashboard_messages( ) {
		// gets all posts from current blog
		if ( is_null( $this->_messages ) ) { // cache em
			$network_posts = $this->_get_network_posts();
			$local_posts = $this->_get_local_posts();
			$this->_messages = array_merge( $network_posts , $local_posts );
		}
		return $this->_messages;
	}
	
	/**
	 *	Get local Dashboard Message Post objects
	 *	Will return local posts.
	 *
	 *	@return	array	Array containing local Dashboard Message post objects.
	 */
	private function _get_local_posts() {
		// multisite main site
		$get_posts_args = array( 
			'posts_per_page' => -1,
			'post_type' => 'dashboard_message',
			'suppress_filters' => 0,
		);

		if ( is_multisite() && is_main_site() ) {
			$get_posts_args['meta_query'] = array( 
				'key' => '_dashboard_network_wide',
				'value' => '1',
				'compare' => '!=',
			);
		}
		$posts = get_posts( $get_posts_args );
		$this->_handle_posts($posts);
		return $posts;
	}
	/**
	 *	Get network wide Dashboard Message Post objects
	 *
	 *	@return	array	Array containing network wide Dashboard Message post objects.
	 */
	private function _get_network_posts() {
		if ( ! is_multisite() )
			return array();
		
		$old_id = get_current_blog_id();
		if ( ! is_main_site() ) 
			switch_to_blog( BLOG_ID_CURRENT_SITE );
		$get_posts_args = array( 
			'posts_per_page' => -1,
			'post_type' => 'dashboard_message',
			'suppress_filters' => 0,
			'meta_key' => '_dashboard_network_wide',
			'meta_value' => '1',
		);
		$network_posts = get_posts( $get_posts_args );
		$this->_handle_posts( $network_posts , BLOG_ID_CURRENT_SITE );
		
		if ($old_id != get_current_blog_id() ) 
			switch_to_blog( $old_id );
		
		return $network_posts;
	}
	/**
	 *	Add post meta values to dashboard post objects.
	 *
	 *	@param	array	&$posts		Array containing Dashboard Message post objects.
	 *	@param	id 		&$blog_id	current blog ID
	 */
	private function _handle_posts( &$posts , $blog_id = null ) {
		// add color & network wide uid
		if ( is_null( $blog_id ) )
			$blog_id = get_current_blog_id();
		foreach ($posts as $i=>$post) {
			$posts[$i]->blog_id = $blog_id;
			$posts[$i]->dashboard_uid = $this->get_box_id( $post );
			$posts[$i]->dashboard_color = get_post_meta( $post->ID  ,'_dashboard_color' , true );
			$posts[$i]->dashboard_icon = get_post_meta( $post->ID  ,'_dashboard_icon' , true );
		}
	}
	
	/**
	 *	Add Dashboard CSS to admin head. fired on 'load-index.php'
	 */
	public function admin_load_dashboard( ) {
		add_action( 'admin_head' , array( &$this , 'admin_head_dashboard') , 10, 1 );
	}
	
	/**
	 *	Return available color schemes.
	 *
	 *	@use public
	 *
	 *	@return array	Assoc containing all avaliable color schemes. 
	 *					keys: color scheme slug, values array with keys label (short 
	 *					descriptive name), background (css background value), color 
	 *					(text color, css color value).
	 *
	 */
	public function get_color_schemes() {
		$colors = array(
			""			=> array( 'label' => __('Default'),	"background"=>"" , 		  "color" => ""), // white
			"yellow" 	=> array( 'label' => __('Yellow'),	"background"=>"#ccaf0b" , "color" => "#333"), // yellow
			"purple" 	=> array( 'label' => __('Purple'),	"background"=>"#f5d5f5" , "color" => "#000"), // purple
			"red"		=> array( 'label' => __('Red'),		"background"=>"#e14d43" , "color" => "#fff"), // red
			"green"		=> array( 'label' => __('Green'),	"background"=>"#a3b745" , "color" => "#fff"), // green
			"blue"		=> array( 'label' => __('Blue'),	"background"=>"#0074a2" , "color" => "#fff"), // blue
			"cyan"		=> array( 'label' => __('Cyan'),	"background"=>"#74B6CE" , "color" => "#000"), // cyan
		);
		/**
		 * Filter available color schemes
		 *
		 * @param array  $colors   Color schemes (see function doc)
		 */
		return apply_filters( 'dashboardmessages_color_schemes' , $colors );
	}

	/**
	 *	Write Dashboard CSS in admin head. fired on 'admin_head' on wp-admin/index.php
	 */
	public function admin_head_dashboard( ) {
		global $wpdb;
		$selectors = array();
		$colors = $this->get_color_schemes();
		
		foreach ( $colors as $key => $item) {
			extract($item); // $label, $background, $color
			if ( ! $key )
				continue;
			$selectors[$key] = array();
		}
		$posts = $this->get_dashboard_messages();
		foreach ( $posts as $post ) {
			if ( !isset($selectors[$post->dashboard_color]) )
				continue;
			$uid = $post->dashboard_uid;
			$selectors[$post->dashboard_color][] = '#'.$uid;
		}

		?><style type="text/css">
		.postbox .hndle .dashicons {
			margin-right:0.5em;
			font-size:1.5em;
		}
		.postbox .inside figure,
		.postbox .inside img {
			max-width:100% !important;
			height:auto;
			margin-left:0;
			margin-right:0;
		}
		<?php
		
		foreach ($selectors as $key => $selector ) {
			if ( ! (bool) $selector )
				continue;
			extract($colors[$key]); // $label, $background, $color
			
			?>/* css generated by wp-dashboard-messages plugin */
			<?php echo implode(',',$selector) ?> {
	background: <?php echo $background ?>;
	color: <?php echo $color ?>;
			}
			<?php
			?>
			<?php echo implode(' h3,',$selector) ?> h3 {
	background: <?php echo $background ?>;
	color: <?php echo $color ?>;
			}
			<?php
			?>
			<?php echo implode(' .hndle,',$selector) ?> .hndle {
	border-bottom-color: <?php echo $color ?>;
			}
			<?php
			?>
			<?php echo implode(' .handlediv,',$selector) ?> .handlediv {
	background: <?php echo $background ?>;
	color: <?php echo $color ?>;
			}
			<?php
		}
		?></style><?php

	}
	// END output
}
DashboardMessages::instance();

endif;

if ( is_admin() )
	require_once( 'admin/class-DashboardMessagesEditPost.php' );


?>