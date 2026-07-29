<?php

declare(strict_types=1);

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use ZipArchive;

/**
 * ProductController — CRUD produits cote vendeur.
 *
 * Responsabilites :
 *  - Lister les produits du vendeur connecte.
 *  - Afficher le formulaire de creation.
 *  - Valider dynamiquement selon le type (physical / digital).
 *  - Gerer l'upload de l'image de couverture.
 */
class ProductController extends Controller
{
    // ──────────────────────────────────────────────
    //  Constantes
    // ──────────────────────────────────────────────

    private const ALLOWED_TYPES           = ['physical', 'digital'];
    private const ALLOWED_DELIVERY         = ['free', 'fixed', 'pay_on_delivery'];
    private const ALLOWED_DIGITAL_DELIVERY = ['link', 'file'];
    private const ALLOWED_FORMATS          = ['square', 'landscape', 'portrait'];
    private const IMAGE_MIMES             = 'image/jpeg,image/png,image/webp';
    private const MAX_IMAGE_SIZE_KB       = 5120; // 5 Mo
    private const MAX_IMAGES              = 8;
    private const DIGITAL_FILE_MIMES      = 'video/mp4,video/quicktime,video/x-msvideo,application/pdf,application/zip,application/x-rar-compressed,image/jpeg,image/png,image/webp,audio/mpeg,audio/mp4,application/vnd.openxmlformats-officedocument.wordprocessingml.document,application/vnd.openxmlformats-officedocument.presentationml.presentation';
    private const MAX_DIGITAL_FILE_KB     = 102400; // 100 Mo par fichier
    private const MAX_DIGITAL_FILES       = 20;

    // ──────────────────────────────────────────────
    //  Liste des produits
    // ──────────────────────────────────────────────

    /**
     * Affiche la liste des produits du vendeur connecte.
     */
    public function index(): InertiaResponse
    {
        $vendor = auth()->user();

        // ── Auto-generation du slug si absent (ancien utilisateur) ──
        if (empty($vendor->slug)) {
            $base = Str::slug($vendor->name);
            $slug = $base . '-' . $vendor->id;
            $vendor->update(['slug' => $slug]);
        }

        $products = Product::where('vendor_id', $vendor->id)
            ->with('images:id,product_id,path,sort_order')
            ->latest()
            ->get();

        return Inertia::render('Products/Index', [
            'products'          => $products,
            'shopDisplayFormat' => $vendor->shop_display_format ?? 'square',
        ]);
    }

    // ──────────────────────────────────────────────
    //  Formulaire de creation
    // ──────────────────────────────────────────────

    /**
     * Affiche le formulaire de creation d'un produit.
     */
    public function create(): InertiaResponse
    {
        return Inertia::render('Products/Create');
    }

    // ──────────────────────────────────────────────
    //  Enregistrement
    // ──────────────────────────────────────────────

