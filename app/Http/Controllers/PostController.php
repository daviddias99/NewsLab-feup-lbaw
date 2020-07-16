<?php

namespace App\Http\Controllers;

use App\Post;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Database\QueryException;
use Carbon\Carbon;
use App\Tag;
use App\Rating;
use App\Content;
use App\Admin;
use App\Utils\ImageUpload;
use App\Utils\CollectionHelper;
use DateTime;


class PostController extends Controller {
    /**
     * Show the Post.
     *
     * @param  int  $id
     * @return View
     */
    public function show($id) {
        // get post information
        $post = PostController::getPost($id);
        if ($post->getStatusCode() !== 200) {
            abort($post->getStatusCode());
        }
        $post = json_decode($post->getContent());

        $userLikedPost = null;
        if (Auth::check()) {
            $userLikedPost = Rating::likeValue($post->id, Auth::user()->id);
        }
        $post->userLikedPost = $userLikedPost;

        // get comments information
        $request = new Request();
        $comments = PostController::getComments($id, $request, true);

        // get related posts
        $relatedPosts = PostController::getRelatedPosts($id);
        if ($relatedPosts->getStatusCode() !== 200) {
            abort($relatedPosts->getStatusCode());
        }

        // get related tags
        $relatedTags = PostController::getRelatedTags($id);
        if ($relatedTags->getStatusCode() !== 200) {
            abort($relatedTags->getStatusCode());
        }

        // return post page
        return view('pages.post',
            ['post' => $post,
            'comments' => $comments,
            'relatedPosts' => json_decode($relatedPosts->getContent()),
            'relatedTags' => json_decode($relatedTags->getContent())]);
    }

    public function showEditor($id = null){
        if($id == null){
            $this->authorize('create', Content::class);
            return view('pages.post_editor', []);
        }

        $post = Post::find($id);
        $this->authorize('update', $post->content);
        return view('pages.post_editor', ['post' => $post]);
    }

