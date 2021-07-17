<?php

namespace App\Http\Controllers\driver\Auth;

use App\Exceptions\Handler;
use App\Http\Controllers\Controller;
use App\Models\Driver;
use App\Models\Session;
use App\Providers\RouteServiceProvider;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Auth\Events\Registered;
use Illuminate\Foundation\Auth\RegistersUsers;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class RegisterController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Register Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles the registration of new users as well as their
    | validation and creation. By default this controller uses a trait to
    | provide this functionality without requiring any additional code.
    |
    */

    use RegistersUsers;

    /**
     * Where to redirect users after registration.
     *
     * @var string
     */
    //protected $redirectTo = RouteServiceProvider::HOME;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest:driver');
    }

    protected function guard()
{
    return Auth::guard('driver');
}

    protected function create(array $data)
    {
        if(request('driving_license_image') ||request('car_license_image') || request('id_image') || request('car_photo')
        || request('image')){
            //$originalName = $data['image']->getClientOriginalName();
            $driving_license = request('driving_license_image')->store('driving_license');
            $car_license = request('car_license_image')->store('car_license');
            $id_image = request('id_image')->store('id_image');
            $car_photo = request('car_photo')->store('car_photo');
            $image = request('image')->store('image');
        }

         return Driver::create([
            'name' => $data['name'],
            'country_code' => $data['country_code'],
            'phone' => '+'. $data['country_code'].$data['phone'],
            'password' => Hash::make($data['password']),
            'car_name' =>$data['car_name'],
            'car_model' => $data['car_model'],
            'car_license_number' =>$data['car_license_number'],
            'driving_license_image' =>$driving_license,
             'car_license_image' => $car_license,
             'id_image' =>$id_image,
             'car_photo' => $car_photo,
             'image' =>$image,
             'trucks_types_id' =>$data['trucks_types_id'],
            'api_token' =>str_random(65)

        ]);
        
    }

    




    public function verify(Request $request){
        $validator = Validator::make($request->all(),[
            'phone' =>['required', 'numeric', 'digits_between:6,25', 'unique:users', 'regex:/^\S*$/u'],
            'code' =>'size:5',
            'country_code' => ['required','string'],

        ]);
       
        if($validator->fails() && $validator->errors()){
            $array = [
                'status_code'=> 422,
                'message' => $validator->errors()->messages()
            ];
            return response($array,422);
        }

        $driver = Driver::where('phone','=', request('country_code').request('phone'))->first();
      
        if(request('code') == Session::where('drivers_id','=',$driver->id)->latest()->first()->code){
            $driver->is_verified = 1;
            $driver->save();
            return response([
                'status' => 200,
                'message' =>"successfully done"
            ]);
        }
        else{
            return response([
                'status' =>409,
                'message' =>"verification code is wrong or has expired"
            ],409);
        }
    }

    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'numeric', 'digits_between:6,25', 'unique:drivers', 'regex:/^\S*$/u'],
            'password' => ['required', 'string', 'min:8'],
            'country_code' => ['required','string'],
            'car_name' =>['required','string'],
            'car_model' =>['required','string'],
            'car_license_number' =>['required','numeric'],
            'driving_license_image' =>['file','required'],
            'car_license_image'=>['file','required'],
            'car_photo' => ['file','required'],
            'trucks_types_id' =>['required', 'numeric'],
            'id_image' =>['file','required']
        ]);    
        if(Driver::where('phone','=',request('country_code').request('phone'))->count() > 0)
        {
            $array = [
                'data' => $request->all(),
                'status_code'=> 407,
                'message' => "this phone regisered before"
            ];
            return response($array,407);
          
        }

        else{

        event(new Registered($driver = $this->create($request->all())));
        $code = Handler::sendCode(request('phone'),request('country_code'));
        $driver_id = Driver::where('phone','=', request('country_code').request('phone'))->first()->id;
        
            Session::create([
                'code' =>$code,
                'drivders_id' =>$driver_id
            ]); 

        $this->guard('driver_api')->login($driver);
        $array = [
            'data' => $driver->toArray(),
            'status_code'=> 200,
            'message' => "successfully registered"
        ];
        return response($array,200);
    }
    }
}
