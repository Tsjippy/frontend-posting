<?php

namespace TSJIPPY\FRONTENDPOSTING;

use TSJIPPY;

if (! defined('ABSPATH')) {
    exit;
}

// Registering custom post status
add_action('init', __NAMESPACE__ . '\initPostStatus');
/**
 * Register a custom post status called "archived"
 */
function initPostStatus()
{
    register_post_status('archived', array(
        'label'                     => _x('Archived', 'post', '%TEXTDOMAIN%'),
        'public'                    => false,
        'exclude_from_search'       => false,
        'show_in_admin_all_list'    => true,
        'show_in_admin_status_list' => true,
        'protected'                 => true,
        /* translators: %d: post count. */
        'label_count'               => _n_noop('Archived <span class="count">(%d)</span>', 'Archived <span class="count">(%d)</span>', '%TEXTDOMAIN%'),
    ));
}

// Display "— Archived" after post name on the dashobard, like you would see "— Draft" for draft posts.
// Not shown when viewing only archived posts because that would be redundant.
add_filter('display_post_states', __NAMESPACE__ . '\displayPostStatus');
/**
 * Display the custom post status "archived" in the post list table.
 *
 * @param array $statuses The existing post statuses.
 * @return array The modified post statuses.
 */
function displayPostStatus($statuses)
{
    global $post; // we need it to check current post status

    if (get_query_var('post_status') != 'archived') { // not for pages with all posts of this status
        if (($post->post_status ?? '') == 'archived') { // если статус поста - Архив
            return array('Archived'); // returning our status label
        }
    }

    return $statuses; // returning the array with default statuses
}