    /**
     * Valide les donnees, gere l'upload image et cree le produit.
     *
     * Validation dynamique :
     *  - type === 'physical' → stock requis.
     *  - type === 'digital'  → access_url requis.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name'               => ['required', 'string', 'max:255'],
            'type'               => ['required', Rule::in(self::ALLOWED_TYPES)],
            'description'        => ['required', 'string', 'max:5000'],
            'price'              => ['required', 'numeric', 'min:100'],
            'commission_percent' => ['required', 'numeric', 'min:0', 'max:100'],
            'stock'                => ['required_if:type,physical', 'nullable', 'integer', 'min:0'],
            'delivery_type'        => ['required_if:type,physical', 'nullable', Rule::in(self::ALLOWED_DELIVERY)],
            'delivery_fee'         => ['required_if:delivery_type,fixed', 'nullable', 'numeric', 'min:0'],
            'digital_delivery_type'=> ['required_if:type,digital', 'nullable', Rule::in(self::ALLOWED_DIGITAL_DELIVERY)],
            'access_url'           => ['nullable', 'url', 'max:2048'],
            'digital_files'        => ['nullable', 'array', 'max:' . self::MAX_DIGITAL_FILES],
            'digital_files.*'      => ['file', 'mimetypes:' . self::DIGITAL_FILE_MIMES, 'max:' . self::MAX_DIGITAL_FILE_KB],
            'images'               => ['nullable', 'array', 'max:' . self::MAX_IMAGES],
            'images.*'             => ['file', 'mimetypes:' . self::IMAGE_MIMES, 'max:' . self::MAX_IMAGE_SIZE_KB],
        ], [
            'name.required'               => 'Le nom du produit est obligatoire.',
            'type.required'               => 'Veuillez selectionner le type de produit.',
            'type.in'                     => 'Type de produit invalide.',
            'description.required'        => 'La description est obligatoire.',
            'price.required'              => 'Le prix est obligatoire.',
            'price.min'                   => 'Le prix minimum est de 100 FCFA.',
            'commission_percent.required' => 'Le pourcentage de commission est obligatoire.',
            'commission_percent.min'      => 'La commission ne peut pas etre negative.',
            'commission_percent.max'      => 'La commission ne peut pas depasser 100%.',
            'stock.required_if'           => 'La quantite en stock est requise pour un produit physique.',
            'stock.min'                   => 'Le stock ne peut pas etre negatif.',
            'delivery_type.required_if'   => 'Veuillez choisir une politique de livraison pour un produit physique.',
            'delivery_type.in'            => 'Option de livraison invalide.',
            'delivery_fee.required_if'    => 'Les frais de livraison sont requis pour l\'option "Frais fixes".',
            'delivery_fee.min'            => 'Les frais de livraison ne peuvent pas etre negatifs.',
            'digital_delivery_type.required_if' => 'Veuillez choisir le mode de livraison digital (lien ou fichiers).',
            'access_url.url'              => 'Le lien d\'acces doit etre une URL valide.',
            'digital_files.max'           => 'Vous pouvez ajouter jusqu\'a ' . self::MAX_DIGITAL_FILES . ' fichiers.',
            'digital_files.*.mimetypes'   => 'Format accepte : Video (MP4, MOV, AVI), PDF, ZIP, RAR, Images, Audio, Word, PowerPoint.',
            'digital_files.*.max'         => 'Chaque fichier ne doit pas depasser 100 Mo.',
            'images.max'                  => 'Vous pouvez ajouter jusqu\'a ' . self::MAX_IMAGES . ' images.',
            'images.*.mimetypes'          => 'Format accepte : JPEG, PNG ou WebP.',
            'images.*.max'                => 'Chaque image ne doit pas depasser 5 Mo.',
        ]);

        // Validation conditionnelle digital
        $digitalDeliveryType = $validated['digital_delivery_type'] ?? null;
        if ($validated['type'] === 'digital') {
            if ($digitalDeliveryType === 'link' && empty($validated['access_url'])) {
                return back()->withErrors(['access_url' => 'Le lien d\'acces est requis.'])->withInput();
            }
            if ($digitalDeliveryType === 'file' && !$request->hasFile('digital_files')) {
                return back()->withErrors(['digital_files' => 'Veuillez uploader au moins un fichier.'])->withInput();
            }
        }

        // ── Zip des fichiers digitaux si necessaire ──
        $digitalFilePath = null;
        if ($validated['type'] === 'digital' && $digitalDeliveryType === 'file' && $request->hasFile('digital_files')) {
            $digitalFilePath = $this->zipDigitalFiles($request->file('digital_files'), auth()->id());
        }

        // ── Creation du produit ──
        $product = Product::create([
            'vendor_id'             => auth()->id(),
            'name'                  => $validated['name'],
            'type'                  => $validated['type'],
            'description'           => $validated['description'],
            'price'                 => $validated['price'],
            'commission_percent'    => $validated['commission_percent'],
            'stock'                 => $validated['type'] === 'physical' ? $validated['stock'] : null,
            'delivery_type'         => $validated['type'] === 'physical' ? ($validated['delivery_type'] ?? null) : null,
            'delivery_fee'          => $validated['type'] === 'physical' && ($validated['delivery_type'] ?? null) === 'fixed'
                                            ? $validated['delivery_fee'] : null,
            'digital_delivery_type' => $validated['type'] === 'digital' ? $digitalDeliveryType : null,
            'access_url'            => $validated['type'] === 'digital' && $digitalDeliveryType === 'link'
                                            ? ($validated['access_url'] ?? null) : null,
            'digital_file_path'     => $digitalFilePath,
        ]);

        // ── Upload images (multi) ──
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $index => $file) {
                $path = $file->store('products', 'public');
                $product->images()->create([
                    'path'       => $path,
                    'sort_order' => $index,
                ]);
            }
            // Premiere image → image_path (retrocompatibilite)
            $first = $product->images()->orderBy('sort_order')->first();
            if ($first) {
                $product->update(['image_path' => $first->path]);
            }
        }

        return redirect()
            ->route('vendor.products.index')
            ->with('success', 'Produit cree avec succes.');
    }

    // ──────────────────────────────────────────────
    //  Formulaire d'edition
    // ──────────────────────────────────────────────

    /**
     * Affiche le formulaire d'edition d'un produit existant.
     */
    public function edit(Product $product): InertiaResponse
    {
        $this->authorizeVendor($product);

        $product->load('images:id,product_id,path,sort_order');

        return Inertia::render('Products/Edit', [
            'product' => $product,
        ]);
    }

