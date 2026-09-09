<?php
namespace App\Notifications;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class LeadFollowupWhatsapp
{
    protected $token         = 'EAAIPXRTHZCvgBQ4UYdS1MOqdNsLXUsq6xjNcqjFrXQmTGINHawyQ205x5rwKNkE65ZCGQZAf9FD63DqyG7fHI1Vwyn6A34anEtCyrcwd6Czf4MiEieouB2Ih3OZCMMTGPAvmz6cE3ZAqTwXWKyRMYvGeYEaPUpNmMs4ZC9AmZB5ochJ0Q6vAGZCYL2erDjh66t0lGAZDZD';
    protected $phoneNumberId = '574569059079993';

    /**
     * Send lead follow-up reminder WhatsApp notification
     *
     * @param string $mobile      - e.g. 919876543210 (country code, no +)
     * @param string $userName    - {{1}} Telecaller / Sales person name
     * @param string $leadName    - {{2}} Lead name
     * @param string $leadPhone   - {{3}} Lead phone number
     * @param string $followUpDate- {{4}} Follow-up date (e.g. 15 Jul 2026)
     */
    public function send($mobile, $userName, $leadName, $leadPhone, $followUpDate)
    {
        $url = "https://graph.facebook.com/v17.0/{$this->phoneNumberId}/messages";

        $payload = [
            'messaging_product' => 'whatsapp',
            'to'                => $mobile,
            'type'              => 'template',
            'template'          => [
                'name'     => 'lead_followup_reminder', // ← create this template in WhatsApp Manager
                'language' => [
                    'code' => 'en_IN'
                ],
                'components' => [
                    [
                        'type'       => 'body',
                        'parameters' => [
                            ['type' => 'text', 'text' => (string) $userName],
                            ['type' => 'text', 'text' => (string) $leadName],
                            ['type' => 'text', 'text' => (string) $leadPhone],
                            ['type' => 'text', 'text' => (string) $followUpDate],
                        ]
                    ]
                ]
            ]
        ];

        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$this->token}",
                'Content-Type'  => 'application/json'
            ])->post($url, $payload);

            if ($response->successful()) {
                Log::info("Lead Followup WhatsApp sent successfully to {$mobile}");
                return true;
            } else {
                Log::error("Lead Followup WhatsApp API Error for {$mobile}: " . $response->body());
                return false;
            }
        } catch (\Exception $e) {
            Log::error("Lead Followup WhatsApp Exception for {$mobile}: " . $e->getMessage());
            return false;
        }
    }
}