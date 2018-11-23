<?php
/**
 *	@package DashboardMessages\PostType
 *	@version 1.0.0
 *	2018-09-22
 */

namespace DashboardMessages\PostType;

if ( ! defined('ABSPATH') ) {
	die('FU!');
}

use DashboardMessages\Core;

class PostTypeDashboardMessage extends PostType {

	/**
	 *	@var string
	 */
	protected $post_type_slug = 'dashboard_message';

	/**
	 *	@var bool
	 */
	protected $enable_block_editor = false;

	/**
	 *	@var array
	 */
	private $_messages = null;



	/**
	 *	@inheritdoc
	 */
	protected function __construct( ) {

		parent::__construct();

		add_action('add_meta_boxes',array( $this ,'add_meta_boxes' ) );

		add_action( 'save_post_' . $this->get_slug(), array( $this ,'save_post') ,10,2);

	}


	/**
	 *	@inheritdoc
	 */
	public function register_post_types( ) {
		// register post type dashboard_message
		$labels = array(
			'name'                => _x( 'Dashboard Messages', 'Post Type General Name', 'wp-dashboard-messages' ),
			'singular_name'       => _x( 'Dashboard Message', 'Post Type Singular Name', 'wp-dashboard-messages' ),
			'menu_name'           => __( 'Dashboard Messages', 'wp-dashboard-messages' ),
			'parent_item_colon'   => __( 'Parent Message:', 'wp-dashboard-messages' ),
			'all_items'           => __( 'All Messages', 'wp-dashboard-messages' ),
			'view_item'           => __( 'View Message', 'wp-dashboard-messages' ),
			'add_new_item'        => __( 'Add New Message', 'wp-dashboard-messages' ),
			'add_new'             => __( 'Add New', 'wp-dashboard-messages' ),
			'edit_item'           => __( 'Edit Message', 'wp-dashboard-messages' ),
			'update_item'         => __( 'Update Message', 'wp-dashboard-messages' ),
			'search_items'        => __( 'Search Message', 'wp-dashboard-messages' ),
			'not_found'           => __( 'Not found', 'wp-dashboard-messages' ),
			'not_found_in_trash'  => __( 'Not found in Trash', 'wp-dashboard-messages' ),
		);


		$args = array(
			'label'					=> __( 'Dashboard Message', 'wp-dashboard-messages' ),
			'description'			=> __( 'With Dashboard Messages an Administrator can put up Messages to the WordPress dashboard.' , 'wp-dashboard-messages' ),
			'labels'				=> $labels,
			'supports'				=> array( 'title' , 'editor', 'author' ),
			'taxonomies'			=> array( ),
			'menu_icon'				=> 'dashicons-megaphone',
			'hierarchical'			=> false,
			'public'				=> false,
			'show_ui'				=> true,
			'show_in_menu'			=> true,
			'show_in_nav_menus'		=> false,
			'show_in_admin_bar'		=> false,
			'menu_position'			=> 41,
			'can_export'			=> false,
			'has_archive'			=> false,
			'exclude_from_search'	=> false,
			'publicly_queryable'	=> false,
			'capability_type'		=> 'page',
		);
		register_post_type( $this->post_type_slug, $args );
	}



	/**
	 *	Get local Dashboard Message Post objects
	 *	Will return local posts.
	 *
	 *	@return	array	Array containing local Dashboard Message post objects.
	 */
	public function get_posts() {

		if ( ! is_null( $this->_messages ) ) { // cache em
			return $this->_messages;
		}

		$get_posts_args = apply_filters( 'dashboard_messages_query', array(
			'posts_per_page'	=> -1,
			'post_type'			=> 'dashboard_message',
			'suppress_filters'	=> 0,
		) );

		$posts = get_posts( $get_posts_args );

		$posts = array_map( array( $this, 'handle_post' ), $posts );

		$this->_messages = apply_filters( 'dashboard_messages', $posts );


		return $this->_messages;
	}

