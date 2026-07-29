<script setup>
import VendorLayout from '../../Layouts/VendorLayout.vue';
import { Head, useForm, Link } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

// ──────────────────────────────────────────────
//  Form
// ──────────────────────────────────────────────

const form = useForm({
    name: '',
    type: '',               // 'physical' | 'digital'
    description: '',
    price: '',
    commission_percent: '',
    stock: '',
    delivery_type: '',      // 'free' | 'fixed' | 'pay_on_delivery'
    delivery_fee: '',
    access_url: '',
    images: [],
});

const MAX_IMAGES = 8;

// ──────────────────────────────────────────────
//  Type helpers
// ──────────────────────────────────────────────

const isPhysical = computed(() => form.type === 'physical');
const isDigital  = computed(() => form.type === 'digital');

function selectType(type) {
    form.type = type;
    // Reset champs conditionnels
    if (type === 'physical') {
        form.access_url = '';
    } else {
        form.stock = '';
        form.delivery_type = '';
        form.delivery_fee = '';
    }
}

// ──────────────────────────────────────────────
//  Image preview
// ──────────────────────────────────────────────

// ──────────────────────────────────────────────
//  Multi-image management
// ──────────────────────────────────────────────

const imagePreviews = ref([]);  // { file, url }
const isDragOver    = ref(false);

function handleFilesSelected(event) {
    const files = Array.from(event.target.files || []);
    addFiles(files);
    event.target.value = '';
}

function handleDrop(event) {
    isDragOver.value = false;
    const files = Array.from(event.dataTransfer?.files || []);
    addFiles(files);
}

function addFiles(files) {
    const allowed = ['image/jpeg', 'image/png', 'image/webp'];
    for (const file of files) {
        if (imagePreviews.value.length >= MAX_IMAGES) break;
        if (!allowed.includes(file.type)) continue;
        if (file.size > 5 * 1024 * 1024) continue;
        imagePreviews.value.push({ file, url: URL.createObjectURL(file) });
    }
    syncFormImages();
}

function removeImage(index) {
    const removed = imagePreviews.value.splice(index, 1);
    if (removed[0]?.url) URL.revokeObjectURL(removed[0].url);
    syncFormImages();
}

function moveImage(from, to) {
    if (to < 0 || to >= imagePreviews.value.length) return;
    const item = imagePreviews.value.splice(from, 1)[0];
    imagePreviews.value.splice(to, 0, item);
    syncFormImages();
}

function syncFormImages() {
    form.images = imagePreviews.value.map(p => p.file);
}

// ──────────────────────────────────────────────
//  Preview prix formaté
// ──────────────────────────────────────────────

function formatCurrency(amount) {
    const v = parseFloat(amount);
    if (!v || v < 0) return '0 FCFA';
    return new Intl.NumberFormat('fr-FR', {
        style: 'decimal',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    }).format(v) + ' FCFA';
}

const commissionAmount = computed(() => {
    const price = parseFloat(form.price);
    const pct   = parseFloat(form.commission_percent);
    if (!price || !pct || price < 0 || pct < 0) return 0;
    return Math.round(price * pct / 100);
});

// ──────────────────────────────────────────────
//  Submit
// ──────────────────────────────────────────────

function submit() {
    form.post(route('vendor.products.store'), {
        preserveScroll: true,
        forceFormData: true,
    });
}
</script>

