<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use Illuminate\Http\Request;
use Omnipay\Omnipay;

class PaymentController extends Controller
{

    private $gateway;

    public function __construct(){

        $this->gateway = Omnipay::create('PayPal_Rest');
        $this->gateway->setClientId(env('PAYPAL_CLIENT_ID'));
        $this->gateway->setSecret(env('PAYPAL_SECRET_ID'));
        $this->gateway->setTestMode(true);
    }


    public function pay(Request $request){


        try {


           $response = $this->gateway->purchase([

                'amount' => $request->amount,
                'currency' => env('PAYPAL_CURRENCY'),
                'returnurl' => url('success'),
                'cancelurl' => url('error'),


            ])->send();

           if($response->isRedirect())

               $response->redirect();
           else
               return $response->getMessage();


        }catch (\Throwable $exception){


            return $exception->getMessage();

        }
    }

    public function success(Request $request){


        if($request->paymentId && $request->PayerID){

            $transaction = $this->gateway->completePurchase([

                'payer_id' => $request->PayerID,
                'transactionReference' => $request->paymentId,

            ]);

            $response = $transaction->send();

            if($response->isSuccessful()){


                $arr = $response->getData();

                $payment = new Payment();
                $payment->payment_id = $arr['id'];
                $payment->payer_id = $arr['payer']['payer_info']['payer_id'];
                $payment->payer_email = $arr['payer']['payer_info']['email'];
                $payment->amount = $arr['transactions'][0]['amount']['total'];
                $payment->currency = env('PAYPAL_CURRENCY');
                $payment->payment_status = $arr['state'];
                $payment->save();

                return "Your payment transaction successfully your transaction ID is ." . $arr['id'];

            }else{

                return $response->getMessage();
            }
        }else{

            return "Payment is denied";
        }
    }

    public function error(){

        return "User denied to Payment";
    }
}
