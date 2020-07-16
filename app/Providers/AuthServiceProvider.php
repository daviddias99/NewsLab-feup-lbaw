<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array
     */
    protected $policies = [
      'App\Card' => 'App\Policies\CardPolicy',
      'App\Item' => 'App\Policies\ItemPolicy',
      'App\Content' => 'App\Policies\ContentPolicy',
      'App\Post' => 'App\Policies\PostPolicy',
      'App\User'=> 'App\Policies\UserPolicy'
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();
    }
}


// return ($post->visible || ($user->id === $post->content->author_id));
// (Auth::check() && !$user->banned);
// ($user->id === $post->content->author_id || $user->isAdmin());