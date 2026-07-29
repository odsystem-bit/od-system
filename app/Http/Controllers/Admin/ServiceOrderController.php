<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ServiceOrder;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class ServiceOrderController extends Controller
{
    /**
     * Affiche le detail d'une commande UGC avec l'integralite
     * des messages — mode lecture seule (Omniscience Admin).
     */
    public function show(ServiceOrder $serviceOrder): InertiaResponse
    {
        $serviceOrder->load([
            'vendor:id,name,email,phone',
            'influencer:id,name,email,phone',
            'service:id,title,type,duration,price,included_revisions',
            'product:id,name,type',
            'messages.sender:id,name,role',
        ]);

        return Inertia::render('ServiceOrders/Show', [
            'order' => $serviceOrder,
        ]);
    }
}
