<div class="row">

    @if( count($users) == 0)
        <div class="col-sm-12 mt-12 d-flex justify-content-center">
            <p class="text-muted align-center"> No results.</p>
        </div>
    @endif

    @foreach ( $users as $user )
        <div class="col-sm-3 mt-3">
            <div class="card border-0 text-center mx-auto" style="width: 10rem;">
                <div class="card-header bg-white">
                    @if ($user['photo'] != null)
                        <div class="square rounded-circle" title="Author photo" style="background-image: url('{{URL::asset('storage/images/users/' . $user['photo'] )}}')"></div>
                    @else
                        <div class="square rounded-circle" title="Author photo" style="background-image: url('{{URL::asset('storage/images/users/default.png')}}')"></div>
                    @endif
                </div>
                <div class="card-body px-0">
                    
                        <h5 class="card-title font-weight-bold"><a href="/users/{{$user['id']}}" class="text-decoration-none text-dark">{{$user['name']}}</a><a class="add_admin" data-userid="{{$user['id']}}" href="#"><i class="ml-1 fas fa-plus-square text-primary"></i></a></h5>
                        <p class="text-muted">{{$user['email']}}</p>
                    
                </div>
            </div>
        </div>
    @endforeach
    </div>