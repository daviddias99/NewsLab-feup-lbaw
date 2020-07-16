<?php

namespace App;

use App\Utils\CollectionHelper;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Carbon\Carbon;

class Post extends Content
{
    // Don't add create and update timestamps in database.

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'post';

    protected $primaryKey = 'content_id';

    /**
     * The content of this post
     */
    public function content() {
        return $this->belongsTo('App\Content', 'content_id');
    }

    /**
     * The tags of this post
     */
    public function tags() {
        return $this->belongsToMany('App\Tag', 'post_tag', 'post_id', 'tag_id');
    }

    /**
     * The direct most recent comments of this post
     */
    public function comments($order = null) {
        if(strcmp($order, 'recent') == 0)
            return $this->hasMany('App\Comment', 'post_id')->join('content', 'comment.content_id', '=', 'content.id')->where('most_recent', true)->orderBy('publication_date', 'desc');
        if(strcmp($order, 'old') == 0)
            return $this->hasMany('App\Comment', 'post_id')->join('content', 'comment.content_id', '=', 'content.id')->where('most_recent', true)->orderBy('publication_date', 'asc');
        return $this->hasMany('App\Comment', 'post_id')->join('content', 'comment.content_id', '=', 'content.id')->where('most_recent', true)->orderBy('content.likes_difference', 'desc')->orderBy('publication_date', 'desc');
    }

    /**
     * Function that retrieves the most recent version of this post (null if it is already the most recent version)
     */
    public function mostRecentVersion() {
        return $this->belongsToMany('App\Post', 'post_version', 'past_version_id', 'cur_version_id');
    }

    /**
     * Returns the versions of the post.
     */
    public function versions() {
        if ($this->content->most_recent) {
            $versions = $this->belongsToMany('App\Post', 'post_version', 'cur_version_id', 'past_version_id')->get();
            $versions->push($this);
            return $versions->sortByDesc('modification_date')->values();
        }
        else {
            $recentVersion = $this->mostRecentVersion;
            return $recentVersion->isEmpty() ? null : $recentVersion[0]->versions();
        }
    }

    public function numberCommentsAndReplies(){
        $comments = $this->comments;
        $nTotal = count($comments);
        foreach($comments as $comment)
            $nTotal += $comment->numberReplies();
        return $nTotal;
    }

    public static function postsInfo($posts) {
        return $posts->map(function ($post) {
            return Post::postInfo($post);
        });
    }

    public static function postInfo($post)
    {
        $post_author = null;
        // if post has no author (account deleted)
        if ($post->content->author !== null) {
            $post_author = [
                'id' => $post->content->author->id,
                'name' => $post->content->author->name
            ];
        }

        return [
            'id' => $post->content_id,
            'title' => $post->title,
            'photo' => $post->photo,
            'body' => $post->content->body,
            'likes_difference' => $post->content->likes_difference,
            'edited' => (!$post->content->most_recent || !is_null($post->modification_date)) ? true : false,
            'publication_date' => $post->publication_date,
            'modification_date' => $post->modification_date,
            'author' => $post_author,
            'visible' => $post->getAttribute('visible'),
            'tags' => $post->tags->map(function ($tag) {
                return ['name' => $tag->name, 'id' => $tag->id, 'color' => $tag->color,];
            }),
        ];
    }

    public static function extendedPostsInfo($posts) {
        return $posts->map(function ($post) {
            return Post::extendedPostInfo($post);
        });
    }

    public static function getRelatedPosts($title, $id) {
        return DB::select(
            "SELECT p.photo as photo, p.title as title, p.publication_date as pub_date, c.most_recent as most_recent, c.id as id, c.likes_difference as likes_diff, u.id as author_id, u.name as name
            FROM \"content\" c inner join post p on c.id = p.content_id left join \"user\" u on c.author_id = u.id, ts_rank_cd(p.search, plainto_tsquery('simple', ?)) as rank
            WHERE p.visible = TRUE AND c.most_recent = TRUE AND p.content_id != ?
            ORDER BY rank DESC LIMIT 3",
            [$title, $id]
        );
    }

