<!-- Modal -->
<div class="modal fade" id="loginModal" tabindex="-1" role="dialog" aria-labelledby="loginModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content p-sm-0 p-md-3">
            <div class="modal-header border-0">
                <h2 class="modal-title" id="loginModalTitle">Sign In</h2>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body text-center col">

                <form class="form-signin col m-auto p-0" method="POST" action="{{ route('login') }}">

                    {{-- TODO: UNCOMMENT --}}
                    {{-- {{ csrf_field() }} --}}

                    <label for="emailLogin" class="col text-left px-0 pb-0 mb-0"><strong>Email *</strong></label>
                    <input type="email" id="emailLogin" name="email" class="form-control mb-3" placeholder="Email" value="{{ old('email') }}" required pattern="[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,4}$" title="Invalid email">

                    <label for="passwordLogin" class="col text-left px-0 pb-0 mb-0"><strong>Password *</strong></label>
                    <input type="password" id="passwordLogin" name="password" class="form-control mb-2" placeholder="Password" required pattern="(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{8,}" title="Must contain at least one number and one uppercase and lowercase letter, and at least 8 or more characters">

                    <p class="small text-muted text-left mb-0">* Required Input</p>

                    <a href="" data-dismiss="modal" data-toggle="modal" data-target="#pwRecoveryModal" class="m-1 text-muted">Forgot your password?</a>
                    <input type="hidden" name="current_page" value="{{Request::getRequestUri()}}">
                    <button class="btn btn-primary btn-block mt-3" type="submit">Sign in</button>
                </form>

                <hr>
                
                <p class="mb-0 mt-4 text-muted">Don't have an account?</p>
                <p class="mb-1 text-muted">Sign up <a data-dismiss="modal" data-toggle="modal" data-target="#registerModal" href="">here</a>.</p>

            </div>
        </div>
    </div>
</div>


<script>
let loginForm = document.querySelector('form.form-signin');
loginForm.addEventListener('submit', function(event){
    event.preventDefault();

    let email = document.getElementById("emailLogin")
    let password = document.getElementById("passwordLogin")

    fetch('{{ route('login') }}', {
        method: 'POST',
        body: JSON.stringify({
            email: email.value,
            password: password.value,
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
                let p = loginForm.querySelector("p.small")
                p.innerHTML = "Wrong credentials"
                p.classList.remove("text-muted")
                p.classList.add("text-primary")
            })
        }
        else if(res.status = 200){
            let p = loginForm.querySelector("p.small")
            if(p.classList.contains("text-primary")){
                p.classList.remove("text-primary")
                p.classList.add("text-muted")
                p.innerHTML = "* Required Input"
            }

            window.location.reload()
        }       
    });
    
})

</script>
