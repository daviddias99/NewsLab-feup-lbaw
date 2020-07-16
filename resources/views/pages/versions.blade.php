@extends('layouts.app')

@section('scripts')
@if(Auth::check())
    <script  
        src="{{ asset('js/rate/rate.js')}}"
    defer></script>
@else
    <script  
    src="{{ asset('js/rate/rate_block.js')}}"
    defer></script>
@endif

<script  
    src="{{ asset('js/pagination/std_pagination.js')}}"
defer></script>
@endsection

@section('title', 'NewsLab - Versions' )

@section('content')
<main  class="container flex-grow-1">
    @if ($type == "comment")
        <div data-logged_in={{Auth::check() ? "yes" : "no"}} class="tab-pane">
            @include('partials.comment_preview_list', [
                'comments' => $versions['data'],
                'emptyMessage' => 'No different versions to show.',
                'context' => 'versions',
                'paginator' => $versions['paginator']
            ])
        </div>
    @elseif ($type == "post")
        <div class="mt-5 flex-row justify-space-between flex-wrap">
            <div class="tab-pane">
                @include('partials.post_preview_list', [
                    'posts' => $versions['data'],
                    'editable' => false,
                    'showAuthor' => true,
                    'showDate' => true,
                    'emptyMessage' => 'No different versions to show.',
                    'paginator' => $versions['paginator']
                ])
            </div>
        </div>
    @else
        <p class="text-muted font-italic text-center">No different versions to show</p>
    @endif

</main>
@endsection
