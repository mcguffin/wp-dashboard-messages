<?php
/**
* @package DashboardMessages
*/


if ( ! class_exists( 'DashboardMessagesEditPost' ) ) :
class DashboardMessagesEditPost {
	private static $_instance = null;
	private $_messages = null;
	
	/**
	 * Getting a singleton.
	 *
	 * @return object single instance of DashboardMessagesEditPost
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
		add_action('admin_init',array( &$this ,'admin_init' ) );
		
		add_action('add_meta_boxes',array( &$this ,'add_meta_boxes' ) );
		add_action('wp_dashboard_setup',array( &$this ,'show_messages' ) , 1 );

	}
	
	// --------------------------------
	//	Basics
	// --------------------------------

	/**
	 *	Register admin hooks. Fires on 'admin_init'
	 *
	 *	@use private
	 */
	public function admin_init() {
		// add color meta box to post_type
		add_action( 'load-post.php' , array( &$this  , 'admin_load_editor') , 10, 1 );
		add_action( 'load-post-new.php' , array( &$this  , 'admin_load_editor') , 10, 1 );
		add_action( 'load-index.php' , array( &$this  , 'admin_load_dashboard') , 10, 1 );
		add_action( 'edit_post' , array( &$this ,'edit_post') ,10,2);

		if ( is_multisite() && current_user_can('manage_sites') && is_main_site() ) {
			add_filter('manage_dashboard_message_posts_columns' , array(&$this , 'add_scope_column'));
			add_filter('manage_dashboard_message_posts_custom_column' , array(&$this , 'manage_scope_column') , 10 ,2 );
		}
	}
	
