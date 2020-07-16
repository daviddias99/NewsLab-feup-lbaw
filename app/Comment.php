<?php

namespace App;

// use App\Content;
use Illuminate\Database\Eloquent\Model;

class Comment extends Content
{
    // Don't add create and update timestamps in database.
    public $timestamps  = false;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'comment';

    protected $primaryKey = 'content_id';

    /**
     * The content of this post
     */
    public function content() {
        return $this->belongsTo('App\Content', 'content_id');
    }

    /**
     * The post of this comment
     */
    public function post() {
        return $this->belongsTo('App\Post', 'post_id', 'content_id');        
    }

    /**
     * Most Recent Replies of this comment
     */
    public function replies() {
  
        return $this->hasMany('App\Reply', 'comment_id')->join('content', 'reply.content_id', '=', 'content.id')->where('most_recent', true)
            ->select('reply.*', 'content.body', 'content.likes_difference', 'content.author_id')->orderBy('publication_date', 'asc');
    }

    public function numberReplies(){
        return count($this->replies);
    }

    /**
     * Function that retrieves the most recent version of this comment (null if it is already the most recent version)
     */
    public function mostRecentVersion() {
        return $this->belongsToMany('App\Comment', 'comment_version', 'past_version_id', 'cur_version_id');
    }

    /**
     * Returns the versions of the comment.
     */
    public function versions() {
        if ($this->content->most_recent) {
            $versions = $this->belongsToMany('App\Comment', 'comment_version', 'cur_version_id', 'past_version_id')->get();
            $versions->push($this);
            return $versions->sortByDesc('modification_date')->values();
        }
        else {
            $recentVersion = $this->mostRecentVersion;
            return $recentVersion->isEmpty() ? null : $recentVersion[0]->versions();
        }
    }

    public static function commentsInfo($comments) {
        return $comments->map(function ($comment) {
            return $comment->commentInfo();
        });
    }

    public function commentInfo() {
        $comment_author = null;
        if ($this->content->author !== null) {
            $comment_author = [
                'id'=> $this->content->author->id,
                'name'=> $this->content->author->name,
                'photo' => $this->content->author->photo,
                'verified' => $this->content->author->verified,
            ];
        }

        return [
            'id' => $this->content_id,
            'author' => $comment_author,
            'body' => $this->content->body,
            'most_recent' => $this->content->most_recent,
            'publication_date' => $this->publication_date,
            'modification_date' => $this->modification_date,
            'edited' => (!$this->content->most_recent || $this->modification_date != null) ? true : false,
            'likes_difference' => $this->content->likes_difference,
        ];
    }

    public static function commentsPreviewInfo($comments) {
        return $comments->map(function ($comment) {
            return Comment::commentPreviewInfo($comment);
        });
    }

    public static function commentPreviewInfo($comment) {
        $comment_author = null;
        if ($comment->content->author !== null) {
            $comment_author = [
                'id'=> $comment->content->author->id
            ];
        }

        return [
            'id' => $comment->content_id,
            'author' => $comment_author,
            'body' => $comment->content->body,
            'most_recent' => $comment->content->most_recent,
            'publication_date' => $comment->publication_date,
            'edited' => (!$comment->content->most_recent || $comment->modification_date != null) ? true : false,
            'likes_difference' => $comment->content->likes_difference,
            'post' => [
                "title" => $comment->post->title,
                "id" => $comment->post->content_id
            ]
        ];
    }
}
