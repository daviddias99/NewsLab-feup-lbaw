// Comment Form animation
let commentDiv = document.getElementById("commentArea")
let commentForm = document.getElementById("comment-form")

commentDiv.addEventListener("focus", function(){
    commentForm.classList.add("expand")
})

commentDiv.addEventListener("focusout", function(){
    if(commentDiv.value == ""){
        commentForm.classList.remove("expand")
        let errorInfo = commentForm.getElementsByClassName("error-info");
        for (let index = 0; index < errorInfo.length; index++)
            errorInfo[index].remove();
    }
})

// Add
commentForm.addEventListener("submit", function(event){
    let body = commentForm.querySelector("textarea")
    let pError = document.getElementById("commentError")
    if(!validShortText(body.value)){
        event.preventDefault()
        addFloatingAlert("Something went wrong. Comment body has invalid characters...")
        pError.innerText = "Comment must have between 1 and 500 letters, numbers or symbols like ?+*_!#$%,\/;.&-"
        return
    }
    let post_id = document.querySelector("#blog-main article").getAttribute("data-id")
    if(!validPositiveInt(post_id)){
        event.preventDefault()
        addFloatingAlert("Invalid post id. Please consider reloading the page")
        return
    }

    pError.innerText = ""  
    sendAjaxRequest('POST', commentForm.getAttribute("action"), { body: body.value }, 'application/json', addCommentHandler, event)
})

function addCommentHandler(res, event){
    switch (res.status) {
        case 400:
            addFloatingAlert("Something went wrong, could not post your comment.");
            res.json().then((data) => {
                addErrorToCommentForm(data['errors'],commentForm);
            })
            break;
        case 401:
            addFloatingAlert("Something went wrong, we were not able to post your comment. Make sure you are logged in.");
            break;
        case 403:
            addFloatingAlert("Something went wrong, you are not allowed to comment on this post.");
            break;
        case 404:
            addFloatingAlert("Something went wrong, we could not find the comment you are trying to post.");
            break;
        case 201:
            addFloatingAlert("Comment added. Way to go!");
            commentForm.querySelector("textarea").value = ""
            commentForm.classList.remove("expand")
            res.json().then((data) => {
                let divOrder = document.querySelector("#comments .order")
                if(divOrder.firstElementChild.tagName == 'P')
                    divOrder.firstElementChild.remove()

                createComment(data.comment, false)

                let errorInfo = commentForm.getElementsByClassName("error-info");
                
                for (let index = 0; index < errorInfo.length; index++)
                    errorInfo[index].remove();
            })
                     
            break;
        default:
            addFloatingAlert("Something went wrong, could not post you comment.");
            break;
    }  
}

function addErrorToCommentForm(errors,target){
    let warning = target.getElementsByClassName("error-info");
    if(warning.length == 0){
        warning = document.createElement("p");
        setAttributes(warning,{"class": "error-info small text-muted mb-0"})
    }
    else
        warning = warning[0]

    for (const key in errors)
        if (errors.hasOwnProperty(key)) {
            warning.innerText =  errors[key];
            break;
        }

    target.appendChild(warning);
}

// Delete
function sendDeleteComment(event){
    sendAjaxRequest('DELETE', this.getAttribute('href'), null, 'application/json', deleteCommentHandler, event)
}

function deleteCommentHandler(res, event){
    switch (res.status) {
        case 200:
            res.json().then((data) => {
                let id = data.id
                let temp = document.querySelector(`div[data-cid="${id}"]`)
                if(temp.querySelector(".reply-area")){
                    createDeletedComment(id)
                    return
                }
                temp.remove();
                addFloatingAlert("Comment deleted successfully");
                decrementNumberHeader()
                let divOrder = document.querySelector("#comments .order")
                if(divOrder.firstElementChild == null)
                    divOrder.innerHTML = `<p class="text-muted font-italic text-center">There is no comment yet</p>`
            })    
            break;
        default:
            addFloatingAlert("Something went wrong, the comment could not be deleted sucessfuly");
            break;
    }
}

// Create
function createComment(comment){
    let id = comment.id
    let temp = document.createElement('div');
    temp.innerHTML = commentHTML(comment);
    let divOrder = document.querySelector("#comments .order")
    divOrder.insertBefore(temp.firstElementChild, divOrder.firstChild)
    incrementNumberHeader()
    document.querySelector(`div[data-cid="${id}"] .delete-comment`).addEventListener('click', sendDeleteComment)
    addCommentEditListener(id)
    document.getElementById(`reply${id}-collapse`).addEventListener('submit', sendAddReply)
    
    let upVote = document.getElementById(`inlineRadio${id}`)
    let downVote = document.getElementById(`inlineRadio-${id}`)

    upVote.addEventListener("click", function (event) {
        event.preventDefault()
        rateContent(upVote, comment.id, true)
    })

    downVote.addEventListener("click", function (event) {
        event.preventDefault()
        rateContent(downVote, comment.id, false)
    })

}

