function updateLinkHandlers() {
    const links = document.getElementsByClassName('page-link');
    Array.from(links).forEach(link => {
        link.addEventListener('click', pageLinkClickHandler);
    });
}

function pageLinkClickHandler(event) {
    event.preventDefault();

    const link = event.currentTarget.getAttribute('href');

    fetch(link, {
            headers: {
                'Accept': 'text/html'
            }
        }
    )
    .then(function (response) {
        return response.text();
    })
    .then(function (data) {
        const query = document.evaluate(
            'ancestor::div[contains(@class, "tab-pane")]',
            event.target,
            null,
            XPathResult.FIRST_ORDERED_NODE_TYPE,
            null
        );

        const tab = query.singleNodeValue;
        tab.innerHTML = data;

        if (link.includes("comments") || link.includes("replies")) { // for comments/replies

            let logged_in = (tab.hasAttribute('data-logged_in') && (tab.getAttribute('data-logged_in') == "yes"))

            if (logged_in) {
                let downVotes = document.querySelectorAll("input[value=dislike]")
                let upVotes = document.querySelectorAll("input[value=like]")
                for (let downVote of downVotes) {
                    let content_id = downVote.getAttribute("name").split("-")[1]
                    downVote.addEventListener("click", function (event) {
                        event.preventDefault()
                        rateContent(downVote, content_id, false)
                    })
                }
        
                for (let upVote of upVotes) {
                    let content_id = upVote.getAttribute("name").split("-")[1]
                    upVote.addEventListener("click", function (event) {
                        event.preventDefault()
                        rateContent(upVote, content_id, true)
                    })
                }
            }
            else {
                let downVotes = document.querySelectorAll("input[value=dislike]")
                let upVotes = document.querySelectorAll("input[value=like]")

                for (let downVote of downVotes)
                    downVote.addEventListener("click", openLogin)

                for (let upVote of upVotes)
                    upVote.addEventListener("click", openLogin)
            }
        }

        else if (link.includes("saved_posts")) { // for the saved posts
            // saved post deletion
            let savedPostCrosses = document.querySelectorAll('article button');
            for (let savedPostCross of savedPostCrosses)
                savedPostCross.addEventListener('click', function(event) {
                    event.preventDefault()
                    deleteSavedPost(savedPostCross);
                });
        }

        else if (link.includes("manage_subs/users")) { // for the user subscriptions
            // user subscription deletion
            let subbedUserCrosses = document.querySelectorAll('.unsub-user');
            for (let subbedUserCross of subbedUserCrosses)
                subbedUserCross.addEventListener('click', function(event) {
                    event.preventDefault()
                    let user_id = event.target.getAttribute("data-id")
                    let elementToRemove = event.target.parentElement.parentElement.parentElement.parentElement
                    deleteUserSub(user_id, "User", elementToRemove);
                });
        }
        else if (link.includes("users")){
            const makePublicButton = document.getElementsByClassName('topRightSecondary');

            for (let item of makePublicButton) {
                item.addEventListener('click', makePostPublic);
            }
        }

            
        updateLinkHandlers();
    })
    .catch(function (error) {
        addFloatingAlert("An unknown error occurred. Please reload the page.");
    });
}

function makePostPublic(event){
    event.preventDefault();
    temp = event.currentTarget
    sendAjaxRequest('POST',event.currentTarget.getAttribute('data-href'),{'visibility': true}, 'application/json',privacyChangeHandler,event)
}


function privacyChangeHandler(res,event){
    switch (res.status) {
        case 200:
            const postID = temp.getAttribute('data-id')
            const selec = 'article[data-id="'+ postID+'"]';
            const main = document.querySelector(selec)
            main.classList.remove('private')
            temp.remove();
            addFloatingAlert("Post changed to public.");
            temp = null;
            break;
        default:
            addFloatingAlert("Could not set post to public.");

    }
}

updateLinkHandlers();
