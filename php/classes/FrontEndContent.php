<?php

namespace TSJIPPY\FRONTENDPOSTING;

use TSJIPPY;
use stdClass;
use WP_Error;

if (! defined('ABSPATH')) {
    exit;
}

class FrontEndContent
{
    public int|WP_Error|null|string $postId;
    public \WP_User $user;
    public \WP_Post|null $post;
    public string $postType;
    public string $name;
    public string $postTitle;
    public array $postCategory;
    public string $postContent;
    public int|null $postParent;
    public int $postImageId;
    public string $postName;
    public bool $lite;
    public bool $fullrights;
    public bool $editRight;
    public bool $update;
    public string $action;
    public string $actionText;
    public string|null $status;
    public array $postTypes;            // possible categories of all post types
    public array $postCategories;        // category for the current post
    public string|null $publishDate;
    public \WP_Post|array|null $oldPost;
    public \WP_Post|array|null $orgPost;
    public int|null $author; 

    public function __construct()
    {
        $this->processRequestParams();

        $this->user           = wp_get_current_user();
        $this->name           = "post";
        $this->postTitle      = '';
        $this->postCategory   = [];
        $this->postContent    = '';
        $this->postParent     = null;
        $this->postImageId    = 0;
        $this->update         = false;
        $this->action         = '';
        $this->actionText     = '';
        $this->postCategories = [];
        $this->oldPost        = null;
        $this->orgPost        = null;

        if ($this->user->has_cap('edit_others_posts')) {
            $this->fullrights = true;
        } else {
            $this->fullrights = false;
        }

        if (get_class($this) == __NAMESPACE__ . '\FrontEndContent') {
            //Add tinymce plugin
            add_filter('mce_external_plugins', array($this, 'addTinymcePlugin'), 999);

            //add tinymce button
            add_filter('mce_buttons', array($this, 'registerButtons'));
        }

        $postTypes            = get_post_types(['public' => true]);
        foreach ($postTypes as $postType => &$taxonomy) {
            if ($postType == 'post' || $postType == 'page') {
                $taxonomy    = 'category';
            } elseif ($postType == 'attachment') {
                $taxonomy    = 'attachment_cat';
            } else {
                $taxonomy    = $postType . 's';
            }
        }

        $this->postTypes    = apply_filters('tsjippy-frontend-content-post-types-and-tax', $postTypes, $this);
    }

    private function processRequestParams(){
        
        $this->postType     = TSJIPPY\sanitize($_REQUEST['type'] ?? 'post');
        $this->postType     = TSJIPPY\sanitize($_REQUEST['post-type'] ?? $this->postType);

        //show lite version of location by default
        if (str_contains($this->postType, '_lite')) {
            $this->lite         = true;
        }else{
            $this->lite         = false;
        }

        $this->postId       = $_REQUEST['post-id'] ?? null;
        if(!empty($this->postId)){
            $this->postId   = (int) $this->postId;
            $this->post     = get_post($this->postId);
        }else{
            $this->post = $this->postId = null;
        }

        $this->publishDate  = TSJIPPY\sanitize($_REQUEST['publish-date'] ?? null);

        $this->author       = (int) $_REQUEST['post-author'] ?? null;

        $this->status       = TSJIPPY\sanitize($_REQUEST['post-status']);
    }

