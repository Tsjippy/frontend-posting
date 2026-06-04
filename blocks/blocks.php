<?php
namespace TSJIPPY\FRONTENDPOSTING;
use TSJIPPY;

add_action('init', __NAMESPACE__ . '\initBlocks');
function initBlocks() {
    register_block_type(
        __DIR__ . '/your_posts/build',
        array(
            'render_callback' => __NAMESPACE__ . '\yourPosts',
       )
   );

    register_block_type(
        __DIR__ . '/pending_pages/build',
        array(
            'render_callback' => __NAMESPACE__ . '\pendingPages',
       )
   );

    // register custom meta tag field
    register_post_meta('', 'expirydate', array(
        'show_in_rest'         => true,
        'single'             => true,
        'type'                 => 'string',
        'default'            => '',
        'sanitize_callback' => 'sanitize_text_field'
   ));

    register_post_meta('', 'static_content', array(
        'show_in_rest'         => true,
        'single'             => true,
        'type'                 => 'boolean',
        'default'            => false,
        'sanitize_callback' => 'sanitize_text_field'
   ));
}

add_action('enqueue_block_editor_assets', __NAMESPACE__ . '\loadBlockAssets');
function loadBlockAssets() {
    TSJIPPY\registerScripts();

    wp_enqueue_script('tsjippy_table_script');

    wp_enqueue_script(
        'tsjippy-expiry-date-block',
        TSJIPPY\pathToUrl(PLUGINPATH. 'blocks/expiry-date/build/index.js'),
        [ 'wp-blocks', 'wp-dom', 'wp-dom-ready', 'wp-edit-post' ],
        PLUGINVERSION
   );
}