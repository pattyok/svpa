<?php
/**
 * Plugin Name: Carkeek Functionality Plugin
 * Description: Standard Tweaks to WP functions for all sites by Carkeek Studios
 * Version: 1.0
 * Author: Patty O'Hara, Carkeek Studios
 * Author URI: https://carkeekstudios.com
 *
 * @package _crk-mu-plugins
 */

namespace _CRK_\Admin;

if ( ! defined( 'DISALLOW_FILE_EDIT' ) ) {
	add_action(
		'admin_init',
		function () {
			define( 'DISALLOW_FILE_EDIT', true ); //phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedConstantFound
		}
	);
}

add_action( 'init', __NAMESPACE__ . '\cleanup_head' );
/**
 * Clean up the <head> to remove generator links and ensure RSS support
 *
 * @return void
 */
function cleanup_head() {
	remove_action( 'wp_head', 'rsd_link' );
	remove_action( 'wp_head', 'wlwmanifest_link' );
	remove_action( 'wp_head', 'wp_generator' );

	add_theme_support( 'automatic-feed-links' );
}

add_action( 'init', __NAMESPACE__ . '\page_post_type_supports' );
/**
 * Add and remove post type supports for the Page post type
 *
 * @return void
 */
function page_post_type_supports() {
	remove_post_type_support( 'page', 'comments' );
	add_post_type_support( 'page', 'excerpt' );
	add_post_type_support( 'page', 'post-thumbnail' );
}



add_action( 'wp_before_admin_bar_render', __NAMESPACE__ . '\cleanup_admin_bar' );
/**
 * Remove a bunch of default stuff from the admin bar. Leaves behind a cleaner bar and frees up space for things like WP Environment Type and Query Monitor
 *
 * @return void
 */
function cleanup_admin_bar() {
	global $wp_admin_bar;


	/* Core */
	$wp_admin_bar->remove_menu( 'wp-logo' );
	$wp_admin_bar->remove_menu( 'comments' );
	$wp_admin_bar->remove_menu( 'search' );




}

add_action( 'admin_enqueue_scripts', __NAMESPACE__ . '\admin_styles', 30 );
/**
 * Enqueue a custom stylesheet for the admin area
 *
 * @return void
 */
function admin_styles() {
	wp_enqueue_style(
		'custom-admin',
		plugins_url( '/custom-admin-styles.css', __FILE__ ),
		[],
		'1.0.0'
	);
}

add_action( 'admin_menu', __NAMESPACE__ . '\reorder_admin_menu', 9999 );
/**
 * Remove and reorder admin menu items
 *
 * General grouping: Content, Media, editor-facing design/options/forms, everything else
 *
 * @return void
 */
function reorder_admin_menu() {
	// phpcs:disable WordPress.WP.GlobalVariablesOverride.Prohibited
	global $menu;

	$menu['31.56465'] = [ '', 'read', 'separator2', '', 'wp-menu-separator' ];
	$menu['53.98435'] = [ '', 'read', 'separator3', '', 'wp-menu-separator' ];

	/* Hide the PublishPress "Author Profile" page since you can edit it just as easily from the Authors page */
	unset( $menu['26.8'] );

	/* default_position => new_position */
	$menu_swap = [
		/*  Post Types */
		20      => '4.5', // pages
		5       => '4.659', // posts
		10      => '57.65498', // Media Library


		/* Plugins */
		6       => '9.59864', // The Events Calendar with other Post Types
		'16.9'  => '58.56849', // Gravity Forms Close to Media Library
		'26.7'  => '68.686544', // PublishPress Authors close to Users
		'58.95' => '80.1264', // Searchwp close to settings
		3       => '1654987.9867498', // Jetpack
		'10.000392854349' => '100.5', // Youtube Videos
	];

	foreach ( $menu_swap as $orig => $new ) {
		if ( array_key_exists( $orig, $menu ) ) {
			$menu[ $new ] = $menu[ $orig ];
			unset( $menu[ $orig ] );
		}
	}
	// phpcs:enable
}


add_action( 'admin_menu', __NAMESPACE__ . '\add_template_parts_menu_item', 9999 );
/**
 * Add a "Template Parts" link to the Appearance submenu for faster access
 *
 * @return void
 */
function add_template_parts_menu_item() {
	add_submenu_page(
		'themes.php',
		'',
		'Template Parts',
		'manage_options',
		'site-editor.php?postType=wp_template_part',
		'',
		1
	);
}

add_shortcode( 'email', __NAMESPACE__ . '\hide_email' );
/**
 * Add support for an [email] shortcode that runs address through antispambot()
 *
 * @param array  $atts shortcode attributes, none expected
 * @param string $content content between opening and closing shortcode tags
 * @return void
 */
function hide_email( $atts, $content = null ) {
	if ( ! is_email( $content ) ) {
		return;
	}

	return '<a href="mailto:' . antispambot( $content ) . '">' . antispambot( $content ) . '</a>';
}

/** LOGIN PAGE */
add_action( 'login_enqueue_scripts', __NAMESPACE__ . '\login_styles' );
/**
 * Style the login screen
 *
 * I'll write a blog post about this one day
 *
 * @return void
 */
