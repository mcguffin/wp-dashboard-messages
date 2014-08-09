<?php
/**
* @package DashboardMessages
* @version 0.1
*/

/*
Plugin Name: WP-Dashboard Messages
Plugin URI: https://github.com/mcguffin/wp-dashboard-messages
Description: Add messages to other User's dashbards.
Author: Jörn Lund
Version: 0.9.0b
Author URI: https://github.com/mcguffin

Text Domain: dashboardmessages
Domain Path: /lang/



green: #A3B745
blue: #0074a2
orange: #d46f15
yellow: #ccaf0b
red #e14d43
cyan: #74B6CE

		√	__('Yellow') 
			__('Purple') 
	√		__('Red') 	
	√		__('Green') 
	√		__('Blue') 	
	√		__('Cyan') 	

*/


if ( ! class_exists( 'DashboardMessages' ) ) :
class DashboardMessages {
	private static $_messages = null;
	private static $colors;


	// --------------------------------
	//	Basics
	// --------------------------------
	static function init( ) {
		// register post type
		add_action('init',array(__CLASS__,'register_post_type' ) );
		add_action('add_meta_boxes',array(__CLASS__,'add_meta_boxes' ) );
		add_action('admin_init',array(__CLASS__,'admin_init' ) );
		add_action('wp_dashboard_setup',array(__CLASS__,'show_messages' ) , 1 );
		load_plugin_textdomain( 'dashboardmessages' , false, dirname( plugin_basename( __FILE__ )) . '/lang');
		self::$colors = array(
			""			=> array( 'label' => __('Default'),	"code"=>"" , "color" => "#f5f5f5,#f9f9f9"), // yellow
			"ccaf0b" 	=> array( 'label' => __('Yellow'),	"code"=>"ccaf0b" , "color" => "333"), // yellow
			"f5d5f5" 	=> array( 'label' => __('Purple'),	"code"=>"f5d5f5" , "color" => "000"), // purple
			"e14d43"	=> array( 'label' => __('Red'),		"code"=>"e14d43" , "color" => "fff"), // red
			"a3b745"	=> array( 'label' => __('Green'),	"code"=>"a3b745" , "color" => "fff"), // green
			"0074a2"	=> array( 'label' => __('Blue'),	"code"=>"0074a2" , "color" => "fff"), // blue
			"74B6CE"	=> array( 'label' => __('Cyan'),	"code"=>"74B6CE" , "color" => "000"), // cyan
		);
		
	}
	static function admin_init() {
		// add color meta box to post_type
		add_action( 'load-post.php' , array(__CLASS__ , 'admin_load_editor') , 10, 1 );
		add_action( 'load-post-new.php' , array(__CLASS__ , 'admin_load_editor') , 10, 1 );
		add_action( 'load-index.php' , array(__CLASS__ , 'admin_load_dashboard') , 10, 1 );
		add_action( 'edit_post' , array(__CLASS__,'edit_post') ,10,2);
	}
	
	
	
	private static function get_box_id( $post ) {
		$blog_id = get_current_blog_id();
		return "dashboard_message_{$blog_id}_{$post->ID}";
	}
	
