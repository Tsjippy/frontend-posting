<?php
namespace TSJIPPY\FRONTENDPOSTING;
use TSJIPPY;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action('init', __NAMESPACE__.'\initTasks');
function initTasks(){
	//add action for use in scheduled task
	add_action( 'expired_posts_check_action', __NAMESPACE__.'\expiredPostsCheck' );
	add_action( 'page_age_warning_action', __NAMESPACE__.'\pageAgeWarning' );
	add_action( 'publish_posts_action', __NAMESPACE__.'\publishPost' );
	add_action( 'publish_sheduled_posts_action', __NAMESPACE__.'\publish_missed_posts' );
	
}

function scheduleTasks(){
    TSJIPPY\scheduleTask('expired_posts_check_action', 'daily');
	TSJIPPY\scheduleTask('publish_sheduled_posts_action', 'quarterly');

	$freq	= SETTINGS['page-age-reminder'] ?? false;
	if($freq){
		TSJIPPY\scheduleTask('page_age_warning_action', $freq);
	}
}

/**
 * Checks for expired posts and removes them
 */
function expiredPostsCheck(){
	//Get all posts with the expirydate meta key with a value equal or before today
	$posts = get_posts(array(
		'numberposts'      => -1,
		'meta_query' => array(
			'relation' => 'AND',
			array(
				'key' => 'expirydate',
				'compare' => 'EXISTS'
			),
			array(
				'key' => 'expirydate',
				'value' => date("Y-m-d"), 
				'compare' => '<=',
				'type' => 'DATE'
			),
		)
	));
	
	foreach($posts as $post){
		$status	= SETTINGS['expired-post-type'] ?? 'trash';

		if($status == 'trash'){
			wp_trash_post($post->ID);
		}else{
			wp_update_post(
				array(
					'ID'             => $post->ID,
					'post_status'    => 'archived',
				),
				false,
				false
			);
		}
		TSJIPPY\printArray("Moving '{$post->post_title}' to $status as it has expired");

	}
}

/**
 * Checks for page who are not updated for a long time
*/
function pageAgeWarning(){
	$emails					= [];
	
	//Loop over all the pages
	foreach ( getOldPages() as $page ) {
		//Get the ID of the current page
		$postId 				= $page->ID;

		$postTitle 				= $page->post_title;

		//Get the edit page url
		$url		= get_permalink(SETTINGS['front-end-post-page'] ?? '');
		$url 		= add_query_arg( ['post-id' => $postId], $url );

		//Get the last modified date
		$secondsSinceUpdated 	= time() - get_post_modified_time('U', true, $page);
		$pageAge				= round($secondsSinceUpdated /60 /60 /24);
		
		//Send an e-mail
		$recipients = getPageRecipients($page);
		foreach($recipients as $recipient){
			$email	= $recipient->user_email;
			//Only email if valid email
			if(!str_contains($email,'.empty')){

				if(!isset($emails[$email])){

					$postOutOfDateEmail    = new PostOutOfDateEmail($recipient, $postTitle, $pageAge, $url);
					$postOutOfDateEmail->filterMail();

					$emails[$email]	= [
						'subject'	=> $postOutOfDateEmail->subject,
						'message'	=> $postOutOfDateEmail->message,
						'count'		=> 1
					];
				}else{
					$postOutOfDateEmail    = new PostOutOfDateEmails($recipient, $postTitle, $pageAge, $url);
					$postOutOfDateEmail->filterMail();

					// replace the main message
					if($emails[$email]['count'] == 1){
						$emails[$email]['message']	= $postOutOfDateEmail->message;
					}
					$message	= $emails[$email]['message'];

					$count		= intval($emails[$email]['count']);
					$count++;

					$emails[$email]	= [
						'subject'	=> $postOutOfDateEmail->subject,
						'message'	=> "$message<br><a href='$url'>$postTitle</a><br>",
						'count'		=> $count
					];
				}
			}
		}
	}

	foreach($emails as $address => $email){
		wp_mail( $address, $email['subject'], $email['message']);
	}
}

/**
 * Function to check who the recipients should be for the page update mail
 */
function getPageRecipients($page){

	$recipients = [];
	
	//Get all the users with a ministry set
	$users = get_users( 
		array( 
			'meta_key'     => 'jobs'
		)	
	);
	
	//Loop over the users to see if they have this ministry set
	foreach($users as $user){
		if (in_array($page->ID, array_keys(get_user_meta( $user->ID, 'jobs', true)))){
			$recipients[] = $user;
		}
	}
	
	//If no one is responsible for this page
	if(empty(count($recipients))){
		$recipients = get_users( array(
			'role'    => 'editor',
		));
	}
	
	return $recipients;
}

/**
 * publish any scheduled post that missed its schedule
 */
function publish_missed_posts(){
	$posts = get_posts(
		array(
			'post_type'		=> 'any',
			'numberposts'	=> -1,
			'post_status'	=> 'future',
			'date_query' => [
				'column' => 'post_date',
				'before'  => "now",
			],
		)
	);

	foreach($posts as $post){
		wp_publish_post($post);
	}
}
