<?php

declare(strict_types=1);

namespace App\Http\Controllers\Influencer;

use App\Http\Controllers\Controller;
use App\Models\KycLog;
use App\Models\User;
use App\Notifications\KycAutoRejectedAdminNotification;
use App\Notifications\KycStatusUpdatedNotification;
use App\Notifications\KycSubmittedAdminNotification;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

/**
 * KYCController — Verification d'identite pour le Créateur de contenu.
 *
 * Responsabilites :
 *  - Afficher le formulaire KYC créateur de contenu (piece d'identite uniquement).
 *  - Stocker les documents d'identite (recto/verso).
 *  - Passer le statut de 'not_submitted' ou 'rejected' a 'pending'.
 *
 * Champs :
 *  - id_card_front : recto de la piece d'identite.
 *  - id_card_back  : verso de la piece d'identite.
 */
class KYCController extends Controller
{
    // ──────────────────────────────────────────────
    //  Constantes de validation
    // ──────────────────────────────────────────────

    private const ALLOWED_DOCUMENT_MIMES = 'image/jpeg,image/png,image/webp,application/pdf';
    private const MAX_DOCUMENT_SIZE_KB   = 5120; // 5 Mo

    // ──────────────────────────────────────────────
    //  1. Affichage du formulaire KYC créateur de contenu
    // ──────────────────────────────────────────────

    /**
     * Affiche la page KYC créateur de contenu avec le statut actuel
     * et les documents deja soumis.
     */
    public function index(): InertiaResponse
    {
        $user = auth()->user();

        $kycRejectionReason = null;
        if (($user->kyc_status ?? '') === 'rejected') {
            $lastReject = KycLog::where('user_id', $user->id)
                ->where('action', 'rejected')
                ->latest()->first();
            $kycRejectionReason = $lastReject?->reason;
        }

        return Inertia::render('KYC/Index', [
            'kyc_status'           => $user->kyc_status ?? 'not_submitted',
            'kyc_rejection_reason' => $kycRejectionReason,
            'birth_date'           => $user->birth_date?->format('Y-m-d'),
            'id_card_expiry'       => $user->id_card_expiry?->format('Y-m-d'),
            'kyc_document_front'   => $user->kyc_document_front
                ? route('admin.kyc.document', ['path' => str_replace('kyc/', '', $user->kyc_document_front)])
                : null,
            'kyc_document_back'    => $user->kyc_document_back
                ? route('admin.kyc.document', ['path' => str_replace('kyc/', '', $user->kyc_document_back)])
                : null,
            'kyc_document_selfie'  => $user->kyc_document_selfie
                ? route('admin.kyc.document', ['path' => str_replace('kyc/', '', $user->kyc_document_selfie)])
                : null,
        ]);
    }

    // ──────────────────────────────────────────────
    //  2. Soumission des documents KYC créateur de contenu
    // ──────────────────────────────────────────────

