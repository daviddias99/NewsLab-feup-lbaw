
let simplemde = new EasyMDE({
    element: document.getElementById("postBodyInput"),
    renderingConfig: {
		sanitizerFunction: function(renderedHTML) {
			return DOMPurify.sanitize(renderedHTML)
		},
	},
});

simplemde.togglePreview()
let htmlBody = document.querySelector(".editor-preview").innerHTML

simplemde.toTextArea();
simplemde = null;
document.getElementById("post_content").innerHTML = htmlBody

let getWindowOptions = function() {
    let width = 500;
    let height = 350;
    let left = (window.innerWidth / 2) - (width / 2);
    let top = (window.innerHeight / 2) - (height / 2);
  
    return [
        'resizable,scrollbars,status',
        'height=' + height,
        'width=' + width,
        'left=' + left,
        'top=' + top,
    ].join();
}

let twitterBtn = document.querySelector('.twitter')
let text = document.querySelector('.blog-post-title').innerHTML
if(text.length > 100){
    text = text.substr(0, 100)
    text += "..."
}

let tags = document.querySelectorAll(".blog-post .tag")
let tagString = ""
for(let i = 0; i < tags.length; i++)
    tagString += tags[i].innerHTML + ","
let hashtags = encodeURIComponent(tagString.substr(0, tagString.length - 1))
text = encodeURIComponent(text)
let shareUrl = 'https://twitter.com/intent/tweet?url=' + location.href + '&text=' + text + '&hashtags=' + hashtags;
twitterBtn.href = shareUrl; 
twitterBtn.addEventListener('click', function(e) {
    e.preventDefault();
    let win = window.open(shareUrl, 'ShareOnTwitter', getWindowOptions());
    win.opener = null; 
});


let facebookBtn = document.querySelector(".facebook")
let fbShareUrl = 'https://www.facebook.com/sharer/sharer.php?u=' + location.href
facebookBtn.href = fbShareUrl; 
facebookBtn.addEventListener('click', function(e) {
    e.preventDefault();
    let win = window.open(fbShareUrl, 'ShareOnFacebook', getWindowOptions());
    win.opener = null; 
});

////////////////////////////////////////////
// Order comments

let orderOpts = document.querySelectorAll("div[aria-labelledby=dropdownMenuFilterOptsButton] a")
Array.from(orderOpts).forEach(opt => {
    opt.addEventListener('click', function(event){
        let id = document.querySelector("#blog-main > .blog-post").getAttribute("data-id");
        if(!validPositiveInt(id)){
            addFloatingAlert("Invalid post id. Please consider reloading the page")
            event.preventDefault()
            return
        }

        let orderVal = opt.getAttribute('href').slice(1)
        if(orderVal != "old" && orderVal != "recent" && orderVal != "numerical"){
            addFloatingAlert("Invalid order value. Please consider reloading the page and not messing with the HTML code")
            event.preventDefault()
            return
        }

        sendGetAjaxRequest('GET', `/api/posts/${id}/comments_replies`, {order: orderVal}, 'text/html', orderHandler, event)
    });
});

function orderHandler(res, _event){
    switch (res.status) {
        case 200:
            res.text().then((html) => {
                document.querySelector(".order").innerHTML = html
                updateListeners()
                updateRateListeners()
            })
            break;
        default:
            addFloatingAlert("Something went wrong, the comments could not be ordered");
            break;
    }  
}