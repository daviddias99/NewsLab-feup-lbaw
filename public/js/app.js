function setAttributes(el, attrs) {
    for (var key in attrs) {
        el.setAttribute(key, attrs[key]);
    }
}

function convertHex(hex, opacity) {
    hex = hex.replace(' ', '');
    hex = hex.replace('#', '');
    r = parseInt(hex.substring(0, 2), 16);
    g = parseInt(hex.substring(2, 4), 16);
    b = parseInt(hex.substring(4, 6), 16);

    result = 'rgba(' + r + ',' + g + ',' + b + ',' + opacity / 100 + ')';
    return result;
}

function addFloatingAlert(message) {

    let alertsSection = document.querySelector("header > section#alerts")

    if (alertsSection == null) {
        alertsSection = document.createElement("section");
        alertsSection.setAttribute("id", "alerts");
        alertsSection.setAttribute("class", "fixed-top");
        let header = document.querySelector("header");
        header.appendChild(alertsSection);
    }

    let newAlert = document.createElement("div");
    let alertButton = document.createElement("button");
    setAttributes(alertButton, { "type": "button", "class": "close", "data-dismiss": "alert", "aria-label": "Close" })
    alertButton.innerHTML = "<span aria-hidden=\"true\">&times;</span>";
    setAttributes(newAlert, { "class": "alert alert-danger floating-alert alert-dismissible fade show", "role": "alert" })
    newAlert.innerHTML = message;
    newAlert.appendChild(alertButton);
    alertsSection.appendChild(newAlert);

    setTimeout(function () {
        newAlert.remove();
    }, 3000)
}

function sendAjaxRequest(method, url, data, type, handler, event) {
    if(event != null) event.preventDefault()
    fetch(url, {
        method: method,
        body: JSON.stringify(data),
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
            'Accept': type
        }
    })
    .then((res) => {
        handler(res, event)
    });
}

function sendAjaxSerializedRequest(method, url, data, type, handler) {
    fetch(url, {
        method: method,
        body: data,
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
            'Accept': type
        }
    })
    .then((res) => {
        handler(res)
    });
}

function sendGetAjaxRequest(method, url, data, type, handler, event) {
    if(event != null) event.preventDefault()

    fetch(url + "?" + encodeForAjax(data), {
        method: method,
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
            'Accept': type
        }
    })
    .then((res) => {
        handler(res, event)
    });
}


function encodeForAjax(data) {
	return Object.keys(data).map(function(k){
	  return encodeURIComponent(k) + '=' + encodeURIComponent(data[k])
	}).join('&')
}

async function fetchHTMLfromTarget(target) {

    const link = target.getAttribute('href');

    return await getPartial(link);
}

function getPartial(link) {

    return fetch(link, {
        headers: {
            'Accept': 'text/html'
        }
    })
    .then(function (response) {
        return response.text();
    }).catch(function (error) {
        addFloatingAlert("An unexpected error occurred. Please reload the page.")
    });
}

/// NAVBAR ///
let searchForm = document.getElementById("search-form")

document.querySelector("#search-form input").addEventListener("keyup", function(event) {
    
    if (event.key === "Enter") {
        searchFunc(event)
    }
});

searchForm.querySelector("a").addEventListener("click",searchFunc)

function searchFunc(event){
    let searchValue = searchForm.querySelector('input').value
    
    if(!validSearch(searchValue)){
        event.preventDefault()
        addFloatingAlert("Invalid Search: must have at least 2 letters, numbers or symbols like ?+*_!#$%,\/;.&-")
        return
    }
    searchForm.querySelector("a").setAttribute('href', "/search?search=" + searchValue)
    document.location.href =   searchForm.querySelector("a").getAttribute('href')
}

$(function () {
    $('[data-toggle="tooltip"]').tooltip()
})

addBreadCrumb();

function addBreadCrumb(){

    if(window.location.href.includes("news")){
        let selected = document.querySelector("a[href='/news'] span")
        selected.setAttribute("style", "text-decoration: underline;")
    } 
    else if(window.location.href.includes("opinions")){
        let selected = document.querySelector("a[href='/opinions']")
        selected.setAttribute("style", "text-decoration: underline;")
    } 
    else if(window.location.href.includes("feed")){
        let selected = document.querySelector("a[href='/feed'] span")
        selected.setAttribute("style", "text-decoration: underline;")
    } 
    else if(window.location.href.includes("admins")){
        let selected = document.querySelectorAll("#user-opts a")

        for (let index = 0; index < selected.length; index++) {
            let element = selected[index];

            if(element.getAttribute("href").includes("admins")){
                element.setAttribute("style", "text-decoration: underline;")
            }
        }
    } 
    else if(window.location.href.includes("manage_subs")){
        let selected = document.querySelectorAll("#user-opts a")

        for (let index = 0; index < selected.length; index++) {
            let element = selected[index];

            if(element.getAttribute("href").includes("manage_subs")){
                element.setAttribute("style", "text-decoration: underline;")
            }
        }
    } 
    else if(window.location.href.includes("users")){
        let selected = document.querySelectorAll("#user-opts a.opt")

        for (let index = 0; index < selected.length; index++) {
            let element = selected[index];

            if(element.getAttribute("href").includes("users") && !element.getAttribute("href").includes("manage_subs")){
                element.setAttribute("style", "text-decoration: underline;")
            }
        }
    }  
    else if(window.location.href.includes("create")){
        let selected = document.querySelectorAll("#user-opts a.opt")

        for (let index = 0; index < selected.length; index++) {
            let element = selected[index];

            if(element.getAttribute("href").includes("create")){
                element.setAttribute("style", "text-decoration: underline;")
            }
        }
    } 
    else if(window.location.href.includes("about")){
        let selected = document.querySelectorAll("footer a")

        for (let index = 0; index < selected.length; index++) {
            let element = selected[index];

            if(element.getAttribute("href").includes("about")){
                element.setAttribute("style", "text-decoration: underline;")
            }
        }
    } 
    else if(window.location.href.includes("faq")){
        let selected = document.querySelectorAll("footer a")

        for (let index = 0; index < selected.length; index++) {
            let element = selected[index];

            if(element.getAttribute("href").includes("faq")){
                element.setAttribute("style", "text-decoration: underline;")
            }
        }
    } 
}