<?php

use App\Models\Order;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// Canal prive pour le chat litige e-commerce.
// Autorise le vendeur proprietaire ou un admin.
Broadcast::channel('dispute.{orderId}', function ($user, $orderId) {
    $order = Order::find($orderId);

    if (! $order) {
        return false;
    }

    // Vendeur proprietaire
    if ($order->vendor_id === $user->id) {
        return true;
    }

    // Admin
    if ($user->role?->value === 'admin') {
        return true;
    }

    return false;
});
