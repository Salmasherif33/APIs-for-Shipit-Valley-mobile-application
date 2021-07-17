<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class contactController extends Controller
{
    //
    public function contact(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'contacts_types_id' => ['required', 'integer'],
            'message' => ['reuired', 'string']
        ]);
        if ($validator->fails()) {
            return response([
                'status' => 422,
                'message' => $validator->errors()
            ], 422);
        }

        if (auth('api')->check()) {
            $code = rand(0, 99999);
            Contact::create([
                'contacts_types_id' => $request->contacts_types_id,
                'message' => $request->message,
                'users_id' => auth('api')->user()->id,
                'code' => $code
            ]);
            return response([
                'status' => 200
            ]);
        } else if (auth('driver_api')->check()) {
            $code = rand(0, 99999);
            Contact::create([
                'contacts_types_id' => $request->contacts_types_id,
                'message' => $request->message,
                'drivers_id' => auth('driver_api')->user()->id,
                'code' => $code
            ]);
            return response([
                'status' => 200
            ]);
        } else {
            $validator = Validator::make($request->all(),[
                'name' =>['required','string'],
                'phone' =>['required']
            ]);
            if ($validator->fails()) {
                return response([
                    'status' => 422,
                    'message' => $validator->errors()
                ], 422);
            }
            $code = rand(0, 99999);
            Contact::create([
                'contacts_types_id' => $request->contacts_types_id,
                'message' => "Name: ". $request->name." - "."Phone: ".$request->phone." - "."Message: ". $request->message,
                'code' => $code
            ]);
            return response([
                'status' => 200
            ]);
        }
    }

    public function getContactsTypes(Request $request){
        if(auth('api')->check() || auth('driver_api')->check()){
            $types = DB::table('contacts_types')->select('name_en')->get();
            return response([
                'status' =>200,
                'data' => $types
            ]);
        }
    }
}
