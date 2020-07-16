<?php

namespace App\Policies;

use App\User;
use App\Tag;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Support\Facades\Auth;

class TagPolicy {
    use HandlesAuthorization;
    
    public function report(?User $user, Tag $tag) {
      return Auth::check() && !$tag->isOfficial();
    }

    public function delete(?User $user, Tag $tag) {
      // Only the admin and the owner can delete one comment
      return Auth::check() && $user->isAdmin() && !$tag->isOfficial();
    }
}
