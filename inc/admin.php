<?php
/**
 * Admin functions
 * 
 * All admin functions rolled into one, easy to digest file
 *
 * @package Pressr
 */

/**
 * Add meta to plugin details
 *
 * Add options to plugin meta line
 *
 * @param  string $links Current links.
 * @param  string $file  File in use.
 *
 * @return string        Links, now with settings added
 */
function pressr_add_plugin_meta( $links, $file ) {

	if ( strpos( $file, 'pressr.php' ) !== false ) {

		// Add link to Github repo.
		$links = array_merge( $links, array( '<a href="https://github.com/dartiss/pressr">' . __( 'Github', 'pressr' ) . '</a>' ) ); 

		// Add link to support forum.
		$links = array_merge( $links, array( '<a href="http://wordpress.org/support/plugin/pressr">' . __( 'Support', 'pressr' ) . '</a>' ) );

		// Add link to a donation page.
		$links = array_merge( $links, array( '<a href="https://artiss.blog/donate">' . __( 'Donate', 'pressr' ) . '</a>' ) );

		// Add link to review page.
		$links = array_merge( $links, array( '<a href="https://wordpress.org/support/plugin/pressr/reviews/#new-post">' . __( 'Write a Review', 'pressr' ) . '&nbsp;⭐️⭐️⭐️⭐️⭐️</a>' ) );
	}       

	return $links;
}

add_filter( 'plugin_row_meta', 'pressr_add_plugin_meta', 10, 2 );

/**
 * Add links to plugin actions
 *
 * Add useful links to the plugin actions
 *
 * @param  string $links Current links.
 * @param  string $file  File in use.
 *
 * @return string        Links, now with settings added.
 */
function pressr_add_plugin_actions( $links, $file ) {

	static $this_plugin;

	if ( ! $this_plugin ) {
		$this_plugin = plugin_basename( __FILE__ );
	}

	if ( strpos( $file, 'pressr.php' ) !== false ) {

		// Add link to settings page.
		array_push( $links, '<a href="admin.php?page=pressr-settings">' . __( 'Settings', 'pressr' ) . '</a>' );
	}

	return $links;
}

add_filter( 'plugin_action_links', 'pressr_add_plugin_actions', 10, 2 );
