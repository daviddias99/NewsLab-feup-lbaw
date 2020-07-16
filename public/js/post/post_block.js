function openLogin(event) {
    event.preventDefault()
    $('#loginModal').modal({
        show: true
    });
}

// Deactivate Write Comment
let sendComment = document.querySelector("#comment-form button")
sendComment.addEventListener("click", openLogin)

let commentDiv = document.getElementById("commentArea")
commentDiv.addEventListener("focus", openLogin)

function updateListeners(){
    // Deactivate Reply
    let replyButtons = document.querySelectorAll("button.reply")
    for (let replyButton of replyButtons){
        replyButton.addEventListener("click", openLogin)
        replyButton.setAttribute("data-target", "")
    }
    
    let dropdownItems1 = document.querySelectorAll(".post-head .dropdown-item")
    for (let dropdownItem of dropdownItems1)
        dropdownItem.addEventListener("click", openLogin)
    
    
    let dropdownItems2 = document.querySelectorAll(".comment-area .dropdown-item")
    for (let dropdownItem of dropdownItems2)
        dropdownItem.addEventListener("click", openLogin)
    
    let reportBtns = document.querySelectorAll('a[data-target="#report-modal"]')
    for (let reportBtn of reportBtns)
        reportBtn.addEventListener("click", openLogin)
}

updateListeners()