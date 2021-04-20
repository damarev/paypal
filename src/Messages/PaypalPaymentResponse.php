<?php

declare(strict_types=1);

/**
 * Contains the PaypalPaymentResponse class.
 *
 * @copyright   Copyright (c) 2021 Attila Fulop
 * @author      Attila Fulop
 * @license     MIT
 * @since       2021-03-04
 *
 */

namespace Vanilo\Paypal\Messages;

use Illuminate\Http\Request;
use Konekt\Enum\Enum;
use Vanilo\Payment\Contracts\PaymentResponse;
use Vanilo\Payment\Contracts\PaymentStatus;
use Vanilo\Payment\Models\PaymentStatusProxy;
use Vanilo\Paypal\Api\PaypalApi;
use Vanilo\Paypal\Concerns\HasPaypalCredentials;

use Vanilo\Paypal\Models\PayPalPaymentStatus;


class PaypalPaymentResponse implements PaymentResponse
{
    use HasPaypalCredentials;

    private Request $request;

    private string $paymentId;

    private PaymentStatus $status;
    private PayPalPaymentStatus $nativeStatus;

    private ?float $amountPaid = null;

    public function __construct(Request $request, string $clientId, string $secret, bool $isSandbox)
    {
        $this->request = $request;
        $this->clientId = $clientId;
        $this->secret = $secret;
        $this->isSandbox = $isSandbox;

        $this->capture();
    }

    public function wasSuccessful(): bool
    {
        return $this->nativeStatus->equals(PayPalPaymentStatus::COMPLETED());
    }

    public function getMessage(): string
    {
        return $this->status->label();
    }

    public function getTransactionId(): ?string
    {
        return $this->paymentId;
    }

    public function getAmountPaid(): ?float
    {
        return $this->amountPaid;
    }

    public function getPaymentId(): string
    {
        return $this->paymentId;
    }

    private function capture(): void
    {
        $this->paymentId = $this->request->get('paymentId', '');
        $token = $this->request->token;

        $captureResponse = (new PaypalApi($this->clientId, $this->secret, $this->isSandbox))->captureOrder($token);

        $this->nativeStatus = new PayPalPaymentStatus($captureResponse->result->status);

        if ($this->wasSuccessful()) {
            $this->status = PaymentStatusProxy::AUTHORIZED();
        } else {
            $this->status = PaymentStatusProxy::DECLINED();
        }

        $this->amountPaid = floatval($captureResponse->result->purchase_units[0]->payments->captures[0]->amount->value);
    }

    public function getStatus(): PaymentStatus
    {
        return $this->status;
    }

    public function getNativeStatus(): PayPalPaymentStatus
    {
        return $this->nativeStatus;
    }
}
