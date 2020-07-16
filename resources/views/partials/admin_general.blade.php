<!-- General Tab -->
<div class="tab-pane fade show active" id="v-pills-general" role="tabpanel" aria-labelledby="v-pills-general-tab">
    <h1>General</h1>
    <hr class="my-4">
    <h3> Admin Info: </h3>
    <div class="row mt-3">
        <div class="col-sm-6 col-lg-4">
            @if ($info['photo'] != null)
                <div class="square rounded-circle" style="background-image: url('{{URL::asset('storage/images/users/' . $info['photo'] )}}')"></div>
            @else
                <div class="square rounded-circle" style="background-image: url('{{URL::asset('storage/images/users/default.png')}}')"></div>
            @endif
        </div>                
        <div class="col-sm-8 my-auto mx-0">
            <p><strong>Name:</strong> {{$info['name']}}</p>
            <p><strong>Email:</strong> {{$info['email']}}</p>
            <p><strong>Birthday:</strong> {{\Carbon\Carbon::parse($info['birthday'])->format('F j, Y')}}  </p>
            <p><strong>Local:</strong> {{$info['local']['city']}}, {{$info['local']['country']}}</p>
        </div>
    </div>
    <hr class="my-4">
    <div class="admin-statistics">
        <h3> Stats: </h3>
        <div class="row mt-4">
            <div class="col-12 col-sm-6 col-md-3 col text-center px-2"><h5>Reports solved</h5><p class="counter-value">{{$stats['reports_solved']}}</p></div>
            <div class="col-12 col-sm-6 col-md-3 col text-center px-2"><h5>Users banned</h5><p class="counter-value">{{$stats['users_banned']}}</p></div>
            <div class="col-12 col-sm-6 col-md-3 col text-center px-2"><h5>Posts deleted</h5><p class="counter-value">{{$stats['posts_deleted']}}</p></div>
            <div class="col-12 col-sm-6 col-md-3 col text-center px-2"><h5>Comments deleted</h5><p class="counter-value">{{$stats['comments_deleted']}}</p></div>
        </div>
    </div>
</div>