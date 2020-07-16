function setAttributes(el, attrs) {
    for(var key in attrs) {
      el.setAttribute(key, attrs[key]);
    }
}

function deleteSavedPost(button) {

    let savedPost = button.parentElement.parentElement.parentElement;
    let post_id = savedPost.getAttribute("data-id");
    if(!validPositiveInt(post_id)){
        addFloatingAlert("Invalid post id. Please consider reloading the page")
        return
    }

    let user_id = document.getElementById("saved-posts").getAttribute("data-id");
    if(!validPositiveInt(user_id)){
        addFloatingAlert("Invalid user id. Please consider reloading the page")
        return
    }

    fetch(`/api/users/${user_id}/saved_posts`, {
        method: 'DELETE',
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

                addFloatingAlert("Post removed successfully from saved posts");
                location.reload();
                break;
            case 400:

                addFloatingAlert("Post could not be removed due to invalid input data.");
                location.reload();
                break;
            case 401:

                addFloatingAlert("Post could not be removed. Please make sure you are logged in.");
                location.reload();
                break;
            case 404:

                addFloatingAlert("Post could not be found. Please reload the page.");
                location.reload();
                break;
            default:
                addFloatingAlert("Something went wrong, the post could not be removed");
                break;
        }  
    });
}

function updateLinkHandlers() {
    $("#saved-posts .page-link").off('click')
    $("#saved-posts .page-link").click( function(event) {
        pageLinkClickHandler(event)
    });
}

function updateAllHandlers(){
    updateLinkHandlers()
    // saved post deletion
    let savedPostCrosses = document.querySelectorAll('article button');
    for (let savedPostCross of savedPostCrosses)
        savedPostCross.addEventListener('click', function(event) {
            event.preventDefault()
            deleteSavedPost(savedPostCross);
        });
}

async function pageLinkClickHandler(event) {
    event.preventDefault();
    let data = await fetchHTMLfromTarget(event.currentTarget);
    let tableBody = document.querySelector(`#saved-posts div.tab-pane`);
    tableBody.innerHTML = data;
    updateAllHandlers();
}

let orderOpts = document.querySelectorAll("div[aria-labelledby=dropdownMenuButton] a")
Array.from(orderOpts).forEach(opt => {
    opt.addEventListener('click', function(event){
        let user_id = document.getElementById("saved-posts").getAttribute("data-id");
        if(!validPositiveInt(user_id)){
            addFloatingAlert("Invalid user id. Please consider reloading the page")
            event.preventDefault()
            return
        }

        let orderVal = opt.getAttribute('href').slice(1)
        if(orderVal != "alpha" && orderVal != "recent" && orderVal != "numerical"){
            addFloatingAlert("Invalid order value. Please consider reloading the page and not messing with the HTML code")
            event.preventDefault()
            return
        }
        sendGetAjaxRequest('GET', `/api/users/${user_id}/saved_posts`, {order: orderVal, ppp: 6, page: 1}, 'text/html', orderHandler, event)
    });
});

function orderHandler(res, _event){
    switch (res.status) {
        case 200:
            res.text().then((html) => {
                document.querySelector(`#saved-posts div.tab-pane`).innerHTML = html
                updateAllHandlers()
            })
            break;
        default:
            addFloatingAlert("Something went wrong, the posts could not be ordered");
            break;
    }  
}

updateAllHandlers()