    public static function getRelatedTags($title) {
        return DB::select(
            "SELECT t.id as id, t.name as name, t.color as color
            FROM tag t, ts_rank_cd(t.search, plainto_tsquery('simple', ?)) as rank
            ORDER BY rank DESC LIMIT 7",
            [$title]
        );
    }

    public static function extendedPostInfo($post)
    {
        $post_author = null;
        // if post has no author (account deleted)
        if ($post->content->author !== null) {
            $post_author = [
                'id' => $post->content->author->id,
                'name' => $post->content->author->name,
                'photo' => $post->content->author->photo
            ];
        }

        return [
            'id' => $post->content_id,
            'title' => $post->title,
            'photo' => $post->photo,
            'body' => $post->content->body,
            'likes_difference' => $post->content->likes_difference,
            'edited' => (!$post->content->most_recent || !is_null($post->modification_date)) ? true : false,
            'publication_date' => $post->publication_date,
            'modification_date' => $post->modification_date,
            'num_comments' => $post->numberCommentsAndReplies(),
            'author' => $post_author,
            'visible' => $post->getAttribute('visible'),
            'tags' => $post->tags->map(function ($tag) {
                return ['name' => $tag->name, 'id' => $tag->id, 'color' => $tag->color,];
            }),
        ];
    }

