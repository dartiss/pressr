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

/**
 * Set up feature points
 *
 * Queue up scripts and add the appropriate action
 */
function pressr_admin_enqueue_scripts() {

	wp_enqueue_style( 'wp-pointer' );
	wp_enqueue_script( 'wp-pointer' );

	add_action( 'admin_print_footer_scripts', 'pressr_admin_print_footer_scripts' );
}

add_action( 'admin_enqueue_scripts', 'pressr_admin_enqueue_scripts' );

/**
 * Display feature pointer
 *
 * This is the bit that displays the actual feature point content and where it points to
 */
function pressr_admin_print_footer_scripts() {

	$pointer_content  = __( '<h3>Welcome to Pressr</h3>', 'pressr' );
	$pointer_content .= __( "<p>Pressr is here to reduce your site's footprint, making it more sustainable.</p>", 'pressr' );
	$pointer_content .= __( '<p>Before Pressr will do anything you need to head to the settings and switch on the functions that you require.</p>', 'pressr' );

	$allowed_html = array(
		'h3' => array(),
		'p'  => array(),
	);

	?>
	<script type="text/javascript">
	//<![CDATA[
	jQuery(document).ready( function($) {
	$('#menu-settings').pointer({
		content: '<?php echo wp_kses( $pointer_content, $allowed_html ); ?>',
		position: 'left',
		close: function() {
	}
		}).pointer('open');
	});
	//]]>
	</script>
	<?php
}
