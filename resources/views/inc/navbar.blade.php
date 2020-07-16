<nav class="navbar navbar-expand-lg navbar-dark bg-primary flex-shrink-0">
		
    <div class="container">
        <a id="left-log" class="navbar-brand p-0 mr-auto mr-4 " href="/home">
            <img src={{URL::asset('storage/images/other/logo_white.png')}} alt="logo">
        </a>
        
        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarToggler" aria-controls="navbarToggler" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarToggler">
            <ul class="navbar-nav  d-flex flex-row">
                <li class="nav-item">
                    <a class="nav-link pr-2" style="font-size: 0.9em ;letter-spacing: 2px" href="/news"><span class="border-right px-3">NEWS</span></a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" style="font-size: 0.9em ;letter-spacing: 2px" href="/opinions">OPINIONS</a>
                </li>
                @if (Auth::check())
                    <li class="nav-item">
                        <a class="nav-link pl-2" style="font-size: 0.9em ;letter-spacing: 2px" href="/feed"><span class="border-left px-3">FEED</span></a>
                    </li>
                @endif
            </ul>

            <a id="middle-log" class="navbar-brand p-0 mx-auto mr-4 " href="/home">
                <img src={{URL::asset('storage/images/other/logo_white.png')}} alt="logo">
            </a>

            <div class="navbar-nav">
                <form id="search-form" class="form-inline mt-0">
                    <div class="input-group">
                        <a class="input-group-prepend nav-link" href="/search">
                            <i class="fas fa-search" aria-hidden="true"></i>
                        </a>
                        <input class="bg-primary my-1_5" type="search" placeholder="Search..." aria-label="Search" pattern="[A-Za-z0-9?+*_!#$%,\/;.&\s-]{2,}" title="Search must have at least 2 letters, numbers or symbols like ?+*_!#$%,\/;.&-">
                    </div>
                </form>

                <div class="d-flex flex-row justify-content-around justify-content-md-start justify-content-lg-end align-items-center">
                    @if (Auth::check())
                    @if (!Auth::user()->banned)
                        <div class="nav-item px-1">
                            <a class="nav-link px-2" href="/posts/create" aria-label="Edit Post">
                                <i data-toggle="tooltip" data-placement="bottom" title="Create post" class="far fa-edit"></i>
                            </a>
                        </div>
                    @endif
                    <div class="nav-item px-1">
                    <a class="nav-link px-2" href="/users/{{Auth::user()->id}}/saved_posts" aria-label="Bookmark">
                            <i data-toggle="tooltip" data-placement="bottom" title="Saved posts" class="far fa-bookmark"></i>
                        </a>
                    </div>
                    @endif

                    <div class="nav-item px-1 dropdown">
                        <a class="nav-link px-2 dropdown-toggle pt-1 align-items-center d-flex" id="dropdownMenuNavButton" href="#" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            @if (Auth::check())
                                @if(Auth::user()->photo !== null)
                                    <img src="{{URL::asset('storage/images/users/' . Auth::user()->photo)}}" alt="User Avatar" class="md-avatar size-1 rounded-circle">
                                @else
                                    <img src="{{URL::asset('storage/images/users/default.png')}}" alt="User Avatar" class="md-avatar size-1 rounded-circle">
                                @endif
                            @else
                            <p class="my-auto mx-1">User</p>
                            @endif
                        </a>
                        <div id="user-opts" class="dropdown-menu dropdown-menu-right rounded-0 px-3" aria-labelledby="dropdownMenuNavButton">


                            @if (Auth::check())
                                <a class="dropdown-item mt-2 d-flex align-items-center" href="/users/{{Auth::user()->id}}">
                                @if(Auth::user()->photo !== null)
                                    <img src="{{URL::asset('storage/images/users/' . Auth::user()->photo)}}" alt="User Avatar" class="md-avatar rounded-circle">
                                @else
                                    <img src="{{URL::asset('storage/images/users/default.png')}}" alt="User Avatar" class="md-avatar rounded-circle">
                                @endif

                                <div class="ml-2">
                                    <h6 class="mb-1">{{Auth::user()->name}}</h6>
                                    @if(Auth::user()->city != null)
                                    <span class="text-muted">{{Auth::user()->city->name}},  {{Auth::user()->city->country->name}}</span>
                                    @else
                                    <span class="font-italic text-muted">No location information</span>
                                    @endif
                                </div>
                            </a>
                            <hr>

                            @if (!Auth::user()->banned)
                            <a class="dropdown-item opt" href="/posts/create">New Story </a>
                            @endif
                            <a class="dropdown-item opt" href="/users/{{Auth::user()->id}}">Profile</a>
                            <a class="dropdown-item opt" href="/users/{{Auth::user()->id}}/manage_subs/">Subscriptions</a>
                            @if (Auth::user()->isAdmin())
                            <a class="dropdown-item opt" href="/admins/{{Auth::user()->id}}">Admin Center</a>
                            @endif
                            <a class="dropdown-item" href="{{ url('/logout') }}"> Logout </a> 
                            @else
                            <a class="dropdown-item opt" data-toggle="modal" data-target="#loginModal" href="#">Sign In</a>
                            <a class="dropdown-item opt" data-toggle="modal" data-target="#registerModal" href="#">Sign Up</a>
                            @endif

                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</nav>