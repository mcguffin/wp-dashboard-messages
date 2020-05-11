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
	protected function __construct() {

		parent::__construct();

		add_action( 'add_meta_boxes', [ $this, 'add_meta_boxes' ] );

		add_action( 'save_post_' . $this->get_slug(), [ $this, 'save_post' ], 10, 2 );

	}


	/**
	 *	@inheritdoc
	 */
	public function register_post_types() {
		// register post type dashboard_message
		$labels = [
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
		];

		$args = [
			'label'					=> __( 'Dashboard Message', 'wp-dashboard-messages' ),
			'description'			=> __( 'With Dashboard Messages an Administrator can put up Messages to the WordPress dashboard.', 'wp-dashboard-messages' ),
			'labels'				=> $labels,
			'supports'				=> [ 'title', 'editor', 'author', 'thumbnail' ],
			'taxonomies'			=> [],
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
		];

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

		$get_posts_args = apply_filters( 'dashboard_messages_query', [
			'posts_per_page'	=> -1,
			'post_type'			=> 'dashboard_message',
			'suppress_filters'	=> 0,
		] );

		$posts = get_posts( $get_posts_args );

		$posts = array_map( [ $this, 'handle_post' ], $posts );

		$this->_messages = apply_filters( 'dashboard_messages', $posts );

		return $this->_messages;
	}

	/**
	 *	Save Post settings.
	 *
	 *	@param int  	$post_id   ID of Current post being edited
	 *	@param object	$post	   Current post object being edited
	 *
	 *	@action save_post_{$post_type}
	 */
	public function save_post( $post_id, $post ) {

		if ( ! check_ajax_referer( 'save-dashboard-message-post-' . $post_id, '_dashboard_post_nonce', false ) ) {
			return;
		}

		$core = Core\Core::instance();
		$colors = $core->get_color_schemes();

		$param = wp_unslash( wp_parse_args( $_POST, [
			'_dashboard_color'		=> false,
			'_dashboard_icon'		=> false,
			'_dashboard_context'	=> false,
			'_dashboard_priority'	=> false,
		]));

		$dashboard_color	= wp_unslash( $param['_dashboard_color'] );
		$dashboard_icon		= wp_unslash( $param['_dashboard_icon'] );
		$dashboard_context	= wp_unslash( $param['_dashboard_context'] );
		$dashboard_priority	= wp_unslash( $param['_dashboard_priority'] );

		$dashboard_color	= $this->sanitize_color( $dashboard_color );
		$dashboard_icon		= $this->sanitize_icon( $dashboard_icon );
		$dashboard_context	= $this->sanitize_context( $dashboard_context );
		$dashboard_priority	= $this->sanitize_priority( $dashboard_priority );

		update_post_meta( $post_id, '_dashboard_color', $dashboard_color );
		update_post_meta( $post_id, '_dashboard_icon', $dashboard_icon );
		update_post_meta( $post_id, '_dashboard_context', $dashboard_context );
		update_post_meta( $post_id, '_dashboard_priority', $dashboard_priority );

	}

	/**
	 *	Add Meta Box to post edit screen.
	 *
	 *	@action add_meta_boxes
	 */
	public function add_meta_boxes() {

		add_meta_box( 'dashboard_options', __( 'Dashboard', 'wp-dashboard-messages' ), [ $this, 'dashboard_meta_box' ], 'dashboard_message', 'side', 'default' );

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
		$post_color = get_post_meta( $post->ID, '_dashboard_color', true );
		$post_icon = get_post_meta( $post->ID, '_dashboard_icon', true );

		wp_nonce_field( 'save-dashboard-message-post-' . $post->ID, '_dashboard_post_nonce' );

		?><div class="color-scheme">
			<h4><?php esc_html_e( 'Color Scheme', 'wp-dashboard-messages' ); ?></h4>
			<div class="dashboard-messages-colors">
				<?php

				foreach ( $core->get_color_schemes() as $code => $item ) {
					$inp_id = 'colorselect-' . $code;
					printf( '<input type="radio" name="_dashboard_color" id="%s" value="%s" %s >',
						esc_attr( $inp_id ),
						esc_attr( $code ),
						checked( $code, $post_color, false )
					);
					printf( '<label for="%s" class="dashboard-colorset-%s">%s</label>',
						esc_attr( $inp_id ),
						esc_attr( $code ),
						esc_html( $item['label'] )
					);
				}
				?>
			</div>
		</div><!-- .misc-pub-section -->
		<hr />
		<div class="dashicon">
			<h4><?php esc_html_e( 'Icon', 'wp-dashboard-messages' ); ?></h4>
			<div class="dashboard-messages-icons select-window">
				<div class="select">
				<?php
				foreach ( [ '' => '' ] + $core->get_dashicons( ) as $icon => $codepoint ) {

					$inp_id = 'dashicon-' . $icon;
					$icon_label = $icon ? ucwords( implode( ' ', explode( '-', $icon ) ) ) : __( 'No Icon', 'wp-dashboard-messages' );

					printf( '<input type="radio" name="_dashboard_icon" id="%s" value="%s" %s >',
						esc_attr( $inp_id ),
						esc_attr( $icon ),
						checked( $icon, $post_icon, false )
					);
					printf(
						'<label for="%s"><span  class="dashicons dashicons-%s"></span>%s</label>',
						esc_attr( $inp_id ),
						esc_attr( $icon ),
						esc_html( $icon_label )
					);
				}
				?>
				</div>
			</div>
		</div><!-- .icon -->
		<div class="dashboard-messages-placements">
			<div class="context">
				<h4><?php esc_html_e( 'Context', 'wp-dashboard-messages' ); ?></h4>
				<?php

				// show 'all_blogs'
				$post_context = get_post_meta( $post->ID, '_dashboard_context', true );
				if ( empty( $post_context ) ) {
					$post_context = 'normal';
				}
				$contexts = [
					'normal'	=> __( 'Normal', 'wp-dashboard-messages' ),
					'side'		=> __( 'Side', 'wp-dashboard-messages' ),
					'column3'	=> __( 'Column 3', 'wp-dasboard-messages' ),
					'column4'	=> __( 'Column 4', 'wp-dasboard-messages' ),
				];
				foreach ( $contexts as $value => $label ) {
					?>
					<div class="dashboard-messages-select-radio">
						<?php
						printf(
							'<input type="radio" id="context-%1$s" name="_dashboard_context" value="%1$s" %2$s />',
							esc_attr( $value ),
							checked( $post_context, $value, false )
						);
						?>
						<label for="context-<?php echo esc_attr( $value ); ?>" >
							<?php
							echo esc_html( $label );
							?>
						</label>
					</div>
					<?php

				}

				?>
			</div>

			<div class="priority">
				<h4><?php esc_html_e( 'Priority', 'wp-dashboard-messages' ); ?></h4>
				<?php

				// show 'all_blogs'
				$post_prio = get_post_meta( $post->ID, '_dashboard_priority', true );
				if ( empty( $post_prio ) ) {
					$post_prio = 'high';
				}
				$prios = [
					'high'		=> __( 'High', 'wp-dashboard-messages' ),
					'default'	=> __( 'Default', 'wp-dashboard-messages' ),
					'low'		=> __( 'Low', 'wp-dashboard-messages' ),
				];
				foreach ( $prios as $value => $label ) {
					?>
					<div class="dashboard-messages-select-radio">
						<?php
						printf(
							'<input type="radio" id="context-%1$s" name="_dashboard_priority" value="%1$s" %2$s />',
							esc_attr( $value ),
							checked( $post_prio, $value, false )
						);
						?>
						<label for="context-<?php echo esc_attr( $value ); ?>" >
							<?php echo esc_html( $label ); ?>
						</label>
					</div>
					<?php

				}

				?>
			</div>
		</div>

		<?php

		do_action( 'dashboard_messages_metabox', $post );
	}


	/**
	 *	Add post meta values to dashboard post objects.
	 *
	 *	@param	array	$posts		Array containing Dashboard Message post objects.
	 *	@param	id 		$blog_id	current blog ID
	 */
	private function handle_post( $post ) {

		$blog_id = get_current_blog_id();

		$post->blog_id 				= $blog_id;
		$post->dashboard_uid		= sprintf( 'dashboard-message-%d-%d', $blog_id, $post->ID );
		$post->dashboard_color		= get_post_meta( $post->ID, '_dashboard_color', true );
		$post->dashboard_icon		= $this->sanitize_icon( get_post_meta( $post->ID, '_dashboard_icon', true ) );
		$post->dashboard_context	= $this->sanitize_context( get_post_meta( $post->ID, '_dashboard_context', true ) );
		$post->dashboard_priority	= $this->sanitize_priority( get_post_meta( $post->ID, '_dashboard_priority', true ) );

		return apply_filters( 'dashboard_messages_handle_post', $post );
	}

	/**
	 *	@param string $priority
	 *	@return string
	 */
	public function sanitize_priority( $priority ) {
		if ( in_array( $priority, [ 'high', 'default', 'low' ] ) ) {
			return $priority;
		}
		return 'high';
	}

	/**
	 *	@param string $context
	 *	@return string
	 */
	public function sanitize_context( $context ) {
		if ( in_array( $context, [ 'normal', 'side', 'column3', 'column4' ] ) ) {
			return $context;
		}
		return 'normal';
	}

	/**
	 *	@param string $icon
	 *	@return string
	 */
	public function sanitize_icon( $icon ) {
		$core = Core\Core::instance();
		$icons = $core->get_dashicons();
		if ( is_array( $icons ) && isset( $icons[ $icon ] ) ) {
			return $icon;
		}
		return '';
	}

	/**
	 *	@param string $color
	 *	@return string
	 */
	public function sanitize_color( $color ) {
		$core = Core\Core::instance();
		$colors = $core->get_color_schemes();
		if ( is_array( $colors ) && isset( $colors[ $color ] ) ) {
			return $color;
		}
		return '';
	}

}
