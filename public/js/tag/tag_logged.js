/////////////////////////////////////////////////////////////

let reportForm = document.querySelector("#report-modal")

let reportButtons = document.querySelectorAll("a[data-target='#report-modal']")
for(let reportButton of reportButtons)
    reportButton.addEventListener('click', function(event){
        event.preventDefault()
        reportForm.querySelector("#reported-id").value = reportButton.getAttribute('href')
    })

let reasons = null
let explanation = reportForm.querySelector("textarea")
reportForm.querySelector(".modal-footer button[type='submit']").addEventListener('click', function(event){
    let pError =  reportForm.querySelector("p")
    let id = reportForm.querySelector("#reported-id").value
    reasons = reportForm.querySelectorAll(".form-check-input:checked")
    explanationVal = explanation.value

    if(!validPositiveInt(id)){
        addFloatingAlert("Invalid tag id. Please consider reloading the page")
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

    sendAjaxRequest('POST', `/api/tags/${id}/report`, {reasons:serialReasons.slice(0, -1), explanation:explanationVal}, 'application/json', reportHandler, event)
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
            addFloatingAlert("Something went wrong, we were not able to report this tag. Make sure you are logged in.");
            $('#report-modal').modal('hide'); 
            break;
        case 403:
            addFloatingAlert("Something went wrong, you are not allowed to report this tag.");
            $('#report-modal').modal('hide'); 
            break;
        case 404:
            addFloatingAlert("Something went wrong, we could not find this tag");
            $('#report-modal').modal('hide'); 
            break;
        case 409:
            addFloatingAlert("You already reported this tag");
            pError.innerText = "* Required Input"
            pError.classList.remove("text-primary")
            pError.classList.add("text-muted")
            $('#report-modal').modal('hide');
            explanation.value = ""
            for(let reason of reasons)
                reason.checked = false
            break;
        case 200:
            addFloatingAlert("Tag reported. Way to go!");
            pError.innerText = "* Required Input"
            pError.classList.remove("text-primary")
            pError.classList.add("text-muted")
            $('#report-modal').modal('hide');
            explanation.value = ""
            for(let reason of reasons)
                reason.checked = false
            break;
        default:
            addFloatingAlert("Something went wrong, we could not report this tag");
            $('#report-modal').modal('hide'); 
            break;
    }
}

////////////////////////////////////////////////////////////////

const subscribeButton = document.getElementById('subscribe_btn')

function subscribeHandler(res, _event){
    let method = (subscribeButton.innerHTML == "Subscribe") ? "POST" : "DELETE"

    switch (res.status) {
        case 200:

            if (method == "POST") {
                addFloatingAlert("Tag added to your subscriptions!");
                subscribeButton.innerHTML = "Unsubscribe";
                subscribeButton.className = "btn btn-outline-secondary ml-auto";
               
                let sub_count_elem = document.getElementById('sub-count')
                let text = sub_count_elem.innerHTML
                let nSubs = parseInt(text)
                nSubs += 1
                sub_count_elem.innerHTML = nSubs
                if (nSubs == 1)
                    sub_count_elem.innerHTML += " Subscriber"
                else
                    sub_count_elem.innerHTML += " Subscribers"
            }
            else {
                addFloatingAlert("Tag removed sucessfully from subscriptions");
                subscribeButton.innerHTML = "Subscribe";
                subscribeButton.className = "btn btn-primary ml-auto";

                let sub_count_elem = document.getElementById('sub-count')
                let text = sub_count_elem.innerHTML
                let nSubs = parseInt(text)
                nSubs -= 1
                sub_count_elem.innerHTML = nSubs
                if (nSubs == 1)
                    sub_count_elem.innerHTML += " Subscriber"
                else
                    sub_count_elem.innerHTML += " Subscribers"
            }

            break;
        default:
            addFloatingAlert("Something went wrong, could not perform operation.");
            break;
    }
}

if (subscribeButton != null) {
    subscribeButton.addEventListener('click', function(event) {
        event.preventDefault()

        let subscribing_id = subscribeButton.getAttribute("data-subscribing_id")
        if (!validPositiveInt(subscribing_id)){
            addFloatingAlert("Invalid User ID. Please consider refreshing the page...")
            return
        }

        let subscribed_id = subscribeButton.parentElement.getAttribute("data-id")
        if (!validPositiveInt(subscribed_id)){
            addFloatingAlert("Invalid tag ID. Please consider refreshing the page...")
            return
        }

        let method = (subscribeButton.innerHTML == "Subscribe") ? "POST" : "DELETE"

        sendAjaxRequest(method, `/api/users/${subscribing_id}/manage_subs/tags`, {id: subscribed_id}, 'application/json', subscribeHandler, null)
    })
}