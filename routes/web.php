<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;


/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {

   
    return view('welcome');

    // if (Auth::check()){
    //     return "The user is logged in";
    // }



    /** IF PASSING INFO THROUGH A FORM, AUTH. THEM */
    //     $username = "fegfe";
    //     $password = "Fefef";
    // if(Auth::attempt(['username'=>$username, 'password'=>$password])){
    //     /** return to where you want to go */
    //     return redirect()->intended('/admin');
        
    // }


    /**another functions */
    //Auth::logout();

});

Auth::routes();

Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');

Auth::routes();

Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');


Route::group(['prefix' => '/admin', 'namespace' => 'Admin', 'as' => 'admin.'], function () {
    // ...
    Route::get('/', function () {

   
        return view('admin.welcome');
    });

});