<?php

namespace App\Policies;

use App\User;
use App\Content;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Support\Facades\Auth;

class ContentPolicy {
    use HandlesAuthorization;

    /**
     * Determine whether the user can update the content.
     *
     * @param  \App\User  $user
     * @param  \App\Content  $content
     * @return mixed
     */
    public function update(?User $user, Content $content) {
        return Auth::check() && ($user->id === $content->author_id && !$user->banned);
    }

    public function rate(?User $user, Content $content) {
        return Auth::check() && ($user->id !== $content->author_id && !$user->banned);
    }
    
    public function report(?User $user, Content $content) {
      return Auth::check() && ($user->id !== $content->author_id && !$user->banned);
    }

    public function create(?User $user) {
      // Any not banned user can create content
      return Auth::check() && !$user->banned;
    }

    public function delete(?User $user, Content $content) {
      // Only the admin and the owner can delete one comment
      return Auth::check() && ($user->isAdmin() || $user->id === $content->author_id) && $content->most_recent;
    }
}
