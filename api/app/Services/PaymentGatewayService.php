<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class PaymentGatewayService
{
    private string $baseUrl;

    public function __construct(?string $baseUrl = null)
    {
        $this->baseUrl = $baseUrl ?? 'https://dad-payments-api.vercel.app';
    }

    public function createDebit(string $type, string $reference, int $value): array
    {
        $url = rtrim($this->baseUrl, '/') . '/api/debit';

        try {
            $response = Http::withoutVerifying()->acceptJson()->post($url, [
                'type' => $type,
                'reference' => $reference,
                'value' => $value,
            ]);

            $ok = $response->status() === 201;
            return [
                'ok' => $ok,
                'status' => $response->status(),
                'body' => $response->json(),
            ];
        } catch (\Throwable $e) {
            return [
                'ok' => false,
                'status' => 500,
                'body' => ['error' => 'gateway_unavailable', 'message' => $e->getMessage()],
            ];
        }
    }
}