    /**
     * Creates a new Post.
     *
     * @return Post The Post created.
     */
    public function create(Request $request) {
        $this->authorize('create', Content::class);

        Validator::extend('ntags', function($attribute, $value, $parameters) {
            $max = (int) $parameters[0];
            $tagList = explode(",", $value);
            return count($tagList) <= $max;
        });

        Validator::extend('nodups', function($attribute, $value, $parameters) {
            $tagList = explode(",", $value);
            return count($tagList) === count(array_flip($tagList));
        });

        Validator::extend('inthefuture', function ($attribute, $value, $parameters) {
            $date = Carbon::parse($value)->subHour();

            return ! $date->isPast();
        });

        $validator = Validator::make($request->all(),
        [
            'type' => [
                'required',
                'string',
                Rule::in(['News', 'Opinion']),
            ],
            'tags' => 'required|string|ntags:2|nodups',
            'title' => 'required|string|regex:/^[A-Za-z0-9?+*_!#$%,\/;.&\s-]{3,100}$/',
            'body' => 'required|string|min:16',
            'photo' => 'required|image|mimes:jpg,jpeg,bmp,png|max:10000',
            'date' => ['string', 'nullable', 'inthefuture', 'regex:/^(0[1-9]|1[0-2])\/(0[1-9]|[1-2][0-9]|3[0-1])\/[0-9]{4} ([1-9]|1[0-2]):[0-5][0-9] [A|P]M$/'],
            'hasNewFile' => Rule::in(['yes'])
        ],
            [
                'tags.required' => "Tags cannot not be empty",
                "tags.string" => "Invalid tags",
                "tags.ntags" => "You must have 1 or 2 tags",
                "tags.nodups" => "You have duplicate tags",
                'title.required' => "Title cannot not be empty",
                "title.string" => "Invalid title",
                "title.regex" => "Title must have between 3 and 100 letters, numbers or symbols like ?+*_!#$%,\/;.&-",
                'body.required' => "Body cannot not be empty",
                "body.string" => "Invalid body",
                "body.min" => "Body too short (must be at least 16 characters long).",
                'photo.required' => "The post needs to have an image",
                "photo.mimes" => "Photo has to be either a jpeg, jpg, bmp or png",
                "photo.image" => "Photo has to be either a jpeg, jpg, bmp or png",
                "photo.max" => "Photo is too big (max size is 10 MB)",
                "date.date" => "Invalid date format",
                "date.inthefuture" => "Date should be somewhere in the future",
                "date.regex" => "Invalid date format"
            ]
        );

        if ($validator->fails()) {
            return response()->json([
                'status' => 'failure',
                'status_code' => 400,
                'message' => 'Bad Request',
                'errors' => $validator->errors(),
            ], 400);
        }

        DB::beginTransaction();
        try{
            $content = new Content();
            $content->body = $request->body;
            $content->author_id = Auth::user()->id;
            $content->save();

            $post = new Post();
            $post->content_id = $content->id;
            $post->title = $request->title;

            $post->publication_date = $request->date !== null ? $request->date : \Carbon\Carbon::now();

            if($request->date !== null && \Carbon\Carbon::now()->diffInMinutes(new \Carbon\Carbon($request->date)) >= 0)
                $post->visible = false;
            else
                $post->visible = true;


            $imageName = ImageUpload::uploadPostImage($post->content_id, $request->file('photo'));
            $post->type = $request->type;
            $post->photo = $imageName;
            $post->save();

            DB::commit();

            $tagList = explode(",", $request->tags);
            foreach ($tagList as $tagName){
                $lowerTagName = strtolower($tagName);

                $tag = Tag::where('name', $lowerTagName)->first();
                if($tag == null){
                    $tag = new Tag();
                    $tag->name = $lowerTagName;
                    $tag->color = '#626d79';
                    $tag->save();
                }
                DB::table('post_tag')->insert(
                    ['post_id' => $post->content_id, 'tag_id' => $tag->id]
                );
            }


            $newPost = PostController::getPost($post->content_id);
            return response()->json(array('post' => json_decode($newPost->getContent())), 201);

        } catch(QueryException $err){
            DB::rollBack();
            return abort(404, "Failed to commit transaction");
        }
    }

