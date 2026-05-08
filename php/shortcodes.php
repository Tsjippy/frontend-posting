<?php
namespace TSJIPPY\FRONTENDPOSTING;
use TSJIPPY;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_shortcode('your_posts', __NAMESPACE__.'\yourPosts');

function yourPosts(){
	//load js
	wp_enqueue_script('tsjippy_table_script');
	
	//Get all posts for the current user
	$postTypes	= get_post_types(['public'=>true]);
	unset( $postTypes['attachment'] );
	
	$userUserPosts = get_posts(
		array(
			'post_type'		=> $postTypes,
			'post_status'	=> 'any',
			'author'		=> get_current_user_id(),
			'orderby'		=> 'post_date',
			'order'			=> 'ASC',
			'numberposts'	=> -1,
		)
	);
	
	$html = "<h2 class='table-title'>Content submitted by you</h2>";
		$html .= "<table class='tsjippy table' id='user-posts'>";
			$html .= "<thead>";
				$html .= "<tr>";
					$html .= "<th>Date</th>";
					$html .= "<th>Type</th>";
					$html .= "<th>Title</th>";
					$html .= "<th>Status</th>";
					$html .= "<th>Actions</th>";
				$html .= "</tr>";
			$html .= "</thead>";
		foreach($userUserPosts as $post){
			$date 		= get_the_modified_date('d F Y',$post);
			
			$type		= ucfirst($post->post_type);
			
			$title 		= $post->post_title;
			
			$status		= ucfirst($post->post_status);
			if($status == 'Publish'){
				$status = 'Published';
			}
			
			$url 		= get_permalink($post);
			$editUrl	= get_permalink(SETTINGS['front-end-post-page'] ?? '');
			if(!$editUrl){
				$editUrl = '';
			}
			$editUrl 	= add_query_arg( ['post-id' => $post->ID], $editUrl);
			if($post->post_status == 'publish'){
				$view = 'View';
			}else{
				$view = 'Preview';
			}
			$actions= "<span><a href='$url'>$view</a></span><span style='margin-left:20px;'> <a href='$editUrl'>Edit</a></span>";
			
			$html .= "<tr class='table-row'>";
				$html .= "<td>$date</td>";
				$html .= "<td>$type</td>";
				$html .= "<td>$title</td>";
				$html .= "<td>$status</td>";
				$html .= "<td>$actions</td>";
			$html .= "</tr>";
		}
		$html .= "</table>";
	
	return $html;
}

//Shortcode to display all pages and post who are pending
add_shortcode("pending-pages", __NAMESPACE__.'\pendingPages');

function pendingPages(){
	//Get all the posts with a pending status
	$pendingPosts 	= get_posts( 
		array(
			'post_status'	=> 'pending',
			'post_type'		=> 'any',
			'numberposts'	=> -1
		)
	);

	//Get all the posts with a pending revision
	$pendingRevisions 	= get_posts( 
		array(
			'post_status'	=> 'inherit',
			'post_type'		=> 'change',
			'numberposts'	=> -1
		)
		
	);

	$url			= get_permalink(SETTINGS['front-end-post-page'] ?? '');
	if(!$url){
		return '';
	}

	$html='';
	//Only if there are any pending posts
	if ( $pendingPosts) {
		$html .= "<strong>Pending content:</strong><br>";
		$html .= "<ul>";
		//For each pending post add a link to edit the post
		foreach ( $pendingPosts as $post ) {
			$url = add_query_arg( ['post-id' => $post->ID], $url );
			if(strtotime($post->post_date_gmt) > time()){
				$date	= date(DATEFORMAT, strtotime($post->post_date_gmt));
				$html .= "<li>$post->post_title (scheduled for $date) <a href='$url'>Publish now</a></li>";
			}else{
				$html .= "<li>$post->post_title <a href='$url' target='_blank'>Review and publish</a></li>";
			}
		}
		$html .= "</ul>";
	}

	if ( $pendingRevisions) {
		$html .= "<br><br><strong>Pending content revisions:</strong><br>";
		$html .= "<ul>";
		//For each pendingRevisions post add a link to edit the post
		foreach ( $pendingRevisions as $post ) {
			$url = add_query_arg( ['post-id' => $post->ID], $url );
			$html .= "<li>$post->post_title <a href='$url'>Review changes</a></li>";
		}
		$html .= "</ul>";
	}

	if(!empty($html)){
		return "<p>$html</p>";
	}
	
	return "<p>No pending posts or pages found</p>";
}

//Shortcode to display number of pending posts and pages
add_shortcode('pending_post_icon', __NAMESPACE__.'\pendingPostIcon');
function pendingPostIcon(){
	//Get all the posts with a pending status
	$pendingPosts 	= get_posts( 
		array(
			'post_status'	=> 'pending',
			'post_type'		=> 'any',
			'numberposts'	=> -1
		)
	);

	//Get all the posts with a pending revision
	$pendingRevisions 	= get_posts( 
		array(
			'post_status'	=> 'inherit',
			'post_type'		=> 'change',
			'numberposts'	=> -1
		)
	);

	if ( $pendingPosts || $pendingRevisions) {
		$pendingTotal = count($pendingPosts) + count($pendingRevisions);
		return "<span class='numberCircle'>$pendingTotal</span>";
	}
}

//Add shortcode for the post edit form
add_shortcode("front_end_post", __NAMESPACE__.'\fontEndPost');
function fontEndPost(){
	$frontEndContent	= new FrontEndContent();
	return $frontEndContent->frontendPost();
}

//Add shortcode for the post edit form
add_shortcode("old-pages", __NAMESPACE__.'\oldPages');
function oldPages(){
	$oldPages	= getOldPages();

	$html	= '<table class="tsjippy table">';
		$html	.= "<tr>";
			$html	.= "<th>";
				$html	.= "Title";
			$html	.= "</th>";
			$html	.= "<th>";
				$html	.= "Last Modified";
			$html	.= "</th>";
			$html	.= "<th>";
				$html	.= "Author";
			$html	.= "</th>";
		$html	.= "</tr>";
		
		foreach($oldPages as $page){
			$url					= get_permalink($page);
			$authorUrl				= TSJIPPY\maybeGetUserPageUrl($page->post_author);
			$authorName				= get_userdata($page->post_author)->first_name;
			$secondsSinceUpdated 	= time() - get_post_modified_time('U', true, $page);
			$pageAge				= round($secondsSinceUpdated /60 /60 /24);

			$html	.= "<tr>";
				$html	.= "<td>";
					$html	.= "<a href='$url'>$page->post_title</a>";
				$html	.= "</td>";
				$html	.= "<td>";
					$html	.= "<a href='$url'>$pageAge days</a>";
				$html	.= "</td>";
				$html	.= "<td>";
					$html	.= "<a href='$authorUrl'>$authorName</a>";
				$html	.= "</td>";
			$html	.= "</tr>";
		}
	$html	.= '</table>';

	return $html;
}