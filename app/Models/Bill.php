<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Bill extends Model
{
    public $timestamps = ["created_at"];
    const UPDATED_AT = null;
    protected $guarded = [];
    protected $table = 'bills';
    use HasFactory;


    public static function calcCost(Order $order){
        $truck_id = $order->trucks_types_id;

            $pickup_id = $order->locations_pickup_id;
            $dest_id = $order->locations_destination_id;


            $location_pickup_lat = Location::where('id',$pickup_id)->first()->latitude;
            $location_pickup_long = Location::where('id',$pickup_id)->first()->longitude;


            $location_dest_lat = Location::where('id',$dest_id)->first()->latitude;
            $location_dest_long = Location::where('id',$dest_id)->first()->longitude;

            $distance = Location::distance($location_pickup_lat, $location_pickup_long,$location_dest_lat,
                                            $location_dest_long,"K");


            $listPrice = DB::table('price_list')->orderBy('category','asc')->where('trucks_types_id',$truck_id)->get();
            foreach($listPrice as $item){
                if($distance  < $item->category){
                   $bill = new Bill(['cost'=>$item->price]);
                   
                    break;
                }
            }
            return $bill->cost;
    }
}
