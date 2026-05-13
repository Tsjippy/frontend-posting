<?php
namespace TSJIPPY\FRONTENDPOSTING;
use TSJIPPY;
use stdClass;
use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class FrontEndContent{
	public $postId;
	public $user;
	public $post;
	public $postType;
	public $name;
	public $postTitle;
	public $postCategory;
	public $postContent;
	public $postParent;
	public $postImageId;
	public $postName;
	public $lite;
	public $fullrights;
	public $editRight;
	public $update;
	public $action;
	public $status;
	public $postTypes;			// possible categories of all post types
	public $postCategories;		// category for the current post
	public $actionText;
	public $oldPost;
	public $orgPost;

	public function __construct(){
		$this->postId			= isset($_GET['post-id']) ? $_GET['post-id'] : '' ;
		$this->user 			= wp_get_current_user();
		$this->post 			= null;
		if(is_numeric($this->postId)){
			$this->post	= get_post($this->postId);
		}
		$this->postType 		= "post";
		$this->name 			= "post";
		$this->postTitle 		= '';
		$this->postCategory 	= [];
		$this->postContent		= '';
		$this->postParent 		= null;
		$this->postImageId		= 0;
		$this->lite 			= false;

		if($this->user->has_cap( 'edit_others_posts' )){
			$this->fullrights		= true;
		}else{
			$this->fullrights		= false;
		}

		if(get_class($this) == __NAMESPACE__.'\FrontEndContent'){
			//Add tinymce plugin
			add_filter('mce_external_plugins', array($this, 'addTinymcePlugin'), 999);

			//add tinymce button
			add_filter('mce_buttons', array($this,'registerButtons'));
		}

		$postTypes			= get_post_types(['public' => true]);
		foreach($postTypes as $postType => &$taxonomy){
			if($postType == 'post' || $postType == 'page'){
				$taxonomy	= 'category';
			}elseif($postType == 'attachment'){
				$taxonomy	= 'attachment_cat';
			}else{
				$taxonomy	= $postType.'s';
			}
		}
		
		$this->postTypes	= apply_filters('tsjippy_frontend_post_types_and_tax', $postTypes, $this);
	}

	/**
	 *
	 * Renders the form to edit existing content or create new content
	 *
	 * @return   string     The form html
	 *
	**/
	public function frontendPost($hide=false){
		if(!function_exists('_wp_translate_postdata')){
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
		if(!$this->editRight && @is_numeric($_GET['post-id'])){
			return '<div class="error">You do not have permission to edit this page.</div>';
		}

		if(isset($_GET['post-id']) && is_numeric($_GET['post-id'])){
			//Show warning if someone else is editing
			$currentEditingUser = wp_check_post_lock($_GET['post-id']);
			if(is_numeric($currentEditingUser)){
				header("Refresh: 30;");
				return "<div class='error' id='	'>".get_userdata($currentEditingUser)->display_name." is currently editing this {$this->postType}, please wait.<br>We will refresh this page every 30 seconds to see if you can go ahead.</div>";
			}

			//Current time minus last modified time
			$secondsSinceUpdated = time()-get_post_modified_time('U', true, $this->post);

			//Show warning when post has been updated recently
			if($secondsSinceUpdated < 3600 && $secondsSinceUpdated > -1){
				$minutes = intval($secondsSinceUpdated/60);
				echo "<div class='warning'>This {$this->postType} has been updated <span id='minutes'>$minutes</span> minutes ago.</div>";
			}

			//Show warning when post is in trash
			if($this->post->post_status == 'trash'){
				echo "<div class='warning'>This {$this->postType} has been deleted.<br>You can republish if that should not be the case.</div>";
			}
		}

		?>
		<div id="frontend-upload-form" <?php if($hide){ echo 'class="hidden"';}?> style='margin-top: 10px;'>
			<?php
			if(!$this->lite){
				$hidden = 'hidden';
			}

			if(has_blocks($this->postContent)){
				$url	= get_edit_post_link($this->postId);
				echo "<div class='warning'>";
					echo "This {$this->postType} contains some Gutenberg blocks.<br>";
					echo "Click <a href='$url'>here</a> if you want to switch to the Gutenberg editor<br>";
				echo "</div>";
			}

			$this->update	= false;
			if(is_numeric($this->postId) && $this->post->post_status == 'publish'){
				$this->update	= true;
			}
			echo "<button class='button tsjippy $hidden show' id='show-all-fields'>Show all fields</button>";

			$this->postTypeSelector();

			$this->addModals();
			do_action('tsjippy_frontend_post_modal');

			$this->showChanges();

			//Write the form to create all posts except events
			?>
			<form id="postform">
				<input type="hidden" class="no-reset" name="post-status" 	value="pending">
				<input type="hidden" class="no-reset" name="post-type" 		value="<?php echo $this->postType; ?>">
				<input type="hidden" class="no-reset" name="post-image-id" 	value="<?php echo $this->postImageId;?>">
				<input type="hidden" class="no-reset" name="update" 			value="<?php echo $this->update;?>">
				<input type='hidden' class='no-reset' name='post-id' 		value='<?php echo $this->postId;?>'>

				<h4>Title</h4>
				<input type="text" name="post-title" class='block' value="<?php echo $this->postTitle;?>" required>

				<?php
				do_action('tsjippy_frontend_post_before_content', $this);

				$this->showCategories();

				?>
				<div class='property attachment hidden'>
					<?php
					//Existing media
					if(is_numeric($this->postId)){
						$image	= wp_get_attachment_image($this->postId);

						echo "<h4>Attachment preview</h4>";
						echo apply_filters('tsjippy_attachment_preview', $image, $this->postId);
					}else{
						?>
						<h4>Upload your file</h4>
						<?php
						$uploader = new TSJIPPY\FILEUPLOAD\FileUpload($this->user->ID);
						echo $uploader->getUploadHtml('attachment', 'private', false, '', true);
					}
					?>
				</div>

		 		<div id="featured-image-div" <?php if($this->postImageId == 0){echo ' class="hidden"';}?>>
					<h4 name="post-image-label">Featured image:</h4>

					<span id='featured-image-wrapper' style='max-height:150px;'>
						<?php
						if($this->postImageId != 0){
							echo get_the_post_thumbnail(
								esc_html($this->postId),
								'thumbnail',
								array(
									'title' => 'Featured Image',
									'class' => 'postimage'
								)
							);
							$text 	= 'Change';
						}else{
							$text = 'Add';
						}
						?>
					</span>
					<button type='button' class='remove-featured-image button'>X</button>
				</div>

				<?php
				//Content wrapper
				if($this->lite){
					echo "<div class='hidden post-content-wrapper lite'>";
				}else{
					echo "<div class='post-content-wrapper lite'>";
				}
					echo "<div class='title-wrapper'>";
						//Post content title
						$class = 'post page property';
						if($this->postType != 'post' && $this->postType != 'page'){
							$class .= ' hidden';
						}

						echo "<h4 class='$class' name='post-content-label'>";
							echo  '<span class="capitalize replace-post-type">'.ucfirst($this->postType).'</span> content';
						echo "</h4>";

						echo "<h4 class='property attachment hidden' name='attachment-content-label'>Description:</h4>";

						do_action('tsjippy_frontend_post_content_title', $this->postType);
					echo "</div>";

					//make it possible to select or upload a featured image
					if ( current_user_can( 'upload_files' ) ) {
						add_action(
							'media_buttons',
							function() use ($text){
								echo "<button type='button' name='add-featured-image' class='button add-media'><span class='wp-media-buttons-icon'></span> $text Featured Image</button>";
							},
							5
						);
					}

					//output tinymce window
					$settings = array(
						'wpautop'					=> false,
						'forced_root_block'			=> true,
						'convert_newlines_to_brs'	=> true,
						'textarea_name'				=> "post-content",
						'textarea_rows'				=> 10
					);
					echo  wp_editor($this->postContent, 'post-content', $settings);
					?>
				</div>

				<?php
				try{
					$this->contentManagerOptions();
				}catch(\Exception $e) {
					TSJIPPY\printArray($e);
				}

				//Add a draft button for new posts
				if($this->postId == null || ($this->post->post_status != 'publish' && $this->post->post_status != 'inherit')){
					if($this->postId == null){
						$buttonText = "Save <span class='replace-post-type'>{$this->postName}</span> as draft";
					}else{
						$buttonText = "Update this <span class='replace-post-type'>{$this->postName}</span> draft";
					}

					?>
					<div class='submit-wrapper' style='display: flex;'>
						<button type='button' class='button savedraft' name='draft-post'><?php echo $buttonText;?></button>
					</div>
					<?php
				}
				echo  TSJIPPY\addSaveButton('submit_post', $this->action);

				if($this->fullrights){
					?>
					<div class='submit-wrapper' style='display: flex;'>
						<button type='button' name='publish-post' class='button'>Publish <span class='replace-post-type'><?php echo $this->postName;?></span></button>
					</div>
					<?php
				}

				ob_start();
				// Add archive button
				if(!empty($this->post) && $this->post->post_status != 'archived'){
					?>
					<div class='submit-wrapper'>
						<button type='submit' class='button' name='archive-post' data-post-id='<?php echo  esc_html($this->postId); ?>'>
							Archive <?php echo  esc_html($this->postName); ?>
						</button>
					</div>
					<?php
				}

				// Add delete button
				if(!empty($this->post) && $this->post->post_status != 'trash'){
					?>
					<div class='submit-wrapper'>
						<button type='button' class='button' name='delete-post' data-post-id='<?php echo  esc_html($this->postId); ?>'>
							Delete <?php echo esc_html($this->postName); ?>
						</button>
					</div>
					<?php
				}

				echo apply_filters('tsjippy-frontend-buttons', ob_get_clean(), $this);
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
	 * @param    array     $plugins	Array of existing plugins
	 * @return   array     			Array of new plugins
	 *
	**/
	public function addTinymcePlugin($plugins) {
		wp_localize_script( 'tsjippy_frontend_script',
			'userSelect',
			['html'=>TSJIPPY\userSelect("Select a person to show the link to",true)],
		);

		$url					= TSJIPPY\pathToUrl(PLUGINPATH."js/tiny_mce.js?ver=".PLUGINVERSION);

		if($url){
			$plugins['select_user'] = $url;
		}

		return $plugins;
	}

	/**
	 *
	 * Add a new button to the TinyMCE window to select an user and insert a user shortcode
	 *
	 * @param    array     $buttons	Array of existing buttons
	 * @return   array     			Array of new buttons
	 *
	**/
	public function registerButtons($buttons) {
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
	public function hasEditRights(){
		//Only set this once
		if(!isset($this->editRight)){
			//Check if allowed to edit this
			if(
				!allowedToEdit($this->post)										&&
				!$this->fullrights
			){
				$this->editRight	= false;
			}else{
				$this->editRight	= true;
			}

			$this->editRight	= apply_filters('tsjippy_frontend_content_edit_rights', $this->editRight, $this->postCategory);
		}
	}

	/**
	 *
	 * Fills the submit form with existing option values of the current post
	 *
	**/
	public function fillPostData(){
		//Load existing post data
		if(is_numeric($this->postId)){
			$this->post			= get_post($this->postId);
			
			if($this->post->post_status == 'inherit'){
				$this->orgPost 		= get_post($this->post->post_parent);
				while($this->orgPost->post_status == 'inherit'){
					$this->orgPost	= get_post($this->orgPost->post_parent);
				}

				$this->postParent	= $this->orgPost->post_parent;
				$this->postType 	= $this->orgPost->post_type;
			}else{
				// Check if there are pending changes
				$args = array(
					'post_parent' => $this->postId,
					'post_type'   => 'change',
					'post_status' => 'inherit',
				);

				$revisions = get_children( $args );

				// Load the first revision of this post so that we have the latest updates
				if(!empty($revisions)){
					$this->post			= get_post($revisions[0]);
				}

				$this->postParent	= $this->post->post_parent;
				$this->postType 	= $this->post->post_type;
			}

			$this->postTitle 									= $this->post->post_title;
			$this->postContent 									= $this->post->post_content;
			$this->postImageId									= get_post_thumbnail_id($this->postId);

			$this->postCategory 								= $this->post->post_category;
		}

		if(!empty($_GET['type'])){
			$this->postType 	= $_GET['type'];
		}
		$this->postType 										= apply_filters('tsjippy-frontendcontent-posttype', $this->postType);

		$this->postName 										= str_replace(["_lite", '-'], ["", ' '], $this->postType);

		//show lite version of location by default
		if(str_contains($this->postType, '_lite')){
			$this->lite 		= true;
		}

		if($this->fullrights && is_numeric($this->postId) && $this->post->post_status == 'publish'){
			$this->action = "Update <span class='replace-post-type'>{$this->postName}</span>";
		}else{
			$this->action = "Submit <span class='replace-post-type'>{$this->postName}</span> for review";
		}
	}

	/**
	 *
	 * Prints pending changes to the screen
	 *
	**/
	public function showChanges(){
		if($this->post->post_status != 'inherit'){
			return;
		}

		// Get changes in title and content
		if(!function_exists('wp_get_revision_ui_diff')){
			include_once ABSPATH . 'wp-admin/includes/revision.php';
		}

		add_filter( "_wp_post_revision_field_post_content", function($text){
			return preg_replace('/<!-- .* -->/i', '', $text);
		});

		$result		= wp_get_revision_ui_diff($this->post->post_parent, $this->post->post_parent, $this->post->ID);

		// Get changes in meta values
		$newMeta	= get_post_meta($this->postId);
		if(!$newMeta){
			$newMeta	= [];
		}
		
		$oldMeta	= get_post_meta($this->orgPost->ID);
		if(!$oldMeta){
			$oldMeta	= [];
		}

		//exclude certain keys
		$exclusion	= ['pending_notification_send', '_edit_lock', '_edit_last', '_themeisle_gutenberg_block_stylesheet', '_wp_page_template'];
		foreach($exclusion as $exclude){
			if(isset($oldMeta[$exclude])){
				unset($oldMeta[$exclude]);
			}

			if(isset($newMeta[$exclude])){
				unset($newMeta[$exclude]);
			}
		}

		// Unserialize the meta values
		foreach($newMeta as &$meta){
			$meta[0]	= maybe_unserialize($meta[0]);

			if(empty($meta[0])){
				$meta[0]	= '';
			}

			$meta	= trim(maybe_serialize($meta[0]));
		}
		unset($meta);

		foreach($oldMeta as &$meta){
			$meta[0]	= maybe_unserialize($meta[0]);
			if(empty($meta[0])){
				$meta[0]	= '';
			}

			$meta	= trim(maybe_serialize($meta[0]));
		}
		unset($meta);

		// Check which meta values have been changed
		$changed	= [];

		foreach($newMeta as $key=>$meta){
			if(!isset($oldMeta[$key]) && !empty($meta)){
				$changed[$key]	= ['old'=>'', 'new'=>$meta];
			}
		}

		// Check which values have been added
		foreach(array_intersect($newMeta, $oldMeta) as $key=>$value){
			if($oldMeta[$key] != $value){
				if(is_array($newValue)){
					foreach($newValue as $k=>$v){
						$newV	= maybe_unserialize($v);
						$oldV	= maybe_unserialize($oldValue[$k]);

						if($newV != $oldV){
							$changed[$k]	= ['old'=>$oldV, 'new'=>$newV];
						}
					}
				}elseif($newValue != $oldValue){
					$changed[$key]	= ['old'=>$oldValue, 'new'=>$newValue];
				}
			}
		}

		foreach($changed as $key=>$change){
			$diff	= wp_text_diff($change['old'], $change['new']);
			if(empty($diff)){
				continue;
			}

			// picture id to picture html
			if($key == '_thumbnail_id'){
				if(is_numeric($change['old'])){
					$diff	= str_replace($change['old'], wp_get_attachment_image( $change['old']), $diff);
				}

				if(is_numeric($change['new'])){
					$diff	= str_replace($change['new'], wp_get_attachment_image( $change['new']), $diff);
				}
				$key	= 'Featured image';
			}

			$result[]	= array(
				'id'	=> 'post_meta',
				'name'	=> ucfirst(str_replace('_', ' ', $key)),
				'diff'	=> $diff
			);
		}

		?>
		<button type='button' class='button small show-diff'>Show what is changed</button>
		<fieldset class='post-diff-wrapper hidden'>
		<legend>
			<h4>Change list</h4>
		</legend>
			<?php
			foreach($result as $r){
				echo  "<h4>{$r['name']}</h4>";
				echo  $r['diff'];
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
	public function postTypeSelector(){
		//do not show for lite posts
		if($this->lite){
			return;
		}

		// Only show type selector if we do not query a specific one
		if(!empty($_GET['type'])){
			return;
		}

		if($this->postId == null){
			$labelText = 'Select the content type you want to create:';
		}else{
			$labelText = "You are editing a {$this->postType}, use selector below if you want to change the post type";
		}

		$html	= "<h4>$labelText</h4>";
		$html	.= "<select id='post-type-selector' name='post-type-selector' required>";

		foreach($this->postTypes as $postType => $taxName){
			if($this->postType == $postType){
				$selected = 'selected="selected"';
			}else{
				$selected = '';
			}

			$typeName	= ucfirst(str_replace("-", " ", $postType));
			if($postType == 'attachment'){
				$typeName = 'Picture/Video/Audio';
			}
			$html	.= "<option value='$postType' $selected>$typeName</option>";
		}

		$html	.=	"</select>";

		if(is_numeric($this->postId)){
			?>
			<form action="" method="post" name="change_post_type">
				<input type="hidden" class="no-reset" name="user-id" value="<?php echo  esc_html($this->user->ID); ?>">
				<input type="hidden" class="no-reset" name="post-id" value="<?php echo  esc_html($this->postId); ?>">
				<?php
				echo  $html;
				echo TSJIPPY\addSaveButton('change-post-type','Change the post type');
				?>
			</form>
			<?php
		}else{
			echo  $html;
		}
	}

	/**
	 *
	 * Add a modal form to add a new category for the selected post type
	 *
	**/
	public function addModals(){
		foreach($this->postTypes as $postType => $taxonomy){
			$categories = get_categories( array(
				'orderby' 	=> 'name',
				'order'   	=> 'ASC',
				'taxonomy'	=> $taxonomy,
				'hide_empty'=> false,
			) );

			?>
			<div id="add-<?php echo $postType;?>-type" class="modal hidden">
				<!-- Modal content -->
				<div class="modal-content">
					<span id="modal-close" class="close">&times;</span>
					<form action="" method="post" id="add-<?php echo $postType;?>-type-form" class="add-category">
						<p>Please fill in the form to add a new <?php echo $postType;?> category</p>
						<input type="hidden" class="no-reset" name="post-type" value="<?php echo $postType;?>">
						<input type="hidden" class="no-reset" name="user-id" value="<?php echo $this->user->ID; ?>">

						<label>
							<h4>Category name<span class="required">*</span></h4>
							<input type="text"  name="cat-name" class='wide' required>
						</label>

						<h4>Parent category</h4>
						<select class="" name='cat-parent'>
							<option value=''>---</option>
							<?php
							foreach($categories as $category){
								//Only ouptut categories without a parent
								if($category->parent == 0){
									echo "<option value='$category->cat_ID'>$category->name</opton>";
								}
							}
							?>
						</select>

						<?php echo TSJIPPY\addSaveButton("add_{$postType}_type", "Add $postType category"); ?>
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
	public function postSpecificFields(){
		?>
		<div id="post-attributes"  class="property post<?php if($this->postType != 'post'){echo ' hidden';}?>">
			<div id="expiry-date-div" class="frontend-form">
				<h4>Expiry date</h4>
				<label>
					<input type='date' class='' name='expirydate' min="<?php echo gmdate("Y-m-d"); ?>" value="<?php echo esc_html(get_post_meta($this->postId, 'expirydate', true)); ?>" style="display: unset; width:unset;">
					Set an optional expiry date of this post
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
	public function pageSpecificFields(){
		?>
		<div id="page-attributes" class="property page<?php if($this->postType != 'page'){echo ' hidden';}?>">
			<div id="parentpage" class="frontend-form">
				<h4>Select a parent page</h4>
				<?php
				echo TSJIPPY\pageSelect('parent-page', $this->postParent, '', ['page'], false);
				?>
			</div>

			<?php
			do_action('tsjippy_page_specific_fields', $this->postId);
			?>
			<div id="static-content" class="frontend-form">
				<h4>Update warnings</h4>
				<label>
					<input type='checkbox' name='static-content' value='static-content' <?php if(get_post_meta($this->postId, 'static_content', true)){echo 'checked';}?>>
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
	public function showCategories(){
		foreach($this->postTypes as $postType => $taxName){
			$categories	= get_categories( array(
				'orderby' 		=> 'name',
				'order'   		=> 'ASC',
				'taxonomy'		=> $taxName,
				'hide_empty'	=> false,
			) );

			$postCats	= [];
			// check for the post categories if the current post type matches the type of the current post
			if($this->postType == $postType){
				$postCats	= wp_get_post_terms($this->postId, $taxName, ['fields' => 'ids']);
			}

			?>
			<div class="property <?php echo $postType; if($this->postType != $postType){echo ' hidden';} ?>">
				<div class="frontend-form">
					<h4><?php echo ucfirst($postType);?> type</h4>
					<div class='categories'>
						<?php
						$parentCategoryHtml 	= '';
						$childCategoryHtml 		= '';
						$hidden					= 'hidden';

						foreach($categories as $category){
							$name 				= $category->name;
							$catId 				= $category->cat_ID;
							$catDescription		= $category->description;
							$parent				= $category->parent;
							$checked			= '';
							$class				= 'info-box';
							$taxonomy			= $category->taxonomy;

							//This category is a not a child
							if($parent == 0){
								$html = 'parentCategoryHtml';
							//has a parent
							}else{
								$html = 'childCategoryHtml';
							}

							//if this cat belongs to this post
							if(in_array($catId, $postCats)){
								$checked = 'checked';

								//If this type has child types, show the label
								if(count(get_term_children($category->cat_ID, $taxonomy))>0){
									$hidden = '';
								}
							}

							//if this is a child, hide it and attach the parent id as attribute
							if($parent != 0){
								//Hide subcategory if parent is not in the cat array
								if(!has_term($parent, $taxonomy, $this->postId)){
									$class .= " hidden";
								}

								//Store cat parent
								$class .= "' data-parent='$parent";
							}

							//$$html --> use the value of $html as variable name
							$$html .= "<div class='$class'>";
								$checkboxClass = "{$postType}type";
								if(count(get_term_children($category->cat_ID, $taxonomy)) > 0){
									$checkboxClass .= " parent_cat";
								}

								//Name of the category
								$$html .= "<label class='option-label category-select'>";
									$$html .= "<input type='checkbox' class='$checkboxClass' name='{$taxonomy}-ids[]' value='$catId' $checked>";
									$$html .= $name;
								$$html .= "</label>";

								//Add info-box if needed
								if(!empty($catDescription)){
									$$html .= "<span class='info-text'>$catDescription</span>";
								}

							$$html .= '</div>';
						}

						?>
						<div id='<?php echo $postType;?>_parenttypes'>
							<?php
							echo $parentCategoryHtml;
							?>
							<button type='button' name='add-<?php echo $postType;?>-type-button' class='button add-cat' data-type='<?php echo $postType;?>'>Add category</button>
						</div>

						<label id='subcategorylabel' class='frontend-profile-label <?php echo $hidden ?>'>Sub-category</label>

						<div id='<?php echo $postType;?>_childtypes' class='childtypes'>
							<?php
							echo $childCategoryHtml;
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
	 * Adds options specific for content managers
	 *
	**/
	public function contentManagerOptions(){
		if($this->fullrights){
			$hidden			= '';
		}else{
			$hidden			= 'hidden';
		}

		$buttontext			= 'Show';

		if(empty($hidden)){
			$buttontext		= 'Hide';
		}

		?>
		<button type="button" class="button" id="advanced-publish-options-button" style='display:block; margin-top:15px;'><span><?php echo $buttontext;?></span> advanced options</button>

		<div class="advanced-publish-options <?php echo $hidden;?>">
			<?php
			// Show change author dropdown
			$authorId = $this->user->ID;
			if(isset($this->post->post_author) && is_numeric($this->post->post_author)){
				$authorId = $this->post->post_author;
			}

			echo TSJIPPY\userSelect('Author', true, false, '', 'post-author', [], $authorId);

			// Only show publish date if not yet published
			if(empty($this->post->post_status) || !in_array($this->post->post_status, ['publish', 'inherit'])){
				if(empty($this->post)){
					$publishDate	= gmdate("Y-m-d");
				}else{
					$publishDate	= max(date("Y-m-d", strtotime($this->post->post_date)), gmdate("Y-m-d"));
				}

				?>
				<label>
					<h4>Publishing date</h4>
					<input type="date" min="<?php echo gmdate("Y-m-d");?>" name="publish-date" value="<?php echo $publishDate;?>">
					Define when the content should be published
				</label>
				<?php
			}

			?>
			<div id="nonews" class="frontend-form">
				<h4>News Gallery</h4>
				<label>
					<input type='checkbox' name='skipgallery' value='skipgallery' <?php if(get_post_meta($this->postId, 'skipgallery', true)){echo 'checked';}?>>
					Do not add this <?php echo $this->post->post_type;?> to the news gallery
				</label>
			</div>
			<?php

			$this->postSpecificFields();

			$this->pageSpecificFields();

			do_action('tsjippy_frontend_post_after_content', $this);

			?>
			<h4>View Permissions</h4>
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

			$userRoles	= $wp_roles->role_names;

			$viewRoles	= [];

			if(is_numeric($this->postId)){
				$viewRoles	= get_post_meta($this->postId, 'post_view_roles');
			}

			?>
			<select name='post-view-roles[]' multiple>
				<option value=''>---</option>

				<?php
				foreach($userRoles as $key=>$roleName){
					if(in_array($key, $viewRoles)){
						$selected = 'selected';
					}else{
						$selected = '';
					}
					echo "<option value='$key' $selected>$roleName</option>";
				}
				?>
			</select>
		</div>
		<?php
	}

	/**
	 *
	 * saves base64 images as images and adds them to the library
	 *
	 * @param    array     $matches	Array of matches from a regex
	 *
	 * @return   string     		Image url
	 *
	**/
	public function uploadImages($matches) {
		$ext 			= $matches[1];
		$filename 		= "frontend_picture";
		$basedir 		= wp_upload_dir()['basedir'];
		$newFilePath 	= "$basedir/$filename.$ext";
		$i 				= 0;
		$uploadId		= 0;

		//Find an available filename
		while( file_exists( $newFilePath ) ) {
			$i++;
			$newFilePath = "$basedir/$filename"."_$i.$ext";
		}

		//Decode the base64
		$fileContents = base64_decode(substr_replace($matches[2] , "", -1));

		//Only continue if the decoding was succesfull
		if( $fileContents !== false){
			//Save the image in the uploads folder
			file_put_contents($newFilePath, $fileContents);

			$uploadId		= TSJIPPY\addToLibrary($newFilePath);
		}else{
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
	public function storeCustomCategories($post){
		foreach($this->postTypes as $postType => $taxonomy){
			$cats = [];
			if(@is_array($_POST[$taxonomy.'-ids'])){
				foreach($_POST[$taxonomy.'-ids'] as $catId) {
					if(is_numeric($catId)){
						$cats[] = $catId;
					}
				}

				//Make sure we only send integers
				$cats = array_map( 'intval', $cats );

				// Store
				wp_set_post_terms($post->ID, $cats, $taxonomy);
			}
		}
	}

	/**
	 * Several checks and adjustments to the post contents
	 */
	public function preparePostContent($postContent){
		//Sanitize the post content
		$postContent 	= wp_kses_post(wp_kses_stripslashes($postContent));

		// Remove some tags
		$postContent 	= preg_replace("/(&lt;|<)(del|ins) .*?(>|&gt;)/im", "", $postContent);

		// Checks for opening and closing formating tags
		$tags			= ['b', 'strong', 'i', 'em', 'mark', 'small', 'sub', 'sup', 'span'];
		$pattern		= '';
		foreach($tags as $tag){
			if(!empty($pattern)){
				$pattern	.= "|";
			}
			$pattern	.= "<\/$tag>\s*<$tag>";	// Closing tag, followed by zero or more spaces followed by an opening tag
		}

		$postContent 	= preg_replace_callback("/$pattern/i", function(){
			return ' ';
		}, $postContent);

		//Find any base64 encoded images in the post content and replace the url
		$postContent 	= preg_replace_callback('/src="image\/(\w+);base64,([^"]*)"/is', array($this, 'uploadImages'), $postContent);

		//Find display names in content and replaces them with a link
		$userPageLinks	= new TSJIPPY\UserPageLinks($postContent, true);

		$postContent	= apply_filters('tsjippy_post_content', $userPageLinks->string);

		// Make sure its UTF-8
		$postContent	= mb_convert_encoding($postContent, 'UTF-8', 'UTF-8');

		$postContent	= html_entity_decode($postContent, ENT_QUOTES, 'UTF-8');

		return $postContent;
	}

	/**
	 * Update an existing post
	 */
	public function updateExistingPost(){
		$this->postId = $_POST['post-id'];

		//Retrieve the old post data
		$post = get_post($this->postId);

		// Check if this is a post revison
		if($this->fullrights && $post->post_type == 'change'){
			// delete revision
			$delete = wp_delete_post( $this->postId );

			if ( $delete ) {
				do_action( 'wp_delete_post_revision', $this->postId, $post);
			}

			// Use parent page as post id
			$this->postId	= $post->post_parent;

			// Load parent post data
			$post = get_post($this->postId);
		}

		$this->update = true;

		$newPostData = ['ID' => $this->postId];

		//Check for updates
		if($this->postTitle != $post->post_title){
			//title
			$newPostData['post_title'] 	= $this->postTitle;

			// name
			$postName	= urldecode($this->postTitle);

			//check if name is unique as it used as slug
			$args	= array(
				'post_type'		=> get_post_types(),
				'post_status'	=> 'any',
				'name'          => $postName,
				'numberposts'	=> -1,
				'exclude'		=> [$this->postId],
			);
			$posts	= get_posts( $args);

			$i=1;
			while(!empty($posts)){
				$postName		= urldecode($this->postTitle.'_'.$i);
				$args['name']	= $postName;
				$i++;
				$posts			= get_posts( $args);
			}

			$newPostData['post_name'] 	= $postName;

			//attached file
			if($_POST['post-type'] == 'attachment' && explode('/', $post->post_mime_type)[0] == 'video'){
				$newPostData['_wp_attached_file'] 	= $this->postTitle;
			}
		}

		// update publish date if needed
		if(
			strtotime($post->post_date) > time()	&&								// Current post date is in the future
			!empty($_POST['publish-date']) 			&& 								// a publishing date is set
			$_POST['publish-date'] != gmdate('Y-m-d', strtotime($post->post_date))	// it is not the same as before
		){
			$publishDate					= gmdate("Y-m-d 08:00:00", strtotime($_POST['publish-date']));
			$newPostData['post_date'] 		= $publishDate;
			$newPostData['post_date_gmt'] 	= $publishDate;
		}

		if($this->postContent != $post->post_content){
			$newPostData['post_content'] 	= $this->postContent;
		}

		if($this->status != $post->post_status){
			$newPostData['post_status'] 	= $this->status;
		}

		if( $_POST['post-author'] != $post->post_author){
			$newPostData['post_author']		= $_POST['post-author'];
		}

		//parent
		if(isset($_POST["parent-$post->post_type"])){
			$newPostData['post_parent']		= $_POST["parent-$post->post_type"];
		}

		if($this->postCategories != $post->post_category){
			$newPostData['post_category'] 	= $this->postCategories;
		}

		//we cannot change the post type here
		if($post->post_type != $this->postType && !in_array($post->post_type, ['revision', 'change'])){
			return new WP_Error('frontend_contend', 'You can not change the post type like that!');
		}

		//Create a revision post if we are updating an already published post
		if($this->status == 'pending' && $post->post_status == 'publish'){
			$this->actionText = 'updated';

			foreach($newPostData as $key=>$data){
				$post->$key	= $data;
			}

			// Mark new post as inherit
			$post->post_status	= 'inherit';
			$post->post_name	= $post->ID.'-revision-v1';
			$post->post_parent	= $post->ID;
			$post->post_type	= 'change';
			unset($post->ID);

			// Insert the post into the database.
			$postId 		= wp_insert_post( $post, true, false);

			$post->ID		= $postId;

			$this->postId	= $postId;
		}else{
			$result = wp_update_post( $newPostData, true, false);

			// check for errors
			if(is_wp_error($result)){
				// most likely some invalid data in post, try to fix it.
				if($result->get_error_message() == "Could not update post in the database."){
					$illigalChars	= [];
					foreach(str_split($newPostData['post_content']) as $index=>$chr){
						json_encode($chr);
						if(json_last_error() == 5){
							$illigalChars[$index] = $chr;
						}elseif(json_last_error() == 0 && !empty($illigalChars)){
							$newPostData['post_content']	= str_replace(implode('', $illigalChars), mb_convert_encoding(implode('', $illigalChars), "UTF-8", "auto"), $newPostData['post_content']);
							$illigalChars	= [];
						}
					}

					// try to update again
					$result = wp_update_post( $newPostData, true, false);

					if(is_wp_error($result)){
						return $result;
					}
				}else{
					return $result;
				}				
			}
			
			if(($post->post_status == 'draft' || $post->post_status == 'pending') && $this->status == 'publish'){
				$this->actionText = 'published';

				// If we publish a post which was pending before send an notification to the author
				if( $post->post_status == 'pending'){
					$author		= get_userdata($post->post_author);
					$url		= get_permalink($post->ID);
					$email    	= new ApprovedPostMail($author->display_name, $post->post_type, $url);
					$email->filterMail();

					//Send e-mail
					wp_mail( $author->user_email, $email->subject, $email->message);
				}
			}else{
				$this->actionText = 'updated';
			}
		}

		return get_post($post->ID);
	}

	/**
	 * Create a new post
	 */
	public function createNewPost(){
		$this->update		= false;
		$this->actionText	= 'created';

		//New post
		$post = array(
			'post_type'		=> $this->postType,
			'post_title'    => $this->postTitle,
			'post_content'  => $this->postContent,
			'post_status'   => $this->status,
			'post-author'   => $_POST['post-author']
		);

		if($this->postType == 'attachment'){
			$this->postId 	= TSJIPPY\addToLibrary(TSJIPPY\urlToPath($_POST['attachment'][0]), $this->postTitle, $this->postContent);
			$post['ID']	= $this->postId;
		}else{
			if(isset($_POST["parent-$this->postType"])){
				$newPostData['post_parent']		= $_POST["parent-$this->postType"];
			}

			if(!empty(count($this->postCategories))){
				$post['post_category'] = $this->postCategories;
			}

			//Schedule the post if in the future
			if($_POST['publish-date'] != gmdate('Y-m-d')){
				$publishDate			= gmdate("Y-m-d 08:00:00", strtotime($_POST['publish-date']));

				$post['post_date'] 		= $publishDate;
				$post['post_date_gmt'] 	= $publishDate;
			}

			// Insert the post into the database.
			$this->postId 	= wp_insert_post( $post, true, false);
			$post['ID']		= $this->postId;
		}

		if(is_wp_error($this->postId)){
			// check for errors
			if(is_wp_error($this->postId)){
				// most likely some invalid data in post, try to fix it.
				if($this->postId->get_error_message() == "Could not update post in the database."){
					$illigalChars	= [];
					foreach(str_split($post['post_content']) as $index=>$chr){
						json_encode($chr);
						if(json_last_error() == 5){
							$illigalChars[$index] = $chr;
						}elseif(json_last_error() == 0 && !empty($illigalChars)){
							$post['post-content']	= str_replace(implode('', $illigalChars), mb_convert_encoding(implode('', $illigalChars), "UTF-8", "auto"), $post['post-content']);
							$illigalChars	= [];
						}
					}

					// try to update again
					$this->postId 	= wp_insert_post( $post, true, false);
					$post['ID']		= $this->postId;

					if(is_wp_error($this->postId)){
						return $this->postId;
					}
				}else{
					return $this->postId;
				}				
			}
		}elseif($this->postId === 0){
			return new WP_Error('Inserting post error', "Could not create the $this->postType!");
		}

		return (object) $post;
	}

	/**
	 *
	 * Saves or publishes a new post or updates an existing one
	 *
	 * @param    string     $status	Desired post status
	 * @return   array|WP_Error     		Result message
	 *
	**/
	public function submitPost($status=''){
		$this->status	= $status;
		if(empty($this->status)){
			$this->status	= sanitize_text_field($_POST['post-status']);
		}

		if(
			$this->status	== 'publish'			&&
			$this->fullrights 						&&
			isset($_POST['publish-date']) 			&&
			$_POST['publish-date'] > gmdate('Y-m-d')
		){
			$this->status	= 'future';
		}

		$this->postType 	= sanitize_text_field($_POST['post-type']);
		
		//First letter should be capital in the title
		$this->postTitle 	= ucfirst(trim(sanitize_text_field($_POST['post-title'])));

		$this->oldPost		= '';
		if(is_numeric($_POST['post-id'])){
			$this->oldPost	= get_post($_POST['post-id']);

			$this->postType	= $this->oldPost->post_type;

			// find the parent with a correct posttype
			while(in_array($this->oldPost->post_type, ['change', 'revision'])){
				$this->oldPost	= get_post($this->oldPost->post_parent);
			}
		}elseif(!empty($this->postTitle)){
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

			foreach($posts as $p){
				if(current_time('Y-m-d') == gmdate('Y-m-d', strtotime($p->post_date))){
					$url	= get_permalink($p);
					return new \WP_Error('frontend', "A post with title $this->postTitle is already published today!\nSee it <a href='$url'>here</a>");
				}
			}
		}

		$this->postContent 	= $this->preparePostContent($_POST['post-content']);

		$this->postCategories = [];
		if(is_array($_POST['category-id'])){
			foreach($_POST['category-id'] as $categoryId) {
				if(!empty($categoryId)){
					$this->postCategories[] = $categoryId;
				}
			}
		}

		// Create the possibility of pre-publish checks
		$error	= apply_filters('tsjippy_frontend_content_validation', '', $this);
		if(is_wp_error($error)){
			return $error;
		}

		//Check if editing an existing post
		if(is_numeric($_POST['post-id'])){
			$post	= $this->updateExistingPost();
		}else{
			$post	= $this->createNewPost();
		}

		if(is_wp_error($post)){
			return $post;
		}

		//Set the featured image
		if(isset($_POST['post-image-id']) && $_POST['post-image-id'] != 0){
			set_post_thumbnail($this->postId, $_POST['post-image-id']);
		}elseif($this->update){
			delete_post_thumbnail($this->postId);
		}

		//Static content
		if(isset($_POST['static-content'])){
			update_metadata( 'post', $this->postId, 'static_content', true);
		}else{
			delete_post_meta($this->postId, 'static_content');
		}

		// Role view rights
		delete_post_meta($this->postId, 'post_view_roles');
		if(isset($_POST['post-view-roles'])){
			if(in_array($_POST['permission-filter-type'], ['blobk', 'allow'])){
				update_metadata('post', $this->postId, 'permission_filter_type', $_POST['permission-filter-type']);
			}

			foreach($_POST['post-view-roles'] as $role){
				add_metadata( 'post', $this->postId, 'post_view_roles', $role);
			}
		}

		//Expiry date
		if(isset($_POST['expirydate'])){
			if(empty($_POST['expirydate'])){
				delete_post_meta($this->postId, 'expirydate');
			}else{
				//Store expiry date
				update_metadata( 'post', $this->postId, 'expirydate', $_POST['expirydate']);
			}
		}

		// News Gallery
		if(!isset($_POST['skipgallery'])){
			delete_post_meta($this->postId, 'skipgallery');
		}else{
			update_metadata( 'post', $this->postId, 'skipgallery', $_POST['skipgallery']);
		}

		if($post->post_status == 'pending' && $this->status == 'pending'){
			sendPendingPostWarning($post, $this->update);
		}

		//store attachment categories
		$this->storeCustomCategories($post);

		do_action('tsjippy_after_post_save', (object)$post, $this);

		wp_after_insert_post( $post, $this->update, $this->oldPost );

		//Return result
		if($this->status == 'publish'){
			$message	= "Succesfully $this->actionText the $this->postType";
		}elseif($this->status == 'draft'){
			$message	= "Succesfully $this->actionText the draft for this $this->postType";
		}elseif($_POST['publish-date'] > gmdate('Y-m-d') && $this->status == 'future'){
			$message	= "Succesfully $this->actionText the $this->postType, it will be published on ".date('d F Y', strtotime($_POST['publish-date'])).' 8 AM';
		}else{
			$message	= "Succesfully $this->actionText the $this->postType, it will be published after it has been reviewed";
		}

		return [
			'message'	=> $message,
			'id'		=> $this->postId,
			'post'		=> $post
		];
	}

	/**
	 *
	 * Archives an existing post
	 * @return   string|WP_Error     		Result message
	 *
	**/
	public function archivePost(){
		$postId 	= $_POST['post-id'];

		$data = array(
			'ID' 			=> $postId,
			'post_status' 	=> 'archived'
		   );
		  
		wp_update_post( $data );

		$post		= get_post($postId);

		$postType	= get_post_type($post);

		if($postType){
			return "Succesfully archived $postType '{$post->post_title}'<br>You can leave this page now";
		}else{
			return new WP_Error('Post archival error', 'Something went wrong');
		}
	}

	/**
	 *
	 * Removes an existing post
	 * @return   string|WP_Error     		Result message
	 *
	**/
	public function removePost(){
		$postId = $_POST['post-id'];

		$post		= wp_trash_post($postId);

		$postType	= get_post_type($post);

		if($postType){
			return "Succesfully deleted $postType '{$post->post_title}'<br>You can leave this page now";
		}else{
			return new WP_Error('Post removal error', 'Something went wrong');
		}
	}

	/**
	 *
	 * Change the type of an existing post
	 *
	 * @return   string|WP_Error     		Result message
	 *
	**/
	public function changePostType(){
		$postType	= $_POST['post-type-selector'];

		$postId		= $_POST['post-id'];

		$result		= set_post_type($postId, $postType);

		// remove the parent as parents need to be of the same type
		$this->removeParents($postId);

		if($result){
			return "Succesfully updated the type to $postType";
		}else{
			return new WP_Error('Update failed', "Could not update the type");
		}
	}

	/**
	 * Removes the parent from a post
	 *
	 * @param	int		$postId	The WP_Post id
	 */
	public function removeParents($postId){
		if(has_post_parent($postId)){
			wp_update_post(
				array(
					'ID'            => $postId,
					'post_parent'   => 0
				)
			);
		}

		// Remove as parent from any children
		foreach(get_children($postId) as $child){
			$this->removeParents($child->ID);
		}
	}

	/**
	 * Get the meta value of the revision or parent post if empty
	 */
	public function getPostMeta( $key){
		$value  = get_post_meta($this->postId, $key, true);
	
		// use parent value if the revision value is non existing
		if(empty($value) && !empty($this->orgPost)){
			$value    =  get_post_meta($this->orgPost->ID, $key, true);
		}
	
		return $value;
	}
}
