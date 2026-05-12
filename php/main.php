<?php
namespace TSJIPPY\FRONTENDPOSTING;
use TSJIPPY;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Gets all the pages who have not been edited recently and are not static
 *
 * @return	array	Array of post objects
 */
function getOldPages(){
	$maxAge	= SETTINGS['max-page-age'] ?? 6;

	//Get all pages without the static content meta key who have been edited last more than X months ago
	return get_posts(array(
		'numberposts'      	=> -1,
		'post_type'        	=> ['page', 'location'],
		'orderby'			=> 'modified',
		'meta_query' => array(
			'relation' => 'OR',
			array(
				'key' 		=> 'static_content',
				'compare'	=> 'NOT EXISTS'
			),
			array(
				'key'		=> 'static_content',
				'compare'	=> '!=',
				'value'		=> true
			),
		),
		'date_query' => [
			'column' => 'post_modified',
			'before'  => "$maxAge months ago",
		],
	));
}

/**
 * Sends a warning to content managers if a pending post is published
 * 
 * @param object $post The post object
 * @param bool $update Whether this is an update or a new post
 * 
 * @return void
 */
function sendPendingPostWarning( object $post, $update){
	//Do not continue if already send
	if(!empty(get_post_meta($post->ID, 'pending_notification_send', true))){
		return;
	}

	$channels	= SETTINGS['pending-channels'] ?? ['email'];
	
	$roles		= SETTINGS['content-manager-roles'] ?? [];
	
	//get all the content managers
	$users = get_users( array(
		'role__in'    => $roles,
	));
	
	if($update){
		$actionText = 'updated';
	}else{
		$actionText = 'created';
	}
	
	$type = $post->post_type;
	
	//send notification to all content managers
	$url			= get_permalink(SETTINGS['front-end-post-page'] ?? '');
	if(!$url){
		return;
	}

	$url			= add_query_arg( ['post-id' => $post->ID], $url );
	$authorName		= get_userdata($post->post_author)->display_name;
	
	foreach($users as $user){
		if(in_array('email', $channels)){
			$pendinfPostEmail    = new PendingPostEmail($user, $authorName, $actionText, $type, $url);
			$pendinfPostEmail->filterMail();
				
			//Send e-mail
			wp_mail( $user->user_email, $pendinfPostEmail->subject, $pendinfPostEmail->message);
		}
	}
	
	//Mark warning as send
	update_metadata( 'post', $post->ID, 'pending_notification_send', true);
}

//Delete the indicator that the warning has been send
add_action(  'transition_post_status', __NAMESPACE__.'\onStatusChange', 10, 3 );
/**
 * Deletes the indicator that the pending post warning has been send when a post is published
 * 
 * @param string $newStatus The new status of the post
 * 	@param string $oldStatus The old status of the post
 * @param object $post The post object
 * 
 * @return void
 */
function onStatusChange( $newStatus, $oldStatus, $post ) {
	if ($newStatus == 'publish' && $oldStatus == 'pending'){
		delete_post_meta($post->ID, 'pending_notification_send');
	}
}

//Allow display attributes in post content
add_filter( 'safe_style_css',  __NAMESPACE__.'\safeStyles');
/**
 * Adds display to the list of safe style attributes
 *
 * @param array $styles The list of safe style attributes
 *
 * @return array The updated list of safe style attributes
 */
function safeStyles( $styles ) {
    $styles[] = 'display';
    return $styles;
}

/**
 * Checks if the current user is allowed to edit a post
 * 
 * @param object|int $post The post object or post ID to check
 *
 * @return	boolean			True if allowed
 */
