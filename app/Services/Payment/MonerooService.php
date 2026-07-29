<?php

declare(strict_types=1);

namespace App\Services\Payment;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MonerooService
{
    /**
     * Crée un lien de paiement via l'API Moneroo
     *
     * @param array $data
     * @return array
     * @throws \Exception
     */
    public static function createPaymentLink(array $data): array
    {
        $apiUrl = config('services.moneroo.api_url');
        $secretKey = config('services.moneroo.secret_key');

        if (!$apiUrl || !$secretKey) {
            throw new \Exception('Configuration Moneroo manquante');
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $secretKey,
                'Content-Type' => 'application/json',
            ])->post($apiUrl . '/payments/initialize', [
                'amount' => $data['amount'],
                'currency' => 'XOF',
                'customer' => [
                    'first_name' => $data['customer_name'],
                    'phone' => $data['customer_phone'],
                ],
                'return_url' => $data['return_url'],
                'description' => $data['description'],
                'metadata' => [
                    'reference' => $data['reference'],
                ],
            ]);

            if (!$response->successful()) {
                throw new \Exception('Moneroo API error: ' . $response->body());
            }

            $responseData = $response->json();

            if (!isset($responseData['data']['checkout_url'])) {
                throw new \Exception('Moneroo response invalide: checkout_url manquant');
            }

            return [
                'url' => $responseData['data']['checkout_url'],
            ];
        } catch (\Exception $e) {
            Log::error('Erreur MonerooService: ' . $e->getMessage());
            throw new \Exception('Erreur création lien paiement Moneroo: ' . $e->getMessage());
        }
    }
}
