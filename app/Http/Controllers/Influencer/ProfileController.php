<?php

declare(strict_types=1);

namespace App\Http\Controllers\Influencer;

use App\Enums\Niche;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

/**
 * ProfileController — Gestion du profil créateur de contenu (reseaux sociaux & VIP).
 *
 * Responsabilites :
 *  - Afficher le formulaire d'edition du profil avec les liens sociaux et compteurs.
 *  - Valider et sauvegarder les URLs de reseaux sociaux et compteurs d'abonnes.
 */
class ProfileController extends Controller
{
    /** Reseaux sociaux pris en charge par la plateforme. */
    private const SOCIAL_PLATFORMS = ['tiktok', 'instagram', 'facebook', 'youtube', 'snapchat'];

    /**
     * Affiche la page d'edition du profil créateur de contenu.
     */
    public function edit(): InertiaResponse
    {
        $user = auth()->user();

        return Inertia::render('Profile/Edit', [
            'user' => [
                'name'                => $user->name,
                'email'               => $user->email,
                'profile_photo'       => $user->profile_photo,
                'is_vip'              => (bool) $user->is_vip,
                'vip_requested_at'    => $user->vip_requested_at?->toISOString(),
                'niches'              => $user->niches ?? [],
                'tiktok_url'          => $user->tiktok_url,
                'instagram_url'       => $user->instagram_url,
                'facebook_url'        => $user->facebook_url,
                'youtube_url'         => $user->youtube_url,
                'snapchat_url'        => $user->snapchat_url,
                'tiktok_followers'    => (int) $user->tiktok_followers,
                'instagram_followers' => (int) $user->instagram_followers,
                'facebook_followers'  => (int) $user->facebook_followers,
                'youtube_followers'   => (int) $user->youtube_followers,
                'snapchat_followers'  => (int) $user->snapchat_followers,
            ],
            'available_niches' => Niche::options(),
        ]);
    }

    /**
     * Valide et sauvegarde les liens sociaux et compteurs d'abonnes declares.
     *
     * Chaque reseau dispose de deux champs :
     *  - {platform}_url      : URL du profil (nullable, doit etre une URL valide).
     *  - {platform}_followers : Nombre d'abonnes declare (integer >= 0).
     */
    public function updateSocials(Request $request): RedirectResponse
    {
        $rules = [];

        foreach (self::SOCIAL_PLATFORMS as $platform) {
            $rules["{$platform}_url"]       = ['nullable', 'url', 'max:500'];
            $rules["{$platform}_followers"] = ['nullable', 'integer', 'min:0', 'max:999999999'];
        }

        // Niches : tableau de 1 a 3 valeurs parmi l'enum Niche
        $rules['niches']   = ['nullable', 'array', 'max:3'];
        $rules['niches.*'] = ['string', Rule::in(Niche::values())];

        $validated = $request->validate($rules, [
            '*.url'       => 'L\'URL fournie n\'est pas valide.',
            '*.integer'   => 'Le nombre d\'abonnes doit etre un nombre entier.',
            '*.min'       => 'Le nombre d\'abonnes ne peut pas etre negatif.',
            'niches.max'  => 'Vous pouvez selectionner 3 niches maximum.',
            'niches.*.in' => 'Niche invalide.',
        ]);

        $user = auth()->user();

        // Normaliser les compteurs null en 0
        foreach (self::SOCIAL_PLATFORMS as $platform) {
            $validated["{$platform}_followers"] = (int) ($validated["{$platform}_followers"] ?? 0);
        }

        $user->update($validated);

        // Marquer la date de mise a jour des stats sociales
        $user->update(['socials_updated_at' => now()]);

        // Verification du minimum de 5 000 abonnes cumules
        $user->refresh();
        $total = $user->total_followers;

        if ($total < 5000) {
            return back()->with('warning', 'Profil enregistre, mais votre total d\'abonnes cumules est de ' . number_format($total, 0, ',', ' ') . '. '
                . 'Le minimum requis pour etre eligible aux campagnes est de 5 000 abonnes cumules sur l\'ensemble de vos reseaux sociaux. '
                . 'Completez vos profils pour atteindre ce seuil.');
        }

        return back()->with('success', 'Reseaux sociaux mis a jour avec succes.');
    }

    /**
     * Met a jour la photo de profil du créateur de contenu.
     */
    public function updatePhoto(Request $request): RedirectResponse
    {
        $request->validate([
            'photo' => ['required', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
        ], [
            'photo.required' => 'Veuillez selectionner une photo.',
            'photo.image'    => 'Le fichier doit etre une image.',
            'photo.mimes'    => 'Formats acceptes : JPEG, PNG, JPG, WebP.',
            'photo.max'      => 'La photo ne doit pas depasser 2 Mo.',
        ]);

        $user = auth()->user();

        // Supprimer l'ancienne photo si elle existe
        if ($user->profile_photo) {
            Storage::disk('public')->delete($user->profile_photo);
        }

        $path = $request->file('photo')->store('profiles', 'public');

        $user->update(['profile_photo' => $path]);

        return back()->with('success', 'Photo de profil mise a jour.');
    }
}