    /**
     * Updates a post.
     *
     * @return ID The ID of the updated Post.
     */
    public function update(Request $request, $id) {
        $post = Post::find($id);

        if (is_null($post)){
            return response()->json([
                'status' => 'failure',
                'status_code' => 404,
                'message' => 'Not Found',
                'errors' => ['post' => 'There is no post with id ' . $id],
            ], 404);
        }
        $this->authorize('update', $post->content);

        Validator::extend('ntags', function($attribute, $value, $parameters) {
            $max = (int) $parameters[0];
            $tagList = explode(",", $value);
            return count($tagList) <= $max;
        });

        Validator::extend('inthefuture', function ($attribute, $value, $parameters) {
            $date = Carbon::parse($value)->subHour();

            return !$date->isPast();
        });

        $validator = Validator::make($request->all(),
            [
                'type' => [
                    'required',
                    'string',
                    Rule::in(['News', 'Opinion']),
                ],
                'tags' => 'required|string|ntags:2',
                'title' => 'required|string|regex:/^[A-Za-z0-9?+*_!#$%,\/;.&\s-]{3,100}$/',
                'body' => 'required|string|min:16',
                'photo' => 'image|mimes:jpg,jpeg,bmp,png|max:10000',
                'date' => ['string', 'nullable', 'inthefuture', 'regex:/^(0[1-9]|1[0-2])\/(0[1-9]|[1-2][0-9]|3[0-1])\/[0-9]{4} ([1-9]|1[0-2]):[0-5][0-9] [A|P]M$/'],
                'hasNewFile' => Rule::in(['yes', 'oldImage'])
            ],
            [
                'tags.required' => "Tags cannot not be empty",
                "tags.string" => "Invalid tags",
                "tags.ntags" => "You must have 1 or 2 tags",
                'title.required' => "Title cannot not be empty",
                "title.string" => "Invalid title",
                "title.regex" => "Title must have between 3 and 100 letters, numbers or symbols like ?+*_!#$%,\/;.&-",
                'body.required' => "Body cannot not be empty",
                "body.string" => "Invalid body",
                "body.min" => "Body too short (must be at least 10 characters long).",
                "photo.mimes" => "Photo has to be either a jpeg, jpg, bmp or png",
                "photo.image" => "Photo has to be either a jpeg, jpg, bmp or png",
                "photo.max" => "Photo is too big (max size is 10 MB)",
                "date.date" => "Invalid date format",
                "date.inthefuture" => "Date should be somewhere in the future",
                "date.regex" => "Invalid date format"
            ]
        );

        if ($validator->fails()) {
            return response()->json([
                'status' => 'failure',
                'status_code' => 400,
                'message' => 'Bad Request',
                'errors' => $validator->errors(),
            ], 400);
        }

        DB::beginTransaction();
        try{
            $post->content->body = $request->body;
            $post->content->author_id = Auth::user()->id;
            $post->content->save();

            $post->title = $request->title;

            $data = $request->date !== null ? new \Carbon\Carbon($request->date) : \Carbon\Carbon::now();
            if(\Carbon\Carbon::now()->diffInMinutes(new \Carbon\Carbon($post->publication_date), false) >= 0){
                if(\Carbon\Carbon::now()->diffInMinutes(new \Carbon\Carbon($data), false) >= 0){
                    $post->publication_date = $data;
                }
            }
            else{
                $post->modification_date = \Carbon\Carbon::now();
            }

            $post->type = $request->type;

            if ($request->hasNewFile == "yes") {
                $imageName = ImageUpload::uploadPostImage($post->content_id, $request->file('photo'));
                $post->photo = $imageName;
            }
            $post->save();

            DB::commit();

            $tagList = explode(",", $request->tags);

            // delete previous tags
            DB::table('post_tag')->where('post_id', $post->content_id)->delete();
            foreach ($tagList as $tagName){
                $lowerTagName = strtolower($tagName);
                $tag = Tag::where('name', $lowerTagName)->first();
                if($tag == null){
                    $tag = new Tag();
                    $tag->name = $lowerTagName;
                    $tag->color = '#626d79';
                    $tag->save();
                }
                DB::table('post_tag')->insert(
                    ['post_id' => $post->content_id, 'tag_id' => $tag->id]
                );
            }

            return response()->json(array('id' => $post->content_id), 200);

        } catch(QueryException $err){
            DB::rollBack();
            return abort(500, $err);
        }
    }

    static public function makePublicAfterTime(){
        $posts = Post::where('visible', false)->get();

        foreach($posts as $post){

            $time = new \Carbon\Carbon($post->publication_date,'Europe/London');
            $time->subHour();
            $diff = $time->diffInMinutes(\Carbon\Carbon::now('Europe/London'), false);

            // $timeDT = new DateTime($post->publication_date);
            // $nowDT = new DateTime($timezone = "Europe/London");

            if( (0 <= $diff) && ($diff <= 5)){

                $post->visible = true;
                $post->save();
            }

        }
    }

