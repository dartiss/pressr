<?php
/**
 * Admin functions
 * 
 * All admin functions rolled into one, easy to digest file
 *
 * @package Pressr
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Invalid request.' );
}

/**
 * Add Pressr settings
 *
 * Add a new sub-page to the settings menu
 */
function pressr_menu() {

	global $pressr_options_hook;

	$pressr_options_hook = add_submenu_page( 'options-general.php', __( 'Pressr Settings', 'pressr' ), __( 'Pressr', 'pressr' ), 'manage_options', 'pressr-options', 'pressr_options' );

	add_action( 'load-' . $pressr_options_hook, 'pressr_add_options_help' );

}

add_action( 'admin_menu', 'pressr_menu' );

/**
 * Add Options Help
 *
 * Add help tab to options screen
 *
 * @uses     ce_options_help    Return help text
 */
function pressr_add_options_help() {

	global $pressr_options_hook;
	$screen = get_current_screen();

	if ( $screen->id !== $pressr_options_hook ) {
		return;
	}

	$screen->add_help_tab(
		array(
			'id'      => 'pressr-options-help-tab',
			'title'   => __( 'Help', 'pressr' ),
			'content' => pressr_options_help(),
		)
	);

	$screen->set_help_sidebar( pressr_help_sidebar() );
}

/**
 * Code Embed Options
 *
 * Define an option screen
 */
function pressr_options() {

	include_once WP_PLUGIN_DIR . '/' . str_replace( basename( __FILE__ ), '', plugin_basename( __FILE__ ) ) . 'options.php';

}

/**
 * Code Embed Options Help
 *
 * Return help text for options screen
 *
 * @return   string  Help Text
 */
function pressr_options_help() {

	$help_text = '<p>' . __( 'Help content to go here', 'pressr' ) . '</p>';

	return $help_text;
}

/**
 * Code Embed Help Sidebar
 *
 * Return sidebar help text
 *
 * @return   string  Help Text
 */
function pressr_help_sidebar() {

	$help_text  = '<p><strong>' . __( 'For more information:', 'pressr' ) . '</strong></p>';
	$help_text .= '<p><a href="https://wordpress.org/plugins/pressr/">' . __( 'Instructions', 'pressr' ) . '</a></p>';
	$help_text .= '<p><a href="https://github.com/dartiss/pressr">' . __( 'Github (code and issues)', 'pressr' ) . '</a></p>';
	$help_text .= '<p><a href="https://wordpress.org/support/plugin/pressr">' . __( 'Support Forum', 'pressr' ) . '</a></p></h4>';

	return $help_text;
}