    // ──────────────────────────────────────────────
    //  Mise a jour
    // ──────────────────────────────────────────────

    /**
     * Valide et met a jour un produit existant.
     */
    public function update(Request $request, Product $product): RedirectResponse
    {
        $this->authorizeVendor($product);

        $validated = $request->validate([
            'name'               => ['required', 'string', 'max:255'],
            'type'               => ['required', Rule::in(self::ALLOWED_TYPES)],
            'description'        => ['required', 'string', 'max:5000'],
            'price'              => ['required', 'numeric', 'min:100'],
            'commission_percent' => ['required', 'numeric', 'min:0', 'max:100'],
            'stock'                => ['required_if:type,physical', 'nullable', 'integer', 'min:0'],
            'delivery_type'        => ['required_if:type,physical', 'nullable', Rule::in(self::ALLOWED_DELIVERY)],
            'delivery_fee'         => ['required_if:delivery_type,fixed', 'nullable', 'numeric', 'min:0'],
            'digital_delivery_type'=> ['required_if:type,digital', 'nullable', Rule::in(self::ALLOWED_DIGITAL_DELIVERY)],
            'access_url'           => ['nullable', 'url', 'max:2048'],
            'digital_files'        => ['nullable', 'array', 'max:' . self::MAX_DIGITAL_FILES],
            'digital_files.*'      => ['file', 'mimetypes:' . self::DIGITAL_FILE_MIMES, 'max:' . self::MAX_DIGITAL_FILE_KB],
            'images'             => ['nullable', 'array', 'max:' . self::MAX_IMAGES],
            'images.*'           => ['file', 'mimetypes:' . self::IMAGE_MIMES, 'max:' . self::MAX_IMAGE_SIZE_KB],
            'removed_image_ids'  => ['nullable', 'array'],
            'removed_image_ids.*'=> ['integer', 'exists:product_images,id'],
            'image_order'        => ['nullable', 'array'],
            'image_order.*'      => ['integer'],
        ]);

        $digitalDeliveryType = $validated['digital_delivery_type'] ?? null;

        // Validation conditionnelle digital
        if ($validated['type'] === 'digital' && $digitalDeliveryType === 'link' && empty($validated['access_url'])) {
            return back()->withErrors(['access_url' => 'Le lien d\'acces est requis.'])->withInput();
        }

        // ── Suppression d'images selectionnees ──
        if (! empty($validated['removed_image_ids'])) {
            $toRemove = $product->images()
                ->whereIn('id', $validated['removed_image_ids'])
                ->get();
            foreach ($toRemove as $img) {
                Storage::disk('public')->delete($img->path);
                $img->delete();
            }
        }

        // ── Reordonnancement des images existantes ──
        if (! empty($validated['image_order'])) {
            foreach ($validated['image_order'] as $sort => $imageId) {
                $product->images()->where('id', (int) $imageId)->update(['sort_order' => $sort]);
            }
        }

        // ── Ajout de nouvelles images ──
        if ($request->hasFile('images')) {
            $maxSort = $product->images()->max('sort_order') ?? -1;
            foreach ($request->file('images') as $file) {
                $maxSort++;
                $path = $file->store('products', 'public');
                $product->images()->create([
                    'path'       => $path,
                    'sort_order' => $maxSort,
                ]);
            }
        }

        // ── Synchroniser image_path (retrocompatibilite) ──
        $firstImage = $product->images()->orderBy('sort_order')->first();
        $imagePath  = $firstImage?->path;

        // ── Zip des fichiers digitaux si re-upload ──
        $digitalFilePath = $product->digital_file_path;
        if ($validated['type'] === 'digital' && ($digitalDeliveryType ?? null) === 'file' && $request->hasFile('digital_files')) {
            // Supprimer l'ancien zip
            if ($product->digital_file_path) {
                Storage::disk('local')->delete($product->digital_file_path);
            }
            $digitalFilePath = $this->zipDigitalFiles($request->file('digital_files'), auth()->id());
        }
        // Si on passe de file → link, supprimer l'ancien zip
        if ($validated['type'] === 'digital' && ($digitalDeliveryType ?? null) === 'link' && $product->digital_file_path) {
            Storage::disk('local')->delete($product->digital_file_path);
            $digitalFilePath = null;
        }
        // Si on passe de digital → physical, nettoyer
        if ($validated['type'] === 'physical' && $product->digital_file_path) {
            Storage::disk('local')->delete($product->digital_file_path);
            $digitalFilePath = null;
        }

        $product->update([
            'name'                  => $validated['name'],
            'type'                  => $validated['type'],
            'description'           => $validated['description'],
            'price'                 => $validated['price'],
            'commission_percent'    => $validated['commission_percent'],
            'stock'                 => $validated['type'] === 'physical' ? $validated['stock'] : null,
            'delivery_type'         => $validated['type'] === 'physical' ? ($validated['delivery_type'] ?? null) : null,
            'delivery_fee'          => $validated['type'] === 'physical' && ($validated['delivery_type'] ?? null) === 'fixed'
                                            ? $validated['delivery_fee'] : null,
            'digital_delivery_type' => $validated['type'] === 'digital' ? $digitalDeliveryType : null,
            'access_url'            => $validated['type'] === 'digital' && ($digitalDeliveryType ?? null) === 'link'
                                            ? ($validated['access_url'] ?? null) : null,
            'digital_file_path'     => $validated['type'] === 'digital' ? $digitalFilePath : null,
            'image_path'            => $imagePath,
        ]);

        return redirect()
            ->route('vendor.products.index')
            ->with('success', 'Produit mis a jour avec succes.');
    }

