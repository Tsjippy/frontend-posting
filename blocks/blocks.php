<?php

namespace TSJIPPY\FRONTENDPOSTING;

use TSJIPPY;

add_action('init', __NAMESPACE__ . '\initBlocks');
function initBlocks()
{
    // Your posts block
    register_block_type(
        'tsjippy-frontend-posting/your-posts',
        array(
            'title'           => __( 'User Posts', 'tsjippy' ),
            'render_callback' => __NAMESPACE__ . '\yourPosts',
            'supports'        => array(
                'autoRegister' => true,
            ),
        ),
    );

    // Pending posts block
    register_block_type(
        'tsjippy-frontend-posting/pending-posts',
        array(
            'title'           => __( 'Pending Posts', 'tsjippy' ),
            'render_callback' => __NAMESPACE__ . '\pendingPages',
            'supports'        => array(
                'autoRegister' => true,
            ),
        ),
    );

    // Frontend Posting Form
    register_block_type(
        'tsjippy-frontend-posting/front-end-posting',
        array(
            'title'           => __( 'Frontend Posting Block', 'tsjippy' ),
            'render_callback' => function ( $attributes ) {
                $frontEndContent    = new FrontEndContent();
                return $frontEndContent->frontendPost();
            },
            'supports'        => array(
                'autoRegister' => true,
            ),
        )
    );

    // Old Posts
    register_block_type(
        'tsjippy-frontend-posting/old-posts',
        array(
            'title'           => __( 'Frontend Posting Block', 'tsjippy' ),
            'attributes'      => array(
                'post_types'   => array(
                    'label'   => __( 'Post Types', 'tsjippy' ),
                    'type'    => 'string',
                    'enum'    => get_post_types(['public' => true]),
                    'default' => 'page',
                )
            ),
            'render_callback' => __NAMESPACE__.'/oldPages',
            'supports'        => array(
                'autoRegister' => true,
            ),
        )
    );

    // register custom meta tag field
    register_post_meta('', "tsjippy_expirydate", array(
        'show_in_rest'      => true,
        'single'            => true,
        'type'              => 'string',
        'default'           => '',
        'sanitize_callback' => 'sanitize_text_field'
    ));

    register_post_meta('', "tsjippy_static_content", array(
        'show_in_rest'      => true,
        'single'            => true,
        'type'              => 'boolean',
        'default'           => false,
        'sanitize_callback' => 'sanitize_text_field'
    ));
}



add_action('enqueue_block_editor_assets', __NAMESPACE__ . '\loadBlockAssets');
/**
 * Load the js for the post settings block
 */
function loadBlockAssets()
{
    TSJIPPY\registerScripts();

    wp_enqueue_script('tsjippy_table_script');

    wp_enqueue_script(
        'tsjippy-expiry-date-block',
        TSJIPPY\pathToUrl(PLUGINPATH . 'blocks/expiry-date/build/index.js'),
        ['wp-blocks', 'wp-dom', 'wp-dom-ready', 'wp-edit-post'],
        PLUGINVERSION
    );
}

/**
 * Display all posts and pages submitted by the current user.
 *
 * @return string The HTML table of the user's posts and pages.
 */
function yourPosts()
{
    //load js
    wp_enqueue_script('tsjippy_table_script');

    //Get all posts for the current user
    $postTypes    = get_post_types(['public' => true]);
    unset($postTypes['attachment']);

    $userUserPosts = get_posts(
        array(
            'post_type'        => $postTypes,
            'post_status'    => 'any',
            'author'        => get_current_user_id(),
            'orderby'        => 'post_date',
            'order'            => 'ASC',
            'numberposts'    => -1,
        )
    );

    ob_start();

    ?>
    <h2 class="table-title">
        Content submitted by you
    </h2>
    <table class="tsjippy table" id="user-posts">
        <thead>
            <tr>
                <th>
                    Date
                </th>
                <th>
                    Type
                </th>
                <th>
                    Title
                </th>
                <th>
                    Status
                </th>
                <th>
                    Actions
                </th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($userUserPosts as $post):
                $date   = get_the_modified_date('d F Y', $post);
                $type   = ucfirst($post->post_type);
                $title  = $post->post_title;
                $status = ucfirst($post->post_status);
                if ($status == 'Publish') {
                    $status = 'Published';
                }
                $url        = get_permalink($post);
                $editUrl    = get_permalink(SETTINGS['front-end-post-page'] ?? createDefaultPages('front-end-post-page'));
                $editUrl    = add_query_arg(['post-id' => $post->ID], $editUrl);
                $view       = ($post->post_status == 'publish') ? 'View' : 'Preview';
            ?>
                <tr class="table-row">
                    <td>
                        <?php echo esc_html($date); ?>
                    </td>
                    <td>
                        <?php echo esc_html($type); ?>
                    </td>
                    <td>
                        <?php echo esc_html($title); ?>
                    </td>
                    <td>
                        <?php echo esc_html($status); ?>
                    </td>
                    <td>
                        <span>
                            <a href='<?php echo esc_url($url); ?>'>
                                <?php echo esc_html($view); ?>
                            </a>
                        </span>
                        <span style='margin-left:20px;'>
                            <a href='<?php echo esc_url($editUrl); ?>'>
                                Edit
                            </a>
                        </span>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php

    return ob_get_clean();
}

