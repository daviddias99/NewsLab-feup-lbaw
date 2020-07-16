<?php

namespace App;

use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Admin extends User
{

    // Don't add create and update timestamps in database.
    public $timestamps  = false;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'admin';
    protected $primaryKey = 'user_id';
    
    /**
     * The user info of the admin
     */
    public function user() {
        return $this->hasOne('App\User','id','user_id');
    }

    public function get_stats(){

        return [
            'posts_deleted' => $this->posts_deleted ,
            'comments_deleted' => $this->comments_deleted,
            'reports_solved' => $this->reports_solved,
            'users_banned' => $this->users_banned
        ];
    }

    public function get_info(){
        if(is_null($this->user->city)){
            $local = null;
        }
        else {
            $local = [
                'city' => $this->user->city->name,
                'country' => $this->user->city->country->name
            ];
        }

        return [
            'id' =>$this->user_id,
            'name' => $this->user->name,
            'email' => $this->user->email,
            'birthday' => $this->user->birthday,
            'local' => $local,
            'photo' => $this->user->photo,
        ];
    }
    
    public static function list_info($admins){

        $result = [];

        foreach ($admins as $admin) {
            array_push($result,[
                'info' => $admin->get_info(),
                'stats' => $admin->get_stats()]);
        }
    
        return $result;
    }

}
