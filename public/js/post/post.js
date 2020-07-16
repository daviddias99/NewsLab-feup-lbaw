// Reports
let reportForm = document.querySelector("#report-modal")

let reasons = null
let explanation = reportForm.querySelector("textarea")
reportForm.querySelector(".modal-footer button[type='submit']").addEventListener('click', function(event){
    let pError =  reportForm.querySelector("p")
    let id = reportForm.querySelector("#reported-id").value
    reasons = reportForm.querySelectorAll(".form-check-input:checked")
    explanationVal = explanation.value

    if(!validPositiveInt(id)){
        addFloatingAlert("Invalid content id. Please consider reloading the page")
        $('#report-modal').modal('hide');
        return
    }

    if(reasons.length < 1 || reasons.length > 3){
        pError.innerText = "You should select between 1 and 3 reasons"
        pError.classList.add("text-primary")
        pError.classList.remove("text-muted")
        return
    }

    if(!validShortText(explanationVal) || explanationVal.length < 6){
        pError.innerText = "Explanation must have between 6 and 500 letters, numbers or symbols like ?+*_!#$%,\/;.&-"
        pError.classList.add("text-primary")
        pError.classList.remove("text-muted")
        return
    }
    else{
        pError.innerText = "* Required Input"
        pError.classList.remove("text-primary")
        pError.classList.add("text-muted")
    }

    let serialReasons = ""
    for(let reason of reasons){
        if(parseInt(reason.value) > 6 || parseInt(reason.value) < 1){
            pError.innerText = "Invalid reason value"
            pError.classList.add("text-primary")
            pError.classList.remove("text-muted")
            return;
        }
        serialReasons += reason.value + ","
    }

    sendAjaxRequest('POST', `/api/contents/${id}/report`, {reasons:serialReasons.slice(0, -1), explanation:explanationVal}, 'application/json', reportHandler, event)
})

function reportHandler(res, _event){
    let pError =  reportForm.querySelector("p")

    switch (res.status) {
        case 400:
            addFloatingAlert("Something went wrong, could not make your report");
            res.json().then((data) => {
                pError.classList.add("text-primary")
                pError.classList.remove("text-muted")
                for (const key in data['errors'])
                    if (data['errors'].hasOwnProperty(key)) {
                        pError.innerText =  data['errors'][key];
                        break;
                }
            })

            break;
        case 401:
            addFloatingAlert("Something went wrong, we were not able to report this content. Make sure you are logged in.");
            $('#report-modal').modal('hide'); 
            break;
        case 403:
            addFloatingAlert("Something went wrong, you are not allowed to report this content.");
            $('#report-modal').modal('hide'); 
            break;
        case 404:
            addFloatingAlert("Something went wrong, we could not find this content");
            $('#report-modal').modal('hide'); 
            break;
        case 409:
            addFloatingAlert("You already reported this content");
            pError.innerText = "* Required Input"
            pError.classList.remove("text-primary")
            pError.classList.add("text-muted")
            $('#report-modal').modal('hide');
            explanation.value = ""
            for(let reason of reasons)
                reason.checked = false
            break;
        case 200:
            addFloatingAlert("Content reported. Way to go!");
            pError.innerText = "* Required Input"
            pError.classList.remove("text-primary")
            pError.classList.add("text-muted")
            $('#report-modal').modal('hide');
            explanation.value = ""
            for(let reason of reasons)
                reason.checked = false

            break;
        default:
            addFloatingAlert("Something went wrong, we could not report this comment");
            break;
    }
}

// Comments Counter
function incrementNumberHeader(){
    let h2 = document.querySelector("#comments h2")
    let text = h2.innerHTML
    let nComments = parseInt(text)
    nComments += 1
    h2.innerHTML = nComments
    if (nComments == 1)
        h2.innerHTML += " Comment"
    else
        h2.innerHTML += " Comments"
}

function decrementNumberHeader(){
    let h2 = document.querySelector("#comments h2")
    let text = h2.innerHTML
    let nComments = parseInt(text)
    nComments -= 1
    h2.innerHTML = nComments
    if (nComments == 1)
        h2.innerHTML += " Comment"
    else
        h2.innerHTML += " Comments"
}

// Edit Form
function openComment(event){
    event.preventDefault();
    let id = event.target.getAttribute('href')
    let pBody = document.getElementById('comment-body-' + id)
    pBody.classList.add('d-none')
    
    let divTextarea = pBody.nextElementSibling
    divTextarea.firstElementChild.value = pBody.innerText
    divTextarea.classList.remove('d-none')

    let editOption = document.getElementById('edit-' + id)
    editOption.classList.remove("d-none")

    let otherOptions = document.getElementById('options-' + id)
    otherOptions.classList.add('d-none')
}

