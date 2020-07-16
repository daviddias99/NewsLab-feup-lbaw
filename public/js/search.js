sideFilter.style.position = 'fixed'
openBtn.style.position = 'fixed'


function sidebar(){
    if(yOff() < 0)
        sideFilter.style.maxHeight = Math.min(vh(100) - convertRemToPixels(4) + yOff(), document.getElementById("wrapper").offsetHeight + convertRemToPixels(1.5)) + 'px'
    else
        sideFilter.style.maxHeight = Math.min(vh(100) - convertRemToPixels(4), document.getElementById("wrapper").offsetHeight + convertRemToPixels(1.5)) + 'px'
}

window.addEventListener('scroll', function(){
    sidebar()
})


function updateLinkHandlers() {
    $("#news .page-link").off('click')
    $("#news .page-link").click( function(event) {
        pageLinkClickHandler(event,'news')
    });

    $("#opinions .page-link").off('click')
    $("#opinions .page-link").click( function(event) {
        pageLinkClickHandler(event,'opinions')
    });

    $("#users .page-link").off('click')
    $("#users .page-link").click( function(event) {
        pageLinkClickHandler(event,'users')
    });
}

async function pageLinkClickHandler(event,id) {
    event.preventDefault();
    let data = await fetchHTMLfromTarget(event.currentTarget);
    let tableBody = document.querySelector(`#${id} div.tab-pane`);
    if(id == 'users')
        tableBody.innerHTML = data
    else if(id == 'opinions'){
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

//////////////////////

document.querySelector("#search-form").classList.add("d-none")
let searchBtn = document.querySelector("#search-big a")
function searchHandler(event){
    event.preventDefault()
    filter()
}

let fromDate = document.getElementById("fromDate")
let endDate = document.getElementById("toDate")

let dateChange = function(e){
    if(new Date(e.date).toDateString() != new Date(e.oldDate).toDateString())
        filter()
}

function addEventListeners(){
    searchBtn.addEventListener('click', searchHandler)

    let checkInputs = document.getElementsByClassName("form-check-input")
    for(let i = 0; i < checkInputs.length; i++)
        checkInputs[i].addEventListener('click', filter)

    minLikes.addEventListener('change', filter)
    maxLikes.addEventListener('change', filter)
    slider.noUiSlider.on('set', filter)

    $("#fromFilter").on("change.datetimepicker",dateChange)
    $("#toFilter").on("change.datetimepicker",dateChange)
}

function removeEventListeners(){
    searchBtn.removeEventListener('click', searchHandler)

    let checkInputs = document.getElementsByClassName("form-check-input")
    for(let i = 0; i < checkInputs.length; i++)
        checkInputs[i].removeEventListener('click', filter)

    minLikes.removeEventListener('change', filter)
    maxLikes.removeEventListener('change', filter)
    slider.noUiSlider.off('set')

    $("#fromFilter").off("change.datetimepicker",dateChange)
    $("#toFilter").off("change.datetimepicker",dateChange)
}


let news = document.getElementById("news")
let opinions = document.getElementById("opinions")
let users = document.getElementById("users")
let tags = document.getElementById("tags")
let data = {}
let arrive = 0
function filter(){
    data = {}
    arrive =  0

    let searchError = document.getElementById("searchError")
    let inputSearch = document.querySelector("#search-big input")
    if(inputSearch.value != "" && !validSearch(inputSearch.value)){
        searchError.innerText = "Search must have at least 2 letters, numbers or symbols like ?+*_!#$%,\/;.&-"
        return
    }
    else if(inputSearch.value != ""){
        data['search'] = inputSearch.value
        searchError.innerText = ""
    }

    let cat = document.querySelectorAll("#category .form-check-input:checked")
    let author = document.querySelectorAll("#author .form-check-input:checked")
    let sort = document.querySelector("#sort .form-check-input:checked")
    
    // Category
    let typeString = ""
    let catString = ""
    let hasTags = false, hasUsers = false, hasOpinions = false, hasNews = false
    for(let i = 0; i < cat.length; i++){
        switch (cat[i].value) {
            case "news":
                typeString += "News,"
                catString += "News,"
                hasNews = true
                break;
            case "opinions":
                typeString += "Opinion,"
                catString += "Opinion,"
                hasOpinions = true
                break;
            case "authors":
                catString += "User,"
                hasUsers = true
                break;
            case "tags":
                catString += "Tag,"
                hasTags = true
                break;
            default:
                addFloatingAlert("Invalid category: " + cat[i].value)
                return;
        }   
    }
    
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


    // Sort
    if(sort)
        data['sortBy'] = sort.value

    if(cat.length == 0 || hasTags){
        arrive++
        sendGetAjaxRequest('GET', `/api/search/tags`, data, 'text/html', tagsHandler, null)
    }

    // Author
    let authorString = ""
    for(let i = 0; i < author.length; i++)
        authorString += author[i].value + ","

    if(authorString != "")
        data['author'] = authorString.slice(0, -1)
    
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

    if(cat.length == 0 || hasUsers){
        arrive++
        sendGetAjaxRequest('GET', `/api/search/users`, data, 'text/html', usersHandler, null)
    }
    
    if(typeString != "")
        data['type'] = typeString.slice(0, -1)
    if(cat.length == 0 || typeString != ""){
        arrive++
        sendGetAjaxRequest('GET', `/api/search/posts`, data, 'text/html', postsHandler, null)
    }
    if(catString != "")
        data['cat'] = catString.slice(0, -1)

    openCategories(hasNews, hasOpinions, hasUsers, hasTags)
}

addEventListeners()
setDefault()

function openCategories(hasNews, hasOpinions, hasUsers, hasTags){
    if(!hasNews && !hasOpinions && !hasUsers && !hasTags){
        news.classList.remove("d-none")
        opinions.classList.remove("d-none")
        users.classList.remove("d-none")
        tags.classList.remove("d-none")
    }
    else{
        if(!hasNews)
            news.classList.add("d-none")
        else
            news.classList.remove("d-none")
        
        if(!hasOpinions)
            opinions.classList.add("d-none")
        else
            opinions.classList.remove("d-none")

        if(!hasUsers)
            users.classList.add("d-none")
        else
            users.classList.remove("d-none")

         if(!hasTags)
            tags.classList.add("d-none")
        else
            tags.classList.remove("d-none")
    }
}

function tagsHandler(res, event){
    switch (res.status) {
        case 200:
            res.text().then((html) => {
                tagsHTML(html)
                addToStack({"html_tags":html}, 'search?' + encodeForAjax(data))
                updateLinkHandlers()
            })
            break;
        default:
            addFloatingAlert("An unexpected error occurred. Please consider reloading the page")
            break;
    }
}

function usersHandler(res, _event){
    switch (res.status) {
        case 200:
            res.text().then((html) => {
                usersHTML(html)
                addToStack({"html_users":html}, 'search?' + encodeForAjax(data))
                updateLinkHandlers()

            })
            break;
        default:
            addFloatingAlert("An unexpected error occurred. Please consider reloading the page")
            break;
    }
}

function postsHandler(res, _event){
    switch (res.status) {
        case 200:
            res.text().then((html) => {
                postsHTML(html)
                addToStack({"html_posts":html}, 'search?' + encodeForAjax(data))
                updateLinkHandlers()

            })
            break;
        default:
            addFloatingAlert("An unexpected error occurred. Please consider reloading the page")
            break;
    }
}

let newState = {}
function addToStack(state, url){
    arrive--

    for (var key in state)
        newState[key] = state[key]

    if(arrive != 0)
        return
    newState['cat'] = data['cat']
    window.history.pushState(newState, "", url)
    newState = {}
}

function tagsHTML(html){
    var div = document.createElement("div"); 
    div.innerHTML = html;
    newTag = div.firstElementChild.innerHTML

    let subTags = tags.querySelector("div.tags-sect")
    subTags.innerHTML = newTag
    sidebar()
}

function usersHTML(html){
    var div = document.createElement("div"); 
    div.innerHTML = html;
    newUsers = div.innerHTML

    let subUsers = users.querySelector("div.tab-pane")
    subUsers.innerHTML = newUsers
    sidebar()
}

function postsHTML(html){
    var div = document.createElement("div"); 
    div.innerHTML = html;
    newNews = div.firstElementChild
    newOpinions = newNews.nextElementSibling.innerHTML
    newNews = newNews.innerHTML

    let subNews = news.querySelector("div.tab-pane")
    let subOpinion = opinions.querySelector("div.tab-pane")
    subNews.innerHTML = newNews
    subOpinion.innerHTML = newOpinions

    sidebar()
}

window.onpopstate = function(e){
    if(e.state){
        if(e.state.html_posts)
            postsHTML(e.state.html_posts)
        if(e.state.html_users)
            usersHTML(e.state.html_users)
        if(e.state.html_tags)
            tagsHTML(e.state.html_tags)
    }
    if(e.state && e.state.cat){
        let res = e.state.cat.split(",")
        openCategories(res.includes("News"), res.includes("Opinion"), res.includes("User"), res.includes("Tag"))        
    }
    else
        openCategories(true, true, true, true)
    setDefault()
    updateLinkHandlers()
};

function setDefault(){
    removeEventListeners()
    let urlParams = new URLSearchParams(window.location.search)

    // Search
    document.querySelector("#search-big input").value = urlParams.get('search')


    // Categories
    if(urlParams.has('cat')){
        let cat = urlParams.get('cat').split(',')
        if(cat.includes('News'))
            document.getElementById('newsCheckBox').checked = true
        else
            document.getElementById('newsCheckBox').checked = false
        if(cat.includes('Opinion'))
            document.getElementById('opinionsCheckBox').checked = true
        else
            document.getElementById('opinionsCheckBox').checked = false
        if(cat.includes('User'))
            document.getElementById('authorsCheckBox').checked = true
        else
            document.getElementById('authorsCheckBox').checked = false
        if(cat.includes('Tag'))
            document.getElementById('tagsCheckBox').checked = true
        else
            document.getElementById('tagsCheckBox').checked = false
        openCategories(cat.includes('News'), cat.includes('Opinion'), cat.includes('User'), cat.includes('Tag'))
    }
    else{
        document.getElementById('newsCheckBox').checked = false
        document.getElementById('opinionsCheckBox').checked = false
        document.getElementById('authorsCheckBox').checked = false
        document.getElementById('tagsCheckBox').checked = false
        openCategories(true, true, true, true)
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