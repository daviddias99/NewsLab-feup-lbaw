@extends('layouts.app')

@section('title', 'NewsLab - ' . $type)

@section('scripts')
    <script  src="{{ asset('js/news_opinions_feed.js')}}" defer></script>
@endsection

@section('content')
<main  class="container flex-grow-1">
    <h1 class="text-left mt-5" id="type" data-type="{{$type}}">{{ucfirst($type)}}</h1>
    <hr>

    <!-- big post section -->
    <section>
        <h2 class="text-left my-5">FEATURED POSTS</h2>
        @if ($featuredPost != null)
            <section>
                <div class="mb-5 post_item post_v_large d-flex flex-row align-items-start justify-content-start">
                    <div class="post_image col-lg-5 col-12">
                        <img alt="Post photo" class="img-feat-post mw-100" src="{{URL::asset('storage/images/posts/' . $featuredPost['photo'])}}">
                    </div>
                    <div class="post_content mt-2 ml-4 col-lg-7 col-12">
                        @foreach($featuredPost['tags'] as $tag)
                            @include('partials.tag', [ 'tag' => $tag, 'type' => 'big'])
                        @endforeach
                        <h2 class="mt-2 mb-4"><a class="text-dark" href="/posts/{{$featuredPost['id']}}">{{$featuredPost['title']}}</a></h2>
                        <div class="post_info d-flex flex-row align-items-center justify-content-start">
                            <div class="post_author d-flex flex-row align-items-center justify-content-start">
                                @if ($featuredPost['author'] != null)
                                    @if ($featuredPost['author']['photo'] != null)
                                        <div class="mx-2">
                                            <img alt="Author profile photo" src="{{URL::asset('storage/images/users/' . $featuredPost['author']['photo'])}}" class="md-avatar author-img-feat-post rounded-circle">
                                        </div>
                                    @else
                                        <div class="mx-2">
                                            <img alt="Author profile photo" src="{{URL::asset('storage/images/users/default.png')}}" class="md-avatar author-img-feat-post rounded-circle">
                                        </div>
                                    @endif
                                    <div class="medium mt-1 text-warning"><a href="/users/{{$featuredPost['author']['id']}}" class="text-warning border-right pr-1 border-warning">{{$featuredPost['author']['name']}}</a><span class="pl-1">{{\Carbon\Carbon::parse($featuredPost['publication_date'])->format('F j, Y')}}</span></div>
                                @else
                                    <div class="mx-2">
                                        <img alt="Author profile photo" src="{{URL::asset('storage/images/users/default.png')}}" class="md-avatar author-img-feat-post rounded-circle">
                                    </div>
                                    <div class="medium mt-1 text-warning"><a class="font-italic text-muted border-right pr-1 border-warning">Account deleted</a><span class="pl-1">{{$featuredPost['author']['name']}}</a><span class="pl-1">{{\Carbon\Carbon::parse($featuredPost['publication_date'])->format('F j, Y')}}</span></div>
                                @endif
                            </div>
                            <div class="post_comments ml-auto ">
                                <a class="text-decoration-none text-secondary " href="/posts/{{$featuredPost['id']}}#comments">{{$featuredPost['num_comments']}} comments</a>
                            </div>
                        </div>
                        <div class="post_text my-2">
                            <p class="m-0 line-clamp">{{$featuredPost['body']}}</p>
                        </div>
                    </div>
                </div>
            </section>
        @endif

        <div class="row">
            <div  id="posts" class="col-md-8 mt-5 d-flex flex-row justify-content-center flex-wrap">
                {{-- @each('partials.post_preview', $mainPosts['data'], 'post')
                <nav class="mb-5" aria-label="Posts navigation">
                    @if (isset($mainPosts['paginator']))
                        {{$mainPosts['paginator']->links('pagination.links')}}
                    @endif
                </nav> --}}

                @include('partials.post_preview_list', [
                    'posts' => $mainPosts['data'],
                    'editable' => false,
                    'showAuthor' => true,
                    'showDate' => false,
                    'emptyMessage' => 'No posts found',
                    'paginator' =>  $mainPosts['paginator'],
                ])

            </div>

        <aside class="col-md-4 mb-5">

            <!-- /.latest -->
            <section class="mb-0">
                <h4 >LATEST</h4>
                <ul class="list-unstyled mb-0">
                    @each('partials.side_post', $latestPosts, 'post')
                </ul>
            </section>

            <!-- /.Popular -->
            <section class="mb-0">
                <h4>POPULAR</h4>
                <ul class="list-unstyled mb-0">
                    @each('partials.side_post', $hotPosts, 'post')
                </ul>
            </section>

            <!-- /.topics -->
            @include('partials.related_tags', ['tags' =>$randomTags])
        </aside>

        </div>
    </section>

</main>

@endsection