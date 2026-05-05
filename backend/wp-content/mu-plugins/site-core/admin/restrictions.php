<?php
/**
 * Core CMS restrictions
 */

if (! defined('ABSPATH')) {
    exit;
}

add_action('admin_menu', function () {
    $content_menu_slug = 'edit.php?post_type=page';

    remove_menu_page('plugins.php');
    remove_menu_page('themes.php');
    remove_menu_page('edit-comments.php');
    remove_menu_page('tools.php');
    remove_menu_page('edit.php');
    remove_submenu_page('options-general.php', 'options-discussion.php');

    global $menu;
    foreach ($menu as $key => $item) {
        if ($item[2] === $content_menu_slug) {
            $menu[$key][0] = 'Content';
        }

        if ($item[2] === 'upload.php') {
            $menu[$key][0] = 'Media Library';
        }
    }

    global $submenu;

    if (! isset($submenu[$content_menu_slug]) || ! is_array($submenu[$content_menu_slug])) {
        return;
    }

    foreach ($submenu[$content_menu_slug] as $key => $item) {
        $submenu_slug = $item[2] ?? '';

        if (! is_string($submenu_slug)) {
            unset($submenu[$content_menu_slug][$key]);
            continue;
        }

        if (0 === strpos($submenu_slug, 'post-new.php?post_type=')) {
            unset($submenu[$content_menu_slug][$key]);
            continue;
        }

        if (1 !== preg_match('/^edit\.php\?post_type=([a-z0-9_]+)$/', $submenu_slug, $matches)) {
            unset($submenu[$content_menu_slug][$key]);
            continue;
        }

        $post_type_object = get_post_type_object($matches[1]);

        if (! $post_type_object || ! isset($post_type_object->labels->name)) {
            unset($submenu[$content_menu_slug][$key]);
            continue;
        }

        $submenu[$content_menu_slug][$key][0] = (string) $post_type_object->labels->name;
    }

    $submenu[$content_menu_slug] = array_values($submenu[$content_menu_slug]);
});

add_action(
    'admin_bar_menu',
    function ($wp_admin_bar) {
        $wp_admin_bar->remove_node('comments');
    },
    999
);

add_action(
    'admin_bar_menu',
    function ($wp_admin_bar) {
        $wp_admin_bar->remove_node('new-post');

        $new_content_node = $wp_admin_bar->get_node('new-content');
        if ($new_content_node) {
            $new_content_node->href = admin_url('post-new.php?post_type=page');
            $wp_admin_bar->add_node($new_content_node);
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

    if (! function_exists('site_core_get_page_builder_post_types')) {
        return;
    }

    foreach (array_keys(site_core_get_page_builder_post_types()) as $post_type) {
        remove_post_type_support($post_type, 'editor');
    }
});

add_filter(
    'use_block_editor_for_post_type',
    function ($use_block_editor, $post_type) {
        if (function_exists('site_core_is_page_builder_post_type') && site_core_is_page_builder_post_type($post_type)) {
            return false;
        }

        return $use_block_editor;
    },
    10,
    2
);

// Disable comments across the admin and content models.
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

    if (! $screen || 'post' !== $screen->base || ! function_exists('site_core_is_page_builder_post_type') || ! site_core_is_page_builder_post_type($screen->post_type)) {
        return;
    }

    remove_meta_box('commentstatusdiv', $screen->post_type, 'normal');
    remove_meta_box('commentsdiv', $screen->post_type, 'normal');
    remove_meta_box('trackbacksdiv', $screen->post_type, 'normal');
});

add_action('admin_head', function (): void {
    $screen = function_exists('get_current_screen') ? get_current_screen() : null;

    if (
        ! $screen ||
        ! function_exists('site_core_is_page_builder_post_type') ||
        ! site_core_is_page_builder_post_type($screen->post_type) ||
        ! in_array($screen->base, ['post', 'post-new'], true)
    ) {
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
