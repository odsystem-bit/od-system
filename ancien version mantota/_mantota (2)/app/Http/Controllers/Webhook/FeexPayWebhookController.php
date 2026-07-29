<?php

declare(strict_types=1);

namespace App\Http\Controllers\Webhook;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Payment\DepositController;
use App\Http\Controllers\Public\CheckoutController;
use App\Models\Order;
use App\Models\Transaction;
use App\Services\SecurityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * FeexPayWebhookController — Reçoit les callbacks de paiement FeexPay.
 *
 * FeexPay envoie un callback avec la référence et le statut du paiement.
 */
class FeexPayWebhookController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $reference = $request->input('reference', $request->input('ref', ''));

        if (! $reference) {
            SecurityService::log('webhook_suspicious', null, [
                'reason' => 'Missing reference',
                'payload' => $request->except(['password', 'token']),
            ]);
            return response()->json(['message' => 'Missing reference'], 200);
        }

        // Les méthodes verifyAndCredit* vérifient le statut via l'API FeexPay
        // Chercher d'abord un dépôt, puis une commande
        $transaction = Transaction::where('gateway_ref', $reference)
            ->where('status', 'pending')
            ->first();

        if ($transaction) {
            DepositController::verifyAndCreditFeexPay($transaction);
            return response()->json(['message' => 'OK'], 200);
        }

        $order = Order::where('payment_gateway_ref', $reference)
            ->where('payment_status', 'awaiting')
            ->first();

        if ($order) {
            CheckoutController::verifyAndCreditOrder($order);
            return response()->json(['message' => 'OK'], 200);
        }

        SecurityService::log('webhook_suspicious', null, [
            'reason' => 'No matching entity',
            'reference' => $reference,
        ]);

        Log::warning('FeexPay webhook: aucune transaction/commande trouvée', [
            'reference' => $reference,
        ]);

        return response()->json(['message' => 'No matching entity'], 200);
    }
}
