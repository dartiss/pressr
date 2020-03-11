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

// Add in the code to add various admin options, as well as the settings screen.
require_once plugin_dir_path( __FILE__ ) . 'inc/admin.php';
require_once plugin_dir_path( __FILE__ ) . 'inc/settings.php';

// Get the options.
pressr_get_options();

// Adds in native lazy loading.
if ( true === PRESSR_OPTION['lazy_load'] ) {
	require_once plugin_dir_path( __FILE__ ) . 'inc/wp-lazy-loading.php';
}


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
		$buffer = str_replace( "<script>document.documentElement.className = document.documentElement.className.replace( 'no-js', 'js' );</script>", '', $buffer );
		$buffer = str_replace( "<script>(function(html){html.className = html.className.replace(/\bno-js\b/,'js')})(document.documentElement);</script>", '', $buffer );
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

	// Remove Google fonts.
	if ( true === PRESSR_OPTION['google_fonts'] ) {
		$buffer = pressr_remove_html( $buffer, "href='https://fonts.googleapis.com" );
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
add_filter(
	'style_loader_tag',
	function(

	string $tag, string $handle ): string {

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
	},
	10,
	2
);

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
remove_filter( 'walker_nav_menu_start_el', 'twentytwenty_nav_menu_social_icons', 999 );

/**
 * Switch off Genericons (used by Jetpack).
 */
function pressr_dequeue_genericons() {
	if ( true === PRESSR_OPTION['genericons'] ) {
		wp_dequeue_style( 'genericons' );
		wp_deregister_style( 'genericons' );
	}
}
add_action( 'wp_enqueue_scripts', 'pressr_dequeue_genericons', 100 );

/**
 * Switch off comments script (use if WP comments are not being used)
 */
function pressr_deregister_comments_script() {
	if ( true === PRESSR_OPTION['comments_script'] ) {
		wp_deregister_script( 'comment-reply' );
	}
}
add_action( 'init', 'pressr_deregister_comments_script' );

/**
 * Dequeue the skip link fix in certain themes
 */
function pressr_dequeue_focus_fix() {
	if ( true === PRESSR_OPTION['skip_fix'] ) {
		wp_dequeue_script( 'twentysixteen-skip-link-focus-fix' );
		wp_dequeue_script( 'twentyseventeen-skip-link-focus-fix' );
	}
}
add_action( 'wp_enqueue_scripts', 'pressr_dequeue_focus_fix', 100 );

/**
 * Remove Jetpack's social menu support
 */
function pressr_remove_jetpack_social_menu_support() {
	if ( true === PRESSR_OPTION['jetpack_social_menu'] ) {
		remove_theme_support( 'jetpack-social-menu' );
	}
}
add_action( 'after_setup_theme', 'pressr_remove_jetpack_social_menu_support', 11 ); 

/**
 * Get the Pressr settings
 *
 * For now, the settings are hard-coded, but will eventually be fetched from saved admin options.
 * Settings are saved into a global array so they don't force (potentially) an SQL read everytime they're needed
 */
function pressr_get_options() {

	$options =
		array(
			'admin_bar'           => true,
			'apple_icon'          => true,
			'canonical'           => true,
			'clean_attributes'    => true,
			'comments_feed'       => true,
			'comments_script'     => true,
			'dns_prefetch'        => true,
			'double_spaces'       => true,
			'emoji'               => true,
			'feed'                => true,
			'generator'           => true,
			'genericons'          => true,
			'google_fonts'        => true,
			'gutenberg_css'       => true,
			'html_comments'       => true,
			'jetpack_css'         => true,
			'jetpack_social_menu' => true,
			'jquery_migrate'      => true,
			'lazy_load'           => true,
			'ms_icon'             => true,
			'no_js'               => true,
			'oembed'              => true,
			'other_feeds'         => true,
			'live_writer'         => true,
			'pingback'            => true,
			'preconnect'          => true,
			'print_css'           => true,
			'profile_tag'         => true,
			'relationship'        => true,
			'rest_api'            => true,
			'shortlink'           => true,
			'skip_fix'            => true,
			'single_search'       => true,
			'tidy_html'           => false,
			'widget_style'        => true,
			'wp_embed'            => true,
			'xmlrpc'              => true,
		);

	global $wp_version;
	if ( version_compare( $wp_version, '5.4', '>=' ) ) {
		$options['lazy_load'] = false;
	}

	define( 'PRESSR_OPTION', $options );
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
