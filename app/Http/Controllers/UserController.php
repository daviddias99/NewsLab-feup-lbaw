<?php

namespace App\Http\Controllers;

use App\Ban;
use App\Tag;
use App\User;
use App\Post;
use App\City;
use App\Admin;
use App\Badge;
use App\Rating;
use App\Country;
use App\Comment;
use App\Report;
use App\UserReport;
use App\Utils\ImageUpload;
use App\Utils\CollectionHelper;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

use Carbon\Carbon;

class UserController extends Controller {
    /**
     * View
     * Show the User Profile.
     * /users/{id}
     *
     * @param int $id
     * @return View
     */
    public function show($id) {
        $user = User::find($id);
        if (is_null($user)) {
            return abort(404, 'There is no user with id ' . $id);
        }

        $subscribed = Auth::check() && $user->subscribed(Auth::user());

        $userInfo = $user->getUserInfo();
        $userBadges = $user->getBadgesInfo();
        $badgesInfo = Badge::orderBy('name')->get()->keyBy('id');

        $posts = $user->posts('recent');
        if (!Auth::check() || Auth::user()->id != $user['id'])
            $posts = $posts->visible();

        $postsPaginator = $posts->paginate(3);
        $postsPaginator->withPath('/api/users/' . $id . '/posts?ppp=3');

        $likesPaginator = $user->likedPosts()->paginate(3);
        $likesPaginator->withPath('/api/users/' . $id . '/likes?ppp=3');

        $commentsPaginator = $user->comments()->paginate(3);
        $commentsPaginator->withPath('/api/users/' . $id . '/comments?ppp=3');

        $commentsData = Comment::commentsPreviewInfo($commentsPaginator);

        for($i = 0; $i < count($commentsData); $i++) {
            $comment = $commentsData[$i];
            $userLikedComment = null;
            if (Auth::check() && $comment['most_recent']) {
                $userLikedComment = Rating::likeValue($comment['id'], Auth::user()->id);
            }
            $comment['userLikedComment'] = $userLikedComment;
            $commentsData[$i] = $comment;
        }

        // return user profile page
        return view('pages.profile', [
            'user' => $userInfo,
            'subscribed' => $subscribed,
            'userBadges' => $userBadges,
            'badgesInfo' => $badgesInfo,
            'posts' => [
                'data' => Post::postsInfo($postsPaginator),
                'paginator' => $postsPaginator
            ],
            'likes' => [
                'data' => Post::postsInfo($likesPaginator),
                'paginator' => $likesPaginator
            ],
            'comments' => [
                'data' => $commentsData,
                'paginator' => $commentsPaginator
            ]
        ]);
    }

    /**
     * View
     * Show Profile Editor
     * /users/{id}/edit
     *
     * @param int $id
     * @return View
     */
    public function showEditor($id){
        $user = User::find($id);
        if (is_null($user)) {
            return abort(404, 'There is no user with id ' . $id);
        }

        $this->authorize('update', $user);

        $countries = Country::all();
        $cities = City::all();
        return view('pages.profile_editor', ['user' => $user->getUserInfo(), 'countries' => $countries, 'cities' => $cities]);
    }

    /**
     * View
     * Method that returns a view with the user's saved posts.
     * /users/{user_id}/saved_posts
     *
     * @param int $id
     */
    public function savedPosts($id) {
        $user = User::find($id);
        if (is_null($user)) {
            return abort(404, 'There is no user with id ' . $id);
        }

        // check if the user has authorization to view the saved posts
        $this->authorize('viewSavedPosts', $user);

        $savedPostsPaginator = $user->savedPosts()->paginate(6);
        $savedPostsPaginator->withPath('/api/users/' . $id . '/saved_posts?ppp=6');

        return view('pages.saved_posts', [
            'user_id' => $user->id,
            'versions' => [
                'data' => Post::postsInfo($savedPostsPaginator),
                'paginator' => $savedPostsPaginator
            ],
        ]);
    }

    /**
     * View
     * Show user statistics page
     * /users/{id}/stats
     *
     * @param int $id
     */
    function showStats($id) {
        $user = User::find($id);
        if (is_null($user)) {
            return abort(404, 'There is no user with id ' . $id);
        }

        $this->authorize('checkStats', $user);

        return view('pages.user_stats', [
            'user' => $user->getUserInfo(),
            'most_liked_post' => Post::postInfo($user->mostLikedPost()),
            'num_posts' => $user->numPosts(),
            'num_comments' => $user->numComments(),
            'posts_likes' => $user->postsTotalLikesDiff(),
            'comments_likes' => $user->commentsTotalLikesDiff()
        ]);
    }

    /**
     * View
     * Method that returns the page with a user's subcriptions.
     * /users/{user_id}/manage_subs
     *
     * @param int $id
     */
    public function manageSubs($id) {
        $tagSubscriptions = $this->getManageSubsTags($id);
        if ($tagSubscriptions->getStatusCode() !== 200) {
            abort($tagSubscriptions->getStatusCode());
        }

        $user = User::find($id);

        $usersPaginator = $user->subbedUsers()->paginate(8);
        $usersPaginator->withPath('/api/users/' . $id . '/manage_subs/users?ppp=8');

        $tagSubscriptions = json_decode($tagSubscriptions->getContent());

        return view('pages.manage_subs',
            [
            'tags' => $tagSubscriptions->tags,
            'users' => [
                'data' => User::getUsersShortInfo($usersPaginator),
                'paginator' => $usersPaginator
                ]
            ]);
    }

