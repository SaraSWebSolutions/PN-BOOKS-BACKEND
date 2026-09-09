<?php
namespace App\Notifications;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SamyDemoWhatsapp
{
    protected $token         = 'EAAIPXRTHZCvgBQ4UYdS1MOqdNsLXUsq6xjNcqjFrXQmTGINHawyQ205x5rwKNkE65ZCGQZAf9FD63DqyG7fHI1Vwyn6A34anEtCyrcwd6Czf4MiEieouB2Ih3OZCMMTGPAvmz6cE3ZAqTwXWKyRMYvGeYEaPUpNmMs4ZC9AmZB5ochJ0Q6vAGZCYL2erDjh66t0lGAZDZD';
    protected $phoneNumberId = '574569059079993';

    /**
     * Send "samy_demo" WhatsApp template (Tamil) — Saamy Elite Promoters follow-up reminder
     *
     * @param string $mobile - e.g. 919876543210 (country code, no +)
     */
    public function send($mobile)
    {
        $url = "https://graph.facebook.com/v17.0/{$this->phoneNumberId}/messages";

        $payload = [
            'messaging_product' => 'whatsapp',
            'to'                => $mobile,
            'type'              => 'template',
            'template'          => [
                'name'     => 'samy_demo',
                'language' => [
                    'code' => 'ta'
                ],
                // No components needed — body has no {{n}} placeholders
                // and the "Contact Us" button is a static call button
                // baked into the approved template.
            ]
        ];

        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$this->token}",
                'Content-Type'  => 'application/json'
            ])->post($url, $payload);

            if ($response->successful()) {
                Log::info("Samy Demo WhatsApp sent successfully to {$mobile}");
                return ['ok' => true, 'body' => $response->json()];
            } else {
                Log::error("Samy Demo WhatsApp API Error for {$mobile}: " . $response->body());
                return ['ok' => false, 'body' => $response->json() ?? $response->body()];
            }
        } catch (\Exception $e) {
            Log::error("Samy Demo WhatsApp Exception for {$mobile}: " . $e->getMessage());
            return ['ok' => false, 'body' => $e->getMessage()];
        }
    }
}