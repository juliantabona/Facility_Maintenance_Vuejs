<?php

namespace App\Http\Controllers\Auth;

use App\User;
use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\SendsPasswordResetEmails;

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


    public function customSendForgotPasswordEmail()
    {
        //  User Instance
        $data = ( new User() )->initiateSendForgotPasswordEmail();
        $success = $data['success'];
        $response = $data['response'];

        //  If the email was sent successfully
        if ($success) {
            //  If this is a success then we have sucess data returned
            $user = $response;

            //  Action was executed successfully
            return oq_api_notify($user, 200);
        }

        //  If the data was not a success then return the response
        return $response;
    }

    public function activate(Request $request)
    {
        //  Check if the mail token exists
        if (empty(request('token'))) {
            //  API Response
            if (oq_viaAPI($request)) {
                return oq_api_notify([
                    'message' => 'Activation token not provided',
                ], 422);
            } else {
                //  Notify the user that the token does not exist
                oq_notify('Activation token not provided');

                //  Go to login page
                return redirect()->route('login');
            }
        }

        //  Get the mail token
        $mailToken = request('token');

        $token = VerifyUser::where('token', $mailToken)->first();

        //  Check if the token exists
        if (count($token)) {
            //  Check if user exists
            $userExists = count($token->user);

            if (!$userExists) {
                //  API Response
                if (oq_viaAPI($request)) {
                    return oq_api_notify([
                        'message' => 'Token does not match any account. Request a new token.',
                    ], 422);
                } else {
                    //  Notify the user that the account associated to the token does not exist
                    oq_notify('Token does not match any account. Request a new token');

                    //  Go to login page
                    return redirect()->route('login');
                }
            }

            //  Get the associated user
            $user = $token->user;

            //  If the user hasn't been verified
            if (!$user->verified) {
                //  Verify the user account
                $user->verified = 1;
                $user->save();

                //  API Response
                if (oq_viaAPI($request)) {
                    return oq_api_notify([
                        'message' => 'Account verified. You can now login',
                        'user' => $user,
                    ], 200);
                } else {
                    //  Notify the user that their account is verified
                    oq_notify('Account verified. You can now login', 'success');

                    //  Go to login page
                    return redirect()->route('login');
                }
            } else {
                //  API Response
                if (oq_viaAPI($request)) {
                    return oq_api_notify([
                        'message' => 'Account already verified. You can login.',
                        'user' => $user,
                    ], 200);
                } else {
                    //  Notify the user that their account is already verified
                    oq_notify('Account already verified. You can login.', 'success');

                    //  Go to login page
                    return redirect()->route('login');
                }
            }
        } else {
            //  API Response
            if (oq_viaAPI($request)) {
                return oq_api_notify([
                    'message' => 'Invalid Token',
                ], 422);
            } else {
                //  Notify the user that their account is already verified
                oq_notify('Invalid Token', 'warning');
                //  Go to login page
                return redirect()->route('login');
            }
        }
    }

}
