<?php

declare(strict_types=1);

/**
 * Contains the PaypalPaymentRequest class.
 *
 * @copyright   Copyright (c) 2021 Attila Fulop
 * @author      Attila Fulop
 * @license     MIT
 * @since       2021-03-04
 *
 */

namespace Vanilo\Paypal\Messages;

use Illuminate\Support\Facades\View;
use Vanilo\Payment\Contracts\PaymentRequest;
use Vanilo\Paypal\Api\PaypalApi;
use Vanilo\Paypal\Concerns\HasPaypalConfiguration;
use Vanilo\Contracts\Address;

class PaypalPaymentRequest implements PaymentRequest
{
    use HasPaypalConfiguration;

    private string $paymentId;

    private string $currency;

    private float $amount;

    private Address $shippingAddress;

    private string $view = 'paypal::_request';

    public function getHtmlSnippet(array $options = []): ?string
    {
        $api = new PaypalApi($this->clientId, $this->secret, $this->isSandbox);

        $approveUrl = $api->createOrder($this->currency, $this->amount, $this->shippingAddress, $this->returnUrl, $this->cancelUrl);

        return View::make(
            $this->view,
            [
                'url' => $approveUrl,
                'autoRedirect' => $options['autoRedirect'] ?? false
            ]
        )->render();
    }

    public function willRedirect(): bool
    {
        return true;
    }

    public function setClientId(string $clientId): self
    {
        $this->clientId = $clientId;

        return $this;
    }

    public function setSecret(string $secret): self
    {
        $this->secret = $secret;

        return $this;
    }

    public function setShippingAddress(Address $shippingAddress): self
    {
        $this->shippingAddress = $shippingAddress;

        return $this;
    }

    public function setReturnUrl(string $returnUrl): self
    {
        $this->returnUrl = $returnUrl;

        return $this;
    }

    public function getReturnUrl(): string
    {
        return $this->returnUrl;
    }

    public function setCancelUrl(string $cancelUrl): self
    {
        $this->cancelUrl = $cancelUrl;

        return $this;
    }

    public function getCancelUrl(): string
    {
        return $this->cancelUrl;
    }

    public function setIsSandbox(bool $isSandbox): self
    {
        $this->isSandbox = $isSandbox;

        return $this;
    }

    public function setCurrency(string $currency): self
    {
        $this->currency = $currency;

        return $this;
    }

    public function setAmount(float $amount): self
    {
        $this->amount = $amount;

        return $this;
    }

    public function setView(string $view): self
    {
        $this->view = $view;

        return $this;
    }

    public function getPaymentId(): string
    {
        return $this->paymentId;
    }

    public function setPaymentId(string $paymentId): self
    {
        $this->paymentId = $paymentId;

        return $this;
    }
}