function login_styles() {
	?>
	<style type="text/css">
		body.login {
			background: #F5F5F7;
		}
		body.login div#login h1 {
			text-align: center;
		}
		body.login div#login h1 a {
			background-image: url(<?php echo esc_url( get_theme_file_uri( 'assets/images/logo.png' ) ); ?>);
			background-size: contain;
			background-position: center;
			width: 600px;
			height: 150px;
			max-width: 100%;			
		}
		#loginform {
			background-color: #fff;
			border-color: #ccc;
		}
		#wp-submit {
			background-color: #38802B;
			border-color: #38802B;
			color: #fff;
		}
		a,
		body.login #nav a,
		body.login #backtoblog a {
			color: #38802B;

		}
		a:hover,
		body.login #nav a:hover,
		body.login #backtoblog a:hover {
			color: #fff;
			text-decoration: underline;

		}
		body.login .message, body.login .notice, body.login .success {
			border-color: #5B6593;
		}
	</style>
	<?php
}

add_filter( 'login_headerurl', __NAMESPACE__ . '\login_logo_url' );
/**
 * Change URL of logo on login screen to go to site homepage
 *
 * @return string URL
 */
function login_logo_url() {
	return esc_url( get_bloginfo( 'url' ) );
}

add_filter( 'login_headertext', __NAMESPACE__ . '\login_logo_url_title' );
/**
 * Change title/alt of logo to be the site name
 *
 * @return URL title
 */
function login_logo_url_title() {
	return esc_html( get_bloginfo( 'name' ) );
}

/** COMMENTS */
// Close comments on the front-end
add_filter('comments_open', '__return_false', 20, 2);
add_filter('pings_open', '__return_false', 20, 2);

// Hide existing comments
add_filter('comments_array', '__return_empty_array', 10, 2);

// Remove comments page in menu
add_action('admin_menu', function () {
	remove_menu_page('edit-comments.php');
});

// Remove comments links from admin bar
add_action('init', function () {
	if (is_admin_bar_showing()) {
		remove_action('admin_bar_menu', 'wp_admin_bar_comments_menu', 60);
	}
});

add_action('admin_init', function() {
	// Redirect any user trying to access comments page
	global $pagenow;

	if ($pagenow === 'edit-comments.php') {
		wp_safe_redirect(admin_url());
		exit;
	}

	// Remove comments metabox from dashboard
	remove_meta_box('dashboard_recent_comments', 'dashboard', 'normal');

	// Disable support for comments and trackbacks in post types
	foreach (get_post_types() as $post_type) {
		if (post_type_supports($post_type, 'comments')) {
			remove_post_type_support($post_type, 'comments');
			remove_post_type_support($post_type, 'trackbacks');
		}
	}
});

/** DASHBOARD */
/** Add a widget to the dashboard.
 * @return void
 */
add_action( 'wp_dashboard_setup', __NAMESPACE__ . '\add_dashboard_widgets' );
function add_dashboard_widgets() {
		global $wp_meta_boxes;

		// remove undesired widgets.
		unset( $wp_meta_boxes['dashboard']['side']['core']['dashboard_quick_press'] );
		unset( $wp_meta_boxes['dashboard']['normal']['core']['tribe_dashboard_widget'] );
		unset( $wp_meta_boxes['dashboard']['normal']['core']['rg_forms_dashboard'] );
		unset( $wp_meta_boxes['dashboard']['normal']['core']['themeisle'] );
		unset( $wp_meta_boxes['dashboard']['normal']['core']['dashboard_activity'] );
		unset( $wp_meta_boxes['dashboard']['normal']['core']['woocommerce_dashboard_recent_reviews'] );

		wp_add_dashboard_widget(
			'carkeek_dashboard_widget', // Widget slug.
			get_bloginfo( 'name' ) . ' Site Management', // Title.
			__NAMESPACE__ . '\dashboard_widget_function' // Display function.
		);

		$dashboard = $wp_meta_boxes['dashboard']['normal']['core'];

		$my_widget = array( 'carkeek_dashboard_widget' => $dashboard['carkeek_dashboard_widget'] );
		unset( $dashboard['carkeek_dashboard_widget'] );

		$sorted_dashboard                             = array_merge( $my_widget, $dashboard );
		$wp_meta_boxes['dashboard']['normal']['core'] = $sorted_dashboard; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited

	}


	/**
	 * Create the function to output the contents of your Dashboard Widget.
	 */
	function dashboard_widget_function() {
		$logo = get_theme_mod( 'custom_logo' );
		if ( $logo ) {
			$logo = wp_get_attachment_image_src( $logo, 'full' );
			$logo = $logo[0];
		} else {
			$logo = get_theme_file_uri( 'assets/images/logo.png' );
		}
		$documentation_url = '/wp-admin/admin.php?page=theme-docs';
		$content = '<div class="ck-dashboard-widget">';
		$content .= '<img style="width:300px;max-width:100%;height:auto;" src="' . esc_url( $logo ) . '" alt="' . esc_attr( get_bloginfo( 'name' ) ) . '" /><h2>' . esc_html( get_bloginfo( 'name' ) ) . '</h2>';

		$content .= '<p>This site is built with a Custom theme by Carkeek Studios.</p>';
		if (!empty($documentation_url)) {
		$content .= 'Refer to your <a href="' . esc_url($documentation_url) . '" target="_BLANK">Theme Docs</a> for tips on managing this site.</p>';
		}
		$content .= '<p>For additional help, feel free to reach out <a href="mailto:patty@carkeekstudios.com" target="_blank">patty@carkeekstudios.com</a></p>';
		$content .= '</div>';
		echo wp_kses_post( $content );
	}