    public static function searchByType(Request $request, $type, $limit, $returnArray, $postBlacklist = null, $pagLink = null)
    {
        $query = Post::query()->select('content.*', 'post.*')->distinct();

        $authorList = $request->author != null ? explode(",", $request->author) : null;
        if (Auth::check() && $authorList != null && in_array("subscribed", $authorList)) {
            $query->fromRaw("content inner join post on content.id = post.content_id
                        inner join post_tag on post.content_id = post_tag.post_id
                        inner join tag on post_tag.tag_id = tag.id
                        inner join \"user\" u on content.author_id = u.id
                        left join user_subscription on content.author_id = user_subscription.subscribed_user_id,
                        ts_rank_cd(setweight(to_tsvector('simple', post.title), 'A') || ' ' ||
                                setweight(to_tsvector('simple', content.body), 'B') || ' ' ||
                                setweight(to_tsvector('simple', u.name), 'C') || ' ' ||
                                setweight(to_tsvector('simple', tag.name), 'D'), plainto_tsquery('simple', ?)) as rank ", [$request->search]);
        } else {
            $query->fromRaw("content inner join post on content.id = post.content_id
                            inner join post_tag on post.content_id = post_tag.post_id
                            inner join tag on post_tag.tag_id = tag.id
                            inner join \"user\" u on content.author_id = u.id,
                            ts_rank_cd(setweight(to_tsvector('simple', post.title), 'A') || ' ' ||
                                    setweight(to_tsvector('simple', content.body), 'B') || ' ' ||
                                    setweight(to_tsvector('simple', u.name), 'C') || ' ' ||
                                    setweight(to_tsvector('simple', tag.name), 'D'), plainto_tsquery('simple', ?)) as rank ", [$request->search]);
        }

        $query = $query->visible()
                       ->mostRecent()
                       ->type($type)
                       ->blacklist($postBlacklist)
                       ->tags($request->tags)
                       ->author($request->author);

        // Text
        if (!empty($request->search)) {
            $query = $query->whereRaw("(post.title || ' ' || content.body || ' ' || u.name || ' ' || tag.name) @@ plainto_tsquery('english', ?)", [$request->search]);
        }

        $query = $query->after($request->begin)
                       ->before($request->end)
                       ->minLikes($request->min)
                       ->maxLikes($request->max)
                       ->order($request->sortBy);

        if ($limit != null) {
            $query = $query->limit($limit);
        }

        $returnContent = null;
        if (!(isset($_GET['ppp']) && isset($_GET['page']))) {
            $posts = $query->get();
            $returnContent = [
                'data' =>  $posts->map(function ($r) {
                    return Post::extendedPostInfo($r);
                }),
                'paginator' => null
            ];
        } else {
            $postsPerPage = $_GET['ppp'];
            $queryCollection = $query->get();
            $paginator = CollectionHelper::paginate($queryCollection, $queryCollection->count(), $postsPerPage);

            if ($pagLink == null) {
                $queryString = str_replace(request()->url(), '', request()->fullUrl());

                $query = request()->query();
                $queryStr = "?";

                foreach ($query as $param => $value) {

                    if ($param == 'tags') {
                        $queryString = $queryString . ($queryStr == "?" ? "" : "&") . "tags=" . json_encode($request->tags);
                    } else if ($param != 'page') {
                        $queryStr =  $queryStr . ($queryStr == "?" ? "" : "&") .  $param . "=" . $value;
                    }
                }

                if (strpos($queryStr, "type") == false) {
                    $queryStr = $queryStr . ($queryStr == "?" ? "" : "&") . 'type=' . $type;
                }

                if (strpos($queryStr, "ppp") == false) {
                    $queryStr = $queryStr . ($queryStr == "?" ? "" : "&") . 'ppp=' . $postsPerPage;
                }

                $paginator->withPath('/api/search/posts' . $queryStr);
            } else {
                $paginator->withPath($pagLink);
            }

            $returnContent = [
                'data' => Post::extendedPostsInfo($paginator),
                'paginator' => $paginator,
            ];
        }

        if ($returnArray)
            return $returnContent;
        else
            return  response()->json($returnContent, 200);
    }

    public function scopeVisible($query)
    {
        return $query->where('visible', true);
    }

    public function scopeMostRecent($query)
    {
        return $query->where('most_recent', true);
    }

    public function scopeType($query, $type)
    {
        if ($type != null) {
            $query = $query->where('post.type', $type);
        }
    }

    public function scopeBlacklist($query, $postBlacklist)
    {
        if ($postBlacklist != null) {
            $query = $query->where(function ($q) use ($postBlacklist) {
                foreach ($postBlacklist as $postId) {
                    $q->where('post.content_id', "!=", $postId);
                }
            });
        }
    }

    public function scopeTags($query, $tags) {
        if ($tags != null) {
            $tagList = explode(",", $tags);
            $query = $query->where(function ($q) use ($tagList) {
                foreach ($tagList as $tag) {
                    $q->orWhere('tag.id', $tag);
                }
            });
        }
    }

    public function scopeAuthor($query, $authorList) {
        if ($authorList != null) {
            $authorList = explode(",", $authorList);
            $query = $query->where(function ($q) use ($authorList) {
                foreach ($authorList as $author) {
                    if (strcmp($author, "verified") == 0)
                        $q->orWhere('u.verified', true);
                    else if (strcmp($author, "subscribed") == 0 && Auth::check())
                        $q->orWhere('user_subscription.subscribing_user_id', Auth::user()->id);
                }
            });
        }
    }

    public function scopeAfter($query, $date) {
        if ($date != null)
            $query->whereDate('post.publication_date', '>=', Carbon::parse($date)->toDateString());
    }

    public function scopeBefore($query, $date) {
        if ($date != null)
            $query->whereDate('post.publication_date', '<=', Carbon::parse($date)->toDateString());
    }

    public function scopeMinLikes($query, $likes) {
        if ($likes != null)
            $query->where("content.likes_difference", '>=', $likes);
    }

    public function scopeMaxLikes($query, $likes) {
        if ($likes != null)
            $query->where("content.likes_difference", '<=', $likes);
    }

    public function scopeOrder($query, $order) {
        if ($order != null) {
            if (strcmp($order, "alpha") == 0)
                $query->orderBy('post.title', 'asc');
            else if (strcmp($order, "numerical") == 0)
                $query->orderBy('content.likes_difference', 'desc');
            else if (strcmp($order, "recent") == 0)
                $query->latest('post.publication_date');
        } else {
            $query->latest('post.publication_date');
        }
    }
}
