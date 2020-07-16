// Add
function sendAddReply(event){
    let pError = document.querySelector(".error-info")
    let body = this.querySelector("textarea")
    if(!validShortText(body.value)){
        event.preventDefault()
        addFloatingAlert("Something went wrong. Reply body has invalid characters...")
        pError.classList.add("text-primary")
        pError.classList.remove("text-muted")
        pError.innerText = "Reply must have between 1 and 500 letters, numbers or symbols like ?+*_!#$%,\/;.&-"
        return
    }

    pError.innerText = "* Required Input"
    pError.classList.remove("text-primary")
    pError.classList.add("text-muted")

    let comment_id = this.parentElement.parentElement.getAttribute("data-cid")
    if(!validPositiveInt(comment_id)){
        event.preventDefault()
        addFloatingAlert("Invalid comment id. Please consider reloading the page")
        return
    }

    sendAjaxRequest('POST', `/api/comments/${comment_id}/reply`, {body: body.value}, 'application/json', addReplyHandler, event)
}

function addReplyHandler(res, event){
    switch (res.status) {
        case 400:
            addFloatingAlert("Something went wrong, could not post your reply.");
            res.json().then((data) => {
                addErrorToCommentForm(data['errors'],event.target);
            })

            break;
        case 401:
            addFloatingAlert("Something went wrong, we were not able to post your reply. Make sure you are logged in.");
            break;
        case 403:
            addFloatingAlert("Something went wrong, you are not allowed to reply to this comment.");
            break;
        case 404:
            addFloatingAlert("Something went wrong, we could not find reply to this comment");
            break;
        case 201:
            addFloatingAlert("Comment added. Way to go!");
            event.target.querySelector("textarea").value = ""
            let comment_id = event.target.parentElement.parentElement.getAttribute("data-cid")

            $(`#reply${comment_id}-collapse`).collapse('hide')
            res.json().then((data) => {
                createReply(data.reply, comment_id, event.target.parentElement)
            })

            let errorInfo = event.target.getElementsByClassName("error-info");

            for (let index = 0; index < errorInfo.length; index++)
                errorInfo[index].innerText = "* Required Input";

            break;
        default:
            addFloatingAlert("Something went wrong, we could not find reply to this comment");
            break;
    }
}

// Create
function createReply(reply, comment_id, form){
    let temp = document.createElement('article');
    temp.innerHTML = replyHTML(reply, comment_id);
    form.parentElement.insertBefore(temp.firstElementChild, form)

    incrementNumberHeader()
    document.querySelector(`article[data-cid="${reply.id}"] .delete-reply`).addEventListener('click', sendDeleteReply)
    addReplyEditListener(reply.id)

    // document.getElementById(`reply${id}-collapse`).addEventListener('submit', newReplyAPI)

    let upVote = document.getElementById(`inlineRadio${reply.id}`)
    let downVote = document.getElementById(`inlineRadio-${reply.id}`)

    let content_id = upVote.getAttribute("name").split("-")[1]
    upVote.addEventListener("click", function (event) {
        event.preventDefault()
        rateContent(upVote, content_id, true)
    })

    downVote.addEventListener("click", function (event) {
        event.preventDefault()
        rateContent(downVote, content_id, false)
    })
}

function createEditReply(id){
    let body_val = document.getElementById(`edit-body-${id}`).value
    let pBody = document.getElementById(`comment-body-${id}`)
    pBody.innerText = body_val
    pBody.classList.remove('d-none')

    let divTextarea = pBody.nextElementSibling
    divTextarea.firstElementChild.value = pBody.innerText
    divTextarea.classList.add('d-none')

    let editOption = document.getElementById('edit-' + id)
    editOption.classList.add("d-none")

    let otherOptions = document.getElementById('options-' + id)
    otherOptions.classList.remove('d-none')

    let p = document.getElementById(`edit-${id}`).previousElementSibling
    if(p.firstElementChild == null)
        p.innerHTML = p.innerHTML + ` <a href="/replies/${id}/versions" class="font-italic text-muted">(Edited)</a>`
}

