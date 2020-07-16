let simplemde = new EasyMDE({
	element: document.getElementById("postBodyInput"),
	hideIcons: ["side-by-side", "fullscreen"],
	showIcons: ["code", "table"],
	spellChecker: true,
    renderingConfig: {
		sanitizerFunction: function(renderedHTML) {
			return DOMPurify.sanitize(renderedHTML)
		},
	},
});

$(function () {
	$('#scheduledDate').datetimepicker({
		minDate: moment(),
		buttons: {
			showToday: true,
			showClose: true,
			showClear: true
		},
		icons: {
			clear: 'fa fa-trash',
		},
	});
});


//////////////////////////////////////////////
let sbmtButton = document.querySelector("button[type='submit']")
sbmtButton.addEventListener("click", function (event) {
	event.preventDefault()

	let postType = document.querySelector("input[name='postType']:checked")
	let tags = document.getElementById("tagsInput")
	let title = document.getElementById("titleInput")
	let date = document.querySelector(".datetimepicker-input")
	let photo = document.getElementById("coverPic")
	let hasNewFile = photo.getAttribute('data-hasnewfile')

	let postTypeError = document.getElementById("postTypeError")
	let tagsError = document.getElementById("tagsError")
	let titleError = document.getElementById("titleError")
	let bodyError = document.getElementById("bodyError")
	let dateError = document.getElementById("dateError")
	let photoError = document.getElementById("photoError")


	postTypeError.innerText = ""
	tagsError.innerText = ""
	titleError.innerText = ""
	bodyError.innerText = ""
	dateError.innerText = ""
	photoError.innerText = ""

	let error = false
	if (postType == null) {
		postTypeError.innerText = "Please choose either news or opinion"
		error = true;
	}
	if (tags.value == "") {
		tagsError.innerText = "Please choose a minimum of 1 tag"
		error = true;
	}
	if (!validTitle(title.value)) {
		titleError.innerText = title.value == "" ? "Title can't be empty" : "Title must have between 3 and 100 letters, numbers or symbols like ?+*_!#$%,\/;.&-"
		error = true;
	}

	if (simplemde.value() == "" || simplemde.value().length < 16) {
		bodyError.innerText = "Body has to be at least 16 chars long"
		error = true;
	}

	if (date != null && date.value != "" && !validDateTime(date.value)) {
		dateError.innerText = "Invalid date - time format: mm/dd/yyy hh:mm AM/PM"
		error = true;
	}
	else if(date != null && date.value != ""  && moment(date.value) < moment().startOf('hour')){
		dateError.innerText = "Publication Date can't be in the past"
		error = true;
	}

	if (hasNewFile == "no") {
		photoError.innerText = "The post needs to have an image"
		error = true
	}

	if (error)
		return;

	let form = document.getElementById("post_editor")
	let formData = new FormData();
	formData.append('type', postType.value)
	formData.append('tags', tags.value)
	formData.append('title', title.value)
	formData.append('body', simplemde.value())

	if (!(photo.files[0] === undefined || photo.files[0] === null))
		formData.append('photo', photo.files[0])
	
	if(date != null)
		formData.append('date', date.value)
	formData.append('hasNewFile', hasNewFile)

	fetch(form.getAttribute('action'), {
		method: form.getAttribute('method'),
		body: formData,
		headers: {
			'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
			'Accept': 'application/json'
		}
	})
		.then((res) => {
			switch (res.status) {
				case 400:
					addFloatingAlert("Something went wrong, could not publish post due to invalid form data.");
					res.json().then((data) => {
						let errors = data.errors;

						Object.keys(errors).forEach(function (key) {
							if(key == "type")
								$("#postTypeError").text(errors[key][0]);
							else if (key == "hasNewFile")
								$("#photoError").text("The post needs to have an image");
							else
								$("#" + key + "Error").text(errors[key][0]);
						});
					})
					
					break;
				case 401:
					addFloatingAlert("Something went wrong, could not publish post. Please make sure you are logged in.");
					break;
				case 403:
					addFloatingAlert("Something went wrong, you are not allowed to publish this post.");
					break;
				case 404:
					addFloatingAlert("Something went wrong, we could not publish this post.");
					break;
				case 201:
					addFloatingAlert("Post created successfully.");
					res.json().then((data) => {
						window.location.replace(`/posts/${data.post.id}`);
					})
					break;
				case 200:
					addFloatingAlert("Post updated successfully.")
					res.json().then((data) => {
						window.location.replace(`/posts/${data.id}`);
					})
					break;
				case 413: // payload too large
					addFloatingAlert("An error occurred (possibly because the image is too large).");
					break;
				default:
					addFloatingAlert("An unknown error occurred. Please reload the page and try again later.");
					break;
			}
		});
})

const reader = new FileReader();
let bannerImage = document.getElementById("bannerImage")

let editImageInput = document.getElementById("coverPic")
editImageInput.addEventListener("change", function(event) {
	document.getElementById("photoError").innerText = ""
	const f = event.target.files[0]
    editImageInput.setAttribute('data-hasnewfile', "yes")
    reader.readAsDataURL(f)
})

reader.addEventListener('load', function(event) {
    bannerImage.style.backgroundImage = "url('" + event.target.result + "')"
})

let deleteImageButton = document.getElementById("deleteImageButton")
deleteImageButton.addEventListener("click", function(event) {
	event.preventDefault()
	document.getElementById("photoError").innerText = ""
	editImageInput.value = null
	editImageInput.setAttribute('data-hasnewfile', "no");
	bannerImage.style.backgroundImage = "url('" + editImageInput.getAttribute('data-defaultimgpath') + "')"
})