    /**
     *
     * Renders the form to edit existing content or create new content
     *
     * @return   string     The form html
     *
     **/
    public function frontendPost($hide = false)
    {
        if (!function_exists('_wp_translate_postdata')) {
            include_once ABSPATH . 'wp-admin/includes/post.php';
        }

        //Load js
        wp_enqueue_style('tsjippy_frontend_style');
        wp_enqueue_script('tsjippy_frontend_script');
        wp_enqueue_media();

        ob_start();

        $this->fillPostData();

        //Show warning if not allowed to edit
        $this->hasEditRights();
        if (!$this->editRight && is_numeric($this->postId)) {
            return '<div class="error">You do not have permission to edit this page.</div>';
        }

        if (is_numeric($this->postId)) {
            //Show warning if someone else is editing
            $currentEditingUser = wp_check_post_lock($this->postId);
            if (is_numeric($currentEditingUser)) {
                header("Refresh: 30;");
                return "<div class='error' id='    '>" . get_userdata($currentEditingUser)->display_name . " is currently editing this {$this->postType}, please wait.<br>We will refresh this page every 30 seconds to see if you can go ahead.</div>";
            }

            //Current time minus last modified time
            $secondsSinceUpdated = time() - get_post_modified_time('U', true, $this->post);

            //Show warning when post has been updated recently
            if ($secondsSinceUpdated < 3600 && $secondsSinceUpdated > -1) {
                $minutes = intval($secondsSinceUpdated / 60);
?>
                <div class='warning'>
                    This <?php echo esc_html($this->postType); ?> has been updated <span id='minutes'><?php echo esc_html($minutes); ?></span> minutes ago.
                </div>
            <?php
            }

            //Show warning when post is in trash
            if ($this->post->post_status == 'trash') {
            ?>
                <div class='warning'>
                    This <?php echo esc_html($this->postType); ?> has been deleted.<br>
                    You can republish if that should not be the case.
                </div>
        <?php
            }
        }

        ?>
        <div
            id="frontend-upload-form"
            <?php if ($hide) {
                echo 'class="hidden"';
            } ?>
            style='margin-top: 10px;'>
            <?php
            if (has_blocks($this->postContent)) {
                $url    = get_edit_post_link($this->postId);
                ?>
                <div class='warning'>
                    This <?php echo esc_html($this->postType); ?> contains some Gutenberg blocks.<br>
                    Click <a href='<?php echo esc_url($url); ?>'>here</a> if you want to switch to the Gutenberg editor<br>
                </div>
            <?php
            }

            $this->update    = false;
            if (is_numeric($this->postId) && $this->post->post_status == 'publish') {
                $this->update    = true;
            }
            ?>
            <button
                class='button tsjippy 
                <?php if (!$this->lite) {
                    echo 'hidden';
                } ?> 
                show'
                id='show-all-fields'>
                Show all fields
            </button>
            <?php

            $this->postTypeSelector();

            $this->addModals();
            do_action('tsjippy-frontend-content-post-modal');

            $this->showChanges();

            //Write the form to create all posts except events
            ?>
            <form id="postform">
                <input type="hidden" class="no-reset" name="post-status" value="pending">
                <input type="hidden" class="no-reset" name="post-type" value="<?php echo esc_attr($this->postType); ?>">
                <input type="hidden" class="no-reset" name="post-image-id" value="<?php echo esc_attr($this->postImageId); ?>">
                <input type="hidden" class="no-reset" name="update" value="<?php echo esc_attr($this->update); ?>">
                <input type='hidden' class='no-reset' name='post-id' value='<?php echo esc_attr($this->postId); ?>'>

                <h4>Title</h4>
                <input type="text" name="post-title" class='block' value="<?php echo esc_attr($this->postTitle); ?>" required>

                <?php
                do_action('tsjippy-frontend-content-post-before-content', $this);

                $this->showCategories();

                ?>
                <div class='property attachment hidden'>
                    <?php
                    //Existing media
                    if (is_numeric($this->postId)) {
                        $image    = wp_get_attachment_image($this->postId);

                    ?>
                        <h4>
                            Attachment preview
                        </h4>
                    <?php
                        // phpcs:ignore
                        echo apply_filters('tsjippy-frontend-content-attachment-preview', $image, $this->postId);
                    } else {
                    ?>
                        <h4>Upload your file</h4>
                    <?php
                        $uploader = new TSJIPPY\FILEUPLOAD\FileUploadHtml($this->user->ID);
                        // phpcs:ignore
                        echo $uploader->getUploadHtml(inputName: 'attachment', targetDir: 'private', editBeforeUpload: true);
                    }
                    ?>
                </div>

                <div
                    id="featured-image-div"
                    <?php if ($this->postImageId == 0) {
                    ?>
                    class="hidden"
                    <?php
                    } ?>>
                    <h4 name="post-image-label">
                        Featured image:
                    </h4>

                    <span id='featured-image-wrapper' style='max-height:150px;'>
                        <?php
                        if ($this->postImageId != 0) {
                            // phpcs:ignore
                            echo get_the_post_thumbnail(
                                esc_html($this->postId),
                                'thumbnail',
                                array(
                                    'title' => 'Featured Image',
                                    'class' => 'postimage'
                                )
                            );
                            $text     = 'Change';
                        } else {
                            $text = 'Add';
                        }
                        ?>
                    </span>
                    <button type='button' class='remove-featured-image button'>
                        X
                    </button>
                </div>

                <div
                    class='post-content-wrapper lite
                    <?php
                    if ($this->lite) {
                        echo 'hidden';
                    } ?>'>

                    <div class='title-wrapper'>
                        <?php
                        //Post content title
                        $class = 'post page property';
                        if ($this->postType != 'post' && $this->postType != 'page') {
                            $class .= ' hidden';
                        }

                        ?>
                        <h4 class='$class' name='post-content-label'>
                            <span class="capitalize replace-post-type">
                                <?php echo esc_html(ucfirst($this->postType)) ?>
                            </span> content
                        </h4>

                        <h4 class='property attachment hidden' name='attachment-content-label'>
                            Description:
                        </h4>
                        <?php

                        do_action('tsjippy-frontend-content-post-content-title', $this->postType);
                        ?>
                    </div>

                    <?php

                    //make it possible to select or upload a featured image
                    if (current_user_can('upload_files')) {
                        add_action(
                            'media_buttons',
                            function () use ($text) {
                            ?>
                            <button type='button' name='add-featured-image' class='button add-media'>
                                <span class='wp-media-buttons-icon'></span>
                                <?php echo esc_html($text); ?> Featured Image
                            </button>
                            <?php
                            },
                            5
                        );
                    }

                    //output tinymce window
                    $settings = array(
                        'wpautop' => false,
                        'forced_root_block' => true,
                        'convert_newlines_to_brs' => true,
                        'textarea_name' => "post-content",
                        'textarea_rows' => 10
                    );
                    wp_editor($this->postContent, 'post-content', $settings);
                    ?>
                </div>

                <?php
                $this->metaOptions();

                //Add a draft button for new posts
                if ($this->postId == null || ($this->post->post_status != 'publish' && $this->post->post_status != 'inherit')) {
                ?>
                    <div class='submit-wrapper' style='display: flex;'>
                        <button type='button' class='button savedraft' name='draft-post'>
                            <?php
                            if ($this->postId == null) {
                                ?>
                                Save 
                                <span class='replace-post-type'>
                                    <?php echo esc_html($this->postName);?>
                                </span> as draft
                                <?php
                            } else {
                                ?>
                                Update this 
                                <span class='replace-post-type'>
                                    <?php echo esc_html($this->postName);?>
                                </span> draft
                                <?php
                            }
                            ?>
                        </button>
                    </div>
                <?php
                }
                TSJIPPY\addSaveButton('submit_post', $this->action);

                if ($this->fullrights) {
                ?>
                    <div class='submit-wrapper' style='display: flex;'>
                        <button type='button' name='publish-post' class='button'>
                            Publish <span class='replace-post-type'>
                                <?php echo esc_attr($this->postName); ?>
                            </span>
                        </button>
                    </div>
                <?php
                }

                ob_start();
                // Add archive button
                if (!empty($this->post) && $this->post->post_status != 'archived') {
                ?>
                    <div class='submit-wrapper'>
                        <button type='submit' class='button' name='archive-post' data-post-id='<?php echo esc_html($this->postId); ?>'>
                            Archive <?php echo esc_html($this->postName); ?>
                        </button>
                    </div>
                <?php
                }

                // Add delete button
                if (!empty($this->post) && $this->post->post_status != 'trash') {
                    ?>
                    <div class='submit-wrapper'>
                        <button type='button' class='button' name='delete-post' data-post-id='<?php echo esc_html($this->postId); ?>'>
                            Delete <?php echo esc_html($this->postName); ?>
                        </button>
                    </div>
                    <?php
                }

                echo wp_kses_post(apply_filters('tsjippy-frontend-content-buttons', ob_get_clean(), $this));
                ?>
            </form>
        </div>

    <?php

        return ob_get_clean();
    }

    /**
     *
     * Add a new plugin to the TinyMCE window to select an user and insert a user shortcode
     *
     * @param    array     $plugins    Array of existing plugins
     * @return   array                 Array of new plugins
     *
     **/
    public function addTinymcePlugin($plugins)
    {
        wp_localize_script(
            'tsjippy_frontend_script',
            'userSelect',
            ['html' => TSJIPPY\userSelect("Select a person to show the link to", true)],
        );

        $url                    = TSJIPPY\pathToUrl(PLUGINPATH . "js/tiny_mce.js?ver=" . PLUGINVERSION);

        if ($url) {
            $plugins['select_user'] = $url;
        }

        return $plugins;
    }

