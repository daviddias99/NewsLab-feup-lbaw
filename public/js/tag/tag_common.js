let headerOffset = document.getElementsByClassName("img-header")[0].offsetHeight
sideFilter.style.top = (convertRemToPixels(4.1) + 420) + 'px'

openBtn.style.top = (convertRemToPixels(5) + 420) + 'px'

function sidebar(){
    if(yOff() < 0)
        sideFilter.style.maxHeight = Math.min(vh(100) - convertRemToPixels(4) + yOff(), document.getElementById("wrapper").offsetHeight + convertRemToPixels(1.5)) + 'px'
    else
        sideFilter.style.maxHeight = Math.min(vh(100) - convertRemToPixels(4), document.getElementById("wrapper").offsetHeight + convertRemToPixels(1.5)) + 'px'
    if(window.pageYOffset > headerOffset){
        sideFilter.style.top = (convertRemToPixels(4.1)) + 'px'
        openBtn.style.top = (convertRemToPixels(5)) + 'px'
        sideFilter.style.position = 'fixed'
        openBtn.style.position = 'fixed'

    }
    else{
        sideFilter.style.position = 'absolute'
        openBtn.style.position = 'absolute'
        
        sideFilter.style.top = (420 + convertRemToPixels(4.1)) + 'px'
        openBtn.style.top = (420 + convertRemToPixels(5)) + 'px'
    }
}

window.addEventListener('scroll', function(){
    sidebar()
})

let tagTitle = document.getElementById("tag-title")
tagTitle.style.backgroundColor = convertHex(tagTitle.dataset.color, 80)

//// Filter
let data = {}
function filter(){
    data = {}
    let cat = document.querySelectorAll("#category .form-check-input:checked")
    let author = document.querySelectorAll("#author .form-check-input:checked")
    let sort = document.querySelector("#sort .form-check-input:checked")
    
    // Category
    let catString = ""
    for(let i = 0; i < cat.length; i++)
        catString += cat[i].value + ","
    if(catString != "")
        data['type'] = catString.slice(0, -1)


    // Author
    let authorString = ""
    for(let i = 0; i < author.length; i++)
        authorString += author[i].value + ","

    if(authorString != "")
        data['author'] = authorString.slice(0, -1)

    data['min'] = minLikes.value
    data['max'] = maxLikes.value
    let likesError = document.getElementById("likesError")
    if(isNaN(parseInt(data['min']))){
        likesError.innerText = "Minimum likes must be a valid number"
        return
    }
    else
        likesError.innerText = ""
    if(isNaN(parseInt(data['max']))){
        likesError.innerText = "Maximum likes must be a valid number"
        return
    }
    else
        likesError.innerText = ""

    let tag = document.getElementById("wrapper").dataset.id 
    data['tags'] = tag

    // Sort
    if(sort)
        data['sortBy'] = sort.value

    let beginDate = document.getElementById("fromDate").value
    let endDate = document.getElementById("toDate").value
    let fromError = document.getElementById("fromError")
    let toError = document.getElementById("toError")
    if(beginDate != "" && !validDate(beginDate)){
        fromError.innerText = "Invalid date format - mm/dd/yyy"
    }
    else if(beginDate != ""){
        data['begin'] = beginDate
        fromError.innerText = ""
    }

    if(endDate != "" && !validDate(toDate)){
        toError.innerText = "Invalid date format - mm/dd/yyy"
    }
    else if(endDate != ""){
        data['end'] = endDate
        toError.innerText = ""
    }

    if(data['begin'] != null && data['end'] != null && new Date(data['begin']) > new Date(data['end'])){
        addFloatingAlert("Begin date must be before end date. Dates changed")
        let placeholder = data['begin']
        data['begin'] = data['end']
        data['end'] = placeholder

        beginDate = data['begin']
        endDate = data['end']
    }

    data['ppp'] = 3;
    data['page'] = 1;
    
    sendGetAjaxRequest('GET', `/api/search/posts`,data, 'text/html', filterHandler, null)
}

function updateLinkHandlers() {
    $("#news .page-link").off('click')
    $("#news .page-link").click( function(event) {
        pageLinkClickHandler(event,'news')
    });

    $("#opinions .page-link").off('click')
    $("#opinions .page-link").click( function(event) {
        pageLinkClickHandler(event,'opinions')
    });
}

async function pageLinkClickHandler(event,id) {
    event.preventDefault();
    let data = await fetchHTMLfromTarget(event.currentTarget);
    let tableBody = document.querySelector(`#${id} div`);
    if(id == 'opinions'){
        let div = document.createElement("div"); 
        div.innerHTML = data;
        tableBody.innerHTML = div.firstElementChild.nextElementSibling.innerHTML
    }
    else if(id == 'news'){
        let div = document.createElement("div"); 
        div.innerHTML = data;
        tableBody.innerHTML = div.firstElementChild.innerHTML
    }
    updateLinkHandlers();
}

///////////////
let dateChange = function(e){
    if(new Date(e.date).toDateString() != new Date(e.oldDate).toDateString())
        filter()
}

addEventListeners()
setDefault()

