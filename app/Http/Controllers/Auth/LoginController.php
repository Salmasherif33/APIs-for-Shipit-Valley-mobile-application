<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Providers\RouteServiceProvider;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers;

    /**
     * Where to redirect users after login.
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
        $this->middleware('guest')->except('logout');
    }

    public function login(Request $request){
        $this->validateLogin($request);

        if($this->guard('api')->attempt($request->only('phone','password'))){
            
            $user = User::where('phone', '=', $request->get('phone'))->first();

            //check if verified 
            if($user->is_verified == 0){
                return response([
                    'status' =>410,
                    'message'=>"phone not verified"
                ],410);
            }
            
            //generate new api token
            $token = $user->createToken('my-app-token')->plainTextToken;
            $user->api_token = $token;
            $user->save();
            $array = [
                'data' => $request->all(),
                'status_code' =>200,
                'message' =>"login successfully",
                'token' => $token
            ];
            return response($array,200);
        }
        
        
        if(User::where('phone', '=', $request->get('phone'))->count()== 0){
            $array = [
                'data' => $request->all(),
                'status_code' =>411,
                'message' =>"Phone not register"
            ];
                return response($array,412);
        }
        else if(!$this->guard('api')->attempt($request->only('password'))){
            $array = [
                'data' => $request->all(),
                'status_code' =>412,
                'message' =>"Password is not correct"
            ];
               return response($array,412);
        }
       
      
        
    }


}
