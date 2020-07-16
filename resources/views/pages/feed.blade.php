@extends('layouts.app')

@section('title', 'NewsLab - Feed')

@section('scripts')
<script src="{{ asset('js/pagination/std_pagination.js')}}" defer></script>
@endsection

@section('content')
<main class="container flex-grow-1">
    <h1 class="text-left mt-5" id="type" data-type="Feed">Feed</h1>
    <hr>

    <div>
        <div class="row">
            <div class="col">
                <section id="authors">
                    <div class="row mt-1 justify-content-between">
                        <h2 class="text-left mx-3 mb-0">SUBSCRIBED AUTHORS</h2>
                    </div>
                    <hr>
                    <div class="mt-5 tab-pane">
                        @include('partials.post_preview_list', [
                        'posts' => $authorPosts['data'],
                        'editable' => false,
                        'showAuthor' => true,
                        'showDate' => false,
                        'emptyMessage' => 'No posts from subscribed authors',
                        'paginator' => $authorPosts['paginator']
                        ])
                    </div>
                </section>
                <section id="tags">
                    <div class="row mt-1 justify-content-between">
                        <h2 class="text-left mx-3 mb-0">SUBSCRIBED TAGS</h2>
                    </div>
                    <hr>
                    <div class="mt-5 tab-pane">
                        @include('partials.post_preview_list', [
                        'posts' => $tagPosts['data'],
                        'editable' => false,
                        'showAuthor' => true,
                        'showDate' => false,
                        'emptyMessage' => 'No posts with the subscribed tags',
                        'paginator' => $tagPosts['paginator']
                        ])
                    </div>
                </section>
            </div>

            <aside class="col-md-4 mb-5">
                <!-- /.Popular -->
                <section class="mb-0">
                    <h4>POPULAR</h4>
                    <ul class="list-unstyled mb-0">
                        @each('partials.side_post', $hotPosts, 'post')
                    </ul>
                </section>

            </aside>
        </div>
    </div>

</main>

@endsection