    /**
     *
     * Add a new button to the TinyMCE window to select an user and insert a user shortcode
     *
     * @param    array     $buttons    Array of existing buttons
     * @return   array                 Array of new buttons
     *
     **/
    public function registerButtons($buttons)
    {
        array_push($buttons, 'select_user');
        return $buttons;
    }

    /**
     *
     * Checks whether the current user has edit rights for the current post
     *
     * @return   boolean  true|false
     *
     *
     **/
    public function hasEditRights()
    {
        //Only set this once
        if (!isset($this->editRight)) {
            //Check if allowed to edit this
            if (
                !allowedToEdit($this->post)                                        &&
                !$this->fullrights
            ) {
                $this->editRight    = false;
            } else {
                $this->editRight    = true;
            }

            $this->editRight    = apply_filters('tsjippy-frontend-content-edit-rights', $this->editRight, $this->postCategory);
        }
    }

    /**
     *
     * Fills the submit form with existing option values of the current post
     *
     **/
    public function fillPostData()
    {
        //Load existing post data
        if (is_numeric($this->postId)) {
            $this->post            = get_post($this->postId);

            if ($this->post->post_status == 'inherit') {
                $this->orgPost         = get_post($this->post->post_parent);
                while ($this->orgPost->post_status == 'inherit') {
                    $this->orgPost    = get_post($this->orgPost->post_parent);
                }

                $this->postParent    = $this->orgPost->post_parent;
                $this->postType     = $this->orgPost->post_type;
            } else {
                // Check if there are pending changes
                $args = array(
                    'post_parent' => $this->postId,
                    'post_type'   => 'change',
                    'post_status' => 'inherit',
                );

                $revisions = get_children($args);

                // Load the first revision of this post so that we have the latest updates
                if (!empty($revisions)) {
                    $this->post   = get_post($revisions[0]);
                }

                $this->postParent = $this->post->post_parent;
                $this->postType   = $this->post->post_type;
            }

            $this->postTitle      = $this->post->post_title;
            $this->postContent    = $this->post->post_content;
            $this->postImageId    = get_post_thumbnail_id($this->postId);

            $this->postCategory   = $this->post->post_category;
        }

        $this->postType           = apply_filters('tsjippy-frontend-content-posttype', $this->postType);

        $this->postName           = str_replace(["_lite", '-'], ["", ' '], $this->postType);

        if ($this->fullrights && is_numeric($this->postId) && $this->post->post_status == 'publish') {
            $this->action = "Update <span class='replace-post-type'>{$this->postName}</span>";
        } else {
            $this->action = "Submit <span class='replace-post-type'>{$this->postName}</span> for review";
        }
    }

    /**
     *
     * Prints pending changes to the screen
     *
     **/
    public function showChanges()
    {
        if (($this->post->post_status ?? '') != 'inherit') {
            return;
        }

        // Get changes in title and content
        if (!function_exists('wp_get_revision_ui_diff')) {
            include_once ABSPATH . 'wp-admin/includes/revision.php';
        }

        add_filter("_wp_post_revision_field_post_content", function ($text) {
            return preg_replace('/<!-- .* -->/i', '', $text);
        });

        $result        = wp_get_revision_ui_diff($this->post->post_parent, $this->post->post_parent, $this->post->ID);

        // Get changes in meta values
        $newMeta    = get_post_meta($this->postId);
        if (!$newMeta) {
            $newMeta    = [];
        }

        $oldMeta    = get_post_meta($this->orgPost->ID);
        if (!$oldMeta) {
            $oldMeta    = [];
        }

        //exclude certain keys
        $exclusion    = ['pending_notification_send', '_edit_lock', '_edit_last', '_themeisle_gutenberg_block_stylesheet', '_wp_page_template'];
        foreach ($exclusion as $exclude) {
            if (isset($oldMeta[$exclude])) {
                unset($oldMeta[$exclude]);
            }

            if (isset($newMeta[$exclude])) {
                unset($newMeta[$exclude]);
            }
        }

        // Unserialize the meta values
        foreach ($newMeta as &$meta) {
            $meta[0]    = maybe_unserialize($meta[0]);

            if (empty($meta[0])) {
                $meta[0]    = '';
            }

            $meta    = trim(maybe_serialize($meta[0]));
        }
        unset($meta);

        foreach ($oldMeta as &$meta) {
            $meta[0]    = maybe_unserialize($meta[0]);
            if (empty($meta[0])) {
                $meta[0]    = '';
            }

            $meta    = trim(maybe_serialize($meta[0]));
        }
        unset($meta);

        // Check which meta values have been changed
        $changed    = [];

        foreach ($newMeta as $key => $meta) {
            if (!isset($oldMeta[$key]) && !empty($meta)) {
                $changed[$key]    = ['old' => '', 'new' => $meta];
            }
        }

        // Check which values have been added
        foreach (array_intersect($newMeta, $oldMeta) as $key => $value) {
            if ($oldMeta[$key] != $value) {
                if (is_array($newValue)) {
                    foreach ($newValue as $k => $v) {
                        $newV    = maybe_unserialize($v);
                        $oldV    = maybe_unserialize($oldValue[$k]);

                        if ($newV != $oldV) {
                            $changed[$k]    = ['old' => $oldV, 'new' => $newV];
                        }
                    }
                } elseif ($newValue != $oldValue) {
                    $changed[$key]    = ['old' => $oldValue, 'new' => $newValue];
                }
            }
        }

        foreach ($changed as $key => $change) {
            $diff    = wp_text_diff($change['old'], $change['new']);
            if (empty($diff)) {
                continue;
            }

            // picture id to picture html
            if ($key == '_thumbnail_id') {
                if (is_numeric($change['old'])) {
                    $diff    = str_replace($change['old'], wp_get_attachment_image($change['old']), $diff);
                }

                if (is_numeric($change['new'])) {
                    $diff    = str_replace($change['new'], wp_get_attachment_image($change['new']), $diff);
                }
                $key    = 'Featured image';
            }

            $result[]    = array(
                'id'    => 'post_meta',
                'name'    => ucfirst(str_replace('_', ' ', $key)),
                'diff'    => $diff
            );
        }

    ?>
        <button type='button' class='button small show-diff'>Show what is changed</button>
        <fieldset class='post-diff-wrapper hidden'>
            <legend>
                <h4>Change list</h4>
            </legend>
            <?php
            foreach ($result as $r) {
                ?>
                <h4>
                    <?php echo esc_attr($r['name']);?>
                </h4>
                <?php echo wp_kses_post($r['diff']);
            }
            ?>
        </fieldset>
        <?php
    }

