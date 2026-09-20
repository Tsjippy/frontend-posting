<?php

namespace TSJIPPY\FRONTENDPOSTING;

use TSJIPPY;

if (! defined('ABSPATH')) {
    exit;
}

add_action('wp_enqueue_scripts', __NAMESPACE__ . '\loadAssets');
/**
 * Load assets for the frontend posting form.
 *
 * @return void
 */
function loadAssets()
{
    wp_register_style('tsjippy_frontend_style', TSJIPPY\pathToUrl(PLUGINPATH . 'css/frontend_posting.min.css'), array(), PLUGINVERSION);

    $dependables    = apply_filters('tsjippy-frontend-content-js', array('@tsjippy/fileupload_script', '@tsjippy/forms_script'));
    wp_register_script_module('@tsjippy/frontend_script', TSJIPPY\pathToUrl(PLUGINPATH . 'js/frontend_posting' . TSJIPPY\JSEXTENSION), $dependables, PLUGINVERSION);

    wp_enqueue_script_module('@tsjippy/edit_post_script', TSJIPPY\pathToUrl(PLUGINPATH . 'js/edit_post' . TSJIPPY\JSEXTENSION), array('@tsjippy/formsubmit_script'), PLUGINVERSION);

    add_filter( 'script_module_data_@tsjippy/edit_post_script', function($data){
        $frontEndPostPage   = SETTINGS['front-end-post-page'] ?? createDefaultPages('front-end-post-page');

        $url    = TSJIPPY\getValidPageLink($frontEndPostPage);
        if ($url) {
            $data['url'] = $url;
        }
        return $data; 
    } );

    $frontEndPostPage   = SETTINGS['front-end-post-page'] ?? createDefaultPages('front-end-post-page');
    if (is_numeric(get_the_ID()) && get_the_ID() == $frontEndPostPage) {
        wp_enqueue_style('tsjippy_frontend_style');
    }
}

add_action('wp_enqueue_media', __NAMESPACE__ . '\loadMediaAssets');
/**
 * Load media assets for the frontend posting form.
 *
 * @return void
 */
function loadMediaAssets()
{
    wp_enqueue_script_module('@tsjippy/library_cat_script', TSJIPPY\pathToUrl(PLUGINPATH . 'js/library' . TSJIPPY\JSEXTENSION), [], PLUGINVERSION);

    add_filter( 'script_module_data_@tsjippy/library_cat_script', function($data){
        $data['library_categories'] = get_categories(array(
            'taxonomy'        => 'attachment_cat',
            'hide_empty'     => false
        ));

        return $data; 
    } );
}
