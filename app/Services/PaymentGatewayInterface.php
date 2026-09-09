<?php

namespace App\Services;

use App\Models\Order;

interface PaymentGatewayInterface
{
    /**
     * Create a payment/checkout session for an order.
     * Returns ['intent_id' => ..., 'checkout_url' => ..., 'raw' => [...]]
     */
    public function createCheckout(Order $order, string $returnUrl, string $cancelUrl): array;

    /**
     * Query the current status of a previously created intent.
     * Returns ['status' => 'authorized'|'captured'|'failed'|..., 'raw' => [...]]
     */
    public function queryStatus(string $intentId): array;

    /**
     * Capture a previously authorized payment (some aggregators auto-capture; implement as no-op if so).
     */
    public function capture(string $intentId, float $amount): array;

    /**
     * Refund a captured payment, full or partial.
     */
    public function refund(string $paymentId, float $amount): array;

    /**
     * Verify an inbound webhook's signature. Return true/false.
     */
    public function verifyWebhookSignature(string $payload, array $headers): bool;
}