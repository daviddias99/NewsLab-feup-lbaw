<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Carbon;
use Illuminate\Http\Request;
use App\Tag;
use App\Post;

class HomepageController extends Controller {
    /**
     * Show the home page.
     *
     * @return View
     */
    public function show() {
        $lastPostDate = Post::max('publication_date');
        $lastPostPreviousMonthDate = Carbon::parse($lastPostDate)->subMonth()->toDateString();
        $usedPostsArray = [];

        // banner posts
        $request = new Request();
        $request->begin = $lastPostPreviousMonthDate;
        $request->sortBy = "numerical";
        $bannerPosts = json_decode(Post::searchByType($request, null, 3, false)->getContent())->data;
        foreach($bannerPosts as $post)
            array_push($usedPostsArray, $post->id);

        // hot tags
        $request = new Request();
        $request->sortBy = "numerical";
        $hotTags = json_decode(TagController::search($request, 3)->getContent())->tags;

        // recent news and opinions
        $request = new Request();
        $request->min = 20;
        $request->sortBy = "recent";
        $recentNews = json_decode(Post::searchByType($request, "News", 3, false, $usedPostsArray)->getContent())->data;
        $recentOpinions = json_decode(Post::searchByType($request, "Opinion", 3, false, $usedPostsArray)->getContent())->data;
        foreach($recentNews as $post)
            array_push($usedPostsArray, $post->id);

        foreach($recentOpinions as $post)
            array_push($usedPostsArray, $post->id);

        // random tag posts
        $randomTagPosts = [];
        $randomTag = null;

        while (count($randomTagPosts) == 0) {
            $randomTag = Tag::inRandomOrder()->first();
            $request = new Request();
            $request->tags = strval($randomTag->id);
            $request->sortBy = "numerical";
            $randomTagPosts =  json_decode(Post::searchByType($request, null, 4, false, $usedPostsArray)->getContent())->data;
        }

        // hot posts
        $request = new Request();
        $request->sortBy = "numerical";
        $hotPosts = json_decode(Post::searchByType($request, null, 3, false)->getContent())->data;

        // random tags
        $randomTags = Tag::inRandomOrder()->limit(8)->get();

        return view('pages.homepage',
            [
             'bannerPosts' => $bannerPosts,
             'hotTags' => $hotTags,
             'recentNews' => $recentNews,
             'recentOpinions' => $recentOpinions,
             'randomTag' => $randomTag,
             'randomTagPosts' => $randomTagPosts,
             'hotPosts' => $hotPosts,
             'randomTags' => $randomTags
            ]
        );
    }

    public function weather(Request $request) {

        $client = new \GuzzleHttp\Client();
        $searchIPLink = 'http://ip-api.com/json/';
        $weatherLink = 'http://api.openweathermap.org/data/2.5/weather?q=';
        $apiKey = 'appid=b683c0b75078429d4673ea9ee9ddf6da';
        $units = 'units=metric';

        if($request->ip() != '127.0.0.1')
            $searchIPLink = $searchIPLink . $request->ip();

        $ipInfo = $client->get($searchIPLink);
        $dec =  json_decode($ipInfo->getBody());


        if(isset($dec->city))
            $city = $dec->city;
        else
            $city = "Porto";


        $weatherInfo = $client->get($weatherLink . $city . '&' . $units . '&' . $apiKey);

        return $weatherInfo->getBody();
    }
}