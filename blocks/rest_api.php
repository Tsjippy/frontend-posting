<?php
namespace TSJIPPY\FRONTENDPOSTING;
use TSJIPPY;

add_action( 'rest_api_init',  __NAMESPACE__.'\restApiInitBlocks');
function restApiInitBlocks() {
	// show post list
	register_rest_route( 
		RESTAPIPREFIX.'/frontendposting',  
		'/your_posts', 
		array(
			'methods' 				=> 'GET',
			'callback' 				=> __NAMESPACE__.'\yourPosts',
			'permission_callback' 	=> function($rest){
				return current_user_can('read');
			},
		)
	);

	// show pening pages
	register_rest_route( 
		RESTAPIPREFIX.'/frontendposting',  
		'/pending_pages', 
		array(
			'methods' 				=> 'GET',
			'callback' 				=> __NAMESPACE__.'\pendingPages',
			'permission_callback' 	=> function($rest){
				return current_user_can('edit_posts');
			},
		)
	);
}