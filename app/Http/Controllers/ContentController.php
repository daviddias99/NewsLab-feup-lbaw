<?php

namespace App\Http\Controllers;

use App\Content;
use App\Rating;
use App\Report;
use App\ContentReport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\QueryException;

/**
 * Controller for the content class
 */
class ContentController extends Controller {

    /**
     * API method that lets the user rate a content, based on the content_id passed as argument.
     * /api/rate/{content_id}
     * 
     * @param Request $request
     * @param int $id
     * 
     * @return int New Like Difference
     */
    public function rate(Request $request, $id) {
        // find content
        $content = Content::find($id);
        if (is_null($content)) {
            return response()->json([
                'status' => 'failure',
                'status_code' => 404,
                'message' => 'Not Found',
                'errors' => ['content' => 'There is no content with this id: ' . $id],
            ], 404);
        }

        // check if the user has authorization to rate the content (403 if not)
        $this->authorize('rate', $content);

        // perform validation on request data (400 bad request if not valid)
        $validator = Validator::make($request->all(), [
            'rating' => 'required|boolean']
        );

        if ($validator->fails()) {
            return response()->json([
                'status' => 'failure',
                'status_code' => 400,
                'message' => 'Bad Request',
                'errors' => $validator->errors(),
            ], 400);
        }

        // if the content is not the most recent version, 400
        if ($content->most_recent !== true)
            return response()->json([
                'status' => 'failure',
                'status_code' => 400,
                'message' => 'Bad Request',
                'errors' => ['content' => 'The content has a newer version, and this one cannot be upvoted/downvoted'],
            ], 400);

        // if the comment was deleted, 400
        if ($content->body === NULL)
            return response()->json([
                'status' => 'failure',
                'status_code' => 400,
                'message' => 'Bad Request',
                'errors' => ['content' => 'The comment was deleted and it cannot be upvoted/downvoted'],
            ], 400);

        // NOTE: because Eloquent does not support tables with composite keys, the DB facade had to
        // be used directly to perform the update and delete.
        $existentRating = Rating::where('content_id', $id)->where('user_id', Auth::user()->id)->first();

        if ($existentRating !== null && $existentRating->like !== $request->rating) {
            DB::table('rating')->where('content_id', $id)->where('user_id', Auth::user()->id)->update(['like' => $request->rating]);
        }
        else if ($existentRating === null) {
            $rating = new Rating();
            $rating->user_id = Auth::user()->id;
            $rating->content_id = $id;
            $rating->like = $request->rating;
            $rating->save();
        }

        $likesDifference = Content::find($id)->likes_difference;

        return response()->json([
            'likes_difference' => $likesDifference
        ], 200);
    }

    /**
     * API method that lets the user remove a rate from a content
     * /api/rate/{content_id}
     * 
     * @param int $id
     * 
     * @return int New Like Difference
     */
    public function unrate($id) {
        // find content
        $content = Content::find($id);
        if (is_null($content)) {
            return response()->json([
                'status' => 'failure',
                'status_code' => 404,
                'message' => 'Not Found',
                'errors' => ['content' => 'There is no content with this id: ' . $id],
            ], 404);
        }

        // check if the user has authorization to rate the content (403 if not)
        $this->authorize('rate', $content);

        // if the content is not the most recent version, 400
        if ($content->most_recent !== true)
            return response()->json([
                'status' => 'failure',
                'status_code' => 400,
                'message' => 'Bad Request',
                'errors' => ['content' => 'The content has a newer version, and this one cannot be upvoted/downvoted'],
            ], 400);

        // if the comment was deleted, 400
        if ($content->body === NULL)
            return response()->json([
                'status' => 'failure',
                'status_code' => 400,
                'message' => 'Bad Request',
                'errors' => ['content' => 'The content was deleted and it cannot be upvoted/downvoted'],
            ], 400);

        // NOTE: because Eloquent does not support tables with composite keys, the DB facade had to
        // be used directly to perform the update and delete.
        $existentRating = Rating::where('content_id', $id)->where('user_id', Auth::user()->id)->first();

        // if there is existent rating and the request rating is the same, simply delete it
        if ($existentRating !== null) {
            DB::table('rating')->where('content_id', $id)->where('user_id', Auth::user()->id)->delete();
        }
        else{
            return response()->json([
                'status' => 'failure',
                'status_code' => 400,
                'message' => 'Bad Request',
                'errors' => ['content' => 'There is no rating in this content'],
            ], 400);
        }

        $likesDifference = Content::find($id)->likes_difference;

        return response()->json([
            'likes_difference' => $likesDifference
        ], 200);
    }

    /**
     * API method that lets the user report a content, based on the content_id passed as argument.
     * /api/contents/{content_id}/report
     * 
     * @param Request $request
     * @param int $id
     * 
     * @return int ID of the report
     */
    public function report(Request $request, $id){
        // find content
        $content = Content::find($id);
        if (is_null($content)){
            return response()->json([
                'status' => 'failure',
                'status_code' => 404,
                'message' => 'Not Found',
                'errors' => 'There is no content with this id: ' . $content,
            ], 404);
        }

        // check if the user has authorization to report the content (401/403 if not)
        $this->authorize('report', $content);

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

        $alreadyReported = ContentReport::search(Auth::user()->id, $id);
        if($alreadyReported)
            return response()->json([
                'status' => 'failure',
                'status_code' => 409,
                'message' => 'Conflict',
                'errors' => ['content' => 'Duplicate content report'],
            ], 409);

        DB::beginTransaction();
        try{
            $report = new Report();
            $report->explanation = $request->explanation;
            $report->reporter_id = Auth::user()->id;
            $report->save();

            $contentReport = new ContentReport();
            $contentReport->report_id = $report->id;
            $contentReport->content_id = $id;
            $contentReport->save();

            $reasonList = explode(",", $request->reasons);
            foreach($reasonList as $reason){
                $reasonInt = (int) $reason;
                DB::table('report_reason')->insert(
                    ['report_id' => $report->id, 'reason_id' => $reasonInt]
                ); 
            }
            
            DB::commit();
            return response()->json(['id' => $report->id], 200);
        } catch(QueryException $err){
            DB::rollBack();
            return response()->json([
                'status' => 'failure',
                'status_code' => 500,
                'message' => 'Internal Error',
                'errors' => ['query' => $err],
            ], 500);
        }
    }
}
