<?php

namespace App\Http\Controllers\Auth;

use App\Exceptions\Handler;
use App\Http\Controllers\Controller;
use App\Models\Session;
use App\Models\User;
use App\Providers\RouteServiceProvider;
use Illuminate\Foundation\Auth\VerifiesEmails;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class VerificationController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Email Verification Controller
    |--------------------------------------------------------------------------
    |
    | This controller is responsible for handling email verification for any
    | user that recently registered with the application. Emails may also
    | be re-sent if the user didn't receive the original email message.
    |
    */

    //use VerifiesEmails;

    /**
     * Where to redirect users after verification.
     *
     * @var string
     */
    //protected $redirectTo = RouteServiceProvider::HOME;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    // public function __construct()
    // {
    //     $this->middleware('auth');
    //     $this->middleware('signed')->only('verify');
    //     $this->middleware('throttle:6,1')->only('verify', 'resend');
    // }




    public function verify(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone' => ['numeric', 'digits_between:6,25', 'unique:users', 'regex:/^\S*$/u'],
            'code' => ['required', 'size:5'],
            'country_code' => ['string'],

        ]);

        if ($validator->fails() && $validator->errors()) {
            $array = [
                'status_code' => 422,
                'message' => $validator->errors()->messages()
            ];
            return response($array, 422);
        }

        /** REGISTERATION VERIFICATION */

        if (request('phone')) {
            $user = User::where('phone', '=', request('country_code') . request('phone'))->first();

            if (request('code') == Session::where('users_id', '=', $user->id)->latest()->first()->code) {
                Session::where('users_id', '=', $user->id)->latest()->first()->delete();
                $user->is_verified = 1;
                $user->save();
                return response([
                    'status' => 200,
                    'message' => "successfully done"
                ]);
            } else {
                return response([
                    'status' => 409,
                    'message' => "verification code is wrong or has expired"
                ], 409);
            }
        }
    }


    /** FORGOT PASSWORD VERIFICATION */

    public function forgotPasswordVerify(Request $request)
    {
        
        $validator = Validator::make($request->all(), [
            'code' => ['required', 'size:5'],

        ]);

        if ($validator->fails() && $validator->errors()) {
            $array = [
                'status_code' => 422,
                'message' => $validator->errors()->messages()
            ];
            return response($array, 422);
        }

        $TmpToken =  $request->bearerToken();
        $id =  auth('api')->user()->id;


        if (
            Session::where('users_id', '=', $id)->where('tmp_code', '=', $TmpToken)->latest()->first()->code
            == request('code')
        ) {
            Session::where('users_id', '=', $id)->latest()->first()->delete();

            return response([
                'status' => 200,
                'message' => "successfully done"
            ]);
        } else {
            return response([
                'status' => 409,
                'message' => "verification code is wrong or has expired"
            ], 409);
        }
    }



    /**UPDATE PHONE */
    public function verifyNewPhone(Request $request)
    {
        $user = auth('api')->user();
        if ($request->code == Session::where('users_id', '=', $user->id)->latest()->first()->code) {
            $user->phone = Session::where('users_id', '=', $user->id)->latest()->first()->tmp_phone;
            $user->save();

            Session::where('users_id', '=', $user->id)->latest()->first()->delete();

            return response([
                'status' => 200,
                'message' => "successfully done"
            ]);
        } else {
            return response([
                'status' => 409,
                'message' => "verification code is wrong or has expired"
            ], 409);
        }
    }

    public function resend(Request $request){
        $validator = Validator::make($request->all(), [
            'phone' =>  ['required', 'numeric', 'digits_between:6,25', 'regex:/^\S*$/u'],
            'country_code' => ['required','string'],
        ]);

        if ($validator->fails() && $validator->errors()) {
            $array = [
                'status_code' => 422,
                'message' => $validator->errors()->messages()
            ];
            return response($array, 422);
        }

        $user =  User::where('phone','=', request('country_code').(int)request('phone'))->first();
        
        $session = Session::where('users_id','=',$user->id);
        if($session->count() > 0){
            $is_expired = $session->latest()->first()->created_at->addMinutes(2);
            if($session->latest()->first()->created_at > $is_expired){
               return response([
                   'status' =>416,
                   'message' =>"failed to send last code less than ago minutes 2"
               ],416);
            }
            
            $code = Handler::sendCode($request->phone,$request->country_code);;
            $session->latest()->first()->update(array(
                'code'=>$code,
                'created_at' =>Carbon::now(),
                'tmp_code' =>('tmp_code' != null ? $code : null)
            ));
            return response([
                'status' =>200,
                'message' =>"activation code sent"
            ]);
        }
    }
}
