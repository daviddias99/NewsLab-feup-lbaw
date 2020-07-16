<?php
    if (! isset($editable))
        $editable = false;

    if (! isset($showAuthor))
        $showAuthor = true;

    if (! isset($showDate))
        $showDate = false;

    if (! isset($showCross))
        $showCross = false;
?>

<article class="col-md-6 col-lg-4 @if (! $post['visible']) private @endif" data-id={{$post['id']}}>
    @if ($showDate)
        <h5 class="my-3"><span class="small">On </span><span class="font-weight-bold">{{$post['modification_date'] == null ? \Carbon\Carbon::parse($post['publication_date'])->toDateString() : \Carbon\Carbon::parse($post['modification_date'])->toDateString()}}:</span></h5>
    @endif
    <div class="card mb-4 border-0">
        <div class="square card-img-top h-13 p-rel pb-0" title="Post photo" style="background-image: url('{{URL::asset('storage/images/posts/' . $post['photo'])}}')">
            @if($showCross)
                <button class="topRight btn btn-sm btn-secondary m-2">
                    <i class="fa fa-times"></i>
                </button>
            @endif
            @if($editable && ((Auth::check() && !Auth::user()->banned) && ($post['author'] != null && Auth::user()->id == $post['author']['id'])))
                <a class="topRight btn btn-sm btn-light m-2 " data-toggle="tooltip" data-placement="top" title="Edit post" href="/posts/{{$post['id']}}/edit">
                    <i class="fas fa-edit"></i>
                </a>
            @endif
            @if (! $post['visible'])
                <button data-id={{$post['id']}} data-href="/api/posts/{{$post['id']}}/visibility" data-toggle="tooltip" data-placement="top" title="Make post public" class="topRightSecondary btn btn-sm btn-light m-2">
                    <i class="far fa-eye-slash"></i>
                </button>
            @endif
            <div class="likes bg-white px-2">
                @if ($post['likes_difference'] > 0)
                    <i class="fas fa-angle-up text-primary"></i> {{$post['likes_difference']}}
                @elseif ($post['likes_difference'] < 0)
                    <i class="fas fa-angle-down text-primary"></i> {{$post['likes_difference']}}
                @else
                    <i class="text-success fas fa-minus"></i> 0
                @endif
            </div>
        </div>
        <div class="card-body px-0">
            @foreach ($post['tags'] as $tag)
                @include('partials.tag', [ 'tag' => $tag, 'type' => 'none'])
            @endforeach
            <div class="mt-1"><a class="text-dark font-weight-bold" href="/posts/{{$post['id']}}"><h6 class="mb-0">{{$post['title']}}</h6></a></div>
            <small class="text-muted">
                @if($showAuthor)
                    <a href="/users/{{$post['author']['id']}}" class="text-warning border-right pr-2 border-warning">{{$post['author']['name']}}</a>
                @endif
                <span class="@if($showAuthor) ml-1 @endif my-1">{{\Carbon\Carbon::parse($post['publication_date'])->format('F j, Y')}}</span>
                @if($post['edited'])
                        <a href="/posts/{{$post['id']}}/versions" class="font-italic text-muted">(Edited)</a>
                @endif
            </small>
            <p class="card-text line-clamp">{{$post['body']}}</p>
        </div>
    </div>
</article>