function allowedToEdit($post){
	if(empty($post)){
		return true;
	}
	
	if(is_numeric($post)){
		$post	= get_post($post);
	}

	$postId			= $post->ID;
	$user 			= wp_get_current_user();
	$postAuthor 	= $post->post_author;
	$postCategory 	= $post->post_category;
	$userPageId 	= TSJIPPY\maybeGetUserPageId($user->ID);
	$ministries 	= (array)get_user_meta($user->ID, "jobs", true);

	if (
		$postAuthor == $user->ID 															|| 	// Own page
		in_array($post->ID, array_keys($ministries))										||	// ministry pafe
		$userPageId == $postId																||	// pseronal user page
		apply_filters('tsjippy_frontend_content_edit_rights', false, $postCategory)				||	// external filter
		$user->has_cap( 'edit_others_posts' )													// user has permission to edit any post
	){
		return true;
	}

	return false;
}

//Add post edit button
add_filter( 'the_content', __NAMESPACE__.'\filterContent', 15, 2);
/**
 * Filters the content to add an edit button for allowed users
 *
 * @param string $content The post content
 * @param string $caller The caller of the filter
 *
 * @return string The updated content
 */
function filterContent( $content, $caller='' ) {
	//Do not show if:
	if (
		!is_user_logged_in() 							||	// not logged in or
		str_contains($content, '[front_end_post]')  	||	// already on the post edit page
		//!is_singular() 									||  // it is not a single page
		is_tax()										||	// not an archive page
		is_front_page()									||	// is the front page
		$caller == 'mailchimp'								// mailchimp
	){
		return $content;
	}

	global $post;
	
	//This is a draft
	if(isset($_GET['p']) || isset($_GET['page_id'])){
		if(isset($_GET['p'])){
			$postId 	= $_GET['p'];
		}else{
			$postId 	= $_GET['page_id'];
		}
	//published
	}else{
		$postId 		= $post->ID;
	}

	$postViewRoles	= get_post_meta($postId, 'post_view_roles');
	if(!empty($postViewRoles) && is_array($postViewRoles)){
		$type		= get_post_meta($postId, 'permission_filter_type', true);
		
		if(!empty($type)){
			$roles 		= get_userdata(get_current_user_id())->roles;
			$match		= array_intersect($postViewRoles, $roles);

			if(
				(
					$type 	== 'block'	&&
					$match	== true		
				) ||
				(
					$type 	== 'allow'	&&
					$match	== false		
				)
			){
				return '<div class="error">You have no permission to see this</div>';
			}
		}
	}
	
	$buttonHtml	= '';
	//Add an edit page button if:
	if ( allowedToEdit($post) ){
		$type 		= str_replace('-', ' ', $post->post_type);
		$buttonText = "Edit this $type";

		if($type == 'attachment'){
			$url		= admin_url("post.php?post=$post->ID&action=edit");
			$buttonHtml	= "<a href=$url class='button small hidden' class='page-edit'>$buttonText</a>";
		}else{
			$buttonHtml	= "<button type='button' class='button small hidden page-edit' data-post-id='$postId'>$buttonText</button>";
		}
	}
	$buttonHtml	= apply_filters('post-edit-button', $buttonHtml, $post, $content);

	return $buttonHtml."<div class='content-wrapper'>$content</div>";
}

add_filter('tsjippy-template-filter',  __NAMESPACE__.'\templateFilter');
/**
 * Filters the template to show a custom template for attachments
 * 
 * @param string $templateFile The original template file
 * @return string The updated template file
 */
function templateFilter($templateFile){
	if(str_contains($templateFile, 'single-attachment')){
		return PLUGINPATH.'templates/single-attachment.php';
	}

	return $templateFile;
}

add_filter('display_post_states', __NAMESPACE__.'\postStatus', 10, 2);
/**
 * Adds display to the list of safe style attributes
 * 
 * @param array $states The list of post states
 * @param object $post The post object
 * 
 * @return array The updated list of safe style attributes
 */
function postStatus( $states, $post ) {
    if ($post->ID == SETTINGS['front-end-post-page'] ?? '' ) {
        $states[] = __('Frontend posting page');
    }elseif ( $post->ID == SETTINGS['pending-posts-page'] ?? '' ) {
        $states[] = __('Pending posts page');
    }

    return $states;
}