<template>
    <Head title="Nouveau produit" />

    <VendorLayout>
        <div class="space-y-6">

            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-xl font-bold text-slate-900">Nouveau produit</h1>
                    <p class="mt-1 text-sm text-slate-500">Ajoutez un produit physique ou digital a votre boutique.</p>
                </div>
                <Link
                    :href="route('vendor.products.index')"
                    class="inline-flex items-center gap-1.5 rounded-full border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-600 shadow-sm transition hover:bg-slate-50"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
                    </svg>
                    Retour
                </Link>
            </div>

            <form @submit.prevent="submit" class="space-y-6">

                <!-- ─────────────────────────────────────
                     SECTION 1 — Type de produit
                ───────────────────────────────────── -->
                <div class="rounded-2xl border border-slate-200/80 bg-white p-6 shadow-sm">
                    <div class="mb-5">
                        <h3 class="flex items-center gap-2 text-base font-semibold text-slate-900">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-purple-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9.568 3H5.25A2.25 2.25 0 003 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 005.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 009.568 3z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 6h.008v.008H6V6z" />
                            </svg>
                            Type de produit
                        </h3>
                        <p class="mt-1 text-sm text-slate-500">Selectionnez la nature de votre produit.</p>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <!-- Carte Physique -->
                        <button
                            type="button"
                            class="relative flex flex-col items-center gap-3 rounded-xl border-2 p-6 text-center transition"
                            :class="form.type === 'physical'
                                ? 'border-purple-500 bg-purple-50 ring-1 ring-purple-500/20'
                                : 'border-slate-200 bg-white hover:border-slate-300'"
                            @click="selectType('physical')"
                        >
                            <!-- SVG Cube/Box — Heroicon -->
                            <div class="flex h-14 w-14 items-center justify-center rounded-xl transition"
                                :class="form.type === 'physical' ? 'bg-purple-100' : 'bg-slate-100'"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" :class="form.type === 'physical' ? 'text-purple-600' : 'text-slate-400'" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 7.5l-9-5.25L3 7.5m18 0l-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-9v9" />
                                </svg>
                            </div>
                            <div>
                                <span class="text-sm font-semibold" :class="form.type === 'physical' ? 'text-purple-700' : 'text-slate-900'">
                                    Produit Physique
                                </span>
                                <p class="mt-1 text-xs leading-relaxed" :class="form.type === 'physical' ? 'text-purple-600/80' : 'text-slate-500'">
                                    Objet materiel avec gestion de stock et livraison.
                                </p>
                            </div>
                            <!-- Radio indicator -->
                            <div class="absolute right-3 top-3 flex h-5 w-5 items-center justify-center rounded-full border-2 transition"
                                :class="form.type === 'physical' ? 'border-purple-500' : 'border-slate-300'"
                            >
                                <div v-if="form.type === 'physical'" class="h-2.5 w-2.5 rounded-full bg-purple-500" />
                            </div>
                        </button>

                        <!-- Carte Digital -->
                        <button
                            type="button"
                            class="relative flex flex-col items-center gap-3 rounded-xl border-2 p-6 text-center transition"
                            :class="form.type === 'digital'
                                ? 'border-purple-500 bg-purple-50 ring-1 ring-purple-500/20'
                                : 'border-slate-200 bg-white hover:border-slate-300'"
                            @click="selectType('digital')"
                        >
                            <!-- SVG Cloud/Link — Heroicon -->
                            <div class="flex h-14 w-14 items-center justify-center rounded-xl transition"
                                :class="form.type === 'digital' ? 'bg-purple-100' : 'bg-slate-100'"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" :class="form.type === 'digital' ? 'text-purple-600' : 'text-slate-400'" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 16.5V9.75m0 0l3 3m-3-3l-3 3M6.75 19.5a4.5 4.5 0 01-1.41-8.775 5.25 5.25 0 0110.233-2.33 3 3 0 013.758 3.848A3.752 3.752 0 0118 19.5H6.75z" />
                                </svg>
                            </div>
                            <div>
                                <span class="text-sm font-semibold" :class="form.type === 'digital' ? 'text-purple-700' : 'text-slate-900'">
                                    Produit Digital
                                </span>
                                <p class="mt-1 text-xs leading-relaxed" :class="form.type === 'digital' ? 'text-purple-600/80' : 'text-slate-500'">
                                    Fichier, formation, lien de telechargement ou acces en ligne.
                                </p>
                            </div>
                            <!-- Radio indicator -->
                            <div class="absolute right-3 top-3 flex h-5 w-5 items-center justify-center rounded-full border-2 transition"
                                :class="form.type === 'digital' ? 'border-purple-500' : 'border-slate-300'"
                            >
                                <div v-if="form.type === 'digital'" class="h-2.5 w-2.5 rounded-full bg-purple-500" />
                            </div>
                        </button>
                    </div>
                    <p v-if="form.errors.type" class="mt-2 text-sm text-red-600">{{ form.errors.type }}</p>
                </div>

                <!-- ─────────────────────────────────────
                     SECTION 2 — Configuration specifique
                ───────────────────────────────────── -->
                <div v-if="form.type" class="rounded-2xl border border-slate-200/80 bg-white p-6 shadow-sm">
                    <div class="mb-5">
                        <h3 class="flex items-center gap-2 text-base font-semibold text-slate-900">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-purple-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6h9.75M10.5 6a1.5 1.5 0 11-3 0m3 0a1.5 1.5 0 10-3 0M3.75 6H7.5m3 12h9.75m-9.75 0a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m-3.75 0H7.5m9-6h3.75m-3.75 0a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m-9.75 0h9.75" />
                            </svg>
                            Configuration specifique
                        </h3>
                        <p class="mt-1 text-sm text-slate-500">{{ isPhysical ? 'Definissez le stock disponible pour votre produit physique.' : 'Renseignez le lien d\'acces pour votre produit digital.' }}</p>
                    </div>

                    <div class="space-y-5">

                        <!-- ── Champ conditionnel : Stock (Physique) ── -->
                        <div v-if="isPhysical">
                            <label for="stock" class="block text-sm font-medium text-slate-700 mb-1">
                                Quantite en stock
                            </label>
                            <input
                                id="stock"
                                v-model="form.stock"
                                type="number"
                                min="0"
                                step="1"
                                placeholder="50"
                                class="block w-full rounded-lg border-slate-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 sm:text-sm"
                            />
                            <p class="mt-1 text-xs text-slate-400">Nombre d'unites disponibles a la vente.</p>
                            <p v-if="form.errors.stock" class="mt-1.5 text-sm text-red-600">{{ form.errors.stock }}</p>
                        </div>

                        <!-- ── Politique de livraison (Physique uniquement) ── -->
                        <div v-if="isPhysical" class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-2">
                                    Politique de livraison
                                </label>
                                <div class="space-y-2">
                                    <!-- Option A — Livraison gratuite -->
                                    <label
                                        class="flex items-center gap-3 rounded-lg border px-4 py-3 cursor-pointer transition"
                                        :class="form.delivery_type === 'free'
                                            ? 'border-purple-500 bg-purple-50 ring-1 ring-purple-500/20'
                                            : 'border-slate-200 hover:border-slate-300'"
                                    >
                                        <input
                                            type="radio"
                                            name="delivery_type"
                                            value="free"
                                            v-model="form.delivery_type"
                                            class="h-4 w-4 border-slate-300 text-purple-600 focus:ring-purple-500"
                                            @change="form.delivery_fee = ''"
                                        />
                                        <div class="flex items-center gap-2 flex-1">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" :class="form.delivery_type === 'free' ? 'text-purple-600' : 'text-slate-400'" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M21 11.25v8.25a1.5 1.5 0 01-1.5 1.5H5.25a1.5 1.5 0 01-1.5-1.5v-8.25M12 4.875A2.625 2.625 0 109.375 7.5H12m0-2.625V7.5m0-2.625A2.625 2.625 0 1114.625 7.5H12m0 0V21m-8.625-9.75h18c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125h-18c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z" />
                                            </svg>
                                            <div>
                                                <span class="text-sm font-medium" :class="form.delivery_type === 'free' ? 'text-purple-700' : 'text-slate-900'">Livraison gratuite</span>
                                                <p class="text-xs" :class="form.delivery_type === 'free' ? 'text-purple-600/70' : 'text-slate-400'">La livraison est offerte au client.</p>
                                            </div>
                                        </div>
                                    </label>

                                    <!-- Option B — Frais fixes -->
                                    <label
                                        class="flex items-center gap-3 rounded-lg border px-4 py-3 cursor-pointer transition"
                                        :class="form.delivery_type === 'fixed'
                                            ? 'border-purple-500 bg-purple-50 ring-1 ring-purple-500/20'
                                            : 'border-slate-200 hover:border-slate-300'"
                                    >
                                        <input
                                            type="radio"
                                            name="delivery_type"
                                            value="fixed"
                                            v-model="form.delivery_type"
                                            class="h-4 w-4 border-slate-300 text-purple-600 focus:ring-purple-500"
                                        />
                                        <div class="flex items-center gap-2 flex-1">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" :class="form.delivery_type === 'fixed' ? 'text-purple-600' : 'text-slate-400'" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75M15 10.5a3 3 0 11-6 0 3 3 0 016 0zm3 0h.008v.008H18V10.5zm-12 0h.008v.008H6V10.5z" />
                                            </svg>
                                            <div>
                                                <span class="text-sm font-medium" :class="form.delivery_type === 'fixed' ? 'text-purple-700' : 'text-slate-900'">Frais fixes payes a la commande</span>
                                                <p class="text-xs" :class="form.delivery_type === 'fixed' ? 'text-purple-600/70' : 'text-slate-400'">Le client paie un montant fixe de livraison au moment de l'achat.</p>
                                            </div>
                                        </div>
                                    </label>

                                    <!-- Option C — Paiement au livreur -->
                                    <label
                                        class="flex items-center gap-3 rounded-lg border px-4 py-3 cursor-pointer transition"
                                        :class="form.delivery_type === 'pay_on_delivery'
                                            ? 'border-purple-500 bg-purple-50 ring-1 ring-purple-500/20'
                                            : 'border-slate-200 hover:border-slate-300'"
                                    >
                                        <input
                                            type="radio"
                                            name="delivery_type"
                                            value="pay_on_delivery"
                                            v-model="form.delivery_type"
                                            class="h-4 w-4 border-slate-300 text-purple-600 focus:ring-purple-500"
                                            @change="form.delivery_fee = ''"
                                        />
                                        <div class="flex items-center gap-2 flex-1">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" :class="form.delivery_type === 'pay_on_delivery' ? 'text-purple-600' : 'text-slate-400'" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 01-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 00-3.213-9.193 2.056 2.056 0 00-1.58-.86H14.25M16.5 18.75h-2.25m0-11.177v-.958c0-.568-.422-1.048-.987-1.106a48.554 48.554 0 00-10.026 0 1.106 1.106 0 00-.987 1.106v7.635m12-6.677v6.677m0 4.5v-4.5m0 0h-12" />
                                            </svg>
                                            <div>
                                                <span class="text-sm font-medium" :class="form.delivery_type === 'pay_on_delivery' ? 'text-purple-700' : 'text-slate-900'">Frais payes en especes au livreur</span>
                                                <p class="text-xs" :class="form.delivery_type === 'pay_on_delivery' ? 'text-purple-600/70' : 'text-slate-400'">Le client paie le livreur directement a la reception.</p>
                                            </div>
                                        </div>
                                    </label>
                                </div>
                                <p v-if="form.errors.delivery_type" class="mt-1.5 text-sm text-red-600">{{ form.errors.delivery_type }}</p>
                            </div>

                            <!-- Input frais fixes (visible seulement si fixed) -->
                            <div v-if="form.delivery_type === 'fixed'">
                                <label for="delivery_fee" class="block text-sm font-medium text-slate-700 mb-1">
                                    Montant des frais de livraison (FCFA)
                                </label>
                                <input
                                    id="delivery_fee"
                                    v-model="form.delivery_fee"
                                    type="number"
                                    min="0"
                                    step="50"
                                    placeholder="1000"
                                    class="block w-full rounded-lg border-slate-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 sm:text-sm"
                                />
                                <p class="mt-1 text-xs text-slate-400">Ce montant sera ajoute au prix du produit lors du checkout.</p>
                                <p v-if="form.errors.delivery_fee" class="mt-1.5 text-sm text-red-600">{{ form.errors.delivery_fee }}</p>
                            </div>
                        </div>

                        <!-- ── Champ conditionnel : Lien d'acces (Digital) ── -->
                        <div v-if="isDigital">
                            <label for="access_url" class="block text-sm font-medium text-slate-700 mb-1">
                                Lien d'acces ou de telechargement
                            </label>
                            <div class="relative">
                                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.19 8.688a4.5 4.5 0 011.242 7.244l-4.5 4.5a4.5 4.5 0 01-6.364-6.364l1.757-1.757m9.86-2.04a4.5 4.5 0 00-1.242-7.244l-4.5-4.5a4.5 4.5 0 00-6.364 6.364L4.343 8.28" />
                                    </svg>
                                </div>
                                <input
                                    id="access_url"
                                    v-model="form.access_url"
                                    type="url"
                                    placeholder="https://drive.google.com/... ou https://votre-site.com/download"
                                    class="block w-full rounded-lg border-slate-300 pl-10 shadow-sm focus:border-purple-500 focus:ring-purple-500 sm:text-sm"
                                />
                            </div>
                            <p class="mt-1 text-xs text-slate-400">Le lien sera communique a l'acheteur apres paiement.</p>
                            <p v-if="form.errors.access_url" class="mt-1.5 text-sm text-red-600">{{ form.errors.access_url }}</p>
                        </div>
                    </div>
                </div>

                <!-- ─────────────────────────────────────
                     SECTION 3 — Commission Affiliation
                ───────────────────────────────────── -->
                <div v-if="form.type" class="rounded-2xl border border-slate-200/80 bg-white p-6 shadow-sm">
                    <div class="mb-5">
                        <h3 class="flex items-center gap-2 text-base font-semibold text-slate-900">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-purple-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75M15 10.5a3 3 0 11-6 0 3 3 0 016 0zm3 0h.008v.008H18V10.5zm-12 0h.008v.008H6V10.5z" />
                            </svg>
                            Commission Affiliation
                        </h3>
                        <p class="mt-1 text-sm text-slate-500">
                            Definissez le pourcentage reverse aux createurs de contenu pour chaque vente generee.
                        </p>
                    </div>

                    <div class="space-y-4">
                        <div>
                            <label for="commission_percent" class="block text-sm font-medium text-slate-700 mb-1">
                                Pourcentage de commission (%)
                            </label>
                            <input
                                id="commission_percent"
                                v-model="form.commission_percent"
                                type="number"
                                min="0"
                                max="100"
                                step="0.5"
                                placeholder="10"
                                class="block w-full rounded-lg border-slate-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 sm:text-sm"
                            />
                            <p v-if="form.errors.commission_percent" class="mt-1.5 text-sm text-red-600">{{ form.errors.commission_percent }}</p>
                        </div>

                        <!-- Explication + preview -->
                        <div class="rounded-lg border border-purple-200 bg-purple-50/50 p-4">
                            <div class="flex items-start gap-3">
                                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-purple-100">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-purple-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" />
                                    </svg>
                                </div>
                                <div class="text-sm">
                                    <p class="font-medium text-purple-800">Comment fonctionne la commission ?</p>
                                    <p class="mt-1 text-purple-700/80">
                                        Ce pourcentage sera automatiquement deduit du prix de vente et reverse a le createur de contenu
                                        qui genere la vente via son lien public. Par exemple, avec une commission de 10% sur
                                        un produit a 5 000 FCFA, le createur de contenu recevra 500 FCFA par vente.
                                    </p>
                                </div>
                            </div>

                            <!-- Calcul dynamique -->
                            <div v-if="commissionAmount > 0" class="mt-3 flex items-center gap-2 rounded-lg bg-white/80 px-3 py-2">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-purple-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 15.75V18m-7.5-6.75h.008v.008H8.25v-.008zm0 2.25h.008v.008H8.25V13.5zm0 2.25h.008v.008H8.25v-.008zm0 2.25h.008v.008H8.25V18zm2.498-6.75h.007v.008h-.007v-.008zm0 2.25h.007v.008h-.007V13.5zm0 2.25h.007v.008h-.007v-.008zm0 2.25h.007v.008h-.007V18zm2.504-6.75h.008v.008h-.008v-.008zm0 2.25h.008v.008h-.008V13.5zm0 2.25h.008v.008h-.008v-.008zm0 2.25h.008v.008h-.008V18zm2.498-6.75h.008v.008H18v-.008zm0 2.25h.008v.008H18V13.5zM15.75 18.75h.008v.008h-.008v-.008z" />
                                </svg>
                                <span class="text-xs font-medium text-slate-600">
                                    Prix {{ formatCurrency(form.price) }} x {{ form.commission_percent }}% =
                                    <span class="font-bold text-purple-700">{{ formatCurrency(commissionAmount) }}</span>
                                    par vente pour le createur de contenu
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ─────────────────────────────────────
                     SECTION 4 — Informations du produit
                ───────────────────────────────────── -->
                <div v-if="form.type" class="rounded-2xl border border-slate-200/80 bg-white p-6 shadow-sm">
                    <div class="mb-5">
                        <h3 class="flex items-center gap-2 text-base font-semibold text-slate-900">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-purple-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                            </svg>
                            Informations du produit
                        </h3>
                        <p class="mt-1 text-sm text-slate-500">Details, prix et image de couverture.</p>
                    </div>

                    <div class="space-y-5">

                        <!-- Nom -->
                        <div>
                            <label for="name" class="block text-sm font-medium text-slate-700 mb-1">
                                Nom du produit
                            </label>
                            <input
                                id="name"
                                v-model="form.name"
                                type="text"
                                maxlength="255"
                                placeholder="Ex : Pack cosmetiques bio"
                                class="block w-full rounded-lg border-slate-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 sm:text-sm"
                            />
                            <p v-if="form.errors.name" class="mt-1.5 text-sm text-red-600">{{ form.errors.name }}</p>
                        </div>

                        <!-- Description -->
                        <div>
                            <label for="description" class="block text-sm font-medium text-slate-700 mb-1">
                                Description
                            </label>
                            <textarea
                                id="description"
                                v-model="form.description"
                                rows="4"
                                maxlength="5000"
                                placeholder="Decrivez votre produit en detail..."
                                class="block w-full rounded-lg border-slate-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 sm:text-sm"
                            ></textarea>
                            <p v-if="form.errors.description" class="mt-1.5 text-sm text-red-600">{{ form.errors.description }}</p>
                        </div>

                        <!-- Prix -->
                        <div>
                            <label for="price" class="block text-sm font-medium text-slate-700 mb-1">
                                Prix (FCFA)
                            </label>
                            <input
                                id="price"
                                v-model="form.price"
                                type="number"
                                min="100"
                                step="50"
                                placeholder="5000"
                                class="block w-full rounded-lg border-slate-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 sm:text-sm"
                            />
                            <p v-if="form.errors.price" class="mt-1.5 text-sm text-red-600">{{ form.errors.price }}</p>
                        </div>

                        <!-- Images du produit — Multi-upload Drag & Drop -->
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-2">
                                Images du produit
                                <span class="text-xs font-normal text-slate-400 ml-1">({{ imagePreviews.length }}/{{ MAX_IMAGES }})</span>
                            </label>

                            <!-- Zone d'upload -->
                            <div
                                v-if="imagePreviews.length < MAX_IMAGES"
                                class="relative flex flex-col items-center justify-center rounded-xl border-2 border-dashed px-6 py-10 text-center transition-colors"
                                :class="isDragOver
                                    ? 'border-purple-400 bg-purple-50'
                                    : 'border-slate-300 bg-slate-50 hover:border-slate-400'"
                                @dragover.prevent="isDragOver = true"
                                @dragleave.prevent="isDragOver = false"
                                @drop.prevent="handleDrop"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909M2.25 18V6a2.25 2.25 0 012.25-2.25h15A2.25 2.25 0 0121.75 6v12A2.25 2.25 0 0119.5 20.25H4.5A2.25 2.25 0 012.25 18z" />
                                </svg>
                                <p class="mt-3 text-sm text-slate-600">
                                    <span class="font-semibold text-purple-600">Cliquez pour choisir</span>
                                    ou glissez-deposez ici
                                </p>
                                <p class="mt-1 text-xs text-slate-400">JPEG, PNG ou WebP — 5 Mo max par image — {{ MAX_IMAGES }} images max</p>
                                <input
                                    type="file"
                                    accept="image/jpeg,image/png,image/webp"
                                    multiple
                                    class="absolute inset-0 cursor-pointer opacity-0"
                                    @change="handleFilesSelected"
                                />
                            </div>

                            <!-- Apercus des images -->
                            <div v-if="imagePreviews.length" class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-4">
                                <div
                                    v-for="(img, idx) in imagePreviews"
                                    :key="idx"
                                    class="group relative aspect-square overflow-hidden rounded-xl border border-slate-200 bg-slate-50"
                                >
                                    <img :src="img.url" alt="Apercu" class="h-full w-full object-cover" />

                                    <!-- Badge premiere image = couverture -->
                                    <span v-if="idx === 0" class="absolute left-2 top-2 rounded-md bg-purple-600 px-1.5 py-0.5 text-[10px] font-bold text-white shadow">
                                        Couverture
                                    </span>

                                    <!-- Actions -->
                                    <div class="absolute inset-x-0 bottom-0 flex items-center justify-center gap-1 bg-gradient-to-t from-black/60 to-transparent px-2 py-2 opacity-0 transition group-hover:opacity-100">
                                        <button v-if="idx > 0" type="button" @click="moveImage(idx, idx - 1)" class="flex h-7 w-7 items-center justify-center rounded-full bg-white/90 text-slate-700 shadow transition hover:bg-white">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" /></svg>
                                        </button>
                                        <button type="button" @click="removeImage(idx)" class="flex h-7 w-7 items-center justify-center rounded-full bg-red-500 text-white shadow transition hover:bg-red-600">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                                        </button>
                                        <button v-if="idx < imagePreviews.length - 1" type="button" @click="moveImage(idx, idx + 1)" class="flex h-7 w-7 items-center justify-center rounded-full bg-white/90 text-slate-700 shadow transition hover:bg-white">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" /></svg>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <p v-if="form.errors.images" class="mt-1.5 text-sm text-red-600">{{ form.errors.images }}</p>
                        </div>
                    </div>
                </div>

                <!-- ─────────────────────────────────────
                     Recapitulatif
                ───────────────────────────────────── -->
                <div v-if="form.type && form.name && form.price" class="rounded-2xl border border-slate-200/80 bg-white p-6 shadow-sm">
                    <h4 class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wider text-slate-500 mb-3">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 6.75h12M8.25 12h12m-12 5.25h12M3.75 6.75h.007v.008H3.75V6.75zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zM3.75 12h.007v.008H3.75V12zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm-.375 5.25h.007v.008H3.75v-.008zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" />
                        </svg>
                        Recapitulatif
                    </h4>
                    <div class="grid gap-2 text-sm">
                        <div class="flex justify-between">
                            <span class="text-slate-500">Produit</span>
                            <span class="font-medium text-slate-900 truncate max-w-[220px]">{{ form.name }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-500">Type</span>
                            <span class="inline-flex items-center gap-1.5 font-medium text-slate-900">
                                <svg v-if="isPhysical" xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-purple-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 7.5l-9-5.25L3 7.5m18 0l-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-9v9" />
                                </svg>
                                <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-purple-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 16.5V9.75m0 0l3 3m-3-3l-3 3M6.75 19.5a4.5 4.5 0 01-1.41-8.775 5.25 5.25 0 0110.233-2.33 3 3 0 013.758 3.848A3.752 3.752 0 0118 19.5H6.75z" />
                                </svg>
                                {{ isPhysical ? 'Physique' : 'Digital' }}
                            </span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-500">Prix</span>
                            <span class="font-medium text-slate-900">{{ formatCurrency(form.price) }}</span>
                        </div>
                        <div v-if="isPhysical && form.stock" class="flex justify-between">
                            <span class="text-slate-500">Stock</span>
                            <span class="font-medium text-slate-900">{{ form.stock }} unites</span>
                        </div>
                        <div v-if="isPhysical && form.delivery_type" class="flex justify-between">
                            <span class="text-slate-500">Livraison</span>
                            <span class="font-medium" :class="form.delivery_type === 'free' ? 'text-emerald-600' : 'text-slate-900'">
                                {{ form.delivery_type === 'free' ? 'Offerte' : form.delivery_type === 'fixed' ? formatCurrency(form.delivery_fee) + ' (fixes)' : 'A payer au livreur' }}
                            </span>
                        </div>
                        <div v-if="form.commission_percent" class="flex justify-between">
                            <span class="text-slate-500">Commission createur de contenu</span>
                            <span class="font-medium text-purple-600">{{ form.commission_percent }}%</span>
                        </div>
                    </div>
                </div>

                <!-- ─────────────────────────────────────
                     Actions
                ───────────────────────────────────── -->
                <div class="flex items-center justify-end gap-3">
                    <Link
                        :href="route('vendor.products.index')"
                        class="rounded-lg border border-slate-300 bg-white px-5 py-2.5 text-sm font-medium text-slate-700 shadow-sm transition hover:bg-slate-50"
                    >
                        Annuler
                    </Link>
                    <button
                        type="submit"
                        :disabled="form.processing || !form.type"
                        class="inline-flex items-center gap-2 rounded-full bg-gradient-to-r from-purple-500 to-violet-600 px-6 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:from-purple-600 hover:to-violet-700 disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        <svg
                            v-if="form.processing"
                            class="h-4 w-4 animate-spin"
                            xmlns="http://www.w3.org/2000/svg"
                            fill="none"
                            viewBox="0 0 24 24"
                        >
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                        </svg>
                        <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                        </svg>
                        {{ form.processing ? 'Creation en cours...' : 'Creer le produit' }}
                    </button>
                </div>

            </form>
        </div>
    </VendorLayout>
</template>
