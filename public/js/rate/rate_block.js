function openLogin(event) {
    event.preventDefault()
    $('#loginModal').modal({
        show: true
    });
}

function updateRateListeners(){
    // Deactivate Ratings
    let downVotes = document.querySelectorAll("input[value=dislike]")
    let upVotes = document.querySelectorAll("input[value=like]")

    for (let downVote of downVotes)
        downVote.addEventListener("click", openLogin)

    for (let upVote of upVotes)
        upVote.addEventListener("click", openLogin)
}

updateRateListeners()