    public function getPost($id) {
        $post = Post::find($id);
        if (is_null($post)) {
            return response()->json([
                'status' => 'failure',
                'status_code' => 404,
                'message' => 'Not Found',
                'errors' => ['post' => 'There is no post with id ' . $id],
            ], 404);
        }

        // check if the user has authorization to view the post
        $this->authorize('view', $post);

        $post_author = null;
        // if post has no author (account deleted)
        if ($post->content->author !== null) {
            $author_local = null;
            if ($post->content->author->city !== null) {
                $author_local = [
                    'city' => $post->content->author->city->name,
                    'country' => $post->content->author->city->country->name,
                ];
            }
            $post_author = [
                'id'=> $post->content->author->id,
                'name'=> $post->content->author->name,
                'photo' => $post->content->author->photo,
                'local' => $author_local,
                'bio' => $post->content->author->bio,
                'verified' => $post->content->author->verified,
            ];
        }

        // get the ID of the most recent version of the post (null if this is already the post recent version)
        $recentVersion = $post->mostRecentVersion;
        $most_recent = $recentVersion->isEmpty() ? null : $recentVersion[0]->content_id;

        return response()->json([
            'id' => $post->content_id,
            'title' => $post->title,
            'likes_difference' => $post->content->likes_difference,
            'visible' => $post->visible,
            'most_recent' => $most_recent,
            'photo' => $post->photo,
            'body' => $post->content->body,
            'edited' => (!$post->content->most_recent || $post->modification_date != null) ? true : false,
            'publication_date' => $post->publication_date,
            'modification_date' => $post->modification_date,
            'author' => $post_author,
            'type' => $post->type,
            'tags' => $post->tags->map(function ($tag) {
                return ['name' => $tag->name, 'id' => $tag->id, 'color' => $tag->color];
            }),

        ], 200);

    }

    public function getComments($id, Request $request, $array=false)
    {
        $html = in_array('text/html', $request->getAcceptableContentTypes());

        $post = Post::find($id);
        if (is_null($post)){
            if ($html)
                return abort(404, 'There is no post with id ' . $id);
            else
                return response()->json([
                    'status' => 'failure',
                    'status_code' => 404,
                    'message' => 'Not Found',
                    'errors' => ['post' => 'There is no post with id ' . $id],
                ], 404);
        }

        $this->authorize('view', $post);

        $validator = Validator::make(
            $request->all(),
            [
                'order' => [
                    'string',
                    Rule::in(['old', 'numerical', 'recent'])
                ]
            ],
            [
                "order.string" => "Invalid sort criterion"
            ]
        );

        if ($validator->fails()) {
            if ($html) {
                return abort(400, 'Invalid sort criterion');
            } else {
                return response()->json([
                    'status' => 'failure',
                    'status_code' => 400,
                    'message' => 'Bad Request',
                    'errors' => $validator->errors(),
                ], 400);
            }
        }

        // check if the user has authorization to view the post
        $this->authorize('view', $post);

        $comments = $post->comments($request->order)->get()->map(function($comment){
            $comment_author = null;
            $userLikedComment = null;
            // if comment has no author (account deleted)
            if ($comment->content->author !== null) {
                $comment_author = [
                    'id'=> $comment->content->author->id,
                    'name'=> $comment->content->author->name,
                    'photo' => $comment->content->author->photo,
                    'verified' => $comment->content->author->verified,
                ];
            }
            if (Auth::check()) {
                $userLikedComment = Rating::likeValue($comment->content_id, Auth::user()->id);
            }
            return [
                'id' => $comment->content_id,
                'author' => $comment_author,
                'body' => $comment->content->body,
                'publication_date' => $comment->publication_date,
                'modification_date' => $comment->modification_date,
                'most_recent' => $comment->content->most_recent,
                'likes_difference' => $comment->content->likes_difference,
                'edited' => (!$comment->content->most_recent || $comment->modification_date != null) ? true : false,
                'userLikedContent' => $userLikedComment,
                'replies' => $comment->replies->map(function($reply) {
                    $reply_author = null;
                    $userLikedReply = null;
                    // if reply has no author (account deleted)
                    if ($reply->content->author !== null) {
                        $reply_author = [
                            'id'=> $reply->content->author->id,
                            'name'=> $reply->content->author->name,
                            'photo' => $reply->content->author->photo,
                            'verified' => $reply->content->author->verified,
                        ];
                    }

                    if (Auth::check()) {
                        $userLikedReply = Rating::likeValue($reply->content_id, Auth::user()->id);
                    }

                    return [
                        'id' => $reply->content_id,
                        'author' => $reply_author,
                        'body' => $reply->content->body,
                        'publication_date' => $reply->publication_date,
                        'modification_date' => $reply->modification_date,
                        'likes_difference' => $reply->content->likes_difference,
                        'edited' => (!$reply->content->most_recent || $reply->modification_date != null) ? true : false,
                        'userLikedContent' => $userLikedReply,
                    ];
                }),
            ];
        });

        if($array)
            return ['total'=> $post->numberCommentsAndReplies(), 'comments' => $comments];
        if($html)
            return view('partials.comment_preview_list', ['context' => 'post', 'comments' => $comments, 'emptyMessage' => 'There is no comment yet']);
        return response()->json(['total'=> $post->numberCommentsAndReplies(), 'comments' => $comments], 200);
    }

