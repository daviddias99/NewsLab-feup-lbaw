<!-- /.comment -->
@if(isset($content['replies']))
<div data-cid="{{$content['id']}}" class="mb-5">
@endif
    <article @if(isset($content['replies']))
                @if($content['author'] === null &&  $content['body'] === null)
                    class="comment-area deleted mb-3 border-left px-3"
                @else
                    class="comment-area mb-3 border-left px-3"
                @endif
            @else
                class="reply-area ml-5 mb-3 border-left px-3" data-cid="{{$content['id']}}"
            @endif>
        <div class="card border-0 pb-1">
            <div class="row no-gutters">
                <div class="col-2 col-sm-1 col-md-2 col-lg-1">
                    @if($content['author'] !== null)
                        <a href="/users/{{$content['author']['id']}}">
                            @if($content['author']['photo'] !== null)
                                <div class="square rounded-circle" title="Author photo" style="background-image: url('{{URL::asset('storage/images/users/' . $content['author']['photo'])}}')"></div>
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
                <div class="col-10 col-sm-11 col-md-10 col-lg-11 d-flex justify-content-between align-items-start">
                    <div class="d-flex flex-column flex-fill">
                        <div class="card-body py-1 d-flex flex-column flex-sm-row align-items-sm-center">
                            @if($content['author'] !== null)
                                <h5 class="card-title my-1"><a href="/users/{{$content['author']['id']}}" class="text-dark mr-2">{{$content['author']['name']}}  @if($content['author']['verified'])<i class="fas fa-check-circle"></i>@endif</a></h5>
                            @else
                                @if($content['body'] !== null)
                                    <h5 class="card-title my-1 mr-2 text-muted font-italic">Account deleted</h5>
                                @else
                                    <h5 class="card-title my-1 mr-2 text-muted font-italic">Comment deleted</h5>
                                @endif
                            @endif
                            <p class="card-text small text-muted pl-2 my-auto border-left">{{\Carbon\Carbon::parse($content['publication_date'])->diffForHumans(\Carbon\Carbon::now(), ['short' => true])}} @if($content['edited'])<a @if(isset($content['replies']))
                                                                                                                                            href="/comments/{{$content['id']}}/versions"
                                                                                                                                        @else
                                                                                                                                            href="/replies/{{$content['id']}}/versions"
                                                                                                                                        @endif
                                                                                                                                        class="font-italic text-muted">(Edited)</a>@endif</p>
                            @if(Auth::check() && ($content['author'] != null && Auth::user()->id == $content['author']['id']))
                            <div id="edit-{{$content['id']}}" class="d-none ml-auto">
                                <button class="btn btn-sm btn-primary hover-bold px-4 submit-edit" data-cid="{{$content['id']}}" type="submit">
                                    <i class="fas fa-paper-plane"></i>
                                </button>
                                <button class="btn btn-sm btn-outline-secondary mx-2 cancel-edit" data-cid="{{$content['id']}}" >
                                    <i class="fa fa-times"></i>
                                </button>
                            </div>
                            @endif
                        </div>
                        @if($content['body'] !== null)
                            <p id="comment-body-{{$content['id']}}" class="px-0 px-sm-3 py-2 m-0 ml-sm-n3">{{$content['body']}}</p>
                            @if(Auth::check() && ($content['author'] != null && Auth::user()->id == $content['author']['id']))
                            <div class="px-0 px-sm-3 py-2 m-0 ml-sm-n3 d-none">
                                <textarea id="edit-body-{{$content['id']}}" class="form-control autoExpand border-primary border-right-0 border-top-0 border-left-0 p-0" data-min-rows='1' rows="1" ></textarea>
                                <p id="error-info-{{$content['id']}}" class="small text-primary mb-0"></p>
                            </div>
                            @endif
                        @else
                            <p class="px-0 px-sm-3 py-2 m-0 ml-sm-n3 text-muted font-italic">This comment has been deleted and is no longer available.</p>
                        @endif
                    </div>
                    @if($content['author'] === null &&  $content['body'] === null)
                    <div id="options-{{$content['id']}}" class="py-1 flex-shrink-0">
                        <p class="mb-0 pr-sm-3">
                            @if($content['likes_difference'] > 0)
                                <i class="fas fa-angle-up"></i>
                            @elseif($content['likes_difference'] == 0)
                                <i class="fas fa-minus"></i>
                            @else
                                <i class="fas fa-angle-down"></i>
                            @endif
                        &nbsp;&nbsp;{{$content['likes_difference']}}</p>
                    </div>
                    @else
                    <div id="options-{{$content['id']}}" class="py-1 flex-shrink-0">
                        <div class="d-flex flex-row-reverse">
                            <div class="dropdown">
                                <button class="btn  btn-sm bg-white" type="button" id="dropdownMenuButton{{$content['id']}}" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                    <i class="fas fa-ellipsis-h"></i>
                                </button>
                                <div class="dropdown-menu " aria-labelledby="dropdownMenuButton{{$content['id']}}">
                                    @if(Auth::check())
                                        @if(isset($content['replies']))
                                            @if(($content['author'] != null && Auth::user()->id == $content['author']['id']))
                                                @if (!Auth::user()->banned)
                                                    <a class="edit-comment dropdown-item" href="{{$content['id']}}">Edit Comment</a>
                                                @endif
                                                <a class="delete-comment dropdown-item" href="/api/comments/{{$content['id']}}">Delete Comment</a>
                                            @elseif(Auth::user()->isAdmin())
                                                <a class="delete-comment dropdown-item" href="/api/comments/{{$content['id']}}">Delete Comment</a>
                                            @endif
                                        @else
                                            @if(($content['author'] != null && Auth::user()->id == $content['author']['id']))
                                                @if (!Auth::user()->banned)
                                                    <a class="edit-reply dropdown-item" href="{{$content['id']}}">Edit Reply</a>
                                                @endif
                                                <a class="delete-reply dropdown-item" href="/api/replies/{{$content['id']}}">Delete Reply</a>
                                            @elseif(Auth::user()->isAdmin())
                                                <a class="delete-reply dropdown-item" href="/api/replies/{{$content['id']}}">Delete Reply</a>
                                            @endif
                                        @endif
                                    @endif
                                    {{-- @if((Auth::check() && !Auth::user()->banned) && ($content['author'] == null || Auth::user()->id != $content['author']['id'])) --}}
                                    @if (! (Auth::check() && isset($content['author']['id']) && Auth::user()->id == $content['author']['id']))
                                        <a class="dropdown-item" href="{{$content['id']}}" data-toggle="modal" data-target="#report-modal">Report</a>
                                    @endif
                                </div>
                            </div>
                            <button class="btn btn-sm bg-white reply" type="button" data-toggle="collapse" @if(isset($content['replies']))
                                                                                                        data-target="#reply{{$content['id']}}-collapse"
                                                                                                    @else
                                                                                                        data-target="#reply{{$comment_id}}-collapse"
                                                                                                    @endif aria-expanded="false" >
                                <i class="fa fa-reply"></i>
                            </button>
                        </div>

                        <div class="likes-row row mx-0">
                            <div class="form-check form-check-inline ml-auto">
                                <input class="form-check-input check-rate {{$content['userLikedContent'] === true ? "checked" : ""}}" type="radio" name="inlineRadioOptions-{{$content['id']}}" id="inlineRadio{{$content['id']}}" value="like" {{$content['userLikedContent'] === true ? "checked" : ""}}>
                                <label class="form-check-label" for="inlineRadio{{$content['id']}}"><i class="fas fa-angle-up"></i></label>
                            </div>
                            <p class="mb-0 pr-sm-3">{{$content['likes_difference']}} </p>
                            <div class="form-check form-check-inline ml-auto">
                                <input class="form-check-input check-rate {{$content['userLikedContent'] === false ? "checked" : ""}}" type="radio" name="inlineRadioOptions-{{$content['id']}}" id="inlineRadio-{{$content['id']}}" value="dislike" {{$content['userLikedContent'] === false ? "checked" : ""}}>
                                <label class="form-check-label" for="inlineRadio-{{$content['id']}}"><i class="fas fa-angle-down"></i></label>
                            </div>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </article>

    @if(isset($content['replies']))
        @foreach($content['replies'] as $reply)
            @include('partials.comment', ['content'=> $reply, 'comment_id' => $content['id']])
        @endforeach
    <!-- /.form reply -->
    <div class="ml-5">
        <form id="reply{{$content['id']}}-collapse" class="collapse bg-light p-2 rounded reply-form"  method="post">
            <button id="submit-reply{{$content['id']}}" class="btn btn-sm btn-primary hover-bold float-right px-4" type="submit"><i class="fas fa-paper-plane"></i></button>
            <div class="form-group mb-1">
                <label class="font-weight-bold p-1" for="replyArea{{$content['id']}}">Reply *</label>
                <textarea class="bg-light form-control border-right-0 border-top-0 border-left-0" id="replyArea{{$content['id']}}" rows="3" required></textarea>
                <p class="error-info small text-muted mb-0">* Required Input</p>

            </div>
        </form>
    </div>
</div>
@endif
