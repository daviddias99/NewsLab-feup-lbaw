@extends('layouts.app')

@section('scripts')

<link rel="stylesheet" href="{{ asset('range/nouislider.css')}}">
<script  src="{{ asset('range/nouislider.min.js')}}"></script>

<script  src="{{ asset('js/filter.js')}}" defer></script>
<script  src="{{ asset('js/search.js')}}" defer></script>

@if(Auth::check())
    <script  src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.22.2/moment.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/tempusdominus-bootstrap-4/5.0.1/css/tempusdominus-bootstrap-4.min.css" />
    <script  src="{{ asset('calendar/tempusdominus.js')}}"></script>
@endif

@endsection

{{-- TODO: por query no titulo --}}
@section('title', 'NewsLab - Search: ' .  request()->query()['search'] )

@section('content')

<div id="wrapper" class="flex-grow-1 toggled">
    @include('partials.filter', ['full' => true])
    <!-- sidebar-wrapper  -->
    <main  class="page-content container">
        <div id="search-big" class="md-form active-pink-2 mt-4 row align-items-center pr-3">
            <div class="col-auto pr-0">
                <a href="/search">
                    <h3 class="my-auto">
                        <i class="fas fa-search px-0" aria-hidden="true"></i>
                    </h3>
                </a>
            </div>
            <div class="col px-0">
                <div class="input-group input-group-lg">
                    <input class="form-control text-muted border-top-0 border-left-0 border-right-0 border-bottom-1" type="search" placeholder="Search" aria-label="Search" value="{{$_GET['search']}}" pattern="[A-Za-z0-9?+*_!#$%,\/;.&\s-]{2,}" title="Search must have at least 2 letters, numbers or symbols like ?+*_!#$%,\/;.&-">
                </div>
            </div>
        </div>
        <p id="searchError" class="small text-primary"></p>

        <!-- news -->
        <section id="news">
            <div class="row mt-5 justify-content-between">
                <h2 class="text-left mx-3 mb-0">NEWS</h2>
            </div>

            <hr>
            <div class="mt-5 tab-pane">
                @include('partials.post_preview_list', [
                    'posts' => $posts['news']['data'],
                    'editable' => false,
                    'showAuthor' => true,
                    'showDate' => false,
                    'emptyMessage' => 'No News found',
                    'paginator' =>  $posts['news']['paginator'],
                ])
            </div>
        </section>

        <!-- opinions -->
        <section id="opinions">
            <div class="row mt-5 justify-content-between">
                <h2 class="text-left mx-3 mb-0">OPINIONS</h2>
            </div>
            <hr>
            <div class="mt-5 tab-pane">
                @include('partials.post_preview_list', [
                    'posts' => $posts['opinion']['data'],
                    'editable' => false,
                    'showAuthor' => true,
                    'showDate' => false,
                    'emptyMessage' => 'No Opinions found',
                    'paginator' =>  $posts['opinion']['paginator'],
                ])
            </div>
        </section>

        <!-- authors -->
        <section id="users">
            <div class="row mt-5 justify-content-between">
                <h2 class="text-left mx-3 mb-0">AUTHORS</h2>
            </div>

            <hr>
            <div class="tab-pane mt-5">
                @include('partials.user_preview_list', [
                    'users' => $users['data'],
                    'emptyMessage' => 'No Users found',
                    'paginator' =>  $users['paginator'],
                ])
            </div>
        </section>

        <!-- tags -->
        <section id="tags">
            <div class="row mt-5 justify-content-between">
                <h2 class="text-left mx-3 mb-0">TAGS</h2>
                <div class="col-auto">
                </div>
            </div>

            <hr>
            <div class="tags-sect d-flex flex-row justify-content-center mt-5 align-items-center flex-wrap mb-4">
                @if(empty($tags['tags']))
                    <p class="text-muted font-italic text-center">No Tags found</p>
                @else
                    @foreach ($tags['tags'] as $tag)
                        <article class="m-3 d-flex align-items-center">
                            @include('partials.tag', [ 'tag' => $tag, 'type' => 'spaced'])
                        </article>
                    @endforeach
                @endif
            </div>
        </section>
    </main>
</div>

@endsection
