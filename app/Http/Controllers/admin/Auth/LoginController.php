<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Providers\RouteServiceProvider;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
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

    protected function guard()
    {
        return Auth::guard('admin');
    }
    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = RouteServiceProvider::ADMIN_HOME;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest:admin')->except('logout');
    }

    public function showLoginForm()
    {
        return view('admin.auth.login');
    }
    protected function loggedOut(Request $request)
    {
        return $request->wantsJson()
            ? new Response('', 204)
            : redirect('/admin');
    }



    public function login(Request $request)
    {
        $this->validateLogin($request);

        if ($this->guard('admin')->attempt($request->only('phone', 'password'))) {

            $admin = Admin::where('phone', '=', $request->get('phone'))->first();
            //generate new api token
            //$token = $user->createToken('my-app-token')->plainTextToken;
            //$user->api_token = $token;
            //$admin->save();

        }


        if (Admin::where('phone', '=', $request->get('phone'))->count() == 0) {
            // $array = [
            //     'data' => $request->all(),
            //     'status_code' =>411,
            //     'message' =>"Phone nor register"
            // ];
            //     return response($array,412);
        } else if (!$this->guard('admin')->attempt($request->only('password'))) {
            // $array = [
            //     'data' => $request->all(),
            //     'status_code' =>412,
            //     'message' =>"Password is not correct"
            // ];
            //    return response($array,412);
        }
    }
}