    /**
     *
     * Show a selector to select or change the post type
     *
     **/
    public function postTypeSelector()
    {
        //do not show for lite posts
        if ($this->lite) {
            return;
        }

        // Only show type selector if we do not query a specific one
        if (!empty($_GET['type'])) {
            return;
        }

        ?>
        <form action="" method="post" name="change-post-type">
            <input type="hidden" class="no-reset" name="user-id" value="<?php echo  esc_html($this->user->ID); ?>">
            <input type="hidden" class="no-reset" name="post-id" value="<?php echo  esc_html($this->postId); ?>">
            <h4>
                <?php 
                if ($this->postId == null) {
                    ?>
                    Select the content type you want to create:
                    <?php
                } else {
                    ?>
                    You are editing a <?php echo esc_html($this->postType);?>, use selector below if you want to change the post type
                    <?php
                }
                ?>
            </h4>
            
            <select id='post-type-selector' name='post-type-selector' required>
                <?php
                foreach ($this->postTypes as $postType => $taxName) {
                    $typeName    = ucfirst(str_replace("-", " ", $postType));
                    if ($postType == 'attachment') {
                        $typeName = 'Picture/Video/Audio';
                    }
                    ?>
                    <option value='<?php echo esc_attr($postType);?>' <?php if ($this->postType == $postType) { echo  'selected="selected"';};?>>
                        <?php echo esc_html($typeName);?>
                    </option>
                    <?php
                }

            ?>
            </select>
        </form>
        <?php
    }

    /**
     *
     * Add a modal form to add a new category for the selected post type
     *
     **/
    public function addModals()
    {
        foreach ($this->postTypes as $postType => $taxonomy) {
            $categories = get_categories(array(
                'orderby'     => 'name',
                'order'       => 'ASC',
                'taxonomy'    => $taxonomy,
                'hide_empty' => false,
            ));

        ?>
            <div id="add-<?php echo esc_attr($postType); ?>-type" class="modal hidden">
                <!-- Modal content -->
                <div class="modal-content">
                    // phpcs:ignore
                    <?php echo TSJIPPY\addCloseButtton(); ?>
                    <form action="" method="post" id="add-<?php echo esc_attr($postType); ?>-type-form" class="add-category">
                        <p>Please fill in the form to add a new <?php echo esc_attr($postType); ?> category</p>
                        <input type="hidden" class="no-reset" name="post-type" value="<?php echo esc_attr($postType); ?>">
                        <input type="hidden" class="no-reset" name="user-id" value="<?php echo esc_attr($this->user->ID); ?>">

                        <label>
                            <h4>Category name<span class="required">*</span></h4>
                            <input type="text" name="cat-name" class='wide' required>
                        </label>

                        <h4>Parent category</h4>
                        <select class="" name='cat-parent'>
                            <option value=''>---</option>
                            <?php
                            foreach ($categories as $category) {
                                //Only ouptut categories without a parent
                                if ($category->parent == 0) {
                                    ?>
                                    <option value='<?php echo esc_attr($category->cat_ID);?>'>
                                        <?php esc_html($category->name);?>
                                    </opton>
                                    <?php
                                }
                            }
                            ?>
                        </select>

                        <?php TSJIPPY\addSaveButton("add_{$postType}_type", "Add $postType category"); ?>
                    </form>
                </div>
            </div>
        <?php
        }
    }

    /**
     *
     * Adds fields specific for the post post_type
     *
     **/
    public function postSpecificFields()
    {
        ?>
        <div 
        id="post-attributes" 
        class="property post
        <?php if ($this->postType != 'post') {
            echo ' hidden';
        } ?>">
            <div id="expiry-date-div" class="frontend-form expand-wrapper">
                <h4>
                    Expiry date
                    <button class="button small expand" type='button'>&#9660;</button>
                </h4>
                <label class='hidden expandable'>
                    Expiry date<br>
                    <input type='date' class='' name='expirydate' min="<?php echo esc_attr(gmdate("Y-m-d")); ?>" value="<?php echo esc_html(get_post_meta($this->postId, 'tsjippy_expirydate', true)); ?>" style="display: unset; width:unset;">
                </label>
            </div>
        </div>
    <?php
    }

    /**
     *
     * Adds fields specific for the page post_type
     *
     **/
    public function pageSpecificFields()
    {
    ?>
        <div 
        id="page-attributes" 
        class="property page
        <?php if ($this->postType != 'page') {
            echo ' hidden';
        } ?>">
            <div id="parentpage" class="frontend-form expand-wrapper">
                <h4>
                    Parent page
                    <button class="button small expand" type='button'>&#9660;</button>
                </h4>
                <div class='expandable hidden'>
                    <?php
                    // phpcs:ignore
                    echo TSJIPPY\pageSelect('parent-page', $this->postParent, '', ['page'], false);
                    ?>
                </div>
            </div>

            <?php
            do_action('tsjippy-frontend-content-page-specific-fields', $this->postId);
            ?>
            <div id="static-content" class="frontend-form expand-wrapper">
                <h4>
                    Update warnings
                    <button class="button small expand" type='button'>&#9660;</button>
                </h4>
                <label class='expandable hidden'>
                    <input 
                    type='checkbox' 
                    name='static-content' 
                    value='static-content' 
                    <?php if (get_post_meta($this->postId, 'tsjippy_static_content', true)) {
                        echo 'checked';
                    } ?>>
                    Do not send update warnings for this page
                </label>
            </div>
        </div>
        <?php
    }

