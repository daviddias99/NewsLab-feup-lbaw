
@extends('layouts.app')

@section('scripts')
    <script  src="{{ asset('js/pie_chart.js') }}" defer></script>
    <script  src="{{ asset('js/stats.js') }}" defer></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.9.3/Chart.min.js"></script>
@endsection

@section('title', 'NewsLab - ' . $user['name'] . ' Stats')

@section('content')


<main  class="container" data-id={{$user['id']}}>
<h1 class="mt-5">User Statistics</h1>
<hr>

<div class="row">
    <div class="card border-0 mt-2 col-md-8" >
        <div class="row no-gutters">

            <div class="col-2 my-auto">
                <a href="/users/{{$user['id']}}">
                    @if ($user['photo'] != null)
                        <div class="square rounded-circle mb-4" title="Author photo" style="background-image: url('{{URL::asset('storage/images/users/' . $user['photo'] )}}')"></div>
                    @else
                        <div class="square rounded-circle mb-4" title="Author photo" style="background-image: url('{{URL::asset('storage/images/users/default.png')}}')"></div>
                    @endif
                </a>
            </div>
            <div class="col-10 my-auto">
                <div class="card-body py-1">
                    <h5 class="card-title my-1"><a href="/users/{{$user['id']}}" class="text-dark">{{$user['name']}} </a><i class="fas fa-check-circle"></i></h5>
                    <p class="const userID = document.querySelector('main').getAttribute('data-id');card-text text-muted">{{$user['email']}}</p>
                </div>
            </div>
        </div>
    </div>


    <div class="mt-3 col-md-4">
        <h4>Overview:</h4>
        <ul>
            <li>{{$user['num_subscribers']}} subscribers</li>
            <li>{{$num_posts}} posts ({{$posts_likes}} likes difference)</li>
            <li>{{$num_comments}} comments ({{$comments_likes}} likes difference)</li>
        </ul>
    </div>
</div>
<hr>
<div class="row mb-5">
    <div class="col-md-6 col-lg-4 order-md-2 order-lg-3">
        <h4 class="mb-4">Most liked post:</h4>
        <div class="card mb-4 border-0">
<div class="square card-img-top h-13 p-rel pb-0" style="background-image: url('{{URL::asset('storage/images/posts/' . $most_liked_post['photo'])}}')">
            @if (! $most_liked_post['visible'])
                <button class="topRightSecondary btn btn-sm btn-light m-2">
                    <i class="far fa-eye-slash"></i>
                </button>
            @endif
            <div class="likes bg-white px-2">
                @if ($most_liked_post['likes_difference'] > 0)
                    <i class="fas fa-angle-up text-primary"></i> {{$most_liked_post['likes_difference']}}
                @elseif ($most_liked_post['likes_difference'] < 0)
                    <i class="fas fa-angle-down text-primary"></i> {{$most_liked_post['likes_difference']}}
                @else
                    <i class="text-success fas fa-minus"></i>0
                @endif
            </div>
        </div>
        <div class="card-body px-0">
            @foreach($most_liked_post['tags'] as $tag)
                <a class="d-inline-block text-monospace text-decoration-none small bg-success py-1 px-3 text-light" href="/tags/{{$tag['id']}}">{{ucfirst($tag['name'])}}</a>
            @endforeach
            <div class="mt-1"><a class="text-dark font-weight-bold" href="/posts/{{$most_liked_post['id']}}"><h6 class="mb-0">{{$most_liked_post['title']}}</h6></a></div>
            <small class="text-muted">
                <span class="my-1">{{\Carbon\Carbon::parse($most_liked_post['publication_date'])->format('F j, Y')}}</span>
                @if($most_liked_post['edited'])
                        <a href="/posts/{{$most_liked_post['id']}}/versions" class="font-italic text-muted">(Edited)</a>
                @endif
            </small>
            <p class="card-text line-clamp">{{$most_liked_post['body']}}</p>
        </div>
        </div>
    </div>
    <div class="col-md-6 col-lg-4 order-md-1 order-lg-1">
        <h4>Tags on posts:</h4>
        <canvas id="tagsStats" width="250" height="200"></canvas>
    </div>
    <div class="col-md-12 col-lg-4 order-md-3 order-lg-2">
        <div class="row">
            <div class="col-md-6 col-lg-12">
                <h4 class="mb-4">Subscribers' Location:</h4>
                <canvas id="locStats" width="250" height="200"></canvas>
            </div>
            <div class="col-md-6 col-lg-12">
                <h4 class="mb-4">Subscribers' Age:</h4>
                <canvas id="ageStats" width="250" height="150"></canvas>
            </div>
        </div>
    </div>
</div>

</main>


@endsection