function createDeletedComment(id){
    let section = document.querySelector(`div[data-cid="${id}"]`)
    section.firstElementChild.classList.add("deleted")

    // Change image
    let img = section.querySelector('.square.rounded-circle')
    img.setAttribute("style", "background-image: url('/storage/images/users/default.png')")
    img.parentElement.removeAttribute('href')

    // Author 
    let author = section.querySelector("h5.card-title")
    author.innerHTML = "Comment deleted"
    author.classList.add('mr-2')
    author.classList.add('text-muted')
    author.classList.add('font-italic')

    section.querySelector(`#edit-${id}`).remove()

    // Body
    let body = section.querySelector(`#comment-body-${id}`)
    body.classList.add('text-muted')
    body.classList.add('font-italic')
    body.removeAttribute('id')
    body.innerHTML = "This comment has been deleted and is no longer available."
    body.nextElementSibling.remove()

    // Likes
    let options = section.querySelector(`#options-${id}`)
    let likes = parseInt(options.querySelector(".likes-row p").innerHTML)
    options.innerHTML = likesHTML(likes)
}

function createEditComment(id){
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
        p.innerHTML = p.innerHTML + ` <a href="/comments/${id}/versions" class="font-italic text-muted">(Edited)</a>`
}

// Edit
function addCommentEditListener(id){
    document.querySelector(`div[data-cid="${id}"] .edit-comment`).addEventListener('click', openComment)
    let edit = document.getElementById(`edit-${id}`)
    edit.querySelector('.cancel-edit').addEventListener('click', cancelEdit)
    edit.parentElement.parentElement.querySelector('.autoExpand').addEventListener('input', function(event){
        autoExpand(event.currentTarget)
    })
    edit.firstElementChild.addEventListener('click', function(event){editComment(event, id)})
}

function editCommentHandler(res, event){
    switch (res.status) {
        case 400:
            addFloatingAlert("Something went wrong, could not edit your comment.");
            res.json().then((data) => {
                let button = event.target
                if(event.target.tagName == "I")
                    button = button.parentElement
                document.getElementById("error-info-" + button.dataset.cid).innerText = data['errors']['body']

            })
            break;
        case 401:
            addFloatingAlert("Something went wrong, we were not able to edit your comment. Make sure you are logged in.");
            break;
        case 403:
            addFloatingAlert("Something went wrong, you are not allowed to edit this comment");
            break;
        case 404:
            addFloatingAlert("Something went wrong, we could not find the comment you are trying to edit");
            break;
        case 200:
            addFloatingAlert("Comment Edited. Way to go!");
            res.json().then((data) => {
                createEditComment(data.id)
                document.getElementById("error-info-" + data.id).innerText = ""
            })
                     
            break;
        default:
            addFloatingAlert("Something went wrong, could not post your comment.");
            break;
    }
}

function editComment(event, id){
    let body = document.getElementById(`edit-body-${id}`)
    if(!validPositiveInt(id) || body == null){
        addFloatingAlert("Invalid comment id. Please consider reloading the page")
        event.preventDefault()
        return
    }

    let pError =  document.getElementById(`error-info-${id}`)
    if(!validShortText(body.value)){
        addFloatingAlert("Something went wrong. Comment body has invalid characters...")
        pError.innerText = "Comment must have between 1 and 500 letters, numbers or symbols like ?+*_!#$%,\/;.&-"
        event.preventDefault()
        return
    }
    pError.innerText = ""

    sendAjaxRequest('PUT', `/api/comments/${id}`, {body: body.value}, 'application/json', editCommentHandler, event)
}

// HTML
function likesHTML(likes){
    if(likes > 0)
        return `<p class="mb-0 pr-sm-3"><i class="fas fa-angle-up"></i>&nbsp;&nbsp;${likes}</p>`
    if(likes < 0)
        return `<p class="mb-0 pr-sm-3"><i class="fas fa-angle-down"></i>&nbsp;&nbsp;${likes}</p>`
    return `<p class="mb-0 pr-sm-3"><i class="fas fa-minus"></i>&nbsp;&nbsp;${likes}</p>`
}

