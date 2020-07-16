<?php $tag = (object) $tag; ?>
@if(strcmp($type, 'big') == 0)
    <a style="background-color: {{$tag->color}};" class="tag d-inline-block text-monospace text-decoration-none py-1 px-3 text-light mr-2" href="/tags/{{$tag->id}}">{{ucfirst($tag->name)}}</a>
@elseif(strcmp($type, 'spaced') == 0)
    <a style="background-color: {{$tag->color}};" class="tag d-inline-block text-monospace text-decoration-none small py-3 px-4 text-light m-1" href="/tags/{{$tag->id}}">{{ucfirst($tag->name)}}</a>
@elseif(strcmp($type, 'enorm') == 0)
    <a style="background-color: {{$tag->color}};" class="tag d-inline-block text-monospace align-self-center text-decoration-none large m-1 py-3 px-4 text-light" href="/tags/{{$tag->id}}">{{ucfirst($tag->name)}}</a>
@elseif(strcmp($type, 'cross') == 0)
    <div class="p-rel m-3 d-flex align-items-center">
        <a style="background-color: {{$tag->color}};" class="tag d-inline-block mx-2 my-1 text-monospace text-decoration-none small py-3 px-4 text-light pr-5" href="/tags/{{$tag->id}}">{{$tag->name}}</a>
        <div class="remove-tag-cross">
            <button type="button" class="close text-light o-100 p-2" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true" class="unsub-tag" data-id={{$tag->id}}>&times;</span>
            </button>
        </div>
    </div>
@else
    <a style="background-color: {{$tag->color}};" class="tag d-inline-block text-monospace text-decoration-none small py-1 px-3 text-light" href="/tags/{{$tag->id}}">{{ucfirst($tag->name)}}</a>
@endif