	/**
	 *	Save Post settings.
	 *
	 *	@param int  	$post_ID   ID of Current post being edited
	 *	@param object	$post	   Current post object being edited
	 *
	 *	@action save_post_{$post_type}
	 */
	public function save_post( $post_ID, $post ) {

		$core = Core\Core::instance();
		$colors = $core->get_color_schemes();
		$dashicons = $core->get_dashicons();

		if ( isset( $_POST['_dashboard_color'] ) && isset( $colors[ $_POST['_dashboard_color'] ] ) ) {
			update_post_meta( $post_ID , '_dashboard_color' , $_POST['_dashboard_color'] );
		}

		if ( isset( $_POST['_dashboard_icon'] ) && ('' === $_POST['_dashboard_icon'] || isset( $dashicons[ $_POST['_dashboard_icon'] ] )) ) {
			// validate!
			update_post_meta( $post_ID , '_dashboard_icon' , $_POST['_dashboard_icon'] );
		}
	}

	/**
	 *	Add Meta Box to post edit screen. Fires on 'add_meta_boxes'
	 *
	 *	@use private
	 */
	public function add_meta_boxes( ) {
		add_meta_box( 'dashboard_options' , __( 'Dashboard', 'wp-dashboard-messages' ) , array( $this , 'dashboard_meta_box') , 'dashboard_message' , 'side' , 'default' );
	}

	/**
	 *	The Dashboard Message Editor Meta Box.
	 *
	 *	@param WP_Post  $post   Current post being edited
	 *
	 *	@use private
	 */
	public function dashboard_meta_box( $post ) {
		// show select color
		$core = Core\Core::instance();
		//
		$post_color = get_post_meta( $post->ID , '_dashboard_color' , true );
		$post_icon = get_post_meta( $post->ID , '_dashboard_icon' , true );

		?><div class="color-scheme">
			<h4><?php _e('Color Scheme','wp-dashboard-messages') ?></h4>
			<div class="dashboard-messages-colors">
				<?php
				foreach ( $core->get_color_schemes() as $code => $item ) {
					extract( $item );
					$inp_id = esc_attr('colorselect-'.$code);
					printf( '<input type="radio" name="_dashboard_color" id="%s" value="%s" %s >',
						$inp_id,
						esc_attr( $code ),
						checked( $code, $post_color, false )
					);
					printf( '<label for="%s" style="%s">%s</label>', $inp_id, $css, $label );
				}
				?>
			</div>
		</div><!-- .misc-pub-section -->
		<?php


		?>
		<hr />
		<div class="dashicon">
			<h4><?php _e('Icon','wp-dashboard-messages') ?></h4>
			<div class="dashboard-messages-icons select-window">
				<div class="select">
				<?php
				foreach ( array( '' => '') + $core->get_dashicons( ) as $icon => $codepoint ) {

					$inp_id = esc_attr('dashicon-' . $icon);
					$icon_label = $icon ? ucwords( implode(' ',explode('-',$icon) )) : __('No Icon','wp-dashboard-messages');

					printf( '<input type="radio" name="_dashboard_icon" id="%s" value="%s" %s >',
						$inp_id,
						esc_attr( $icon ),
						checked( $icon, $post_icon, false )
					);
					printf( '<label for="%s"><span  class="dashicons dashicons-%s"></span>%s</label>', $inp_id, $icon, $icon_label );
				}
				?>
				</div>
			</div>
		</div><!-- .misc-pub-section -->
		<?php

		do_action( 'dashboard_messages_metabox', $post );

		return;


	}


	/**
	 *	Add post meta values to dashboard post objects.
	 *
	 *	@param	array	&$posts		Array containing Dashboard Message post objects.
	 *	@param	id 		&$blog_id	current blog ID
	 */
	private function handle_post( $post ) {
		$blog_id = get_current_blog_id();
		$post->blog_id = $blog_id;
		$post->dashboard_uid = sprintf( 'dashboard-message-%d-%d', $blog_id, $post->ID );
		$post->dashboard_color = get_post_meta( $post->ID  ,'_dashboard_color' , true );
		$post->dashboard_icon = get_post_meta( $post->ID  ,'_dashboard_icon' , true );

		return apply_filters( 'dashboard_messages_handle_post', $post );
	}


}