	static function register_post_type( ) {
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
	
	
	
	
	// --------------------------------
	//	Editing messages
	// --------------------------------
	static function add_meta_boxes( ) {
		add_meta_box( 'dashboard_options' , __( 'Dashboard' ) , array(__CLASS__, 'dashboard_meta_box') , 'dashboard_message' , 'side' , 'default' );
	}
	static function dashboard_meta_box(  $post ) {
		// show select color
		// 
		$post_color = get_post_meta( $post->ID , '_dashboard_color' , true );

		
		?><div class="misc-pub-section"><?php
			?><h2><?php _e('Background Color') ?></h2><?php
			foreach ( self::$colors as $code=>$color ) {
				extract( $color );
				if ( $code ) {
					?><div class="colorselect" style="background-color:#<?php echo $code ?>;"><?php
					?><input type="radio" name="_dashboard_color" value="<?php echo $code ?>" id="colorselect<?php echo $code ?>" <?php checked($code,$post_color,true) ?> /><?php
				} else {
					?><div class="colorselect"><?php
					?><input type="radio" name="_dashboard_color" value="<?php echo $code ?>" id="colorselect" <?php checked( $code , $post_color , true ) ?> /><?php
				}
					?><label for="colorselect<?php echo $code ?>"><span class="colorfield" style="color:#<?php echo $label ?>"><?php echo $label ?></span></label><?php
				?></div><?php
			}
			?><div style="clear:both;"></div><?php
		?></div><?php
	
		if ( is_multisite() && is_main_site() ) {
			?><div class="misc-pub-section"><?php
				?><h2><?php _e('Scope','dashboardmessages') ?></h2><?php
				// show 'all_blogs' 
				$post_network_wide = get_post_meta( $post->ID , '_dashboard_network_wide' , true );

				?><input type="radio" name="_dashboard_network_wide" value="" id="local-scope" <?php checked( (bool) $post_network_wide , false , true ) ?> /><?php
				?><label for=local-scope" > <?php _e('Show message only on this blog.','dashboardmessages') ?></label><br /><?php

				?><input type="radio" name="_dashboard_network_wide" value="1" id="network-scope" <?php checked( (bool) $post_network_wide , true , true ) ?> /><?php
				?><label for="network-scope"> <?php _e('Show message on the entire network.','dashboardmessages') ?></label><br /><?php
			
			?></div><?php
		}
	}
	static function edit_post( $post_ID, $post ) {
		if ( $post->post_type != 'dashboard_message' )
			return;
		
		if ( isset( $_POST['_dashboard_color'] ) )
			update_post_meta( $post_ID , '_dashboard_color' , $_POST['_dashboard_color'] );

		if ( isset( $_POST['_dashboard_network_wide'] ) )
			update_post_meta( $post_ID , '_dashboard_network_wide' , (int) $_POST['_dashboard_network_wide'] );

	}
	
	static function admin_head_editor( ) {
		?><style type="text/css">
		/*
		Style ] page edit:
		*/
		#dashboard_options .inside {
			margin:0;
			padding:0;
		}
		#dashboard_options h2 {
			margin:0;
		}
		#dashboard_options .colorselect {
			float:left;
			width:46%;
			padding:1%;
			margin:2% 2% 0 0;
		}
		#dashboard_options .colorfield {
			display:inline-block;
			color:#666666;
			font-weight:bold;
			margin-left:0.25em;
		}		.
		</style><?php
	}
	
	
	
	
	
	
	
	// --------------------------------
	//	Showing the messages
	// --------------------------------
	static function admin_load_editor( ) {
		add_action( 'admin_head' , array(__CLASS__ , 'admin_head_editor') , 10, 1 );
	}
	
	static function show_messages() {
		// if network switch to main blog, self::show_blog_messages();, switch back.
		$posts = self::get_dashboard_messages( );
		foreach ( $posts as $post ) {
			$uid = self::get_box_id( $post );
			$content = apply_filters( 'the_content' , $post->post_content );
			$fnc_body = '$str = <<<EOT
' . $content . '
EOT;
echo $str;';
			if ( current_user_can_for_blog( $post->blog_id , 'edit_post' , $post->ID ) ) {
				if ( is_multisite() )
					$fnc_body .= "\nswitch_to_blog( {$post->blog_id} );";
				$fnc_body .= "\nedit_post_link( __('Edit this') , '<p>' , '</p>' , {$post->ID} );";
				if ( is_multisite() )
					$fnc_body .= "\nrestore_current_blog( );";
			}
			add_meta_box( $post->dashboard_uid , $post->post_title, create_function( '' , $fnc_body ) , 'dashboard' , 'normal' , 'high' );
		}
	}
	
	static function get_dashboard_messages( ) {
		// gets all posts from current blog
		if ( is_null( self::$_messages ) ) { // cache em
			$network_posts = self::_get_network_posts();
			$local_posts = self::_get_local_posts();
			self::$_messages = array_merge( $network_posts , $local_posts );
		}
		return self::$_messages;
	}
	private static function _get_local_posts() {
		if ( is_multisite() && is_main_site() ) {
			$posts = get_posts( array( 
					'posts_per_page' => -1,
					'post_type' => 'dashboard_message',
					'suppress_filters' => 0,
					'meta_query' => array(
						'key' => '_dashboard_network_wide',
						'value' => '1',
						'compare' => '!=',
					),
				) );
		} else if ( is_multisite() ) {
			$posts = get_posts('post_type=dashboard_message&suppress_filters=0');
		} else {
			$posts = get_posts('post_type=dashboard_message&suppress_filters=0&posts_per_page=-1');
		}
		self::_handle_posts($posts);
		return $posts;
	}
	private static function _get_network_posts() {
		if ( ! is_multisite() )
			return array();
		
		$old_id = get_current_blog_id();
		if ( ! is_main_site() ) 
			switch_to_blog( BLOG_ID_CURRENT_SITE );
		$network_posts = get_posts('post_type=dashboard_message&suppress_filters=0&meta_key=_dashboard_network_wide&meta_value=1&posts_per_page=-1');
		
		self::_handle_posts( $network_posts , BLOG_ID_CURRENT_SITE );
		
		if ($old_id != get_current_blog_id() ) 
			switch_to_blog( $old_id );
		
		return $network_posts;
	}
	private static function _handle_posts( &$posts , $blog_id = null ) {
		// add color & network wide uid
		if ( is_null( $blog_id ) )
			$blog_id = get_current_blog_id();
		foreach ($posts as $i=>$post) {
			$posts[$i]->blog_id = $blog_id;
			$posts[$i]->dashboard_uid = self::get_box_id( $post );
			$posts[$i]->dashboard_color = get_post_meta( $post->ID  ,'_dashboard_color' , true );
		}
	}
	
	static function admin_load_dashboard( ) {
		add_action( 'admin_head' , array(__CLASS__ , 'admin_head_dashboard') , 10, 1 );
	}
	static function admin_head_dashboard( ) {
		global $wpdb;
		$selectors = array();
		foreach ( self::$colors as $code => $item) {
			extract($item);
			if ( ! $code )
				continue;
			$selectors[$code] = array();
		}
		$posts = self::get_dashboard_messages();
		foreach ( $posts as $post ) {
			if ( !isset($selectors[$post->dashboard_color]) )
				continue;
			$uid = $post->dashboard_uid;
			$selectors[$post->dashboard_color][] = '#'.$uid;
		}

		?><style type="text/css">
		<?php
		
		foreach ($selectors as $code => $selector ) {
			if ( ! (bool) $selector )
				continue;
			extract(self::$colors[$code]);
			
			?>/* css generated by wp-dashboard-messages plugin */
			<?php echo implode(',',$selector) ?>{
	background: #<?php echo $code ?>;
	color: #<?php echo $color ?>;
			}
			<?php
			?>
			<?php echo implode(' h3,',$selector) ?> h3{
	background: #<?php echo $code ?>;
	color: #<?php echo $color ?>;
			}
			<?php
			?>
			<?php echo implode(' .hndle,',$selector) ?> .hndle{
	border-bottom-color: #<?php echo $color ?>;
			}
			<?php
			?>
			<?php echo implode(' .handlediv,',$selector) ?> .handlediv{
	background: #<?php echo $code ?>;
	color: #<?php echo $color ?>;
			}
			<?php
		}
		?></style><?php
	}
	
	
}
DashboardMessages::init();
endif;



?>