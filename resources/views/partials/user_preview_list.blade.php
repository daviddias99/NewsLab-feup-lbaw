<div class="row justify-content-center flex-wrap">
    @if (count($users) == 0)
        <p class="text-muted font-italic text-center">{{$emptyMessage}}</p>
    @elseif(isset($sub) && $sub)
        @each('partials.user_card_sub', $users, 'user')
    @else
        @each('partials.user_card', $users, 'user')
    @endif
</div>

@if (isset($paginator) && count($users) > 0)
    {{$paginator->links('pagination.links')}}
@endif