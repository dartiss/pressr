<?php
/*
Plugin Name: Pressr
Plugin URI: https://github.com/dartiss/pressr
Description: Reduce page size, creating smaller, more sustainable, site output
Version: 0.1.0
Author: dartiss
Author URI: https://artiss.blog
Text Domain: pressr
*/

/*

Note for anybody viewing this...

This plugin is in the earliest, most-draft state that you can imagine and the code will not be neat!

Eventually, there will be an admin back-end to allow all of the following to be switched on/off

Also some of this code will still need reviewing to ascertain its relevance

Kudos to WP Toolbelt (https://github.com/BinaryMoon/wp-toolbelt/blob/master/modules/cleanup/module.php) for some of the code
*/

// Dequeue Jetpack scripts
 
function pressr_child_dequeues() {

	wp_dequeue_script( 'devicepx' );

}

add_action( 'wp_enqueue_scripts', 'pressr_child_dequeues' );

add_filter( 'jetpack_implode_frontend_css', '__return_false', 99 ); // This *may* be needed for Jetpack's Gutenberg blocks

// If just one post in result just show it - this removes a redundant page load from the search process

function pressr_child_single_result() {

	if ( is_search() ) {
		global $wp_query;
		if ( 1 === $wp_query->post_count && 1 === $wp_query->max_num_pages ) {
			wp_safe_redirect( get_permalink( $wp_query->posts[ 0 ]->ID ) );
			exit;
		}
	}
}

add_action( 'template_redirect', 'pressr_child_single_result' );

// Remove core Emoji support.

remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
remove_action( 'wp_print_styles', 'print_emoji_styles' );
remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );
remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );
add_filter( 'emoji_svg_url', '__return_false' );

// Remove JQuery Migrate

function remove_jquery_migrate( $scripts ) {

	if ( !is_admin() && isset( $scripts->registered[ 'jquery' ] ) ) {
		$script = $scripts->registered[ 'jquery' ];
		
		if ( $script->deps ) {
			$script->deps = array_diff( $script->deps, array(
				'jquery-migrate'
			) );
		}
	}
}

add_action( 'wp_default_scripts', 'remove_jquery_migrate' );

// Remove JS for embedding other WP posts

function deregister_wp_embed() {

	wp_deregister_script( 'wp-embed' );
}

add_action( 'wp_footer', 'deregister_wp_embed' );

// Remove Windows Live Writer support

remove_action( 'wp_head', 'wlwmanifest_link' );

// Remove WordPress generator meta tag

remove_action( 'wp_head', 'wp_generator' );

// Remove feeds

remove_action( 'wp_head', 'feed_links', 2 );

// Remove feeds for categories, search, tags and post comments

remove_action( 'wp_head', 'feed_links_extra', 3 );

// Remove weblog client link

remove_action( 'wp_head', 'rsd_link' );

// Remove shortlink

remove_action( 'wp_head', 'wp_shortlink_wp_head', 10, 0 );
remove_action( 'template_redirect', 'wp_shortlink_header', 11 );

// Remove post relationship links

remove_action( 'wp_head', 'index_rel_link' );
remove_action( 'wp_head', 'start_post_rel_link', 10, 0 );
remove_action( 'wp_head', 'parent_post_rel_link', 10, 0 ); 
remove_action( 'wp_head', 'adjacent_posts_rel_link', 10, 0 );
remove_action( 'wp_head', 'adjacent_posts_rel_link_wp_head', 10, 0 );

// Remove HTML comments

function callback( $buffer ) {

	// Remove all HTML comments
    $buffer = preg_replace( '/<!--(.*)-->/Uis', '', $buffer );

    //return $buffer;

    // Remove carriage returns, new lines and tabs
    $buffer = str_replace( "\r", '', $buffer );
    $buffer = str_replace( "\n", '', $buffer );   
    $buffer = str_replace( "\t", '', $buffer );   

	// Remove spaces between HTML tags    
    $buffer = preg_replace( '/(\>)\s*(\<)/m', '$1$2', $buffer );

    // Remove double spaces until there are none left (looping round removes instances of more than 2 spaces in a row)
    $count = 1;
    while ( $count >= 1 ) {
    	$buffer = str_replace( '  ', ' ', $buffer, $count );
    }    

    return $buffer;
}

function buffer_start() {
    ob_start( "callback" );
}

function buffer_end() {
    ob_end_flush();
}

add_action( 'get_header', 'buffer_start' );
add_action( 'wp_footer', 'buffer_end' );

// Remove superfluous Gutenberg scripts

function deregister_gutenberg_styles() {

	if ( !function_exists( 'gutenberg_register_scripts_and_styles' ) ) {

		wp_dequeue_style( 'wp-block-library' );
		wp_deregister_style( 'wp-block-library' );
	}
}

add_action( 'wp_print_styles', 'deregister_gutenberg_styles', 100 );

// Remove the admin bar

add_filter( 'show_admin_bar', '__return_false' );

// Remove Apple Touch Icon

function removeAppleTouchIconFilter( $string ) {

	return strpos( $string, 'apple-touch-icon' ) === false;
}

function prevent_apple_touch_icon_metatag( $meta_tags ) {

	return array_filter( $meta_tags, 'removeAppleTouchIconFilter' );
}

add_filter( 'site_icon_meta_tags','prevent_apple_touch_icon_metatag' );

// Remove Windows 10 Icon

function removeMSIconFilter( $string ) {

	return strpos( $string, 'msapplication-TileImage' ) === false;
}

function prevent_ms_icon_metatag( $meta_tags ) {

	return array_filter( $meta_tags, 'removeMSIconFilter' );
}

add_filter( 'site_icon_meta_tags','prevent_ms_icon_metatag' );

// Switch off Pingbacks

// Not sure if this reduces code - need to look

add_filter(
		'xmlrpc_methods',
		function( $methods ) {

			unset( $methods[ 'pingback.ping' ] );
			return $methods;

		}
	);

// Switch off Dashicons

add_action(
	'wp_print_styles',
	function() {
		if ( ! is_admin_bar_showing() && ! is_customize_preview() ) {
			wp_deregister_style( 'dashicons' );
		}
	},
	100
);

// Remove WP DNS-Prefetch

remove_action( 'wp_head', 'wp_resource_hints', 2 );
