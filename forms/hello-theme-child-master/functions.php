<?php

require_once __DIR__ . '/form_ids.php';
require_once __DIR__ . '/form_fields.php';

/*==========================================================================
 * Table of Contents
 * -------------------------------------------------------------------------
 * 1. Theme Setup ................. enqueue styles, dashboard, admin (below)
 * 2. Date & Event Forms .......... form-date-event.php
 * 3. Confirmation Forms .......... form-confirmations.php
 * 4. LTRP & Culture Assessment ... form-ltrp.php
 * 5. Curriculum Ordering ......... form-curriculum.php
 * 6. Cross-Form Validation ....... form-validation.php
 * 7. Shared Helpers .............. form-helpers.php
 * 8. Footer Assets ............... footer-assets.php
 *========================================================================*/

/*--------------------------------------------------------------------------
 * 1. Theme Setup
 *------------------------------------------------------------------------*/

/**
 * Theme functions and definitions
 *
 * @package HelloElementorChild
 */

/**
 * Load child theme css and optional scripts
 *
 * @return void
 */
function hello_elementor_child_enqueue_scripts()
{
    wp_enqueue_style(
        'hello-elementor-child-style',
        get_stylesheet_directory_uri() . '/style.css',
        [
            'hello-elementor-theme-style',
        ],
        '1.0.0'
    );
}
add_action('wp_enqueue_scripts', 'hello_elementor_child_enqueue_scripts', 20);

/* Disable Gutenburg */
add_filter('use_block_editor_for_post', '__return_false');

/* Support */
function remove_footer_admin()
{

    echo 'Website by <a href="https://macedondigital.com.au/" target="_blank">Macedon Digital</a> | Contact us for support <a href="mailto:info@macedondigital.com.au" target="_blank">info@macedondigital.com.au</a></p>';

}

add_filter('admin_footer_text', 'remove_footer_admin');

/**
 * DM Website Support Dashboard Widget
 */

add_action('wp_dashboard_setup', 'md_custom_dashboard_widgets');

function md_custom_dashboard_widgets()
{
    global $wp_meta_boxes;

    wp_add_dashboard_widget('custom_help_widget', 'Theme Support', 'custom_dashboard_help');
}

function custom_dashboard_help()
{
    echo '<p>Welcome to the TRP Forms website! Need help? Contact Macedon Digital - <a href="mailto:info@macedondigital.com.au">info@macedondigital.com.au</a></p>';
}


/**
* Remove Annoying WordPress Dashboard Widgets
*/

add_action('wp_dashboard_setup', 'md_remove_dashboard_widgets');

function md_remove_dashboard_widgets()
{

    remove_meta_box('dashboard_primary', 'dashboard', 'side'); // WordPress.com Blog
    remove_meta_box('dashboard_plugins', 'dashboard', 'normal'); // Plugins
    remove_meta_box('dashboard_right_now', 'dashboard', 'normal'); // Right Now
    remove_action('welcome_panel', 'wp_welcome_panel'); // Welcome Panel
    remove_action('try_gutenberg_panel', 'wp_try_gutenberg_panel'); // Try Gutenberg
    remove_meta_box('dashboard_quick_press', 'dashboard', 'side'); // Quick Press widget
    remove_meta_box('dashboard_recent_drafts', 'dashboard', 'side'); // Recent Drafts
    remove_meta_box('dashboard_secondary', 'dashboard', 'side'); // Other WordPress News
    remove_meta_box('dashboard_incoming_links', 'dashboard', 'normal'); //Incoming Links
    remove_meta_box('dashboard_recent_comments', 'dashboard', 'normal'); // Recent Comments
    remove_meta_box('dashboard_activity', 'dashboard', 'normal'); // Activity
    remove_meta_box('e-dashboard-overview', 'dashboard', 'normal'); //Remove Elementor
}

/**
* Remove Rank Math footer message
*/

add_action('rank_math/whitelabel', '__return_true');


add_action('wp_dashboard_setup', 'md_remove_dashboard_widget');
/**
 *  Remove Site Health Dashboard Widget
 *
 */
function md_remove_dashboard_widget()
{
    remove_meta_box('dashboard_site_health', 'dashboard', 'normal');
}

/**
 *  Remove Site Title from all pages
 *
 */

function ele_disable_page_title($return)
{
    return false;
}
add_filter('hello_elementor_page_title', 'ele_disable_page_title');

/*--------------------------------------------------------------------------
 * Include split-out modules
 *------------------------------------------------------------------------*/

require_once __DIR__ . '/form-date-event.php';
require_once __DIR__ . '/form-confirmations.php';
require_once __DIR__ . '/form-ltrp.php';
require_once __DIR__ . '/form-curriculum.php';
require_once __DIR__ . '/form-validation.php';
require_once __DIR__ . '/form-helpers.php';
require_once __DIR__ . '/footer-assets.php';
