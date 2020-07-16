@extends('layouts.app')

@section('scripts')
<script  
    src="{{ asset('js/manage_subs.js')}}"
defer></script>
<script  
    src="{{ asset('js/pagination/std_pagination.js')}}"
defer></script>
@endsection

@section('title', 'NewsLab - Subscriptions' )

@section('content')
<main  class="container" data-id={{Auth::check() ? Auth::user()->id : null}}>

    <h1 class="text-left mt-5">My Subscriptions</h1>

    <hr>

    <section class="container">
        <!-- tags -->
        <div class="row my-4">
            <h3>Tags</h3>
            <div class="d-flex flex-row flex-wrap mt-4 justify-content-center align-items-center">
                @if (count($tags) == 0)
                    <p class="mt-5 text-muted font-italic text-center">No subscribed tags to show</p>
                @else
                    @foreach($tags as $tag)
                        @include('partials.tag', [ 'tag' => $tag, 'type' => 'cross'])
                    @endforeach
                @endif
            </div>
        </div>
        <!-- authors -->
        <div class="row my-4">
            <h3 class="my-0 mr-2">Authors</h3>
        </div>

        <div class="tab-pane">
            @include('partials.user_preview_list', [
                'users' => $users['data'],
                'sub' => true,
                'emptyMessage' => 'No subscribed users to show',
                'paginator' => $users['paginator']
            ])
        </div>
    </section>
</main>

@endsection