    /**
     *
     * Display the categories for a specific post_type
     *
     **/
    public function showCategories()
    {
        foreach ($this->postTypes as $postType => $taxName) {
            $categories    = get_categories(array(
                'orderby'         => 'name',
                'order'           => 'ASC',
                'taxonomy'        => $taxName,
                'hide_empty'    => false,
            ));

            $postCats    = [];
            // check for the post categories if the current post type matches the type of the current post
            if ($this->postType == $postType) {
                $postCats    = wp_get_post_terms($this->postId, $taxName, ['fields' => 'ids']);
            }

        ?>
            <div 
            class="property 
            <?php echo esc_attr($postType);
            if ($this->postType != $postType) {
                echo ' hidden';
            } ?>">
                <div class="frontend-form">
                    <h4>
                        <?php echo esc_html(ucfirst($postType)); ?> type
                    </h4>
                    <div class='categories'>
                        <?php
                        $parentCategoryHtml = '';
                        $childCategoryHtml  = '';
                        $hidden             = 'hidden';

                        foreach ($categories as $category) {
                            $name           = $category->name;
                            $catId          = $category->cat_ID;
                            $catDescription = $category->description;
                            $parent         = $category->parent;
                            $checked        = '';
                            $class          = 'info-box';
                            $taxonomy       = $category->taxonomy;

                            //This category is a not a child
                            if ($parent == 0) {
                                $html = 'parentCategoryHtml';
                                //has a parent
                            } else {
                                $html = 'childCategoryHtml';
                            }

                            //if this cat belongs to this post
                            if (in_array($catId, $postCats)) {
                                $checked = 'checked';

                                //If this type has child types, show the label
                                if (count(get_term_children($category->cat_ID, $taxonomy)) > 0) {
                                    $hidden = '';
                                }
                            }

                            //if this is a child, hide it and attach the parent id as attribute
                            if ($parent != 0) {
                                //Hide subcategory if parent is not in the cat array
                                if (!has_term($parent, $taxonomy, $this->postId)) {
                                    $class .= " hidden";
                                }

                                //Store cat parent
                                $class .= "' data-parent='$parent";
                            }

                            //$$html --> use the value of $html as variable name
                            $$html .= "<div class='$class'>";
                            $checkboxClass = "{$postType}type";
                            if (count(get_term_children($category->cat_ID, $taxonomy)) > 0) {
                                $checkboxClass .= " parent_cat";
                            }

                            //Name of the category
                            $$html .= "<label class='option-label category-select'>";
                            $$html .= "<input type='checkbox' class='$checkboxClass' name='{$taxonomy}-ids[]' value='$catId' $checked>";
                            $$html .= $name;
                            $$html .= "</label>";

                            //Add info-box if needed
                            if (!empty($catDescription)) {
                                $$html .= "<span class='info-text'>$catDescription</span>";
                            }

                            $$html .= '</div>';
                        }

                        ?>
                        <div id='<?php echo esc_attr($postType); ?>_parenttypes'>
                            <?php
                            echo wp_kses_post($parentCategoryHtml);
                            ?>
                            <button type='button' name='add-<?php echo esc_attr($postType); ?>-type-button' class='button add-cat' data-type='<?php echo esc_attr($postType); ?>'>
                                Add category
                            </button>
                        </div>

                        <label id='subcategorylabel' class='frontend-profile-label <?php echo esc_html($hidden); ?>'>
                            Sub-category
                        </label>

                        <div id='<?php echo esc_attr($postType); ?>_childtypes' class='childtypes'>
                            <?php
                            echo wp_kses_post($childCategoryHtml);
                            ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php
        }
    }

    /**
     *
     * Adds extra options
     *
     **/
    public function metaOptions()
    {
        do_action('tsjippy-frontend-content-post-before-default-options-content', $this);

        // Show change author dropdown
        $authorId = $this->user->ID;
        if (isset($this->post->post_author) && is_numeric($this->post->post_author)) {
            $authorId = $this->post->post_author;
        }
        
        ?>
        <div class='expand-wrapper'>
            <h4>
                Author <button class="button small expand" type='button'>&#9660;</button>
            </h4>

            <div class='hidden expandable'>
                <?php
                TSJIPPY\userSelect(onlyAdults: true, id: 'post-author', userId: $authorId, echo: true);
                ?>
            </div>
        </div>
        <?php

        // Only show publish date if not yet published
        if (empty($this->post->post_status) || !in_array($this->post->post_status, ['publish', 'inherit'])) {
            if (empty($this->post)) {
                $publishDate    = gmdate("Y-m-d");
            } else {
                $publishDate    = max(gmdate("Y-m-d", strtotime($this->post->post_date)), gmdate("Y-m-d"));
            }

            ?>
            <div class='expand-wrapper'>
                <h4>
                    Publishing date
                    <button class="button small expand" type='button'>&#9660;</button>
                </h4>

                <div class='hidden expandable'>
                    Publish Date<br>
                    <input type="date" min="<?php echo esc_attr(gmdate("Y-m-d")); ?>" name="publish-date" value="<?php echo esc_attr($publishDate); ?>">
                </div>
            </div>
            <?php
        }

        ?>
        <div id="nonews" class="frontend-form expand-wrapper">
            <h4>
                News Gallery
                <button class="button small expand" type='button'>&#9660;</button>
            </h4>
            <label class='hidden expandable'>
                <input
                    type='checkbox'
                    name='skipgallery'
                    value='skipgallery'
                    <?php if (get_post_meta($this->postId, 'tsjippy_skipgallery', true)) {
                        echo 'checked';
                    } ?>>
                Do not add this <?php echo esc_attr($this->post->post_type ?? ''); ?> to the news gallery
            </label>
        </div>
        <?php

        $this->postSpecificFields();

        $this->pageSpecificFields();

        do_action('tsjippy-frontend-content-post-after-content', $this);

        ?>
        <div class='expand-wrapper'>
            <h4>
                View Permissions
                <button class="button small expand" type='button'>&#9660;</button>
            </h4>

            <div class='hidden expandable'>
                <label>
                    <input type='radio' name='permission-filter-type' id='permission-filter-type' value='block'>
                    Block this page
                </label>
                <label>
                    <input type='radio' name='permission-filter-type' id='permission-filter-type' value='allow'>
                    Allow this page
                </label>
                <br>
                for accounts with one of the following roles:<br>
                <?php
                global $wp_roles;

                $userRoles    = $wp_roles->role_names;

                $viewRoles    = [];

                if (is_numeric($this->postId)) {
                    $viewRoles    = get_post_meta($this->postId, 'tsjippy_post_view_roles');
                }

                ?>
                <select name='post-view-roles[]' multiple>
                    <option value=''>---</option>

                    <?php
                    foreach ($userRoles as $key => $roleName) {
                        ?>
                        <option 
                        value='<?php echo esc_attr($key);?>'
                        <?php
                        if (in_array($key, $viewRoles)) {
                            echo('selected');
                        }
                        ?>>
                            <?php echo esc_html($roleName);?>
                        </option>
                        <?php
                    }
                    ?>
                </select>
            </div>
        </div>
        <?php
    }

