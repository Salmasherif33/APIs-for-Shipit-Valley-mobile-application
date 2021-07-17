<?php

namespace App\Http\Controllers\Auth;

use App\Exceptions\Handler;
use App\Http\Controllers\Controller;
use App\Models\Driver;
use App\Models\Session;
use App\Models\User;
use Illuminate\Foundation\Auth\SendsPasswordResetEmails;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

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

    //use SendsPasswordResetEmails;


    public function forgot(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone' => ['required', 'numeric', 'digits_between:6,25', 'unique:drivers', 'regex:/^\S*$/u'],
            'country_code' => ['required', 'string']
        ]);

        if ($validator->fails()) {
            return response([
                'message' => $validator->errors()->messages()
            ], 422);
        }

        if (Driver::where('phone', '=', request('country_code') . (int)request('phone'))->count() == 0) {
            $array = [
                'data' => $request->all(),
                'status_code' => 413,
                'message' => "this phone is not regisered"
            ];
            return response($array, 413);
        }

        $driver = Driver::where('phone','=',request('country_code').(int)request('phone'))->first();
        if($driver->is_active){
        $code = Handler::sendCode(request('phone'),request('country_code'));
        $TmpToken = $driver->createToken('my-app-token')->plainTextToken;
        Session::create([
            'tmp_code' =>$TmpToken,
            'code' => $code,
            'drivers_id' =>$driver->id
        ]);

        return response([
            'status' =>200,
            'TmpToken' => $TmpToken
        ]);

        }
    }

  
}
