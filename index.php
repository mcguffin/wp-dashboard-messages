<?php

/*
Plugin Name: WP Dashboard Messages
Plugin URI: https://github.com/mcguffin/wp-dashboard-messages
Description: Show Messages on the WP Admin Dashboard.
Author: Jörn Lund
Version: 1.1.1
Author URI: https://github.com/mcguffin
License: GPL3
GitHub Plugin URI: mcguffin/wp-dashboard-messages
Requires WP: 4.8
Requires PHP: 5.6
Text Domain: wp-dashboard-messages
Domain Path: /languages/
*/

/*  Copyright 2018 Jörn Lund

	This program is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License, version 2, as
	published by the Free Software Foundation.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program; if not, write to the Free Software
	Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

/*
Plugin was generated by WP Plugin Scaffold
https://github.com/mcguffin/wp-plugin-scaffold
Command line args were: ``
*/


namespace DashboardMessages;

if ( ! defined('ABSPATH') ) {
	die('FU!');
}


if ( is_admin() || defined( 'DOING_AJAX' ) ) {

	require_once __DIR__ . DIRECTORY_SEPARATOR . 'include/autoload.php';

	Core\Core::instance( __FILE__ );
	PostType\PostTypeDashboardMessage::instance();
	Admin\Admin::instance();

}
