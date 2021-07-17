<?php

namespace App\Http\Controllers\driver;

use App\Exceptions\Handler;
use App\Http\Controllers\Controller;
use App\Models\Driver;
use App\Models\Financial;
use App\Models\Session;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class DriverController extends Controller
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
                'car_name' => ['required', 'string'],
                'car_model' => ['string'],
                'car_license_number' => ['numeric'],
                'driving_license_image' => ['file'],
                'car_license_image' => ['file'],
                'car_photo' => ['file'],
                'trucks_types_id' => ['numeric'],
                'id_image' => ['file']

            ]);
            if ($validator->fails()) {
                return response([
                    'message' => $validator->errors()->messages()
                ], 422);
            }


            $driver = auth('driver_api')->user();
            if ($driver->is_active) {
                //if phone regisered before to another user


                if (
                    Driver::where('phone', '=', $request->country_code . (int)$request->phone)->count() > 0 &&
                    $request->country_code . (int)$request->phone != $driver->phone
                ) {
                    return response([
                        'status' => 415,
                        'message' => "This phone registered before"
                    ], 415);
                }

                //user change his phone
                if ($request->country_code . (int)$request->phone != $driver->phone) {
                    $code = Handler::sendCode(request('phone'), request('country_code'));
                    Session::create([
                        'tmp_phone' => $request->country_code . (int)$request->phone,
                        'drivers_id' => $driver->id,
                        'code' => $code
                    ]);
                }

                //user update other fields
                if (request('name'))
                    $driver->name = $request->name;
                if (request('country_code'))
                    $driver->country_code = $request->country_code;
                if ($request->image) {
                    $driver->image = request('image')->store('images');
                }
                if (request('car_name'))
                    $driver->car_name = $request->car_name;
                if (request('car_model'))
                    $driver->car_model = request('car_model');
                if (request('car_license_number'))
                    $driver->car_license_number = request('car_license_number');
                if (request('driving_license_image'))
                    $driver->driving_license_image = request('driving_license_image')->store('driving_license');
                if (request('car_license_image'))
                    $driver->car_license_image = request('car_license_image')->store('car_license');
                if (request('car_photo'))
                    $driver->car_photo = request('car_photo')->store('car_photo');
                if (request('trucks_types_id'))
                    $driver->trucks_types_id = request('trucks_types_id');
                if (request('id_image'))
                    $driver->id_image = request('id_image')->store('image');

                $driver->fill($request->all());
                $driver->save();
                return response([
                    'status' => 200,
                    'message' => "profile updated successfully"
                ]);
            }
        }
    }


    public function myProfit(Request $request){
        if(auth('driver_api')->check()){
            if(Financial::where('drivers_id','=',auth('driver_api')->user()->id)->count() > 0){
                $profit =  auth('driver_api')->user()->financials()->sum('total_benefit');
                return response([
                    'status' =>200,
                    'profit' => $profit
                ]);
            }
        }
    }
}