function addEventListeners(){
    let checkInputs = document.querySelectorAll("#sidebar .form-check-input")
    for(let i = 0; i < checkInputs.length; i++)
        checkInputs[i].addEventListener('click', filter)

    minLikes.addEventListener('change', filter)
    maxLikes.addEventListener('change', filter)
    slider.noUiSlider.on('set', filter)

    $("#fromFilter").on("change.datetimepicker",dateChange)
    $("#toFilter").on("change.datetimepicker",dateChange)
}

function removeEventListeners(){
    let checkInputs = document.getElementsByClassName("form-check-input")
    for(let i = 0; i < checkInputs.length; i++)
        checkInputs[i].removeEventListener('click', filter)

    minLikes.removeEventListener('change', filter)
    maxLikes.removeEventListener('change', filter)
    slider.noUiSlider.off('set')

    $("#fromFilter").off("change.datetimepicker",dateChange)
    $("#toFilter").off("change.datetimepicker",dateChange)
}

function filterHandler(res, _event){
    switch (res.status) {
        case 200:
            res.text().then((html) => {
                let cats = document.querySelectorAll("#category .form-check-input:checked")
                cat = []
                for(let i = 0; i < cats.length; i++)
                    cat.push(cats[i].value)
                
                setFilterResults(html, cat)

                let tag = window.location.pathname.split('/')[2]
                delete data['tags']
                window.history.pushState({"html":html, "category": cat},"", `${tag}?` + encodeForAjax(data));
                updateLinkHandlers()
            })
            break;
        default:
            addFloatingAlert("An unexpected error occurred. Please consider reloading the page")
            break;
    }
}

window.onpopstate = function(e){
    if(e.state){
        setFilterResults(e.state.html, e.state.category);
        setDefault()
    }
};

function setFilterResults(html, cat){
    var div = document.createElement("div"); 
    div.innerHTML = html;
    newNews = div.firstElementChild
    newOpinions = newNews.nextElementSibling.innerHTML
    newNews = newNews.innerHTML

    let news = document.querySelector("#news > div")
    let opinion = document.querySelector("#opinions > div")
    news.innerHTML = newNews
    opinion.innerHTML = newOpinions

    if(cat.length == 1){
        if(cat[0] == "News")
            opinion.parentElement.classList.add("d-none")
        else
            news.parentElement.classList.add("d-none")
    }
    else{
        news.parentElement.classList.remove("d-none")
        opinion.parentElement.classList.remove("d-none")
    }
    sidebar()
}

function setDefault(){

    let news = document.querySelector("#news > div")
    let opinion = document.querySelector("#opinions > div")
    removeEventListeners()
    let urlParams = new URLSearchParams(window.location.search)

    // Categories
    if(urlParams.has('type')){
        let cat = urlParams.get('type').split(',')
        if(cat.includes('News')){
            document.getElementById('newsCheckBox').checked = true
            news.parentElement.classList.remove("d-none")
        }
        else{
            document.getElementById('newsCheckBox').checked = false
            news.parentElement.classList.add("d-none")
        }
        if(cat.includes('Opinion')){
            document.getElementById('opinionsCheckBox').checked = true
            opinion.parentElement.classList.remove("d-none")
        }
        else{
            document.getElementById('opinionsCheckBox').checked = false
            opinion.parentElement.classList.add("d-none")
        }
    }
    else{
        document.getElementById('newsCheckBox').checked = false
        document.getElementById('opinionsCheckBox').checked = false

        news.parentElement.classList.remove("d-none")
        opinion.parentElement.classList.remove("d-none")
    }

    // Author
    if(urlParams.has('author')){
        let author = urlParams.get('author').split(',')
        if(document.getElementById('subscribedCheckBox'))
            if(author.includes('subscribed'))
                document.getElementById('subscribedCheckBox').checked = true
            else
                document.getElementById('subscribedCheckBox').checked = false
        if(author.includes('verified'))
            document.getElementById('verifiedCheckBox').checked = true
        else
            document.getElementById('verifiedCheckBox').checked = false
    }
    else{
        if(document.getElementById('subscribedCheckBox'))
            document.getElementById('subscribedCheckBox').checked = false
        document.getElementById('verifiedCheckBox').checked = false
    }

    slider.noUiSlider.set([urlParams.get('min'),urlParams.get('max')])

    // Date
    if(urlParams.has('begin')){
        document.getElementById("fromDate").value = urlParams.get('begin')
    }
    else{
        document.getElementById("fromDate").value = ""
    }
    if(urlParams.has('end')){
        document.getElementById("toDate").value = urlParams.get('end')
    }
    else{
        document.getElementById("toDate").value = ""
    }

    if(urlParams.has('sortBy')){
        let sortBy = urlParams.get('sortBy')
        if(sortBy.includes('alpha'))
            document.getElementById('sortAlpha').checked = true
        else if(sortBy.includes('numerical'))
            document.getElementById('sortNumer').checked = true
        else if(sortBy.includes('recent'))
            document.getElementById('sortRecent').checked = true
        else{
            document.getElementById('sortAlpha').checked = false
            document.getElementById('sortNumer').checked = false
            document.getElementById('sortRecent').checked = false
        }
    }
    else{
        document.getElementById('sortAlpha').checked = false
        document.getElementById('sortNumer').checked = false
        document.getElementById('sortRecent').checked = false
    }

    addEventListeners()
}

updateLinkHandlers()
