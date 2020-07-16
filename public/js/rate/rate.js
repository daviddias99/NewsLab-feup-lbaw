function rateContent(button, content_id, rating) {
    if(!validPositiveInt(content_id)){
        addFloatingAlert("Invalid content id. Please consider reloading the page")
        return
    }

    let method = ""
    let selected = document.querySelector(`input[name="inlineRadioOptions-${content_id}"].checked`)
    if(selected != null){
        if(selected.getAttribute('id') == button.getAttribute('id'))
            method = 'DELETE'
        else
            method = 'PUT'
    }
    else
        method = 'POST'

    if(rating !== true && rating !== false){
        addFloatingAlert("Invalid rating. Please consider reloading the page")
        return
    }

    fetch(`/api/rate/${content_id}`, {
        method: method,
        body: method == 'DELETE' ?  '' : JSON.stringify({rating: rating}),
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
            'Accept': 'application/json'
        }
    })
    . then((res)=>{
        switch (res.status) {
            case 400:
                addFloatingAlert("Something went wrong, could not perform rating.");
                break;
            case 401:
                addFloatingAlert("Something went wrong, could not perform rating. Please make sure you are logged in.");
                break;
            case 403:
                addFloatingAlert("Something went wrong, you are not allowed to rate that content.");
                break;
            case 404:
                addFloatingAlert("Something went wrong, we could not find the content you are trying to rate.");
                break;
            case 200:
                res.json().then((data) => {
                    let p = button.parentNode.parentNode.querySelector("p")
                    p.innerHTML = data.likes_difference
                    button.checked = !button.checked
                    if(selected != null)
                        selected.classList.remove('checked')
                    if(method != 'DELETE')
                        button.classList.add('checked')
                })
                break;
            default:
                addFloatingAlert("Something went wrong, could not perform rating.");
                break;
        }  
    });
}

function updateRateListeners(){
    // Ratings
    let downVotes = document.querySelectorAll("input[value=dislike]")
    for (let downVote of downVotes) {
        let content_id = downVote.getAttribute("name").split("-")[1]
        downVote.addEventListener("click", function (event) {
            event.preventDefault()
            rateContent(downVote, content_id, false)
        })
    }

    let upVotes = document.querySelectorAll("input[value=like]")
    for (let upVote of upVotes) {
        let content_id = upVote.getAttribute("name").split("-")[1]
        upVote.addEventListener("click", function (event) {
            event.preventDefault()
            rateContent(upVote, content_id, true)
        })
    }
}

updateRateListeners()