function cancelEdit(event){
    event.preventDefault()
    let id = event.currentTarget.dataset.cid
    let pBody = document.getElementById('comment-body-' + id)
    pBody.classList.remove('d-none')
    
    let divTextarea = pBody.nextElementSibling
    divTextarea.firstElementChild.value = pBody.innerText
    divTextarea.classList.add('d-none')

    let editOption = document.getElementById('edit-' + id)
    editOption.classList.add("d-none")

    let otherOptions = document.getElementById('options-' + id)
    otherOptions.classList.remove('d-none')
}

function setEditListeners(event){
    let id = this.dataset.cid
    let is_comment = (document.querySelector(`div[data-cid="${id}"]`) != null)
    if(is_comment)
        editComment(event, id)
    else
        editReply(event, id)
}

function autoExpand(textarea) {
	textarea.style.height = 'inherit';

	// Get the computed styles for the element
	var computed = window.getComputedStyle(textarea);

	// Calculate the height
	var height = parseInt(computed.getPropertyValue('border-top-width'), 10)
	             + parseInt(computed.getPropertyValue('padding-top'), 10)
	             + textarea.scrollHeight
	             + parseInt(computed.getPropertyValue('padding-bottom'), 10)
	             + parseInt(computed.getPropertyValue('border-bottom-width'), 10);

	textarea.style.height = height + 'px';
}

// Delete
let delete_post = document.querySelector(".delete-post")
if(delete_post != null)
    delete_post.addEventListener("click", function(event){
        sendAjaxRequest('DELETE', this.getAttribute('href'), null, 'application/json', deletePostHandler, event)
    })

function deletePostHandler(res, event){
    switch (res.status) {
        case 200:
            addFloatingAlert("Post deleted successfully");
            // Simulate an HTTP redirect:
            window.location.replace("/home");
            break;
        default:
            addFloatingAlert("Something went wrong, could not delete post.");
            break;
    }
}

let savePostButton = document.getElementById("save-post-button")
savePostButton.addEventListener("click", savePost)

function savePost(event){
    event.preventDefault()

    let post_id = document.querySelector("#blog-main > .blog-post").getAttribute("data-id");
    if(!validPositiveInt(post_id)){
        addFloatingAlert("Save abort: Invalid post id. Please consider reloading the page")
        return
    }

    let user_id = event.target.parentElement.getAttribute("data-id");
    if(!validPositiveInt(user_id)){
        addFloatingAlert("Save abort: Invalid user id. Please consider reloading the page")
        return
    }

    fetch(`/api/users/${user_id}/saved_posts`, {
        method: 'POST',
        body: JSON.stringify({
            id: post_id
        }),
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
            'Accept': 'application/json'
        }
    })
    . then((res)=>{
        switch (res.status) {
            case 200:
                addFloatingAlert("Post added successfully to saved posts");   
                break;
            case 400:
                res.json().then((data) => {
                    if(data.message == "Already saved") {
                        addFloatingAlert("Post is already saved");
                    }
                    else {
                        addFloatingAlert("Something went wrong, the post could not be added to saved posts");
                    }
                })
                break;
            default:
                addFloatingAlert("Something went wrong, the post could not be added to saved posts");
                break;
        }  
    });
}

// Change post privacy

let vis_button = document.getElementById('visibility_btn')

if(vis_button != null)
    vis_button.addEventListener('click',changePostVis)

function changePostVis(event){
    event.preventDefault();
    let vis = event.srcElement.classList.contains("set_private") ? "false" : "true";
    sendAjaxRequest('POST',event.srcElement.getAttribute('href'),{'visibility': vis}, 'application/json',privacyChangeHandler)
}

async function privacyChangeHandler(res, event){

    if(res.status == 200){
        data = await res.json()
        addFloatingAlert("Visibility changed to " + data.visibility + ".");
        if(data.visibility == 'public'){
            vis_button.classList.remove("set_public")
            vis_button.classList.add("set_private")
            vis_button.innerHTML = "Make Post Private";

        }
        else{
            vis_button.classList.remove("set_private")
            vis_button.classList.add("set_public")
            vis_button.innerHTML = "Make Post Public";
        }
    }
    else {
        addFloatingAlert("Could not change visibility.");
    }

}


function updateListeners(){
    updateCommentListeners()
    updateReplyListeners()

    let reportButtons = document.querySelectorAll("a[data-target='#report-modal']")
    for(let reportButton of reportButtons)
        reportButton.addEventListener('click', function(event){
            event.preventDefault()
            reportForm.querySelector("#reported-id").value = reportButton.getAttribute('href')
        })

    let cancelEditButtons = document.getElementsByClassName("cancel-edit")
    for(let cancelEditButton of cancelEditButtons)
        cancelEditButton.addEventListener('click', cancelEdit)
    
    let submitEditButtons = document.getElementsByClassName("submit-edit")
    for(let submitEditButton of submitEditButtons)
        submitEditButton.addEventListener('click', setEditListeners)
    
    let autoTextAreas = document.getElementsByClassName("autoExpand")
    for(let autoTextArea of autoTextAreas)
        autoTextArea.addEventListener('input', function(event){
            autoExpand(event.currentTarget)
        })
}

updateListeners()