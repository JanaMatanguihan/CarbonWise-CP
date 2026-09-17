<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class BrevoMailService
{
    public function send(
        string $to,
        string $subject,
        string $htmlContent,
        ?string $toName = null
    ): void {
        $response = Http::withHeaders([
            'accept' => 'application/json',
            'api-key' => config('services.brevo.api_key'),
            'content-type' => 'application/json',
        ])
        ->timeout(15)
        ->post(config('services.brevo.api_url') . '/smtp/email', [
            'sender' => [
                'email' => config('services.brevo.from_email'),
                'name' => config('services.brevo.from_name'),
            ],
            'to' => [
                [
                    'email' => $to,
                    'name' => $toName,
                ],
            ],
            'subject' => $subject,
            'htmlContent' => $htmlContent,
        ]);

        if ($response->failed()) {
            throw new RuntimeException(
                'Brevo API error: ' . $response->body()
            );
        }
    }
}