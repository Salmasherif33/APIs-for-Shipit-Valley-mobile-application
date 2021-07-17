<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Providers\RouteServiceProvider;
use Illuminate\Foundation\Auth\ResetsPasswords;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class ResetPasswordController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Password Reset Controller
    |--------------------------------------------------------------------------
    |
    | This controller is responsible for handling password reset requests
    | and uses a simple trait to include this behavior. You're free to
    | explore this trait and override any methods you wish to tweak.
    |
    */

    //use ResetsPasswords;

    /**
     * Where to redirect users after resetting their password.
     *
     * @var string
     */
    //protected $redirectTo = RouteServiceProvider::HOME;



    public function changePassword(Request $request)
    {
        if (auth('driver_api')->check()) {
            $validator = Validator::make($request->all(), [
                'newpassword' => ['required', 'min:6', 'string']

            ]);

            if ($validator->fails()) {
                return response([
                    'message' => $validator->errors()->messages()
                ], 422);
            }

            $driver = auth('driver_api')->user();
            $driver->password = Hash::make(request('newpassword'));
            $driver->api_token = $request->bearerToken();
            $driver->save();
            return response([
                'status' => 200
            ]);
        }
    }

    public function updatePassword(Request $request){
        if (auth('driver_api')->check()) {
            $validator = Validator::make($request->all(), [
                'newpassword' => ['required', 'min:6', 'string'],
                'oldpassword' =>['required','min:6','string']

            ]);

            if ($validator->fails()) {
                return response([
                    'message' => $validator->errors()->messages()
                ], 422);
            }

            $driver = auth('driver_api')->user();
            
           if( ! Hash::check($request->oldpassword, $driver->password)){
                return response([
                    'status' =>414,
                    'message' =>'the old password is incorrect'
                ],414);
           }
           
           $driver->password = Hash::make(request('newpassword'));
            $driver->save();
            return response([
                'status' => 200,
                'message' =>"password updated successfully"
            ]);
        }
    }
}
