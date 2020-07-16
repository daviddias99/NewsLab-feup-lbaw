@if (count($comments) > 0)
    @if ($context == "versions")
        @each('partials.comment_version', $comments, 'comment')
        @if(isset($paginator))
            {{$paginator->links('pagination.links')}}
        @endif
    @elseif ($context == "profile")
        @each('partials.comment_preview', $comments, 'comment')
        @if(isset($paginator))
            {{$paginator->links('pagination.links')}}
        @endif
    @elseif ($context == "post")
        @each('partials.comment', $comments, 'content')
    @endif
@else
    <p class="text-muted font-italic text-center">{{$emptyMessage}}</p>
@endif