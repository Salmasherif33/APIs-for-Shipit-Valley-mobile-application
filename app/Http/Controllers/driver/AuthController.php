<?php

namespace App\Http\Controllers\driver;

use App\Http\Controllers\Controller;
use App\Providers\RouteServiceProvider;
use App\Models\Driver;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;



class AuthController extends Controller
{
    //
    protected function create(array $data)
    {
        if(request('driving_licnense_image') ||request('car_licnense_image') || request('id_image') || request('car_photo')){
            //$originalName = $data['image']->getClientOriginalName();
            $driving_license = request('driving_licnense_image')->store('driving_license');
            $car_license = request('car_licnense_image')->store('car_license');
            $id_image = request('id_image')->store('id_image');
            $car_photo = request('car_photo')->store('car_photo');
        }

         return Driver::create([
            'name' => $data['name'],
            'country_code' => $data['country_code'],
            'phone' => $data['phone'],
            'password' => Hash::make($data['password']),
            'car_name' =>$data['car_name'],
            'car_model' => $data['car_model'],
            'car_license_number' =>$data['car_license_number'],
            'driving_licnense_image' =>$driving_license,
             'car_licnense_image' => $car_license,
             'id_image' =>$id_image,
             'car_photo' => $car_photo,
            'api_token' =>str_random(65)
        ]);
        
    }

    public function register(Request $request)
    {
      $validator = Validator::make($request->all(), [
        'name' => ['required', 'string', 'max:255'],
        'phone' => ['required', 'numeric', 'digits_between:6,25', 'unique:users', 'regex:/^\S*$/u'],
        'password' => ['required', 'string', 'min:8'],
        'country_code' => ['required','string'],
        'car_name' =>['required','string'],
        'car_model' =>['required','string'],
        'car_license_number' =>['required','numeric'],
        'driving_license_image' =>['file','required'],
        'car_licnense_image'=>['file','required'],
        'car_photo' => ['file','required'],
        'sub_truck_type_id' =>['required', 'numeric'],
        'id_image' =>['file','required']
    ]);   
    if($validator->fails()){
        $array = [
            'data' => $request->all(),
            'status_code'=> 407,
            'message' => "this phone registered before"
        ];
        return response($array,407);
      
    }
    else{
        event(new Registered($driver = $this->create($request->all())));
        //Auth::guard('driver')->attempt($request->only('phone','password'));

    }
}
       
}
