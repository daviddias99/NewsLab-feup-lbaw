<section class="mb-0">
    <h4 class=" pb-2">TAGS</h4>
    @foreach($tags as $tag)
        @include('partials.tag', [ 'tag' => $tag, 'type' => 'spaced'])
    @endforeach
</section>