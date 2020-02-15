<?php
/*
Plugin Name: Pressr
Plugin URI: https://github.com/dartiss/pressr
Description: 🗜Reduce page size, creating smaller, more sustainable, site output.
Version: 0.1.0
Author: dartiss
Author URI: https://artiss.blog
Text Domain: pressr
*/

/*

Note for anybody viewing this...

This plugin is in the earliest, most-draft state that you can imagine and the code will not be neat!

Eventually, there will be an admin back-end to allow all of the following to be switched on/off

All of the code has been reviewed to make sure it does what it should

Kudos to WP Toolbelt (https://github.com/BinaryMoon/wp-toolbelt/blob/master/modules/cleanup/module.php) for some of the code
*/

/*

To Do:

Admin back-end
    Tips
    Add feature pointer

*/

function pressr_set_plugin_meta( $links, $file ) {

    if ( strpos( $file, 'pressr.php' ) !== false ) {

        $links = array_merge( $links, array( '<a href="https://github.com/dartiss/pressr">' . __( 'Github', 'pressr' ) . '</a>' ) ); 

        $links = array_merge( $links, array( '<a href="http://wordpress.org/support/plugin/pressr">' . __( 'Support', 'pressr' ) . '</a>' ) );

        $links = array_merge( $links, array( '<a href="https://artiss.blog/donate">' . __( 'Donate', 'pressr' ) . '</a>' ) );

        $links = array_merge( $links, array( '<a href="https://wordpress.org/support/plugin/pressr/reviews/#new-post">' . __( 'Write a Review', 'pressr' ) . '&nbsp;⭐️⭐️⭐️⭐️⭐️</a>' ) );
    }       

    return $links;
}

add_filter( 'plugin_row_meta', 'pressr_set_plugin_meta', 10, 2 );

function pressr_press_code() {

    // Removes Jetpack CSS. Only use if no CSS components of Jetpack required

    add_filter( 'jetpack_implode_frontend_css', '__return_false', 99 );

    // Remove JS for embedding other WP posts

    function deregister_wp_embed() {

        wp_deregister_script( 'wp-embed' );
    }

    add_action( 'wp_footer', 'deregister_wp_embed' );

    // Remove superfluous Gutenberg scripts

    function deregister_gutenberg_styles() {

        if ( !function_exists( 'gutenberg_register_scripts_and_styles' ) ) {

            wp_dequeue_style('wp-block-library');
            wp_dequeue_style('wp-block-library-theme');
        }
    }

    add_action( 'wp_print_styles', 'deregister_gutenberg_styles', 100 );

    // If just one post in result just show it - this removes a redundant page load from the search process

    function pressr_remove_single_results() {

    	if ( is_search() ) {

    		global $wp_query;

    		if ( 1 === $wp_query->post_count && 1 === $wp_query->max_num_pages ) {
    			wp_safe_redirect( get_permalink( $wp_query->posts[ 0 ]->ID ) );
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

    // Remove Windows Live Writer support

    remove_action( 'wp_head', 'wlwmanifest_link' );

    // Remove WordPress generator meta tag

    remove_action( 'wp_head', 'wp_generator' );

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

    add_filter( 'site_icon_meta_tags', 'prevent_ms_icon_metatag' );

    // Remove the recent widgets styling

    add_filter( 'show_recent_comments_widget_style', '__return_false' );

    // Remove REST API
     
    remove_action( 'wp_head', 'rest_output_link_wp_head' );

    // Clean up attributes in style tags

    add_filter( 'style_loader_tag', function ( string $tag, string $handle ): string {

        // Remove ID attribute
        $tag = str_replace( "id='${handle}-css'", '', $tag );

        // Remove type attribute
        $tag = str_replace( " type='text/css'", '', $tag );

        // Remove trailing slash
        $tag = str_replace( ' />', '>', $tag );

        // Remove double spaces
        return str_replace( '  ', '', $tag );

    }, 10, 2 );

}

add_filter( 'plugins_loaded', 'pressr_press_code' );
