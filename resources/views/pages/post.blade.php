@extends('layouts.app')

@section('scripts')
<script  src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.22.2/moment.min.js"></script>
<link rel="stylesheet" href="https://unpkg.com/easymde/dist/easymde.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/dompurify/2.0.11/purify.min.js"></script>
<script src="https://unpkg.com/easymde/dist/easymde.min.js"></script>
<script src="{{ asset('js/post/post_body.js')}}" defer></script>

<link rel="stylesheet" href="{{ asset('css/print.css') }}">


@if(Auth::check())
<script  src="{{ asset('js/post/reply.js')}}" defer></script>
<script  src="{{ asset('js/post/comment.js')}}" defer></script>
<script  src="{{ asset('js/post/post.js')}}" defer></script>
<script  src="{{ asset('js/rate/rate.js')}}" defer></script>
@else
<script   src="{{ asset('js/post/post_block.js')}}" defer></script>
<script  src="{{ asset('js/rate/rate_block.js')}}" defer></script>
@endif
{{-- <script  src="https://platform.twitter.com/widgets.js" charset="utf-8"></script> --}}
@endsection

@section('title', 'NewsLab - ' . $post->title )

@section('content')

<div class="img-header mb-4" title="Post header photo" style="background-image: url('{{URL::asset('storage/images/posts/'. $post->photo )}}')">
    <div class="overlay"></div>
    <ul class="menu bottomRightMenu">
        <li class="share left">
            <i class="fa fa-share-alt"></i>
            <ul class="submenu">
                <li><a href="#" class="facebook"><i class="fab fa-facebook-f"></i></a></li>
                <li><a href="#" class="twitter"><i class="fab fa-twitter"></i></a></li>
            </ul>
        </li>
    </ul>
</div>

