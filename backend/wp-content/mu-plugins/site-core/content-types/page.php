<?php
/**
 * Site Core Page content type configuration.
 */

if (! defined('ABSPATH')) {
    exit;
}

site_core_register_page_builder_post_type(
    'page',
    array(
        'hero',
        'rich_text',
        'form',
    )
);
