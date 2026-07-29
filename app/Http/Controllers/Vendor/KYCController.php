<?php

declare(strict_types=1);

namespace App\Http\Controllers\Vendor;

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
 * KYCController — Verification d'identite differenciee pour le Vendeur.
 *
 * Responsabilites :
 *  - Afficher le formulaire KYC specifique vendeur (infos business).
 *  - Stocker les documents d'identite (recto/verso) + informations commerciales.
 *  - Passer le statut de 'not_submitted' ou 'rejected' a 'pending'.
 *
 * Champs specifiques au vendeur :
 *  - business_name  : nom commercial ou raison sociale.
 *  - id_card_front  : recto de la piece d'identite.
 *  - id_card_back   : verso de la piece d'identite.
 *  - ifu_or_rccm    : numero IFU ou RCCM (optionnel, entreprises formelles).
 */
class KYCController extends Controller
{
    // ──────────────────────────────────────────────
    //  Constantes de validation
    // ──────────────────────────────────────────────

    private const ALLOWED_DOCUMENT_MIMES = 'image/jpeg,image/png,image/webp,application/pdf';
    private const MAX_DOCUMENT_SIZE_KB   = 5120; // 5 Mo

    // ──────────────────────────────────────────────
    //  1. Affichage du formulaire KYC vendeur
    // ──────────────────────────────────────────────

    /**
     * Affiche la page KYC vendeur avec le statut actuel,
     * le nom commercial enregistre et les documents deja soumis.
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
            'kyc_status'            => $user->kyc_status ?? 'not_submitted',
            'kyc_rejection_reason'  => $kycRejectionReason,
            'business_name'         => $user->business_name,
            'ifu_or_rccm'           => $user->ifu_or_rccm,
            'birth_date'            => $user->birth_date?->format('Y-m-d'),
            'id_card_expiry'        => $user->id_card_expiry?->format('Y-m-d'),
            'kyc_document_front'  => $user->kyc_document_front
                ? route('admin.kyc.document', ['path' => str_replace('kyc/', '', $user->kyc_document_front)])
                : null,
            'kyc_document_back'   => $user->kyc_document_back
                ? route('admin.kyc.document', ['path' => str_replace('kyc/', '', $user->kyc_document_back)])
                : null,
            'kyc_document_selfie' => $user->kyc_document_selfie
                ? route('admin.kyc.document', ['path' => str_replace('kyc/', '', $user->kyc_document_selfie)])
                : null,
        ]);
    }

    // ──────────────────────────────────────────────
    //  2. Soumission des documents KYC vendeur
    // ──────────────────────────────────────────────

    /**
     * Valide et stocke les informations business et documents d'identite.
     * Change le statut KYC en 'pending' pour revue par l'admin.
     *
     * Seuls les utilisateurs avec statut 'not_submitted' ou 'rejected'
     * peuvent soumettre. Les utilisateurs 'pending' ou 'approved' sont bloques.
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
        $validated = $request->validate([
            'business_name' => ['required', 'string', 'max:255'],
            'ifu_or_rccm'   => ['nullable', 'string', 'max:100'],
            'birth_date'     => ['required', 'date', 'before:today'],
            'id_card_expiry' => ['required', 'date', 'after:today'],
            'id_card_front'  => [
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
            'business_name.required'   => 'Le nom commercial est obligatoire.',
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
            'kyc_document_front'  => $frontPath,
            'kyc_document_back'   => $backPath,
            'kyc_document_selfie' => $selfiePath,
            'business_name'       => $validated['business_name'],
            'ifu_or_rccm'         => $validated['ifu_or_rccm'] ?? null,
            'birth_date'          => $validated['birth_date'],
            'id_card_expiry'      => $validated['id_card_expiry'],
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

            $user->notify(new KycStatusUpdatedNotification('rejected', $rejectReason));

            $admins = User::where('role', 'admin')->get();
            foreach ($admins as $admin) {
                $admin->notify(new KycAutoRejectedAdminNotification($user, $rejectReason));
            }

            return back()->with('error', 'Votre dossier a ete rejete automatiquement : ' . $rejectReason);
        }

        // Si OK, passer en pending pour revue admin
        $user->update(['kyc_status' => 'pending']);

        // Notifier tous les admins de la nouvelle soumission KYC
        $admins = User::where('role', 'admin')->get();
        foreach ($admins as $admin) {
            $admin->notify(new KycSubmittedAdminNotification($user));
        }

        return back()->with('success', 'Documents soumis avec succes. Votre verification est en cours de traitement.');
    }

    // ──────────────────────────────────────────────
    //  Robot KYC — Controles automatiques
    // ──────────────────────────────────────────────

    private function robotScreening(User $user): ?string
    {
        if ($user->birth_date) {
            $age = Carbon::parse($user->birth_date)->age;
            if ($age < 18) {
                return "Vous devez avoir au moins 18 ans pour utiliser la plateforme. Age detecte : {$age} ans.";
            }
        }

        if ($user->id_card_expiry) {
            if (Carbon::parse($user->id_card_expiry)->isPast()) {
                return 'Votre piece d\'identite est expiree (date d\'expiration : ' . Carbon::parse($user->id_card_expiry)->format('d/m/Y') . '). Veuillez fournir une carte valide.';
            }
        }

        $documentsToCheck = [
            'kyc_document_front' => 'Le recto de la piece d\'identite',
            'kyc_document_back'  => 'Le verso de la piece d\'identite',
            'kyc_document_selfie' => 'La photo selfie avec carte',
        ];

        foreach ($documentsToCheck as $field => $label) {
            if ($user->$field) {
                $size = Storage::disk('local')->size($user->$field);
                if ($size < 10240) {
                    return "{$label} semble invalide ou illisible (fichier trop petit : " . round($size / 1024, 1) . " Ko). Veuillez soumettre une image claire et lisible.";
                }
            }
        }

        return null;
    }
}