<main  class="container flex-grow-1">
    <!-- row -->
    <div class="row">

        <!-- /.blog-main -->
        <div id="blog-main" class="col-md-8">

            <!-- /.blog-post -->
            <article class="blog-post pb-4 border-bottom border-light border-4 mb-4" data-id={{$post->id}}>
                @if($post->most_recent != null)
                    <p>There is a more recent version of this post: click <a href="/posts/{{$post->most_recent}}">here</a> to see the latest version.</p>
                @endif

                @foreach($post->tags as $tag)
                    @include('partials.tag', [ 'tag' => $tag, 'type' => 'big'])
                @endforeach
                @if ($post->type == "Opinion")
                    <a href="/opinions" class="d-inline-block text-monospace text-decoration-none bg-secondary text-light mx-2 py-1 px-3">{{$post->type}}</a>
                @elseif($post->type == "News")
                    <a href="/news" class="d-inline-block text-monospace text-decoration-none bg-secondary text-light mx-2 py-1 px-3">{{$post->type}}</a>
                @endif

                <div class="post-head row mx-0 justify-content-between my-3">
                    <h1 class="blog-post-title">{{$post->title}}</h1>

                    <div class="d-flex align-items-center">
                        @if($post->most_recent != null)
                            <div id="options-{{$post->id}}" class="py-1 flex-shrink-0">
                                <p class="mb-0 pr-sm-3">
                                    @if($post->likes_difference > 0)
                                        <i class="fas fa-angle-up"></i>
                                    @elseif($post->likes_difference == 0)
                                        <i class="fas fa-minus"></i>
                                    @else
                                        <i class="fas fa-angle-down"></i>
                                    @endif
                                &nbsp;&nbsp;{{$post->likes_difference}}</p>
                            </div>
                        @else
                            <div class="form-check form-check-inline">
                                <input class="form-check-input check-rate {{$post->userLikedPost === true ? "checked" : ""}}" type="radio" name="inlineRadioOptions-{{$post->id}}" id="inlineRadio0" value="like" {{$post->userLikedPost === true ? "checked" : ""}}>
                                <label class="form-check-label" for="inlineRadio0"><i class="fas fa-angle-up"></i></label>
                            </div>
                            <p class="mb-0 pr-3">{{$post->likes_difference}}</p>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input check-rate {{$post->userLikedPost === false ? "checked" : ""}}" type="radio" name="inlineRadioOptions-{{$post->id}}" id="inlineRadio" value="dislike" {{$post->userLikedPost === false ? "checked" : ""}}>
                                <label class="form-check-label" for="inlineRadio"><i class="fas fa-angle-down"></i></label>
                            </div>
                        @endif
                        <div class="dropdown">
                            <button class="btn  btn-sm bg-white" type="button" id="dropdownMenuPostOptsButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <i class="fas fa-ellipsis-h"></i>
                            </button>
                            <div class="dropdown-menu" aria-labelledby="dropdownMenuPostOptsButton" data-id={{Auth::check() ? Auth::user()->id : null}}>
                                @if(Auth::check())
                                    <a id="save-post-button" class="dropdown-item" href="#">Save Post</a>
                                @endif
                                @if(Auth::check() && $post->author != null && Auth::user()->id == $post->author->id)
                                    @if (!Auth::user()->banned)
                                        <a class="dropdown-item" href="/posts/{{$post->id}}/edit">Edit Post</a>
                                    @endif
                                    @if ($post->visible)
                                        <a id="visibility_btn" class="dropdown-item set_private" href="/api/posts/{{$post->id}}/visibility">Make Post Private</a>
                                    @else
                                        <a id="visibility_btn" class="dropdown-item set_public" href="/api/posts/{{$post->id}}/visibility">Make Post Public</a>
                                    @endif
                                @endif
                                {{-- @if((Auth::check() && !Auth::user()->banned) && ($post->author == null || Auth::user()->id != $post->author->id)) --}}
                                @if (! (Auth::check() && Auth::user()->id == $post->author->id))
                                    <a class="dropdown-item" data-toggle="modal" data-target="#report-modal" href="{{$post->id}}">Report Post</a>
                                @endif
                                {{-- @endif --}}
                                @if(Auth::check() && (($post->author != null && Auth::user()->id == $post->author->id) || Auth::user()->isAdmin()))
                                    <a class="dropdown-item delete-post" href="/api/posts/{{$post->id}}">Delete Post</a>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
                <hr>

                <div id="post_content">
                    @if ($post->body !== null)
                        <textarea id="postBodyInput">{{ $post->body }}</textarea>
                    @else
                        <p class="font-italic text-muted">No content available</p>
                    @endif
                </div>
                <div class="small mt-3 text-muted">{{\Carbon\Carbon::parse($post->publication_date)->format('F j, Y')}} @if($post->edited)<a href="./{{$post->id}}/versions" class="font-italic text-muted">(Edited)</a>@endif</div>
            </article>

            <!-- /.comments -->
            <section id="comments" class="mb-4">
                <div class="row align-items-end px-3">
                    <h2 class="my-0">{{$comments['total']}} @if($comments['total'] == 1) Comment @else Comments @endif</h2>

                    <div class="dropdown ml-auto">
                        <button class="btn btn-small bg-primary" type="button" id="dropdownMenuFilterOptsButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <svg height="1.25rem" viewBox="-4 0 393 393.99003" width="1.25rem">
                                <g stroke="white">
                                    <path stroke-width="8" fill="white" d="m368.3125 0h-351.261719c-6.195312-.0117188-11.875 3.449219-14.707031 8.960938-2.871094 5.585937-2.3671875 12.3125 1.300781 17.414062l128.6875 181.28125c.042969.0625.089844.121094.132813.183594 4.675781 6.3125 7.203125 13.957031 7.21875 21.816406v147.796875c-.027344 4.378906 1.691406 8.582031 4.777344 11.6875 3.085937 3.105469 7.28125 4.847656 11.65625 4.847656 2.226562 0 4.425781-.445312 6.480468-1.296875l72.3125-27.574218c6.480469-1.976563 10.78125-8.089844 10.78125-15.453126v-120.007812c.011719-7.855469 2.542969-15.503906 7.214844-21.816406.042969-.0625.089844-.121094.132812-.183594l128.683594-181.289062c3.667969-5.097657 4.171875-11.820313 1.300782-17.40625-2.832032-5.511719-8.511719-8.9726568-14.710938-8.960938zm-131.53125 195.992188c-7.1875 9.753906-11.074219 21.546874-11.097656 33.664062v117.578125l-66 25.164063v-142.742188c-.023438-12.117188-3.910156-23.910156-11.101563-33.664062l-124.933593-175.992188h338.070312zm0 0"></path>
                                </g>
                            </svg>
                        </button>
                        <div class="dropdown-menu dropdown-menu-right" aria-labelledby="dropdownMenuFilterOptsButton">
                            <a class="dropdown-item" href="#old">Cronological</a>
                            <a class="dropdown-item" href="#recent">Most Recent</a>
                            <a class="dropdown-item" href="#numerical">Upvotes</a>
                        </div>
                    </div>
                </div>
                <hr>

                <form id="comment-form" class="p-2 rounded mb-3" action="/api/posts/{{$post->id}}/comment" method="POST">
                    <button class="btn btn-sm btn-primary hover-bold float-right px-4" type="submit"><i class="fas fa-paper-plane"></i></button>
                    <div class="form-group mb-1">
                        <label class="font-weight-bold p-1 m-0" for="commentArea">Comment</label>
                        <textarea class="form-control border-primary border-right-0 border-top-0 border-left-0 p-1" placeholder="Write your comment here..." id="commentArea" rows="1" ></textarea>
                        <p id="commentError" class="small text-primary text-left mb-0"></p>
                    </div>
                </form>

                <div class="order">
                @include('partials.comment_preview_list', ['context' => 'post', 'comments' => $comments['comments'], 'emptyMessage' => 'There is no comment yet'])
                </div>
            </section>
        </div>

        <!-- /.blog-sidebar -->
        <aside class="col-md-4 blog-sidebar">

            <!-- /.author -->
            <section class="p-3 mb-3 bg-light rounded">
                <h4 class="">Author</h4>
                <div class="card border-0 bg-light">
                    <div class="row no-gutters">
                        <!-- <div class="avata rounded-circle"> -->
                        <div class="col-2 col-md-3">
                            @if($post->author !== NULL)
                                <a href="/users/{{$post->author->id}}">
                                    @if($post->author->photo !== NULL)
                                        <div class="square rounded-circle" title="Author default photo"  style="background-image: url('{{URL::asset('storage/images/users/' . $post->author->photo)}}')"></div>
                                    @else
                                        <div class="square rounded-circle" title="Author photo" style="background-image: url('{{URL::asset('storage/images/users/default.png')}}')"></div>
                                    @endif
                                </a>
                            @else
                                <a>
                                    <div class="square rounded-circle" title="Author default photo"  style="background-image: url('{{URL::asset('storage/images/users/default.png')}}')"></div>
                                </a>
                            @endif
                            {{-- <!-- <img src="{{URL::asset("$post->photo")}}" class="sq-vimg img-responsive full-width" alt="Avatar"> --> --}}
                        </div>
                        <div class="col-10 col-md-9">
                            <div class="card-body">
                                @if($post->author != NULL)
                                    <h5 class="card-title"><a class="text-dark" href="/users/{{$post->author->id}}">{{$post->author->name}}</a> @if($post->author->verified)<i class="fas fa-check-circle"></i>@endif</h5>
                                    @if($post->author->local != NULL)
                                        <p class="card-text">{{$post->author->local->city}},&nbsp;&nbsp;{{$post->author->local->country}}</p>
                                    @else
                                        <p class="card-text font-italic text-muted">No location information</p>
                                    @endif
                                @else
                                    <h5 class="card-title font-italic text-muted">Account deleted</h5>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
                @if($post->author != NULL)
                    <p class="mb-0 mt-2">{{$post->author->bio}}</p>
                @else
                    <p class="mb-0 mt-2 font-italic text-muted">The author of this post has deleted his account.</p>
                @endif
            </section>

            <!-- /.related-news -->
            <section class="mb-0">
                <h4 class="mb-3">RELATED POSTS</h4>
                <ul class="list-unstyled mb-0">
                    @each('partials.side_post', $relatedPosts->relatedPosts, 'post')
                </ul>
            </section>

            <!-- /.topics -->
            @include('partials.related_tags', ['tags' =>$relatedTags->relatedTags])
        </aside>

    </div>

</main>

@if(Auth::check() && !Auth::user()->banned)
    @include('inc.report')
@endif

@endsection