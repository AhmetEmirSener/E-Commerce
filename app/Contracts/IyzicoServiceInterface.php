<?php

namespace App\Contracts;

interface IyzicoServiceInterface
{
    public function complete3DS(string $paymentId, string $conversationId);
    public function cancelPayment(string $paymentId, string $ip);
    public function deleteSavedCard(string $cardUserKey, string $cardToken);
    public function initialize3DSWithToken(array $data);
}