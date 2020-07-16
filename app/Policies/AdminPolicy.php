<?php

namespace App\Policies;

use App\User;
use App\Admin;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Support\Facades\Auth;

class AdminPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view the admin center page.
     * @return mixed
     */
    public function view(?User $user, Admin $admin)
    {
        return Auth::check() && $admin->user_id === $user->id;
    }

    public function list(User $user)
    {
        return Auth::check() && Admin::find($user->id);
    }

    public function create(User $user){

        return Auth::check() && Admin::find($user->id);
    }

    public function delete(User $user, $admin_id){

        return Auth::check() && Admin::find($user->id) && $admin_id != $user->id;
    }

}
