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

		add_action( 'wp_dashboard_setup', [ $this, 'show_messages' ], 1 );

		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ], 1 );

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

			$content = apply_filters( 'the_content', $post->post_content );

			$context = $post->dashboard_context;
			if ( empty( $context ) ) {
				$context = 'normal';
			}

			$prio = $post->dashboard_priority;
			if ( empty( $prio ) ) {
				$prio = 'high';
			}

			$before_title = '';

			if ( $post->dashboard_icon ) {

				$before_title = '<span class="dashicons dashicons-' . $post->dashboard_icon . '"></span>';

			}
			add_meta_box( $post->dashboard_uid, $before_title . $post->post_title, [ $this, 'print_message_content' ], 'dashboard', $context, $prio, $post );
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
	public function print_message_content( $str, $param ) {

		$message_post = $param['args'];

		if ( $message_post ) {

			if ( is_multisite() ) {
				switch_to_blog( $message_post->blog_id );
			}
			if ( has_post_thumbnail( $message_post->ID ) ) {
				?>
				<div class="dashboard-message-thumbnail">
					<?php echo get_the_post_thumbnail( $message_post, 'full' ); ?>
				</div>
				<?php
			}
			$content = get_the_content( null, false, $message_post );
			echo wp_kses_post( apply_filters( 'the_content', $content ) );

			if ( current_user_can_for_blog( $message_post->blog_id, 'edit_post', $message_post->ID ) ) {
				edit_post_link( __( 'Edit', 'wp-dashboard-messages' ), '<p>', '</p>', $message_post->ID );
			}

			if ( 'dismissable' === $message_post->dashboard_layout ) {
				?>
				<button type="button" class="dashboard-message-dismiss notice-dismiss">
					<span class="screen-reader-text"><?php esc_html_e( 'Dismiss', 'wp-dashboard-messages' ); ?></span>
				</button>
				<?php
			}


			if ( is_multisite() ) {
				restore_current_blog( );
			}
		}
	}

	/**
	 *	Enqueue options Assets
	 *
	 *	@action admin_print_scripts
	 */
	public function enqueue_assets() {
		global $wp_version;
		
		$core = Core\Core::instance();
		$posttype = PostType\PostTypeDashboardMessage::instance();
		$posts = $posttype->get_posts();
		$rules = [];
		$color_schemes = Core\Core::instance()->get_color_schemes();
		$css = '/* Dashboard Messages Colors */' . "\n";

		foreach ( $posts as $post ) {
			if ( ! $post->dashboard_color || ! isset( $color_schemes[ $post->dashboard_color ] ) ) {
				continue;
			}
			if ( ! isset( $rules[ $post->dashboard_color ] ) ) {
				$rules[ $post->dashboard_color ] = [
					'selector'	=> [],
					'css'		=> $color_schemes[ $post->dashboard_color ]['css'],
				];
			}
			$rules[ $post->dashboard_color ]['selector'][] = '#' . $post->dashboard_uid;
		}
		foreach ( $rules as $rule ) {
			$css .= sprintf('%1$s { %2$s }' . "\n", implode( ',', $rule['selector'] ), $rule['css'] );
		}

		foreach ( $color_schemes as $scheme => $style ) {
			if ( empty( $style['css'] ) ) {
				continue;
			}
			$css .= sprintf(
				'.dashboard-colorset-%1$s { %2$s }' . "\n",
				sanitize_key( $scheme ),
				$style['css']
			);
		}

		wp_add_inline_style( 'common', $css, 'after' );
		$css_asset = '/css/admin/edit.css';
		if ( version_compare( $wp_version, '5.5.0', '<' ) ) {
			$css_asset = '/css/admin/edit-legacy.css';
		}
		wp_enqueue_style( 'dashboard-messages-edit', $this->core->get_asset_url( $css_asset ), [], $core->version() );

		wp_enqueue_script( 'dashboard-messages-edit', $this->core->get_asset_url( 'js/admin/edit.js' ), [], $core->version() );

	}

	/**
	 *	@param String $css
	 *	@return Array map css prop to value
	 */
	private function parse_css( $css ) {
		$styles = [];
		$css_props = array_filter( explode( ';', $css ) );
		foreach ( $css_props as $line ) {
			@list( $prop, $value ) = explode( ':', $line );
			if ( !isset( $value ) ) {
				continue;
			}
			$styles[trim($prop)] = trim($value);
		}
		return $styles;
	}

}
