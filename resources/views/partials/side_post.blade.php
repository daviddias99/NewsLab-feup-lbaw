<li class="mb-4">
    <article class="d-flex flex-row align-items-start justify-content-start">
        <div class="col-md-4 p-0 avatar h-6">
            <img class="mw-100" src={{URL::asset('storage/images/posts/'. $post->photo )}} alt="News Banner">
        </div>
        <div class="col-md-8 pl-4">
            @foreach($post->tags as $tag)
                @include('partials.tag', [ 'tag' => $tag, 'type' => 'none'])
            @endforeach
            <div class="mt-1">
                <a class="text-dark" href="/posts/{{$post->id}}">
                    <h6>{{$post->title}}</h6>
                </a>
            </div>
        <div class="small mt-2 text-warning">
            @if ($post->author !== null)
                <a href="/users/{{$post->author->id}}" class="text-warning border-right pr-1 border-warning">
                    {{$post->author->name}}
                </a>
            @else
                <a class="text-warning border-right pr-1 border-warning font-italic">
                    Account Deleted
                </a>
            @endif
            <span class="pl-1">{{\Carbon\Carbon::parse($post->publication_date)->format('F j, Y')}}</span></div>
        </div>
    </article>
</li>