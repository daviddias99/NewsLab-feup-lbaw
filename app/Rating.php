<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Rating extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'rating';

    public $timestamps = false;

    protected $primaryKey = ['user_id', 'content_id'];
    public $incrementing = false;

    /**
     * the content of the rating
     */
    public function content()
    {
        return $this->belongsTo('App\Content');
    }

    /**
     * the user who rated
     */
    public function user()
    {
        return $this->belongsTo('App\User');
    }

    /**
     * Used to check if a user has liked/disliked a certain content
     */
    public static function likeValue($content_id, $user_id) {
        $rating = Rating::where('content_id', $content_id)->where('user_id', $user_id)->first();
        return $rating == null ? null : $rating->like;
    }
}