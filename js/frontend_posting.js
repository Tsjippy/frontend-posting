import './../../tsjippy-locations/js/user_location.js';
import { addStyles } from '../../tsjippy-shared-functionality/includes/js/partials/load_assets.js';

console.log("Frontendposting.js loaded");

async function confirmPostDelete( event, type='delete' ) {
	let url;
	let target	= event.target;
	event.preventDefault();
	parent 		= target.closest('#frontend-upload-form');

	let options	= {
		title: `Are you sure?`,
		ConfirmButtonText: `Yes, ${type} it!`
	};

	let response = await new Main.Alert(`Are you sure you want to ${type} this ${document.querySelector('[name="post-type"]').value}?`, 'warning', options);

	if (response == 'confirm') {
		let postId 			= target.dataset.postId;
		
		let buttonText 		= target.innerHTML;

		// Show loader in button
		let text			= buttonText.split(' ')[0];
		text				= text.substring(0, text.length - 1)+'ing...';
		target.innerHTML	= Main.showLoader(null, false, 20, text, true, true);
	
		// Submit the delete request
		var formData = new FormData();
		formData.append('post-id', postId);

		if(type == 'delete'){
			url 	= 'frontend_posting/remove_post'
		}else{
			url 	= 'frontend_posting/archive_post'
		}
		
		response = await FormSubmit.fetchRestApi(url, formData);

		if(response){
			Main.displayMessage(response);
		}

		// restore button text
		target.innerHTML	= buttonText;	
	}
}

async function refreshPostLock(){
	var postId = document.querySelector('[name="post-id"]');

	if(postId != null && postId.value != ''){
		var formData	= new FormData();
		formData.append('post-id', postId.value);
		FormSubmit.fetchRestApi('frontend_posting/refresh_post_lock', formData);
	}
}

async function deletePostLock(){
	var postId = document.querySelector('[name="post-id"]');

	if(postId != null && postId.value != ''){
		var formData	= new FormData();
		formData.append('post-id', postId.value);
		await FormSubmit.fetchRestApi('frontend_posting/delete_post_lock', formData);
	}
}

// Strores a change of post type on the server
async function changePostType(target){
	var response = await FormSubmit.submitForm(target, 'frontend_posting/change_post_type');

	if(response){
		Main.displayMessage(response);
	}
}

// Switches the available fields on post type change
function switchforms(target){
	var postType;
	var parent 			= document.getElementById('frontend-upload-form');

	if(target == null){
		postType		= location.search.replace('?type=', '');
	}else{
		postType 		= target.value;
	}
	
	document.querySelector('#postform [name="post-type"]').value 	= postType;
	
	//Change button text
	parent.querySelectorAll('.replace-post-type').forEach(function(el){
		el.textContent = postType;
	});
	
	//Show the lite elements if there is no content
	if(tinymce.activeEditor == null || tinymce.activeEditor.getContent() == ""){
		parent.querySelectorAll('.lite').forEach(function(el){
			el.classList.remove('hidden');
		});
		
		//show the button to show all fields again
		parent.querySelector('#show-all-fields').classList.add('hidden');
	}

	parent.querySelectorAll('#wp-post-content-media-buttons, .advanced-publish-options, #advanced-publish-options-button').forEach(el=>el.classList.remove('hidden'));
	
	//Show all page options
	parent.querySelectorAll(`.property.${postType}`).forEach(el=>el.classList.remove('hidden'));

	//Hide other options
	parent.querySelectorAll(`.property:not(.${postType})`).forEach(el=>el.classList.add('hidden'));

	if(postType == 'attachment') {
		// tick the box to always include the url
		parent.querySelector('[name="signal-url"]').checked=true;
	}
}

function showAllFields(target){
	var parent = target.closest('#frontend-upload-form');
	if(parent.querySelector("#show-all-fields").classList.contains('show')){
		//Show the lite elements if the button is clicked
		parent.querySelectorAll('.lite').forEach(function(el){
			el.classList.remove('hidden');
		});
		
		target.classList.remove('show');
		target.classList.add('shouldhide');
		
		target.textContent = "Only show required fields" 
	}else{
		//Hide the lite elements if the button is clicked
		parent.querySelectorAll('.lite').forEach(function(el){
			el.classList.add('hidden');
		});
		
		target.classList.add('show');
		target.classList.remove('shouldhide');
		
		target.textContent = "Show all fields" 
	}
}

function addFeaturedImage(event) {
	event.preventDefault();
	
	var parent = event.target.closest('#frontend-upload-form');

	let file_frame = wp.media.frames.file_frame = wp.media({
		title: 'Select featured image' ,
		button: {
			text: 'Save featured image',
		},
		multiple: false
	});

	file_frame.on( 'select', function() {
		//Get the selected image
		var attachment = file_frame.state().get('selection').first().toJSON();
		
		//Store the id
		parent.querySelector('[name="post-image-id"]').value = attachment.id;
		
		//Show the image
	
		document.getElementById('featured-image-div').classList.remove('hidden');

		var imgdiv = document.getElementById('featured-image-wrapper');
		
		//If already an image set, remove it
		if(imgdiv.querySelector('img') != null){
			imgdiv.querySelector('img').remove()
		}
		
		imgdiv.insertAdjacentHTML('beforeEnd','<img width="150" height="150" src="'+attachment.url+'">');
		
		//Change button text
		parent.querySelectorAll('[name="add-featured-image"]' ).forEach(function(element){
			element.innerHTML = element.innerHTML.replace("Add",'Replace');
		});
	});

	file_frame.open();
}