// Delete
function sendDeleteReply(event){
    sendAjaxRequest('DELETE', this.getAttribute('href'), null, 'application/json', deleteReplyHandler, event)
}

function deleteReplyHandler(res, event){
    switch (res.status) {
        case 200:
            addFloatingAlert("Comment deleted successfully");
            res.json().then((data) => {
                let id = data.id
                let temp = document.querySelector(`article[data-cid="${id}"]`)
                let other_comment = temp.nextElementSibling.classList.contains("reply-area");
                other_comment = (!other_comment) ? temp.previousElementSibling.classList.contains("reply-area") : other_comment;


                let section = temp.parentElement;
                temp.remove()
                let deleted = section.querySelector(".comment-area.deleted")

                if(deleted != null && !other_comment){
                    section.remove()
                    decrementNumberHeader()
                }
            })
            decrementNumberHeader()
            break;
        default:
            addFloatingAlert("Something went wrong, could not delete reply.");
            break;
    }
}

// Edit
function addReplyEditListener(id){
    document.querySelector(`article[data-cid="${id}"] .edit-reply`).addEventListener('click', openComment)
    let edit = document.getElementById(`edit-${id}`)
    edit.querySelector('.cancel-edit').addEventListener('click', cancelEdit)
    edit.parentElement.parentElement.querySelector('.autoExpand').addEventListener('input', function(event){
        autoExpand(event.currentTarget)
    })
    edit.firstElementChild.addEventListener('click', function(event){editReply(event, id)})
}

function editReplyHandler(res, event){
    switch (res.status) {
        case 400:
            addFloatingAlert("Something went wrong, could not edit your reply.");
            res.json().then((data) => {
                let button = event.target
                if(event.target.tagName == "I")
                    button = button.parentElement
                document.getElementById("error-info-" + button.dataset.cid).innerText = data['errors']['body']

            })
            break;
        case 401:
            addFloatingAlert("Something went wrong, we were not able to edit your reply. Make sure you are logged in.");
            break;
        case 403:
            addFloatingAlert("Something went wrong, you are not allowed to edit this reply");
            break;
        case 404:
            addFloatingAlert("Something went wrong, we could not find the reply you are trying to edit");
            break;
        case 200:
            addFloatingAlert("Reply Edited. Way to go!");

            res.json().then((data) => {
                createEditReply(data.id)
                document.getElementById("error-info-" + data.id).innerText = ""
            })

            break;
        default:
            addFloatingAlert("Something went wrong, could not post your reply.");
            break;
    }
}

function editReply(event, id){
    let body = document.getElementById(`edit-body-${id}`)
    if(!validPositiveInt(id) || body == null){
        addFloatingAlert("Invalid reply id. Please consider reloading the page")
        event.preventDefault()
        return
    }

    let pError =  document.getElementById(`error-info-${id}`)
    if(!validShortText(body.value)){
        addFloatingAlert("Something went wrong. Reply body has invalid characters...")
        pError.innerText = "Reply must have between 1 and 500 letters, numbers or symbols like ?+*_!#$%,\/;.&-"
        event.preventDefault()
        return
    }
    pError.innerText = ""
    
    sendAjaxRequest('PUT', `/api/replies/${id}`, {body: body.value}, 'application/json', editReplyHandler, event)
}

