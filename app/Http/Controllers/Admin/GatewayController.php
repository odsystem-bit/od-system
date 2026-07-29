<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Gateway;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class GatewayController extends Controller
{
    public function index(): InertiaResponse
    {
        $gateways = Gateway::orderBy('priority')->orderBy('name')->get();

        return Inertia::render('Gateways/Index', [
            'gateways' => $gateways,
        ]);
    }

    public function update(Request $request, Gateway $gateway): RedirectResponse
    {
        $validated = $request->validate([
            'is_active'        => ['required', 'boolean'],
            'public_key'       => ['nullable', 'string', 'max:500'],
            'secret_key'       => ['nullable', 'string', 'max:500'],
            'webhook_secret'   => ['nullable', 'string', 'max:500'],
            'environment'      => ['required', 'string', 'in:sandbox,live'],
            'countries'        => ['nullable', 'array'],
            'countries.*'      => ['string', 'size:2'],
            'payin_fee'        => ['nullable', 'numeric', 'min:0', 'max:99'],
            'payout_fee'       => ['nullable', 'numeric', 'min:0', 'max:99'],
            'priority'         => ['nullable', 'integer', 'min:1', 'max:100'],
            'supports_refund'  => ['nullable', 'boolean'],
            'supports_payout'  => ['nullable', 'boolean'],
        ]);

        $gateway->update($validated);

        return back()->with('success', "Passerelle {$gateway->name} mise à jour.");
    }
}
