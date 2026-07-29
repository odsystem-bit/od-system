<?php

declare(strict_types=1);

namespace App\Http\Controllers\Shared;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

/**
 * KYCController — Verification d'identite unifiee pour les deux roles.
 *
 * Responsabilites :
 *  - Afficher le statut KYC actuel et l'interface de soumission.
 *  - Stocker les documents d'identite (recto/verso) de maniere securisee.
 *  - Passer le statut de 'not_submitted' ou 'rejected' a 'pending'.
 *
 * Un seul controleur pour vendor ET influencer : la route est partagee
 * et protegee par le middleware 'auth' uniquement (les deux roles y accedent).
 */
class KYCController extends Controller
{
    // ──────────────────────────────────────────────
    //  Constantes de validation
    // ──────────────────────────────────────────────

    private const ALLOWED_DOCUMENT_MIMES = 'image/jpeg,image/png,image/webp,application/pdf';
    private const MAX_DOCUMENT_SIZE_KB   = 5120; // 5 Mo

    // ──────────────────────────────────────────────
    //  1. Affichage du statut KYC
    // ──────────────────────────────────────────────

    /**
     * Affiche la page KYC avec le statut actuel de l'utilisateur.
     * Si 'approved', aucun formulaire n'est affiche.
     * Si 'rejected', l'utilisateur peut resoumettre.
     */
    public function index(): InertiaResponse
    {
        $user = auth()->user();

        return Inertia::render('KYC/Index', [
            'kyc_status'      => $user->kyc_status ?? 'not_submitted',
            'kyc_document_front' => $user->kyc_document_front ? Storage::disk('public')->url($user->kyc_document_front) : null,
            'kyc_document_back'  => $user->kyc_document_back ? Storage::disk('public')->url($user->kyc_document_back) : null,
        ]);
    }

    // ──────────────────────────────────────────────
    //  2. Soumission des documents KYC
    // ──────────────────────────────────────────────

    /**
     * Valide et stocke les documents d'identite (recto + verso).
     * Change le statut KYC en 'pending' pour revue par l'admin.
     *
     * Seuls les utilisateurs avec statut 'not_submitted' ou 'rejected'
     * peuvent soumettre. Les utilisateurs 'pending' ou 'approved' sont bloques.
     */
    public function store(Request $request): RedirectResponse
    {
        $user = auth()->user();

        // ── Verrou : seuls not_submitted et rejected peuvent soumettre ──
        if (! in_array($user->kyc_status, ['not_submitted', 'rejected'], true)) {
            return back()->withErrors([
                'kyc' => 'Votre verification est deja en cours ou approuvee.',
            ]);
        }

        // ── Validation des documents ──
        $request->validate([
            'document_front' => [
                'required',
                'file',
                'mimetypes:' . self::ALLOWED_DOCUMENT_MIMES,
                'max:' . self::MAX_DOCUMENT_SIZE_KB,
            ],
            'document_back' => [
                'required',
                'file',
                'mimetypes:' . self::ALLOWED_DOCUMENT_MIMES,
                'max:' . self::MAX_DOCUMENT_SIZE_KB,
            ],
        ], [
            'document_front.required'  => 'Le recto de la piece d\'identite est obligatoire.',
            'document_front.mimetypes' => 'Format accepte : JPEG, PNG, WebP ou PDF.',
            'document_front.max'       => 'Le fichier ne doit pas depasser 5 Mo.',
            'document_back.required'   => 'Le verso de la piece d\'identite est obligatoire.',
            'document_back.mimetypes'  => 'Format accepte : JPEG, PNG, WebP ou PDF.',
            'document_back.max'        => 'Le fichier ne doit pas depasser 5 Mo.',
        ]);

        // ── Nettoyage des anciens documents si resoumission ──
        if ($user->kyc_document_front) {
            Storage::disk('public')->delete($user->kyc_document_front);
        }
        if ($user->kyc_document_back) {
            Storage::disk('public')->delete($user->kyc_document_back);
        }

        // ── Stockage sur disque public (accessible via /storage/) ──
        $frontPath = $request->file('document_front')
            ->store('kyc/' . $user->id, 'public');

        $backPath = $request->file('document_back')
            ->store('kyc/' . $user->id, 'public');

        // ── Mise a jour de l'utilisateur ──
        $user->update([
            'kyc_status'         => 'pending',
            'kyc_document_front' => $frontPath,
            'kyc_document_back'  => $backPath,
        ]);

        return back()->with('success', 'Documents soumis avec succes. Votre verification est en cours de traitement.');
    }
}
