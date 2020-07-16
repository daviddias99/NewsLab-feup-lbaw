
<div class="row justify-content-center flex-wrap">
    <!-- ./post -->
    @if (count($posts) == 0)
        <p class="text-muted font-italic text-center">{{$emptyMessage}}</p>
    @else
        @foreach ($posts as $post)
            @include('partials.post_preview', [ 'post' => $post, 'editable' => $editable, 'showAuthor' => $showAuthor , 'showDate' => $showDate ])
        @endforeach
    @endif
</div>

@if (isset($paginator) && count($posts) > 0)
    {{$paginator->links('pagination.links')}}
@endif