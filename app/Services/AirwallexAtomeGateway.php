<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Atome (BNPL) accessed through Airwallex's Payment Acceptance API.
 *
 * ⚠️ IMPORTANT — verify against your live Airwallex dashboard/docs before going to production:
 *   - Base URL differs for demo vs production (api-demo.airwallex.com vs api.airwallex.com).
 *   - Exact request/response field names can change; this mirrors Airwallex's
 *     publicly documented PaymentIntent + Atome redirect flow as of writing.
 *   - You need: AIRWALLEX_CLIENT_ID, AIRWALLEX_API_KEY, AIRWALLEX_BASE_URL in .env
 *
 * Flow:
 *   1. login()               -> bearer token (cached ~28 min, tokens expire in 30)
 *   2. createCheckout()      -> creates PaymentIntent, confirms with payment_method.type = atome,
 *                                Airwallex returns a redirect URL to Atome's hosted auth page.
 *   3. Customer pays on Atome, gets redirected back to $returnUrl.
 *   4. Airwallex sends a webhook (payment_intent.succeeded / .failed) -> handled in
 *      PaymentWebhookController, which calls queryStatus() to double-confirm before
 *      trusting the webhook.
 */
class AirwallexAtomeGateway implements PaymentGatewayInterface
{
    protected string $baseUrl;
    protected string $clientId;
    protected string $apiKey;

    public function __construct()
    {
        $this->baseUrl  = rtrim(config('services.airwallex.base_url'), '/');
        $this->clientId = config('services.airwallex.client_id');
        $this->apiKey   = config('services.airwallex.api_key');
    }

    protected function token(): string
    {
        return Cache::remember('airwallex_access_token', 1680, function () {
            $res = Http::withHeaders([
                'x-client-id' => $this->clientId,
                'x-api-key'   => $this->apiKey,
            ])->post("{$this->baseUrl}/api/v1/authentication/login");

            $res->throw();

            return $res->json('token');
        });
    }

    public function createCheckout(Order $order, string $returnUrl, string $cancelUrl): array
    {
        $payload = [
            'request_id'        => (string) Str::uuid(),
            'amount'             => (float) $order->total_amount,
            'currency'           => $order->currency->code ?? 'SGD',
            'merchant_order_id'  => $order->order_number,
            'order' => [
                'products' => $order->items->map(fn ($i) => [
                    'name'     => $i->title,
                    'quantity' => $i->quantity,
                    'unit_price' => (float) $i->unit_price,
                ])->values()->all(),
                'shipping' => $order->shippingAddress ? [
                    'first_name'  => $order->shippingAddress->full_name,
                    'phone_number'=> $order->shippingAddress->phone,
                    'address' => [
                        'city'        => $order->shippingAddress->city,
                        'country_code'=> optional($order->shippingAddress->country)->iso2 ?? 'SG',
                        'street'      => $order->shippingAddress->address_line1,
                        'postcode'    => $order->shippingAddress->postal_code,
                    ],
                ] : null,
            ],
            'return_url' => $returnUrl,
        ];

        $create = Http::withToken($this->token())
            ->post("{$this->baseUrl}/api/v1/pa/payment_intents/create", $payload);

        $create->throw();
        $intent = $create->json();

        $confirm = Http::withToken($this->token())
            ->post("{$this->baseUrl}/api/v1/pa/payment_intents/{$intent['id']}/confirm", [
                'request_id'     => (string) Str::uuid(),
                'payment_method' => ['type' => 'atome'],
                'return_url'     => $returnUrl,
            ]);

        $confirm->throw();
        $confirmed = $confirm->json();

        return [
            'intent_id'    => $intent['id'],
            'checkout_url' => $confirmed['next_action']['url'] ?? null, // redirect target
            'raw'          => ['create' => $intent, 'confirm' => $confirmed],
        ];
    }

    public function queryStatus(string $intentId): array
    {
        $res = Http::withToken($this->token())
            ->get("{$this->baseUrl}/api/v1/pa/payment_intents/{$intentId}");

        $res->throw();
        $data = $res->json();

        return ['status' => $data['status'] ?? 'unknown', 'raw' => $data];
    }

    public function capture(string $intentId, float $amount): array
    {
        // Atome via Airwallex auto-captures on success; explicit capture usually not required.
        // Kept for interface parity / future card-gateway swap.
        return ['status' => 'captured', 'raw' => []];
    }

    public function refund(string $paymentId, float $amount): array
    {
        $res = Http::withToken($this->token())
            ->post("{$this->baseUrl}/api/v1/pa/refunds/create", [
                'request_id'     => (string) Str::uuid(),
                'payment_intent_id' => $paymentId,
                'amount'         => $amount,
            ]);

        $res->throw();

        return ['status' => 'refunded', 'raw' => $res->json()];
    }

    public function verifyWebhookSignature(string $payload, array $headers): bool
    {
        $signature = $headers['x-signature'][0] ?? null;
        $timestamp = $headers['x-timestamp'][0] ?? null;
        if (! $signature || ! $timestamp) {
            return false;
        }

        $expected = hash_hmac('sha256', $timestamp . $payload, config('services.airwallex.webhook_secret'));

        return hash_equals($expected, $signature);
    }
}