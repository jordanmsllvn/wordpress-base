<?php
/**
 * Core CMS restrictions
 */

add_action('admin_menu', function () {
    remove_menu_page('plugins.php');
    remove_menu_page('themes.php');
    remove_menu_page('edit-comments.php');
    remove_menu_page('tools.php');
    remove_menu_page('edit.php');

    global $menu;
    foreach ($menu as $key => $item) {
        if ($item[2] === 'edit.php?post_type=page') {
            $menu[$key][0] = 'Content';
        }
    }
});

add_action(
	'admin_bar_menu',
	function ( $wp_admin_bar ) {
		$wp_admin_bar->remove_node( 'comments' );
	},
	999
);

add_action('wp_dashboard_setup', function () {

    remove_meta_box('dashboard_primary', 'dashboard', 'side');
    remove_meta_box('dashboard_quick_press', 'dashboard', 'side');
    remove_meta_box('dashboard_activity', 'dashboard', 'normal');
    remove_meta_box('dashboard_right_now', 'dashboard', 'normal');

});

add_action('init', function () {
    remove_theme_support('core-block-patterns');
    remove_post_type_support('page', 'editor');
});

//nuke comments completely
add_action('admin_init', function () {

    global $pagenow;

    if ($pagenow === 'edit-comments.php') {
        wp_redirect(admin_url());
        exit;
    }

    remove_meta_box('dashboard_recent_comments', 'dashboard', 'normal');

    foreach (get_post_types() as $post_type) {
        if (post_type_supports($post_type, 'comments')) {
            remove_post_type_support($post_type, 'comments');
            remove_post_type_support($post_type, 'trackbacks');
        }
    }
});

// remove update nags
// add_filter('pre_site_transient_update_core', '__return_null');
