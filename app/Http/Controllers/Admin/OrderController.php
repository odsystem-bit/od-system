<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class OrderController extends Controller
{
    public function index(Request $request): InertiaResponse
    {
        $query = Order::query()
            ->with([
                'vendor:id,name,email',
                'product:id,name',
                'influencer:id,name,email',
            ]);

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('reference', 'like', "%{$search}%")
                  ->orWhere('customer_name', 'like', "%{$search}%")
                  ->orWhere('customer_phone', 'like', "%{$search}%")
                  ->orWhere('customer_email', 'like', "%{$search}%")
                  ->orWhereHas('vendor', fn ($q2) => $q2->where('name', 'like', "%{$search}%"))
                  ->orWhereHas('influencer', fn ($q2) => $q2->where('name', 'like', "%{$search}%"));
            });
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($paymentStatus = $request->input('payment_status')) {
            $query->where('payment_status', $paymentStatus);
        }

        if ($delivery = $request->input('delivery_company')) {
            $query->where('delivery_company', $delivery);
        }

        $orders = $query->orderByDesc('created_at')->paginate(25)->withQueryString();

        return Inertia::render('Orders/Index', [
            'orders'  => $orders,
            'filters' => $request->only(['search', 'status', 'payment_status', 'delivery_company']),
        ]);
    }

    public function export(): StreamedResponse
    {
        $orders = Order::with(['vendor:id,name', 'product:id,name', 'influencer:id,name'])
            ->orderByDesc('created_at')
            ->get();

        return new StreamedResponse(function () use ($orders) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Reference', 'Client', 'Telephone', 'Produit', 'Vendeur', 'Createur de contenu', 'Montant', 'Commission', 'Statut', 'Livraison', 'Date']);

            foreach ($orders as $order) {
                fputcsv($handle, [
                    $order->reference,
                    $order->customer_name,
                    $order->customer_phone,
                    $order->product?->name ?? '',
                    $order->vendor?->name ?? '',
                    $order->influencer?->name ?? '',
                    $order->total_amount,
                    $order->commission_amount,
                    $order->status->value,
                    $order->delivery_company ?? '',
                    $order->created_at->format('Y-m-d H:i'),
                ]);
            }

            fclose($handle);
        }, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="commandes_' . date('Y-m-d') . '.csv"',
        ]);
    }
}
