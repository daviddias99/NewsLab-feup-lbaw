function setAttributes(el, attrs) {
    for(var key in attrs) {
      el.setAttribute(key, attrs[key]);
    }
}

// tag subscription deletion
let subbedTagCrosses = document.querySelectorAll('.unsub-tag');
for (let subbedTagCross of subbedTagCrosses)
    subbedTagCross.addEventListener('click', function(event) {
        event.preventDefault()
        let tag_id = event.target.getAttribute("data-id")
        let elementToRemove = event.target.parentElement.parentElement.parentElement
        deleteUserSub(tag_id, "Tag", elementToRemove);
    });
    
// user subscription deletion
let subbedUserCrosses = document.querySelectorAll('.unsub-user');
for (let subbedUserCross of subbedUserCrosses)
    subbedUserCross.addEventListener('click', function(event) {
        event.preventDefault()
        let user_id = event.target.getAttribute("data-id")
        let elementToRemove = event.target.parentElement.parentElement.parentElement.parentElement
        deleteUserSub(user_id, "User", elementToRemove);
    });



function deleteUserSub(request_id, type, elementToRemove) {
    if(!validPositiveInt(request_id)){
        addFloatingAlert("Invalid request id. Please consider reloading the page")
        return
    }

    let endpointType = null;
    if (type == "Tag")
        endpointType = "tags"
    else if (type == "User")
        endpointType = "users"
    else{
        addFloatingAlert("Invalid delete type. Please consider reloading the page")
        return
    }

    let user_id = document.querySelector("main").getAttribute("data-id")
    if(!validPositiveInt(user_id)){
        addFloatingAlert("Invalid user id. Please consider reloading the page")
        return
    }

    fetch(`/api/users/${user_id}/manage_subs/${endpointType}`, {
        method: 'DELETE',
        body: JSON.stringify({
            id: request_id
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
                if (type == "Tag") {
                    elementToRemove.remove();
                    addFloatingAlert("Tag removed sucessfully from subscriptions");
                }
                else if (type == "User") {
                    location.reload() 
                    addFloatingAlert("User removed sucessfully from subscriptions");
                }

                break;
            default:
                addFloatingAlert("Something went wrong, the subscription could not be removed");
                break;
        }  
    });
}

