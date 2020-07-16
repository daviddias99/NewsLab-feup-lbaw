
const user_search_btn = document.querySelector('#admin_search_button')
const user_search_bar = document.querySelector('#search-users')

user_search_bar.addEventListener("keyup", function(event) {
    if (event.key === "Enter") {
        userSearch(event)
    }
});

user_search_btn.addEventListener('click', userSearch);

function updateLinkHandlers() {
    $("#admin_list .page-link").off('click')
    $("#admin_list .page-link").click( function(event) {
        pageLinkClickHandler(event,'admin_list')
    });

    $("#banned_users_list .page-link").off('click')
    $("#banned_users_list .page-link").click( function(event) {
        pageLinkClickHandler(event,'banned_users_list')
    });

    $("#open_reports .page-link").off('click')
    $("#open_reports .page-link").click( function(event) {
        pageLinkClickHandler(event,'nav-rp-open')
    });

    $("#closed_reports .page-link").off('click')
    $("#closed_reports .page-link").click( function(event) {
        pageLinkClickHandler(event,'nav-rp-closed')
    });
}

function updateHandlers(linkSelector, callback){

    const links = document.querySelectorAll(linkSelector);

    Array.from(links).forEach(btn => {
        btn.removeEventListener('click', callback);
        btn.addEventListener('click', callback);
    });
}

function updateAllHandlers(){

    updateHandlers(".close-report",closeReport)
    updateHandlers(".unban_user",unbanUser)
    updateHandlers(".delete-admin",removeAdmin);
    updateLinkHandlers();
}

async function pageLinkClickHandler(event,id) {
    event.preventDefault();
    let data = await fetchHTMLfromTarget(event.currentTarget);
    let tableBody = document.getElementById(id);
    tableBody.innerHTML = data;
    updateAllHandlers();
}

async function fetchHTMLfromTarget(target) {

    const link = target.getAttribute('href');

    return await getPartial(link);
}

async function removeAdmin(event) {

    const adminID = event.currentTarget.getAttribute('data-admin_id');

    if(!validPositiveInt(adminID)){
        addFloatingAlert("Invalid admin id. Please consider reloading the page")
        return
    }

    let response = await fetch('/api/admins/' + adminID, {
        method: 'DELETE',
        headers: {
            'Accept': 'application/json',
        }
    }).then(res => res.json());

    if (response['status_code'] == 200) {

        let link = document.querySelector(".admin_list_nav .active a.page-link").getAttribute('href');
        let data = await getPartial(link);
        let tableBody = document.getElementById('admin_list');
        tableBody.innerHTML = data;
        updateAllHandlers();
        addFloatingAlert("Admin removed successfully!");
    }
    else{
        addFloatingAlert("Could not remove admin.");
    }

}

async function unbanUser(event) {

    const userID = event.currentTarget.getAttribute('data-user_id');
    if(!validPositiveInt(userID)){
        addFloatingAlert("Invalid user id. Please consider reloading the page")
        return
    }

    let response = await fetch('/api/users/' + userID + '/unban', {
        method: 'POST',
        headers: {
            'Accept': 'application/json',
        }
    })

    if (response.status == 200) {

        let link = document.querySelector(".banned_list_nav .active a.page-link").getAttribute('href');
        let data = await getPartial(link);
        let tableBody = document.getElementById('banned_users_list');
        tableBody.innerHTML = data;
        updateAllHandlers();
        addFloatingAlert("Unbanned user successfully!");
    }
    else{
        addFloatingAlert("Could not unban user.");
    }
}

async function closeReport(event) {

    const reportID = event.currentTarget.getAttribute('data-report_id');
    if(!validPositiveInt(reportID)){
        addFloatingAlert("Invalid report id. Please consider reloading the page")
        return
    }

    let response = await fetch('/api/reports/' + reportID, {
        method: 'PUT',
        headers: {
            'Accept': 'application/json',
        }
    }).then(res => res.json());

    if (response['status_code'] == 200) {

        let link_open = document.querySelector(".report_inbox_open_nav .active a.page-link").getAttribute('href');
        let data_open = await getPartial(link_open);
        let tableBody_open = document.getElementById('nav-rp-open');
        tableBody_open.innerHTML = data_open;

        let link_closed = document.querySelector(".report_inbox_closed_nav .active a.page-link").getAttribute('href');
        let data_closed = await getPartial(link_closed);
        let tableBody_closed = document.getElementById('nav-rp-closed');
        tableBody_closed.innerHTML = data_closed;
        updateAllHandlers();
        addFloatingAlert("Report marked as closed!");
    }
    else{ 
        addFloatingAlert("Could not close report!");
    }
    

}

async function newAdminResultHandler(result, event){
    $('#searchAdmin').modal('hide')

    switch (result['status']) {
        case 201:
            addFloatingAlert("Admin added successfully!");
            break;
    
        case 400:
            addFloatingAlert("No admin ID nor provided!");
            break;
        case 403:
            addFloatingAlert("Banned users cannot be admins!");
            break;

        case 409:
            addFloatingAlert("The user is already an admin!");
            break;
        default:
            addFloatingAlert("Could not add admin!");
            break;
    }        

    userSearch();
    let target = document.querySelector(".admin_list_nav li.active a");
    let data = await fetchHTMLfromTarget(target);
    let tableBody = document.getElementById('admin_list');
    tableBody.innerHTML = data;
    updateAllHandlers();
}

async function userSearch(){
    let pError = document.getElementById("nameError")
    if(!validUserName(user_search_bar.value)){
        pError.innerText = "Name should contain between 3 and 25 letters"
        return
    }
    pError.innerText = ""

    let data_closed = await getPartial('/api/admins/candidates?search='+ user_search_bar.value);
    const modalBody = document.getElementById('new_admin_list_placeholder')

    modalBody.innerHTML = data_closed;
    updateHandlers(".add_admin", addAdmin);
}

async function addAdmin(event) {
    const userID = event.currentTarget.getAttribute('data-userid');
    if(!validPositiveInt(userID)){
        addFloatingAlert("Invalid user id. Please consider reloading the page")
        return
    }

    sendAjaxRequest('POST','/api/admins',{ user_id: userID},'application/json',newAdminResultHandler,null)
}

updateAllHandlers();


