<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Financial extends Model
{
    protected $guarded = [];
    use HasFactory;


    public static function calcFinancial(int $driver_id, int $acceptorder)
    {
        //cost
        $getBill = Bill::where('orders_id', '=', $acceptorder)->first();
        if ($getBill->discount != null) {
            $costAfterDisc = $getBill->cost - ($getBill->cost * $getBill->discount / 100);
        } else {
            $costAfterDisc = $getBill->cost;
        }
        //fees
        if (Driver::where('id', '=', $driver_id)->first()->fees == null) {
            $fees = Setting::where('fees', '!=', 'null')->first()->fees;
        } else {

            $fees = Driver::where('id', '=', $driver_id)->first()->fees;
        }

        //calc
        $total_benefit = $costAfterDisc * ($fees / 100);
        Financial::create([
            'total_benefit' => $total_benefit,
            'paid_money' => $costAfterDisc,
            'drivers_id' => $driver_id
        ]);
    }

    public static function subFinancial($driver_id)
    {
        $f = Financial::where('drivers_id','=',$driver_id)->latest('created_at')->first();
        $f->total_benefit = 0;
        $f->paid_money = 0;
        $f->save();
    }

    public function driver(){
        return $this->belongsTo('App\Models\Driver');
    }
}
