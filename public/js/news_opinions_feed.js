updateLinkHandlers()

function updateLinkHandlers() {
    $("#posts .page-link").off('click')
    $("#posts .page-link").click( function(event) {
        type = document.querySelector('#type').getAttribute('data-type')
        pageLinkClickHandler(event, type)
    });
}

async function pageLinkClickHandler(event,id) {

    event.preventDefault();
    let data = await fetchHTMLfromTarget(event.currentTarget);
    let tableBody = document.querySelector('#posts');
    
    if(id == 'Opinion') {
        let div = document.createElement("div"); 
        div.innerHTML = data;
        tableBody.innerHTML = div.firstElementChild.nextElementSibling.innerHTML
    }
    else if(id == 'News') {
        let div = document.createElement("div"); 
        div.innerHTML = data;
        tableBody.innerHTML = div.firstElementChild.innerHTML
    }

    updateLinkHandlers();
}
