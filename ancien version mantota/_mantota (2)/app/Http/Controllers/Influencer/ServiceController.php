<?php

declare(strict_types=1);

namespace App\Http\Controllers\Influencer;

use App\Http\Controllers\Controller;
use App\Models\InfluencerService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class ServiceController extends Controller
{
    // ──────────────────────────────────────────────
    //  Constantes de validation
    // ──────────────────────────────────────────────

    private const ALLOWED_TYPES     = ['ugc_humain', 'video_pub_ia'];
    private const ALLOWED_DURATIONS = ['15s', '30s', '60s', 'long'];

    // ──────────────────────────────────────────────
    //  Actions CRUD
    // ──────────────────────────────────────────────

    /**
     * Liste des services du créateur de contenu connecte.
     */
    public function index(): InertiaResponse
    {
        $user = auth()->user();

        $services = InfluencerService::where('influencer_id', $user->id)
            ->withCount('orders')
            ->latest()
            ->get();

        return Inertia::render('Services/Index', [
            'services' => $services,
            'is_vip'   => (bool) $user->is_vip,
        ]);
    }

    /**
     * Creer un nouveau service — VIP uniquement.
     */
    public function store(Request $request): RedirectResponse
    {
        $user = auth()->user();

        // Gate VIP
        abort_unless((bool) $user->is_vip, 403, 'Seuls les créateurs de contenu VIP peuvent creer des services.');

        $validated = $request->validate([
            'title'              => ['required', 'string', 'max:255'],
            'type'               => ['required', Rule::in(self::ALLOWED_TYPES)],
            'price'              => ['required', 'numeric', 'min:500'],
            'duration'           => ['required', Rule::in(self::ALLOWED_DURATIONS)],
            'description'        => ['required', 'string', 'max:3000'],
            'included_revisions' => ['required', 'integer', 'min:0', 'max:5'],
            'image'              => ['nullable', 'image', 'max:2048'],
        ], [
            'title.required'              => 'Le titre du service est obligatoire.',
            'type.required'               => 'Le type de service est obligatoire.',
            'type.in'                     => 'Le type doit etre UGC Humain ou Video Pub IA.',
            'price.required'              => 'Le prix est obligatoire.',
            'price.min'                   => 'Le prix minimum est de 500 FCFA.',
            'duration.required'           => 'La duree est obligatoire.',
            'duration.in'                 => 'La duree selectionnee est invalide.',
            'description.required'        => 'La description est obligatoire.',
            'included_revisions.required' => 'Le nombre de retouches est obligatoire.',
            'included_revisions.max'      => 'Le maximum est de 5 retouches.',
            'image.image'                 => 'Le fichier doit etre une image.',
            'image.max'                   => 'L\'image ne peut pas depasser 2 Mo.',
        ]);

        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('services', 'public');
        }

        InfluencerService::create([
            'influencer_id'      => $user->id,
            'title'              => $validated['title'],
            'type'               => $validated['type'],
            'price'              => $validated['price'],
            'duration'           => $validated['duration'],
            'description'        => $validated['description'],
            'included_revisions' => (int) $validated['included_revisions'],
            'image_path'         => $imagePath,
        ]);

        return redirect()
            ->route('influencer.services.index')
            ->with('success', 'Service cree avec succes.');
    }

    /**
     * Supprimer un service.
     */
    public function destroy(InfluencerService $service): RedirectResponse
    {
        $this->authorizeOwner($service);

        $service->delete();

        return redirect()
            ->route('influencer.services.index')
            ->with('success', 'Service supprime.');
    }

    /**
     * Formulaire de modification d'un service.
     */
    public function edit(InfluencerService $service): InertiaResponse
    {
        $this->authorizeOwner($service);

        return Inertia::render('Services/Edit', [
            'service'          => $service,
            'allowedTypes'     => self::ALLOWED_TYPES,
            'allowedDurations' => self::ALLOWED_DURATIONS,
        ]);
    }

    /**
     * Mettre a jour un service.
     */
    public function update(Request $request, InfluencerService $service): RedirectResponse
    {
        $this->authorizeOwner($service);

        $validated = $request->validate([
            'title'              => ['required', 'string', 'max:255'],
            'type'               => ['required', Rule::in(self::ALLOWED_TYPES)],
            'price'              => ['required', 'numeric', 'min:500'],
            'duration'           => ['required', Rule::in(self::ALLOWED_DURATIONS)],
            'description'        => ['required', 'string', 'max:3000'],
            'included_revisions' => ['required', 'integer', 'min:0', 'max:5'],
            'image'              => ['nullable', 'image', 'max:2048'],
        ], [
            'title.required'              => 'Le titre du service est obligatoire.',
            'type.in'                     => 'Le type doit etre UGC Humain ou Video Pub IA.',
            'price.required'              => 'Le prix est obligatoire.',
            'price.min'                   => 'Le prix minimum est de 500 FCFA.',
            'duration.in'                 => 'La duree selectionnee est invalide.',
            'description.required'        => 'La description est obligatoire.',
            'included_revisions.max'      => 'Le maximum est de 5 retouches.',
            'image.image'                 => 'Le fichier doit etre une image.',
            'image.max'                   => 'L\'image ne peut pas depasser 2 Mo.',
        ]);

        $data = [
            'title'              => $validated['title'],
            'type'               => $validated['type'],
            'price'              => $validated['price'],
            'duration'           => $validated['duration'],
            'description'        => $validated['description'],
            'included_revisions' => (int) $validated['included_revisions'],
        ];

        if ($request->hasFile('image')) {
            $data['image_path'] = $request->file('image')->store('services', 'public');
        }

        $service->update($data);

        return redirect()
            ->route('influencer.services.index')
            ->with('success', 'Service mis a jour.');
    }

    // ──────────────────────────────────────────────
    //  Guard
    // ──────────────────────────────────────────────

    private function authorizeOwner(InfluencerService $service): void
    {
        abort_unless((int) $service->influencer_id === (int) auth()->id(), 403);
    }
}
