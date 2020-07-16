@extends('layouts.app')

@section('scripts')

    <script src="{{ asset('js/pie_chart.js') }}" defer></script>
    <script src="{{ asset('js/pagination/std_pagination.js') }}" defer></script>
<script src="{{ asset('js/profile/profile_common.js') }}" defer></script>
    @if(Auth::check())
        <script src="{{ asset('js/rate/rate.js') }}" defer></script>
        <script src="{{ asset('js/profile/profile.js') }}" defer></script>

        <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.22.2/moment.min.js"></script>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/tempusdominus-bootstrap-4/5.0.1/css/tempusdominus-bootstrap-4.min.css" />
        <script src="{{ asset('calendar/tempusdominus.js')}}"></script>
    @else
        <script src="{{ asset('js/rate/rate_block.js') }}" defer></script>
        <script src="{{ asset('js/profile/profile_block.js') }}" defer></script>
    @endif

    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.9.3/Chart.min.js"></script>
@endsection

@section('title', 'NewsLab - ' . $user['name'])

@section('content')

<main  class="container flex-grow-1" data-id={{$user['id']}}>
    <div class="d-flex flex-column mt-5">
        <h1 class="m-0">{{$user['name']}} @if ($user['verified']) <i class="fas fa-check-circle"></i>@endif</h1>
        <div class="flex-row d-flex align-items-center">
            <p id="sub-count" class="text-muted m-0">{{$user['num_subscribers']}} @if ($user['num_subscribers'] == 1) Subscriber @else Subscribers @endif</p>
            @if (! Auth::check() || Auth::user()->id != $user['id'])
                @if ($subscribed)
                    <button id="subscribe_btn" data-subscribing_id={{Auth::check() ? Auth::user()->id : ""}} type="button" class="btn btn-outline-secondary ml-auto">Unsubscribe</button>
                @else
                    <button id="subscribe_btn" data-subscribing_id={{Auth::check() ? Auth::user()->id : ""}} type="button" class="btn btn-primary ml-auto">Subscribe</button>
                @endif
            @endif
        </div>
    </div>

    <hr class="mt-1">
    <div class="row p-rel">
        <div style="z-index:10;" class="dropdown topRight r-15">
            <button class="btn btn-light" type="button" id="dropdownMenuProfileOptsButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                <i class="fas fa-ellipsis-h"></i>
            </button>
            <div class="dropdown-menu dropdown-menu-right" aria-labelledby="dropdownMenuProfileOptsButton">
                @if (Auth::check())
                    @if ((Auth::user()->id == $user['id']))
                        <a class="dropdown-item" href="/users/{{$user['id']}}/stats">Statistics</a>
                        <a class="dropdown-item" href="/users/{{$user['id']}}/edit">Edit Profile</a>
                    @elseif (Auth::user()->isAdmin())
                        <div id="ban_unban_div">
                            @if (!$user['is_banned'])
                                <a class="dropdown-item" href="#" data-toggle="modal" data-target="#ban-modal">Ban User</a>
                            @else
                                <a class="dropdown-item" id="unban_user" href="/api/users/{{$user['id']}}/unban">Unban user</a>
                            @endif
                        </div>
                    @endif
                @endif
                @if (! (Auth::check() && Auth::user()->id == $user['id']))
                    <a class="dropdown-item" href="{{$user['id']}}" data-toggle="modal" data-target="#report-modal">Report User</a>
                @endif
            </div>
        </div>
        <div class="col-sm-6 col-lg-4">
            @if ($user['photo'] != null)
                <div class="square rounded-circle mb-4" title="Author photo" style="background-image: url('{{URL::asset('storage/images/users/' . $user['photo'] )}}')"></div>
            @else
                <div class="square rounded-circle mb-4" title="Author photo" style="background-image: url('{{URL::asset('storage/images/users/default.png')}}')"></div>
            @endif
        </div>
        <div class="col-sm-6 col-lg-4 my-auto mx-0">
            <p><strong>Email: </strong>{{$user['email']}}</p>
            <p><strong>Birthday: </strong>{{$user['birthday']}}</p>
            @if (! is_null($user['local']))
                <p><strong>Local: </strong>{{$user['local']['city']}}, {{$user['local']['country']}}</p>
            @endif
            @if (! is_null($user['bio']))
                <p><strong>Bio: </strong>{{$user['bio']}}</p>
            @endif
        </div>
        <div id="tagsOnPosts" class="col-sm-8 col-lg-4">
            <h4>Tags on posts:</h4>
            <canvas id="tagsStats" width="250" height="200"></canvas>
        </div>
    </div>
    <div class="mt-5">
        <ul class="nav nav-tabs" id="profileTab" role="tablist">
            <li class="nav-item">
                <a class="nav-link active" id="posts-tab" data-toggle="tab" href="#posts" role="tab" aria-controls="posts" aria-selected="true">Posts</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="likes-tab" data-toggle="tab" href="#likes" role="tab" aria-controls="likes" aria-selected="true">Likes</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="comments-tab" data-toggle="tab" href="#comments" role="tab" aria-controls="comments" aria-selected="false">Comments</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="badges-tab" data-toggle="tab" href="#badges" role="tab" aria-controls="badges" aria-selected="false">Badges</a>
            </li>
        </ul>

        <div class="tab-content" id="profileTabContent">
            <!-- ./my_posts  -->
            <div class="tab-pane fade show active py-3" id="posts" role="tabpanel" aria-labelledby="posts-tab">
                <div>
                    @include('partials.post_preview_list', [
                        'posts' => $posts['data'],
                        'editable' => true,
                        'showAuthor' => false,
                        'showDate' => false,
                        'emptyMessage' => 'This user has no posts.',
                        'paginator' => $posts['paginator']
                    ])
                </div>
            </div>

            <!-- ./my_likes  -->
            <div class="tab-pane fade py-3" id="likes" role="tabpanel" aria-labelledby="likes-tab">
                <div>
                @include('partials.post_preview_list', [
                    'posts' => $likes['data'],
                    'editable' => false,
                    'showAuthor' => true,
                    'showDate' => false,
                    'emptyMessage' => 'This user hasn\'t liked any posts yet.',
                    'paginator' => $likes['paginator']
                ])
                </div>
            </div>

            <!-- ./my_comments -->

        <div data-logged_in={{Auth::check() ? "yes" : "no"}} class="tab-pane fade py-3" id="comments" role="tabpanel" aria-labelledby="comments-tab">
                <div>
                    @include('partials.comment_preview_list', [
                        'comments' => $comments['data'],
                        'emptyMessage' => 'This user has no comments.',
                        'paginator' => $comments['paginator'],
                        'context' => 'profile'
                    ])
                </div>
            </div>

            <!-- ./badges -->
            <div class="tab-pane fade py-3" id="badges" role="tabpanel" aria-labelledby="badges-tab">
                <div class="row flex-wrap justify-content-around">
                    @foreach($userBadges as $id => $name)
                        @include('partials.badge', [ 'badge' => $badgesInfo[$id], 'hasBadge' => true ])
                    @endforeach
                    @foreach($badgesInfo as $badge)
                        @if (! isset($userBadges[$badge['id']]))
                            @include('partials.badge', ['badge' => $badge, 'hasBadge' => false ])
                        @endif
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    @if(Auth::check())
        @include('inc.report')
        @if(Auth::user()->isAdmin())
            @include('inc.ban')
        @endif
    @endif
</main>




@endsection