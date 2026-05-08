<?php
namespace TSJIPPY\FRONTENDPOSTING;
use TSJIPPY;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'rest_api_init', __NAMESPACE__.'\restApiInit');
function restApiInit() {
	// get_attachment_contents
	register_rest_route(
		RESTAPIPREFIX.'/frontend_posting',
		'/get_attachment_contents',
		array(
			'methods' 				=> 'POST',
			'callback' 				=> __NAMESPACE__.'\getAttachmentContents',
			'permission_callback' 	=> '__return_true',
			'args'					=> array(
				'attachment-id'		=> array(
					'required'	=> true,
					'validate_callback' => function($attachmentId){
						return is_numeric($attachmentId);
					}
				),
			)
		)
	);

	// add_category
	register_rest_route(
		RESTAPIPREFIX.'/frontend_posting',
		'/add_category',
		array(
			'methods' 				=> 'POST',
			'callback' 				=> __NAMESPACE__.'\addCategory',
			'permission_callback' 	=> '__return_true',
			'args'					=> array(
				'cat-name'		=> array('required'	=> true),
				'post-type'		=> array(
					'required'	=> true,
					'validate_callback' => function($param) {
						return in_array($param, get_post_types());
					}
				),
			)
		)
	);

	//submit_post
	register_rest_route(
		RESTAPIPREFIX.'/frontend_posting',
		'/submit_post',
		array(
			'methods' 				=> 'POST',
			'callback' 				=> __NAMESPACE__.'\submitPost',
			'permission_callback' 	=> '__return_true',
			'args'					=> array(
				'post-type'		=> array(
					'required'	=> true,
					'validate_callback' => function($param) {
						return in_array($param, get_post_types());
					}
				),
				'post-title'		=> array(
					'required'	=> true
				),
				'post-content'	=> array(
					'required'	=> true
				),
				'post-author'	=> array(
					'required'	=> true
				),
				'publish-date'	=> array(
					'validate_callback' => function($param) {
						return TSJIPPY\isDate($param);
					}
				),
			)
		)
	);

	// remove_post
	register_rest_route(
		RESTAPIPREFIX.'/frontend_posting',
		'/remove_post',
		array(
			'methods' 				=> 'POST',
			'callback' 				=> function(){
				$frontEndContent	= new FrontEndContent();
				return $frontEndContent->removePost();
			},
			'permission_callback' 	=> function(){
				$frontEndContent	= new FrontEndContent();
				return $frontEndContent->fullrights;
			},
			'args'					=> array(
				'post-id'		=> array(
					'required'	=> true,
					'validate_callback' => function($postId){
						return is_numeric($postId);
					}
				)
			)
		)
	);

	// archive post
	register_rest_route(
		RESTAPIPREFIX.'/frontend_posting',
		'/archive_post',
		array(
			'methods' 				=> 'POST',
			'callback' 				=> function(){
				$frontEndContent	= new FrontEndContent();
				return $frontEndContent->archivePost();
			},
			'permission_callback' 	=> function(){
				$frontEndContent	= new FrontEndContent();
				return $frontEndContent->fullrights;
			},
			'args'					=> array(
				'post-id'		=> array(
					'required'	=> true,
					'validate_callback' => function($postId){
						return is_numeric($postId);
					}
				)
			)
		)
	);

	// refresh post lock
	register_rest_route(
		RESTAPIPREFIX.'/frontend_posting',
		'/refresh_post_lock',
		array(
			'methods' 				=> 'POST',
			'callback' 				=> function(){
				try{
					if(!function_exists('wp_set_post_lock')){
						include ABSPATH . 'wp-admin/includes/post.php';
					}
					wp_set_post_lock($_POST['post-id']);
					return 'Succes';
				}catch (\Exception $e) {
					return $e;
				}
			},
			'permission_callback' 	=> '__return_true',
			'args'					=> array(
				'post-id'		=> array(
					'required'	=> true,
					'validate_callback' => function($postId){
						return is_numeric($postId);
					}
				)
			)
		)
	);

	// delete post lock
	register_rest_route(
		RESTAPIPREFIX.'/frontend_posting',
		'/delete_post_lock',
		array(
			'methods' 				=> 'POST',
			'callback' 				=> function(){
				delete_post_meta( $_POST['post-id'], '_edit_lock');
				return 'Succes';
			},
			'permission_callback' 	=> '__return_true',
			'args'					=> array(
				'post-id'		=> array(
					'required'	=> true,
					'validate_callback' => function($postId){
						return is_numeric($postId);
					}
				)
			)
		)
	);

	// change post type
	register_rest_route(
		RESTAPIPREFIX.'/frontend_posting',
		'/change_post_type',
		array(
			'methods' 				=> 'POST',
			'callback' 				=> function(){
				$frontEndContent	= new FrontEndContent();
				return $frontEndContent->changePostType();
			},
			'permission_callback' 	=> function(){
				$frontEndContent	= new FrontEndContent();
				return $frontEndContent->fullrights;
			},
			'args'					=> array(
				'post-id'		=> array(
					'required'	=> true,
					'validate_callback' => function($postId){
						return is_numeric($postId);
					}
				),
				'post-type-selector'		=> array(
					'required'	=> true,
					'validate_callback' => function($param) {
						return in_array($param, get_post_types());
					}
				)
			)
		)
	);

	// Get frontend content form
	register_rest_route(
		RESTAPIPREFIX.'/frontend_posting',
		'/post_edit',
		array(
			'methods' 				=> 'POST',
			'callback' 				=> __NAMESPACE__.'\sendForm',
			'permission_callback' 	=> function(){
				return allowedToEdit($_REQUEST['post-id']);
			},
			'args'					=> array(
				'post-id'		=> array(
					'required'			=> true,
					'validate_callback' => function($postId){
						return is_numeric($postId);
					}
				)
			)
		)
	);

	// Get post
	register_rest_route(
		RESTAPIPREFIX.'/frontend_posting',
		'/post_result',
		array(
			'methods' 				=> 'POST',
			'callback' 				=> __NAMESPACE__.'\sendPost',
			'permission_callback' 	=> '__return_true',
			'args'					=> array(
				'post-id'		=> array(
					'required'	=> true,
					'validate_callback' => function($postId){
						return is_numeric($postId);
					}
				)
			)
		)
	);

	// Check for posts with the same title
	register_rest_route(
		RESTAPIPREFIX.'/frontend_posting',
		'/check_duplicate',
		array(
			'methods' 				=> 'POST',
			'callback' 				=> __NAMESPACE__.'\checkForDuplicate',
			'permission_callback' 	=> '__return_true',
			'args'					=> array(
				'title'		=> array(
					'required'	=> true
				)
			)
		)
	);

}

