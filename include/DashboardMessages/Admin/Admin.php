<?php
/**
 *	@package DashboardMessages\Admin
 *	@version 1.0.0
 *	2018-09-22
 */

namespace DashboardMessages\Admin;

if ( ! defined('ABSPATH') ) {
	die('FU!');
}

use DashboardMessages\Core;
use DashboardMessages\PostType;


class Admin extends Core\Singleton {

	private $core;

	/**
	 *	@inheritdoc
	 */
	protected function __construct() {

		$this->core = Core\Core::instance();

		add_action( 'wp_dashboard_setup', array( $this ,'show_messages' ) , 1 );
	//	add_action( 'load-index.php' , array( $this  , 'admin_load_dashboard') , 10, 1 );

		add_action( 'admin_init', array( $this , 'admin_init' ) );
		add_action( 'admin_enqueue_scripts', array( $this , 'enqueue_assets' ), 1 );


	}

	public function disable_block_editor( $is_enabled, $post_type ) {

	}


	/**
	 *	Show Messages on dashboard. Fires on action hook 'wp_dashboard_setup'
	 *	Retreives Messages for every Blog and adds a metabox for each Message.
	 *
	 *	@action load-index.php
	 *	@use private
	 */
	public function show_messages() {
		// if network switch to main blog, self::show_blog_messages();, switch back.

		$posttype = PostType\PostTypeDashboardMessage::instance();
		$posts = $posttype->get_posts();

		foreach ( $posts as $post ) {

			$content = apply_filters( 'the_content' , $post->post_content );

			$before_title = '';

			if ( $post->dashboard_icon ) {

				$before_title = '<span class="dashicons dashicons-'.$post->dashboard_icon.'"></span>';

			}
			add_meta_box( $post->dashboard_uid , $before_title . $post->post_title, array( $this , 'print_message_content' ) , 'dashboard' , 'normal' , 'high' , $post );
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

			if (current_user_can_for_blog( $post->blog_id , 'edit_post' , $post->ID ) ) {
				edit_post_link( __( 'Edit', 'wp-dashboard-messages' ) , '<p>' , '</p>' , $post->ID );
			}


			if ( is_multisite() ) {
				restore_current_blog( );
			}
		}
	}

	/**
	 *	Admin init
	 *	@action admin_init
	 */
	public function admin_init() {
	}

	/**
	 *	Enqueue options Assets
	 *	@action admin_print_scripts
	 */
	public function enqueue_assets() {
		$posttype = PostType\PostTypeDashboardMessage::instance();
		$posts = $posttype->get_posts();
		$rules = array();
		$color_schemes = Core\Core::instance()->get_color_schemes();
		$css = '/* Dashboard Messages Colors */'."\n";
		
		foreach ( $posts as $post ) {
			if ( ! $post->dashboard_color || ! isset( $color_schemes[ $post->dashboard_color ] ) ) {
				continue;
			}
			if ( ! isset( $rules[ $post->dashboard_color ] ) ) {
				$rules[ $post->dashboard_color ] = array(
					'selector'	=> array(),
					'css'		=> $color_schemes[ $post->dashboard_color ]['css'],
				);
			}
			$rules[ $post->dashboard_color ]['selector'][] = '#' . $post->dashboard_uid;
		}
		foreach ( $rules as $rule ) {
			$css .= sprintf('%s { %s }' . "\n", implode(',',$rule['selector']), $rule['css'] );
		}

		wp_add_inline_style( 'common', $css, 'after' );

		wp_enqueue_style( 'dashboard-messages-edit' , $this->core->get_asset_url( '/css/admin/edit.css' ) );

		wp_enqueue_script( 'dashboard-messages-edit' , $this->core->get_asset_url( 'js/admin/edit.js' ) );
		wp_localize_script('dashboard-messages-edit' , 'dashboard_messages_admin' , array(
		) );
	}

}
