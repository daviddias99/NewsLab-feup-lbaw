<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Post;
use App\Tag;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class NewsOpinionsFeedController extends Controller
{
    /**
     * Show the news page.
     *
     * @return View
     */
    public function showNews() {
        return $this->showNewsOpinionsPage("News");
    }

    /**
     * Show the opinions page.
     *
     * @return View
     */
    public function showOpinions() {
        return $this->showNewsOpinionsPage("Opinion");
    }


    /**
     * Helper function to either return Opinions or News
     *
     * @return View
     */
    public function showNewsOpinionsPage($type) {
        $lastPostPreviousMonthDate = Carbon::now()->subMonth(1)->format('m/d/Y');
        
        // featured post
        $request = new Request();
        $request->begin = $lastPostPreviousMonthDate;
        $request->sortBy = "numerical";

        $featuredPost = Post::searchByType($request, $type, 1, true)['data'];
        $featuredPost = (count($featuredPost) > 0) ? $featuredPost[0] : null;
        if ($featuredPost == null) {
            $request = new Request();
            $request->sortBy = "numerical";
            $featuredPost = Post::searchByType($request, $type, 1, true)['data'];
            $featuredPost = (count($featuredPost) > 0) ? $featuredPost[0] : null;
        }


        // main posts
        $request = new Request();
        $request->sortBy = "recent";
        $_GET['ppp'] = 6;
        $_GET['page'] = 1;
        $mainPosts = Post::searchByType($request, $type, null, true);
        unset($_GET['ppp']);
        unset($_GET['page']);

        // latest posts
        $latestPosts = json_decode(Post::searchByType($request, $type, 3, false)->getContent())->data;

        // hot posts
        $request = new Request();
        $request->sortBy = "numerical";
        $hotPosts = json_decode(Post::searchByType($request, $type, 3, false)->getContent())->data;

        // random tags
        $randomTags = Tag::inRandomOrder()->limit(8)->get();

        return view(
            'pages.news-opinions',
            [
                'type' => $type,
                'featuredPost' => $featuredPost,
                'mainPosts' => $mainPosts,
                'latestPosts' => $latestPosts,
                'hotPosts' => $hotPosts,
                'randomTags' => $randomTags
            ]
        );
    }

    /**
     * Show the feed page.
     *
     * @return View
     */
    public function showFeed() {
        if (!Auth::check()) {
            return abort(401, 'User needs to login');
        }

        $_GET['ppp'] = 6;
        $_GET['page'] = 1;

        $request = new Request();

        $authorPosts = UserController::getSubbedAuthorPosts($request);
        $tagPosts = UserController::getSubbedTagPosts($request);

        unset($_GET['ppp']);
        unset($_GET['page']);

        $usedPostsArray = [];

        $request = new Request();
        $request->author = "subscribed";
        $request->sortBy = "numerical";
        $hotPostsAuthor = json_decode(Post::searchByType($request, null, 4, false)->getContent())->data;
        foreach($hotPostsAuthor as $post)
            array_push($usedPostsArray, $post->id);

        $user = User::find(Auth::user()->id);

        $subbedTags = $user->subbedTags()->get();
        $tagArr = array();
        foreach ($subbedTags as $tag) {
            array_push($tagArr, $tag->id);
        }
        $tags = implode(",", $tagArr);

        $request = new Request();
        $request->tags = $tags;
        $request->sortBy = "numerical";
        $hotPostsTags = json_decode(Post::searchByType($request, null, 4, false, $usedPostsArray)->getContent())->data;

        $hotPosts = array_merge($hotPostsAuthor, $hotPostsTags);

        return view('pages.feed', [
            'tagPosts' => $tagPosts,
            'authorPosts' => $authorPosts,
            'hotPosts' => $hotPosts
        ]);
    }
}