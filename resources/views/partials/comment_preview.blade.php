<article class="mb-4 px-3 border-left-hover-primary">
    <div class="w-100 d-flex justify-content-between my-1">
        <div>
            <h5 class="my-0"><span class="small">On</span> <a class="text-dark" href="/posts/{{$comment['post']['id']}}">{{$comment['post']['title']}}</a></h5>
            <p class="text-muted mb-0 small pt-1">{{\Carbon\Carbon::parse($comment['publication_date'])->diffForHumans(\Carbon\Carbon::now(), ['short' => true])}}</p>
        </div>
        <div>
            <div class="d-flex flex-row-reverse">
                <div class="dropdown">
                    <button class="btn btn-sm bg-white" type="button" id="dropdownMenuButtonCm{{$comment['id']}}" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        <i class="fas fa-ellipsis-h"></i>
                    </button>
                    <div class="dropdown-menu dropdown-menu-right" aria-labelledby="dropdownMenuButtonCm{{$comment['id']}}">
                        @if((Auth::check() && !Auth::user()->banned) && ($comment['author'] == null || Auth::user()->id != $comment['author']['id']))
                            <a class="dropdown-item" href="#" data-toggle="modal" data-target="#report-modal">Report</a>
                        @endif
                    </div>
                </div>
            </div>

            <div class="likes-row row mx-0">
                <div class="form-check form-check-inline ml-auto">
                    <input class="form-check-input check-rate" type="radio" name="inlineRadioOptions-{{$comment['id']}}" id="inlineRadio{{$comment['id']}}" value="like" {{$comment['userLikedComment'] === true ? "checked" : ""}}>
                    <label class="form-check-label" for="inlineRadio{{$comment['id']}}"><i class="fas fa-angle-up"></i></label>
                </div>
                <p class="mb-0 pr-sm-3">{{$comment['likes_difference']}}</p>
                <div class="form-check form-check-inline ml-auto">
                    <input class="form-check-input check-rate" type="radio" name="inlineRadioOptions-{{$comment['id']}}" id="inlineRadio-{{$comment['id']}}" value="dislike" {{$comment['userLikedComment'] === false ? "checked" : ""}}>
                    <label class="form-check-label" for="inlineRadio-{{$comment['id']}}"><i class="fas fa-angle-down"></i></label>
                </div>
            </div>
        </div>
    </div>
    <p>{{$comment['body']}}</p>
</article>