    /**
     * API endpoint that fetches the related posts of a certain post.
     * /api/posts/{post_id}/related_posts
     */
    public function getRelatedPosts($id) {
        $post = Post::find($id);
        if (is_null($post)){
            return response()->json([
                'status' => 'failure',
                'status_code' => 404,
                'message' => 'Not Found',
                'errors' => ['post' => 'There is no post with id ' . $id],
            ], 404);
        }


        // check if the user has authorization to view the post
        $this->authorize('view', $post);

        $relatedPostsQuery = Post::getRelatedPosts($post->title, $post->content_id);
        $relatedPosts = [];

        foreach($relatedPostsQuery as $post) {
            $post_author = null;
            // if post has no author (account deleted)
            if ($post->author_id !== null) {
                $post_author = [
                    'id'=> $post->author_id,
                    'name'=> $post->name
                ];
            }

            $tagsQuery = Tag::join('post_tag', 'post_tag.tag_id', '=', 'tag.id')
                ->where('post_tag.post_id', $post->id)->get();

            $tags = [];
            foreach ($tagsQuery as $tag) {
                array_push($tags, ['name' => $tag->name, 'id' => $tag->id, 'color' => $tag->color]);
            }

            array_push($relatedPosts, [
                'id' => $post->id,
                'title' => $post->title,
                'photo' => $post->photo,
                'likes_difference' => $post->likes_diff,
                'most_recent' => $post->most_recent,
                'publication_date' => $post->pub_date,
                'author' => $post_author,
                'tags' => $tags,
            ]);
        }

        return response()->json(['relatedPosts'=> $relatedPosts], 200);
    }

    public function getRelatedTags($id) {
        $post = Post::find($id);
        if (is_null($post)){
            return response()->json([
                'status' => 'failure',
                'status_code' => 404,
                'message' => 'Not Found',
                'errors' => ['post' => 'There is no post with id ' . $id],
            ], 404);
        }

        // check if the user has authorization to view the post
        $this->authorize('view', $post);


        $relatedTagsQuery = Post::getRelatedTags($post->title);

        $relatedTags = [];
        foreach($relatedTagsQuery as $tag) {
            array_push($relatedTags, [
                'id' => $tag->id,
                'name' => $tag->name,
                'color' => $tag->color,
            ]);
        }

        return response()->json(['relatedTags'=> $relatedTags], 200);
    }

    public function delete(Request $request, $id)
    {
        $post = Post::find($id);
        if (is_null($post)) {
            return response()->json([
                'status' => 'failure',
                'status_code' => 404,
                'message' => 'Not Found',
                'errors' => ['post' => 'There is no post with id ' . $id],
            ], 404);
        }

        $this->authorize('delete', $post->content);

        $is_Admin = Admin::find(Auth::user()->id);

        if($is_Admin && $post->content->author_id != Auth::user()->id){
            $is_Admin->posts_deleted++;
            $is_Admin->save();
        }

        $post->content->delete();

        ImageUpload::deletePostImage($post->photo);

        return response()->json([], 200);
    }


    /**
     * Method that presents the page with all the versions of the specified post.
     * /posts/{post_id}/versions
     */
    public function versions($id) {
        $post = Post::find($id);
        if (is_null($post)) {
            return abort(404, 'There is no post with id ' . $id);
        }

        $versions = $post->versions();

        $versionsPaginator = CollectionHelper::paginate($versions, $versions->count(), 6);
        $versionsPaginator->withPath('/api/posts/' . $id . '/versions?ppp=6');

        return view('pages.versions', [
            'type' => 'post',
            'versions' => [
                'data' => Post::postsInfo($versionsPaginator),
                'paginator' => $versionsPaginator
            ],
        ]);
    }

