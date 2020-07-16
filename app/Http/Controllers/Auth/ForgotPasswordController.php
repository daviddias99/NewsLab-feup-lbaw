<?php

namespace App\Http\Controllers\Auth;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\SendsPasswordResetEmails;
use Illuminate\Contracts\Auth\PasswordBroker;
class ForgotPasswordController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Password Reset Controller
    |--------------------------------------------------------------------------
    |
    | This controller is responsible for handling password reset emails and
    | includes a trait which assists in sending these notifications from
    | your application to your users. Feel free to explore this trait.
    |
    */
    use SendsPasswordResetEmails;
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest');
    }

    public function getEmail(Request $request, PasswordBroker $passwords)
    {
        $this->validate($request, ['email' => 'required|email']);

        $response = $passwords->sendResetLink($request->only('email'), function($m)
        {
            $m->subject($this->getEmailSubject());
        });

        switch ($response)
        {
            case PasswordBroker::RESET_LINK_SENT:
                return[
                    'error'=>'false',
                    'msg'=>'A password link has been sent to your email address'
                ];

            case PasswordBroker::INVALID_USER:
                return[
                    'error'=>'true',
                    'msg'=>"We can't find a user with that email address"
                ];
        }
    }
}