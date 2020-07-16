$(function () {
    $('#birthdayInput').datetimepicker({
        format: 'L',
        buttons: {
            showClose: true,
            showClear: true
        },
        date: moment(document.querySelector("#birthdayInput input").defaultValue),
        icons: {
            clear: 'fa fa-trash',
        }
    });
});


/////////////////////////////////////////////////////////

let cities = document.getElementById("cityInput")
let countries = document.getElementById("countryInput")
countries.addEventListener('change', function(){
    let id = parseInt(countries.value)
    if(id < 0){
        cities.parentElement.parentElement.classList.add('d-none')
    }
    else{
        cities.parentElement.parentElement.classList.remove('d-none')
        for(let i = 0; i < cities.childElementCount; i++){
            if(parseInt(cities.children[i].dataset.country) != id)
                cities.children[i].classList.add('d-none')
            else
                cities.children[i].classList.remove('d-none')
        }
        cities.children[0].classList.remove('d-none')
        // cities.children[0].setAttribute('selected', 'selected')
        cities.value = -1
    }
})

let submitBtn = document.querySelector("#submitBtn")
let form = document.querySelector("main form") 
submitBtn.addEventListener("click", function(event){
    event.preventDefault()
    let hasError = false

    let formData = new FormData();

    // Name
    let nameInput = document.getElementById("nameInput")
    let nameError = document.getElementById("nameError")
    if(!validUserName(nameInput.value)){
        nameError.innerText = nameInput.value == "" ? "Name cannot be empty" : "Name should contain between 3 and 25 letters (and spaces)"
        hasError = true
    }
    else{
        formData.append('name', nameInput.value)
        nameError.innerText = ""
    }

    // Email
    let emailInput = document.getElementById("emailInput")
    let emailError = document.getElementById("emailError")
    if(!validEmail(emailInput.value)){
        emailError.innerText = emailInput.value == "" ? "Email cannot be empty" : "Invalid email format"
        hasError = true
    }
    else{
        formData.append('email', emailInput.value)
        emailError.innerText = ""
    }

    // Birthday
    let birthdayInput = document.querySelector(".datetimepicker-input")
    let birthdayError = document.getElementById("birthdayError")
    if(!validDate(birthdayInput.value)){
        birthdayError.innerText = birthdayInput.value == "" ? "Birthday cannot be empty" : "Invalid date format - mm/dd/yyy"
        hasError = true
    }
    else if(moment().diff(new Date(birthdayInput.value), 'years') < 13){
        birthdayError.innerText = "You must be at least 13yo"
        hasError = true
    }
    else{
        formData.append('birthday', birthdayInput.value)
        birthdayError.innerText = ""
    }

    //Old Password
    let passInput = document.getElementById("passInput")
    let passError = document.getElementById("passError")
    if(!validPassword(passInput.value)){
        passError.innerText = passInput.value == "" ? "Please validate your password" : "Password must contain at least one number and one uppercase and lowercase letter, and at least 8 or more characters"
        hasError =true
    }
    else{
        formData.append('password', passInput.value)
        passError.innerText = ""
    }

    //New Password
    let newPassInput = document.getElementById("newPassInput")
    let newPassError = document.getElementById("newPassError")

    let confirmPassInput = document.getElementById("confirmPassInput")
    let confirmPassError = document.getElementById("confirmPassError")

    if(newPassInput.value != "" && validPassword(newPassInput.value)){
        if(confirmPassInput.value == ""){
            confirmPassError.innerText = "Please confirm your password"
            hasError = true
        }
        else if(confirmPassInput.value != newPassInput.value){
            confirmPassError.innerText = "New password and confirmation do not match"
            hasError = true
        }
        else{
            formData.append('confirmPass', confirmPassInput.value)
            formData.append('newPass', newPassInput.value)
            newPassError.innerText = ""
            confirmPassError.innerText = ""
        }
    }
    else if(newPassInput.value != ""){
        newPassError.innerText = "Password must contain at least one number and one uppercase and lowercase letter, and at least 8 or more characters"
        hasError = true
    }
    else if(confirmPassInput.value != ""){
        newPassError.innerText = "New password and confirmation do not match"
        hasError = true
    }

    // Country+City
    let country = parseInt(countries.options[countries.selectedIndex].value)
    let city = parseInt(cities.options[cities.selectedIndex].value)
    let cityError = document.getElementById("cityError")    
    if(country > 0 && city < 0){
        cityError.innerText = "You must select a city"
        hasError = true
    }
    else if(country > 0 && city > 0){
        if(cities.options[cities.selectedIndex].dataset.country != country){
            cityError.innerText = "City and country do not match"
            hasError = true
        }
        else{
            cityError.innerText = ""
            formData.append('city', city)
            formData.append('country', country)
        }
    }

    //Bio
    let bioInput = document.getElementById("bioInput")
    let bioError = document.getElementById("bioError")
    if(bioInput.value != "" && (!validShortText(bioInput.value) || bioInput.value.length < 5)){
        bioError.innerText = "Biography must have between 6 and 500 letters, numbers or symbols like ?+*_!#$%,\/;.&-"
        hasError = true
    }
    else if(bioInput.value != ""){
        formData.append('bio', bioInput.value)
        bioError.innerText = ""
    }

    if(hasError){
        addFloatingAlert("Something went wrong with the form submission")
        return
    }


	let photo = document.getElementById("profilePic")
    let hasNewFile = photo.getAttribute('data-hasnewfile')
    
    if (!(photo.files[0] === undefined || photo.files[0] === null))
        formData.append('photo', photo.files[0])

    formData.append('hasNewFile', hasNewFile)

	sendAjaxSerializedRequest(form.getAttribute("method"), form.getAttribute("action"), formData, "application/json", editHandler)
})

