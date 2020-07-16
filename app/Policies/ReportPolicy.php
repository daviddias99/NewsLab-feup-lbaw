<?php

namespace App\Policies;

use App\User;
use App\Admin;
use App\Report;
use App\UserReport;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Support\Facades\Auth;

class ReportPolicy
{
    use HandlesAuthorization;


    public function list(User $user)
    {
        return Auth::check() && Admin::find($user->id);
    }

    public function close(User $user, Report $report)
    {
        if($report->type() == 'user'){
            
            $reported_user = (UserReport::find($report->id))->getUser;
            return Auth::check() && Admin::find($user->id) && ($report->reporter_id != $user->id) && ($user->id != $reported_user->id);
        }

        return Auth::check() && Admin::find($user->id) && ($report->reporter_id != $user->id);
    }
}
