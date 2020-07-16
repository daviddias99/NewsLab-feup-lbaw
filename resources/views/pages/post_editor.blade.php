@extends('layouts.app')

@section('scripts')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.22.2/moment.min.js"></script>
    <script src="{{ asset('calendar/tempusdominus.js')}}"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/tempusdominus-bootstrap-4/5.0.1/css/tempusdominus-bootstrap-4.min.css" />
			
    <script  src="{{ asset('js/tags.js')}}" defer></script>
    <script  src="{{ asset('js/edit_post.js')}}" defer></script>
    <link rel="stylesheet" href="https://unpkg.com/easymde/dist/easymde.min.css">    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/dompurify/2.0.11/purify.min.js"></script>
    <script src="https://unpkg.com/easymde/dist/easymde.min.js"></script>
@endsection

@section('title', 'NewsLab - Post Editor')

@section('content')

@if(isset($post))
    <form id="post_editor" action="/api/posts/{{$post->content_id}}" method="POST">
@else
    <form id="post_editor" action="/api/posts" method="POST">
@endif
    @if(isset($post))
        <div id="bannerImage" title="Post photo"  class="img-header mb-4" style="background-image: url('{{URL::asset('storage/images/posts/' . $post->photo)}}')">
    @else
        <div id="bannerImage" title="Post photo"  class="img-header mb-4" style="background-image: url('{{URL::asset('storage/images/posts/default.png')}}')">
    @endif
        <div class="overlay"></div>
        <div class="menu bottomRightMenu row">
            <div class="form-group mb-0">
                <label for="coverPic" class="text-white bg-success btn mb-0 rounded-0">
                    <i data-toggle="tooltip" data-placement="top" title="Edit photo" class="fas fa-pencil-alt"></i>
                </label>
                @if (isset($post))
                    <input type="file" class="form-control-file d-none" accept="image/*" id="coverPic" data-hasnewfile="oldImage" data-defaultimgpath={{URL::asset('storage/images/posts/default.png')}}>
                @else
                    <input type="file" required class="form-control-file d-none" accept="image/*" id="coverPic" data-hasnewfile="no" data-defaultimgpath={{URL::asset('storage/images/posts/default.png')}}>
                @endif
            </div>
            <button id="deleteImageButton" class="btn btn-secondary mx-2 rounded-0">
                <i data-toggle="tooltip" data-placement="top" title="Remove photo" class="fas fa-times"></i>
            </button>
        </div>
    </div>
    
    <div  class="container flex-grow-1">
        <!-- /.blog-post -->
        <div class="blog-post pb-4">
            <p id="photoError" class="small text-primary"></p>
            <fieldset class="mb-3">
                <legend class="col-form-label py-0 mb-1"><strong>Type: *</strong></legend>
                <div class="custom-control custom-radio custom-control-inline">
                    <input class="custom-control-input" type="radio" name="postType" id="news" value="News"
                    @if(isset($post) && $post->type == "News")
                        checked
                    @endif
                    >
                    <label class="custom-control-label" for="news">
                        News
                    </label>
                </div>
                <div class="custom-control custom-radio custom-control-inline">
                    <input class="custom-control-input" type="radio" name="postType" id="opinion" value="Opinion"
                    @if(isset($post) && $post->type == "Opinion")
                        checked
                    @endif
                    >
                    <label class="custom-control-label" for="opinion">
                        Opinion
                    </label>
                </div>
                <p id="postTypeError" class="small text-primary"></p>
            </fieldset>
            <div class="form-group col-md-10 col-xl-8 px-0">
                <label for="tagsInput"><strong>Tags:</strong> (max 2) *</label>
                <input class="form-control rounded-0 text-dark tags" id="tagsInput" placeholder="Choose your tags" required
                @if(isset($post))
                    <?php 
                        $value = "";
                        foreach($post->tags as $tag)
                            $value .= $tag->name . ",";
                    ?>
                    value="{{$value}}"
                @endif
                >
                <p id="tagsError" class="small text-primary"></p>
            </div>
            <div class="form-group col-md-10 col-xl-8 px-0">
                <label for="titleInput"><strong>Title: *</strong></label>
                <input class="form-control rounded-0 text-dark" id="titleInput" placeholder="Choose one title" required pattern="[A-Za-z0-9?+*_!#$%,\/;.&\s-]{3,100}" title="Title must have between 3 and 100 letters, numbers or symbols like ?+*_!#$%,\/;.&-"
                @if(isset($post))
                    value="{{$post->title}}"
                @endif
                >
                <p id="titleError" class="small text-primary"></p>
            </div>
    
            <hr>
    
            <div class="form-group mb-0">
                <label for="postBodyInput" class="col-form-label"><strong>Post: *</strong></label>
                <textarea class="form-control rounded-0 text-dark" rows="35" id="postBodyInput" placeholder="Write your post here..." required>@if(isset($post)){{$post->content->body}}@endif</textarea>
                <p id="bodyError" class="small text-primary"></p>
            </div>
            <p class="small text-muted">* Required Input</p>

            <div class="row ml-0">
                <button type="submit" class="mt-0 btn btn-primary rounded-0">Submit</button>
                @if(!isset($post) || !$post->visible)
                <div class="form-group mb-0 ml-2">
                    <div class="input-group date" id="scheduledDate" data-target-input="nearest">
                        <input type="text" class="form-control datetimepicker-input rounded-0" data-target="#scheduledDate" placeholder="Now" pattern="(0[1-9]|1[0-2])/(0[1-9]|[1-2][0-9]|3[0-1])/[0-9]{4} ([1-9]|1[0-2]):[0-5][0-9] [A|P]M" title="Invalid date - time format - mm/dd/yyy hh:mm AM/PM"/>
                        <div class="input-group-append" data-target="#scheduledDate" data-toggle="datetimepicker">
                            <div data-toggle="tooltip" data-placement="top" title="Publishing date" class="input-group-text rounded-0"><i class="fa fa-calendar"></i></div>
                        </div>
                    </div>
                </div>
                @endif
            </div>
            <p id="dateError" class="small text-primary"></p>
        </div>
    
    </div>
</form>

@endsection
