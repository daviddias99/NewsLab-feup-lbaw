<?php

namespace App\Http\Controllers;

use App\Content;
use App\Comment;
use App\Reply;
use App\Admin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use Illuminate\Database\QueryException;
use App\Rating;
use App\Utils\CollectionHelper;

class ReplyController extends Controller
{
    /**
     * API Creates a new reply
     * /api/comments/{comment_id}/reply
     * 
     * @param int $comment_id
     * @param Request $request
     * 
     * @return Reply The reply created.
     */
    public function create(Request $request, $comment_id)
    {
        $comment = Comment::find($comment_id);
        if(is_null($comment)) {
            return response()->json([
                'status' => 'failure',
                'status_code' => 404,
                'message' => 'Not Found',
                'errors' => ['comment' => "Comment doesn't exist"],
            ], 404);
        }

        $this->authorize('create', Content::class);

        $validator = Validator::make($request->all(), [
            'body' => 'required|string|regex:/^[A-Za-z0-9?+*_!#$%,\/;.&\s-]{1,500}$/'],
            [
                'body.required' => "A reply cannot be empty",
                "body.string" => "Invalid reply",
                "body.regex" => "Comment must have between 1 and 500 letters, numbers or symbols like ?+*_!#$%,\/;.&-"
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
        try {
            $content = new Content();
            $content->body = $request->body;
            $content->author_id = Auth::user()->id;
            $content->save();

            $reply = new Reply();
            $reply->content_id = $content->id;
            $reply->comment_id = $request->comment_id;
            $reply->save();

            DB::commit();
            $reply = Reply::find($content->id);
            return response()->json(['reply' => $reply->replyInfo()], 201);

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

    /**
     * API Deletes a reply.
     * /api/replies/{id}
     * 
     * @param int $id
     *
     * @return int ID The ID of the Reply deleted
     */
    public function delete($id)
    {
        $reply = Reply::find($id);
        if(is_null($reply)){
            return response()->json([
                'status' => 'failure',
                'status_code' => 404,
                'message' => 'Not Found',
                'errors' => ['reply' => 'There is no reply with id: ' . $id],
            ], 404);
        }

        $this->authorize('delete', $reply->content);

        $is_Admin = Admin::find(Auth::user()->id);
        if($is_Admin && $reply->content->author_id != Auth::user()->id){
            $is_Admin->comments_deleted++;
            $is_Admin->save();
        }

        $reply->content->delete();

        return response()->json(['id'=>$id], 200);
    }

    /**
     * API Updates a reply.
     * /api/replies/{id}
     * 
     * @param int $id
     * @param Request $request
     * 
     * @return int ID The ID of the updated Reply
     */
    public function update(Request $request, $id)
    {
        $reply = Reply::find($id);
        if (is_null($reply)){
            return response()->json([
                'status' => 'failure',
                'status_code' => 404,
                'message' => 'Not Found',
                'errors' => ['reply' => 'There is no reply with id: ' . $id],
            ], 404);
        }

        $this->authorize('update', $reply->content);

        if(strcmp($reply->content->body, $request->body) == 0)
            return response()->json([
                'status' => 'failure',
                'status_code' => 400,
                'message' => 'Bad Request',
                'errors' => ['body' => 'Reply was not changed'],
            ], 400);

        $validator = Validator::make($request->all(), [
            'body' => 'required|string|regex:/^[A-Za-z0-9?+*_!#$%,\/;.&\s-]{1,500}$/'],
            [
                'body.required' => "A reply cannot be empty.",
                "body.string" => "Invalid reply",
                "body.regex" => "Reply must have between 1 and 500 letters, numbers or symbols like ?+*_!#$%,\/;.&-"
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
            $reply->content->body = $request->body;
            $reply->content->save();

            $reply->modification_date = \Carbon\Carbon::now();
            $reply->save();

            DB::commit();

            return response()->json(array('id' => $reply->content_id), 200);

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

    /**
     * API method that fetches the versions of a certain reply.
     * /api/replies/{reply_id}/versions 
     * 
     * @param int $id
     * @param Request $request
     * 
     * Reponse can be either JSON or HTML depending on the Accept type
     */
    public function getVersions(Request $request, $id) {
        $html = in_array('text/html', $request->getAcceptableContentTypes());
        
        $reply = Reply::find($id);
        if(is_null($reply)){
            if($html)
                return abort(404, 'There is no reply with id: ' . $id);
            else
                return response()->json([
                    'status' => 'failure',
                    'status_code' => 404,
                    'message' => 'Not Found',
                    'errors' => ['reply' => 'There is no reply with id: ' . $id],
                ], 404);
        }


        if (!(isset($_GET['ppp']) && isset($_GET['page']))) {
            $versions = $reply->versions();
            $paginator = null;
        } else {
            if (! (is_numeric($_GET['page']) && ($_GET['page'] > 0) && is_numeric($_GET['ppp']) && ($_GET['ppp'] > 0)))
            {
                if($html)
                    return abort(400, 'Invalid parameters');
                else
                    return response()->json([
                        'status' => 'failure',
                        'status_code' => 400,
                        'message' => 'Bad Request',
                        'errors' => ['paginator' => 'Either page or ppp were not positive ints'],
                    ], 400);
            }
            
            $repliesPerPage = $_GET['ppp'];
            $versions = $reply->versions();
            $paginator = CollectionHelper::paginate($versions, $versions->count(), $repliesPerPage);
            $versions = $paginator;
            $paginator->withPath('/api/replies/' . $id . '/versions?ppp=' . $repliesPerPage);
        }

        $versions = Reply::repliesInfo($versions);

        if ($html) {
            $versions = $versions->map(function($reply) {
                $userLikedComment = null;
                if (Auth::check() && $reply['most_recent']) {
                    $userLikedComment = Rating::likeValue($reply['id'], Auth::user()->id);
                }
                $reply['userLikedComment'] = $userLikedComment;
                return $reply;
            });

            return View('partials.comment_preview_list', [ 'comments' => $versions, 'context' => "versions", 'emptyMessage' => "There are no versions to show.", 'paginator' => $paginator ]);
        } 
        else {
            return response()->json(['versions'=> $versions], 200);
        }
    }

    /**
     * Method that presents the page with all the versions of the specified reply.
     * /replies/{reply_id}/versions
     * 
     * @return View Rendered view of the page
     */
    public function versions($id) {
        $reply = Reply::find($id);
        if(is_null($reply)){
            return abort(404, 'There is no reply with id: ' . $id);
        }

        $versions = $reply->versions();
        $versionsPaginator = CollectionHelper::paginate($versions, $versions->count(), 1);
        $versionsPaginator->withPath('/api/replies/' . $id . '/versions?ppp=1');

        $repliesData = Reply::repliesInfo($versionsPaginator);
        for($i = 0; $i < count($repliesData); $i++) {
            $reply = $repliesData[$i];
            $userLikedComment = null;
            if (Auth::check() && $reply['most_recent']) {
                $userLikedComment = Rating::likeValue($reply['id'], Auth::user()->id);
            }
            $reply['userLikedComment'] = $userLikedComment;
            $repliesData[$i] = $reply;
        }

        return view('pages.versions', [
            'type' => 'comment', 
            'versions' => [
                'data' => $repliesData,
                'paginator' => $versionsPaginator
            ],
        ]);
    }
}