	public function add_scope_column( $columns ) {
		$columns['scope'] = __('Scope','dashboardmessages');
		return $columns;
	}
	public function manage_scope_column( $column , $post_ID ) {
		if ( $column == 'scope' ) {
			if ( get_post_meta( $post_ID , '_dashboard_network_wide' , true) ) {
				?><span class="dashicons dashicons-admin-site"></span><?php
			} else {
				?><span class="dashicons dashicons-admin-home"></span><?php
			}
		}
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

	// --------------------------------
	//	Editing messages
	// --------------------------------
	
	/**
	 *	Add Meta Box to post edit screen. Fires on 'add_meta_boxes'
	 *
	 *	@use private
	 */
	public function add_meta_boxes( ) {
		add_meta_box( 'dashboard_options' , __( 'Dashboard' ) , array( &$this , 'dashboard_meta_box') , 'dashboard_message' , 'side' , 'default' );
	}
	/**
	 *	The Dashboard Message Editor Meta Box.
	 *
	 *	@param WP_Post  $post   Current post being edited
	 *
	 *	@use private
	 */
	public function dashboard_meta_box(  $post ) {
		// show select color
		// 
		$post_color = get_post_meta( $post->ID , '_dashboard_color' , true );
		$post_icon = get_post_meta( $post->ID , '_dashboard_icon' , true );

		?><div class="misc-pub-section"><?php
			?><h2><?php _e('Background Color') ?></h2><?php
			foreach ( $this->get_color_schemes() as $code => $item ) {
				extract( $item );
				if ( $background && $color ) {
					?><div class="colorselect" style="background:<?php echo $background ?>;color:#<?php echo $color ?>"><?php
					?><input type="radio" name="_dashboard_color" value="<?php echo $code ?>" id="colorselect<?php echo $code ?>" <?php checked( $code , $post_color , true ) ?> /><?php
				} else { // default
					?><div class="colorselect"><?php
					?><input type="radio" name="_dashboard_color" value="<?php echo $code ?>" id="colorselect" <?php checked( $code , $post_color , true ) ?> /><?php
				}
					?><label for="colorselect<?php echo $code ?>"><span class="colorfield" style="color:<?php echo $color ?>"><?php echo $label ?></span></label><?php
				?></div><?php
			}
			?><div style="clear:both;"></div><?php
		?></div><?php
		// select dashicon, output '<span class="dashicons dashicons-megaphone"></span>' before title.
		$dashicons = $this->get_dashicons( );
		array_unshift($dashicons,'');
		
		?><div class="misc-pub-section"><?php
			?><h2><?php _e('Icon') ?></h2><?php
			
			?><div class="select-dashicon-wrap"><?php
				?><div class="select-dashicon"><?php
				foreach($dashicons as $icon) {
					$icon_label = ucwords( implode(' ',explode('-',$icon) ));
					?><input type="radio" name="_dashboard_icon" value="<?php echo $icon ?>" id="dashicon-<?php echo $icon ?>" <?php checked( $icon , $post_icon , true ) ?> /><?php
					?><label for="dashicon-<?php echo $icon ?>" class="dashicons dashicons-<?php echo $icon ?>" title="<?php echo $icon_label ?>"></label><?php
				}
				?></div><?php
			?></div><?php
		?></div><?php
		
		if ( is_multisite() && is_main_site() && current_user_can('manage_sites') ) {
			?><div class="misc-pub-section"><?php
				?><h2><?php _e('Scope','dashboardmessages') ?></h2><?php
				// show 'all_blogs' 
				$post_network_wide = get_post_meta( $post->ID , '_dashboard_network_wide' , true );

				?><input type="radio" name="_dashboard_network_wide" value="" id="local-scope" <?php checked( (bool) $post_network_wide , false , true ) ?> /><?php
				?><label for="local-scope" > <?php _e('Show message only on this blog.','dashboardmessages') ?></label><br /><?php

				?><input type="radio" name="_dashboard_network_wide" value="1" id="network-scope" <?php checked( (bool) $post_network_wide , true , true ) ?> /><?php
				?><label for="network-scope"> <?php _e('Show message on the entire network.','dashboardmessages') ?></label><br /><?php
			
			?></div><?php
		}
	}
	/**
	 *	Save Post settings.
	 *	Fires on 'edit_post'
	 *
	 *	@use private
	 *
	 *	@param int  	$post_ID   ID of Current post being edited
	 *	@param object	$post	   Current post object being edited
	 */
	public function edit_post( $post_ID, $post ) {
		if ( $post->post_type != 'dashboard_message' )
			return;
		
		if ( isset( $_POST['_dashboard_color'] ) )
			update_post_meta( $post_ID , '_dashboard_color' , $_POST['_dashboard_color'] );

		if ( isset( $_POST['_dashboard_icon'] ) )
			update_post_meta( $post_ID , '_dashboard_icon' , $_POST['_dashboard_icon'] );

		if ( is_multisite() && is_main_site() && current_user_can('manage_sites') && isset( $_POST['_dashboard_network_wide'] ) )
			update_post_meta( $post_ID , '_dashboard_network_wide' , (int) $_POST['_dashboard_network_wide'] );
	}
	
	/**
	 *	Styles for the edit post screen.
	 *
	 *	@use private
	 */
	public function admin_head_editor( ) {
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
		}
		.select-dashicon-wrap {
			position:relative;
		}
		.select-dashicon {
			background:#fff;
		}
		.select-dashicon.active {
			position:absolute;
			right:0;
			bottom:0;
			border:1px solid #f3f3f3;
			padding:10px;
			width:420px;
			max-height:420px;
			overflow:auto;
			z-index:999;
			box-shadow: 0px 1px 3px rgba(0, 0, 0, 0.2);
		}
		.select-dashicon .dashicons {
			font-size:40px;
			width:40px;
			height:40px;
			padding:5px;
		}
		.select-dashicon input {
			display:none;
		}
		.select-dashicon.active label {
			display:inline-block;
		}
		.select-dashicon input ~ label {
			display:none;
		}
		.select-dashicon :checked + label {
			display:inline-block;
			background:#e5e5e5;
			
		}
		.select-dashicon label {
			position:relative;
		}
		.select-dashicon.active label {
			position:static;
		}
		.select-dashicon :checked + label:after {
			position:absolute;
			left:100%;
			bottom:0;
			white-space:nowrap;
			content:attr(title);
			font-size:14px;
			font-family:"Open Sans",sans-serif;
			padding:0.25em;
		}
		.select-dashicon.active [type="radio"] + label:after {
			display:none;
		}
		.select-dashicon input[type="radio"] + label:before {
			color:#888;
		}
		.select-dashicon input[type="radio"]:checked + label:before {
			color:#0074a2;
			color:#333;
		}
		</style><?php
		?><script type="text/javascript">
			(function($){
				$(document).on('click','.select-dashicon label,*',function(event){
					var $target = $(event.target);
					if ($target.has('.select-dashicon').length) {
						console.log('hide',event.target)
						$('.select-dashicon').removeClass('active');
					} else if ( $target.parents('.select-dashicon').length && $target.is('label') ) {
						$('.select-dashicon').toggleClass('active');
						event.stopPropagation();
					}
				});
			})(jQuery);
		</script><?php
	}
	
	
	
	
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
	 *	Get Dashicon names.
	 *
	 *	@return	array	Available Dashicons.
	 */
	private function get_dashicons( ) {
	 	$dashiconfile = plugin_dir_path( dirname(__FILE__) ) . 'js/dashicons.json';
		$icons = json_decode(file_get_contents( $dashiconfile ));
		return $icons;
	}
	
	
	
	// --------------------------------
	//	Showing the messages
	// --------------------------------

	/**
	 *	Hook Edit screen css.
	 */
	public function admin_load_editor( ) {
		add_action( 'admin_head' , array( &$this , 'admin_head_editor') , 10, 1 );
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

}

DashboardMessagesEditPost::instance();
endif;