    /**
     * API method that fetches the versions of a certain post.
     * /api/posts/{post_id}/versions
     */
    public function getVersions(Request $request, $id) {
        $html = in_array('text/html', $request->getAcceptableContentTypes());

        $post = Post::find($id);
        if (is_null($post)) {
            if ($html) {
                abort(404, 'There is no post with id: ' . $id);
            } else {
                return response()->json([
                    'status' => 'failure',
                    'status_code' => 404,
                    'message' => 'Not Found',
                    'errors' => ['post' => 'There is no post with id ' . $id],
                ], 404);
            }
        }


        if (! (isset($_GET['ppp']) && isset($_GET['page']))) {
            $versions = $post->versions();
            $paginator = null;
        } else {
            if (!(is_numeric($_GET['page']) && ($_GET['page'] > 0) && is_numeric($_GET['ppp']) && ($_GET['ppp'] > 0))) {
                if ($html)
                    abort(400, "Invalid ppp and page arguments.");
                else
                    return response()->json([
                        'status' => 'failure',
                        'status_code' => 400,
                        'message' => 'Bad Request',
                        'errors' => ['message' => 'The page and ppp arguments need to be positive integers.'],
                    ], 400);
            }

            $postsPerPage = $_GET['ppp'];
            $versions = $post->versions();
            $paginator = CollectionHelper::paginate($versions, $versions->count(), $postsPerPage);
            $versions = $paginator;
            $paginator->withPath('/api/posts/' . $id . '/versions?ppp=' . $postsPerPage);
        }

        $versions = Post::postsInfo($versions);

        if ($html)
            return View('partials.post_preview_list', ['posts' => $versions, 'editable' => false, 'showAuthor' => true, 'showDate' => true, 'emptyMessage' => 'There are no versions of this post to show.', 'paginator' => $paginator ]);
        else
            return response()->json(['versions'=> $versions], 200);
    }

    /**
     * visibility
     */
    public function visibility(Request $request, $id){
        $post = Post::find($id);

        if (is_null($post)){
            return response()->json([
                'status' => 'failure',
                'status_code' => 404,
                'message' => 'There is no post with id '.$id,
            ], 404);
        }

        // check if the usergetVersions has authorization to alter the post's visibility
        $this->authorize('changeVisibility', $post);

        if(is_null($request->visibility)){
            return response()->json([
                'status' => 'failure',
                'status_code' => 400,
                'message' => 'No visibility given',
            ], 400);
        }

        if($request->visibility != 'true' && $request->visibility != 'false' ){
            return response()->json([
                'status' => 'failure',
                'status_code' => 400,
                'message' => 'Wrong type of visibility (must be true of false)',
            ], 400);
        }
        else{
            $pub_date = new Carbon($post->publication_date);

            if($pub_date->gt( Carbon::now())){
                $post->publication_date = Carbon::now();
            }

            $post->visible = $request->visibility == 'true' ? true : false;
            $post->save();
        }

        return response()->json([
            'status' => 'success',
            'status_code' => 200,
            'message' => 'Visibility changed.',
            'visibility' => ($post->visible == true ? "public" : "private")
        ], 200);
    }

