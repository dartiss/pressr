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

	pressr_get_options();

	// Removes Jetpack CSS. Only use if no CSS components of Jetpack required.
	if ( true === PRESSR_OPTION['jetpack_css'] ) {
		add_filter( 'jetpack_implode_frontend_css', '__return_false', 99 );
	}

	/**
	 * Remove JS for embedding other WP posts
	 */
	function pressr_deregister_wp_embed() {

		if ( true === PRESSR_OPTION['wp_embed'] ) {
			wp_deregister_script( 'wp-embed' );
		}
	}

	add_action( 'wp_footer', 'pressr_deregister_wp_embed' );

	/**
	 * Remove superfluous Gutenberg scripts
	 */
	function pressr_deregister_gutenberg_styles() {

		if ( ! function_exists( 'gutenberg_register_scripts_and_styles' ) && true === PRESSR_OPTION['gutenberg_css'] ) {

			wp_dequeue_style( 'wp-block-library' );
			wp_dequeue_style( 'wp-block-library-theme' );
		}
	}

	add_action( 'wp_print_styles', 'pressr_deregister_gutenberg_styles', 100 );

	/**
	 * If just one post in result just show it - this removes a redundant page load from the search process
	 */
	function pressr_remove_single_results() {

		if ( is_search() && true === PRESSR_OPTION['single_search'] ) {

			global $wp_query;

			if ( 1 === $wp_query->post_count && 1 === $wp_query->max_num_pages ) {
				wp_safe_redirect( get_permalink( $wp_query->posts[0]->ID ) );
				exit;
			}
		}
	}

	add_action( 'template_redirect', 'pressr_remove_single_results' );

	// Remove core Emoji support.

	if ( true === PRESSR_OPTION['emoji'] ) {
		remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
		remove_action( 'wp_print_styles', 'print_emoji_styles' );
		remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );
		remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
		remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );

		add_filter( 'emoji_svg_url', '__return_false' );
	}

	// Remove Windows Live Writer support.
	if ( true === PRESSR_OPTION['live_writer'] ) {
		remove_action( 'wp_head', 'wlwmanifest_link' );
	}

	// Remove WordPress generator meta tag.
	if ( true === PRESSR_OPTION['generator'] ) { 
		remove_action( 'wp_head', 'wp_generator' );
	}

	// Remove XML-RPC link.
	if ( true === PRESSR_OPTION['xmlrpc'] ) {
		remove_action( 'wp_head', 'rsd_link' );
	}

	// Remove shortlink.
	if ( true === PRESSR_OPTION['shortlink'] ) {
		remove_action( 'wp_head', 'wp_shortlink_wp_head', 10, 0 );
		remove_action( 'template_redirect', 'wp_shortlink_header', 11 );
	}

	// Remove post relationship links.
	if ( true === PRESSR_OPTION['relationship'] ) {
		remove_action( 'wp_head', 'index_rel_link' );
		remove_action( 'wp_head', 'start_post_rel_link', 10, 0 );
		remove_action( 'wp_head', 'parent_post_rel_link', 10, 0 ); 
		remove_action( 'wp_head', 'adjacent_posts_rel_link', 10, 0 );
		remove_action( 'wp_head', 'adjacent_posts_rel_link_wp_head', 10, 0 );
	}

	/**
	 * Remove JQuery Migrate
	 *
	 * @param string $scripts JQuery scripts.
	 */
	function remove_jquery_migrate( $scripts ) {
		if ( true === PRESSR_OPTION['jquery_migrate'] ) {

			if ( ! is_admin() && isset( $scripts->registered['jquery'] ) ) {
				$script = $scripts->registered['jquery'];
				
				if ( $script->deps ) {
					$script->deps = array_diff( $script->deps, array( 'jquery-migrate' ) );
				}
			}
		}
	}
	add_action( 'wp_default_scripts', 'remove_jquery_migrate' );

	/**
	 * Remove HTML comments
	 *
	 * @param string $buffer Content.
	 */
	function pressr_callback( $buffer ) {

		// Remove all HTML comments.
		if ( true === PRESSR_OPTION['html_comments'] ) {
			$buffer = preg_replace( '/<!--(.*)-->/Uis', '', $buffer );
		}

		// Remove double spaces until there are none left (looping round removes instances of more than 2 spaces in a row).
		if ( true === PRESSR_OPTION['double_spaces'] ) {
			$count = 1;
			while ( $count >= 1 ) {
				$buffer = str_replace( '  ', ' ', $buffer, $count );
			}
		}

		// Remove profile tag.
		if ( true === PRESSR_OPTION['profile_tag'] ) {
			$buffer = pressr_remove_html( $buffer, 'rel="profile"' );
		}

		// Remove no-js.
		if ( true === PRESSR_OPTION['no_js'] ) {
			$buffer = str_replace( ' class="no-js"', '', $buffer );
			$buffer = str_replace( " class='no-js'", '', $buffer );
			$buffer = str_replace( "<script>document.documentElement.className = document.documentElement.className.replace( 'no-js', 'js' );</script>\n", '', $buffer );
		}

		// Remove theme's print CSS.
		if ( true === PRESSR_OPTION['print_css'] ) {
			$buffer = pressr_remove_html( $buffer, "media='print'" );
		}

		// Remove Pingback meta.
		if ( true === PRESSR_OPTION['pingback'] ) {
			$buffer = pressr_remove_html( $buffer, 'rel="pingback"' );
		}

		// Remove DNS pre-fetches.
		if ( true === PRESSR_OPTION['dns_prefetch'] ) {
			$buffer = pressr_remove_html( $buffer, "rel='dns-prefetch'" );
		}

		// Remove pre-connects.
		if ( true === PRESSR_OPTION['preconnect'] ) {
			$buffer = pressr_remove_html( $buffer, "rel='preconnect'" );
		}

		if ( true === PRESSR_OPTION['tidy_html'] ) {

			// Remove spaces between HTML tags.
			$buffer = preg_replace( '/(\>)\s*(\<)/m', '$1$2', $buffer );

			// Remove carriage returns.
			$buffer = str_replace( "\r", '', $buffer );

			// Remove newlines.
			$buffer = str_replace( "\n", '', $buffer );

			// Remove tabs.
			$buffer = str_replace( "\t", '', $buffer );
		}

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
	if ( true === PRESSR_OPTION['admin_bar'] ) {
		add_filter( 'show_admin_bar', '__return_false' );
	}

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

	if ( true === PRESSR_OPTION['apple_icon'] ) {
		add_filter( 'site_icon_meta_tags', 'pressr_apple_touch_icon' );
	}

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
	if ( true === PRESSR_OPTION['ms_icon'] ) {
		add_filter( 'site_icon_meta_tags', 'pressr_ms_icon' );
	}

	// Remove the recent widgets styling.
	if ( true === PRESSR_OPTION['widget_style'] ) {
		add_filter( 'show_recent_comments_widget_style', '__return_false' );
	}

	// Remove REST API.
	if ( true === PRESSR_OPTION['rest_api'] ) {
		remove_action( 'wp_head', 'rest_output_link_wp_head' );
	}

	// Clean up attributes in style tags.
	add_filter( 'style_loader_tag', function( string $tag, string $handle ): string {

		if ( true === PRESSR_OPTION['clean_attributes'] ) {

			// Remove ID attribute.
			$tag = str_replace( "id='${handle}-css'", '', $tag );

			// Remove type attribute.
			$tag = str_replace( " type='text/css'", '', $tag );

			// Remove trailing slash.
			$tag = str_replace( ' />', '>', $tag );
			
			// Remove double spaces.
			$tag = str_replace( '  ', '', $tag );
		}

		return $tag;

	}, 10, 2 );

	// Remove main feed from meta.
	if ( true === PRESSR_OPTION['feed'] ) {
		add_filter( 'feed_links_show_posts_feed', '__return_false' );
	}

	// Remove commments feed from meta.
	if ( true === PRESSR_OPTION['comments_feed'] ) {
		add_filter( 'feed_links_show_comments_feed', '__return_false' );
	}

	// Switch off meta for other feeds.
	if ( true === PRESSR_OPTION['other_feeds'] ) {
		remove_action( 'wp_head', 'feed_links_extra', 3 );
	}

	// Switch off canonical meta.
	if ( true === PRESSR_OPTION['canonical'] ) {
		remove_action( 'wp_head', 'rel_canonical' );
	}

	// Switch off oEmbed discovery.
	if ( true === PRESSR_OPTION['oembed'] ) {
		remove_action( 'wp_head', 'wp_oembed_add_discovery_links', 10 );
		remove_action( 'wp_head', 'wp_oembed_add_host_js' );
		remove_action( 'rest_api_init', 'wp_oembed_register_route' );
		remove_filter( 'oembed_dataparse', 'wp_filter_oembed_result', 10 );
	}

	/**
	 * Switch off Genericons (used by Jetpack).
	 */
	function dequeue_my_css() {
		if ( true === PRESSR_OPTION['genericons'] ) {
			wp_dequeue_style( 'genericons' );
			wp_deregister_style( 'genericons' );
		}
	}
	add_action( 'wp_enqueue_scripts', 'dequeue_my_css', 100 );

}