    /**
     *
     * saves base64 images as images and adds them to the library
     *
     * @param    array     $matches    Array of matches from a regex
     *
     * @return   string             Image url
     *
     **/
    public function uploadImages($matches)
    {
        $ext             = $matches[1];
        $filename         = "frontend_picture";
        $basedir         = wp_upload_dir()['basedir'];
        $newFilePath     = "$basedir/$filename.$ext";
        $i                 = 0;
        $uploadId        = 0;

        //Find an available filename
        while (file_exists($newFilePath)) {
            $i++;
            $newFilePath = "$basedir/$filename" . "_$i.$ext";
        }

        //Decode the base64
        $fileContents = base64_decode(substr_replace($matches[2], "", -1));

        //Only continue if the decoding was succesfull
        if ($fileContents !== false) {
            //Save the image in the uploads folder
            file_put_contents($newFilePath, $fileContents);

            $uploadId        = TSJIPPY\addToLibrary($newFilePath);
        } else {
            TSJIPPY\printArray('Not a valid image');
            return '';
        }

        //Return the image url
        $url = wp_get_attachment_image_url($uploadId, '');
        return "src='$url'";
    }

    /**
     * Store categories of custom post type
     *
     */
    public function storeCustomCategories($post)
    {
        $postVars   = TSJIPPY\sanitize($_POST);
        foreach ($this->postTypes as $postType => $taxonomy) {
            $cats = [];
            if (is_array($postVars[$taxonomy . '-ids'] ?? '')) {
                foreach ($postVars[$taxonomy . '-ids'] as $catId) {
                    if (is_numeric($catId)) {
                        $cats[] = $catId;
                    }
                }

                //Make sure we only send integers
                $cats = array_map('intval', $cats);

                // Store
                wp_set_post_terms($post->ID, $cats, $taxonomy);
            }
        }
    }

    /**
     * Several checks and adjustments to the post contents
     */
    public function preparePostContent($postContent)
    {
        //Sanitize the post content
        $postContent     = wp_kses_post(wp_kses_stripslashes($postContent));

        // Remove some tags
        $postContent     = preg_replace("/(&lt;|<)(del|ins) .*?(>|&gt;)/im", "", $postContent);

        // Checks for opening and closing formating tags
        $tags            = ['b', 'strong', 'i', 'em', 'mark', 'small', 'sub', 'sup', 'span'];
        $pattern        = '';
        foreach ($tags as $tag) {
            if (!empty($pattern)) {
                $pattern    .= "|";
            }
            $pattern    .= "<\/$tag>\s*<$tag>";    // Closing tag, followed by zero or more spaces followed by an opening tag
        }

        $postContent     = preg_replace_callback("/$pattern/i", function () {
            return ' ';
        }, $postContent);

        //Find any base64 encoded images in the post content and replace the url
        $postContent     = preg_replace_callback('/src="image\/(\w+);base64,([^"]*)"/is', array($this, 'uploadImages'), $postContent);

        //Find display names in content and replaces them with a link
        $userPageLinks    = new TSJIPPY\UserPageLinks($postContent, true);

        $postContent    = apply_filters('tsjippy-frontend-content-post-content', $userPageLinks->string);

        // Make sure its UTF-8
        $postContent    = mb_convert_encoding($postContent, 'UTF-8', 'UTF-8');

        $postContent    = html_entity_decode($postContent, ENT_QUOTES, 'UTF-8');

        return $postContent;
    }

