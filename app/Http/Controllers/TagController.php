<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;

use App\Tag;
use App\Report;
use App\TagReport;
use App\Post;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Http\Request;
use Illuminate\Database\QueryException;

class TagController extends Controller {
    /**
     * Show the tag page.
     *
     * @return View
     */
    public function show($id)
    {
        $tag = Tag::find($id);
        if (is_null($tag)) {
            return abort(404, 'There is no tag with id ' . $id);
        }

        $subscribed = Auth::check() && $tag->subscribed(Auth::user());

        $prov = request()->query();
        $prov['tags'] = $tag->id;

        $request = new Request($prov);


        $_GET['ppp'] = !isset($_GET['ppp']) ? 3 : $_GET['ppp'];
        $_GET['page'] = !isset($_GET['page']) ? 1 : $_GET['page'];

        if (!(is_numeric($_GET['page']) && ($_GET['page'] > 0) && is_numeric($_GET['ppp']) && ($_GET['ppp'] > 0))) {
            return response()->json([
                'status' => 'failure',
                'status_code' => 400,
                'message' => 'Bad Request',
                'errors' => ['message' => 'The page and ppp arguments need to be positive integers.'],
            ], 400);
        }

        $posts = PostController::search($request, true);

        return view('pages.tag', [
            'tag' => $tag->getTagInfo(),
            'subscribed' => $subscribed,
            'posts' => $posts
        ]);
    }

    /**
     * API method that lets the user search for posts of a certain tag.
     * /api/search/tags
     */
    public static function search(Request $request, $limit = null)
    {
        Validator::extend('typelist', function ($attribute, $value, $parameters) {
            $validValues = explode(",", $parameters[0]);
            if (!is_array($validValues))
                return false;

            $validValues = array_map((function ($value) {
                return strtolower($value);
            }), $validValues);

            $valueList = explode(",", $value);
            foreach ($valueList as $value) {
                error_log($value);
                if (!in_array(strtolower($value), $validValues)) {
                    error_log("false");
                    return false;
                }
            }

            return true;
        });

        $validator = Validator::make(
            $request->all(),
            [
                'search' => [
                    'string',
                    'regex:/^[A-Za-z0-9?+*_!#$%,\/;.&\s-]{2,}$/'
                ],
                'author' => [
                    'string',
                    'typelist:Verified,Subscribed'
                ],
                'sortBy' => [
                    'string',
                    Rule::in(['alpha', 'numerical'])
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
                'min.numeric' => "Invalid minimum subs number",
                'max.numeric' => "Invalid maximum subs number",
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

        $result = Tag::search($request, $limit);
        $html = in_array('text/html', $request->getAcceptableContentTypes());

        if ($html)
            return view('partials.tag_preview_list', ['tags' => $result]);
        else
            return response()->json(['tags' => $result], 200);
    }

    /**
     * API method that lets the user report a tag, based on the tag_id passed as argument.
     * /api/tags/{tag_id}/report
     */
    public function report(Request $request, $id)
    {

        // find tag
        $tag = Tag::find($id);
        if (is_null($tag)) {
            return response()->json([
                'status' => 'failure',
                'status_code' => 404,
                'message' => 'Not Found',
                'errors' => 'There is no tag with this id: ' . $tag,
            ], 404);
        }

        // check if the user has authorization to report the tag (401/403 if not)
        $this->authorize('report', $tag);

        Validator::extend('nreasons', function ($attribute, $value, $parameters) {
            $max = (int) $parameters[0];
            $reasonList = explode(",", $value);
            return count($reasonList) <= $max && count($reasonList) > 0;
        });

        Validator::extend('value', function ($attribute, $value, $parameters) {
            $max = (int) $parameters[0];
            $reasonList = explode(",", $value);
            foreach ($reasonList as $reason) {
                $reasonInt = (int) $reason;
                if ($reasonInt > $max || $reasonInt < 1)
                    return false;
            }
            return true;
        });

        Validator::extend('nodups', function ($attribute, $value, $parameters) {
            $reasonList = explode(",", $value);
            return count($reasonList) === count(array_flip($reasonList));
        });

        // perform validation on request data (400 bad request if not valid)
        $validator = Validator::make($request->all(), [
            'explanation' => 'required|string|regex:/^[A-Za-z0-9?+*_!#$%,\/;.&\s-]{6,500}$/',
            'reasons' => 'required|string|nreasons:3|value:6|nodups',],
            [
                'explanation.required' => "The explanation cannot be empty",
                "explanation.string" => "Invalid explanation",
                "explanation.regex" => "Explanation must have between 6 and 500 letters, numbers or symbols like ?+*_!#$%,\/;.&-",
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

        $alreadyReported = TagReport::search(Auth::user()->id, $id);
        if ($alreadyReported)
            return response()->json([
                'status' => 'failure',
                'status_code' => 409,
                'message' => 'Conflict',
                'errors' => 'Duplicate tag report',
            ], 409);

        DB::beginTransaction();
        try {
            $report = new Report();
            $report->explanation = $request->explanation;
            $report->reporter_id = Auth::user()->id;
            $report->save();

            $tagReport = new TagReport();
            $tagReport->report_id = $report->id;
            $tagReport->tag_id = $id;
            $tagReport->save();

            $reasonList = explode(",", $request->reasons);
            foreach ($reasonList as $reason) {
                $reasonInt = (int) $reason;
                DB::table('report_reason')->insert(
                    ['report_id' => $report->id, 'reason_id' => $reasonInt]
                );
            }

            DB::commit();
            return response()->json(['id' => $report->id], 200);
        } catch (QueryException $err) {
            DB::rollBack();

            return response()->json([
                'status' => 'failure',
                'status_code' => 500,
                'message' => 'Internal server error',
                'errors' => 'Failed to commit transactio',
            ], 500);
        }
    }
}
