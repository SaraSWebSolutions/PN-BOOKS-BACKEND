<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PaymentWebhookLog;
use App\Models\Transaction;
use App\Services\PaymentGatewayInterface;
use Illuminate\Http\Request;

class PaymentWebhookController extends Controller
{
    public function __construct(protected PaymentGatewayInterface $gateway)
    {
    }

    /**
     * POST /api/webhooks/atome
     * Public route — NOT behind auth:sanctum. Protected by signature verification instead.
     */
    public function handle(Request $request)
    {
        $payload = $request->getContent();
        $isValid = $this->gateway->verifyWebhookSignature($payload, $request->headers->all());

        $log = PaymentWebhookLog::create([
            'gateway'         => 'atome',
            'event_type'      => $request->input('name') ?? $request->input('type'),
            'payload'         => $request->all(),
            'signature_valid' => $isValid,
            'processed'       => false,
        ]);

        if (! $isValid) {
            return response()->json(['status' => 'error', 'message' => 'Invalid signature'], 401);
        }

        $intentId = data_get($request->all(), 'data.object.id')
            ?? data_get($request->all(), 'payment_intent_id');

        $transaction = Transaction::where('gateway_intent_id', $intentId)->first();

        if (! $transaction) {
            return response()->json(['status' => 'error', 'message' => 'Unknown transaction'], 404);
        }

        // Don't trust the webhook body alone — re-query the gateway to confirm true status.
        $verified = $this->gateway->queryStatus($intentId);

        $transaction->update([
            'status'           => $this->mapStatus($verified['status']),
            'callback_payload' => $request->all(),
            'raw_response'     => $verified['raw'],
        ]);

        $order = $transaction->order;

        if (in_array($verified['status'], ['SUCCEEDED', 'succeeded', 'captured'])) {
            $order->update(['payment_status' => 'paid', 'status' => 'processing']);
        } elseif (in_array($verified['status'], ['FAILED', 'failed', 'CANCELLED'])) {
            $order->update(['payment_status' => 'failed', 'status' => 'failed']);
        }

        $log->update(['processed' => true]);

        return response()->json(['status' => 'success']);
    }

    protected function mapStatus(string $gatewayStatus): string
    {
        return match (strtolower($gatewayStatus)) {
            'succeeded', 'captured' => 'captured',
            'requires_capture', 'authorized' => 'authorized',
            'failed' => 'failed',
            'cancelled' => 'cancelled',
            default => 'pending',
        };
    }
}