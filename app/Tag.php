<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class Tag extends Model
{
    // Don't add create and update timestamps in database.
    public $timestamps  = false;

    protected $table = 'tag';
    protected $fillable = ['name'];


    public static function scopeOfficial($query)
    {
        return $query->whereNotNull('photo');
    }

    public function isOfficial()
    {
        return $this->photo != null;
    }

    /**
     * The posts that have this tag.
     */
    public function posts() {
        return $this->belongsToMany('App\Post', 'post_tag', 'tag_id', 'post_id');
    }

    public function newsPosts() {
        return $this->belongsToMany('App\Post', 'post_tag', 'tag_id', 'post_id')
            ->join('content', 'post.content_id', '=', 'content.id')
            ->mostRecent()->visible()->where('type', 'News');
    }

    public function opinionPosts() {
        return $this->belongsToMany('App\Post', 'post_tag', 'tag_id', 'post_id')
            ->join('content', 'post.content_id', '=', 'content.id')
            ->where('most_recent', true)->visible()->mostRecent();
    }

    public function getNumSubscribers() {
        return Tag::belongsToMany('App\User', 'tag_subscription', 'tag_id', 'user_id')->count();
    }

    public function getTagInfo() {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'photo' => $this->photo,
            'color' => $this->color,
            'num_subscribers' => $this->getNumSubscribers(),
        ];
    }

    public function subscribed($user){
        $subscribed = DB::table('tag_subscription')
                        ->where('user_id', $user->id)
                        ->where('tag_id', $this->id)
                        ->exists();

        if (empty($subscribed))
            return false;

        return $subscribed;
    }

    public static function search(Request $request, $limit){
        $query = Tag::query()
                    ->select('tag.*', 'subs.nsubs')->distinct();
        if(!empty($request->search))
            $query->selectRaw("ts_rank_cd(to_tsvector(tag.name), plainto_tsquery('simple', ?)) as rank", [$request->search]);

        $query->join(
                    DB::raw('(select t.id as tid, count(ts.user_id) as nsubs
                            from tag as t left join tag_subscription as ts on t.id = ts.tag_id
                            group by tid) as subs'), 'tag.id', '=', 'subs.tid')
              ->leftjoin('tag_subscription as ts', 'tag.id', '=', 'ts.tag_id');

        // Text
        if(!empty($request->search)){
            $query = $query->whereRaw("(tag.name) @@ plainto_tsquery('english', ?)", [$request->search])
                        ->orderBy("rank", "desc");
        }

        if($request->author != null){
            $authorList = explode(",", $request->author);
            $query = $query->where(function ($q) use($authorList){
                foreach($authorList as $author){
                    if(strcmp($author, "verified") == 0)
                        $q->official();
                    else if(strcmp($author, "subscribed") == 0 && Auth::check()) {
                        $q->orWhere('ts.user_id', Auth::user()->id);
                    }
                }
            });
        }

        if($request->min != null)
            $query->where("subs.nsubs", '>=', $request->min);
        if($request->max != null)
            $query->where("subs.nsubs", '<=', $request->max);

        // Order
        if($request->sortBy != null){
            if(strcmp($request->sortBy, "alpha") == 0)
                $query->orderBy('tag.name', 'asc');
            else if(strcmp($request->sortBy, "numerical") == 0)
                $query->orderBy('subs.nsubs', 'desc');
        }
        else{
            $query->orderBy('tag.name', 'asc');
        }

        if ($limit != null) {
            $query = $query->limit($limit);
        }

        $result = $query->get();

        return $result->map(function ($r) {
            return $r->getTagInfo();
        });
    }

    public static function getTagsInfo($tags){
        return $tags->map(function ($tag) {
            return [
                'id' => $tag->id,
                'name' => $tag->name,
                'color' => $tag->color,
                'frequency' => $tag->frequency
            ];
        });
    }
}