    /**
     * API method that searches for a certain post
     * /api/search/posts/
     */
    public static function search(Request $request, $returnArray = false)
    {
        Validator::extend('typelist', function ($attribute, $value, $parameters) {
            $validValues = array_map((function ($value) {
                return strtolower($value);
            }), $parameters);

            $valueList = explode(",", $value);
            foreach ($valueList as $value) {
                 if (! in_array(strtolower($value), $validValues)) {
                    return false;
                }
            }

            return true;
        });

        $validator = Validator::make($request->all(), [
            'search' => [
                'string',
                'regex:/^[A-Za-z0-9?+*_!#$%,\/;.&\s-]{2,}$/'
            ],
            'type' => [
                'string',
                'typelist:News,Opinion'
            ],
            'author' => [
                'string',
                'typelist:Verified,Subscribed'
            ],
            'sortBy' => [
                'string',
                Rule::in(['alpha', 'numerical', 'recent'])
            ],
            'begin' => [
                'string',
                'regex:/^(0[1-9]|1[0-2])\/(0[1-9]|[1-2][0-9]|3[0-1])\/[0-9]{4}$/'
            ],
            'end' => [
                'string',
                'regex:/^(0[1-9]|1[0-2])\/(0[1-9]|[1-2][0-9]|3[0-1])\/[0-9]{4}$/'
            ],
            'min' => [
                'numeric'
            ],
            'max' => [
                'numeric'
            ]
        ],
        [
            "search.string" => "Invalid search data type",
            "search.regex" => "Search must have at least 2 letters, numbers or symbols like ?+*_!#$%,\/;.&-'",
            "type.string" => "Invalid type format",
            "type.typelist" => "Invalid type",
            "author.string" => "Invalid author format",
            "author.typelist" => "Invalid author",
            "sortBy.string" => "Invalid sort criterion",
            'begin.string' => "Invalid begin date",
            'begin.regex' => "Invalid begin date format",
            'end.string' => "Invalid end date",
            'end.regex' => "Invalid end date format",
            'min.numeric' => "Invalid minimum likes number",
            'max.numeric' => "Invalid maximum likes number",
        ]);

        $html = in_array('text/html', $request->getAcceptableContentTypes());
        if ($validator->fails()) {
            if ($html) {
                abort(400, $validator->errors());
            } else {
                return response()->json([
                    'status' => 'failure',
                    'status_code' => 400,
                    'message' => 'Bad Request',
                    'errors' => $validator->errors(),
                ], 400);
            }
        }

        // Categories
        $newsPosts = ($html || $returnArray)  ? ['data' => [], 'paginator' => null] : (object) ['data' => [], 'paginator' => null];
        $opinionPosts = ($html || $returnArray) ? ['data' => [], 'paginator' => null] : (object) ['data' => [], 'paginator' => null];

        if ($request->type != null) {
            $typeList = explode(",", $request->type);
            foreach ($typeList as $type) {
                if (strcmp($type, "News") == 0) {
                    $newsPosts = Post::searchByType($request, 'News', null, $returnArray || $html);
                    if (!($returnArray || $html)) {
                        if ($newsPosts->getStatusCode() !== 200) {
                            return $newsPosts;
                        }
                        $newsPosts = json_decode($newsPosts->getContent(), $html);
                    }
                } else if (strcmp($type, "Opinion") == 0) {
                    $opinionPosts = Post::searchByType($request, 'Opinion', null, $returnArray || $html);
                    if (!($returnArray || $html)) {
                        if ($opinionPosts->getStatusCode() !== 200) {
                            return $opinionPosts;
                        }
                        $opinionPosts = json_decode($opinionPosts->getContent(), $html);
                    }
                }
            }
        } else {
            // get posts information
            $newsPosts = Post::searchByType($request, 'News', null, $returnArray || $html);
            if (!($returnArray || $html)) {
                if ($newsPosts->getStatusCode() !== 200) {
                    return $newsPosts;
                }
                $newsPosts = json_decode($newsPosts->getContent(), $html);
            }
            $opinionPosts = Post::searchByType($request, 'Opinion', null, $returnArray || $html);
            if (!($returnArray || $html)) {
                if ($opinionPosts->getStatusCode() !== 200) {
                    return $opinionPosts;
                }
                $opinionPosts = json_decode($opinionPosts->getContent(), $html);
            }
        }

        if ($html)
            return view('partials.post_preview_search', ['news' => $newsPosts, 'opinion' => $opinionPosts]);
        else if ($returnArray)
            return ['news' => $newsPosts, 'opinion' => $opinionPosts];
        else
            return response()->json(['news' => $newsPosts, 'opinion' => $opinionPosts], 200);
    }
}