function catChanged(target){
	var parentId = target.closest('.info-box').dataset.parent;
	
	var parentDiv = target.closest('.categories');
	
	if(target.checked){
		//An recipetype is just selected, find all element with its value as parent attribute
		parentDiv.querySelectorAll("[data-parent='"+target.value+"']").forEach(el=>{
			//Make this subcategory visible
			el.classList.remove('hidden');
			
			//Show the label
			parentDiv.querySelector('#subcategorylabel').classList.remove('hidden');
		});
		
	//If we just deselected a parent category
	}else if(parentId == undefined){
		//Hide the label if there is no category visible anymore
		if(parentDiv.querySelector('.childtypes input[type="checkbox"]:checked') == null){
			parentDiv.querySelector('#subcategorylabel').classList.add('hidden');
		
			//An recipetype is just deselected, find all element with its value as parent attribute
			parentDiv.querySelectorAll("[data-parent='"+target.value+"']").forEach(el=>{
				//Make this subcategory invisible
				el.classList.add('hidden');
			});
		}
	}
}

async function addCatType(target){
	let parentDiv, parentData;
	let response	= await FormSubmit.submitForm(target, 'frontend_posting/add_category');

	if(response){
		//Get the newly added category parent id
		let parentCat  	= target.closest('form').querySelector('[name="cat-parent"]').value;
		let postType	= target.closest('form').querySelector('[name="post-type"]').value;
		let catName		= target.closest('form').querySelector('[name="cat-name"]').value;
		
		//No parent category
		if(parentCat == ''){
			parentDiv		= document.getElementById(postType+'_parenttypes');
			parentData		= '';
		//There is a parent
		}else{
			parentDiv	 	= document.getElementById(postType+'_childtypes');
			parentData		= `data-parent="${parentCat}"`;
			
			//Select parent if it is not checked already
			let parent = document.querySelector(`.${postType}type[value="${parentCat}"]`);
			if(!parent.checked){
				parent.click();
			}
		}
		
		//Add the new category as checkbox
		let html = `
		<div class="info-box" ${parentData}>
			<input type="checkbox" class="${postType}type" id="${postType}type[]" value="${response.id}" checked>
			<label class="option-label category-select">${catName}</label>
		</div>
		`
		parentDiv.insertAdjacentHTML('afterBegin', html);
		Main.hideModals();

		Main.displayMessage(`Succesfully added the ${catName} category`);
	}
}

async function submitPost(target){
	// make sure we save the latest plain text edits
	tinymce.triggerSave(true, true);

	let response	= await FormSubmit.submitForm(target, 'frontend_posting/submit_post');
	
	if(response){
		// If no html found, reload the page
		if(!response.html || !response.js){
			let message = response.message || 'Post submitted successfully. Reloading page...';
			if(response.data != undefined){
				message = message + ' ' + response.data;
			}
			Main.displayMessage(message);

			location.href	= response.url;
			return;
		}

		// Update the url
		const url 		= new URL(response.url);
		window.history.pushState({}, '', url);

		let div	= document.getElementById('page-title-image');

		// Update the header image if there is one
		if(response.picture){
			if( div == null ){
				div	= document.createElement('div');
				div.id	= 'page-title-image';
			}
			div.style.backgroundImage	= `url("${response.picture}")`;
		}

		// Close any modals to restore scrolling
		Main.hideModals();

		// Replace the main content with the returned html
		document.querySelector('main').innerHTML	= response.html;

		addStyles(response, document);

		// Scroll page to top
		window.scrollTo(0, 0);

		document.querySelector('.page-edit').classList.remove('hidden');

		Main.displayMessage(response.message);
	}
}

//Retrieve file contents over AJAX
async function readFileContents(attachmentId){
	let formData	= new FormData();
	formData.append('attachment-id', attachmentId);

	let response	= await FormSubmit.fetchRestApi('frontend_posting/get_attachment_contents', formData);

	if(response){
		// Get current content
		let content	= tinyMCE.activeEditor.getContent();
		
		//Add contents to editor
		tinymce.activeEditor.setContent(content+response);
	}
}

/**
 * Checks every 0.1 second if wp.media.frame is available.
 * If available adds an on insert function
 */
async function insertMediaContents(){
	setTimeout(
		async function(){
			if(wp.media.frame == undefined){
				insertMediaContents();
				return;
			}
			wp.media.frame.on('insert', async function() {
				let selection = wp.media.frame.state().get('selection').first().toJSON(); 
				if (['.txt', '.doc', '.rtf'].some(v => selection.url.includes(v))) {
					let options	= {
						title: `Question`,
						ConfirmButtonText: 'Yes please',
						CancelButtonText: 'No thanks',
						timer: 3000
					};

					let html = Main.showLoader(null, false, 50, '', true);

					let response = await new Main.Alert(`Do you want to insert the contents of this file into the post?`, 'question', options);

					if (response == 'confirm') {
						let options	= {
							title: `Please wait...`
						};

						new Main.Alert(html, 'question', options);

						readFileContents(selection.id);
					}
				}
			});
		},
		100
	);
}

