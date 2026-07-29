<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\AdminEmail;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class AdminEmailController extends Controller
{
    private const TEMPLATES = [
        'bienvenue' => [
            'label'   => 'Bienvenue',
            'subject' => 'Bienvenue sur MANTOTA, {name} !',
            'body'    => "Bonjour {name},\n\nMerci de rejoindre la communaute MANTOTA !\n\nVotre compte ({email}) est actif. Connectez-vous pour decouvrir les opportunites disponibles.\n\nL'equipe MANTOTA",
        ],
        'mise_a_jour' => [
            'label'   => 'Mise a jour plateforme',
            'subject' => 'Nouveautes sur MANTOTA',
            'body'    => "Bonjour {name},\n\nNous avons apporte des ameliorations a la plateforme MANTOTA.\n\nDecouvrez les nouvelles fonctionnalites en vous connectant a votre tableau de bord.\n\nL'equipe MANTOTA",
        ],
        'rappel_kyc' => [
            'label'   => 'Rappel KYC',
            'subject' => 'Completez votre verification KYC sur MANTOTA',
            'body'    => "Bonjour {name},\n\nVotre verification d'identite (KYC) n'est pas encore completee.\n\nSoumettez vos documents pour debloquer toutes les fonctionnalites de la plateforme.\n\nL'equipe MANTOTA",
        ],
        'promotion' => [
            'label'   => 'Promotion / Offre',
            'subject' => 'Offre speciale MANTOTA pour vous !',
            'body'    => "Bonjour {name},\n\nNous avons une offre speciale pour vous !\n\nConnectez-vous a votre compte pour en profiter.\n\nL'equipe MANTOTA",
        ],
        'personnalise' => [
            'label'   => 'Email personnalise',
            'subject' => '',
            'body'    => '',
        ],
    ];

    private const MERGE_TAGS = [
        '{name}'  => 'Nom de l\'utilisateur',
        '{email}' => 'Email de l\'utilisateur',
        '{role}'  => 'Role (Vendeur / Createur de contenu)',
    ];

    public function index(): InertiaResponse
    {
        $history = AdminEmail::with('sender:id,name')
            ->latest('sent_at')
            ->paginate(20);

        return Inertia::render('Emails/Index', [
            'history'   => $history,
            'templates' => self::TEMPLATES,
            'mergeTags' => self::MERGE_TAGS,
        ]);
    }

    public function send(Request $request)
    {
        $validated = $request->validate([
            'subject'     => ['required', 'string', 'max:255'],
            'body'        => ['required', 'string', 'max:10000'],
            'target_role' => ['required', 'string', 'in:vendor,influencer,all'],
            'template'    => ['nullable', 'string'],
        ]);

        $query = User::query()->whereNotNull('email_verified_at')->whereNull('deleted_at');

        if ($validated['target_role'] !== 'all') {
            $role = $validated['target_role'] === 'vendor' ? UserRole::VENDOR : UserRole::INFLUENCER;
            $query->where('role', $role);
        } else {
            $query->whereIn('role', [UserRole::VENDOR, UserRole::INFLUENCER]);
        }

        $users = $query->get(['id', 'name', 'email', 'role']);
        $count = 0;

        foreach ($users as $user) {
            $roleLabel = $user->role === UserRole::VENDOR ? 'Vendeur' : 'Createur de contenu';
            $subject = str_replace(
                ['{name}', '{email}', '{role}'],
                [$user->name ?? 'Utilisateur', $user->email, $roleLabel],
                $validated['subject']
            );
            $body = str_replace(
                ['{name}', '{email}', '{role}'],
                [$user->name ?? 'Utilisateur', $user->email, $roleLabel],
                $validated['body']
            );

            try {
                Mail::raw($body, function ($message) use ($user, $subject) {
                    $message->to($user->email)->subject($subject);
                });
                $count++;
            } catch (\Throwable $e) {
                report($e);
            }
        }

        AdminEmail::create([
            'subject'          => $validated['subject'],
            'body'             => $validated['body'],
            'target_role'      => $validated['target_role'],
            'template'         => $validated['template'] ?? null,
            'recipients_count' => $count,
            'sent_by'          => auth()->id(),
            'sent_at'          => now(),
        ]);

        return back()->with('success', "Email envoye avec succes a {$count} destinataire(s).");
    }
}
