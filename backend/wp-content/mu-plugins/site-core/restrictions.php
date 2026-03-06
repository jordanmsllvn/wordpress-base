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
    remove_submenu_page('options-general.php', 'options-discussion.php');

    global $menu;
    foreach ($menu as $key => $item) {
        if ($item[2] === 'edit.php?post_type=page') {
            $menu[$key][0] = 'Content';
        }

        if ($item[2] === 'upload.php') {
            $menu[$key][0] = 'Media Library';
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

add_action(
	'admin_bar_menu',
	function ( $wp_admin_bar ) {
		$wp_admin_bar->remove_node( 'new-post' );

		$new_content_node = $wp_admin_bar->get_node( 'new-content' );
		if ( $new_content_node ) {
			$new_content_node->href = admin_url( 'post-new.php?post_type=page' );
			$wp_admin_bar->add_node( $new_content_node );
		}
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

add_action('add_meta_boxes', function (): void {
    $screen = function_exists('get_current_screen') ? get_current_screen() : null;

    if (! $screen || 'post' !== $screen->base || 'page' !== $screen->post_type) {
        return;
    }

    remove_meta_box('commentstatusdiv', 'page', 'normal');
    remove_meta_box('commentsdiv', 'page', 'normal');
    remove_meta_box('trackbacksdiv', 'page', 'normal');
});

add_action('admin_head', function (): void {
    $screen = function_exists('get_current_screen') ? get_current_screen() : null;

    if (!$screen || 'page' !== $screen->post_type || ! in_array($screen->base, ['post', 'post-new'], true)) {
        return;
    }

    ?>
    <script>
        (function () {
            var hidePageBuilderHandleActions = function () {
                var boxes = document.querySelectorAll('.postbox');
                boxes.forEach(function (box) {
                    var title = box.querySelector('.hndle');
                    if (title && title.textContent.trim() === 'Page Builder') {
                        var handleActions = box.querySelector('.handle-actions');
                        if (handleActions) {
                            handleActions.style.display = 'none';
                        }
                    }
                });
            };

            hidePageBuilderHandleActions();
            document.addEventListener('DOMContentLoaded', hidePageBuilderHandleActions);
            document.addEventListener('postbox-toggled', hidePageBuilderHandleActions);
        })();
    </script>
    <?php
});

// remove update nags
// add_filter('pre_site_transient_update_core', '__return_null');