async function checkForDuplicate(target){
	document.querySelectorAll('#post-title-warning').forEach(el=>el.remove());

	if(target.value == ''){
		return;
	}

	let formData	= new FormData();
	formData.append('title', target.value);
	formData.append('type', target.closest('form').querySelector('[name="post-type"]').value);
	formData.append('exclude', target.closest('form').querySelector('[name="post-id"]').value);

	let response	= await FormSubmit.fetchRestApi('frontend_posting/check_duplicate', formData);

	if(response){
		target.insertAdjacentHTML('afterEnd', response['html']);
		Main.displayMessage(response['warning'], 'warning');
	}
}

document.addEventListener("DOMContentLoaded", function() {
	
	if(window['frontendLoaded'] == undefined){
		window['frontendLoaded']	= true;

		//Update minutes
		let minutes = document.querySelector("#minutes");
		if(minutes != null){
			setInterval(function() {
				minutes.innerHTML = parseInt(minutes.innerHTML)+1;
			}, 60000);
		}
		
		refreshPostLock();
		
		//Refresh the post lock every minute
		setInterval(refreshPostLock, 60000);
		
		// run js if we open a specific post type
		if(location.search.includes('?type') || location.search.includes('?post-id')){
			switchforms(document.querySelector('#postform [name="post-type"]'));
		}

		// Listen to insert media actions
		insertMediaContents();
	}
});

//Remove post lock when move away from the page
window.onbeforeunload = function(){
	if(document.getElementById('post_lock_warning') == null){
		deletePostLock();
	}
};

document.addEventListener("click", event =>{
	let target = event.target;

	if(target.name == 'submit_post' || (target.parentNode != null && target.parentNode.name == 'submit_post')){
		submitPost(target);
	}else if(target.name == 'draft-post' || (target.parentNode != null && target.parentNode.name == 'draft-post')){
		//change action value
		target.closest('form').querySelector('[name="post-status"]').value = 'draft';
		
		submitPost(target);
	}else if(target.name == 'publish-post'){
		//change action value
		target.closest('form').querySelector('[name="post-status"]').value = 'publish';
		
		submitPost(target);
	}else if(target.name == 'delete-post'){
		confirmPostDelete(event, 'delete');
	}else if(target.name == 'archive-post'){
		confirmPostDelete(event, 'archive');
	}else if(target.name == 'change-post-type'){
		changePostType(target);
	}else if(target.name == "add-featured-image"){
		addFeaturedImage(event);
	}else if(target.id == 'show-all-fields'){
		showAllFields(target);
	}else if(target.id == 'advanced-publish-options-button'){
		let div = target.closest('form').querySelector('.advanced-publish-options');
		if(div.classList.contains('hidden')){
			div.classList.remove('hidden');
			target.querySelector('span').textContent = 'Hide';
		}else{
			div.classList.add('hidden');
			target.querySelector('span').textContent = 'Show';
		}
	}
	
	else if(target.matches('.remove-featured-image')){
		document.querySelector('[name="post-image-id"]').value	= 0;
		
		let parent	= document.getElementById('featured-image-div');
		parent.classList.add('hidden');
		parent.querySelector('img').remove();

		document.querySelector('[name="add-featured-image"]').textContent = 'Add featured image';
	}
	
	// Show add category modal
	else if(target.classList.contains('add-cat')){
		document.getElementById('add-'+target.dataset.type+'-type').classList.remove('hidden');
	}

	else if(target.matches('.add-category .form-submit')){
		addCatType(target);
	}

	else if(target.matches('button.show-diff')){
		target.parentNode.querySelector('.post-diff-wrapper').classList.toggle('hidden');
	}else{
		return;
	}

	event.stopImmediatePropagation();
});

document.addEventListener('change', event=>{
	let target = event.target;

	//listen to change of post type
	if(target.id == 'post-type-selector'){
		switchforms(target);

		// title is not empty
		if(	target.closest('#frontend-upload-form').querySelector('[name="post-title"]').value != ''){
			checkForDuplicate(target.closest('#frontend-upload-form').querySelector('[name="post-title"]'));
		}
	}else if(target.name == 'post-title'){
		checkForDuplicate(target);
	}

	else if(target.list != null){
		//find the relevant datalist option
		let datalistOp = target.list.querySelector(`[value='${target.value}' i]`);
		if(datalistOp != null){
			let value = datalistOp.dataset.value;

			let valEl	= target.parentNode.querySelector('.datalistvalue');
			if(valEl != null){
				valEl.value	= value;
			}
		}
	}
	
	//listen for parent type select
	else if(target.classList.contains('parent_cat')){
		catChanged(target);
	}else{
		return;
	}

	event.stopImmediatePropagation();
});