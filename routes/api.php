<?php

use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\driver\Auth\ForgotPasswordController as AuthForgotPasswordController;

use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\driver\Auth\ResetPasswordController as AuthResetPasswordController;

use App\Http\Controllers\Auth\VerificationController;
use App\Http\Controllers\contactController;
use App\Http\Controllers\driver\Auth\VerificationController as AuthVerificationController;

use App\Http\Controllers\driver\Auth\LoginController;
use App\Http\Controllers\driver\Auth\RegisterController as AuthRegisterController;
use App\Http\Controllers\driver\AuthController;
use App\Http\Controllers\driver\DriverController;
use App\Http\Controllers\generalController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Auth::routes();

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

Route::middleware('auth:driver_api')->get('/driver', function (Request $request) {
    return $request->user('driver_api');
});




Route::group(['prefix' => '/driver', 'namespace' => 'driver', 'as' => 'driver.'], function () {

    Route::post('/register', [AuthRegisterController::class, 'register']);
    Route::post('/login', [LoginController::class, 'login']);
    Route::post('/verify', [AuthVerificationController::class, 'verify']);
    Route::post('/forgetpassword', [AuthForgotPasswordController::class, 'forgot']);
    Route::post('/resendcode', [AuthVerificationController::class, 'resend']);

});


Route::group(
    ['middleware' => ['auth:sanctum', 'auth:driver_api'], 'prefix' => '/driver', 'namespace' => 'driver', 'as' => 'driver.'],
    function () {

        Route::post('/getorders', [OrderController::class, 'getOrders']);
        Route::post('/acceptorder', [OrderController::class, 'acceptOrder']);
        Route::post('/cancelorder', [OrderController::class, 'cancelByDriver']);
        Route::post('/arrivepickup', [OrderController::class, 'ArrivePickUp']);
        Route::post('/goodsloading', [OrderController::class, 'goodsLoading']);
        Route::post('/startmoving', [OrderController::class, 'startMoving']);
        Route::post('/arrivedestination', [OrderController::class, 'arriveDestination']);
        Route::post('/finishtrip', [OrderController::class, 'driverFinishTrip']);

        Route::post('/forgotpassword/verify', [AuthVerificationController::class, 'forgotPasswordVerify']);

        Route::post('/changepassword', [AuthResetPasswordController::class, 'changePassword']);

        Route::post('/updatepassword', [AuthResetPasswordController::class, 'updatePassword']);
        Route::post('/updateprofile', [DriverController::class, 'updateProfile']);
        Route::post('/contact', [contactController::class, 'contact']);
        Route::post('/unseennotf', [NotificationController::class, 'unseenNotf']);
        Route::post('/getnotf', [NotificationController::class, 'getNotf']);
        Route::post('/closetrip', [OrderController::class, 'closeTrip']);
        Route::post('/rate', [OrderController::class, 'rateOrder']);
        Route::post('/contactstypes', [contactController::class, 'getContactsTypes']);
        Route::post('/truckstypes', [generalController::class, 'getTrucksTypes']);
        Route::post('/goodstypes', [generalController::class, 'getGoodsTypes']);
        Route::post('/bankaccounts', [generalController::class, 'getBankAccounts']);
        Route::post('/myprofit', [DriverController::class, 'myProfit']);
        Route::post('/activeorder', [OrderController::class, 'getActiveOrder']);


    }
);









Route::post('/register', [RegisterController::class, 'register']);
Route::post('/verify', [VerificationController::class, 'verify']);
Route::post('/forgetpassword', [ForgotPasswordController::class, 'forgot']);
Route::post('/resendcode', [VerificationController::class, 'resend']);
Route::post('/contact', [contactController::class, 'contact']);



Route::group(['middleware' => ['auth:sanctum', 'auth:api']], function () {

    Route::post('/order', [OrderController::class, 'storeOrder']);
    Route::post('/discountcode', [OrderController::class, 'discount']);
    Route::post('/getorders', [OrderController::class, 'getOrders']);
    Route::post('/cancelorder', [OrderController::class, 'cancelOrder']);
    Route::post('/finishtrip', [OrderController::class, 'userFinishTrip']);
    Route::post('/forgotpassword/verify', [VerificationController::class, 'forgotPasswordVerify']);

    Route::post('/changepassword', [ResetPasswordController::class, 'changePassword']);

    Route::post('/updatepassword', [ResetPasswordController::class, 'updatePassword']);
    Route::post('/updateprofile', [UserController::class, 'updateProfile']);
    Route::post('/verifynewphone', [VerificationController::class, 'verifyNewPhone']);
    Route::post('/user/contact', [contactController::class, 'contact']);
    Route::post('/unseennotf', [NotificationController::class, 'unseenNotf']);
    Route::post('/getnotf', [NotificationController::class, 'getNotf']);
    Route::post('/paymenttype', [OrderController::class, 'paymentType']);

    Route::post('/offlinepayment', [OrderController::class, 'offlinePayment']);
    Route::post('/rate', [OrderController::class, 'rateOrder']);
    Route::post('/contactstypes', [contactController::class, 'getContactsTypes']);
    Route::post('/truckstypes', [generalController::class, 'getTrucksTypes']);
    Route::post('/goodstypes', [generalController::class, 'getGoodsTypes']);
    Route::post('/bankaccounts', [generalController::class, 'getBankAccounts']);
    Route::post('/activeorder', [OrderController::class, 'getActiveOrder']);



});
