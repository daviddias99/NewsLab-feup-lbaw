$(function () {
	$('#banTime').datetimepicker({
        minDate: moment().add(1,'days'),
        format: 'L',
		buttons: {
			showToday: true,
			showClose: true,
            showClear: true,
		},
		icons: {
			clear: 'fa fa-trash',
		},
	});
});

$(function () {
    $('[data-toggle="tooltip"]').tooltip()
  })

const unbanUserButton = document.getElementById('unban_user');
if(unbanUserButton != null)
    unbanUserButton.addEventListener('click',unbanUser);

const banUserButton = document.getElementById('ban_btn');
if(banUserButton != null)
    banUserButton.addEventListener('click', banUser);

const permaban_check = document.getElementById('permaban');
if(permaban_check != null)
    permaban_check.addEventListener('click', toggleBanCalendar);

const makePublicButton = document.getElementsByClassName('topRightSecondary');

for (let item of makePublicButton) {
    item.addEventListener('click', makePostPublic);
}

let temp = null;

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

function toggleBanCalendar(event){
    const permaBanState = event.srcElement.checked
    const cInput = document.getElementById("ban-calendar-input")

    if(permaBanState)
        cInput.setAttribute('disabled',true)
    else
        cInput.removeAttribute('disabled')

}

async function banUser(event){

    event.preventDefault();
    const link = event.currentTarget.getAttribute('data-href');
    const permaban = document.getElementById('permaban').checked
    let end_Date = document.querySelector(".datetimepicker-input")
    let body = ""

    let pError = document.getElementById("dateError")
    if(!permaban){
        if(!validDate(end_Date.value)){
            pError.innerText = "Invalid date format - mm/dd/yyy"
            return
        }
        pError.innerText = ""
        body = JSON.stringify({"endDate": end_Date.value})
    }


    fetch(link, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: body
        }
    )
    . then((res)=>{

        $('#ban-modal').modal('hide');
        switch (res.status) {
            case 200:
                const unbanDiv = document.querySelector("#ban_unban_div");
                const userID = document.querySelector("main").getAttribute('data-id')
                unbanDiv.innerHTML = '<a class="dropdown-item" id="unban_user" href="/api/users/'+userID+ '/unban">Unban user</a>'
                const unbanUserButton = document.getElementById('unban_user');
                unbanUserButton.addEventListener('click',unbanUser);
                addFloatingAlert("User banned.");
                break;
            case 404:
                addFloatingAlert("Could not find user.");
                break;
            case 409:
                addFloatingAlert("User already banned.");
                break;
            case 403:
                addFloatingAlert("You do not have permissions to ban this user.");
                break;
            default:
                addFloatingAlert("Could not ban user.");

        }
    })
    .catch(function (error) {
        addFloatingAlert("Could not ban user.");
    });
}
async function unbanUser(event){

    event.preventDefault();
    const link = event.currentTarget.getAttribute('href');

    fetch(link, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            }
        }
    )
    . then((res)=>{

        $('#ban-modal').modal('hide');
        switch (res.status) {
            case 200:

                const unbanDiv = document.querySelector("#ban_unban_div");
                unbanDiv.innerHTML = '<a class="dropdown-item" href="#" data-toggle="modal" data-target="#ban-modal">Ban User</a>'
                addFloatingAlert("User unbanned.");
                const banUserButton = document.getElementById('ban_btn');
                banUserButton.addEventListener('click', banUser);
                break;
            case 403:
                addFloatingAlert("You are not authorized to unban this user.");
                break;
            case 404:
                addFloatingAlert("User not found.");
                break;
            default:
                addFloatingAlert("Could not unban user.");

        }
    })
    .catch(function (error) {
        addFloatingAlert("Could not unban user.");
    });
}

/////////////////////////////////////////////////////////////

const subscribeButton = document.getElementById('subscribe_btn')

function subscribeHandler(res, _event){
    let method = (subscribeButton.innerHTML == "Subscribe") ? "POST" : "DELETE"

    switch (res.status) {
        case 200:

            if (method == "POST") {
                addFloatingAlert("User added to your subscriptions!");
                subscribeButton.innerHTML = "Unsubscribe"
                subscribeButton.className = "btn btn-outline-secondary ml-auto"

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
                addFloatingAlert("User removed sucessfully from subscriptions");
                subscribeButton.innerHTML = "Subscribe"
                subscribeButton.className = "btn btn-primary ml-auto"

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

        let subscribed_id = document.querySelector("main").getAttribute("data-id")
        if (!validPositiveInt(subscribed_id)){
            addFloatingAlert("Invalid User ID. Please consider refreshing the page...")
            return
        }

        let method = (subscribeButton.innerHTML == "Subscribe") ? "POST" : "DELETE"

        sendAjaxRequest(method, `/api/users/${subscribing_id}/manage_subs/users`, {id: subscribed_id}, 'application/json', subscribeHandler, null)
    })
}

/////////////////////////////////////////////////////////////
// Reports
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
        addFloatingAlert("Invalid user id. Please consider reloading the page")
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

    sendAjaxRequest('POST', `/api/users/${id}/report`, {reasons:serialReasons.slice(0, -1), explanation:explanationVal}, 'application/json', reportHandler, event)
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
            addFloatingAlert("Something went wrong, we were not able to report this user. Make sure you are logged in.");
            $('#report-modal').modal('hide');
            break;
        case 403:
            addFloatingAlert("Something went wrong, you are not allowed to report this user.");
            $('#report-modal').modal('hide');
            break;
        case 404:
            addFloatingAlert("Something went wrong, we could not find this user");
            $('#report-modal').modal('hide');
            break;
        case 409:
            addFloatingAlert("You already reported that user");
            pError.innerText = "* Required Input"
            pError.classList.remove("text-primary")
            pError.classList.add("text-muted")
            $('#report-modal').modal('hide');
            explanation.value = ""
            for(let reason of reasons)
                reason.checked = false
            break;
        case 200:
            addFloatingAlert("User reported. Way to go!");
            pError.innerText = "* Required Input"
            pError.classList.remove("text-primary")
            pError.classList.add("text-muted")
            $('#report-modal').modal('hide');
            explanation.value = ""
            for(let reason of reasons)
                reason.checked = false
            break;
        default:
            addFloatingAlert("Something went wrong, we could not report this user");
            $('#report-modal').modal('hide');
            break;
    }
}