// HTML
function replyHTML(reply, comment_id){
    let a = `<article class="reply-area ml-5 mb-3 border-left px-3" data-cid="${reply.id}">
                <div class="card border-0 pb-1">
                    <div class="row no-gutters">
                        <div class="col-2 col-sm-1 col-md-2 col-lg-1">`
    let b
    if(reply.author.photo !== null)
        b = `<a href="/users/${reply.author.id}}">
                    <div class="square rounded-circle" style="background-image: url('/storage/images/users/${reply.author.photo}')"></div>
            </a>`
    else
        b = `<a href="/users/${reply.author.id}}">
                <div class="square rounded-circle" style="background-image: url('/storage/images/users/default.png')"></div>
            </a>`

    let c =`</div>
            <div class="col-10 col-sm-11 col-md-10 col-lg-11 d-flex justify-content-between align-items-start">
                <div class="d-flex flex-column flex-fill">
                    <div class="card-body py-1 d-flex flex-column flex-sm-row align-items-sm-center">`
    let d
    if (reply.author.verified)
        d = `<h5 class="card-title my-1"><a href="/users/${reply.author.id}" class="text-dark mr-2">${reply.author.name}  <i class="fas fa-check-circle"></i></a></h5>`
    else
        d = `<h5 class="card-title my-1"><a href="/users/${reply.author.id}" class="text-dark mr-2">${reply.author.name}</a></h5>`
    let e = reply.edited ? `<p class="card-text small text-muted pl-2 my-auto border-left">` + moment(reply.publication_date).fromNow() + ` <a href="/replies/${reply.id}/versions" class="font-italic text-muted">(Edited)</a></p>` :
             `<p class="card-text small text-muted pl-2 my-auto border-left">` + moment(reply.publication_date).fromNow() + ` </p>`

    let f = ` <div id="edit-${reply.id}" class="d-none ml-auto">
                <button class="btn btn-sm btn-primary hover-bold px-4 submit-edit" data-cid="${reply.id}" type="submit">
                    <i class="fas fa-paper-plane"></i>
                </button>
                <button class="btn btn-sm btn-outline-secondary mx-2 cancel-edit" data-cid="${reply.id}" >
                    <i class="fa fa-times"></i>
                </button>
            </div>
        </div>
        <p id="comment-body-${reply.id}" class="px-0 px-sm-3 py-2 m-0 ml-sm-n3">${reply.body}</p>
        <div class="px-0 px-sm-3 py-2 m-0 ml-sm-n3 d-none">
            <textarea id="edit-body-${reply.id}" class="form-control autoExpand border-primary border-right-0 border-top-0 border-left-0 p-0" data-min-rows='1' rows="1" ></textarea>
            <p id="error-info-${reply.id}" class="small text-primary mb-0"></p>
        </div>
        </div>
            <div id="options-${reply.id}" class="py-1 flex-shrink-0">
                <div class="d-flex flex-row-reverse">
                    <div class="dropdown">
                        <button class="btn  btn-sm bg-white" type="button" id="dropdownMenuButton${reply.id}" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <i class="fas fa-ellipsis-h"></i>
                        </button>
                        <div class="dropdown-menu" aria-labelledby="dropdownMenuButton${reply.id}">
                            <a class="edit-reply dropdown-item" href="${reply.id}">Edit Reply</a>
                            <a class="delete-reply dropdown-item" href="/api/replies/${reply.id}">Delete Reply</a>
                        </div>
                    </div>
                    <button class="btn btn-sm bg-white reply" type="button" data-toggle="collapse" data-target="#reply${comment_id}-collapse" aria-expanded="false" >
                        <i class="fa fa-reply"></i>
                    </button>
                </div>

                        <div class="likes-row row mx-0">
                            <div class="form-check form-check-inline ml-auto">
                                <input class="form-check-input check-rate" type="radio" name="inlineRadioOptions-${reply.id}}" id="inlineRadio${reply.id}" value="like">
                                <label class="form-check-label" for="inlineRadio${reply.id}"><i class="fas fa-angle-up"></i></label>
                            </div>
                        <p class="mb-0 pr-sm-3">${reply.likes_difference} </p>
                            <div class="form-check form-check-inline ml-auto">
                                <input class="form-check-input check-rate" type="radio" name="inlineRadioOptions-${reply.id}" id="inlineRadio-${reply.id}" value="dislike">
                                <label class="form-check-label" for="inlineRadio-${reply.id}"><i class="fas fa-angle-down"></i></label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </article>`
    return a + b + c + d + e + f
}

function updateReplyListeners(){
    let replyForms = document.getElementsByClassName("reply-form")
    for(let replyForm of replyForms)
        replyForm.addEventListener('submit', sendAddReply)
    
    let replyDelButtons = document.getElementsByClassName("delete-reply")
    for(let replyDelButton of replyDelButtons)
        replyDelButton.addEventListener('click', sendDeleteReply)

    let replyEditButtons = document.getElementsByClassName("edit-reply")
    for(let replyEditButton of replyEditButtons)
        replyEditButton.addEventListener('click', openComment)
}