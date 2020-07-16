<?php

namespace App;

// use App\Content;
use Illuminate\Database\Eloquent\Model;

class Reply extends Content
{
    // Don't add create and update timestamps in database.
    public $timestamps  = false;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'reply';

    protected $primaryKey = 'content_id';

    /**
     * The content of this Reply
     */
    public function content() {
        return $this->belongsTo('App\Content', 'content_id');
    }

    /**
     * The comment of this reply
     */
    public function comment() {
        return $this->belongsTo('App\Comment', 'comment_id', 'content_id');        
    }

        /**
     * Function that retrieves the most recent version of this reply (null if it is already the most recent version)
     */
    public function mostRecentVersion() {
        return $this->belongsToMany('App\Reply', 'reply_version', 'past_version_id', 'cur_version_id');
    }

    /**
     * Returns the versions of the reply.
     */
    public function versions() {
        if ($this->content->most_recent) {
            $versions = $this->belongsToMany('App\Reply', 'reply_version', 'cur_version_id', 'past_version_id')->get();
            $versions->push($this);
            return $versions->sortByDesc('modification_date')->values();
        }
        else {
            $recentVersion = $this->mostRecentVersion;
            return $recentVersion->isEmpty() ? null : $recentVersion[0]->versions();
        }
    }

    public static function repliesInfo($replies) {
        return $replies->map(function ($reply) {
            return $reply->replyInfo();
        });
    }

    public function replyInfo() {
        $reply_author = null;
        if ($this->content->author !== null) {
            $reply_author = [
                'id'=> $this->content->author->id,
                'name'=> $this->content->author->name,
                'photo' => $this->content->author->photo,
                'verified' => $this->content->author->verified,
            ];
        }

        return [
            'id' => $this->content_id,
            'author' => $reply_author,
            'body' => $this->content->body,
            'most_recent' => $this->content->most_recent,
            'publication_date' => $this->publication_date,
            'modification_date' => $this->modification_date,
            'edited' => (!$this->content->most_recent || $this->modification_date != null) ? true : false,
            'likes_difference' => $this->content->likes_difference,
        ];
    }

    public static function repliesPreviewInfo($replies) {
        return $replies->map(function ($reply) {
            return Reply::replyPreviewInfo($reply);
        });
    }

    public static function replyPreviewInfo($reply) {
        return [
            'id' => $reply->content_id,
            'body' => $reply->content->body,
            'most_recent' => $reply->content->most_recent,
            'publication_date' => $reply->publication_date,
            'edited' => (!$reply->content->most_recent || $reply->modification_date != null) ? true : false,
            'likes_difference' => $reply->content->likes_difference,
            'post' => [
                "title" => $reply->post->title,
                "id" => $reply->post->content_id
            ]
        ];
    }
}