    /**
     * Update an existing post
     */
    public function updateExistingPost()
    {
        //Retrieve the old post data
        $post = get_post($this->postId);

        // Check if this is a post revison
        if ($this->fullrights && $post->post_type == 'change') {
            // delete revision
            $delete = wp_delete_post($this->postId);

            if ($delete) {
                do_action('wp_delete_post_revision', $this->postId, $post);
            }

            // Use parent page as post id
            $this->postId    = $post->post_parent;

            // Load parent post data
            $post = get_post($this->postId);
        }

        $this->update = true;

        $newPostData = ['ID' => $this->postId];

        //Check for updates
        if ($this->postTitle != $post->post_title) {
            //title
            $newPostData['post_title']     = $this->postTitle;

            // name
            $postName    = urldecode($this->postTitle);

            //check if name is unique as it used as slug
            $args    = array(
                'post_type'   => get_post_types(),
                'post_status' => 'any',
                'name'        => $postName,
                'numberposts' => -1
            );

            $i = 1;
            while (true) {
                $posts    = get_posts($args);

                // Remove the current post
                foreach($posts as $index => $post){
                    if($post->ID == $this->postId){
                        unset($posts[$index]);
                    }
                }

                if(empty($posts)){
                    break;
                }

                $postName        = urldecode($this->postTitle . '_' . $i);
                $args['name']    = $postName;
                $i++;
            }

            $newPostData['post_name']     = $postName;

            //attached file
            if ($this->postType == 'attachment' && explode('/', $post->post_mime_type)[0] == 'video') {
                $newPostData['_wp_attached_file']     = $this->postTitle;
            }
        }

        // update publish date if needed
        if (
            strtotime($post->post_date) > time()    &&                                // Current post date is in the future
            ($this->publishDate ?? '') != gmdate('Y-m-d', strtotime($post->post_date))    // it is not the same as before
        ) {
            $publishDate                    = gmdate("Y-m-d 08:00:00", strtotime(TSJIPPY\sanitize($this->publishDate  ?? '')));
            $newPostData['post_date']       = $publishDate;
            $newPostData['post_date_gmt']   = $publishDate;
        }

        if ($this->postContent != $post->post_content) {
            $newPostData['post_content']     = $this->postContent;
        }

        if ($this->status != $post->post_status) {
            $newPostData['post_status']     = $this->status;
        }

        if (($this->author ?? '') != $post->post_author) {
            $newPostData['post_author']        = TSJIPPY\sanitize($this->author ?? '');
        }

        //parent
        if (isset($_POST["parent-$post->post_type"])) {
            $newPostData['post_parent']        = TSJIPPY\sanitize($_POST["parent-$post->post_type"] ?? '');
        }

        if ($this->postCategories != $post->post_category) {
            $newPostData['post_category']     = $this->postCategories;
        }

        //we cannot change the post type here
        if ($post->post_type != $this->postType && !in_array($post->post_type, ['revision', 'change'])) {
            return new WP_Error('frontend_contend', 'You can not change the post type like that!');
        }

        //Create a revision post if we are updating an already published post
        if ($this->status == 'pending' && $post->post_status == 'publish') {
            $this->actionText = 'updated';

            foreach ($newPostData as $key => $data) {
                $post->$key    = $data;
            }

            // Mark new post as inherit
            $post->post_status    = 'inherit';
            $post->post_name    = $post->ID . '-revision-v1';
            $post->post_parent    = $post->ID;
            $post->post_type    = 'change';
            unset($post->ID);

            // Insert the post into the database.
            $postId         = wp_insert_post($post, true, false);

            $post->ID        = $postId;

            $this->postId    = $postId;
        } else {
            $result = wp_update_post($newPostData, true, false);

            // check for errors
            if (is_wp_error($result)) {
                // most likely some invalid data in post, try to fix it.
                if ($result->get_error_message() == "Could not update post in the database. ") {
                    $illigalChars    = [];
                    foreach (str_split($newPostData['post_content']) as $index => $chr) {
                        json_encode($chr);
                        if (json_last_error() == 5) {
                            $illigalChars[$index] = $chr;
                        } elseif (json_last_error() == 0 && !empty($illigalChars)) {
                            $newPostData['post_content']    = str_replace(implode('', $illigalChars), mb_convert_encoding(implode('', $illigalChars), "UTF-8", "auto"), $newPostData['post_content']);
                            $illigalChars    = [];
                        }
                    }

                    // try to update again
                    $result = wp_update_post($newPostData, true, false);

                    if (is_wp_error($result)) {
                        return $result;
                    }
                } else {
                    return $result;
                }
            }

            if (($post->post_status == 'draft' || $post->post_status == 'pending') && $this->status == 'publish') {
                $this->actionText = 'published';

                // If we publish a post which was pending before send an notification to the author
                if ($post->post_status == 'pending') {
                    $author        = get_userdata($post->post_author);
                    $url        = get_permalink($post->ID);
                    $email        = new ApprovedPostMail($author->display_name, $post->post_type, $url);
                    $email->filterMail();

                    //Send e-mail
                    wp_mail($author->user_email, $email->subject, $email->message);
                }
            } else {
                $this->actionText = 'updated';
            }
        }

        return get_post($post->ID);
    }

    /**
     * Create a new post
     */
    public function createNewPost()
    {
        $this->update        = false;
        $this->actionText    = 'created';

        //New post
        $post = array(
            'post_type'     => $this->postType,
            'post_title'    => $this->postTitle,
            'post_content'  => $this->postContent,
            'post_status'   => $this->status,
            'post-author'   => TSJIPPY\sanitize($this->author ?? '')
        );

        if ($this->postType == 'attachment') {
            $this->postId     = TSJIPPY\addToLibrary(TSJIPPY\urlToPath(TSJIPPY\sanitize($_POST['attachment'][0] ?? '', 'url')), $this->postTitle, $this->postContent);
            $post['ID']    = $this->postId;
        } else {
            if (isset($_POST["parent-$this->postType"])) {
                $newPostData['post_parent']        = TSJIPPY\sanitize($_POST["parent-$this->postType"]);
            }

            if (!empty(count($this->postCategories))) {
                $post['post_category'] = $this->postCategories;
            }

            //Schedule the post if in the future
            if (($this->publishDate ?? '') != gmdate('Y-m-d')) {
                $publishDate            = gmdate("Y-m-d 08:00:00", strtotime(TSJIPPY\sanitize($this->publishDate ?? '')));

                $post['post_date']         = $publishDate;
                $post['post_date_gmt']     = $publishDate;
            }

            // Insert the post into the database.
            $this->postId     = wp_insert_post($post, true, false);
            $post['ID']        = $this->postId;
        }

        if (is_wp_error($this->postId)) {
            // check for errors
            if (is_wp_error($this->postId)) {
                // most likely some invalid data in post, try to fix it.
                if ($this->postId->get_error_message() == "Could not update post in the database. ") {
                    $illigalChars    = [];
                    foreach (str_split($post['post_content']) as $index => $chr) {
                        json_encode($chr);
                        if (json_last_error() == 5) {
                            $illigalChars[$index] = $chr;
                        } elseif (json_last_error() == 0 && !empty($illigalChars)) {
                            $post['post-content']    = str_replace(implode('', $illigalChars), mb_convert_encoding(implode('', $illigalChars), "UTF-8", "auto"), $post['post-content']);
                            $illigalChars    = [];
                        }
                    }

                    // try to update again
                    $this->postId     = wp_insert_post($post, true, false);
                    $post['ID']        = $this->postId;

                    if (is_wp_error($this->postId)) {
                        return $this->postId;
                    }
                } else {
                    return $this->postId;
                }
            }
        } elseif ($this->postId === 0) {
            return new WP_Error('Inserting post error', "Could not create the $this->postType!");
        }

        return (object) $post;
    }

