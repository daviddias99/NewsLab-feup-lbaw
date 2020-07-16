<article class="my-5">
    <h5 class="my-3"><span class="small">On </span><span class="font-weight-bold">{{$comment['modification_date'] == null ? \Carbon\Carbon::parse($comment['publication_date'])->toDateString() : \Carbon\Carbon::parse($comment['modification_date'])->toDateString()}}:</span></h5>
    <article class="mb-3 border-left px-3">
        <div class="card border-0 pb-1">
            <div class="row no-gutters">
                <div class="col-2 col-sm-1">
                    @if($comment['author'] !== null)
                        <a href="/users/{{$comment['author']['id']}}">
                            @if($comment['author']['photo'] !== null)
                                <div class="square rounded-circle" title="Author photo" style="background-image: url('{{URL::asset('storage/images/users/' . $comment['author']['photo'])}}')"></div>
                            @else
                                <div class="square rounded-circle" title="Author photo" style="background-image: url('{{URL::asset('storage/images/users/default.png')}}')"></div>
                            @endif
                        </a>
                    @else
                        <a>
                            <div class="square rounded-circle" title="Author photo" style="background-image: url('{{URL::asset('storage/images/users/default.png')}}')"></div>
                        </a>
                    @endif
                </div>
                <div class="col-10 col-sm-11 d-flex justify-content-between align-items-start">
                    <div class="d-flex flex-column">
                        <div class="card-body py-1 d-flex flex-column flex-sm-row align-items-sm-center">
                            @if($comment['author'] !== null)
                                <h5 class="card-title my-1"><a href="/users/{{$comment['author']['id']}}" class="text-dark mr-2">{{$comment['author']['name']}}  @if($comment['author']['verified'])<i class="fas fa-check-circle"></i>@endif</a></h5>
                            @else
                                @if($comment['body'] !== null)
                                    <h5 class="card-title my-1 mr-2 text-muted font-italic">Account deleted</a></h5>
                                @else
                                    <h5 class="card-title my-1 mr-2 text-muted font-italic">Comment deleted</a></h5>
                                @endif
                            @endif
                            <p class="card-text small text-muted pl-2 my-auto border-left">{{\Carbon\Carbon::parse($comment['publication_date'])->diffForHumans(\Carbon\Carbon::now(), ['short' => true])}} 
                                @if($comment['edited'])
                                    <a class="font-italic text-muted">(Edited)</a>
                                @endif
                            </p>
                        </div>
                        @if($comment['body'] !== null) 
                            <p id="comment-body-{{$comment['id']}}" class="px-0 px-sm-3 py-2 m-0 ml-sm-n3">{{$comment['body']}}</p>
                        @else
                            <p class="px-0 px-sm-3 py-2 m-0 ml-sm-n3 text-muted font-italic">This comment has been deleted and is no longer available.</p>
                        @endif
                    </div>
                    @if($comment['author'] === null &&  $comment['body'] === null)
                        <div id="options-{{$comment['id']}}" class="py-1 flex-shrink-0">
                            <p class="mb-0 pr-sm-3">
                                @if($comment['likes_difference'] > 0)
                                    <i class="fas fa-angle-up"></i>
                                @elseif($comment['likes_difference'] == 0)
                                    <i class="fas fa-minus"></i>
                                @else
                                    <i class="fas fa-angle-down"></i>
                                @endif
                            &nbsp;&nbsp;{{$comment['likes_difference']}}</p>
                        </div>
                    @else
                        <div id="options-{{$comment['id']}}" class="py-1 flex-shrink-0">
                            <div class="d-flex flex-row-reverse">
                                <div class="dropdown">
                                    <button class="btn  btn-sm bg-white" type="button" id="dropdownMenuButton{{$comment['id']}}" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                        <i class="fas fa-ellipsis-h"></i>
                                    </button>
                                    <div class="dropdown-menu " aria-labelledby="dropdownMenuButton{{$comment['id']}}">
                                        @if((Auth::check() && !Auth::user()->banned) && ($comment['author'] == null || Auth::user()->id != $comment['author']['id']))
                                            <a class="dropdown-item" href="#" data-toggle="modal" data-target="#report-modal">Report</a>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <div class="likes-row row mx-0">
                                @if ($comment['most_recent'])
                                    <div class="form-check form-check-inline ml-auto">
                                        <input class="form-check-input check-rate" type="radio" name="inlineRadioOptions-{{$comment['id']}}" id="inlineRadio{{$comment['id']}}" value="like" {{$comment['userLikedComment'] === true ? "checked" : ""}}>
                                        <label class="form-check-label" for="inlineRadio{{$comment['id']}}"><i class="fas fa-angle-up"></i></label>
                                    </div>
                                @endif
                                <p class="mb-0 pr-sm-3">{{$comment['likes_difference']}} </p>
                                @if ($comment['most_recent'])
                                    <div class="form-check form-check-inline ml-auto">
                                        <input class="form-check-input check-rate" type="radio" name="inlineRadioOptions-{{$comment['id']}}" id="inlineRadio-{{$comment['id']}}" value="dislike" {{$comment['userLikedComment'] === false ? "checked" : ""}}>
                                        <label class="form-check-label" for="inlineRadio-{{$comment['id']}}"><i class="fas fa-angle-down"></i></label>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </article>
</article>