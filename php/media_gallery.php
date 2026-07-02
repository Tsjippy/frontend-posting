<?php

namespace TSJIPPY\FRONTENDPOSTING;

use TSJIPPY;

if (! defined('ABSPATH')) {
    exit;
}

add_filter('tsjippy-media-gallery-edit-link', __NAMESPACE__ . '\editLink', 10, 2);/**
 * Filter the edit link for media gallery items.
 *
 * @param string $link The original edit link.
 * @param int    $id   The ID of the media item.
 *
 * @return string The modified edit link.
 */
function editLink($link, $id)
{
    $url            = get_permalink(SETTINGS['front-end-post-page'] ?? '');
    if ($url) {
        return "<a href='$url?post-id=$id' class='button'>Edit</a>";
    }
    return $link;
}
