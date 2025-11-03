<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;


use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Cart;


use Stripe\Stripe;
use Stripe\Webhook;

class StripeWebhookController extends Controller
{
    public function handle(Request $request){
        Stripe::setApiKey(env('STRIPE_SECRET'));

        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');
        $webhookSecret = env('STRIPE_WEBHOOK_SECRET');

        try {
            $event = Webhook::constructEvent(
                $payload,$sigHeader,$webhookSecret
            );

            if($event->type){
                if($event->type==='payment_intent.succeeded'){
                    $paymentIntent = $event->data->object;
                    $orderId = $paymentIntent->metadata->order_id;

                    $order= Order::find($orderId);
                    $cart = Cart::where('user_id',$order->user_id)->where('is_selected',1)->get();

                    foreach($cart as $items){
                        OrderItem::create([
                            'order_id'=>$orderId,
                            'product_id'=>$items->product_id,
                            'quantity'=>$items->quantity,
                            'price'=>$items->price,
                            'total'=>$items->total
                        ]);
                    }
                    $cart->each->delete();


                    if($order){
                        $order->update([
                            'payment_status'=>'paid',
                            'invoice'=>$paymentIntent->id
                        ]);
                    }
                }
                elseif($event->type==='payment_intent.payment_failed'){
                    $paymentIntent=$event->data->object;
                    $orderId=$paymentIntent->metadata->order_id;
                    
                    $order=Order::find($orderId);
                    if($order){
                        $order->update([
                            'payment_status'=>'failed'
                        ]);
                    }
                }

                return response()->json(['status' => 'success']);


               
            }
        }  catch (\Exception $e) {
            return response()->json(['status' => 'failed', 'message' => $e->getMessage()], 400);
        }
    }
}
