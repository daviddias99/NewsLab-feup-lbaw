<?php

namespace App;


use Illuminate\Support\Facades\DB;
use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use Notifiable;
    // Don't add create and update timestamps in database.
    public $timestamps  = false;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'user';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'email', 'password', 'birthday',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token', ''
    ];

    /**
     * The city of the user
     */
    public function city() {
        return $this->belongsTo('App\City', 'location_id', 'id');
    }

    public function isAdmin() {
        return Admin::find($this->id) != null ? true : false;
    }

    public function getNumSubscribers() {
        return $this->belongsToMany('App\User', 'user_subscription', 'subscribed_user_id', 'subscribing_user_id')->count();
    }

    public function subscribed($user) {
        $subscribed = DB::table('user_subscription')
                        ->where('subscribing_user_id', $user->id)
                        ->where('subscribed_user_id', $this->id)
                        ->exists();

        if (empty($subscribed))
            return false;

        return $subscribed;
    }

    public function getTags() {
        $posts = $this->posts();

        $tagged_posts = $posts->join('post_tag', 'content_id', '=', 'post_id')
                              ->join('tag', 'tag_id', '=', 'tag.id')
                              ->select('tag.id as id', 'tag.name as name', DB::raw('count(content_id) as frequency'))
                              ->groupBy('tag.id', 'tag.name', 'author_id')
                              ->orderBy('frequency', 'desc');
        return $tagged_posts->get();
    }

    public function getSubsCountry() {
        $subscribers = $this->where('user.id', $this->id)
                            ->join('user_subscription', 'user.id', '=', 'subscribed_user_id')
                            ->join('user as other_user', 'subscribing_user_id', '=', 'other_user.id')
                            ->join('location', 'other_user.location_id', '=', 'location.id')
                            ->join('country', 'country_id', '=', 'country.id')
                            ->select('country.name', DB::raw('count(*) as frequency'))
                            ->groupBy('country.name')
                            ->orderBy('frequency', 'desc')->get();

        return $subscribers;
    }

    public function getNumSubsInRange($min, $max) {

        $num = $this->where('user.id', $this->id)
                    ->join('user_subscription', 'user.id', '=', 'subscribed_user_id')
                    ->join('user as other_user', 'subscribing_user_id', '=', 'other_user.id')
                    ->select(DB::raw('count(distinct subscribing_user_id)'));

        if (is_null($max)) {
            $num = $num->whereRaw("date_part('year', age(now(), other_user.birthday)) >= ?", [$min]);
        } else {
            $num = $num->whereRaw("date_part('year', age(now(), other_user.birthday)) >= ? AND date_part('year', age(now(), other_user.birthday)) <= ?", [$min, $max]);
        }

        return $num->get()[0]->count;
    }

    public function getSubsAge() {
        return [
            '13-20' => $this->getNumSubsInRange(13, 20),
            '21-30' => $this->getNumSubsInRange(21, 30),
            '31-40' => $this->getNumSubsInRange(31, 40),
            '41-50' => $this->getNumSubsInRange(41, 50),
            '51-60' => $this->getNumSubsInRange(51, 60),
            '60+' => $this->getNumSubsInRange(61, null)
        ];
    }

    public function mostLikedPost() {
        $maxLikes = $this->posts()->max('likes_difference');

        $post = $this->posts()->where('likes_difference', $maxLikes)->orderBy('publication_date', 'desc')->first();

        return $post;///$post->get();
    }

    public function posts($order = null) {
        if(strcmp($order, 'recent') == 0)
            return $this->hasManyThrough(
                'App\Post',
                'App\Content',
                'author_id',
                'content_id',
                'id',
                'id'
            )->where('most_recent', true)->orderBy('publication_date', 'desc');
        return $this->hasManyThrough(
            'App\Post',
            'App\Content',
            'author_id',
            'content_id',
            'id',
            'id'
        )->where('most_recent', true);
    }

    public function comments() {
        return $this->hasManyThrough(
            'App\Comment',
            'App\Content',
            'author_id',
            'content_id',
            'id',
            'id'
        )->where('most_recent', true)->distinct()->orderBy('publication_date', 'desc');
    }

    public function likedPosts() {
        return $this->belongsToMany('App\Post', 'rating', 'user_id', 'content_id')->join('content', 'post.content_id', '=', 'content.id')
        ->where('most_recent', true)->wherePivot('like', true)->visible()->orderBy('post.publication_date', 'desc');
    }

    public function numPosts() {
        return $this->posts()->count();
    }

    public function numComments() {
        return $this->comments()->count();
    }

    public function postsTotalLikesDiff() {
        $likes = $this->posts()->sum('likes_difference');
        return $likes;
    }

    public function commentsTotalLikesDiff() {
        $likes = $this->comments()->sum('likes_difference');
        return $likes;
    }

    public function badges() {
        return $this->belongsToMany('App\Badge', 'has_badge', 'user_id', 'badge_id')->select('icon', 'name', 'description')->orderBy('name');
    }

    public function savedPosts($order = null) {
        $query = $this->belongsToMany('App\Post', 'saved_post', 'user_id', 'post_id')
                   ->join('content', 'post.content_id', '=', 'content.id')
                   ->visible()->mostRecent();
        if(strcmp($order, 'recent') == 0)
            return $query->orderBy('publication_date', 'desc');
        if(strcmp($order, 'numerical') == 0)
            return $query->orderBy('likes_difference', 'desc');
        return $query->orderBy('post.title', 'asc');
    }

    public function subbedTags() {
        return $this->belongsToMany('App\Tag', 'tag_subscription', 'user_id', 'tag_id')->orderBy('tag.name', 'asc');
    }

    public function subbedUsers() {
        return $this->belongsToMany('App\User', 'user_subscription', 'subscribing_user_id', 'subscribed_user_id')->distinct()->orderBy('user.name', 'asc');
    }

    public function getBadgesInfo() {
        $badges = $this->badges()->get();

        $userBadges = [];
        foreach ($badges as $badge) {
            $userBadges[intval($badge->pivot->badge_id)] = $badge->name;
        }

        return $userBadges;
    }

    public function getUserInfo() {

        if (is_null($this->city)) {
            $local = null;
        } else {
            $local = [
                'city' => $this->city->name,
                'country' => $this->city->country->name
            ];
        }

        return [
            'id' => $this->id,
            'name' => $this->name,
            'verified' => $this->verified,
            'email' => $this->email,
            'birthday' => $this->birthday,
            'local' => $local,
            'bio' => $this->bio,
            'num_subscribers' => $this->getNumSubscribers(),
            'photo' => $this->photo,
            'is_banned' => $this->banned,
            'is_admin' => $this->isAdmin()
        ];
    }

    public static function getBannedUsersInfo($users){
        return $users->map(function ($user) {
            return $user->getBanInfo();
        });
    }

    public function getBanInfo(){
        $newUser = [];
        $newUser['banned_user_id'] = $this->id;
        $newUser['banned_user_name'] = $this->name;
        $ban = Ban::where('user_id',$this->id)->first();

        $newUser['admin_user_id'] = $ban->admin_id;

        $admin = User::find($ban->admin_id);

        if($admin){
            $newUser['admin_user_name'] = $admin->name;
        }
        else{
            $newUser['admin_user_name'] = null;
        }

        $newUser['start_date'] = $ban['ban_start'];
        $newUser['end_date'] = $ban['ban_end'];

        return $newUser;
    }

    public static function getUsersShortInfo($users) {
        return $users->map(function ($user) {
            return [
                'id' => $user->id,
                'name' => $user->name,
                'verified' => $user->verified,
                'email' => $user->email,
                'photo' => $user->photo,
            ];
        });
    }

    /**
     * Helper function with FTS search
     */
    public static function searchQuery($search){
        $query = User::query()
                    ->select('user.*')->distinct()
                    ->selectRaw("ts_rank_cd(to_tsvector(\"user\".name), plainto_tsquery('simple', ?)) as rank", [$search])
                    ->join(
                        DB::raw('(select u.id as uid, count(us.subscribed_user_id) as nsubs
                                from "user" as u left join user_subscription as us on u.id = us.subscribed_user_id
                                group by uid ) as subs'), 'user.id', '=', 'subs.uid');

        // Text
        if(!empty($search)){
            $query = $query->whereRaw("(\"user\".name) @@ plainto_tsquery('english', ?)", [$search])
                        ->orderBy("rank", "DESC");
        }

        return $query;
    }
}
