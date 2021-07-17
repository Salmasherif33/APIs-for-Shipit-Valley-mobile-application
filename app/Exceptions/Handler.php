<?php

namespace App\Exceptions;

use App\Models\Driver;
use App\Models\Session;
use App\Models\User;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     *
     * @return void
     */
    public function register()
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    public static function sendCode($phone,$country_code){
        $code = rand(0,99999);
        $nexmo = app('Nexmo\Client');
        $nexmo->message()->send([
            'to' => $country_code . (int) $phone,
            'from' => 'Vonage APIs',
            'text' =>'Verify code: '. $code
        ]);
        return $code;
       
       
    }
}
