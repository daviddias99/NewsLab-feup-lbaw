@extends('layouts.app')

@section('scripts')
<script 
    src="{{ asset('js/saved_posts.js')}}"
defer></script>
@endsection

@section('title', 'NewsLab - Saved Posts' )

@section('content')
<main  class="container">

    <div class="row mt-5">
        <h1 class="text-left mx-3 mb-0">Your Saved Posts</h1>
        <div class="dropdown d-flex flex-column justify-content-center">
            <button class="btn btn-small bg-primary" type="button" id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                <svg height="1.25rem" viewBox="-4 0 393 393.99003" width="1.25rem">
                    <g stroke="white">
                        <path stroke-width="8" fill="white" d="m368.3125 0h-351.261719c-6.195312-.0117188-11.875 3.449219-14.707031 8.960938-2.871094 5.585937-2.3671875 12.3125 1.300781 17.414062l128.6875 181.28125c.042969.0625.089844.121094.132813.183594 4.675781 6.3125 7.203125 13.957031 7.21875 21.816406v147.796875c-.027344 4.378906 1.691406 8.582031 4.777344 11.6875 3.085937 3.105469 7.28125 4.847656 11.65625 4.847656 2.226562 0 4.425781-.445312 6.480468-1.296875l72.3125-27.574218c6.480469-1.976563 10.78125-8.089844 10.78125-15.453126v-120.007812c.011719-7.855469 2.542969-15.503906 7.214844-21.816406.042969-.0625.089844-.121094.132812-.183594l128.683594-181.289062c3.667969-5.097657 4.171875-11.820313 1.300782-17.40625-2.832032-5.511719-8.511719-8.9726568-14.710938-8.960938zm-131.53125 195.992188c-7.1875 9.753906-11.074219 21.546874-11.097656 33.664062v117.578125l-66 25.164063v-142.742188c-.023438-12.117188-3.910156-23.910156-11.101563-33.664062l-124.933593-175.992188h338.070312zm0 0"></path>
                    </g>
                </svg>
            </button>
            <div class="dropdown-menu dropdown-menu-right" aria-labelledby="dropdownMenuButton">
                <a class="dropdown-item" href="#alpha">Alphabetical</a>
                <a class="dropdown-item" href="#recent">Most Recent</a>
                <a class="dropdown-item" href="#numerical">Upvotes</a>
            </div>
        </div>
    </div>

    <hr>
    <div id="saved-posts" class="mt-5 d-flex flex-row justify-space-between flex-wrap" data-id={{Auth::user()->id}}>
        <div class="tab-pane">
            @include('partials.post_preview_list', [
                'posts' => $versions['data'],
                'editable' => false,
                'showAuthor' => true,
                'showDate' => false,
                'showCross' => true,
                'emptyMessage' => 'No saved posts to show.',
                'paginator' => $versions['paginator']
            ])
        </div>
    </div>
</main>

@endsection