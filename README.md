This plugin makes it possible to add and edit pages, posts and custom post types.<br>

== Description ==
Just place this shortcode on any page: <code>[front_end_post]</code>.<br>
An overview of the posts created by the current user can be displayed using the: <code>[your_posts]</code> shortcode.<br>
If anyone without publish rights tries to add or edit a page, it will be stored as pending.<br>
An overview of pending content can be shown using the <code>[pending_pages]</code> shortcode.<br>
You can use the <code>[pending_post_icon]</code> shortcode as an indicator, displaying the amount of pending posts in menu items.<br>
This plugin also adds a custom post status: archived. Meaning a post is not visible but still kept for reference
	

== Hooks ==
# FILTERS
- apply_filters('sim_frontend_content_edit_rights', false, $postCategory)	
- apply_filters('post-edit-button', $buttonHtml, $post, $content);
- apply_filters( "content_template", $baseTemplate, 'content' );
- apply_filters('sim-frontend-content-js', array('sim_fileupload_script', 'sim_forms_script'));
- apply_filters('sim_attachment_preview', $image, $this->postId);
- apply_filters('sim-frontend-buttons', ob_get_clean(), $this);
- apply_filters('sim_frontend_content_edit_rights', $this->editRight, $this->postCategory);
- apply_filters('sim_post_content', $postContent);
- apply_filters('sim_frontend_content_validation', '', $this);
- apply_filters('sim_media_gallery_download_url', $url, $id);
- apply_filters('sim_media_gallery_download_filename', '', $type, $id);