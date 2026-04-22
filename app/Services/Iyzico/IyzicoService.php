<?php

namespace App\Services\Iyzico;
use Iyzipay\Options;
use Iyzipay\Request\CreateCheckoutFormInitializeRequest;
use Iyzipay\Model\CheckoutFormInitialize;
use Iyzipay\Model\Buyer;
use Iyzipay\Model\Address;
use Iyzipay\Model\BasketItem;
use Iyzipay\Model\BasketItemType;
use Iyzipay\Model\ThreedsInitialize;
use Iyzipay\Request\CreatePaymentRequest;
use Iyzipay\Model\PaymentCard;
use Iyzipay\Model\ThreedsPayment;

use Iyzipay\Request\CreateThreedsPaymentRequest;
use Iyzipay\Model\InstallmentInfo;
use Iyzipay\Request\RetrieveInstallmentInfoRequest;
use Iyzipay\Model\Card;
use Iyzipay\Request\DeleteCardRequest;

use Iyzipay\Request\CreateCancelRequest;
use Iyzipay\Request\CreateRefundRequest;

use Iyzipay\Model\Cancel;
use Iyzipay\Model\Refund;


class IyzicoService
{
    protected Options $options;

    public function __construct()
    {   
        $this->options = new Options();
        $this->options->setApiKey(config('iyzico.api_key'));
        $this->options->setSecretKey(config('iyzico.secret_key'));
        $this->options->setBaseUrl(config('iyzico.base_url'));

        $this->options->SSLVerifyPeer = false;

    }

    public function getOptions():Options{
        
        return $this->options;
    }


    public function initializeCheckoutForm(array $data):CheckoutFormInitialize
    {
        
        $request = new CreateCheckoutFormInitializeRequest();
        $request->setlocale('tr');
        $request->setConversationId((string) $data['order_id']);
        $request->setPrice($data['total']);
        $request->setPaidPrice($data['total']);
        $request->setCurrency('TRY');
        $request->setBasketId((string) $data['order_id']);
        $request->setPaymentGroup('PRODUCT');
        $request->setCallbackUrl(config('app.url') . '/api/payment/callback');
        $request->setEnabledInstallments([1, 2, 3, 6, 9, 12]);

        $buyer = new Buyer();
        $buyer->setId((string) $data['user']['id']);
        $buyer->setName($data['user']['name']);
        $buyer->setSurname($data['user']['surname']);
        $buyer->setEmail($data['user']['email']);
        $buyer->setIdentityNumber('11111111111');
        $buyer->setRegistrationAddress($data['user']['address']);
        $buyer->setCity($data['user']['city']);
        $buyer->setCountry('Turkey');
        $buyer->setIp($data['ip']);
        $request->setBuyer($buyer);

        $shippingAddress = new Address();
        $shippingAddress->setContactName($data['user']['name'] . ' ' . $data['user']['surname']);
        $shippingAddress->setCity($data['user']['city']);
        $shippingAddress->setCountry('Turkey');
        $shippingAddress->setAddress($data['user']['address']);
        $request->setShippingAddress($shippingAddress);

        $billingAddress = new Address();
        $billingAddress->setContactName($data['user']['name'] . ' ' . $data['user']['surname']);
        $billingAddress->setCity($data['user']['city']);
        $billingAddress->setCountry('Turkey');
        $billingAddress->setAddress($data['user']['address']);
        $request->setBillingAddress($billingAddress);

        $basketItems = [];
        foreach ($data['items'] as $item) {
            $basketItem = new BasketItem();
            $basketItem->setId((string) $item['id']);
            $basketItem->setName($item['product']['name']);
            $basketItem->setCategory1($item['product']['category_id']);
            $basketItem->setItemType(BasketItemType::PHYSICAL);
            $basketItem->setPrice($item['total']);
            $basketItems[] = $basketItem;
        }
        $request->setBasketItems($basketItems);

        return CheckoutFormInitialize::create($request, $this->options);

    }   



    public function buildBaseRequest($data){
        $request = new CreatePaymentRequest();
        $request->setlocale('tr');
        $request->setConversationId((string) $data['order_id']);
        $request->setPrice($data['total']);
        $request->setPaidPrice($data['paidPrice']);
        $request->setCurrency('TRY');
        $request->setInstallment($data['installment'] ?? 1);
        $request->setBasketId((string) $data['order_id']);
        $request->setPaymentChannel('WEB');
        $request->setPaymentGroup('PRODUCT');
        $request->setCallbackUrl(config('app.url') . '/api/payment/callback');

        return $request;
    }

    public function buildBuyerRequest($request,$data){
        $buyer = new Buyer();
        $buyer->setId((string) $data['user']['id']);
        $buyer->setName($data['user']['name']);
        $buyer->setSurname($data['user']['surname']);
        $buyer->setEmail($data['user']['email']);
        $buyer->setIdentityNumber('11111111111');
        $buyer->setRegistrationAddress($data['user']['address']);
        $buyer->setCity($data['user']['city']);
        $buyer->setCountry('Turkey');
        $buyer->setIp($data['ip']);

        $request->setBuyer($buyer);

        return $request;

    }

