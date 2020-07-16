@extends('layouts.app')

@section('title', 'NewsLab - Home')

@section('scripts')

<script  src="{{ asset('js/homepage.js')}}" defer></script>      

@endsection

@section('content')

<div class="main">
    <div id="carouselBannerPosts" class="carousel slide" data-ride="carousel">
        <ol class="carousel-indicators">
            @if(count($bannerPosts) > 0)
                <li data-target="#carouselBannerPosts" data-slide-to="0" class="active"></li>
            @endif
            @if(count($bannerPosts) > 1)
                <li data-target="#carouselBannerPosts" data-slide-to="1"></li>
            @endif
            @if(count($bannerPosts) > 2)
                <li data-target="#carouselBannerPosts" data-slide-to="2"></li>
            @endif
        </ol>
        <div class="carousel-inner">
            @foreach($bannerPosts as $id => $post)
                <div class="carousel-item @if($id == 0) active @endif">
                    <div class="img-front-page-header mb-4" title="Post photo" style="background-image: url('{{URL::asset('storage/images/posts/' . $post->photo)}}')">
                    </div>
                    <div class="fp_carousel_caption carousel-caption fp-carroussel-ovrl m-3 mb-4 py-3 px-5">
                        <div class="d-flex justify-content-around align-items-center flex-md-row flex-column">
                            <h1 class="mb-md-0 mb-sm-2">{{$post->title}}</h1>
                            <form action="/posts/{{$post->id}}">
                                <input class="btn btn-primary btn-lg px-5" type="submit" value="Read more" />
                            </form>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
        <a class="carousel-control-prev" href="#carouselBannerPosts" role="button" data-slide="prev">
            <span class="carousel-control-prev-icon" aria-hidden="true"></span>
            <span class="sr-only">Previous</span>
        </a>
        <a class="carousel-control-next" href="#carouselBannerPosts" role="button" data-slide="next">
            <span class="carousel-control-next-icon" aria-hidden="true"></span>
            <span class="sr-only">Next</span>
        </a>
    </div>
    <div class="container mt-3">
        <div class="row">
            @foreach($hotTags as $tag)
                <div class="col-md-4 col-sm-12 hot-tag">
                    <div class="img-front-page-tag-preview mb-4 d-flex justify-content-center "  title="Tag photo" 
                    @if(isset($tag->photo))
                        style="background-image: url('{{URL::asset('storage/images/tags/'.$tag->photo)}}')"
                    @else
                        style="background-image: url('{{URL::asset('storage/images/tags/default.jpg')}}')"
                    @endif
                    >
                        @include('partials.tag', ['tag' =>$tag, 'type' => 'enorm'])
                    </div>
                </div>
            @endforeach
            <div class="section_bar my-4 "></div>
        </div>
    </div>

    <div class="content_container">
        <div class="container">
            <div class="row">
                <div class="col-lg-8 ">
                    <div class="main_content ">
                        <div class="featured_title">
                            <div class="container pl-0">
                                <div class="row">
                                    <div class="col">
                                        <div class=" mt-2  section_title_container d-flex flex-row align-items-center justify-content-start">
                                            <h1 class="mx-0 featured_post_title ">NEWS </h1>
                                        </div>
                                        <hr class="mb-5">
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="featured ">
                            <div class="row">
                                <div class="col-lg-8">
                                    @if (count($recentNews) > 0)
                                        <div class="post_item post_v_large d-flex flex-column align-items-start justify-content-start">
                                            <div class="post_image w-100"><img alt="Post image" class="mw-100" src="{{URL::asset('storage/images/posts/' . $recentNews[0]->photo)}}"></div>
                                            <div class="post_content mt-2">
                                                @foreach($recentNews[0]->tags as $tag)
                                                    @include('partials.tag', [ 'tag' => $tag, 'type' => 'none'])
                                                @endforeach
                                                <h2 class="post_title"><a class="text-dark" href="/posts/{{$recentNews[0]->id}}">{{$recentNews[0]->title}}</a></h2>
                                                <div class="post_info d-flex flex-row align-items-center justify-content-start">
                                                    <div class="post_author d-flex flex-row align-items-center justify-content-start">
                                                        @if($recentNews[0]->author != null)
                                                            @if($recentNews[0]->author->photo != null)
                                                                <div class="mx-2">
                                                                    <img alt="Post image" src="{{URL::asset('storage/images/users/' . $recentNews[0]->author->photo)}}"  class="md-avatar size-1 rounded-circle">
                                                                </div>
                                                            @else
                                                                <div class="mx-2">
                                                                    <img src="{{URL::asset('storage/images/users/default.png')}}" alt="User Avatar"  class="md-avatar size-1 rounded-circle">
                                                                </div>
                                                            @endif
                                                            <div class="medium mt-1  text-warning"><a href="/users/{{$recentNews[0]->author->id}}" class="text-warning border-right pr-1 border-warning">{{$recentNews[0]->author->name}}</a><span class="pl-1">{{\Carbon\Carbon::parse($recentNews[0]->publication_date)->format('F j, Y')}}</span></div>
                                                        @else
                                                            <div class="mx-2">
                                                                <img src="{{URL::asset('storage/images/users/default.png')}}" alt="User Avatar"  class="md-avatar size-1 rounded-circle">
                                                            </div>
                                                            <div class="medium mt-1  text-warning"><a class="border-right pr-1 border-warning font-italic text-muted">Account deleted</a><span class="pl-1">{{\Carbon\Carbon::parse($recentNews[0]->publication_date)->format('F j, Y')}}</span></div>
                                                        @endif
                                                    </div>
                                                <div class="post_comments ml-auto "><a class="text-decoration-none text-secondary" href="/posts/{{$recentNews[0]->id}}#comments">{{$recentNews[0]->num_comments}} comments</a></div>
                                                </div>
                                                <div class="post_text my-2">
                                                    <p class="line-clamp">{{$recentNews[0]->body}}</p>
                                                </div>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                                
                                <div class="col-lg-4">
                                    @if (count($recentNews) > 1) 
                                        <div class="post_item mb-2 post_v_small d-flex flex-column align-items-start justify-content-start">
                                            <div class="post_image w-100 pb-1"><img alt="Post image" class="mw-100" src="{{URL::asset('storage/images/posts/' . $recentNews[1]->photo)}}"></div>
                                            <div class="post_content">
                                                @foreach($recentNews[1]->tags as $tag)
                                                    @include('partials.tag', [ 'tag' => $tag, 'type' => 'none'])
                                                @endforeach
                                                <h5 class="post_title mb-0"><a class="text-dark" href="/posts/{{$recentNews[1]->id}}">{{$recentNews[1]->title}}</a></h5>
                                                <div class="post_info d-flex flex-row align-items-center justify-content-start">
                                                    <div class="post_author d-flex flex-row align-items-center justify-content-start">
                                                        @if($recentNews[1]->author != null)
                                                            <div class="medium mt-1 text-warning"><a href="/users/{{$recentNews[1]->author->id}}" class="text-warning border-right pr-1 border-warning">{{$recentNews[1]->author->name}}</a><span class="pl-1">{{\Carbon\Carbon::parse($recentNews[1]->publication_date)->format('F j, Y')}}</span></div>
                                                        @else
                                                            <div class="medium mt-1 text-warning"><a class="font-italic text-muted border-right pr-1 border-warning">Account deleted</a><span class="pl-1">{{\Carbon\Carbon::parse($recentNews[1]->publication_date)->format('F j, Y')}}</span></div>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endif
                                    
                                    @if (count($recentNews) > 2)
                                        <div class="post_item mb-2 post_v_small d-flex flex-column align-items-start justify-content-start">
                                            <div class="post_image w-100 pb-1"><img alt="Post image" class="mw-100" src="{{URL::asset('storage/images/posts/' . $recentNews[2]->photo)}}"></div>
                                            <div class="post_content">
                                                @foreach($recentNews[2]->tags as $tag)
                                                    @include('partials.tag', [ 'tag' => $tag, 'type' => 'none'])
                                                @endforeach
                                                <h5 class="post_title mb-0"><a class="text-dark" href="/posts/{{$recentNews[2]->id}}">{{$recentNews[2]->title}}</a></h5>
                                                <div class="post_info d-flex flex-row align-items-center justify-content-start">
                                                    <div class="post_author d-flex flex-row align-items-center justify-content-start">
                                                        @if($recentNews[2]->author != null)
                                                            <div class="medium mt-1 text-warning"><a href="/users/{{$recentNews[2]->author->id}}" class="text-warning border-right pr-1 border-warning">{{$recentNews[2]->author->name}}</a><span class="pl-1">{{\Carbon\Carbon::parse($recentNews[2]->publication_date)->format('F j, Y')}}</span></div>
                                                        @else
                                                            <div class="medium mt-1 text-warning"><a class="font-italic text-muted border-right pr-1 border-warning">Account deleted</a><span class="pl-1">{{\Carbon\Carbon::parse($recentNews[2]->publication_date)->format('F j, Y')}}</span></div>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endif

                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="main_content mt-3">
                        <div class="featured_title">
                            <div class="container pl-0">
                                <div class="row">
                                    <div class="col">
                                        <div class=" mt-2  section_title_container d-flex flex-row align-items-center justify-content-start">
                                            <h1 class="mx-0 featured_post_title">OPINIONS </h1>
                                        </div>
                                        <hr class="mb-5">
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="featured ">
                            <div class="row">
                                <div class="col-lg-8">
                                    @if (count($recentOpinions) > 0)
                                        <div class="post_item post_v_large d-flex flex-column align-items-start justify-content-start">
                                            <div class="post_image w-100"><img alt="Post image" class="mw-100" src="{{URL::asset('storage/images/posts/' . $recentOpinions[0]->photo)}}"></div>
                                            <div class="post_content mt-2">
                                                @foreach($recentOpinions[0]->tags as $tag)
                                                    @include('partials.tag', [ 'tag' => $tag, 'type' => 'none'])
                                                @endforeach
                                                <h2 class="post_title"><a class="text-dark" href="/posts/{{$recentOpinions[0]->id}}">{{$recentOpinions[0]->title}}</a></h2>
                                                <div class="post_info d-flex flex-row align-items-center justify-content-start">
                                                    <div class="post_author d-flex flex-row align-items-center justify-content-start">
                                                        @if($recentOpinions[0]->author != null)
                                                            @if($recentOpinions[0]->author->photo != null)
                                                                <div class="mx-2">
                                                                    <img src="{{URL::asset('storage/images/users/' . $recentOpinions[0]->author->photo)}}" alt="User Avatar" class="md-avatar size-1 rounded-circle">
                                                                </div>
                                                            @else
                                                                <div class="mx-2">
                                                                    <img src="{{URL::asset('storage/images/users/default.png')}}" alt="User Avatar" class="md-avatar size-1 rounded-circle">
                                                                </div>
                                                            @endif
                                                            <div class="medium mt-1  text-warning"><a href="/users/{{$recentOpinions[0]->author->id}}" class="text-warning border-right pr-1 border-warning">{{$recentOpinions[0]->author->name}}</a><span class="pl-1">{{\Carbon\Carbon::parse($recentOpinions[0]->publication_date)->format('F j, Y')}}</span></div>
                                                        @else
                                                            <div class="mx-2">
                                                                <img src="{{URL::asset('storage/images/users/default.png')}}" alt="User Avatar" class="md-avatar size-1 rounded-circle">
                                                            </div>
                                                            <div class="medium mt-1 text-warning"><a class="border-right pr-1 border-warning font-italic text-muted">Account deleted</a><span class="pl-1">{{\Carbon\Carbon::parse($recentOpinions[0]->publication_date)->format('F j, Y')}}</span></div>
                                                        @endif
                                                    </div>
                                                    <div class="post_comments ml-auto "><a class="text-decoration-none text-secondary" href="/posts/{{$recentOpinions[0]->id}}#comments">{{$recentOpinions[0]->num_comments}} comments</a></div>
                                                </div>
                                                <div class="post_text my-2">
                                                    <p class="line-clamp">{{$recentOpinions[0]->body}}</p>
                                                </div>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                                
                                <div class="col-lg-4">
                                    @if (count($recentOpinions) > 1)
                                        <div class="post_item mb-2 post_v_small d-flex flex-column align-items-start justify-content-start">
                                            <div class="post_image w-100 pb-1"><img alt="Post image" class="mw-100" src="{{URL::asset('storage/images/posts/' . $recentOpinions[1]->photo)}}"></div>
                                            <div class="post_content">
                                                @foreach($recentOpinions[1]->tags as $tag)
                                                    @include('partials.tag', [ 'tag' => $tag, 'type' => 'none'])
                                                @endforeach
                                                <h5 class="post_title mb-0"><a class="text-dark" href="/posts/{{$recentOpinions[1]->id}}">{{$recentOpinions[1]->title}}</a></h5>
                                                <div class="post_info d-flex flex-row align-items-center justify-content-start">
                                                    <div class="post_author d-flex flex-row align-items-center justify-content-start">
                                                        @if($recentOpinions[1]->author != null)
                                                            <div class="medium mt-1 text-warning"><a href="/users/{{$recentOpinions[1]->author->id}}" class="text-warning border-right pr-1 border-warning">{{$recentOpinions[1]->author->name}}</a><span class="pl-1">{{\Carbon\Carbon::parse($recentOpinions[1]->publication_date)->format('F j, Y')}}</span></div>
                                                        @else
                                                            <div class="medium mt-1 text-warning"><a class="font-italic text-muted border-right pr-1 border-warning">Account deleted</a><span class="pl-1">{{\Carbon\Carbon::parse($recentOpinions[1]->publication_date)->format('F j, Y')}}</span></div>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endif
                                    
                                    @if (count($recentOpinions) > 2) 
                                        <div class="post_item mb-2 post_v_small d-flex flex-column align-items-start justify-content-start">
                                            <div class="post_image w-100 pb-1"><img alt="Post image" class="mw-100" src="{{URL::asset('storage/images/posts/' . $recentOpinions[2]->photo)}}"></div>
                                            <div class="post_content">
                                                @foreach($recentOpinions[2]->tags as $tag)
                                                    @include('partials.tag', [ 'tag' => $tag, 'type' => 'none'])
                                                @endforeach
                                                <h5 class="post_title mb-0"><a class="text-dark" href="/posts/{{$recentOpinions[2]->id}}">{{$recentOpinions[2]->title}}</a></h5>
                                                <div class="post_info d-flex flex-row align-items-center justify-content-start">
                                                    <div class="post_author d-flex flex-row align-items-center justify-content-start">
                                                        @if($recentOpinions[2]->author != null)
                                                            <div class="medium mt-1 text-warning"><a href="/users/{{$recentOpinions[2]->author->id}}" class="text-warning border-right pr-1 border-warning">{{$recentOpinions[2]->author->name}}</a><span class="pl-1">{{\Carbon\Carbon::parse($recentOpinions[2]->publication_date)->format('F j, Y')}}</span></div>
                                                        @else
                                                            <div class="medium mt-1 text-warning"><a class="font-italic text-muted border-right pr-1 border-warning">Account deleted</a><span class="pl-1">{{\Carbon\Carbon::parse($recentOpinions[2]->publication_date)->format('F j, Y')}}</span></div>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endif

                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="world">
                        <div class="featured_title">
                            <div class="container pl-0">
                                <div class="row">
                                    <div class="col">
                                        <div class=" mt-2  section_title_container d-flex flex-row align-items-center justify-content-start">
                                        <h1 class="mx-0 featured_post_title">RANDOM TOPIC - {{ucfirst($randomTag->name)}}</h1>

                                        </div>
                                        <hr class="mb-5">
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row world_row mb-4 justify-content-md-center">
                            <div class="col-lg-11">
                                <div class="row">
                                    @foreach($randomTagPosts as $post)
                                        <div class="col-lg-6">
                                            <div class="post_item mb-2 post_v_small d-flex flex-column align-items-start justify-content-start">
                                                <div class="post_image w-100 pb-1"><img alt="Post image" class="mw-100" src="{{URL::asset('storage/images/posts/' . $post->photo)}}"></div>
                                                <div class="post_content">
                                                    @foreach($post->tags as $tag)
                                                        @include('partials.tag', [ 'tag' => $tag, 'type' => 'none'])
                                                    @endforeach
                                                    <h5 class="post_title mb-0"><a class="text-dark" href="/posts/{{$post->id}}">{{$post->title}}</a></h5>
                                                    <div class="post_info d-flex flex-row align-items-center justify-content-start">
                                                        <div class="post_author d-flex flex-row align-items-center justify-content-start">
                                                            @if($post->author != null)
                                                                <div class="medium mt-1 text-warning"><a href="/users/{{$post->author->id}}" class="text-warning border-right pr-1 border-warning">{{$post->author->name}}</a><span class="pl-1">{{\Carbon\Carbon::parse($post->publication_date)->format('F j, Y')}}</span></div>
                                                            @else
                                                                <div class="medium mt-1 text-warning"><a class="font-italic text-muted border-right pr-1 border-warning">Account deleted</a><span class="pl-1">{{\Carbon\Carbon::parse($post->publication_date)->format('F j, Y')}}</span></div>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </div>

                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>

                            @if (count($randomTagPosts) > 0)
                                <form action="/tags/{{$randomTag->id}}">
                                    <input class="btn btn-primary btn-lg px-5" type="submit" value="Read more" />
                                </form>
                            @endif
                        </div>
                    </div>

                </div>

                <div class="col-lg-4 p-4">
                    <div class="sidebar">

                        <div id="sidebar_weather">
                            <h4 class=""> WEATHER</h4>
                            <div class="card md-3">
                                <ul class="list-inline">
                                    <li><img id="weather_icon" src="," alt="" /></li>
                                    <li id="weather_status"></li>
                                </ul>
                                <h1 class="mt-2" id="temperature_celsius">--</h1>
                                <div id="date_time">
                                    <h3 id="time">--:--</h3>
                                    <p id="date"></p>
                                </div>
                                <p id="city"></p>
                            </div>
                        </div>


                        <section class="mb-0 mt-5">
                            <h4>HOT POSTS</h4>
                            <ul class="list-unstyled mb-0">
                                @each('partials.side_post', $hotPosts, 'post')
                            </ul>
                        </section>

                        @include('partials.related_tags', ['tags' =>$randomTags])
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>

@endsection