    /**
     * API Update User Profile
     * /api/users/{id}
     *
     * @param int $id
     * @param Request $request
     */
    public function update($id, Request $request){
        $user = User::find($id);
        if (is_null($user)){
            return response()->json([
                'status' => 'failure',
                'status_code' => 404,
                'message' => 'Not Found',
                'errors' => ['user' => 'There is no user with id ' . $id],
            ], 404);
        }

        $this->authorize('update', $user);

        $validator = Validator::make($request->all(),
        [
            'name' => 'required|string|regex:/^[a-zA-Z ]{3,25}$/',
            'email' => [Rule::unique('user')->ignore($user->id), 'required', 'string', 'email', 'max:255', 'regex:/^[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,4}$/'],
            'password' => 'required|string|regex:/^(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{8,}$/',
            'birthday' => ['required', 'date', 'before:13 years ago', 'regex:/^(0[1-9]|1[0-2])\/(0[1-9]|[1-2][0-9]|3[0-1])\/[0-9]{4}$/'],
            'bio' => 'string|nullable|regex:/^[A-Za-z0-9?+*_!#$%,\/;.&\s-]{6,500}$/',
            'photo' => 'image|mimes:jpg,jpeg,bmp,png|max:10000',
            'hasNewFile' => Rule::in(['yes', 'oldImage', 'no'])
        ],
        [
            "name.regex" => 'Name should contain between 3 and 25 letters and spaces',
            "email.regex" => 'Invalid email',
            "password.regex" => 'password must contain at least one number and one uppercase and lowercase letter, and at least 8 or more characters',
            "birthday.regex" => 'Invalid date format - mm/dd/yyy',
            "bio.regex" => 'Invalid bio',
            "photo.mimes" => "Photo has to be either a jpeg, jpg, bmp or png",
            "photo.image" => "Photo has to be either a jpeg, jpg, bmp or png",
            "photo.max" => "Photo is too big (max size is 10 MB)"
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

        if(isset($request->country)){
            $country = Country::find($request->country);
            if (is_null($country)){
                return response()->json([
                    'status' => 'failure',
                    'status_code' => 400,
                    'message' => 'Bad Request',
                    'errors' => ['country' => 'Invalid country'],
                ], 400);
            }

            $city = City::find($request->city);
            if (is_null($city)){
                return response()->json([
                    'status' => 'failure',
                    'status_code' => 400,
                    'message' => 'Bad Request',
                    'errors' => ['city' => 'Invalid city'],
                ], 400);
            }
            if($city->country->id != $country->id){
                return response()->json([
                    'status' => 'failure',
                    'status_code' => 400,
                    'message' => 'Bad Request',
                    'errors' => ['city' => 'Invalid city - country mismatch'],
                ], 400);
            }
        }

        if (!(Hash::check($request->password, Auth::user()->password))) {
            return response()->json([
                'status' => 'failure',
                'status_code' => 400,
                'message' => 'Bad Request',
                'errors' => ['pass' => 'Wrong password'],
            ], 400);
        }

        if (strcmp($request->hasNewFile, 'yes') == 0){
            $this->validate($request, [
                'newPass' => 'string|min:6',
                'confirmPass' => 'same:newPass'
            ]);
        }

        if($request->file('photo') != null){
            $imageName = ImageUpload::uploadUserImage($user->id, $request->file('photo'));
            $user->photo = $imageName;
        }
        else if(strcmp($request->hasNewFile, 'no')==0){
            $user->photo = null;
        }

        $user->name = $request->name;
        $user->email = $request->email;
        $user->birthday = $request->birthday;
        if(isset($request->bio)){
            $user->bio = $request->bio;
        }

        if(isset($request->country)){
            $user->location_id = $request->city;
        }
        else{
            $user->location_id = null;
        }

        if ($request->newPass != ""){
            $user->password = bcrypt($request->newPass);
        }
        $user->save();
        return response()->json(['id' => $user->id], 200);
    }

    /**
     * API Get User Information
     * /api/users/{id}
     *
     * @param int $id
     */
    public function getUser($id) {
        $user = User::find($id);
        if (is_null($user)){
            return response()->json([
                'status' => 'failure',
                'status_code' => 404,
                'message' => 'Not Found',
                'errors' => ['user' => 'There is no user with id ' . $id],
            ], 404);
        }

        return response()->json($user->getUserInfo(), 200);
    }

    /**
     * API Get tags used by the user
     * /api/users/{id}/tags
     *
     * @param int $id
     */
    public function getPostTags($id) {
        $user = User::find($id);
        if (is_null($user)){
            return response()->json([
                'status' => 'failure',
                'status_code' => 404,
                'message' => 'Not Found',
                'errors' => ['user' => 'There is no user with id ' . $id],
            ], 404);
        }

        $tags = $user->getTags($id);
        return response()->json(Tag::getTagsInfo($tags), 200);
    }

    /**
     * API Get user badges
     * /api/users/{id}/badges
     *
     * @param int $id
     */
    public function getBadges($id) {
        $user = User::find($id);
        if (is_null($user)){
            return response()->json([
                'status' => 'failure',
                'status_code' => 404,
                'message' => 'Not Found',
                'errors' => ['user' => 'There is no user with id ' . $id],
            ], 404);
        }

        return response()->json($user->getBadgesInfo(), 200);
    }

    /**
     * API method that fetches the saved posts of a user.
     * /api/users/{user_id}/saved_posts
     *
     * @param int $id
     * @param Request $request
     */
    public function getSavedPosts(Request $request, $id) {
        $html = in_array('text/html', $request->getAcceptableContentTypes());

        $user = User::find($id);
        if (is_null($user)){
            if ($html)
                return abort(404, 'There is no user with id ' . $id);
            else
                return response()->json([
                    'status' => 'failure',
                    'status_code' => 404,
                    'message' => 'Not Found',
                    'errors' => ['user' => 'There is no user with id ' . $id],
                ], 404);
        }

        // check if the user has authorization to view the saved posts
        $this->authorize('viewSavedPosts', $user);

        $validator = Validator::make(
            $request->all(),
            [
                'order' => [
                    'string',
                    Rule::in(['alpha', 'numerical', 'recent'])
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

        $order = is_null($request->order) ? 'alpha' : $request->order;

        if (! (isset($_GET['ppp']) && isset($_GET['page']))) {
            $savedPosts = $user->savedPosts($order)->get()->map(function($r) { return Post::postInfo($r); });
            $paginator = null;
        } else {
            if (! (is_numeric($_GET['page']) && ($_GET['page'] > 0) && is_numeric($_GET['ppp']) && ($_GET['ppp'] > 0))) {
                if ($html)
                    return abort(400, 'The page and ppp arguments need to be positive integers.');
                else
                    return response()->json([
                        'status' => 'failure',
                        'status_code' => 400,
                        'message' => 'Bad Request',
                        'errors' => ['message' => 'The page and ppp arguments need to be positive integers.'],
                    ], 400);
            }

            $postsPerPage = $_GET['ppp'];
            $queryCollection = $user->savedPosts($order)->get();
            $paginator = CollectionHelper::paginate($queryCollection, $queryCollection->count(), $postsPerPage);
            $paginator->withPath('/api/users/' . $id . '/saved_posts?order=' . $order . '&ppp=' . $postsPerPage);
            $savedPosts = Post::extendedPostsInfo($paginator);
        }

        if ($html)
            return View('partials.post_preview_list', ['posts' => $savedPosts, 'editable' => false, 'showAuthor' => true, 'showDate' => false, 'showCross' => true, 'emptyMessage' => 'No saved posts to show.', 'paginator' => $paginator ]);
        else
            return response()->json(['savedPosts'=> $savedPosts, 'paginator' => $paginator], 200);
    }

    /**
     * API method to add a post to the saved posts of a user.
     * /api/users/{user_id}/saved_posts
     *
     * @param int $id
     * @param Request $request
     */
    public function addSavedPost($id, Request $request) {
        $validator = Validator::make(
            $request->all(),
            [
                'id' => 'required|integer|min:0'
            ],
            [
                'id.required' => "A post ID has to be specified.",
                "id.integer" => "Invalid post ID.",
                "id.min" => "Invalid post."
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

        $user = User::find($id);
        if (is_null($user)){
            return response()->json([
                'status' => 'failure',
                'status_code' => 404,
                'message' => 'Not Found',
                'errors' => ['user' => 'There is no user with id ' . $id],
            ], 404);
        }

        $this->authorize('addSavedPost', $user);

        if (is_null(Post::find($request->id))) {
            return response()->json([
                'status' => 'failure',
                'status_code' => 400,
                'message' => 'Bad Request',
                'errors' => ['post' => 'There is no post with id ' . $request->id],
            ], 400);
        }

        $savedPost = $user->savedPosts()->where('content_id', $request->id)->first();
        if ($savedPost != null) {
            return response()->json([
                'status' => 'failure',
                'status_code' => 400,
                'message' => 'Bad Request',
                'errors' => ['save' => ['Already saved']],
            ], 400);
        }

        DB::table('saved_post')->insert(['user_id' => $id, 'post_id' => $request->id]);

        return response()->json(['message' => 'Operation performed successfully.'], 200);
    }

    /**
     * API method that deletes the saved post of a user.
     * /api/users/{user_id}/saved_posts
     *
     * @param int $id
     * @param Request $request
     */
    public function deleteSavedPost($id, Request $request) {
        $user = User::find($id);
        if (is_null($user)){
            return response()->json([
                'status' => 'failure',
                'status_code' => 404,
                'message' => 'Not Found',
                'errors' => ['user' => 'There is no user with id ' . $id],
            ], 404);
        }

        $validator = Validator::make(
            $request->all(),
            [
                'id' => 'required|integer|min:0'
            ],
            [
                'id.required' => "A post ID has to be specified.",
                "id.integer" => "Invalid post ID.",
                "id.min" => "Invalid post."
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

        $this->authorize('deleteSavedPost', $user);

        $savedPost = $user->savedPosts()->where('content_id', $request->id)->first();
        if ($savedPost == null) {
            return response()->json([
                'status' => 'failure',
                'status_code' => 400,
                'message' => 'Bad Request',
                'errors' => ['user' => ['User ' . $id . 'has no saved post with ID ' . $request->id]],
            ], 400);
        }

        DB::table('saved_post')->where('user_id', $id)->where('post_id', $request->id)->delete();

        return response()->json(['message' => 'Operation performed successfully.'], 200);
    }

    /**
     * API method that fetch user posts
     * /api/users/{id}/posts
     *
     * @param int $id
     * @param Request $request
     *
     * Reponse can be either JSON or HTML depending on the Accept type
     */
    function posts(Request $request, $id) {
        $html = in_array('text/html', $request->getAcceptableContentTypes());

        $user = User::find($id);
        if (is_null($user)) {
            if ($html)
                return abort(404, 'There is no user with id ' . $id);
            else
                return response()->json([
                    'status' => 'failure',
                    'status_code' => 404,
                    'message' => 'Not Found',
                    'errors' => ['user' => 'There is no user with id ' . $id],
                ], 404);
        }


        $posts = $user->posts('recent');
        if (!Auth::check() || Auth::user()->id != $user['id'])
            $posts = $posts->visible();

        if (! (isset($_GET['ppp']) && isset($_GET['page']))) {
            $paginator = null;
        } else {
            if (! (is_numeric($_GET['page']) && ($_GET['page'] > 0) && is_numeric($_GET['ppp']) && ($_GET['ppp'] > 0))) {
                if ($html)
                    return abort(400, 'The page and ppp arguments need to be positive integers.');
                else
                    return response()->json([
                        'status' => 'failure',
                        'status_code' => 400,
                        'message' => 'Bad Request',
                        'errors' => ['message' => 'The page and ppp arguments need to be positive integers.'],
                    ], 400);
            }

            $postsPerPage = $_GET['ppp'];
            $paginator = $posts->paginate($postsPerPage);
            $posts = $paginator;
            $paginator->withPath('/api/users/' . $id . '/posts?ppp=' . $postsPerPage);
        }

        $posts = Post::postsInfo($posts);

        if ($html)
            return View('partials.post_preview_list', ['posts' => $posts, 'editable' => true, 'showAuthor' => false, 'showDate' => false, 'emptyMessage' => 'This user has no posts.', 'paginator' => $paginator ]);
        else
            return response()->json(['posts' => $posts, 'paginator' => $paginator], 200);
    }

    /**
     * API method that fetch user likes
     * /api/users/{id}/likes
     *
     * @param int $id
     * @param Request $request
     *
     * Reponse can be either JSON or HTML depending on the Accept type
     */
    function likes(Request $request, $id) {
        $html = in_array('text/html', $request->getAcceptableContentTypes());

        $user = User::find($id);
        if (is_null($user)) {
            if ($html)
                return abort(404, 'There is no user with id ' . $id);
            else
                return response()->json([
                    'status' => 'failure',
                    'status_code' => 404,
                    'message' => 'Not Found',
                    'errors' => ['user' => 'There is no user with id ' . $id],
                ], 404);
        }

        if (!(isset($_GET['ppp']) && isset($_GET['page']))) {
            $likes = $user->likedPosts;
            $paginator = null;
        } else {
            if (! (is_numeric($_GET['page']) && ($_GET['page'] > 0) && is_numeric($_GET['ppp']) && ($_GET['ppp'] > 0))) {
                if ($html)
                    return abort(400, 'The page and ppp arguments need to be positive integers.');
                else
                    return response()->json([
                        'status' => 'failure',
                        'status_code' => 400,
                        'message' => 'Bad Request',
                        'errors' => ['message' => 'The page and ppp arguments need to be positive integers.'],
                    ], 400);
            }

            $postsPerPage = $_GET['ppp'];
            $paginator = $user->likedPosts()->paginate($postsPerPage);
            $paginator->withPath('/api/users/' . $id . '/likes?ppp=' . $postsPerPage);
        }

        if ($paginator != null)
            $likes = Post::postsInfo($paginator);

        if ($html)
            return View('partials.post_preview_list', ['posts' => $likes, 'editable' => false, 'showAuthor' => true, 'showDate' => false, 'emptyMessage' => 'This user hasn\'t liked any post yet.', 'paginator' => $paginator]);
        else
            return response()->json(['likes' => $likes, 'paginator' => $paginator], 200);
    }

    /**
     * API method that fetch user comments
     * /api/users/{id}/comments
     *
     * @param int $id
     * @param Request $request
     *
     * Reponse can be either JSON or HTML depending on the Accept type
     */
    function comments(Request $request, $id) {
        $html = in_array('text/html', $request->getAcceptableContentTypes());

        $user = User::find($id);
        if (is_null($user)) {
            if ($html)
                return abort(404, 'There is no user with id ' . $id);
            else
                return response()->json([
                    'status' => 'failure',
                    'status_code' => 404,
                    'message' => 'Not Found',
                    'errors' => ['user' => 'There is no user with id ' . $id],
                ], 404);
        }

        if (!(isset($_GET['ppp']) && isset($_GET['page']))) {
            $comments = $user->comments;
            $paginator = null;
        } else {
            if (! (is_numeric($_GET['page']) && ($_GET['page'] > 0) && is_numeric($_GET['ppp']) && ($_GET['ppp'] > 0))) {
                if ($html)
                    return abort(400, 'The page and ppp arguments need to be positive integers.');
                else
                    return response()->json([
                        'status' => 'failure',
                        'status_code' => 400,
                        'message' => 'Bad Request',
                        'errors' => ['message' => 'The page and ppp arguments need to be positive integers.'],
                    ], 400);
            }

            $commentsPerPage = $_GET['ppp'];
            $paginator = $user->comments()->paginate($commentsPerPage);
            $comments = $paginator;
            $paginator->withPath('/api/users/' . $id . '/comments?ppp=' . $commentsPerPage);
        }

        $comments = Comment::commentsPreviewInfo($comments);

        if ($html) {
            for($i = 0; $i < count($comments); $i++) {
                $comment = $comments[$i];
                $userLikedComment = null;
                if (Auth::check() && $comment['most_recent']) {
                    $userLikedComment = Rating::likeValue($comment['id'], Auth::user()->id);
                }
                $comment['userLikedComment'] = $userLikedComment;
                $comments[$i] = $comment;
            }

            return View('partials.comment_preview_list', [ 'comments' => $comments, 'context' => "profile", 'emptyMessage' => "This user has no comments.", 'paginator' => $paginator ]);
        }
        else {
            return response()->json(['comments' => $comments, 'paginator' => $paginator], 200);
        }
    }

    /**
     * API Fetch user statistics
     * /api/users/{id}/stats
     *
     * @param int $id
     */
    function getStats($id) {
        $user = User::find($id);
        if (is_null($user)) {
            return response()->json([
                'status' => 'failure',
                'status_code' => 404,
                'message' => 'Not Found',
                'errors' => ['user' => 'There is no user with id ' . $id],
            ], 404);
        }

        $this->authorize('checkStats', $user);

        return response()->json([
            'user_id' => $id,
            'tags_on_posts' => Tag::getTagsInfo($user->getTags($id)),
            'subs_location' => $user->getSubsCountry(),
            'subs_age' => $user->getSubsAge(),
            'most_liked_post' => Post::postInfo($user->mostLikedPost())
        ], 200);
    }

    /**
     * API Fetch banned users
     * /api/users/banned
     *
     * @param Request $request
     *
     * Reponse can be either JSON or HTML depending on the Accept type
     */
    public function getBanned(Request $request) {
        $this->authorize('listBanned', User::class);
        $result = [];
        $p = $request['page'];

        $html = in_array('text/html', $request->getAcceptableContentTypes());

        if (!(isset($_GET['ppp']) && isset($_GET['page']))) {
            $users = User::all()->where('banned',true);
            $result = User::getBannedUsersInfo($users);
            $paginator = null;
        } else {
            if (! (is_numeric($_GET['page']) && ($_GET['page'] > 0) && is_numeric($_GET['ppp']) && ($_GET['ppp'] > 0))) {
                if ($html)
                    return abort(400, 'The page and ppp arguments need to be positive integers.');
                else
                    return response()->json([
                        'status' => 'failure',
                        'status_code' => 400,
                        'message' => 'Bad Request',
                        'errors' => ['message' => 'The page and ppp arguments need to be positive integers.'],
                    ], 400);
            }

            do{
                $postsPerPage = $_GET['ppp'];
                $paginator = User::where('banned',true)->paginate($postsPerPage,['*'],'page', $p);
                $paginator->withPath('/api/users/banned?ppp=' . $postsPerPage);
                $result = User::getBannedUsersInfo($paginator);

                if( ($p <= 1) || ($paginator->count() > 0))
                    break;
                else
                    $p--;
            } while(true);
        }

        $result = $result->values();

        if ($html)
            return View('partials.admin_banned_users_table', ['data' => $result, 'emptyMessage' => 'There are no banned users.', 'paginator' => $paginator ]);
        else
            return response()->json(['banned' => $result, 'paginator' => $paginator], 200);
    }

    /**
     * API Ban user
     * /api/users/{id}/ban
     *
     * @param int $id
     * @param Request $request
     */
    public function banUser(Request $request, $id) {
        $user = User::find($id);
        if (is_null($user)) {
            return response()->json([
                'status' => 'failure',
                'status_code' => 404,
                'message' => 'Not Found',
                'errors' => ['user' => 'There is no user with id ' . $id],
            ], 404);
        }

        $this->authorize('ban', Auth::user());

        $validator = Validator::make(
            $request->all(),
            [
                'endDate' => ['date', 'after:now', 'regex:/^(0[1-9]|1[0-2])\/(0[1-9]|[1-2][0-9]|3[0-1])\/[0-9]{4}$/'],
            ],
            [
                'endDate.date' => 'endDate needs to be a date.',
                'endDate.after' => "endDate needs to be after today's date.",
                'endDate.regex' => 'Invalid date format - mm/dd/yyy'
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

        if(Ban::where('user_id',$id)->first())
            return response()->json([
                'status' => 'failure',
                'status_code' => 409,
                'message' => 'Conflict',
                'errors' => ['user' => 'User already banned'],
            ], 409);

        $newBan = new Ban();
        $newBan->user_id = $user->id;
        $newBan->admin_id = Auth::user()->id;
        $newBan->user_id = $user->id;

        if($request->input("endDate")){
            $newBan->ban_end = $request->input("endDate");
        }

        if(!$newBan->save()){
            return response()->json([
                'status' => 'failure',
                'status_code' => 500,
                'message' => 'Internal Server Error',
                'errors' => ['message' => 'Error processing the request.'],
            ], 500);
        }

        $is_admin = Admin::find($id);
        if($is_admin){
            $is_admin->delete();
        }

        return response()->json(['message' => 'Operation performed successfully.'], 200);
    }

    /**
     * API Unban user
     * /api/users/{id}/unban
     *
     * @param int $id
     * @param Request $request
     */
    public function unbanUser($id){
        $user = User::find($id);
        if (is_null($user)) {
            return response()->json([
                'status' => 'failure',
                'status_code' => 404,
                'message' => 'Not Found',
                'errors' => ['user' => 'There is no user with id ' . $id],
            ], 404);
        }

        $this->authorize('ban', Auth::user());

        $ban = Ban::where('user_id',$id);

        if(is_null($ban->first())){
            return response()->json([
                'status' => 'failure',
                'status_code' => 400,
                'message' => 'Bad Request',
                'errors' => ['user' => 'There is no banned user with id' . $id],
            ], 400);
        }

        $ban->first()->delete();
        $user->banned = false;
        $user->save();

        return response()->json(['message' => 'Operation performed successfully.'], 200);
    }

    /**
     * API method that returns a JSON with information about a user's subcriptions (tags).
     * /api/users/{user_id}/manage_subs/tags
     *
     * @param int $id
     */
    public function getManageSubsTags($id) {
        $user = User::find($id);
        if (is_null($user)) {
            return response()->json([
                'status' => 'failure',
                'status_code' => 404,
                'message' => 'Not Found',
                'errors' => ['user' => 'There is no user with id ' . $id],
            ], 404);
        }

        // check if the user has authorization to view the subcriptions
        $this->authorize('viewSubs', $user);

        return response()->json(['user_id' => $id, 'tags' => Tag::getTagsInfo($user->subbedTags)]);
    }


    /**
     * API method that returns information about a user's subcriptions (users).
     * /api/users/{user_id}/manage_subs/users
     *
     * @param int $id
     * @param Request $request
     *
     * Reponse can be either JSON or HTML depending on the Accept type
     */
    public function getManageSubsUsers(Request $request, $id) {
        $html = in_array('text/html', $request->getAcceptableContentTypes());

        $user = User::find($id);
        if (is_null($user)) {
            if ($html)
                return abort(404, 'There is no user with id ' . $id);
            else
                return response()->json([
                    'status' => 'failure',
                    'status_code' => 404,
                    'message' => 'Not Found',
                    'errors' => ['user' => 'There is no user with id ' . $id],
                ], 404);
        }

        // check if the user has authorization to view the subcriptions
        $this->authorize('viewSubs', $user);

        if (!(isset($_GET['ppp']) && isset($_GET['page']))) {
            $subbedUsers = $user->subbedUsers;
            $paginator = null;
        } else {
            if (! (is_numeric($_GET['page']) && ($_GET['page'] > 0) && is_numeric($_GET['ppp']) && ($_GET['ppp'] > 0))) {
                if ($html)
                    return abort(400, 'The page and ppp arguments need to be positive integers.');
                else
                    return response()->json([
                        'status' => 'failure',
                        'status_code' => 400,
                        'message' => 'Bad Request',
                        'errors' => ['message' => 'The page and ppp arguments need to be positive integers.'],
                    ], 400);
            }

            $usersPerPage = $_GET['ppp'];
            $paginator = $user->subbedUsers()->paginate($usersPerPage);
            $subbedUsers = $paginator;
            $paginator->withPath('/api/users/' . $id . '/manage_subs/users?ppp=' . $usersPerPage);
        }

        $subbedUsers = User::getUsersShortInfo($subbedUsers);

        if ($html)
            return View('partials.user_preview_list', ['users' => $subbedUsers, 'sub' => true, 'emptyMessage' => 'No subscribed users to show', 'paginator' => $paginator]);
        else
            return response()->json(['user_id' => $id, 'users' => $subbedUsers, 'paginator' => $paginator]);
    }

    /**
     * API method that deletes a user subscription of a certain tag.
     * /api/users/{user_id}/manage_subs/tags
     *
     * @param int $id
     * @param Request $request
     */
    public function deleteTagSub($id, Request $request) {
        return $this->deleteSubGeneral($id, $request, "Tag");
    }


    /**
     * API method that deletes a user subscription of a certain user.
     * /api/users/{user_id}/manage_subs/users
     *
     * @param int $id
     * @param Request $request
     */
    public function deleteUserSub($id, Request $request) {
        return $this->deleteSubGeneral($id, $request, "User");
    }

    /**
     * API method that makes a user subscription and adds it to a user's subscriptions.
     * /api/users/{user_id}/manage_subs/users
     *
     * @param int $id
     * @param Request $request
     */
    public function addUserSub($id, Request $request) {
        return $this->addSubGeneral($id, $request, "User");
    }

    /**
     * API method that makes a tag subscription and adds it to a user's subscriptions.
     * /api/users/{user_id}/manage_subs/tags
     *
     * @param int $id
     * @param Request $request
     */
    public function addTagSub($id, Request $request) {
        return $this->addSubGeneral($id, $request, "Tag");
    }

    /**
     * API method do search users
     * /api/search/users
     *
     * @param Request $request
     * @param boolean $returnArray
     *
     * Reponse can be either JSON or HTML depending on the Accept type
     * Used internally can also return an array
     */
    public static function search(Request $request, $returnArray=false){
        $html = in_array('text/html', $request->getAcceptableContentTypes());

        Validator::extend('typelist', function ($attribute, $value, $parameters) {
            $validValues = array_map((function ($value) {
                return strtolower($value);
            }), $parameters);

            $valueList = explode(",", $value);
            foreach ($valueList as $value) {
                if (!in_array(strtolower($value), $validValues)) {
                    return false;
                }
            }

            return true;
        });

        error_log($request->begin);

        $validator = Validator::make($request->all(), [
            'search' => [
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
                'date',
                'regex:/^(0[1-9]|1[0-2])\/(0[1-9]|[1-2][0-9]|3[0-1])\/[0-9]{4}$/'
            ],
            'end' => [
                'date',
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
            'search.regex' => 'search must have at least 2 letters, numbers or symbols like ?+*_!#$%,\/;.&-',
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
            'max.numeric' => "Invalid maximum like number",
        ]);

        if ($validator->fails()) {
            if ($html) {
                return abort(400, $validator->errors());
            } else
                return response()->json([
                    'status' => 'failure',
                    'status_code' => 400,
                    'message' => 'Bad Request',
                    'errors' => $validator->errors(),
                ], 400);
        }

        $query = User::searchQuery($request->search);

        // Author
        if($request->author != null){
            $authorList = explode(",", $request->author);
            if(Auth::check())
                $query->join('user_subscription', 'user.id', '=', 'user_subscription.subscribed_user_id');

            $query = $query->where(function ($q) use($authorList){
                foreach($authorList as $author){
                    if(strcmp($author, "verified") == 0)
                        $q->orWhere('user.verified', true);
                    else if(strcmp($author, "subscribed") == 0 && Auth::check())
                        $q->orWhere('user_subscription.subscribing_user_id', Auth::user()->id);
                }
            });
        }

        // Date
        if($request->begin != null)
            $query->whereDate('user.birthday', '>=', Carbon::parse($request->begin)->toDateString());
        if($request->end != null)
            $query->whereDate('user.birthday', '<=', Carbon::parse($request->end)->toDateString());

        //Subs
        if($request->min != null)
            $query->where("subs.nsubs", '>=', $request->min);
        if($request->max != null)
            $query->where("subs.nsubs", '<=', $request->max);

        // Order
        if($request->sortBy != null){
            if(strcmp($request->sortBy, "alpha") == 0)
                $query->orderBy('user.name', 'asc');
            else if(strcmp($request->sortBy, "numerical") == 0)
                $query->orderBy('subs.nsubs', 'desc');
        }
        else{
            $query->orderBy('user.name', 'asc');
        }


        if (! (isset($_GET['ppp']) && isset($_GET['page']))) {
            $users = $query->get();
            $returnContent = [
               'data' => User::getUsersShortInfo($users),
               'paginator' => null
            ];
        } else {
            if (! (is_numeric($_GET['page']) && ($_GET['page'] > 0) && is_numeric($_GET['ppp']) && ($_GET['ppp'] > 0))) {
                if ($html)
                    return abort(400, 'The page and ppp arguments need to be positive integers.');
                else
                    return response()->json([
                        'status' => 'failure',
                        'status_code' => 400,
                        'message' => 'Bad Request',
                        'errors' => ['message' => 'The page and ppp arguments need to be positive integers.'],
                    ], 400);
            }

            $usersPerPage = $_GET['ppp'];
            $queryCollection = $query->get();
            $paginator = CollectionHelper::paginate($queryCollection, $queryCollection->count(), $usersPerPage);
            $paginator->withPath('/api/search/users' . str_replace(request()->url(), '',request()->fullUrl()) .'&ppp=' . $usersPerPage);
            $returnContent = [
                'data' => User::getUsersShortInfo($paginator),
                'paginator' => $paginator
            ];
        }

        if($html)
            return view('partials.user_preview_list', ['users' => $returnContent['data'], 'emptyMessage' => 'No Users found', 'paginator' =>  $returnContent['paginator']]);
        else if ($returnArray)
            return $returnContent;
        else {
            if (isset($returnContent['data'])) {
                return response()->json(['users' => $returnContent['data']], 200);
            }
            else {
                return response()->json(['users' => $returnContent], 200);
            }
        }
    }

    /**
     * API method that lets the user (or an admin) delete its account
     * /api/users/{id}
     *
     * @param int $id
     * @param Request $request
     */
    public function delete($id, Request $request){
        $userToDelete = User::find($id);
        if (is_null($userToDelete)) {
            return response()->json([
                'status' => 'failure',
                'status_code' => 404,
                'message' => 'Not Found',
                'errors' => ['user' => 'There is no user with id ' . $id],
            ], 404);
        }

        $this->authorize('delete', $userToDelete);

        $validator = Validator::make($request->all(), [
            'password' => 'required|string',
            [
                'password.required' => "The password field is required.",
                'password.string' => "The password needs to be a string."
            ]
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => 'failure',
                'status_code' => 400,
                'message' => 'Bad Request',
                'errors' => $validator->errors()
            ], 400);
        }

        $loggedInUser = User::find(Auth::user()->id);

        if (!Hash::check($request->password, Auth::user()->password) && !$loggedInUser->isAdmin())  {
            return response()->json([
                'status' => 'failure',
                'status_code' => 400,
                'message' => 'Delete Failed',
                'errors' => ['pass' => 'Wrong password'],
            ], 400);
        }

        $deleted = $userToDelete->delete();
        ImageUpload::deleteUserImage($userToDelete->photo);

        if ($deleted) {
            return response()->json(['message' => 'Operation performed successfully.'], 200);
        } else {
            return response()->json([
                'status' => 'failure',
                'status_code' => 500,
                'message' => 'Internal Server Eror',
                'errors' => ['message' => 'Could not delete.'],
            ], 500);
        }
    }

    /**
     * API method that lets the user report a user, based on the user_id passed as argument.
     * /api/users/{user_id}/report
     *
     * @param int $id
     * @param Request $request
     */
    public function report(Request $request, $id){
        // find user
        $user = User::find($id);
        if (is_null($user)){
            return response()->json([
                'status' => 'failure',
                'status_code' => 404,
                'message' => 'Not Found',
                'errors' => ['user' => 'There is no user with id ' . $id],
            ], 404);
        }

        Validator::extend('nreasons', function($attribute, $value, $parameters) {
            $max = (int) $parameters[0];
            $reasonList = explode(",", $value);
            return count($reasonList) <= $max && count($reasonList) > 0;
        });

        Validator::extend('value', function($attribute, $value, $parameters) {
            $max = (int) $parameters[0];
            $reasonList = explode(",", $value);
            foreach($reasonList as $reason){
                $reasonInt = (int) $reason;
                if($reasonInt > $max || $reasonInt < 1)
                    return false;
            }
            return true;
        });

        Validator::extend('nodups', function($attribute, $value, $parameters) {
            $reasonList = explode(",", $value);
            return count($reasonList) === count(array_flip($reasonList));
        });

        // perform validation on request data (400 bad request if not valid)
        $validator = Validator::make($request->all(), [
            'explanation' => 'required|string|min:5',
            'reasons' => 'required|string|nreasons:3|value:6|nodups',],
            [
                'explanation.required' => "The explanation cannot be empty",
                "explanation.string" => "Invalid explanation",
                "explanation.min" => "Explanation too short (must be at least 5 characters long).",
                'reasons.required' => "You have to have at least one reason",
                "reasons.string" => "Invalid reason",
                "reasons.nreasons" => "You must have between 1 and 3 reasons",
                "reasons.value" => "Invalid reason option",
                "reasons.nodups" => "You have a duplicate reason",
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

        // check if the user has authorization to report the user (401/403 if not)
        $this->authorize('report', $user);

        $alreadyReported = UserReport::search(Auth::user()->id, $id);
        if($alreadyReported)
            return response()->json([
                'status' => 'failure',
                'status_code' => 409,
                'message' => 'Conflict',
                'errors' => ['message' => 'Duplicate user report'],
            ], 409);

        $report = new Report();
        $report->explanation = $request->explanation;
        $report->reporter_id = Auth::user()->id;
        $report->save();

        $userReport = new UserReport();
        $userReport->report_id = $report->id;
        $userReport->user_id = $id;
        $userReport->save();

        $reasonList = explode(",", $request->reasons);
        foreach($reasonList as $reason){
            $reasonInt = (int) $reason;
            DB::table('report_reason')->insert(
                ['report_id' => $report->id, 'reason_id' => $reasonInt]
            );
        }

        return response()->json(['message' => 'Operation performed successfully.', 'id' => $report->id], 200);
    }


    /**
     * API Method used to get posts that were made by users that are subscribed.
     * /api/feed/users
     *
     * @param Request $request
     */
    public static function getSubbedAuthorPosts(Request $request) {
        $html = in_array('text/html', $request->getAcceptableContentTypes());

        if (!Auth::check()) {
            if($html)
                return abort(401, 'User needs to login');
            else
                return response()->json([
                    'status' => 'failure',
                    'status_code' => 401,
                    'message' => 'Unauthorized',
                    'errors' => ['user' => 'User needs to login']
                ], 401);
        }

        $user = User::find(Auth::user()->id);
        if(is_null($user)){
            if ($html)
                return abort(404, 'There is no user with id ' . Auth::user()->id);
            else
                return response()->json([
                    'status' => 'failure',
                    'status_code' => 404,
                    'message' => 'Not Found',
                    'errors' => ['user' => 'There is no user with id ' . Auth::user()->id],
                ], 404);
        }

        $request = new Request();
        $request->author = "subscribed";
        $request->sortBy = "recent";

        if (! (isset($_GET['ppp']) && isset($_GET['page']))) {
            $pagLink = null;
            $posts = Post::searchByType($request, null, null, true, null, $pagLink);
        } else {
            if (! (is_numeric($_GET['page']) && ($_GET['page'] > 0) && is_numeric($_GET['ppp']) && ($_GET['ppp'] > 0))) {
                if ($html)
                    return abort(400, 'The page and ppp arguments need to be positive integers.');
                else
                    return response()->json([
                        'status' => 'failure',
                        'status_code' => 400,
                        'message' => 'Bad Request',
                        'errors' => ['message' => 'The page and ppp arguments need to be positive integers.'],
                    ], 400);
            }

            $pagLink = '/api/feed/users?ppp=' . $_GET['ppp'];
            $posts = Post::searchByType($request, null, null, true, null, $pagLink);
        }

        $paginator = $posts['paginator'];
        $posts = $posts['data'];

        if ($html) {
            return view('partials.post_preview_list', [
                'posts' => $posts,
                'editable' => false,
                'showAuthor' => true,
                'showDate' => false,
                'emptyMessage' => 'No posts from subscribed authors',
                'paginator' =>  $paginator
            ]);
        }
        else {
            return ['data' => $posts, 'paginator' => $paginator];
        }
    }

    /**
     * API Method used to get posts that have tags that are subscribed.
     * /api/feed/tags
     *
     * @param Request $request
     */
    public static function getSubbedTagPosts(Request $request) {
        $html = in_array('text/html', $request->getAcceptableContentTypes());

        if (!Auth::check()) {
            if($html)
                return abort(401, 'User needs to login');
            else
                return response()->json([
                    'status' => 'failure',
                    'status_code' => 401,
                    'message' => 'Unauthorized',
                    'errors' => ['user' => 'User needs to login']
                ], 401);
        }

        $user = User::find(Auth::user()->id);
        if(is_null($user)){
            if($html)
                return abort(404, 'There is no user with id ' . Auth::user()->id);
            else
                return response()->json([
                    'status' => 'failure',
                    'status_code' => 404,
                    'message' => 'Not Found',
                    'errors' => ['user' => 'There is no user with id ' . Auth::user()->id],
                ], 404);
        }


        $subbedTags = $user->subbedTags()->get();
        $tagArr = array();
        foreach ($subbedTags as $tag) {
            array_push($tagArr, $tag->id);
        }
        $tags = implode(",", $tagArr);

        $request = new Request();
        $request->tags = $tags;
        $request->sortBy = "recent";

        $posts = null;
        if($tags == ""){
            $posts = ['data' => [], 'paginator' => []];
        }
        else {
            if (! (isset($_GET['ppp']) && isset($_GET['page']))) {
                $pagLink = null;
                $posts = Post::searchByType($request, null, null, true, null, $pagLink);
            } else {
                if (! (is_numeric($_GET['page']) && ($_GET['page'] > 0) && is_numeric($_GET['ppp']) && ($_GET['ppp'] > 0))) {
                    if ($html)
                        return abort(400, 'The page and ppp arguments need to be positive integers.');
                    else
                        return response()->json([
                            'status' => 'failure',
                            'status_code' => 400,
                            'message' => 'Bad Request',
                            'errors' => ['message' => 'The page and ppp arguments need to be positive integers.'],
                        ], 400);
                }

                $pagLink = '/api/feed/tags?ppp=' . $_GET['ppp'];
                $posts = Post::searchByType($request, null, null, true, null, $pagLink);
            }
        }

        $paginator = $posts['paginator'];
        $posts = $posts['data'];

        if ($html) {
            return view('partials.post_preview_list', [
                'posts' => $posts,
                'editable' => false,
                'showAuthor' => true,
                'showDate' => false,
                'emptyMessage' => 'No posts from subscribed authors',
                'paginator' =>  $paginator,
            ]);
        }
        else {
            return ['data' => $posts, 'paginator' => $paginator];
        }
    }

    /**
     * Generic subscription deletion function, to be called by the API endpoints,
     * in order to delete a user or tag subscription.
     */
    public function deleteSubGeneral($id, Request $request, $type) {
        $user = User::find($id);
        if(is_null($user)){
            return response()->json([
                'status' => 'failure',
                'status_code' => 404,
                'message' => 'Not Found',
                'errors' => ['user' => 'There is no user with id ' . $id],
            ], 404);
        }

        $this->authorize('deleteSub', $user);

        $validator = Validator::make($request->all(), [
            'id' => 'required|integer|min:0',
            ],
            [
                'id.required' => "An ID needs to be specified.",
                "id.integer" => "Invalid ID (must be integer).",
                "id.min" => "Invalid ID (needs to be 0 or higher).",
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

        if ($type == "User") {
            $subbedUser = $user->subbedUsers()->where('subscribed_user_id', $request->id)->first();
            if ($subbedUser == null) {
                return response()->json([
                    'status' => 'failure',
                    'status_code' => 400,
                    'message' => 'Bad Request',
                    'errors' => ['user' => 'User ' . $id . ' does not subscribe a user with ID ' . $request->id],
                ], 400);
            }
            DB::table('user_subscription')->where('subscribing_user_id', $id)->where('subscribed_user_id', $request->id)->delete();
        }

        else if ($type == "Tag") {
            $subbedTag = $user->subbedTags()->where('tag_id', $request->id)->first();
            if ($subbedTag == null) {
                return response()->json([
                    'status' => 'failure',
                    'status_code' => 400,
                    'message' => 'Bad Request',
                    'errors' => ['user' => 'User ' . $id . 'does not subscribe a tag with ID ' . $request->id],
                ], 400);
            }
            DB::table('tag_subscription')->where('user_id', $id)->where('tag_id', $request->id)->delete();
        }
        else {
            return response()->json([
                'status' => 'failure',
                'status_code' => 400,
                'message' => 'Bad Request',
                'errors' => ['type' => 'A type (Tag or User) needs to be specified.'],
            ], 400);
        }

        return response()->json(['message' => 'Operation performed successfully.'], 200);
    }

    /**
     * Generic subscription addition function, to be called by the API endpoints,
     * in order to add a user or tag subscription.
     */
    public function addSubGeneral($id, Request $request, $type) {
        $user = User::find($id);
        if(is_null($user)){
            return response()->json([
                'status' => 'failure',
                'status_code' => 404,
                'message' => 'Not Found',
                'errors' => ['user' => 'There is no user with id ' . $id],
            ], 404);
        }

        $this->authorize('addSub', $user);

        $validator = Validator::make($request->all(), [
            'id' => 'required|integer|min:0',
            ],
            [
                'id.required' => "An ID needs to be specified.",
                "id.integer" => "Invalid ID (must be integer).",
                "id.min" => "Invalid ID (needs to be 0 or higher).",
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

        if ($type == "Tag") {
            if (is_null(Tag::find($request->id))) {
                return response()->json([
                    'status' => 'failure',
                    'status_code' => 400,
                    'message' => 'Bad Request',
                    'errors' => ['tag' => 'There is no tag with id' . $request->id],
                ], 400);
            }

            $tag = $user->subbedTags()->where('tag_id', $request->id)->first();
            if ($tag != null) {
                return response()->json([
                    'status' => 'failure',
                    'status_code' => 400,
                    'message' => 'Bad Request',
                    'errors' => ['sub' => 'Already subbed'],
                ], 400);
            }

            DB::table('tag_subscription')->insert(['user_id' => $id, 'tag_id' => $request->id]);

        }
        else if ($type == "User") {

            if ($id === $request->id) {
                return response()->json([
                    'status' => 'failure',
                    'status_code' => 400,
                    'message' => 'Bad Request',
                    'errors' => ['user' => 'A user cannot subscribe to himself.'],
                ], 400);
            }

            if (is_null(User::find($request->id))) {
                return response()->json([
                    'status' => 'failure',
                    'status_code' => 400,
                    'message' => 'Bad Request',
                    'errors' => ['user' => 'There is no user with id ' . $request->id],
                ], 400);
            }

            $subbedUser = $user->subbedUsers()->where('subscribed_user_id', $request->id)->first();
            if ($subbedUser != null) {
                return response()->json([
                    'status' => 'failure',
                    'status_code' => 400,
                    'message' => 'Bad Request',
                    'errors' => ['sub' => 'Already subbed'],
                ], 400);
            }

            DB::table('user_subscription')->insert(['subscribing_user_id' => $id, 'subscribed_user_id' => $request->id]);
        }
        else {
            return response()->json([
                'status' => 'failure',
                'status_code' => 400,
                'message' => 'Bad Request',
                'errors' => ['type' => 'A type (Tag or User) needs to be specified.'],
            ], 400);
        }

        return response()->json(['message' => 'Operation performed successfully.'], 200);
    }

    /**
     * Periodic function to unban users
     */
    static public function unbanAfterTime(){
        $ban = Ban::all();

        foreach($ban as $banInfo){

            $user = User::find($banInfo->user_id);

            if($banInfo->ban_end != null){
                $time = new \Carbon\Carbon($banInfo->ban_end,'Europe/London');
                $diff = $time->diffInDays(\Carbon\Carbon::now('Europe/London'), false);

                if($diff >= 0){

                    $user = User::find($banInfo->user_id);
                    $banInfo->delete();
                    $user->banned = false;
                    $user->save();
                }
            }

        }
    }
}
