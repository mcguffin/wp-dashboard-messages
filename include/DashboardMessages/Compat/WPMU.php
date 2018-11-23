<?php
/**
 *	@package DashboardMessages\Compat
 *	@version 1.0.0
 *	2018-09-22
 */

namespace DashboardMessages\Compat;

if ( ! defined('ABSPATH') ) {
	die('FU!');
}


use DashboardMessages\Core;
use DashboardMessages\PostType;


class WPMU extends Core\PluginComponent {

	/**
	 *	@inheritdoc
	 */
	protected function __construct() {
		if ( is_main_site() ) {

			add_filter('dashboard_messages_query', array( $this, 'exclude_network_args' ) );

			if ( current_user_can('manage_network_users') ) {

				add_action( 'save_post_dashboard_message', array( $this ,'save_post') ,10,2);

				add_action( 'dashboard_messages_metabox', array( $this, 'metabox' ));

				add_filter( 'manage_dashboard_message_posts_columns' , array( $this , 'add_scope_column'));
				add_filter( 'manage_dashboard_message_posts_custom_column' , array( $this , 'manage_scope_column') , 10 ,2 );
			}
		}
		add_filter('dashboard_messages', array( $this, 'add_network_messages' ) );
	}

	/**
	 *	@filter manage_dashboard_message_posts_columns
	 */
	public function add_scope_column( $columns ) {
		$columns['scope'] 	= __('Scope','wp-dashboard-messages');
		return $columns;
	}

	/**
	 *	@filter manage_dashboard_message_posts_custom_column
	 */
	public function manage_scope_column( $column , $post_ID ) {
		if ( $column == 'scope' ) {
			if ( get_post_meta( $post_ID , '_dashboard_network_wide' , true ) ) {
				?>
				<span class="dashicons dashicons-admin-site"></span>
				<?php _e('Network','wp-dashboard-messages'); ?>
				<?php
			} else {
				?>
				<span class="dashicons dashicons-admin-home"></span>
				<?php _e('This Blog','wp-dashboard-messages'); ?>
				<?php
			}
		}
	}

	/**
	 *	Save Post settings.
	 *	Fires on 'save_post_{$post_type}'
	 *
	 *	@use private
	 *
	 *	@param int  	$post_ID   ID of Current post being edited
	 *	@param object	$post	   Current post object being edited
	 */
	public function save_post( $post_ID, $post ) {

		if ( isset( $_POST['_dashboard_network_wide'] ) ) {
			update_post_meta( $post_ID , '_dashboard_network_wide' , absint( $_POST['_dashboard_network_wide'] ) );
		}
	}

	/**
	 *	@action dashboard_messages_metabox
	 */
	public function metabox( $post ) {
		?>
		<div class="dashboard-messages-scope">
			<h4><?php _e('Scope','wp-dashboard-messages') ?></h4>
			<?php

			// show 'all_blogs'
			$post_network_wide = get_post_meta( $post->ID , '_dashboard_network_wide' , true );

			?>
			<div class="dashboard-messages-select-radio">
				<?php
				printf( '<input type="radio" id="local-scope" name="_dashboard_network_wide" value="" %s />', checked( (bool) $post_network_wide, false, false ) );
				?>
				<label for="local-scope" >
					<?php
					_e('Show message only on this blog.','wp-dashboard-messages');
					?>
				</label>
			</div>

			<div class="dashboard-messages-select-radio">
				<?php
				printf( '<input type="radio" id="network-scope" name="_dashboard_network_wide" value="1" %s />', checked( (bool) $post_network_wide, true, false ) );
				?>

				<label for="network-scope" >
					<?php
					_e('Show message on the entire network.','wp-dashboard-messages');
					?>
				</label>
			</div>
		</div>
		<?php

	}

	/**
	 *	@filter dasboard_messages_query
	 */
	public function exclude_network_args( $query_args ) {
		// exclude network messages
		$query_args['meta_query'] = array(
			'key' => '_dashboard_network_wide',
			'value' => '1',
			'compare' => '!=',
		);
		return $query_args;
	}


	/**
	 *	@filter dasboard_messages_query
	 */
	public function network_only_args( $query_args ) {
		$query_args['meta_query'] = array(
			'key' => '_dashboard_network_wide',
			'value' => '1',
			'compare' => '=',
		);
		return $query_args;
	}


	public function add_network_messages( $posts ) {

		$main_blog_id = get_network()->site_id;

		if ( get_current_blog_id() === $main_blog_id ) {
			return $posts;
		}

		$posttype = PostType\PostTypeDashboardMessage::instance();

		switch_to_blog( $main_blog_id );

		add_filter('dashboard_messages_query', array( $this, 'network_only_args' ) );

		$network_posts = $posttype->get_posts( );

		$posts = array_merge( $network_posts , $posts );

		remove_filter('dashboard_messages_query', array( $this, 'network_only_args' ) );

		restore_current_blog();

		return $posts;
	}


	/**
	 *	@inheritdoc
	 */
	public function activate(){
	}

	 /**
	  *	@inheritdoc
	  */
	 public function deactivate(){

	 }

	 /**
	  *	@inheritdoc
	  */
	 public static function uninstall() {
		 // remove content and settings
	 }

	/**
 	 *	@inheritdoc
	 */
	public function upgrade( $new_version, $old_version ) {
	}

}