/**
 * Display all pages and posts that are pending.
 *
 * @return string The HTML content of the pending pages and posts.
 */
function pendingPages()
{
    //Get all the posts with a pending status
    $pendingPosts     = get_posts(
        array(
            'post_status'    => 'pending',
            'post_type'        => 'any',
            'numberposts'    => -1
        )
    );

    //Get all the posts with a pending revision
    $pendingRevisions     = get_posts(
        array(
            'post_status'    => 'inherit',
            'post_type'        => 'change',
            'numberposts'    => -1
        )

    );

    $url            = get_permalink(SETTINGS['front-end-post-page'] ?? createDefaultPages('front-end-post-page'));
    if (!$url) {
        return '';
    }

    ob_start();
    ?>
    <p>
        <?php
        //Only if there are any pending posts
        if ($pendingPosts) {
        ?>
            <strong>
                Pending content:
            </strong>
            <br>
            <ul>
                <?php
                    //For each pending post add a link to edit the post
                    foreach ($pendingPosts as $post) {
                        $url = add_query_arg(['post-id' => $post->ID], $url);
                        if (strtotime($post->post_date_gmt) > time()) {
                            $date    = gmdate(TSJIPPY\DATEFORMAT, strtotime($post->post_date_gmt));
                ?>
                        <li>
                            <?php echo wp_kses_post($post->post_title); ?> (scheduled for <?php echo esc_attr($date); ?>)
                            <a href='<?php echo esc_url($url); ?>'>
                                Publish now
                            </a>
                        </li>
                    <?php
                        } else {
                    ?>
                        <li>
                            <?php echo wp_kses_post($post->post_title); ?>
                            <a href='<?php echo esc_url($url); ?>' target='_blank'>
                                Review and publish
                            </a>
                        </li>
                <?php
                        }
                    }
                ?>
            </ul>
            <?php
                }

                if ($pendingRevisions) {
            ?>
            <br>
            <br>
            <strong>
                Pending content revisions:
            </strong>
            <br>
            <ul>
                <?php
                    //For each pendingRevisions post add a link to edit the post
                    foreach ($pendingRevisions as $post) {
                        $url = add_query_arg(['post-id' => $post->ID], $url);
                ?>
                    <li>
                        <?php echo wp_kses_post($post->post_title); ?>
                        <a href='<?php echo esc_url($url); ?>'>
                            Review changes
                        </a>
                    </li>
                <?php
                    }
                ?>
            </ul>
            <?php
        }
        ?>
    </p>
    <?php

    if ($pendingPosts || $pendingRevisions) {
        return ob_get_clean();
    }

    ob_end_clean();

    return "<p>No pending posts or pages found</p>";
}

/**
 * Shortcode to display all pages that have not been updated for a long time.
 *
 * @return string The HTML content of the old pages.
 */
function oldPages()
{
    $oldPages    = getOldPages();

    ob_start();
    ?>
    <table class="tsjippy table">
        <tr>
            <th>
                Title
            </th>
            <th>
                Last Modified
            </th>
            <th>
                Author
            </th>
        </tr>

        <?php
        foreach ($oldPages as $page) {
            $url                 = get_permalink($page);
            $authorUrl           = get_author_posts_url($page->post_author);
            $authorName          = get_userdata($page->post_author)->first_name;
            $secondsSinceUpdated = time() - get_post_modified_time('U', true, $page);
            $pageAge             = round($secondsSinceUpdated / 60 / 60 / 24);
        ?>
            <tr>
                <td>
                    <a href="<?php echo esc_url($url); ?>">
                        <?php echo esc_html($page->post_title); ?>
                    </a>
                </td>
                <td>
                    <a href="<?php echo esc_url($url); ?>">
                        <?php echo esc_html($pageAge); ?> days
                    </a>
                </td>
                <td>
                    <a href="<?php echo esc_url($authorUrl); ?>">
                        <?php echo esc_html($authorName); ?>
                    </a>
                </td>
            </tr>
        <?php
        }
        ?>
    </table>
    <?php

    return ob_get_clean();
}