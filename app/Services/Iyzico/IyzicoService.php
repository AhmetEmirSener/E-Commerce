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


    public function initialize3DS(array $data):ThreedsInitialize{
        $request = new CreatePaymentRequest();
        $request->setlocale('tr');
        $request->setConversationId((string) $data['order_id']);
        $request->setPrice($data['total']);
        $request->setPaidPrice($data['paidPrice']);
        $request->setCurrency('TRY');
        $request->setInstallment(1);
        $request->setBasketId((string) $data['order_id']);
        $request->setPaymentChannel('WEB');
        $request->setPaymentGroup('PRODUCT');
        $request->setCallbackUrl(config('app.url') . '/api/payment/callback');

        $paymentCard = new PaymentCard();
        $paymentCard->setCardHolderName($data['card']['holder_name']);
        $paymentCard->setCardNumber($data['card']['number']);
        $paymentCard->setExpireMonth($data['card']['expire_month']);
        $paymentCard->setExpireYear($data['card']['expire_year']);
        $paymentCard->setCvc($data['card']['cvc']);
        $paymentCard->setRegisterCard($data['save_card'] ? 1 : 0);
        
        $request->setPaymentCard($paymentCard);


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

        $request->setInstallment($data['installment'] ?? 1);


        return ThreedsInitialize::create($request, $this->options);

    }

    public function initialize3DSWithToken(array $data): ThreedsInitialize
{
    $request = new CreatePaymentRequest();
    $request->setLocale('tr');
    $request->setConversationId((string) $data['order_id']);
    $request->setPrice($data['total']);
    $request->setPaidPrice($data['paidPrice']);
    $request->setCurrency('TRY');
    $request->setInstallment($data['installment'] ?? 1);
    $request->setBasketId((string) $data['order_id']);
    $request->setPaymentChannel('WEB');
    $request->setPaymentGroup('PRODUCT');
    $request->setCallbackUrl(config('app.url') . '/api/payment/callback');

    // 💳 SAVED CARD TOKEN
    $paymentCard = new PaymentCard();
    $paymentCard->setCardUserKey($data['card_user_key']);
    $paymentCard->setCardToken($data['card_token']);
    $request->setPaymentCard($paymentCard);

    // 👤 BUYER
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

    // 📦 SHIPPING
    $shippingAddress = new Address();
    $shippingAddress->setContactName($data['user']['name'] . ' ' . $data['user']['surname']);
    $shippingAddress->setCity($data['user']['city']);
    $shippingAddress->setCountry('Turkey');
    $shippingAddress->setAddress($data['user']['address']);
    $request->setShippingAddress($shippingAddress);

    // 🧾 BILLING
    $billingAddress = new Address();
    $billingAddress->setContactName($data['user']['name'] . ' ' . $data['user']['surname']);
    $billingAddress->setCity($data['user']['city']);
    $billingAddress->setCountry('Turkey');
    $billingAddress->setAddress($data['user']['address']);
    $request->setBillingAddress($billingAddress);

    // 🛒 ITEMS
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

}
