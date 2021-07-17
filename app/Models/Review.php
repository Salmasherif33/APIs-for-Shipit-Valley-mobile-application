<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    public $timestamps = ["created_at"];
    const UPDATED_AT = null;
    protected $guarded = [];
    use HasFactory;


    public static function check(int $user_id, int $order_id){
        if(Review::where('orders_id', '=', $order_id)->where('users_id', '=', $user_id)->
        where('type', '=', 'userToDriver')->count()== 0){
            return false;
        }
        return true;
    }
    

    public static function checkDriver(int $driver_id, int $order_id){
        if(Review::where('orders_id', '=', $order_id)->where('drivers_id', '=', $driver_id)->
        where('type', '=', 'driverToUser')->count()== 0){
            return false;
        }
        return true;
    }
}
