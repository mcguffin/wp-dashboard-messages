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

	const NETWORK_META_KEY = '_dashboard_network_wide';

	/**
	 *	@inheritdoc
	 */
	protected function __construct() {

		if ( is_main_site() ) {

			//	add_filter( 'dashboard_messages_query', [ $this, 'exclude_network_args' ] );

			if ( current_user_can( 'manage_network_users' ) ) {

				add_action( 'save_post_dashboard_message', [ $this, 'save_post' ], 10, 2 );

				add_action( 'add_meta_boxes', [ $this, 'add_meta_boxes' ]);

				add_filter( 'manage_dashboard_message_posts_columns', [ $this, 'add_scope_column' ] );
				add_filter( 'manage_dashboard_message_posts_custom_column', [ $this, 'manage_scope_column' ], 10, 2 );
			}
		}

		add_filter( 'map_meta_cap', [ $this, 'map_meta_cap' ], 10, 4 );
		add_filter( 'dashboard_messages', [ $this, 'add_network_messages' ] );

	}


	/**
	 *	Add Meta Box to post edit screen.
	 *
	 *	@action add_meta_boxes
	 */
	public function add_meta_boxes() {
		add_meta_box( 'dashboard_network_options', __( 'Dashboard', 'wp-dashboard-messages' ), [ $this, 'dashboard_meta_box' ], 'dashboard_message', 'side', 'default' );
	}


	/**
	 *	Require network permissions to edit network messages.
	 *
	 *	@filter map_meta_cap
	 */
	public function map_meta_cap( $caps, $cap, $user_id, $args = [] ) {
		if ( 'edit_post' === $cap ) {
			// allow
			if ( current_user_can( 'manage_network_options' ) || ! count( $args ) ) {
				return $caps;
			}
			$post = get_post( $args[0] );
			if ( ! $post ) {
				return $caps;
			}
			if ( 'dashboard_message' !== $post->post_type ) {
				return $caps;
			}

			if ( 0 !== absint( get_post_meta( $post->ID, self::NETWORK_META_KEY, true ) ) ) {
				$caps[] = 'do_not_allow';
			}
		}
		return $caps;
	}

	/**
	 *	@filter manage_dashboard_message_posts_columns
	 */
	public function add_scope_column( $columns ) {
		$columns['scope'] = __( 'Scope', 'wp-dashboard-messages' );
		return $columns;
	}

	/**
	 *	@filter manage_dashboard_message_posts_custom_column
	 */
	public function manage_scope_column( $column, $post_ID ) {
		if ( 'scope' === $column ) {
			if ( get_post_meta( $post_ID, self::NETWORK_META_KEY, true ) ) {
				?>
				<span class="dashicons dashicons-admin-site"></span>
				<?php esc_html_e( 'Network', 'wp-dashboard-messages' ); ?>
				<?php
			} else {
				?>
				<span class="dashicons dashicons-admin-home"></span>
				<?php esc_html_e( 'This Blog', 'wp-dashboard-messages' ); ?>
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
	public function save_post( $post_id, $post ) {

		if ( ! check_ajax_referer( 'ms-save-dashboard-message-post-' . $post_id, '_ms_dashboard_post_nonce', false ) ) {
			return;
		}

		if ( isset( $_POST[ self::NETWORK_META_KEY ] ) ) {
			$val = wp_unslash( $_POST[ self::NETWORK_META_KEY ] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			update_post_meta( $post_id, self::NETWORK_META_KEY, absint( $val ) );
		}
	}

	/**
	 *	@action dashboard_messages_metabox
	 */
	public function dashboard_meta_box( $post ) {
		?>
		<div class="dashboard-messages-scope">
			<h4><?php esc_html_e( 'Scope', 'wp-dashboard-messages' ); ?></h4>
			<?php

			// show 'all_blogs'
			$post_network_wide = get_post_meta( $post->ID, self::NETWORK_META_KEY, true );

			?>
			<div class="dashboard-messages-select-radio">
				<?php
				wp_nonce_field( 'ms-save-dashboard-message-post-' . $post->ID, '_ms_dashboard_post_nonce' );
				printf(
					'<input type="radio" id="local-scope" name="%s" value="" %s />',
					esc_attr( self::NETWORK_META_KEY ),
					checked( (bool) $post_network_wide, false, false )
				);
				?>
				<label for="local-scope" >
					<?php
					esc_html_e ('Show message only on this blog.', 'wp-dashboard-messages' );
					?>
				</label>
			</div>

			<div class="dashboard-messages-select-radio">
				<?php
				printf( '<input type="radio" id="network-scope" name="%s" value="1" %s />',
					esc_attr( self::NETWORK_META_KEY ),
					checked( (bool) $post_network_wide, true, false )
				);
				?>

				<label for="network-scope">
					<?php
					esc_html_e( 'Show message on the entire network.', 'wp-dashboard-messages' );
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
		$query_args['meta_query'] = [
			[
				'key' => self::NETWORK_META_KEY,
				'value' => '1',
				'compare' => '!=',
			],
		];
		return $query_args;
	}


	/**
	 *	@filter dasboard_messages_query
	 */
	public function network_only_args( $query_args ) {
		$query_args['meta_query'] = [
			[
				'key' => self::NETWORK_META_KEY,
				'value' => '1',
			],
		];
		return $query_args;
	}


	public function add_network_messages( $posts ) {

		$main_blog_id = get_network()->site_id;

		if ( get_current_blog_id() === $main_blog_id ) {
			return $posts;
		}

		$posttype = PostType\PostTypeDashboardMessage::instance();

		switch_to_blog( $main_blog_id );

		add_filter( 'dashboard_messages_query', [ $this, 'network_only_args' ] );

		$network_posts = $posttype->get_posts( );

		$posts = array_merge( $network_posts, $posts );

		remove_filter( 'dashboard_messages_query', [ $this, 'network_only_args' ] );

		restore_current_blog();

		return $posts;
	}


	/**
	 *	@inheritdoc
	 */
	public function activate() {
	}

	 /**
	  *	@inheritdoc
	  */
	public function deactivate() {
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
		return [
			'success' => true,
			'message' => '',
		];
	}

}