/**
 * Converts a file's contents to html
 */
function getAttachmentContents(\WP_REST_Request $request ){
	$path	= get_attached_file($request['attachment-id']);

	if(!file_exists($path)){
		return new \WP_Error('frontendposting', "File $path does not exist!");
	}

	return readTextFile($path);
}

/**
 * Add a new category to a post type
 */
function addCategory(\WP_REST_Request $request ){
	$name		= $request->get_param('cat-name');
	$parent		= $request->get_param('cat-parent');
	$postType	= $request->get_param('post-type');
	$taxes		= get_object_taxonomies($postType);
	$taxonomy	= end($taxes);
	
	$args 		= ['slug' => strtolower($name)];
	if(is_numeric($parent)){
		$args['parent'] = $parent;
	}
	
	$result 	= wp_insert_term( ucfirst($name), $taxonomy, $args);

	if(is_wp_error($result)){
		return $result;
	}

	do_action('tsjippy_after_category_add', $postType, strtolower($name), $result);
	
	if(is_wp_error($result)){
		return new \WP_Error('Event Cat error', $result->get_error_message(), ['status' => 500]);
	}else{
		return [
			'id'		=> $result['term_id'],
			'message'	=> "Added $name succesfully as a $postType category"
		];
	}
}

/**
 * Get the front posting form
 */
function sendForm(){
	do_action('wp_enqueue_scripts');
	wp_enqueue_media();
	
	wp_enqueue_editor();

	$frontEndContent			= new FrontEndContent();
	$frontEndContent->postId	= $_REQUEST['post-id'];
	$html						= $frontEndContent->frontendPost(true);

	\_WP_Editors::enqueue_scripts();
	ob_start();
	wp_print_scripts(["tsjippy_frontend_script"]);
	print_footer_scripts();
	\_WP_Editors::editor_js();
	wp_print_media_templates();
	$js	= ob_get_clean();

	do_action('wp_enqueue_style');
	ob_start();
	wp_print_styles();
	$css	= ob_get_clean();

	return [
		'html'	=> $html,
		'js'	=> $js,
		'css'	=> $css
	];
}

