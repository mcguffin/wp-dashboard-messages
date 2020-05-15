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
use DashboardMessages\Taxonomy;

abstract class PostType extends Core\PluginComponent {

	/**
	 *	@var string
	 */
	protected $post_type_slug = null;


	/**
	 *	@var null|array
	 */
	protected $post_type_caps = null;


	/**
	 *	@var boolean
	 */
	protected $enable_block_editor = true;

	/**
	 *	@inheritdoc
	 */
	protected function __construct() {

		parent::__construct();

		add_action( 'init', [ $this, 'register_post_types' ] );

		if ( ! $this->enable_block_editor ) {

			add_filter( 'use_block_editor_for_post_type', [ $this, 'disable_block_editor' ], 10, 2);

		}
	}


	/**
	 *	Filter block editor support
	 *
	 *	@param bool $is_enabled
	 *	@param string $post_type
	 *	@filter use_block_editor_for_post_type
	 */
	public function disable_block_editor( $is_enabled, $post_type ) {
		if ( $post_type === $this->get_slug() ) {
			return false;
		}
		return $is_enabled;
	}

	/**
	 *	@return string
	 */
	public function get_slug() {
		return $this->post_type_slug;
	}

	/**
	 *	Register Post Type
	 *
	 *	@action init
	 */
	abstract public function register_post_types();

	/**
	 *	@inheritdoc
	 */
	public function activate() {
		// register post types, taxonomies
		$this->register_post_types();

		// flush rewrite rules
		flush_rewrite_rules();

		return [
			'success'	=> true,
			'messages'	=> [],
		];
	}

	/**
	 *	@inheritdoc
	 */
	public function deactivate() {

		// flush rewrite rules
		flush_rewrite_rules();

		return [
			'success'	=> true,
			'messages'	=> [],
		];
	}

	/**
	 *	@inheritdoc
	 */
	public static function uninstall() {

		$deleted_posts = 0;
		$posts = get_posts( [
			'post_type' 		=> $this->post_type_slug,
			'post_status'		=> 'any',
			'posts_per_page'	=> -1,
		] );
		foreach ( $posts as $post ) {
			wp_delete_post( $post->ID, true );
			$deleted_posts++;
		}

		return [
			'success'	=> true,
			'messages'	=> [
				/* translators: %d number of posts deleted during uninstall */
				sprintf( _n( 'Deleted %d Post', 'Deleted %d Posts', $deleted_posts, 'dashboard-messages' ), $deleted_posts ),
			],
		];
	}

	/**
	 *	@inheritdoc
	 */
	public function upgrade( $new_version, $old_version ) {
	}



	/**
	 *	Add custom capabilities to admin role
	 *
	 *	@return boolean
	 */
	protected function add_custom_capabilities() {

		if ( ! is_null( $this->post_type_caps ) ) {

			$admin_role = get_role( 'administrator' );

			if ( ! is_null( $admin_role ) ) {
				foreach ( $this->post_type_caps as $cap ) {
					if ( ! $admin_role->has_cap( $cap ) ) {
						$admin_role->add_cap( $cap );
					}
				}
				return true;
			}
		}
		return false;
	}

	/**
	 *	Remove custom capabilities from all roles
	 */
	protected function remove_custom_capabilities() {
		// all roles!
		global $wp_roles;

		$roles = $wp_roles->roles;

		foreach ( array_keys( $wp_roles->roles ) as $role_slug ) {
			$role = get_role( $role_slug );
			foreach ( $this->_post_type_caps as $cap ) {
				if ( $role->has_cap( $cap ) ) {
					$role->remove_cap( $cap );
				}
			}
		}
	}

}
