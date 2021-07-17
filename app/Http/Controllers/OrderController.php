<?php

namespace App\Http\Controllers;

use App\Http\Resources\OrderResource;
use App\Models\Bill;
use App\Models\Contact;
use App\Models\Discount_code;
use App\Models\Driver;
use App\Models\Financial;
use App\Models\Location;
use App\Models\offlinePayment;
use App\Models\Order;
use App\Models\Review;
use App\Models\Session;
use App\Models\Setting;
use App\Models\User;
use App\Models\Users_Discout;
use GuzzleHttp\Promise\Create;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class OrderController extends Controller
{
    //
    private function Validation(Request $request)
    {
        $validator  = Validator::make($request->all(), [
            'locations_pickup_id' => ['required'],
            'locations_destination_id' => ['required'],
            'goods_types_id' => ['required'],
            'image' => ['file'],
            'descriptions' => ['string'],
            'i_am_recipient' => ['boolean'],
            'recipient_name' => ['string', (request('i_am_recipient') == 0 ? 'required' : '')],
            'load_weight' => ['string'],
            'country_code' => ['numeric', (request('i_am_recipient') == 0 ? 'required' : '')],
            'phone' => ['string', (request('i_am_recipient') == 0 ? 'required' : '')],
            'trucks_types_id' => ['required', 'numeric']
        ]);

        if ($validator->fails()) {
            return false;
        } else
            return true;
    }

    private function validateOrder(Request $request)
    {
        $validator =  Validator::make($request->all(), [
            'order_id' => ['required', 'numeric']
        ]);

        if ($validator->fails()) {
            return response([
                'status' => 422,
                'message' => $validator->errors()
            ], 422);
        }
    }


    public function storeOrder(Request $request)
    {
        if (auth('api')->check()) {
            $user_id = auth('api')->user()->id;
            if (!$this->Validation($request))
                return $this->Validation($request);

            $inputs = $request->all();

            if (request('image')) {
                $image = request('image')->store('orderImages');
                $inputs['image'] = $image;
            }

            $inputs['users_id'] = $user_id;
            $inputs['code'] = rand(0, 99999);

            //checkreview or not

            if (Order::where('users_id', '=', $user_id)->count() > 0) {
                $latestOrder = Order::where('users_id', '=', $user_id)->latest('users_id')->first();

                //check the laststatus
                if ($latestOrder->status != "closed") {
                    $array = [
                        'status' => $latestOrder->status,
                        'order' => $inputs,
                        'message' => "cannot send the order because you have order not done yet",
                    ];
                    return response($array, 417);
                }

                if (!Review::check($user_id, $latestOrder->id)) {
                    $array = [
                        'status' => $latestOrder->status,
                        'message' => "cannot send the order because you haven't rated your last order",
                    ];
                    return response($array, 418);
                }
            }
            //store
            else {
                $newOrder = Order::create($inputs);

                //calculate the bill
                $this->bill();

                $array = [
                    'status' => 200,
                    'order' => $newOrder,
                    'message' => 'order successfully submitted',
                ];
                return response($array, 200);
            }
        }
    }

    private function bill()
    {
        $order = Order::latest('users_id')->first();
        $billCost = Bill::calcCost($order);
        $bill_inputs = [
            'orders_id' => $order->id,
            'cost' => $billCost,
            'payment_type' => 'offline',
            'fees' => 15
        ];
        Bill::create($bill_inputs);
    }



    public function discount(Request $request)
    {

        $validator =  Validator::make($request->all(), [
            'code' => ['required', 'string'],
            'order_id' => ['required', 'numeric']
        ]);


        if ($validator->fails()) {

            return response($validator->errors()->first());
        }

        if (auth('api')->check()) {
            $user_id = auth('api')->user()->id;
            $order_id = request('order_id');


            $code = Discount_code::where('code', '=', request('code'))->first();
            //if code exists
            if (Discount_code::where('code', '=', request('code'))->count() == 0) {
                return response([
                    'status' => 422,
                    'message' => 'code not exist'
                ], 422);
            }
            //if code is active
            else if ($code->is_active != 1) {
                return response([
                    'status' => 419,
                    'message' => 'code not activated'
                ], 419);
            }
            //if code has enough count
            else if ($code->count == 0) {
                return response([
                    'status' => 420,
                    'message' => 'code richied to limit'
                ], 420);
            }
            //if user use code before
            else if (
                Users_Discout::where('users_id', '=', $user_id)->count() > 0
                && Users_Discout::where('discount_code_id', '=', $code->id)->count() > 0
            ) {

                return response([
                    'status' => 421,
                    'message' => 'code used before'
                ], 421);
            }
            //save user use discount
            else {

                Users_Discout::Create([
                    'users_id' => $user_id,
                    'discount_code_id' => $code->id
                ]);
                $code->count--;
                $code->save();
                $bill = Bill::where('orders_id', '=', $order_id)->first();

                $bill->discount = $code->discount;
                $bill->save();

                return response([
                    'status' => 200,
                    'message' => 'discount applied successfully'
                ]);
            }
        }
    }


    //get orders
    public function getOrders(Request $request)
    {
        if (auth('driver_api')->check()) {
            $driver_id = auth('driver_api')->user()->id;
            if (request('status') == 'pending') {
                $orders = DB::table('orders')->where('orders.status', '!=', 'closed')->where('drivers_id', '=', $driver_id)
                    ->join('bills', 'orders.id', '=', 'bills.orders_id')
                    ->where('bills.status', '=', 'paid')->select('orders.*');

                return response([
                    'status' => 200,
                    'orders' => $orders->get(OrderResource::collection($orders->paginate(10)))
                ]);
            } else {
                $orders = DB::table('orders')->where('orders.status', '=', 'closed')->where('drivers_id', '=', $driver_id)
                    ->join('bills', 'orders.id', '=', 'bills.orders_id')
                    ->where('bills.status', '=', 'paid')->select('orders.*');

                return response([
                    'status' => 201,
                    'orders' => $orders->get(OrderResource::collection($orders->paginate(10)))
                ]);
            }
        }

        if (auth('api')->check()) {
            $user_id = auth('api')->user()->id;
            if (request('status') == 'pending') {
                $orders = DB::table('orders')->where('orders.status', '!=', 'closed')->where('users_id', '=', $user_id)
                    ->join('bills', 'orders.id', '=', 'bills.orders_id')
                    ->where('bills.status', '=', 'paid')->select('orders.*');

                return response([
                    'status' => 200,
                    'orders' => $orders->get(OrderResource::collection($orders->paginate(10)))
                ]);
            } else {
                $orders = Order::where('orders.status', '=', 'closed')->where('users_id', '=', $user_id)
                    ->join('bills', 'orders.id', '=', 'bills.orders_id')
                    ->where('bills.status', '=', 'paid')->select('orders.*');


                return response([
                    'status' => 201,
                    'orders' => $orders->get(OrderResource::collection($orders->paginate(10)))
                ]);
            }
        }
    }



    public function cancelOrder(Request $request)
    {
        if (auth('api')->check()) {

            $this->validateOrder($request);

            $orderCancel = Order::where('id', '=', request('order_id'))->first();

            if ($orderCancel->status != "awaitingPayment") {
                return response([
                    'status' => 423,
                    'message' => "can't cancel the order"
                ], 423);
            } else {
                $orderCancel->status = "cancelledByUser";
                $orderCancel->save();
                return response([
                    'status' => 200,
                    'message' => " Successfully canceled"
                ]);
            }
        }
    }

    public function acceptOrder(Request $request)
    {
        if (auth('driver_api')->check()) {
            $driver_id = auth('driver_api')->user()->id;
            $this->validateOrder($request);

            $acceptorder = Order::where('id', '=', request('order_id'))->where('drivers_id', '=', $driver_id)->first();

            //accept only if status is awaitingDriver
            if ($acceptorder != null) {
                if ($acceptorder->status != "awaitingDriver") {
                    return response([
                        'status' => 424,
                        'message' => "action can't completed"
                    ], 424);
                }
            }
            //must done all the orders before
            if (
                Order::where('drivers_id', '=', $driver_id)->where('status', '!=', 'closed')
                ->where('status', '!=', 'awaitingDriver')->count() > 0
            ) {
                return response([
                    'status' => 425,
                    'message' => "cannot send the order because you have order not done yet"
                ], 425);
            }

            //must review the last order
            $latestOrder = DB::table('orders')->where('drivers_id', '=', $driver_id)->where('status', '!=', 'awaitingDriver');
            if ($latestOrder->count() > 0) {
                if (!Review::checkDriver($driver_id, $latestOrder->latest('drivers_id')->first()->id)) {
                    $array = [
                        'status' => 426,
                        'message' => "cannot send the order because you haven't rated your last order",
                    ];
                    return response($array, 426);
                }
            }

            //accept the order
            $acceptorder->status = "acceptedByDriver";
            $acceptorder->save();

            //calc financial 
            Financial::calcFinancial($driver_id, $acceptorder->id);

            return response([
                'status' => 200,
                'message' => "successfully accepted"
            ]);
        }
    }


    /** CANCEL ORDER BY DRIVER */

    public function cancelByDriver(Request $request)
    {
        if (auth('driver_api')->check()) {
            $driver_id = auth('driver_api')->user()->id;
            $this->validateOrder($request);

            $acceptorder = Order::where('id', '=', request('order_id'))->where('drivers_id', '=', $driver_id)->first();
            if ($acceptorder != null) {
                if ($acceptorder->status != "acceptedByDriver") {
                    return response([
                        'status' => 427,
                        'message' => "can't cancel the order"
                    ], 427);
                }

                $acceptorder->status = "cancelledByDriver";
                $acceptorder->save();
                Financial::subFinancial($driver_id);

                return response([
                    'status' => 200,
                    'message' => "Successfully canceled"
                ]);
            }
        }
    }

    public function ArrivePickUp(Request $request)
    {
        if (auth('driver_api')->check()) {
            $driver_id = auth('driver_api')->user()->id;

            $this->validateOrder($request);
            $acceptorder = Order::where('id', '=', request('order_id'))->where('drivers_id', '=', $driver_id)->first();
            if ($acceptorder != null) {
                if ($acceptorder->status != "acceptedByDriver") {
                    return response([
                        'status' => 428,
                        'message' => "action can't completed"
                    ], 428);
                }

                $acceptorder->status = "arrivedPickUpLocation";
                $acceptorder->save();
            }
        }
    }

    public function goodsLoading(Request $request)
    {
        if (auth('driver_api')->check()) {
            $driver_id = auth('driver_api')->user()->id;

            $this->validateOrder($request);
            $acceptorder = Order::where('id', '=', request('order_id'))->where('drivers_id', '=', $driver_id)->first();
            if ($acceptorder != null) {
                if ($acceptorder->status != "arrivedPickUpLocation") {
                    return response([
                        'status' => 429,
                        'message' => "action can't completed"
                    ], 429);
                }

                $acceptorder->status = "goodsLoading";
                $acceptorder->save();
            }
        }
    }

    public function startMoving(Request $request)
    {
        if (auth('driver_api')->check()) {
            $driver_id = auth('driver_api')->user()->id;

            $this->validateOrder($request);
            $acceptorder = Order::where('id', '=', request('order_id'))->where('drivers_id', '=', $driver_id)->first();
            if ($acceptorder != null) {
                if ($acceptorder->status != "goodsLoading") {
                    return response([
                        'status' => 430,
                        'message' => "action can't completed"
                    ], 430);
                }

                $acceptorder->status = "startMoving";
                $acceptorder->save();
            }
        }
    }

    public function arriveDestination(Request $request)
    {
        if (auth('driver_api')->check()) {
            $driver_id = auth('driver_api')->user()->id;

            $this->validateOrder($request);
            $acceptorder = Order::where('id', '=', request('order_id'))->where('drivers_id', '=', $driver_id)->first();
            if ($acceptorder != null) {
                if ($acceptorder->status != "startMoving") {
                    return response([
                        'status' => 431,
                        'message' => "action can't completed"
                    ], 431);
                }

                $acceptorder->status = "arrivedToDestinationLocation";
                $acceptorder->save();
            }
        }
    }

    public function driverFinishTrip(Request $request)
    {
        if (auth('driver_api')->check()) {
            $driver_id = auth('driver_api')->user()->id;

            $this->validateOrder($request);
            $acceptorder = Order::where('id', '=', request('order_id'))->where('drivers_id', '=', $driver_id)->first();
            if ($acceptorder != null) {
                if ($acceptorder->status != "arrivedToDestinationLocation") {
                    return response([
                        'status' => 432,
                        'message' => "action can't completed"
                    ], 432);
                }

                $acceptorder->status = "finishedTripByDriver";
                $acceptorder->save();
            }
        }
    }
    public function userFinishTrip(Request $request)
    {
        if (auth('api')->check()) {
            $user_id = auth('api')->user()->id;

            $this->validateOrder($request);
            $acceptorder = Order::where('id', '=', request('order_id'))->where('users_id', '=', $user_id)->first();
            if ($acceptorder != null) {
                if ($acceptorder->status != "finishedTripByDriver") {
                    return response([
                        'status' => 433,
                        'message' => "action can't completed",
                    ], 433);
                }
                $generateCode = rand(0, 99999);
                // Contact::create([
                //     'code' => $generateCode,
                //     'contacts_types_id' => 10,
                //     'users_id' => $user_id,
                //     'drivers_id' => $acceptorder->drivers_id,
                //     'message' => "grgregregerger"
                // ]);
                Session::create([
                    'code' => $generateCode,
                    'users_id' => $user_id,
                    'orders_id' => $acceptorder->id,
                    'drivers_id' => $acceptorder->drivers_id
                ]);

                $acceptorder->status = "fininshedTripByUser";
                $acceptorder->save();
                return response([
                    'status' => 200,
                    'code' => $generateCode
                ]);
            }
        }
    }

    public function paymentType(Request $request)
    {
        if (auth('api')->check()) {
            $validator = Validator::make($request->all(), [
                'order_id' => ['required', 'integer'],
                'type' => ['required', 'string']
            ]);

            if ($validator->fails()) {
                return response([
                    'message' => $validator->errors()->messages()
                ], 422);
            }
            $order = Bill::where('orders_id', '=', $request->order_id)->first();
            $order->update(['payment_type' => $request->type]);

            return response(['status' => 200]);
        }
    }

    public function offlinePayment(Request $request)
    {
        if (auth('api')->check()) {
            $validator = Validator::make($request->all(), [
                'image' => ['required', 'file']
            ]);

            if ($validator->fails()) {
                return response([
                    'message' => $validator->errors()->messages()
                ], 422);
            }
            if (Order::where('users_id', '=', auth('api')->user()->id)->where('status', '=', 'awaitingPayment')->count() > 0) {

                $order = Order::where('users_id', '=', auth('api')->user()->id)->where('status', '=', 'awaitingPayment')
                    ->latest()->first();
            }
            $billID = Bill::where('orders_id', '=', $order->id)->first()->id;
            $code = rand(0, 99999);
            offlinePayment::create([
                'bills_id' => $billID,
                'image_deposit' => request('image')->store('image_deposit'),
                'code' => $code
            ]);

            return response([
                'status' => 200,
                'code' => $code,
                'message' => 'sent successfully'
            ]);
        }
    }

    public function closeTrip(Request $request)
    {
        if (auth('driver_api')->check()) {
            $validator = Validator::make($request->all(), [
                'order_id' => ['required', 'integer'],
                'code' => ['required', 'string']
            ]);

            if ($validator->fails()) {
                return response([
                    'message' => $validator->errors()->messages()
                ], 422);
            }

            if (Order::where('id', '=', $request->order_id)->count() > 0) {
                $order = Order::where('id', '=', $request->order_id)->latest()->first();
                if ($order->status != "fininshedTripByUser")
                    return response([
                        'status' => 434,
                        'message' => "Can't complete the action",
                    ], 434);

                $code = Session::where('drivers_id', '=', auth('driver_api')->user()->id)->latest()->first()->code;
                if ($request->code != $code)
                    return response([
                        'status' => 435,
                        'message' => "code is wrong"
                    ], 435);

                $order->status = "closed";
                $order->save();
                return response([
                    'status' => 200,
                    'message' => "done successfully"
                ]);
            }
        }
    }


    public function rateOrder(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'order_id' => ['required', 'integer'],
            'target_id' => ['required', 'integer'],
            'rate' => ['required', 'numeric']
        ]);

        if ($validator->fails()) {
            return response([
                'message' => $validator->errors()->messages()
            ], 422);
        }
        if (Order::where('id', '=', $request->order_id)->count() > 0) {
            $order = Order::where('id', '=', $request->order_id)->first();


            if (auth('api')->check()) {
                Review::create([
                    'rate' => $request->rate,
                    'type' => "userToDriver",
                    'drivers_id' => $order->drivers_id,
                    'users_id' => $order->users_id,
                    'orders_id' => $request->order_id
                ]);
            }

            if (auth('driver_api')->check()) {
                Review::create([
                    'rate' => $request->rate,
                    'type' => "driverToUser",
                    'drivers_id' => $order->drivers_id,
                    'users_id' => $order->users_id,
                    'orders_id' => $request->order_id
                ]);
            }
            return response([
                'status' => 200,
                'message' => "your rate has been successfully added"
            ]);
        }
    }

    public function getActiveOrder(Request $request){
        if(auth('api')->check()){
            if(Order::where('users_id','=',auth('api')->user()->id)->count() > 0){
               $orders = Order::whereIn('status',['acceptedByDriver','arrivedPickUpLocation','goodsLoading',
               'startMoving','arrivedToDestinationLocation','finishedTripByDriver','fininshedTripByUser',
               'acceptedByCompany'])->get();

               return response([
                   'status'=>200,
                   'orders' => $orders
               ]);
            }
        }
    }
}
