<div class="modal fade" id="pwRecoveryModal" tabindex="-1" role="dialog" aria-labelledby="pwRecoveryModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content p-sm-0 p-md-3">
            <div class="modal-header border-0">
                <h2 class="modal-title" id="pwRecoveryModalTitle">{{ __('Reset Password') }}</h2>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body text-center col">
                <p class="text-lg-left">Don't worry! Enter your email address and check your inbox for instructions to reset your account's password.</p>
                <form class="form-pwrecovery col m-auto p-0" method="POST" action="{{ route('password.email') }}">
                    @csrf

                    <label for="inputEmail" class="col text-left px-0 pb-0 mb-0"><strong>{{ __('Email *') }}</strong></label>
                    <input id="inputEmail" type="email" class="form-control @error('email') is-invalid @enderror" name="email" value="{{ old('email') }}" required autocomplete="email" pattern="[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,4}$" title="Invalid email">
                    <p class="small text-muted text-left mb-0">* Required Input</p>
                    <button type="submit" class="btn btn-primary btn-block mt-3">{{ __('Send Password Reset Link') }}</button>
                </form>
                <p class="mb-0 text-muted mt-4">Sign in <a data-dismiss="modal" data-toggle="modal" data-target="#loginModal" href="">here</a>.</p>

            </div>
        </div>
    </div>
</div>

<script>
let pwRecoveryModal = document.querySelector('#pwRecoveryModal form.form-pwrecovery');
pwRecoveryModal.addEventListener('submit', function(event){
    event.preventDefault();

    let email = document.getElementById("inputEmail")
    let token = document.querySelector("#pwRecoveryModal input[name=_token]")
    fetch('{{ route('password.email') }}', {
        method: 'POST',
        body: JSON.stringify({
            email: email.value,
            _token: token.value 
        }),
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
            'Accept': 'application/json'
        }
    })
   . then((res)=>{
            res.json().then(data => {
                if(data.error == "true"){
                    let p = pwRecoveryModal.querySelector("p.small")
                    p.innerHTML = "Wrong credentials"
                    p.classList.remove("text-muted")
                    p.classList.add("text-primary")
                    p.innerHTML = data.msg
                }
                else {
                    let p = loginForm.querySelector("p.small")
                    p.classList.remove("text-primary")
                    p.classList.add("text-muted")
                    p.innerHTML = "* Required Input"
                    $('#pwRecoveryModal').modal('hide'); 
                    addFloatingAlert(data.msg)
                }
            })
        }       
    );
    
})
</script>