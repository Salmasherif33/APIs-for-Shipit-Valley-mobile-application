<?php

namespace App\Http\Controllers;

use App\Exceptions\Handler;
use App\Models\Session;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    //

    public function updateProfile(Request $request)
    {
        if (auth('api')->check()) {
            $validator = Validator::make($request->all(), [
                'name' => ['string', 'max:255'],
                'phone' => ['numeric', 'digits_between:6,25', 'regex:/^\S*$/u'],
                'password' => ['string', 'min:8'],
                'country_code' => ['string'],
                'image' => ['file'],

            ]);
            if ($validator->fails()) {
                return response([
                    'message' => $validator->errors()->messages()
                ], 422);
            }


            $user = auth('api')->user();
            if ($user->is_active) {
                //if phone regisered before to another user
                

                if (
                    User::where('phone', '=', $request->country_code . (int)$request->phone)->count() > 0 &&
                    $request->country_code . (int)$request->phone != $user->phone
                ) {
                    return response([
                        'status' => 415,
                        'message' => "This phone registered before"
                    ], 415);
                }

                //user change his phone
                if ($request->country_code . (int)$request->phone != $user->phone) {
                    $code = Handler::sendCode(request('phone'), request('country_code'));
                    Session::create([
                        'tmp_phone' => $request->country_code . (int)$request->phone,
                        'users_id' => $user->id,
                        'code' => $code
                    ]);
                }

                //user update other fields
                if(request('name'))
                    $user->name = $request->name;
                if(request('country_code'))
                    $user->country_code = $request->country_code;
                if($request->image){
                    $user->image = request('image')->store('images');
                }
                $user->fill($request->all());
                $user->save();
                return response([
                    'status' => 200,
                    'message' => "profile updated successfully"
                ]);

            }
        }
    }

    
    
}
