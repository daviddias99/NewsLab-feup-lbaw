function validShortText(body) {
    return /^[A-Za-z0-9?+*_!#$%,\/;.&\s-]{1,500}$/gm.test(body)
}

function validSearch(query){
    return /^[A-Za-z0-9?+*_!#$%,\/;.&\s-]{2,}$/gm.test(query)
}

function validPositiveInt(integer) {
    return /^[0-9]+$/gm.test(integer)
}

function validUserName(name) {
    return /^[a-zA-Z ]{3,25}$/gm.test(name)
}

function validEmail(email){
    return /^[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,4}$/gm.test(email)
}

function validDate(date){
    return /^(0[1-9]|1[0-2])\/(0[1-9]|[1-2][0-9]|3[0-1])\/[0-9]{4}$/gm.test(date)
}

function validDateTime(dateTime){
    return /^(0[1-9]|1[0-2])\/(0[1-9]|[1-2][0-9]|3[0-1])\/[0-9]{4} ([1-9]|1[0-2]):[0-5][0-9] [A|P]M$/gm.test(dateTime)
}

function validPassword(password){
    return /^(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{8,}$/gm.test(password)
}

function validTitle(title){
    return /^[A-Za-z0-9?+*_!#$%,\/;.&\s-]{3,100}$/gm.test(title)
}