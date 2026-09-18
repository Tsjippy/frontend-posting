<?php

namespace TSJIPPY\FRONTENDPOSTING;

use TSJIPPY;

if (! defined('ABSPATH')) {
    exit;
}

// filter library if needed
add_filter('ajax_query_attachments_args',  __NAMESPACE__ . '\attachmentArgs');
/**
 * Filter the query arguments for attachments in the media library.
 *
 * @param array $query The original query arguments.
 *
 * @return array The modified query arguments.
 */
function attachmentArgs($query)
{
    if (!empty($_REQUEST['query']['category'])) {
        $category = TSJIPPY\sanitize($_REQUEST['query']['category'] ?? '');

        $query['tax_query'] = array(
            array(
                'taxonomy'  => 'attachment_cat',
                'field'     => 'slug',
                'terms'     => $category,
            )
        );
    }

    return $query;
}

add_action('init', __NAMESPACE__ . '\initTaxonomies');
/**
 * Initialize custom taxonomies for attachments and pages.
 *
 * This function registers the 'attachment_cat' taxonomy for attachments and ensures that
 * the 'category' and 'post_tag' taxonomies are available for pages.
 *
 * @return void
 */
function initTaxonomies()
{

    $taxonomies = array('category', 'post_tag'); // add the 2 tax to ...
    foreach ($taxonomies as $tax) {
        register_taxonomy_for_object_type($tax, 'page');
    }

    TSJIPPY\createTaxonomies('attachment_cat', 'attachment', 'attachments');
}

/**
 * Add categories to attachment page
 *
 * WP expects a comma seperated list of cat slugs, so
 * we create a checkbox who update a hidden input wiht the comma seperated checkboxes
 */

add_filter('attachment_fields_to_edit', __NAMESPACE__ . '\attachmentFieldsToEdit', 10, 2);
/**
 * Modify the attachment fields to include a category selection.
 *
 * @param array   $formFields The original form fields for the attachment.
 * @param \WP_Post $post      The attachment post object.
 *
 * @return array The modified form fields with category selection.
 */
function attachmentFieldsToEdit($formFields, $post)
{
    $categories    = get_categories(array(
        'orderby'    => 'name',
        'order'      => 'ASC',
        'taxonomy'   => 'attachment_cat',
        'hide_empty' => false,
    ));

    ob_start();
    ?>
    <div class='attachment-cat-wrapper'>
        <?php
        foreach ($categories as $category) {
            $name     = str_replace('-', ' ', ucfirst($category->slug));
            $catId    = $category->cat_ID;
            $taxonomy = $category->taxonomy;

        ?>
            <label>
                <input type='checkbox' 
                    name='attachment-categories[]' 
                    <?php echo has_term($catId, $taxonomy, $post->ID) ? 'checked' : '' ?> 
                    style='width: initial' 
                    class='attachment-cat-checkbox' 
                    value='<?php echo esc_attr($catId); ?>' 
                >
                <?php echo esc_attr($name); ?>
            </label><br>
        <?php
        }
        ?>
    </div>
    <?php

    $formFields['attachment-cat']['input'] = 'html';
    $formFields['attachment-cat']['html']  = ob_get_clean();
    $formFields['attachment-cat']['label'] = 'Categories';

    return $formFields;
}

add_action('edit_attachment', __NAMESPACE__ . '\storeCategory');
/**
 * Stores or removes the visibility
 * 
 * @param   int $attachmentId   The id to store visibility for
 */
function storeCategory($attachmentId)
{
    wp_set_object_terms( $attachmentId, array_map('intval', $_REQUEST['attachment-categories']), 'attachment_cat', true );
}

add_action('tsjippy-before-archive', __NAMESPACE__ . '\beforeArchive');
/**
 * Display a button to add a new post or event before the archive.
 *
 * @param string $type The type of content (e.g., 'post', 'event').
 *
 * @return void
 */
function beforeArchive($type)
{
    $url            = get_permalink(SETTINGS['front-end-post-page'] ?? createDefaultPages('front-end-post-page'));
    if (is_numeric($url)) {
        if ($type == 'event') {
            $text    = "Add an event to the calendar";
        } else {
            $text    = "Add a new $type";
        }

    ?>
        <a href='<?php echo esc_url("$url?type=$type"); ?>' class='button'>
            <?php echo esc_html($text); ?>
        </a>
        <br>
<?php
    }
}

add_filter('tsjippy-empty-description', __NAMESPACE__ . '\emptyDescription', 10, 2);
/**
 * Display a message and a button to add a description for a post or page that lacks one.
 *
 * @param string  $message The original message.
 * @param \WP_Post $post    The post object.
 *
 * @return string The modified message with a button to add a description.
 */
function emptyDescription($message, $post)
{
    $url            = get_permalink(SETTINGS['front-end-post-page'] ?? createDefaultPages('front-end-post-page'));
    if (!$url) {
        $url    = '';
    }
    $message    = "<div style='margin-top:10px;'>";
    $message    .= "This {$post->post_type} lacks a description.<br>";
    $message    .= "Please add one.<br>";
    $message    .= "<a href='$url?post-id={$post->ID}' class='button'>Add description</a>";
    $message    .= '</div>';

    return $message;
}

add_filter('tsjippy-empty-taxonomy', __NAMESPACE__ . '\emptyTax', 10, 2);
/**
 * Display a message and a button to add a taxonomy term for a post or page that lacks one.
 *
 * @param string  $message The original message.
 * @param string  $type    The type of taxonomy (e.g., 'category', 'tag').
 *
 * @return string The modified message with a button to add a taxonomy term.
 */
function emptyTax($message, $type)
{
    $url         = get_permalink(SETTINGS['front-end-post-page'] ?? createDefaultPages('front-end-post-pagee'));
    $message    .= "<br><a href='$url?type=$type' class='button'>Add a $type</a>";
    return $message;
}