    /**
     *
     * Saves or publishes a new post or updates an existing one
     *
     * @param    string     $status    Desired post status
     * @return   array|WP_Error        Result message
     *
     **/
    public function submitPost()
    {
        if (
            $this->status    == 'publish'            &&
            $this->fullrights                         &&
            ($this->publishDate ?? '') > gmdate('Y-m-d')
        ) {
            $this->status    = 'future';
        }

        //First letter should be capital in the title
        $this->postTitle     = ucfirst(trim(TSJIPPY\sanitize($_POST['post-title'] ?? '')));

        $this->oldPost        = null;
        if (!empty($this->post)) {
            $this->oldPost    = $this->post;

            $this->postType   = $this->oldPost->post_type;

            // find the parent with a correct posttype
            while (in_array($this->oldPost->post_type, ['change', 'revision'])) {
                $this->oldPost = get_post($this->oldPost->post_parent);
            }
        } elseif (!empty($this->postTitle)) {
            // check double posting
            $posts = get_posts(
                array(
                    'post_type'              => $this->postType,
                    'title'                  => $this->postTitle,
                    'post_status'            => 'all',
                    'numberposts'            => -1,
                    'update_post_term_cache' => false,
                    'update_post_meta_cache' => false,
                    'orderby'                => 'post_date ID',
                    'order'                  => 'ASC',
                )
            );

            foreach ($posts as $p) {
                if (current_time('Y-m-d') == gmdate('Y-m-d', strtotime($p->post_date))) {
                    $url    = get_permalink($p);
                    return new \WP_Error('frontend', "A post with title $this->postTitle is already published today!\nSee it <a href='$url'>here</a>");
                }
            }
        }

        $this->postContent     = $this->preparePostContent(TSJIPPY\sanitize($_POST['post-content'] ?? ''));

        $this->postCategories = [];
        if (is_array($_POST['category-id'] ?? false)) {
            foreach ($_POST['category-id'] as $categoryId) {
                if (!empty($categoryId)) {
                    $this->postCategories[] = $categoryId;
                }
            }
        }

        // Create the possibility of pre-publish checks
        $error    = apply_filters('tsjippy-frontend-content-validation', '', $this);
        if (is_wp_error($error)) {
            return $error;
        }

        //Check if editing an existing post
        if (is_numeric($this->postId)) {
            $post    = $this->updateExistingPost();
        } else {
            $post    = $this->createNewPost();
        }

        if (is_wp_error($post)) {
            return $post;
        }

        //Set the featured image
        if (($_POST['post-image-id'] ?? 0) != 0) {
            set_post_thumbnail($this->postId, (int) $_POST['post-image-id']);
        } elseif ($this->update) {
            delete_post_thumbnail($this->postId);
        }

        //Static content
        if (isset($_POST['static-content'])) {
            update_metadata('post', $this->postId, 'tsjippy_static_content', true);
        } else {
            delete_post_meta($this->postId, 'tsjippy_static_content');
        }

        // Role view rights
        delete_post_meta($this->postId, 'tsjippy_post_view_roles');

        if (in_array(TSJIPPY\sanitize($_POST['permission-filter-type'] ?? ''), ['blobk', 'allow'])) {
            update_metadata('post', $this->postId, 'tsjippy_permission_filter_type', $_POST['permission-filter-type']);
        }

        foreach (TSJIPPY\sanitize($_POST['post-view-roles'] ?? []) as $role) {
            add_metadata('post', $this->postId, 'tsjippy_post_view_roles', $role);
        }

        //Expiry date
        if (isset($_POST['expirydate'])) {
            if (empty($_POST['expirydate'])) {
                delete_post_meta($this->postId, 'tsjippy_expirydate');
            } else {
                //Store expiry date
                update_metadata('post', $this->postId, 'tsjippy_expirydate', TSJIPPY\sanitize($_POST['expirydate']));
            }
        }

        // News Gallery
        if (!isset($_POST['skipgallery'])) {
            delete_post_meta($this->postId, 'tsjippy_skipgallery');
        } else {
            update_metadata('post', $this->postId, 'tsjippy_skipgallery', true);
        }

        if ($post->post_status == 'pending' && $this->status == 'pending') {
            sendPendingPostWarning($post, $this->update);
        }

        //store attachment categories
        $this->storeCustomCategories($post);

        do_action('tsjippy-frontend-content-after-post-save', (object)$post, $this);

        wp_after_insert_post($post, $this->update, $this->oldPost);

        //Return result
        if ($this->status == 'publish') {
            $message    = "Succesfully $this->actionText the $this->postType";
        } elseif ($this->status == 'draft') {
            $message    = "Succesfully $this->actionText the draft for this $this->postType";
        } elseif (($this->publishDate ?? '') > gmdate('Y-m-d') && $this->status == 'future') {
            $message    = "Succesfully $this->actionText the $this->postType, it will be published on " . gmdate('d F Y', strtotime(TSJIPPY\sanitize($this->publishDate ?? ''))) . ' 8 AM';
        } else {
            $message    = "Succesfully $this->actionText the $this->postType, it will be published after it has been reviewed";
        }

        return [
            'message'   => $message,
            'id'        => $this->postId,
            'post'      => $post
        ];
    }

    /**
     *
     * Archives an existing post
     * @return   string|WP_Error             Result message
     *
     **/
    public function archivePost()
    {
        $data = array(
            'ID'             => $this->postId,
            'post_status'    => 'archived'
        );

        wp_update_post($data);

        $postType    = get_post_type($this->post);

        if ($postType) {
            return "Succesfully archived $postType '{$this->post->post_title}'<br>You can leave this page now";
        } else {
            return new WP_Error('Post archival error', 'Something went wrong');
        }
    }

    /**
     *
     * Removes an existing post
     * @return   string|WP_Error             Result message
     *
     **/
    public function removePost()
    {
        $post     = wp_trash_post($this->postId);

        $postType = get_post_type($post);

        if ($postType) {
            return "Succesfully deleted $postType '{$post->post_title}'<br>You can leave this page now";
        } else {
            return new WP_Error('Post removal error', 'Something went wrong');
        }
    }

    /**
     *
     * Change the type of an existing post
     *
     * @return   string|WP_Error             Result message
     *
     **/
    public function changePostType()
    {
        $postType = TSJIPPY\sanitize($_POST['post-type-selector']);

        $result   = set_post_type($this->postId, $postType);

        // remove the parent as parents need to be of the same type
        $this->removeParents($this->postId);

        if ($result) {
            return "Succesfully changed the type to $postType";
        } else {
            return new WP_Error('Update failed', "Could not change the type");
        }
    }

    /**
     * Removes the parent from a post
     *
     * @param    int        $postId    The WP_Post id
     */
    public function removeParents($postId)
    {
        if (has_post_parent($postId)) {
            wp_update_post(
                array(
                    'ID'            => $postId,
                    'post_parent'   => 0
                )
            );
        }

        // Remove as parent from any children
        foreach (get_children($postId) as $child) {
            $this->removeParents($child->ID);
        }
    }

    /**
     * Get the meta value of the revision or parent post if empty
     */
    public function getPostMeta($key)
    {
        $value  = get_post_meta($this->postId, "tsjippy_$key", true);

        // use parent value if the revision value is non existing
        if (empty($value) && !empty($this->orgPost)) {
            $value    =  get_post_meta($this->orgPost->ID, "tsjippy_$key", true);
        }

        return $value;
    }
}
