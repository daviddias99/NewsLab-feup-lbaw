<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Post;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    /**
     * Show the search page.
     *
     * @return View
     */
    public function show()
    {

        if (!request()->has('search') || empty(request()->search)) {
            return redirect()->back();
        }

        $request = new Request(request()->query());

        $_GET['ppp'] = !isset($_GET['ppp']) ? 3 : $_GET['ppp'];
        $_GET['page'] = !isset($_GET['page']) ? 1 : $_GET['page'];

        if (!(is_numeric($_GET['page']) && ($_GET['page'] > 0) && is_numeric($_GET['ppp']) && ($_GET['ppp'] > 0))) {
            abort(400, "Invalid ppp and page arguments.");
        }

        $posts = PostController::search($request, true);
        if (!is_array($posts)) {
            return abort(400, reset(json_decode($posts->getContent())->errors)[0]);
        }

        $tags = TagController::search($request);
        $tags = json_decode($tags->getContent(), true);

        $users = UserController::search($request, true);

        return view('pages.search', [
            'posts' => $posts,
            'users' => $users,
            'tags' => $tags
        ]);
    }
}