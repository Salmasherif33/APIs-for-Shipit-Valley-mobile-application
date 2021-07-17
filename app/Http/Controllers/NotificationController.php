<?php

namespace App\Http\Controllers;

use App\Http\Resources\OrderResource;
use Illuminate\Pagination\Paginator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class NotificationController extends Controller
{
    //
    public function unseenNotf(Request $request){
        if(auth('api')->check()){
            $count = DB::table('notify_users')->where('users_id','=',auth('api')->user())->count();
            return response([
                'status' =>200,
                'count' =>$count
            ]);
        }
        else if(auth('driver_api')->check()){
            $count = DB::table('notify_users')->where('drivers_id','=',auth('driver_api')->user())->count();
            return response([
                'status' =>200,
                'count' =>$count
            ]);
        }
    }

    public function getNotf(Request $request){
        $validator = Validator::make($request->all(),[
            'page' =>['required','integer']
        ]);
        if ($validator->fails()) {
            return response([
                'status' => 422,
                'message' => $validator->errors()
            ], 422);
        }

        if(auth('api')->check()){
            $notf = DB::table('notify_users')->where('users_id','=',5)->where('is_seen','=',0)->get();
            $new = array();
            foreach($notf as $k=>$v){
                $new[$k] = clone $v;
            }
            DB::table('notify_users')->where('users_id','=',5)->where('is_seen','=',0)->update(['is_seen'=>1]);
            
            return response([
                'status' =>200,
                'notification' => $this->paginate($new,1, request('page'))
            ]);
        }
        else if(auth('driver_api')->check()){

        }

    }

    private function paginate($items, $perPage = 1, $page = null, $options = [])
    {
        $page = $page ?: (Paginator::resolveCurrentPage() ?: 1);
        $items = $items instanceof Collection ? $items : Collection::make($items);
        return new LengthAwarePaginator($items->forPage($page, $perPage), $items->count(), $perPage, $page, $options);
    }
}
