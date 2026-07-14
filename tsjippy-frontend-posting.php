<?php

namespace TSJIPPY\FRONTENDPOSTING;

use TSJIPPY;

/**
 * Plugin Name:          Tsjippy Frontend Posting
 * Description:          This plugin makes it possible to add and edit pages, posts and custom post types. Just place this shortcode on any page: <code>[front_end_post]</code>. An overview of the posts created by the current user can be displayed using the: <code>[your_posts]</code> shortcode. If anyone without publish rights tries to add or edit a page, it will be stored as pending. An overview of pending content can be shown using the <code>[pending_pages]</code> shortcode. You can use the <code>[pending_post_icon]</code> shortcode as an indicator, displaying the amount of pending posts in menu items. This plugin also adds a custom post status: archived. Meaning a post is not visible but still kept for reference
 * Version:              10.5.8
 * Author:               Ewald Harmsen
 * AuthorURI:            harmseninnigeria.nl
 * Requires at least:    6.3
 * Requires PHP:         8.3
 * Tested up to:         7.0
 * Plugin URI:            https://github.com/Tsjippy/frontendposting/
 * Tested:               7.0
 * TextDomain:            tsjippy
 * Requires Plugins:    
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 *
 * @author Ewald Harmsen
 */
if (! defined('ABSPATH')) {
    exit;
}

// Define constants
define(__NAMESPACE__ . '\PLUGIN', plugin_basename(__FILE__));
define(__NAMESPACE__ . '\PLUGINPATH', __DIR__ . '/');
define(__NAMESPACE__ . '\PLUGINSLUG', str_replace('tsjippy-', '', basename(__FILE__, '.php')));
define(__NAMESPACE__ . '\PLUGINVERSION', get_plugin_data(__FILE__, false, false)['Version']);
define(__NAMESPACE__ . '\SETTINGS', get_option('tsjippy_frontend-posting_settings', []));

// run right before activation
register_activation_hook(__FILE__, function () {
    if(file_exists(__DIR__  . '/shared-functionality/loader.php')){
        require_once(__DIR__  . '/shared-functionality/loader.php');
    }

    createDefaultPages();

    if(function_exists('TSJIPPY\activate')){
        \TSJIPPY\activate();
    }
});

// run on deactivation
register_deactivation_hook(__FILE__, function () {
    $postId    = SETTINGS['front-end-post-page'] ?? false;
    if ($postId) {
        wp_delete_post($postId, true);
    }

    $postId    = SETTINGS['pending-posts-page'] ?? false;
    if ($postId) {
        wp_delete_post($postId, true);
    }
});

// Load shared code
if(file_exists(__DIR__  . '/shared-functionality/loader.php')){
    require_once(__DIR__  . '/shared-functionality/loader.php');
}

/**
 * Creates default pages if needed
 * 
 * @param string    $returnKey  The key to return a value for, default empty
 */
function createDefaultPages($returnKey=''){
    /**
     *  Default pages
     */
    $settings    = SETTINGS;

    // Create frontend posting page
    if(!isset($settings['front-end-post-page'])){
        $settings['front-end-post-page']    = TSJIPPY\ADMIN\createDefaultPage('Add content', '<!-- wp:tsjippy-frontend-posting/front-end-posting /-->');
    }
    
    if(!isset($settings['pending-posts-page'])){
        $settings['pending-posts-page']     = TSJIPPY\ADMIN\createDefaultPage('Pending Posts', '<!-- wp:tsjippy-frontend-posting/pending-posts /-->');
    }

    update_option('tsjippy_frontend-posting_settings', $settings);

    if(!empty($returnKey) && isset($settings[$returnKey])){
        return $settings[$returnKey];
    }
}