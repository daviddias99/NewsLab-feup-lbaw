<div class="col-sm-6 col-md-4 col-lg-3 mt-3">
    <div class="card border-0 text-center mx-auto" style="width: 12rem;">
        <div class="card-header bg-white">
            @if($user['photo'] != null)
            <div class="square rounded-circle" title="User photo"
                style="background-image: url('{{URL::asset('storage/images/users/'. $user['photo'] )}}')"></div>
            @else
            <div class="square rounded-circle" title="User photo"
                style="background-image: url('{{URL::asset('storage/images/users/default.png')}}')"></div>
            @endif
        </div>
        <div class="card-body px-0">
            <a class="text-decoration-none text-dark" href="/users/{{$user['id']}}">

                <h5 class="card-title font-weight-bold">{{$user['name']}}</h5>
            </a>

            <p class="text-muted">{{$user['email']}}</p>
            <button type="button" class="btn btn-outline-secondary unsub-user"
                data-id={{$user['id']}}>Unsubscribe</button>
        </div>
    </div>
</div>