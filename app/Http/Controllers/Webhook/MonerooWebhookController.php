<?php

declare(strict_types=1);

namespace App\Http\Controllers\Webhook;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Transaction;
use App\Models\Wallet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MonerooWebhookController extends Controller
{
    /**
     * Reçoit les webhooks de Moneroo pour confirmer les paiements
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function handle(Request $request): \Illuminate\Http\JsonResponse
    {
        // Récupérer le payload
        $payload = $request->getContent();
        $signature = $request->header('X-Moneroo-Signature');
        $webhookSecret = config('services.moneroo.webhook_secret');

        // Vérifier la signature
        if (!$webhookSecret) {
            Log::error('Moneroo webhook secret non configuré');
            return response()->json(['error' => 'Configuration manquante'], 500);
        }

        $expectedSignature = hash_hmac('sha256', $payload, $webhookSecret);

        if (!hash_equals($expectedSignature, $signature)) {
            Log::warning('Signature Moneroo invalide');
            return response()->json(['error' => 'Signature invalide'], 403);
        }

        $data = $request->json()->all();

        // Vérifier l'événement de paiement réussi
        if (!isset($data['event']) || $data['event'] !== 'payment.success') {
            Log::info('Événement Moneroo ignoré: ' . ($data['event'] ?? 'inconnu'));
            return response()->json(['ok' => true]);
        }

        // Récupérer la référence depuis les métadonnées
        $reference = $data['metadata']['reference'] ?? null;

        if (!$reference) {
            Log::error('Référence manquante dans le webhook Moneroo');
            return response()->json(['error' => 'Référence manquante'], 400);
        }

        return DB::transaction(function () use ($reference, $data) {
            // Chercher la commande par référence
            $order = Order::where('reference', $reference)->first();

            if (!$order) {
                Log::error('Commande non trouvée pour la référence: ' . $reference);
                return response()->json(['error' => 'Commande non trouvée'], 404);
            }

            // Vérifier si déjà payée
            if ($order->payment_status === 'paid') {
                Log::info('Commande déjà payée: ' . $reference);
                return response()->json(['ok' => true]);
            }

            // Mettre à jour le statut de paiement
            $order->update([
                'payment_status' => 'paid',
                'payment_gateway_ref' => $data['data']['id'] ?? null,
            ]);

            // Charger le wallet du vendeur
            $wallet = Wallet::where('user_id', $order->vendor_id)->first();

            if (!$wallet) {
                Log::error('Wallet non trouvé pour le vendeur: ' . $order->vendor_id);
                return response()->json(['error' => 'Wallet non trouvé'], 404);
            }

            // Créer l'escrow dans le wallet du vendeur
            $wallet->increment('escrow_balance', $order->vendor_earnings);

            // Créer une Transaction d'audit
            Transaction::create([
                'user_id' => $order->vendor_id,
                'type' => 'escrow_in',
                'amount' => $order->vendor_earnings,
                'description' => 'Escrow commande ' . $order->reference,
                'reference' => $order->reference,
                'status' => 'completed',
            ]);

            Log::info('Paiement Moneroo confirmé: ' . $reference . ', montant: ' . $order->vendor_earnings);

            return response()->json(['ok' => true]);
        });
    }
}