    // ──────────────────────────────────────────────
    //  Suppression
    // ──────────────────────────────────────────────

    /**
     * Supprime un produit du vendeur.
     */
    public function destroy(Product $product): RedirectResponse
    {
        $this->authorizeVendor($product);

        // Bloquer la suppression si des commandes actives existent
        $activeOrders = Order::where('product_id', $product->id)
            ->whereIn('status', ['pending', 'shipped'])
            ->exists();

        if ($activeOrders) {
            return back()->withErrors(['product' => 'Impossible de supprimer ce produit : des commandes en cours existent.']);
        }

        // Supprimer les commandes terminees/annulees liees
        Order::where('product_id', $product->id)->delete();

        // Detacher le produit des campagnes
        Campaign::where('product_id', $product->id)->update(['product_id' => null]);

        // Supprimer toutes les images de la galerie
        foreach ($product->images as $img) {
            Storage::disk('public')->delete($img->path);
        }
        // Supprimer l'ancienne image_path si elle ne fait pas partie de la galerie
        if ($product->image_path) {
            Storage::disk('public')->delete($product->image_path);
        }
        // Supprimer le fichier zip digital
        if ($product->digital_file_path) {
            Storage::disk('local')->delete($product->digital_file_path);
        }

        $product->delete();

        return redirect()
            ->route('vendor.products.index')
            ->with('success', 'Produit supprime.');
    }

    // ──────────────────────────────────────────────
    //  Format d'affichage boutique
    // ──────────────────────────────────────────────

    /**
     * Met a jour le format d'affichage des produits dans la boutique.
     */
    public function updateShopFormat(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'shop_display_format' => ['required', 'string', Rule::in(self::ALLOWED_FORMATS)],
        ]);

        auth()->user()->update([
            'shop_display_format' => $validated['shop_display_format'],
        ]);

        return redirect()
            ->route('vendor.products.index')
            ->with('success', 'Format d\'affichage mis a jour.');
    }

    // ──────────────────────────────────────────────
    //  Zip helper pour fichiers digitaux
    // ──────────────────────────────────────────────

    /**
     * Zippe les fichiers uploades et les stocke dans le disque local (prive).
     *
     * @param  array<\Illuminate\Http\UploadedFile>  $files
     * @return string  Chemin relatif du zip sur le disque local
     */
    private function zipDigitalFiles(array $files, int $vendorId): string
    {
        $zipName = 'digital-products/' . $vendorId . '/' . Str::uuid() . '.zip';
        $zipFullPath = storage_path('app/private/' . $zipName);

        // Creer le dossier si besoin
        $dir = dirname($zipFullPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $zip = new ZipArchive();
        if ($zip->open($zipFullPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            abort(500, 'Impossible de creer l\'archive ZIP.');
        }

        foreach ($files as $file) {
            $zip->addFile(
                $file->getRealPath(),
                $file->getClientOriginalName()
            );
        }

        $zip->close();

        return $zipName;
    }

    // ──────────────────────────────────────────────
    //  Guard helper
    // ──────────────────────────────────────────────

    /**
     * Verifie que le produit appartient au vendeur connecte.
     */
    private function authorizeVendor(Product $product): void
    {
        abort_unless((int) $product->vendor_id === (int) auth()->id(), 403);
    }
}
