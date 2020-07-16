<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Content extends Model
{
    // Don't add create and update timestamps in database.
    public $timestamps  = false;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'content';

  /**
   * The author of this content belongs to.
   */
    public function author() {
        return $this->belongsTo('App\User', 'author_id');
    }

    public function getLikes(){

        return $this->hasMany('App\Rating',"content_id","id")->where('like',true)->count();
    }

    public function getDislikes(){
        return $this->hasMany('App\Rating',"content_id","id")->where('like',false)->count();
    }

}