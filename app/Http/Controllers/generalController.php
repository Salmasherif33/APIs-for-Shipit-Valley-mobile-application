<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class generalController extends Controller
{
    //

    public function getTrucksTypes(Request $request){
        if(auth('api')->check() || auth('driver_api')->check()){
            $types = DB::table('trucks_types')->select('trucks_types.*')->get();
            return response([
                'status' =>200,
                'data' => $types
            ]);
        }
    }

    public function getGoodsTypes(Request $request){
        if(auth('api')->check() || auth('driver_api')->check()){
            $types = DB::table('goods_types')->select('goods_types.*')->get();
            return response([
                'status' =>200,
                'data' => $types
            ]);
        }
    }
    public function getBankAccounts(Request $request){
        if(auth('api')->check() || auth('driver_api')->check()){
            $types = DB::table('bank_accounts')->select('bank_accounts.*')->get();
            return response([
                'status' =>200,
                'data' => $types
            ]);
        }
    }
}