    public function buildAddressRequest($request,$data){
        
        $shippingAddress = new Address();
        $shippingAddress->setContactName($data['user']['name'] . ' ' . $data['user']['surname']);
        $shippingAddress->setCity($data['user']['city']);
        $shippingAddress->setCountry('Turkey');
        $shippingAddress->setAddress($data['user']['address']);

        $request->setShippingAddress($shippingAddress);

        $billingAddress = new Address();
        $billingAddress->setContactName($data['user']['name'] . ' ' . $data['user']['surname']);
        $billingAddress->setCity($data['user']['city']);
        $billingAddress->setCountry('Turkey');
        $billingAddress->setAddress($data['user']['address']);

        $request->setBillingAddress($billingAddress);

        return $request;

    }

    public function buildBasketItemsRequest($request,$data){
        $basketItems = [];
        foreach ($data['items'] as $item) {
            $basketItem = new BasketItem();
            $basketItem->setId((string) $item['id']);
            $basketItem->setName($item['product']['name']);
            $basketItem->setCategory1($item['product']['category_id']);
            $basketItem->setItemType(BasketItemType::PHYSICAL);
            $basketItem->setPrice($item['total']);
            $basketItems[] = $basketItem;
        }
        $request->setBasketItems($basketItems);

        return $request;

    }

    public function handleRequests($data){
        $request= $this->buildBaseRequest($data);

        $this->buildBuyerRequest($request,$data);
        $this->buildAddressRequest($request,$data);
        $this->buildBasketItemsRequest($request,$data);

        return $request;
    }

    public function initialize3DS(array $data):ThreedsInitialize{
        $request= $this->handleRequests($data);

        $paymentCard = new PaymentCard();
        $paymentCard->setCardHolderName($data['card']['holder_name']);
        $paymentCard->setCardNumber($data['card']['number']);
        $paymentCard->setExpireMonth($data['card']['expire_month']);
        $paymentCard->setExpireYear($data['card']['expire_year']);
        $paymentCard->setCvc($data['card']['cvc']);
        $paymentCard->setRegisterCard($data['save_card'] ? 1 : 0);
        
        $request->setPaymentCard($paymentCard);

        return ThreedsInitialize::create($request, $this->options);

    }

    public function initialize3DSWithToken(array $data): ThreedsInitialize{
        $request= $this->handleRequests($data);
        
        $paymentCard = new PaymentCard();
        $paymentCard->setCardUserKey($data['card_user_key']);
        $paymentCard->setCardToken($data['card_token']);
        $request->setPaymentCard($paymentCard);

    

        return ThreedsInitialize::create($request, $this->options);
    }



    public function complete3DS(string $paymentId, string $conversationId):ThreedsPayment{
        $request = new CreateThreedsPaymentRequest();
        $request->setlocale('tr');
        $request->setConversationId($conversationId);
        $request->setPaymentId($paymentId);

        return ThreedsPayment::create($request, $this->options);

    }


    public function getInstallments(string $binNumber, float $price):InstallmentInfo{
        $request= new RetrieveInstallmentInfoRequest();
        $request->setLocale('tr');
        $request->setBinNumber($binNumber);
        $request->setPrice($price);

        return InstallmentInfo::retrieve($request, $this->options);
    }


    public function getPaidPrice(string $binNumber,float $price,int $installment):float {
        $info = $this->getInstallments($binNumber, $price);

        $prices = $info->getInstallmentDetails()[0]->getInstallmentPrices();

        foreach($prices as $item){
            if($item->getInstallmentNumber() === $installment){
                return (float) $item->getTotalPrice();
            }
        }

        return $price;
    }


    public function deleteSavedCard(string $cardUserKey, string $cardToken){
        $request = new DeleteCardRequest();
        $request->setLocale('tr');
        $request->setConversationId(uniqid());
        $request->setCardUserKey($cardUserKey);
        $request->setCardToken($cardToken);

        return Card::delete($request,$this->options);
    }



    public function cancelPayment($paymentId,$ip){

        $request = new CreateCancelRequest();
        $request->setLocale('tr');
        $request->setPaymentId($paymentId); 
        $request->setIp($ip);

        $result = Cancel::create($request, $this->options);

        return $result;
    }

    public function refundPayment(string $paymentTransactionId, float $price, string $ip):Refund{
        $request = new CreateRefundRequest();
        $request->setLocale('tr');
        $request->setPaymentTransactionId($paymentTransactionId); 
        $request->setPrice($price);
        $request->setCurrency('TRY');
        $request->setIp($ip);

        return Refund::create($request,$this->options);
    }




    public function handleInstallments($result,float $total){
        $installments=[];
        
        if ($result->getStatus() !== 'success' || empty($result->getInstallmentDetails())) {
            return $installments;
        }

        foreach($result->getInstallmentDetails() as $detail){
            foreach($detail->getInstallmentPrices() as $installmentPrice){
                $installments[]=[
                    'installment'=> $installmentPrice->getInstallmentNumber(),
                    'installment_price'=>$installmentPrice->getInstallmentPrice(),
                    'installment_diff'=>round($installmentPrice->getTotalPrice() - $total,2),
                    'total_price'=>$installmentPrice->getTotalPrice()
                ];
            }
        }

        return $installments;
    }
}