add_filter( 'plugins_loaded', 'pressr_press_code' );

/**
 * Get the Pressr settings
 *
 * For now, the settings are hard-coded, but will eventually be fetched from saved admin options.
 * Settings are saved into a global array so they don't force (potentially) an SQL read everytime they're needed
 */
function pressr_get_options() {

	// These are the settings for my own, production site.
	/**
	define( 'PRESSR_OPTION', array(
		'admin_bar'        => true,
		'apple_icon'       => false,
		'canonical'        => true,
		'clean_attributes' => true,
		'comments_feed'    => true,
		'dns_prefetch'     => false,
		'double_spaces'    => true,
		'emoji'            => false,
		'feed'             => false,
		'generator'        => true,
		'genericons'       => false,
		'gutenberg_css'    => false,
		'html_comments'    => true,
		'jetpack_css'      => false,
		'jquery_migrate'   => true,
		'ms_icon'          => true,
		'no_js'            => false,
		'other_feeds'      => true,
		'live_writer'      => true,
		'oembed'           => false,
		'pingback'         => true,
		'preconnect'       => true,
		'print_css'        => true,
		'profile_tag'      => true,
		'relationship'     => true,
		'rest_api'         => false,
		'shortlink'        => true,
		'single_search'    => true,
		'tidy_html'        => false,
		'widget_style'     => true,
		'wp_embed'         => false,
		'xmlrpc'           => false,
	) );
	**/

	// This will switch all options on, for testing purposes.
	define( 'PRESSR_OPTION', array(
		'admin_bar'        => true,
		'apple_icon'       => true,
		'canonical'        => true,
		'clean_attributes' => true,
		'comments_feed'    => true,
		'dns_prefetch'     => true,
		'double_spaces'    => true,
		'emoji'            => true,
		'feed'             => true,
		'generator'        => true,
		'genericons'       => true,
		'gutenberg_css'    => true,
		'html_comments'    => true,
		'jetpack_css'      => true,
		'jquery_migrate'   => true,
		'ms_icon'          => true,
		'no_js'            => true,
		'oembed'           => true,
		'other_feeds'      => true,
		'live_writer'      => true,
		'pingback'         => true,
		'preconnect'       => true,
		'print_css'        => true,
		'profile_tag'      => true,
		'relationship'     => true,
		'rest_api'         => true,
		'shortlink'        => true,
		'single_search'    => true,
		'tidy_html'        => false,
		'widget_style'     => true,
		'wp_embed'         => true,
		'xmlrpc'           => true,
	) );
}

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
