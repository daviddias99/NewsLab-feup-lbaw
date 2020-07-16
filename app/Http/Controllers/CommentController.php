<?php

namespace App\Http\Controllers;

use App\Content;
use App\Post;
use App\Comment;
use App\Admin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use App\Rating;
use Illuminate\Database\QueryException;
use App\Utils\CollectionHelper;

class CommentController extends Controller
{
    /**
     * API Creates a new comment.
     * /api/posts/{post_id}/comment
     *
     * @param int $post_id
     * @param Request $request
     *
     * @return Comment The comment created.
     */
    public function create(Request $request, $post_id)
    {
        $post = Post::find($post_id);
        if(is_null($post)) {
            return response()->json([
                'status' => 'failure',
                'status_code' => 404,
                'message' => 'Not Found',
                'errors' => ['post' => "Post doesn't exist"],
            ], 404);
        }

        $this->authorize('create', Comment::class);

        $validator = Validator::make($request->all(), [
            'body' => 'required|string|regex:/^[A-Za-z0-9?+*_!#$%,\/;.&\s-]{1,500}$/'],
            [
                'body.required' => "A comment can't be empty.",
                "body.string" => "Invalid comment.",
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
        try{
            $content = new Content();
            $content->body = $request->body;
            $content->author_id = Auth::user()->id;
            $content->save();

            $comment = new Comment();
            $comment->content_id = $content->id;
            $comment->post_id = $post_id;
            $comment->save();

            DB::commit();
            $comment = Comment::find($content->id);
            return response()->json(['comment' => $comment->commentInfo()], 201);

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
     * API Deletes a comment.
     * /api/comments/{id}
     *
     * @param int $id
     *
     * @return int ID The ID of the Comment deleted
     */
    public function delete($id)
    {
        $comment = Comment::find($id);
        if(is_null($comment)){
            return response()->json([
                'status' => 'failure',
                'status_code' => 404,
                'message' => 'Not Found',
                'errors' => ['comment' => 'There is no comment with id: ' . $id],
            ], 404);
        }

        $this->authorize('delete', $comment->content);

        $is_Admin = Admin::find(Auth::user()->id);
        if($is_Admin && $comment->content->author_id != Auth::user()->id){
            $is_Admin->comments_deleted++;
            $is_Admin->save();
        }

        $comment->content->delete();

        return response()->json(['id' => $id], 200);
    }

    /**
     * API Updates a comment.
     * /api/comments/{id}
     *
     * @param int $id
     * @param Request $request
     *
     * @return int ID The ID of the updated Comment
     */
    public function update(Request $request, $id)
    {
        $comment = Comment::find($id);
        if (is_null($comment)){
            return response()->json([
                'status' => 'failure',
                'status_code' => 404,
                'message' => 'Not Found',
                'errors' => ['comment' => 'There is no comment with id: ' . $id],
            ], 404);
        }

        $this->authorize('update', $comment->content);

        if(strcmp($comment->content->body, $request->body) == 0)
            return response()->json([
                'status' => 'failure',
                'status_code' => 400,
                'message' => 'Bad Request',
                'errors' => ['body' => 'Comment was not changed'],
            ], 400);
        $validator = Validator::make($request->all(), [
            'body' => 'required|string|regex:/^[A-Za-z0-9?+*_!#$%,\/;.&\s-]{1,500}$/'],
            [
                'body.required' => "A comment cannot be empty.",
                "body.string" => "Invalid comment.",
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
        try{
            $comment->content->body = $request->body;
            $comment->content->save();

            $comment->modification_date = \Carbon\Carbon::now();
            $comment->save();

            DB::commit();

            return response()->json(array('id' => $comment->content_id), 200);

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
     * API method that fetches the versions of a certain comment.
     * /api/comments/{comment_id}/versions
     *
     * @param int $id
     * @param Request $request
     *
     * Reponse can be either JSON or HTML depending on the Accept type
     */
    public function getVersions(Request $request, $id) {
        $html = in_array('text/html', $request->getAcceptableContentTypes());

        $comment = Comment::find($id);
        if(is_null($comment)){
            if($html)
                return abort(404, 'There is no comment with id: ' . $id);
            else
                return response()->json([
                    'status' => 'failure',
                    'status_code' => 404,
                    'message' => 'Not Found',
                    'errors' => ['comment' => 'There is no comment with id: ' . $id],
                ], 404);
        }

        if (!(isset($_GET['ppp']) && isset($_GET['page']))) {
            $versions = $comment->versions();
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

            $commentsPerPage = $_GET['ppp'];
            $versions = $comment->versions();
            $paginator = CollectionHelper::paginate($versions, $versions->count(), $commentsPerPage);
            $versions = $paginator;
            $paginator->withPath('/api/comments/' . $id . '/versions?ppp=' . $commentsPerPage);
        }

        $versions = Comment::commentsInfo($versions);

        if ($html) {
            $versions = $versions->map(function($comment) {
                $userLikedComment = null;
                if (Auth::check() && $comment['most_recent']) {
                    $userLikedComment = Rating::likeValue($comment['id'], Auth::user()->id);
                }
                $comment['userLikedComment'] = $userLikedComment;
                return $comment;
            });

            return View('partials.comment_preview_list', [ 'comments' => $versions, 'context' => "versions", 'emptyMessage' => "There are no versions to show.", 'paginator' => $paginator ]);
        }
        else {
            return response()->json(['versions'=> $versions], 200);
        }
    }

    /**
     * Method that presents the page with all the versions of the specified comment.
     * /comments/{comment_id}/versions
     *
     * @return View Rendered view of the page
     */
    public function versions($id) {
        $comment = Comment::find($id);
        if(is_null($comment)){
            return abort(404, 'There is no comment with id: ' . $id);
        }

        $versions = $comment->versions();
        $versionsPaginator = CollectionHelper::paginate($versions, $versions->count(), 6);
        $versionsPaginator->withPath('/api/comments/' . $id . '/versions?ppp=6');

        $commentsData = Comment::commentsInfo($versionsPaginator);
        for($i = 0; $i < count($commentsData); $i++) {
            $comment = $commentsData[$i];
            $userLikedComment = null;
            if (Auth::check() && $comment['most_recent']) {
                $userLikedComment = Rating::likeValue($comment['id'], Auth::user()->id);
            }
            $comment['userLikedComment'] = $userLikedComment;
            $commentsData[$i] = $comment;
        }

        return view('pages.versions', [
            'type' => 'comment',
            'versions' => [
                'data' => $commentsData,
                'paginator' => $versionsPaginator
            ],
        ]);
    }
}