    /**
     * Valide et stocke les documents d'identite.
     * Change le statut KYC en 'pending' pour revue par l'admin.
     */
    public function store(Request $request): RedirectResponse
    {
        $user = auth()->user();

        // ── Verrou : seuls not_submitted et rejected peuvent soumettre ──
        if (! in_array($user->kyc_status, ['not_submitted', 'rejected', null], true)) {
            return back()->withErrors([
                'kyc' => 'Votre verification est deja en cours ou approuvee.',
            ]);
        }

        // ── Validation des champs ──
        $request->validate([
            'birth_date' => ['required', 'date', 'before:today'],
            'id_card_expiry' => ['required', 'date', 'after:today'],
            'id_card_front' => [
                'required',
                'file',
                'mimetypes:' . self::ALLOWED_DOCUMENT_MIMES,
                'max:' . self::MAX_DOCUMENT_SIZE_KB,
            ],
            'id_card_back' => [
                'required',
                'file',
                'mimetypes:' . self::ALLOWED_DOCUMENT_MIMES,
                'max:' . self::MAX_DOCUMENT_SIZE_KB,
            ],
            'id_card_selfie' => [
                'required',
                'file',
                'mimetypes:image/jpeg,image/png,image/webp',
                'max:' . self::MAX_DOCUMENT_SIZE_KB,
            ],
        ], [
            'birth_date.required'      => 'La date de naissance est obligatoire.',
            'birth_date.date'          => 'Date de naissance invalide.',
            'birth_date.before'        => 'La date de naissance doit etre dans le passe.',
            'id_card_expiry.required'  => 'La date d\'expiration de la carte est obligatoire.',
            'id_card_expiry.date'      => 'Date d\'expiration invalide.',
            'id_card_expiry.after'     => 'Votre carte d\'identite est expiree. Veuillez utiliser une carte valide.',
            'id_card_front.required'   => 'Le recto de la piece d\'identite est obligatoire.',
            'id_card_front.mimetypes'  => 'Format accepte : JPEG, PNG, WebP ou PDF.',
            'id_card_front.max'        => 'Le fichier ne doit pas depasser 5 Mo.',
            'id_card_back.required'    => 'Le verso de la piece d\'identite est obligatoire.',
            'id_card_back.mimetypes'   => 'Format accepte : JPEG, PNG, WebP ou PDF.',
            'id_card_back.max'         => 'Le fichier ne doit pas depasser 5 Mo.',
            'id_card_selfie.required'  => 'La photo avec votre carte en main est obligatoire.',
            'id_card_selfie.mimetypes' => 'Format accepte : JPEG, PNG ou WebP.',
            'id_card_selfie.max'       => 'Le fichier ne doit pas depasser 5 Mo.',
        ]);

        // ── Nettoyage des anciens documents si resoumission ──
        if ($user->kyc_document_front) {
            Storage::disk('local')->delete($user->kyc_document_front);
        }
        if ($user->kyc_document_back) {
            Storage::disk('local')->delete($user->kyc_document_back);
        }
        if ($user->kyc_document_selfie) {
            Storage::disk('local')->delete($user->kyc_document_selfie);
        }

        // ── Stockage sur disque local (prive — non accessible via URL) ──
        $frontPath = $request->file('id_card_front')
            ->store('kyc/' . $user->id, 'local');

        $backPath = $request->file('id_card_back')
            ->store('kyc/' . $user->id, 'local');

        $selfiePath = $request->file('id_card_selfie')
            ->store('kyc/' . $user->id, 'local');

        // ── Mise a jour de l'utilisateur ──
        $user->update([
            'kyc_document_front'   => $frontPath,
            'kyc_document_back'    => $backPath,
            'kyc_document_selfie'  => $selfiePath,
            'birth_date'           => $request->input('birth_date'),
            'id_card_expiry'       => $request->input('id_card_expiry'),
        ]);

        // ══════════════════════════════════════
        //  ROBOT KYC — Pre-screening automatique
        // ══════════════════════════════════════
        $rejectReason = $this->robotScreening($user);

        if ($rejectReason) {
            $user->update(['kyc_status' => 'rejected']);

            KycLog::create([
                'user_id'  => $user->id,
                'admin_id' => null,
                'action'   => 'rejected',
                'reason'   => '[Robot] ' . $rejectReason,
            ]);

            // Notifier l'utilisateur
            $user->notify(new KycStatusUpdatedNotification('rejected', $rejectReason));

            // Notifier tous les admins
            $admins = User::where('role', 'admin')->get();
            foreach ($admins as $admin) {
                $admin->notify(new KycAutoRejectedAdminNotification($user, $rejectReason));
            }

            return back()->with('error', 'Votre dossier a ete rejete automatiquement : ' . $rejectReason);
        }

        // Si le robot ne detecte rien, passer en pending pour revue admin
        $user->update(['kyc_status' => 'pending']);

        // Notifier tous les admins de la nouvelle soumission KYC
        $admins = User::where('role', 'admin')->get();
        foreach ($admins as $admin) {
            $admin->notify(new KycSubmittedAdminNotification($user));
        }

        return back()->with('success', 'Documents soumis avec succes. Votre verification est en cours de traitement.');
    }

