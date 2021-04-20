<?php

declare(strict_types=1);

namespace Vanilo\Paypal\Api;

use PayPalCheckoutSdk\Core\PayPalHttpClient;
use PayPalCheckoutSdk\Core\ProductionEnvironment;
use PayPalCheckoutSdk\Core\SandboxEnvironment;
use PayPalCheckoutSdk\Orders\OrdersCaptureRequest;
use PayPalCheckoutSdk\Orders\OrdersCreateRequest;
use PayPalHttp\HttpResponse;

class PaypalApi
{
    private PayPalHttpClient $client;

    public function __construct(string $clientId, string $secret, bool $isSandbox)
    {
        $env = $isSandbox ? new SandboxEnvironment($clientId, $secret) : new ProductionEnvironment($clientId, $secret);

        $this->client = new PayPalHttpClient($env);
    }

    public function createOrder(string $currency, float $amount, string $returnUrl, string $cancelUrl): string
    {
        $orderCreateRequest = new OrdersCreateRequest();
        $orderCreateRequest->prefer('return=representation');
        $orderCreateRequest->body = [
            'intent' => 'CAPTURE',
            'application_context' => [
                'return_url' => $returnUrl,
                'cancel_url' => $cancelUrl
            ],
            'purchase_units' => [
                [
                    'amount' => [
                        'currency_code' => $currency,
                        'value' => $amount
                    ],
                    // 'shipping' => [
                    //     'address' => [
                    //         'address_line_1' => '123 Townsend St',
                    //         'address_line_2' => 'Floor 6',
                    //         'admin_area_2' => 'San Francisco',
                    //         'admin_area_1' => 'CA',
                    //         'postal_code' => '94107',
                    //         'country_code' => 'US',
                    //     ],
                    // ],
                ]
            ]
        ];

        \Log::channel('paypal')->debug('PaypalApi->createOrder', ['amount'=>$amount]);
        \Log::channel('paypal')->debug('PaypalApi->createOrder(rounded)', ['amount'=>round($amount, 2)]);
        \Log::channel('paypal')->debug('PaypalApi->createOrder(number_format)', ['amount'=>number_format($amount, 2, '.', '')]);

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
