<!-- Modal -->
<div class="modal fade" id="registerModal" tabindex="-1" role="dialog" aria-labelledby="registerModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
        <div class="modal-content p-sm-0 p-md-3">
            <div class="modal-header border-0">
                <h2 class="modal-title" id="registerModalTitle">Sign Up</h2>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body text-center col">
                <form id="registerForm" class="form-signup col m-auto p-0" method="POST" action="{{ route('register') }}">
                    {{ csrf_field() }}
                    <label for="emailInput" class="col text-left px-0 pb-0 mb-0"><strong>Email *</strong></label>
                    <input type="email" id="emailInput" name="email" class="form-control mb-2" value="{{ old('email') }}" required autofocus pattern="[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,4}$" title="Invalid email">
                    <p class="small text-primary text-left mb-0" role="alert" id="emailError">
                    </p>

                    <label for="nameInput" class="col text-left px-0 pb-0 mb-0"><strong>Name *</strong></label>
                    <input type="text" id="nameInput" name="name" class="form-control mb-2" value="{{ old('name') }}" required pattern="[a-zA-Z ]{3,25}" title="Name should contain between 3 and 25 letters and spaces">
                    <p class="small text-primary text-left mb-0" role="alert" id="nameError">
                    </p>

                    <label for="dobInput" class="col text-left px-0 pb-0 mb-0"><strong>Birthday *</strong></label>
                    <div class="input-group date col px-0" id="birthdayInput" data-target-input="nearest">
                        <input type="text" class="form-control mb-2 text-dark datetimepicker-input" id="dobInput" name="birthday" autocomplete="off" data-target="#birthdayInput" value="{{ old('birthday') }}" required pattern="(0[1-9]|1[0-2])/(0[1-9]|[1-2][0-9]|3[0-1])/[0-9]{4}" title="Invalid date format - mm/dd/yyy"/>
                        <div class="input-group-append mb-2" data-target="#birthdayInput" data-toggle="datetimepicker">
                            <div class="input-group-text"><i class="fa fa-calendar"></i></div>
                        </div>
                    </div>
                    <p class="small text-primary text-left mb-0" role="alert" id="birthdayError">
                    </p>

                    <div class="row">
                        <div class="col-sm pr-sm-2 pr-md-1">
                            <label for="passwordInput" class="col text-left px-0 pb-0 mb-0"><strong>Password *</strong></label>
                            <input type="password" id="passwordInput" name="password" class="form-control mb-2" required pattern="(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{8,}" title="Must contain at least one number and one uppercase and lowercase letter, and at least 8 or more characters">
                            <p class="small text-primary text-left mb-0" role="alert" id="passwordError">
                            </p>

                        </div>
                        <div class="col-sm pl-sm-2 pl-md-1">
                            <label for="password_confirmation" class="col text-left px-0 pb-0 mb-0"><strong>Confirm password *</strong></label>
                            <input type="password" id="password_confirmation" name="password_confirmation" class="form-control mb-2" required pattern="(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{8,}" title="Must contain at least one number and one uppercase and lowercase letter, and at least 8 or more characters">
                    
                        </div>
                    </div>
                    <p class="small text-muted text-left mb-0">* Required Input</p>
                    <button class="btn btn-primary btn-block mt-3" type="submit">Sign Up</button>
                </form>

                <hr>

                <p class="mb-0 mt-4 text-muted">Already have an account?</p>
                <p class="mb-1 text-muted">Sign in <a data-dismiss="modal" data-toggle="modal" data-target="#loginModal" href="">here</a>.</p>

            </div>
        </div>
    </div>
</div>

<script>
let registerForm = document.getElementById("registerForm")
registerForm.addEventListener('submit', function(event){
    event.preventDefault();

    let email = document.getElementById("emailInput")
    let name = document.getElementById("nameInput")
    let birthday = document.getElementById("dobInput")
    let password = document.getElementById("passwordInput")
    let passwordConf = document.getElementById("password_confirmation")

    document.getElementById("emailError").innerText = ""
    document.getElementById("nameError").innerText = ""
    document.getElementById("birthdayError").innerText = ""
    document.getElementById("passwordError").innerText = ""


    fetch('{{ route('register') }}', {
        method: 'POST',
        body: JSON.stringify({
            email: email.value,
            name: name.value,
            birthday: birthday.value,
            password: password.value,
            password_confirmation: passwordConf.value,
        }),
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
            'Accept': 'application/json'
        }
    })
   . then((res)=>{

        if(res.status == 422){
            res.json().then(data => {

                let errors = data.errors;
                Object.keys(errors).forEach(function (key) {
                    $("#" + key + "Error").text(errors[key][0]);
                });
            })
        }
        else if(res.status = 200){
            window.location.reload()
        }       
    });
    
})

$(function () {
    $('#birthdayInput').datetimepicker({
        format: 'L',
        buttons: {
            showClose: true,
            showClear: true
        },
        viewMode: 'years',
        icons: {
            clear: 'fa fa-trash',
        },
        useCurrent: false,

    });
});
</script>