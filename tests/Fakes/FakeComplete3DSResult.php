<?php
namespace Tests\Fakes;

class FakeComplete3DSResult
{
    private string $status;
    private ?string $errorMessage;
    private ?string $errorCode;
    private ?string $paymentId;
    private ?string $cardToken;
    private ?string $cardUserKey;
    private array $paymentItems;

    public function __construct(
        string $status = 'success',
        ?string $errorMessage = null,
        ?string $errorCode = null,
        ?string $paymentId = 'pay_123',
        ?string $cardToken = null,
        ?string $cardUserKey = null,
        array $paymentItems = []
    ) {
        $this->status = $status;
        $this->errorMessage = $errorMessage;
        $this->errorCode = $errorCode;
        $this->paymentId = $paymentId;
        $this->cardToken = $cardToken;
        $this->cardUserKey = $cardUserKey;
        $this->paymentItems = $paymentItems;
    }

    public function getStatus(): string { return $this->status; }
    public function getErrorMessage(): ?string { return $this->errorMessage; }
    public function getErrorCode(): ?string { return $this->errorCode; }
    public function getPaymentId(): ?string { return $this->paymentId; }
    public function getCardToken(): ?string { return $this->cardToken; }
    public function getCardUserKey(): ?string { return $this->cardUserKey; }
    public function getLastFourDigits(): ?string { return '1234'; }
    public function getCardAssociation(): ?string { return 'VISA'; }
    public function getPaymentItems(): array { return $this->paymentItems; }
}