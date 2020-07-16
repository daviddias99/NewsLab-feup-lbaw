<?php

namespace App\Policies;

use App\User;
use App\Admin;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Support\Facades\Auth;

class UserPolicy {

    use HandlesAuthorization;
    
    /**
     * Determine whether the user can view the saved posts of a user.
     */
    public function viewSavedPosts(?User $user, User $model) {
        return (Auth::check() && (Auth::user()->id === $model->id));
    }

    /**
     * Determine whether the user can add a post post to the saved posts of a user.
     */
    public function addSavedPost(?User $user, User $model) {
        return (Auth::check() && (Auth::user()->id === $model->id));
    }

    /**
     * Determine whether the user can delete a saved post of a user.
     */
    public function deleteSavedPost(?User $user, User $model) {
        return (Auth::check() && (Auth::user()->id === $model->id));
    }

    /**
     * Determine whether the user can list the banned users.
     */
    public function listBanned(User $user){
        return Auth::check() && Admin::find($user->id);
    }

    /**
     * Determine whether the user can be banned.
     */
    public function ban(User $user){
        return Auth::check() && Admin::find($user->id);
    }

    /**
     * Determine whether the user view the subscriptions of a user.
     */
    public function viewSubs(?User $user, User $model) {
        return (Auth::check() && (Auth::user()->id === $model->id));
    }

    /**
     * Determine whether the user can delete a subcription of a certain user.
     */
    public function deleteSub(?User $user, User $model) {
        return (Auth::check() && (Auth::user()->id === $model->id));
    }

    /**
     * Determine whether the user can add a subscription to the subscriptions of a certain user.
     */
    public function addSub(?User $user, User $model) {
        return (Auth::check() && (Auth::user()->id === $model->id));
    }

    /**
     * Determine whether the user can check this profile statistics
     */
    public function checkStats(?User $user, User $model) {
        return (Auth::check() && (Auth::user()->id === $model->id));
    }

    /**
     * Determine whether the user can edit this profile
     */
    public function update(?User $user, User $model) {
        return (Auth::check() && (Auth::user()->id === $model->id));
    }
    
    /**
     * Determine whether the user can delete this profile
     */
    public function delete(?User $user, User $model) {
        return (Auth::check() && (Auth::user()->id === $model->id));
    }

    /**
     * Determine whether the user can report this profile
     */
    public function report(?User $user, User $model) {
        return Auth::check() && (Auth::user()->id !== $model->id);
    }
}
