<?php

declare(strict_types=1);

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class SettingsController extends Controller
{
    public function index(): InertiaResponse
    {
        $user = auth()->user();

        return Inertia::render('Settings/Index', [
            'shop_name'           => $user->shop_name,
            'shop_logo_path'      => $user->shop_logo_path,
            'shop_theme'          => $user->shop_theme ?? 'classique',
            'shop_display_format' => $user->shop_display_format ?? 'square',
        ]);
    }

    public function updateBranding(Request $request): RedirectResponse
    {
        $user = auth()->user();

        $validated = $request->validate([
            'shop_name' => ['nullable', 'string', 'max:255'],
            'shop_logo' => ['nullable', 'image', 'mimes:jpeg,png,webp', 'max:2048'],
        ], [
            'shop_name.max'    => 'Le nom de boutique ne doit pas depasser 255 caracteres.',
            'shop_logo.image'  => 'Le logo doit etre une image.',
            'shop_logo.mimes'  => 'Format accepte : JPEG, PNG ou WebP.',
            'shop_logo.max'    => 'Le logo ne doit pas depasser 2 Mo.',
        ]);

        $data = ['shop_name' => $validated['shop_name']];

        if ($request->hasFile('shop_logo')) {
            if ($user->shop_logo_path) {
                Storage::disk('public')->delete($user->shop_logo_path);
            }

            $data['shop_logo_path'] = $request->file('shop_logo')
                ->store('shop-logos/' . $user->id, 'public');
        }

        $user->update($data);

        return back()->with('success', 'Branding de la boutique mis a jour.');
    }

    public function updateTheme(Request $request): RedirectResponse
    {
        $user = auth()->user();

        $validated = $request->validate([
            'shop_theme'          => ['required', 'string', 'in:classique,luxe,ocean,nature,sunset,neon,minimal,terracotta,royal,bonbon'],
            'shop_display_format' => ['required', 'string', 'in:square,landscape,portrait'],
        ]);

        $user->update($validated);

        return back()->with('success', 'Theme de la boutique mis a jour.');
    }
}
