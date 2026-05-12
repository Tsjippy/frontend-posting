<?php
namespace TSJIPPY\FRONTENDPOSTING;
use TSJIPPY;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'wp_enqueue_scripts', __NAMESPACE__.'\loadAssets');
function loadAssets() {
    wp_register_style('tsjippy_frontend_style', TSJIPPY\pathToUrl(PLUGINPATH.'css/frontend_posting.min.css'), array(), PLUGINVERSION);
	
    //Load js
    wp_register_script('tsjippy_forms_script', TSJIPPY\pathToUrl(PLUGINPATH.'../tsjippy-forms/js/forms.min.js'), array('tsjippy_formsubmit_script'), PLUGINVERSION,true);

    $dependables    = apply_filters('tsjippy-frontend-content-js', array('tsjippy_fileupload_script', 'tsjippy_forms_script'));
	wp_register_script('tsjippy_frontend_script', TSJIPPY\pathToUrl(PLUGINPATH.'js/frontend_posting.min.js'), $dependables, PLUGINVERSION, true);

    wp_enqueue_script('tsjippy_edit_post_script', TSJIPPY\pathToUrl(PLUGINPATH.'js/edit_post.min.js'), array('tsjippy_formsubmit_script'), PLUGINVERSION, true);
    
    $frontEndPostPage   = SETTINGS['front-end-post-page'] ?? false;

    if($frontEndPostPage){
        $url    = TSJIPPY\getValidPageLink($frontEndPostPage);
        if($url){
            wp_add_inline_script('tsjippy_edit_post_script', "var edit_post_url = '$url'", 'before');
        }

        if(is_numeric(get_the_ID()) && get_the_ID() == $frontEndPostPage ){
            wp_enqueue_style('tsjippy_frontend_style');
        }
    }
}

add_action( 'wp_enqueue_media', __NAMESPACE__.'\loadMediaAssets');
function loadMediaAssets(){
    wp_enqueue_script('tsjippy_library_cat_script', TSJIPPY\pathToUrl(PLUGINPATH.'js/library.min.js'), [], PLUGINVERSION);
    wp_localize_script(
        'tsjippy_library_cat_script',
        'categories',
        get_categories( array(
            'taxonomy'		=> 'attachment_cat',
            'hide_empty' 	=> false
        ) )
    );
}