function commentHTML(comment){
    let a = `<div data-cid="${comment.id}" class="mb-5">
                 <article class="comment-area mb-3 border-left px-3">
                    <div class="card border-0 pb-1">
                        <div class="row no-gutters">
                            <div class="col-2 col-sm-1 col-md-2 col-lg-1">`
    let b
    if(comment.author.photo !== null)
        b = `<a href="/users/${comment.author.id}}">
                    <div class="square rounded-circle" style="background-image: url('/storage/images/users/${comment.author.photo}')"></div>
            </a>`
    else
        b = `<a href="/users/${comment.author.id}}">
                <div class="square rounded-circle" style="background-image: url('/storage/images/users/default.png')"></div>
            </a>`
    let c =`</div>
            <div class="col-10 col-sm-11 col-md-10 col-lg-11 d-flex justify-content-between align-items-start">
                <div class="d-flex flex-column flex-fill">
                    <div class="card-body py-1 d-flex flex-column flex-sm-row align-items-sm-center">`
    let d
    if (comment.author.verified)
        d = `<h5 class="card-title my-1"><a href="/users/${comment.author.id}" class="text-dark mr-2">${comment.author.name}  <i class="fas fa-check-circle"></i></a></h5>`
    else
        d = `<h5 class="card-title my-1"><a href="/users/${comment.author.id}" class="text-dark mr-2">${comment.author.name}</a></h5>`
    let e = comment.edited ? `<p class="card-text small text-muted pl-2 my-auto border-left">` + moment(comment.publication_date).fromNow() + ` <a href="/comments/${comment.id}/versions" class="font-italic text-muted">(Edited)</a></p>` :
             `<p class="card-text small text-muted pl-2 my-auto border-left">` + moment(comment.publication_date).fromNow() + ` </p>`

    let f = ` <div id="edit-${comment.id}" class="d-none ml-auto">
                    <button class="btn btn-sm btn-primary hover-bold px-4 submit-edit" data-cid="${comment.id}" type="submit">
                        <i class="fas fa-paper-plane"></i>
                    </button>
                    <button class="btn btn-sm btn-outline-secondary mx-2 cancel-edit" data-cid="${comment.id}" >
                        <i class="fa fa-times"></i>
                    </button>
                </div>
            </div>
            <p id="comment-body-${comment.id}" class="px-0 px-sm-3 py-2 m-0 ml-sm-n3">${comment.body}</p>
            <div class="px-0 px-sm-3 py-2 m-0 ml-sm-n3 d-none">
                <textarea id="edit-body-${comment.id}" class="form-control autoExpand border-primary border-right-0 border-top-0 border-left-0 p-0" data-min-rows='1' rows="1" ></textarea>
                <p id="error-info-${comment.id}" class="small text-primary mb-0"></p>
            </div>
        </div>
            <div id="options-${comment.id}" class="py-1 flex-shrink-0">
                <div class="d-flex flex-row-reverse">
                    <div class="dropdown">
                        <button class="btn  btn-sm bg-white" type="button" id="dropdownMenuButton${comment.id}" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <i class="fas fa-ellipsis-h"></i>
                        </button>
                        <div class="dropdown-menu" aria-labelledby="dropdownMenuButton${comment.id}">
                            <a class="edit-comment dropdown-item" href="${comment.id}">Edit Comment</a>
                            <a class="delete-comment dropdown-item" href="/api/comments/${comment.id}">Delete Comment</a>
                        </div>
                    </div>
                    <button class="btn btn-sm bg-white reply" type="button" data-toggle="collapse" data-target="#reply${comment.id}-collapse" aria-expanded="false" >
                        <i class="fa fa-reply"></i>
                    </button>
                </div>

                        <div class="likes-row row mx-0">
                            <div class="form-check form-check-inline ml-auto">
                                <input class="form-check-input check-rate" type="radio" name="inlineRadioOptions-${comment.id}}" id="inlineRadio${comment.id}" value="like">
                                <label class="form-check-label" for="inlineRadio${comment.id}"><i class="fas fa-angle-up"></i></label>
                            </div>
                        <p class="mb-0 pr-sm-3">${comment.likes_difference} </p>
                            <div class="form-check form-check-inline ml-auto">
                                <input class="form-check-input check-rate" type="radio" name="inlineRadioOptions-${comment.id}" id="inlineRadio-${comment.id}" value="dislike">
                                <label class="form-check-label" for="inlineRadio-${comment.id}"><i class="fas fa-angle-down"></i></label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </article>
    <div class="ml-5">
        <form id="reply${comment.id}-collapse" class="collapse bg-light p-2 rounded reply-form"  method="post">
            <button id="submit-reply${comment.id}" class="btn btn-sm btn-primary hover-bold float-right px-4" type="submit"><i class="fas fa-paper-plane"></i></button>
            <div class="form-group mb-1">
                <label class="font-weight-bold p-1" for="replyArea${comment.id}">Reply *</label>
                <textarea class="bg-light form-control border-right-0 border-top-0 border-left-0" id="replyArea${comment.id}" rows="3" required></textarea>
                <p class="error-info small text-muted mb-0">* Required Input</p>
            </div>
        </form>
    </div>
</div>`

    return a + b + c + d + e + f
}

function updateCommentListeners(){
    let commentDelButtons = document.getElementsByClassName("delete-comment")
    for(let commentDelButton of commentDelButtons)
        commentDelButton.addEventListener('click', sendDeleteComment)

    let commentEditButtons = document.getElementsByClassName("edit-comment")
    for(let commentEditButton of commentEditButtons)
        commentEditButton.addEventListener('click', openComment)
}