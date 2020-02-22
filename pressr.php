<?php
/**
Plugin Name: Pressr
Plugin URI: https://github.com/dartiss/pressr
Description: 🗜Reduce page size, creating smaller, more sustainable, site output.
Version: 0.1.0
Author: dartiss
Author URI: https://artiss.blog
Text Domain: pressr

@package Pressr
 */

/**
Note for anybody viewing this...

This plugin is in the earliest, most-draft state that you can imagine and the code will not be neat!

Eventually, there will be an admin back-end to allow all of the following to be switched on/off

All of the code has been reviewed to make sure it does what it should

To Do:

- Continue to add compression code
	- Review various page outputs to see what can be removed
- Admin back-end
	- Tips
	- Add feature pointer
- Add compression options for WP Admin
 */

require_once plugin_dir_path( __FILE__ ) . 'inc/admin.php';

/**
 * Compress code
 *
 * Description to go here 
 */
function pressr_press_code() {

	// Removes Jetpack CSS. Only use if no CSS components of Jetpack required.
	add_filter( 'jetpack_implode_frontend_css', '__return_false', 99 );

	/**
	 * Remove JS for embedding other WP posts
	 */
	function pressr_deregister_wp_embed() {

		wp_deregister_script( 'wp-embed' );
	}

	add_action( 'wp_footer', 'pressr_deregister_wp_embed' );

	/**
	 * Remove superfluous Gutenberg scripts
	 */
	function pressr_deregister_gutenberg_styles() {

		if ( ! function_exists( 'gutenberg_register_scripts_and_styles' ) ) {

			wp_dequeue_style( 'wp-block-library' );
			wp_dequeue_style( 'wp-block-library-theme' );
		}
	}

	add_action( 'wp_print_styles', 'pressr_deregister_gutenberg_styles', 100 );

	/**
	 * If just one post in result just show it - this removes a redundant page load from the search process
	 */
	function pressr_remove_single_results() {

		if ( is_search() ) {

			global $wp_query;

			if ( 1 === $wp_query->post_count && 1 === $wp_query->max_num_pages ) {
				wp_safe_redirect( get_permalink( $wp_query->posts[0]->ID ) );
				exit;
			}
		}
	}

	add_action( 'template_redirect', 'pressr_remove_single_results' );

	// Remove core Emoji support.

	remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
	remove_action( 'wp_print_styles', 'print_emoji_styles' );
	remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );
	remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
	remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );

	add_filter( 'emoji_svg_url', '__return_false' );

	// Remove Windows Live Writer support.
	remove_action( 'wp_head', 'wlwmanifest_link' );

	// Remove WordPress generator meta tag.
	remove_action( 'wp_head', 'wp_generator' );

	// Remove weblog client link.
	remove_action( 'wp_head', 'rsd_link' );

	// Remove shortlink.
	remove_action( 'wp_head', 'wp_shortlink_wp_head', 10, 0 );
	remove_action( 'template_redirect', 'wp_shortlink_header', 11 );

	// Remove post relationship links.
	remove_action( 'wp_head', 'index_rel_link' );
	remove_action( 'wp_head', 'start_post_rel_link', 10, 0 );
	remove_action( 'wp_head', 'parent_post_rel_link', 10, 0 ); 
	remove_action( 'wp_head', 'adjacent_posts_rel_link', 10, 0 );
	remove_action( 'wp_head', 'adjacent_posts_rel_link_wp_head', 10, 0 );

	/**
	 * Remove HTML comments
	 *
	 * @param string $buffer Content.
	 */
	function pressr_callback( $buffer ) {

		// Remove all HTML comments.
		$buffer = preg_replace( '/<!--(.*)-->/Uis', '', $buffer );

		// Remove double spaces until there are none left (looping round removes instances of more than 2 spaces in a row).
		$count = 1;
		while ( $count >= 1 ) {
			$buffer = str_replace( '  ', ' ', $buffer, $count );
		}

		// Remove profile tag.
		$buffer = pressr_remove_html( $buffer, 'rel="profile"' );

		// Remove no-js.
		$buffer = str_replace( ' class="no-js"', '', $buffer );
		$buffer = str_replace( " class='no-js'", '', $buffer );
		$buffer = str_replace( "<script>document.documentElement.className = document.documentElement.className.replace( 'no-js', 'js' );</script>\n", '', $buffer );

		// Remove theme's print CSS.
		$buffer = pressr_remove_html( $buffer, "media='print'" );

		// Remove Pingback meta.
		$buffer = pressr_remove_html( $buffer, 'rel="pingback"' );

		// Remove DNS pre-fetches.
		$buffer = pressr_remove_html( $buffer, "rel='dns-prefetch'" );

		//return $buffer;

		// Remove spaces between HTML tags.
		$buffer = preg_replace( '/(\>)\s*(\<)/m', '$1$2', $buffer );

		// Remove carriage returns.
		$buffer = str_replace( "\r", '', $buffer );

		// Remove newlines.
		$buffer = str_replace( "\n", '', $buffer );

		// Remove tabs.
		$buffer = str_replace( "\t", '', $buffer );

		return $buffer;
	}

	/**
	 * Buffer start
	 */
	function pressr_buffer_start() {
		ob_start( 'pressr_callback' );
	}
	add_action( 'get_header', 'pressr_buffer_start' );

	/**
	 * Buffer End
	 */
	function pressr_buffer_end() {
		ob_end_flush();
	}
	add_action( 'shutdown', 'pressr_buffer_end' );

	// Remove the admin bar.
	add_filter( 'show_admin_bar', '__return_false' );

	/**
	 * Remove Apple Touch Icon
	 *
	 * @param string $string Meta tags.
	 */
	function pressr_remove_apple_touch_icon( $string ) {

		return strpos( $string, 'apple-touch-icon' ) === false;
	}

	/**
	 * Filter out the Apple Touch icon
	 *
	 * @param string $meta_tags Meta tags.
	 */
	function pressr_apple_touch_icon( $meta_tags ) {

		return array_filter( $meta_tags, 'pressr_remove_apple_touch_icon' );
	}

	add_filter( 'site_icon_meta_tags', 'pressr_apple_touch_icon' );

	/**
	 * Remove Windows 10 Icon
	 *
	 * @param string $string Meta tags.
	 */
	function pressr_remove_ms_icon( $string ) {

		return strpos( $string, 'msapplication-TileImage' ) === false;
	}

	/**
	 * Filter out the MS icon
	 *
	 * @param string $meta_tags Meta tags.
	 */
	function pressr_ms_icon( $meta_tags ) {

		return array_filter( $meta_tags, 'pressr_remove_ms_icon' );
	}

	add_filter( 'site_icon_meta_tags', 'pressr_ms_icon' );

	// Remove the recent widgets styling.
	add_filter( 'show_recent_comments_widget_style', '__return_false' );

	// Remove REST API.
	remove_action( 'wp_head', 'rest_output_link_wp_head' );

	// Clean up attributes in style tags.
	add_filter( 'style_loader_tag', function( string $tag, string $handle ): string {

		// Remove ID attribute.
		$tag = str_replace( "id='${handle}-css'", '', $tag );

		// Remove type attribute.
		$tag = str_replace( " type='text/css'", '', $tag );

		// Remove trailing slash.
		$tag = str_replace( ' />', '>', $tag );

		// Remove double spaces.
		return str_replace( '  ', '', $tag );

	}, 10, 2 );

}

add_filter( 'plugins_loaded', 'pressr_press_code' );

// Remove main feed from meta.
add_filter( 'feed_links_show_posts_feed', '__return_false' );

// Remove commments feed from meta.
add_filter( 'feed_links_show_comments_feed', '__return_false' );

// Switch off meta for other feeds.
remove_action( 'wp_head', 'feed_links_extra', 3 );

/**
 * Remove a tag from supplied HTML
 *
 * Pass in some HTML and something from a tag to uniquely identify it and this function will remove the entire tag
 *
 * @param  string $html  The HTML to be modified.
 * @param  string $find  Unique content of tag to find.
 *
 * @return string        Modified HTML.
 */
function pressr_remove_html( $html, $find ) {

	$found = true;
	while ( $found ) {
		$pos = strpos( $html, $find );
		if ( false !== $pos ) {
			$tag_start = strrpos( substr( $html, 0, $pos ), '<' );
			$tag_end   = strpos( substr( $html, $pos ), '>' ) + $pos;
			$html      = str_replace( substr( $html, $tag_start, $tag_end - $tag_start + 1 ), '', $html );
		} else {
			$found = false;
		}
	}

	return $html;
}
