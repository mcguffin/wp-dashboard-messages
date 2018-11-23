<?php
/**
 *	@package DashboardMessages\Core
 *	@version 1.0.1
 *	2018-09-22
 */

namespace DashboardMessages\Core;

if ( ! defined('ABSPATH') ) {
	die('FU!');
}

use DashboardMessages\Compat;
use DashboardMessages\PostType;

class Core extends Plugin {

	/**
	 *	@inheritdoc
	 */
	protected function __construct() {

		add_action( 'plugins_loaded' , array( $this , 'init_compat' ), 0 );
		add_action( 'init' , array( $this , 'init' ) );

		add_action( 'wp_enqueue_scripts' , array( $this , 'wp_enqueue_style' ) );

		$args = func_get_args();
		parent::__construct( ...$args );
	}


	/**
	 *	Return available color schemes.
	 *
	 *	@use public
	 *
	 *	@return array	Assoc containing all available color schemes.
	 *					array(
	 *						'color-scheme-slug'	=> array(
	 *							'label'	=> 'Funky',
	 *							'css'	=> 'color:tamato;backgrond:cucumber;'
	 *						)
	 *					)
	 *
	 */
	public function get_color_schemes() {

		$wp_blue			= '#0085ba';
		$wp_blue_light		= '#00a0d2';
		$wp_blue_lighter	= '#e5f5fa';
		$wp_green			= '#46b450';
		$wp_green_lighter	= '#ecf7ed';
		$wp_red				= '#dc3232';
		$wp_red_lighter		= '#f1cccc';
		$wp_yellow			= '#ffb900';
		$wp_yellow_lighter	= '#fff8e5';
		$wp_black			= '#23282d';
		$wp_gray_darker		= '#32373c';
		$wp_gray_dark		= '#cccccc';
		$wp_gray			= '#f1f1f1';
		$wp_gray_lighter	= '#f9f9f9';
		$wp_gray_lightest	= '#fafafa';
		$wp_white			= '#ffffff';


		$colors = array(
			''			=> array( 'label' => __( 'Default', 'wp-dashboard-messages' ),	'css' => ''), // white
			'success'	=> array( 'label' => __( 'Success', 'wp-dashboard-messages' ),	'css' => "border-left:4px solid {$wp_green};"), // green
			'info'		=> array( 'label' => __( 'Info', 'wp-dashboard-messages' ),		'css' => "border-left:4px solid {$wp_blue};"), // red
			'warning' 	=> array( 'label' => __( 'Warning', 'wp-dashboard-messages' ),	'css' => "border-left:4px solid {$wp_yellow};"), // yellow
			'error' 	=> array( 'label' => __( 'Error', 'wp-dashboard-messages' ),	'css' => "border-left:4px solid {$wp_red};"), // purple
			'yellow' 	=> array( 'label' => __( 'Yellow', 'wp-dashboard-messages' ),	'css' => "background-color:{$wp_yellow};color:{$wp_white};"), // yellow
			'purple' 	=> array( 'label' => __( 'Purple', 'wp-dashboard-messages' ),	'css' => "background-color:{$wp_red_lighter};color:{$wp_black};"), // purple
			'red'		=> array( 'label' => __( 'Red', 'wp-dashboard-messages' ),		'css' => "background-color:{$wp_red};color:{$wp_white};"), // red
			'green'		=> array( 'label' => __( 'Green', 'wp-dashboard-messages' ),	'css' => "background-color:{$wp_green};color:{$wp_white};"), // green
			'blue'		=> array( 'label' => __( 'Blue', 'wp-dashboard-messages' ),		'css' => "background-color:{$wp_blue};color:{$wp_white};"), // blue
			'cyan'		=> array( 'label' => __( 'Cyan', 'wp-dashboard-messages' ),		'css' => "background-color:{$wp_blue_lighter};color:{$wp_black};"), // cyan
		);

		/**
		 * Filter available color schemes
		 *
		 * @param array  $colors   Color schemes (see function doc)
		 */
		return apply_filters( 'dashboard_messages_color_schemes' , $colors );
	}



	/**
	 *	Get Dashicon names.
	 *
	 *	@return	array	Available Dashicons.
	 */
	public function get_dashicons( ) {
		$icons = json_decode( file_get_contents( $this->get_asset_path( 'misc/dashicons.json' ) ), true );
		return $icons;
	}

	/**
	 *	Load frontend styles and scripts
	 *
	 *	@action wp_enqueue_scripts
	 */
	public function wp_enqueue_style() {
	}


	/**
	 *	Load Compatibility classes
	 *
	 *  @action plugins_loaded
	 */
	public function init_compat() {
		if ( is_multisite() && function_exists('is_plugin_active_for_network') && is_plugin_active_for_network( $this->get_wp_plugin() ) ) {
			Compat\WPMU::instance();
		}

	}


	/**
	 *	Init hook.
	 *
	 *  @action init
	 */
	public function init() {
	}

	/**
	 *	Get asset url for this plugin
	 *
	 *	@param	string	$asset	URL part relative to plugin class
	 *	@return string URL
	 */
	public function get_asset_url( $asset ) {
		$pi = pathinfo($asset);
		if ( defined('SCRIPT_DEBUG') && SCRIPT_DEBUG && in_array( $pi['extension'], ['css','js']) ) {
			// add .dev suffix (files with sourcemaps)
			$asset = sprintf('%s/%s.dev.%s', $pi['dirname'], $pi['filename'], $pi['extension'] );
		}
		return plugins_url( $asset, $this->get_plugin_file() );
	}


	/**
	 *	Get asset url for this plugin
	 *
	 *	@param	string	$asset	URL part relative to plugin class
	 *	@return string URL
	 */
	public function get_asset_path( $asset ) {
		$pi = pathinfo($asset);
		if ( defined('SCRIPT_DEBUG') && SCRIPT_DEBUG && in_array( $pi['extension'], ['css','js']) ) {
			// add .dev suffix (files with sourcemaps)
			$asset = sprintf('%s/%s.dev.%s', $pi['dirname'], $pi['filename'], $pi['extension'] );
		}
		return $this->get_plugin_dir() . '/' . preg_replace( '/^(\/+)/', '', $asset );
		return plugins_url( $asset, $this->get_plugin_file() );
	}


}