function editHandler(res){
    switch (res.status) {
        case 200:

            addFloatingAlert("Profile edit saved")
            res.json().then((data) => {
                window.location.replace(`/users/${data['id']}`);
            })
            break;
        default:
            addFloatingAlert("Something went wrong with form submission")

            res.json().then((data) => {

                document.getElementById("passInput").value = ""
                let errors = data.errors;
                Object.keys(errors).forEach(function (key) {
                    $("#" + key + "Error").text(errors[key][0]);
                });
            })
            break;
    }

}

/////////////////////////////////////////////////////////

const reader = new FileReader();
let bannerImage = document.getElementById("profileImage")

let editImageInput = document.getElementById("profilePic")
editImageInput.addEventListener("change", function(event) {
	document.getElementById("photoError").innerText = ""
	const f = event.target.files[0]
    editImageInput.setAttribute('data-hasnewfile', "yes")
    reader.readAsDataURL(f)
})

reader.addEventListener('load', function(event) {
    bannerImage.style.backgroundImage = "url('" + event.target.result + "')"
})

let deleteImageButton = document.getElementById("deleteImageButton")
deleteImageButton.addEventListener("click", function(event) {
	event.preventDefault()
	document.getElementById("photoError").innerText = ""
	editImageInput.value = null
	editImageInput.setAttribute('data-hasnewfile', "no");
	bannerImage.style.backgroundImage = "url('" + editImageInput.getAttribute('data-defaultimgpath') + "')"
})

/////////////////////////////////////////////////////////

let confirmBtn = document.getElementById("confBtn")
confirmBtn.addEventListener("click", function(event){
    event.preventDefault()
    let data = {}  
     //Old Password
    let passInput = document.getElementById("passInput")
    let passError = document.getElementById("passError")
    if(!validPassword(passInput.value)){
        $('#confirmDeletion').modal('hide'); 
        addFloatingAlert("You must validate your password before deleting your account")
        passError.innerText = "Please validate your password"
        return        
    }

    data['password'] = passInput.value
    passError.innerText = ""

    sendAjaxRequest('DELETE', '/api' +  form.getAttribute("action"), data, 'application/json', deleteHandler, event)
})

function deleteHandler(res, event){

    switch (res.status) {
        case 200:
            window.location.replace('/home')
            addFloatingAlert("Account successfully deleted")
            break;
        default:
            res.json().then((data) => {
                $('#confirmDeletion').modal('hide'); 
                addFloatingAlert(data.message)
            })
            break;
    }
}