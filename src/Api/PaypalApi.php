<?php

declare(strict_types=1);

namespace Vanilo\Paypal\Api;

use PayPalCheckoutSdk\Core\PayPalHttpClient;
use PayPalCheckoutSdk\Core\ProductionEnvironment;
use PayPalCheckoutSdk\Core\SandboxEnvironment;
use PayPalCheckoutSdk\Orders\OrdersCaptureRequest;
use PayPalCheckoutSdk\Orders\OrdersCreateRequest;
use PayPalHttp\HttpResponse;
use Vanilo\Contracts\Address;

class PaypalApi
{
    private PayPalHttpClient $client;

    public function __construct(string $clientId, string $secret, bool $isSandbox)
    {
        $env = $isSandbox ? new SandboxEnvironment($clientId, $secret) : new ProductionEnvironment($clientId, $secret);

        $this->client = new PayPalHttpClient($env);
    }

    public function createOrder(string $currency, float $amount, Address $shippingAddress, string $returnUrl, string $cancelUrl): string
    {
        $orderCreateRequest = new OrdersCreateRequest();
        $orderCreateRequest->prefer('return=representation');
        $orderCreateRequest->body = [
            'intent' => 'CAPTURE',
            'application_context' => [
                'return_url' => $returnUrl,
                'cancel_url' => $cancelUrl,
                'shipping_preference' => 'NO_SHIPPING',
            ],
            'purchase_units' => [
                [
                    'amount' => [
                        'currency_code' => $currency,
                        'value' => number_format($amount, 2, '.', ''),
                    ],
                    // 'shipping' => [
                    //     'address' => [
                    //         'address_line_1' => $shippingAddress->getAddress(),
                    //         'address_line_2' => '',
                    //         'admin_area_2' => $shippingAddress->getCity(),
                    //         'admin_area_1' => '',
                    //         'postal_code' => $shippingAddress->getPostalCode(),
                    //         'country_code' => $shippingAddress->getCountryCode(),
                    //     ],
                    // ],
                ]
            ]
        ];

        \Log::channel('paypal')->debug('PayPalOrdersCreateRequest', $orderCreateRequest->body);

        $response = $this->client->execute($orderCreateRequest);

        return $this->getApproveUrl($response);
    }

    public function captureOrder(string $orderId): HttpResponse
    {
        return $this->client->execute(new OrdersCaptureRequest($orderId));
    }

    private function getApproveUrl(HttpResponse $response): string
    {
        foreach ($response->result->links as $link) {
            if ('approve' == $link->rel) {
                return $link->href;
            }
        }
    }
}
