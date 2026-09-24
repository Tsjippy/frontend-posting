import { addStyles } from "@tsjippy/load_assets";

import{
  fetchRestApi
} from "@tsjippy/form_submit_functions";

import { 
  showLoader 
} from "@tsjippy/show_loader";

import { 
  hideModals 
} from "@tsjippy/modals";

console.log("Edit post.js loaded");

let editPostSwitch = async function (event) {
  let button = event.target;
  let wrapper = button.closest("div").querySelector(".content-wrapper");
  let insertLocation = wrapper.parentNode;

  let formData = new FormData();
  let postId = button.dataset.postId;
  formData.append("post-id", postId);

  const data   = JSON.parse(
    document.getElementById(
        'wp-script-module-data-@tsjippy/library_cat_script'
    ).textContent
  );

  const url = new URL(data.url);
  url.searchParams.set("post-id", postId);

  window.history.pushState({}, "", url);

  let loader = showLoader(wrapper, true, 50, "Requesting form...");

  let response = await fetchRestApi(
    "frontend_posting/post_edit",
    formData,
  );

  if (response) {
    // Close any modals to restore scrolling
    hideModals();

    let div = document.createElement("div");
    div.classList.add("content-wrapper");
    div.innerHTML = response.html;

    // remove previous content
    document.querySelectorAll(".content-wrapper").forEach((el) => el.remove());

    insertLocation.appendChild(div);

    addStyles(response, document); // runs also the afterScriptsLoaded function

    loader.remove();
  } else {
    loader.outerHTML = button.outerHTML;
  }
};

document.addEventListener("DOMContentLoaded", function () {
  document.querySelectorAll(".page-edit.hidden").forEach((el) => {
    el.classList.remove("hidden");
  });
});

document.addEventListener("click", function (ev) {
  if (ev.target.matches(".page-edit")) {
    ev.stopPropagation();
    editPostSwitch(ev);
  }
});

// after scripts have been loaded over AJAX
document.addEventListener("scriptsloaded", function () {
  document
    .querySelectorAll("#frontend-upload-form")
    .forEach((el) => el.classList.remove("hidden"));

  document
    .querySelectorAll(".content-wrapper")
    .forEach((el) => el.classList.remove("hidden"));
  document
    .querySelectorAll(".loader-wrapper:not(.hidden)")
    .forEach((el) => el.remove());
});
