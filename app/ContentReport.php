<?php

namespace App;

use App\Content;
use App\Http\Controllers\PostController;

class ContentReport extends Report
{
    public $timestamps  = false;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'content_report';
    protected $primaryKey = 'report_id';

    public function getContent()
    {

        return $this->hasOne('App\Content', 'id', 'content_id');

    }

    public function getType(){

        if(Post::find($this->content_id))
            return 'post';
        
        if (Comment::find($this->content_id))
            return 'comment' ;
        
        if (Reply::find($this->content_id))
            return 'reply' ;

    }

    public function item()
    {
        $content = $this->getContent;
        $post = Post::find($content->id);

        if($post){

            $pc = new PostController();
            $likes_arr = [];
            $likes_arr['likes'] = $content->getLikes();
            $likes_arr['dislikes'] = $content->getDislikes();
            $result = json_decode($pc->getPost($content->id)->getContent(),$assoc=true);
            unset($result['likes_difference']);
            unset($result['body']);
            $result['rating'] = $likes_arr;
            return $result;
        }

        $comment = Comment::find($content->id);

        if ($comment){
            $likes_arr = [];
            $likes_arr['likes'] = $content->getLikes();
            $likes_arr['dislikes'] = $content->getDislikes();
            $result = $comment->commentInfo();
            unset($result['likes_difference']);
            unset($result['replies']);
            $result['rating'] = $likes_arr;
            return $result;
        }
        
        $reply = Reply::find($content->id);

        if ($reply){
            $likes_arr = [];
            $likes_arr['likes'] = $content->getLikes();
            $likes_arr['dislikes'] = $content->getDislikes();
            $result = $reply->replyInfo();
            unset($result['likes_difference']);
            $result['rating'] = $likes_arr;
            return $result;
        }

        return $content;
    }

    public static function search($reporterID, $reportedID){
        $query = ContentReport::query('content_report.id')
                        ->join('report', 'content_report.report_id', '=', 'report.id')
                        ->where('report.reporter_id', $reporterID)
                        ->where('report.closed', false)
                        ->where('content_report.content_id', $reportedID);
        return $query->exists();
    }
}
