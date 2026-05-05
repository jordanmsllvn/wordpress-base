<?php
/**
 * Site Core Resources content type.
 */

if (! defined('ABSPATH')) {
    exit;
}

site_core_register_page_builder_post_type(
    'resource',
    array(
        'rich_text',
        'form',
    )
);

add_action('init', 'site_core_register_resource_post_type');

/**
 * Register the Resources content type.
 */
function site_core_register_resource_post_type()
{
    register_post_type(
        'resource',
        array(
            'labels' => array(
                'name'                  => __('Resources', 'site-core'),
                'singular_name'         => __('Resource', 'site-core'),
                'menu_name'             => __('Resources', 'site-core'),
                'name_admin_bar'        => __('Resource', 'site-core'),
                'add_new'               => __('Add Resource', 'site-core'),
                'add_new_item'          => __('Add New Resource', 'site-core'),
                'edit_item'             => __('Edit Resource', 'site-core'),
                'new_item'              => __('New Resource', 'site-core'),
                'view_item'             => __('View Resource', 'site-core'),
                'view_items'            => __('View Resources', 'site-core'),
                'search_items'          => __('Search Resources', 'site-core'),
                'not_found'             => __('No resources found.', 'site-core'),
                'not_found_in_trash'    => __('No resources found in Trash.', 'site-core'),
                'all_items'             => __('Resources', 'site-core'),
                'archives'              => __('Resource Archives', 'site-core'),
                'attributes'            => __('Resource Attributes', 'site-core'),
                'insert_into_item'      => __('Insert into resource', 'site-core'),
                'uploaded_to_this_item' => __('Uploaded to this resource', 'site-core'),
            ),
            'public'             => true,
            'show_ui'            => true,
            'show_in_menu'       => 'edit.php?post_type=page',
            'show_in_admin_bar'  => true,
            'show_in_rest'       => true,
            'has_archive'        => true,
            'rewrite'            => array('slug' => 'resources'),
            'menu_icon'          => 'dashicons-media-document',
            'supports'           => array('title', 'thumbnail', 'revisions'),
            'publicly_queryable' => true,
            'query_var'          => true,
            'delete_with_user'   => false,
        )
    );
}
