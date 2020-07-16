
<section>
    @if(empty($tags[0]))
        <p class="text-muted font-italic text-center">No Tags found</p>
    @else
        @foreach ($tags as $tag)
            <article class="m-3 d-flex align-items-center">
                @include('partials.tag', [ 'tag' => $tag, 'type' => 'spaced'])
            </article>
        @endforeach
    @endif
</section>