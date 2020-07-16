function openLogin(event) {
    event.preventDefault()
    $('#loginModal').modal({
        show: true
    });
}

const subscribeButton = document.getElementById('subscribe_btn');
if (subscribeButton != null) {
    subscribeButton.addEventListener('click', openLogin);
}

let reportBtns = document.querySelectorAll('a[data-target="#report-modal"]')
for (let reportBtn of reportBtns)
    reportBtn.addEventListener("click", openLogin)