    // ──────────────────────────────────────────────
    //  3. Demande de statut VIP
    // ──────────────────────────────────────────────

    /**
     * Enregistre une demande de statut VIP pour le créateur de contenu.
     * Conditions : ne doit pas deja etre VIP et ne doit pas avoir
     * une demande en cours.
     */
    public function requestVip(): RedirectResponse
    {
        $user = auth()->user();

        if ($user->is_vip) {
            return back()->withErrors(['vip' => 'Vous possedez deja le statut VIP.']);
        }

        if ($user->vip_requested_at !== null) {
            return back()->withErrors(['vip' => 'Une demande VIP est deja en cours de traitement.']);
        }

        // Verification du minimum de 5 000 abonnes cumules
        if ($user->total_followers < 5000) {
            return back()->withErrors(['vip' => 'Votre total d\'abonnes cumules est de ' . number_format($user->total_followers, 0, ',', ' ') . '. '
                . 'Le minimum requis pour soumettre une demande VIP est de 5 000 abonnes cumules sur l\'ensemble de vos reseaux sociaux. '
                . 'Veuillez mettre a jour vos profils et renseigner vos vrais chiffres. '
                . 'Attention : toute declaration frauduleuse sera verifiee manuellement et pourra entrainer un bannissement definitif de la plateforme.']);
        }

        $user->update(['vip_requested_at' => now()]);

        return back()->with('success', 'Votre demande VIP a ete soumise. Nous l\'examinerons dans les plus brefs delais.');
    }

    // ──────────────────────────────────────────────
    //  4. Robot KYC — Controles automatiques
    // ──────────────────────────────────────────────

    /**
     * Verifie automatiquement les donnees du dossier KYC.
     * Retourne la raison du rejet ou null si tout est OK.
     */
    private function robotScreening(User $user): ?string
    {
        // 1. Verification de l'age minimum (18 ans)
        if ($user->birth_date) {
            $age = Carbon::parse($user->birth_date)->age;
            if ($age < 18) {
                return "Vous devez avoir au moins 18 ans pour utiliser la plateforme. Age detecte : {$age} ans.";
            }
        }

        // 2. Verification de l'expiration de la carte
        if ($user->id_card_expiry) {
            if (Carbon::parse($user->id_card_expiry)->isPast()) {
                return 'Votre piece d\'identite est expiree (date d\'expiration : ' . Carbon::parse($user->id_card_expiry)->format('d/m/Y') . '). Veuillez fournir une carte valide.';
            }
        }

        // 3. Verification du minimum de 5 000 abonnes cumules
        if ($user->total_followers < 5000) {
            return 'Votre total d\'abonnes cumules est de ' . number_format($user->total_followers, 0, ',', ' ') . '. '
                . 'Le minimum requis est de 5 000 abonnes cumules sur l\'ensemble de vos reseaux sociaux. '
                . 'Veuillez mettre a jour vos profils dans la section Mon Profil avant de soumettre votre dossier KYC. '
                . 'Attention : toute declaration frauduleuse de nombre d\'abonnes sera verifiee manuellement et pourra entrainer un bannissement definitif.';
        }

        // 4. Verification que les images ne sont pas trop petites (< 10 Ko = probablement invalide)
        $documentsToCheck = [
            'kyc_document_front' => 'Le recto de la piece d\'identite',
            'kyc_document_back'  => 'Le verso de la piece d\'identite',
            'kyc_document_selfie' => 'La photo selfie avec carte',
        ];

        foreach ($documentsToCheck as $field => $label) {
            if ($user->$field) {
                $size = Storage::disk('local')->size($user->$field);
                if ($size < 10240) { // < 10 Ko
                    return "{$label} semble invalide ou illisible (fichier trop petit : " . round($size / 1024, 1) . " Ko). Veuillez soumettre une image claire et lisible.";
                }
            }
        }

        return null; // Tout OK
    }
}