/**
 * Gets a post
 */
function sendPost(){
	$postId	= $_REQUEST['post-id'];

	// Get the picture
	$url	= get_the_post_thumbnail_url($postId, 'full');

	// Get  the content
	$content = apply_filters( 'the_content', get_the_content() );

	return [
		'url'		=> $url,
		'content'	=> $content
	];
}

function submitPost(){
	global $post;

	$frontEndContent	= new FrontEndContent();
	$result 			= $frontEndContent->submitPost();

	if(is_wp_error($result)){
		return $result;
	}

	// Load the updated  post in the loop
	if($result['post']->post_type  == 'change'){
		$p	= $frontEndContent->oldPost->ID;
	}else{
		$p	= $frontEndContent->postId;
	}

	$posts	= new \WP_Query( array(
		'p'			=> $p,
		'post_type' => 'any'
	) );

	$GLOBALS['wp_query']= $posts;
	$post				= $posts->post;
	$GLOBALS['post']	= $post;

	if('page' == $post->post_type){
		$type = 'page';
	}else{
		$type = 'single';
	}
	// Find the the correct template
	$baseTemplate	= locate_template(["content-{$type}.php",'content.php']);
	$template 		= apply_filters( "content_template", $baseTemplate, 'content' );

	if(empty($template)){
		$html	= false;
	}else{
		// Get the html from the template
		ob_start();
		include_once($template);
		$html=ob_get_clean();
	}

	// Get the picture
	$result['picture']	= get_the_post_thumbnail_url($post->ID, 'full');

	$result['html'] = do_shortcode($html);

	if($post->post_status == 'pending'){
		$result['url']	= get_preview_post_link($post->ID);
	}else{
		$result['url']	= get_permalink($post->ID);
	}
	
	if($frontEndContent->update){
		do_action('wp_enqueue_scripts');
		ob_start();
		wp_print_scripts();
		print_footer_scripts();
		$result['js']	= ob_get_clean();

		do_action('wp_enqueue_style');
		ob_start();
		wp_print_styles();
		$result['css']	= ob_get_clean();
	}

	return $result;
}

function checkForDuplicate(\WP_REST_Request $request ){
	global $wpdb;

	$url			= TSJIPPY\ADMIN\getDefaultPageLink(PLUGINSLUG, 'front-end-post-pages');
	if(!$url){
		return;
	}

	$title	= $request->get_param('title');
	$type	= $request->get_param('type');
	$exclude= $request->get_param('exclude');

	$query	= "SELECT * FROM {$wpdb->prefix}posts WHERE post_title LIKE %s AND post_type = %s";

	if(is_numeric($exclude)){
		$query	.= " AND ID != %d";
	}
	$posts 	= $wpdb->get_results($wpdb->prepare($query, ["%$title%", $type, $exclude]), OBJECT);

	$html	= "";

	// check for exact match
	$found	= false;
	foreach($posts as $post){
		if($post->post_title != $title){
			continue;
		}

		$html	.= "A post with title '$title' already exists.<br>";
		$url1		= get_permalink($post);
		$url2		= add_query_arg( ['post-id' => $post->ID], $url );
		$html		.= "See it <a href='$url1'>here</a>, or edit it <a href='$url2'>here</a>";

		$found	= true;
		break;
	}

	// no exact match found
	if(!empty($posts) && !$found){
		if(count($posts) == 1){
			$existingTitle	= $posts[0]->post_title;
			$html	.= "A post with a similar title '$existingTitle' already exists.<br>";

			$url1		= get_permalink($posts[0]);
			$url2		= add_query_arg( ['post-id' => $posts[0]->ID], $url );
			$html		.= "See it <a href='$url1'>here</a>, or edit it <a href='$url2'>here</a>";
			
		}elseif( count($posts) > 1){
			$count	= count($posts);
			$html	.= "$count posts with a similar title already exist:<br>";

			foreach($posts as $post){
				$url1		= get_permalink($post);
				$url2		= add_query_arg( ['post-id' => $post->ID], $url );
				$html		.= "$post->post_title <a href='$url1'>view</a>, or <a href='$url2'>edit</a><br>";
			}

		}
	}

	if(empty($html)){
		return '';
	}
	
	return [
		'warning'	=> $html,
		'html'		=> "<div class='warning' id='post-title-warning'>$html</div>"
	];
}