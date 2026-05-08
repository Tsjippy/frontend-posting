<?php
namespace TSJIPPY\FRONTENDPOSTING;
use TSJIPPY;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// filter library if needed
add_filter( 'ajax_query_attachments_args',  __NAMESPACE__.'\attachmentArgs');
function attachmentArgs($query){
    if(!empty($_REQUEST['query']['category'])){
        $category = $_REQUEST['query']['category'];

		$query['tax_query'] = array(
			array (
				'taxonomy' 	=> 'attachment_cat',
				'field' 	=> 'slug',
				'terms' 	=> $category ,
			)
		);
    }

    return $query;
}

add_action( 'init', __NAMESPACE__.'\initTaxonomies');
function initTaxonomies() {

    $taxonomies = array( 'category', 'post_tag' ); // add the 2 tax to ...
    foreach ( $taxonomies as $tax ) {
		register_taxonomy_for_object_type( $tax, 'page' );
    }

	TSJIPPY\createTaxonomies('attachment_cat', 'attachment', 'attachments');
}

/**
 * Add categories to attachment page
 *
 * WP expects a comma seperated list of cat slugs, so
 * we create a checkbox who update a hidden input wiht the comma seperated checkboxes
 */

add_filter( 'attachment_fields_to_edit', __NAMESPACE__.'\attachmentFieldsToEdit', 10, 2);
function attachmentFieldsToEdit($formFields, $post ){
    $categories	= get_categories( array(
		'orderby' 		=> 'name',
		'order'   		=> 'ASC',
		'taxonomy'		=> 'attachment',
		'hide_empty' 	=> false,
	) );

	$checkboxes		= '';
	$catNames			= '';
	foreach($categories as $category){
		$name 				= str_replace('-', ' ', ucfirst($category->slug));
		$catId 				= $category->cat_ID;
		$checked			= '';
		$taxonomy			= $category->taxonomy;
		
		//if this cat belongs to this post
		if(has_term($catId, $taxonomy, $post->ID)){
			$checked 	 = 'checked';
			if(!empty($catNames)){
				$catNames	.= ',';
			}
			$catNames		.= $category->slug;
		}

		$checkboxes	.= "<label>";
			$checkboxes	.= "<input $checked style='width: initial' type='checkbox' class='attachment-cat-checkbox' value='{$category->slug}' onchange='attachmentChanged(this)'>";
			$checkboxes	.= $name;
		$checkboxes	.= "</label><br>";
	}

    $html   = "<div class='attachment-cat-wrapper'>";
		$html	.= "<script>";
			$html	.= "function attachmentChanged(element){";
				$html	.= "let val		= element.value;";
				$html	.= "let catEl	= document.getElementById('attachments[{$post->ID}][attachment-cat]');";
				$html	.= "if(element.checked){";
					$html	.= "catEl.value	= catEl.value + ','+val;";
				$html	.= "} else{";
					$html	.= "catEl.value	= catEl.value.replace(','+val, '').replace(val, '');";
				$html	.= "}";
			$html	.= "}";
		$html	.= "</script>";
		$html	.= "<input type='hidden' class='no-reset' name='attachments[{$post->ID}][attachment-cat]' id='attachments[{$post->ID}][attachment-cat]' value='$catNames'>";
        $html   .= $checkboxes;
    $html   .= "</div>";

    $formFields['attachment-cat']['input']	= 'html';
    $formFields['attachment-cat']['html']	= $html;
	$formFields['attachment-cat']['label']	= 'Categories';

    return $formFields;
}

add_action('tsjippy_before_archive', __NAMESPACE__.'\beforeArchive');
function beforeArchive($type){
    $url			= get_permalink(SETTINGS['front-end-post-page'] ?? '');
	if(is_numeric($url)){
		if($type == 'event'){
			$text	= "Add an event to the calendar";
		}else{
			$text	= "Add a new $type";
		}

		echo "<a href='$url?type=$type' class='button'>$text</a><br>";
	}
}

add_filter('tsjippy_empty_description', __NAMESPACE__.'\emptyDescription', 10, 2);
function emptyDescription($message, $post){
    $url			= get_permalink(SETTINGS['front-end-post-page'] ?? '');
	if(!$url){
		$url	= '';
	}
	$message	= "<div style='margin-top:10px;'>";
		$message	.= "This {$post->post_type} lacks a description.<br>";
		$message	.= "Please add one.<br>";
		$message	.= "<a href='$url?post-id={$post->ID}' class='button'>Add description</a>";
	$message	.= '</div>';

	return $message;
}

add_filter('tsjippy-empty-taxonomy', __NAMESPACE__.'\emptyTax', 10, 2);
function emptyTax($message, $type){
	$url			= get_permalink(SETTINGS['front-end-post-page'] ?? '');
	$message	.= "<br><a href='$url?type=$type' class='button'>Add a $type</a>";
	return $message;
}