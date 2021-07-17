<?php

namespace App\Http\Controllers\Auth;

use App\Exceptions\Handler;
use App\Http\Controllers\Controller;
use App\Models\Session;
use App\Providers\RouteServiceProvider;
use App\Models\User;
use App\Notifications\WelcomeNotification;
use Illuminate\Http\Request;
use Illuminate\Auth\Events\Registered;
use Illuminate\Foundation\Auth\RegistersUsers;
use Notification;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification as FacadesNotification;
use Illuminate\Support\Facades\Validator;
use Nexmo\Laravel\Facade\Nexmo;

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
    protected $redirectTo = RouteServiceProvider::HOME;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest');
    }

    /**
     * Get a validator for an incoming registration request.
     *
     * @param  array  $data
     * @return \Illuminate\Contracts\Validation\Validator
     */
    // protected function validator(array $dat
    /**
     * Create a new user instance after a valid registration.
     *
     * @param  array  $data
     * @return \App\Models\User
     */
    protected function create(array $data)
    {
        
        if(request('image')){
            $input = request('image')->store('images');
        }
        
         return User::create([
            'name' => $data['name'],
            'country_code' => $data['country_code'],
            'phone' => $data['country_code'].(int)$data['phone'],
            'password' => Hash::make($data['password']),
             'image' => $input,
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
        'image' =>['file'],
        
    ]);   
    if($validator->fails()){
        return response([
            'message' => $validator->errors()->messages()
        ],422);
    }
    
    if(User::where('phone','=',request('country_code').request('phone'))->count() > 0)
    {
        $array = [
            'data' => $request->all(),
            'status_code'=> 407,
            'message' => "this phone regisered before"
        ];
        return response($array,407);
      
    }
   

        event(new Registered($user = $this->create($request->all())));
        
        $code = Handler::sendCode(request('phone'),request('country_code'));
        $user_id = User::where('phone','=', request('country_code').(int)request('phone'))->first()->id;
        
        Session::create([
            'code' =>$code,
            'users_id' =>$user_id
        ]); 

        $this->guard('api')->login($user);
        $array = [
            'data' => $user->toArray(),
            'status_code'=> 200,
            'message' => "successfully registered"
        ];
        return response($array,200);
    
    }
}
