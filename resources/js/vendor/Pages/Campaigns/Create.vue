<script setup>
import VendorLayout from '../../Layouts/VendorLayout.vue';
import { Head, useForm, Link } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    available_balance: { type: Number, required: true },
    kyc_status:        { type: String, required: true },
    products:          { type: Array, default: () => [] },
    available_niches:  { type: Array, default: () => [] },
    tiers:             { type: Array, default: () => [] },
    tier_thresholds:   { type: Object, default: () => ({}) },
    is_admin:          { type: Boolean, default: false },
    min_cpc:           { type: Number, default: 25 },
});

// ═══════════════════════════════════════════════════════════════════
// MISSION 4 : DICTIONNAIRE STATIQUE COHERENT (Meme source que Checkout.vue)
// ═══════════════════════════════════════════════════════════════════
const locations = {
    'BJ': { name: 'Bénin', cities: ['Cotonou', 'Porto-Novo', 'Abomey-Calavi', 'Parakou', 'Bohicon', 'Autre...'] },
    'CI': { name: 'Côte d\'Ivoire', cities: ['Abidjan', 'Bouaké', 'Daloa', 'Yamoussoukro', 'San-Pédro', 'Autre...'] },
    'SN': { name: 'Sénégal', cities: ['Dakar', 'Thiès', 'Rufisque', 'Touba', 'Ziguinchor', 'Autre...'] },
    'TG': { name: 'Togo', cities: ['Lomé', 'Sokodé', 'Kara', 'Kpalimé', 'Atakpamé', 'Autre...'] },
    'CM': { name: 'Cameroun', cities: ['Douala', 'Yaoundé', 'Garoua', 'Bamenda', 'Maroua', 'Autre...'] },
};

const isKycApproved = computed(() => props.is_admin || props.kyc_status === 'approved');

const form = useForm({
    title: '',
    destination_type: 'shop',
    product_id: '',
    media: null,
    target_country: [],
    niche: '',
    instructions: '',
    click_price: '',
    platforms: [],
    total_budget: '',
    open_sea: false,
    is_system_campaign: false,
});

const estimatedClicks = computed(() => {
    const cpc = parseFloat(form.click_price) || props.min_cpc;
    const budget = parseFloat(form.total_budget) || 0;
    if (budget <= 0 || cpc <= 0) return 0;
    return Math.floor(budget / cpc);
});

const TIER_ARGENT = props.tier_thresholds?.argent || 25000;
const TIER_OR     = props.tier_thresholds?.or || 100000;

const currentTier = computed(() => {
    const tb = parseFloat(form.total_budget) || 0;
    if (tb >= TIER_OR) return 'or';
    if (tb >= TIER_ARGENT) return 'argent';
    return 'bronze';
});

const tierConfig = {
    bronze: {
        label: 'Bronze',
        type: 'Nano-createurs de contenu',
        desc: 'Acces aux Nano-createurs de contenu de votre Niche',
        range: '1k - 10k abonnes',
        gradient: 'from-amber-600/10 to-amber-600/5',
        border: 'border-amber-300',
        badge: 'bg-amber-100 text-amber-700 ring-amber-500/30',
        text: 'text-amber-700',
        bar: 'bg-amber-500',
    },
    argent: {
        label: 'Argent',
        type: 'Micro / Macro createurs de contenu',
        desc: 'Acces aux Micro/Macro createurs de contenu',
        range: '10k - 100k abonnes',
        gradient: 'from-purple-600/10 to-purple-600/5',
        border: 'border-purple-300',
        badge: 'bg-purple-100 text-purple-700 ring-purple-500/30',
        text: 'text-purple-700',
        bar: 'bg-purple-500',
    },
    or: {
        label: 'Or',
        type: 'Elites / Stars',
        desc: 'Acces aux Elites ! Priorite maximale sur la plateforme.',
        range: '100k - 1M+ abonnes',
        gradient: 'from-purple-600/10 to-purple-600/5',
        border: 'border-purple-300',
        badge: 'bg-purple-100 text-purple-700 ring-purple-500/30',
        text: 'text-purple-700',
        bar: 'bg-purple-500',
    },
};

const currentTierData = computed(() => tierConfig[currentTier.value]);

const tierProgress = computed(() => {
    const tb = parseFloat(form.total_budget) || 0;
    if (tb >= TIER_OR) return 100;
    if (tb >= TIER_ARGENT) return Math.min(((tb - TIER_ARGENT) / (TIER_OR - TIER_ARGENT)) * 100, 100);
    return Math.min((tb / TIER_ARGENT) * 100, 100);
});

/**
 * TARGET_COUNTRIES : Liste des codes pays disponibles (BJ, CI, SN, TG, CM).
 * Les codes sont stockes en base, pas les noms, pour eviter les conflits "Cote d'Ivoire" vs "CI".
 */
const targetCountries = computed(() => {
    return Object.entries(locations).map(([code, data]) => ({
        code,
        name: data.name,
    }));
});

function toggleCountry(countryCode) {
    const idx = form.target_country.indexOf(countryCode);
    if (idx === -1) {
        form.target_country.push(countryCode);
    } else {
        form.target_country.splice(idx, 1);
    }
}

const platformOptions = [
    { key: 'tiktok',    label: 'TikTok' },
    { key: 'facebook',  label: 'Facebook' },
    { key: 'instagram', label: 'Instagram' },
    { key: 'youtube',   label: 'YouTube' },
    { key: 'snapchat',  label: 'Snapchat' },
];

function togglePlatform(key) {
    const idx = form.platforms.indexOf(key);
    if (idx === -1) {
        form.platforms.push(key);
    } else {
        form.platforms.splice(idx, 1);
    }
}

function selectDestination(type) {
    form.destination_type = type;
    if (type !== 'product') form.product_id = '';
}

const selectedProduct = computed(() => {
    if (!form.product_id) return null;
    return props.products.find(p => p.id === Number(form.product_id)) || null;
});

const mediaPreviewUrl  = ref(null);
const mediaPreviewType = ref(null);
const isDragOver       = ref(false);

function handleFileSelected(event) {
    const file = event.target.files?.[0];
    if (file) applyMediaFile(file);
}

function handleDrop(event) {
    isDragOver.value = false;
    const file = event.dataTransfer?.files?.[0];
    if (file) applyMediaFile(file);
}

function applyMediaFile(file) {
    form.media = file;
    if (mediaPreviewUrl.value) URL.revokeObjectURL(mediaPreviewUrl.value);
    mediaPreviewUrl.value  = URL.createObjectURL(file);
    mediaPreviewType.value = file.type.startsWith('video/') ? 'video' : 'image';
}

function removeMedia() {
    form.media = null;
    if (mediaPreviewUrl.value) URL.revokeObjectURL(mediaPreviewUrl.value);
    mediaPreviewUrl.value  = null;
    mediaPreviewType.value = null;
}

function formatCurrency(amount) {
    return new Intl.NumberFormat('fr-FR', {
        style: 'decimal',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    }).format(amount) + ' FCFA';
}

const isBalanceSufficient = computed(() => {
    if (form.is_system_campaign) return true;
    const budget = parseFloat(form.total_budget) || 0;
    if (budget <= 0) return true;
    return props.available_balance >= budget;
});

const balanceAfterDebit = computed(() => {
    const budget = parseFloat(form.total_budget) || 0;
    return props.available_balance - budget;
});

function submit() {
    form.post(route('vendor.campaigns.store'), {
        preserveScroll: true,
        forceFormData: true,
    });
}
</script>

<template>
    <Head title="Creer une campagne" />
    <VendorLayout>
        <div class="space-y-6">
            <!-- Bandeau legal (disclaimer) -->
            <div class="flex items-start gap-4 rounded-2xl border border-amber-300 bg-amber-50 px-5 py-4">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-amber-100">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-amber-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                    </svg>
                </div>
                <div>
                    <h4 class="text-sm font-semibold text-amber-800">Conditions de creation de campagne</h4>
                    <ul class="mt-1 space-y-1 text-sm text-amber-700 list-disc list-inside">
                        <li>Toute campagne creee est <strong>definitive</strong> et ne peut etre supprimee.</li>
                        <li>Le budget est <strong>debite immediatement</strong> de votre solde.</li>
                        <li>La mise en pause est limitee a <strong>1 heure maximum</strong> pour eviter la fraude au trafic.</li>
                        <li>Les contenus contraires a notre charte seront automatiquement rejetes.</li>
                    </ul>
                </div>
            </div>
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-xl font-bold text-slate-900">Nouvelle campagne</h1>
                    <p class="mt-1 text-sm text-slate-500">Configurez votre campagne 100% Performance (CPC + CPA).</p>
                </div>
                <Link
                    :href="route('vendor.dashboard')"
                    class="inline-flex items-center gap-1.5 rounded-2xl border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-600 shadow-sm transition hover:bg-slate-50"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
                    </svg>
                    Retour
                </Link>
            </div>
            <!-- Bandeau KYC -->
            <div
                v-if="!isKycApproved"
                class="flex items-start gap-4 rounded-2xl border border-amber-200 bg-amber-50 px-5 py-4"
            >
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-amber-100">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-amber-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m0-10.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.75c0 5.592 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.75h-.152c-3.196 0-6.1-1.249-8.25-3.286zm0 13.036h.008v.008H12v-.008z" />
                    </svg>
                </div>
                <div>
                    <h4 class="text-sm font-semibold text-amber-800">Verification d'identite requise</h4>
                    <p class="mt-1 text-sm text-amber-700">Votre compte doit etre verifie avant de lancer une campagne.</p>
                    <Link
                        :href="route('vendor.kyc.index')"
                        class="mt-3 inline-flex items-center gap-1.5 rounded-2xl bg-amber-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-amber-700"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 9h3.75M15 12h3.75M15 15h3.75M4.5 19.5h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5zm6-10.125a1.875 1.875 0 11-3.75 0 1.875 1.875 0 013.75 0zm1.294 6.336a6.721 6.721 0 01-3.17.789 6.721 6.721 0 01-3.168-.789 3.376 3.376 0 016.338 0z" />
                        </svg>
                        Completer mon KYC
                    </Link>
                </div>
            </div>
            <!-- Formulaire -->
            <form v-if="isKycApproved" id="campaign-form" @submit.prevent="submit" class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">
                <!-- COLONNE GAUCHE — FORMULAIRE (2/3) -->
                <div class="lg:col-span-2 space-y-6">

                    <!-- ═══════ CARD 1 — Informations generales ═══════ -->
                    <div class="rounded-2xl border border-slate-200 bg-white shadow-md overflow-hidden">
                        <div class="bg-gradient-to-r from-purple-50 to-slate-50 px-6 py-4 border-b border-slate-200">
                            <h3 class="flex items-center gap-2 text-lg font-bold text-slate-900">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-purple-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" />
                                </svg>
                                Informations generales
                            </h3>
                            <p class="mt-1 text-sm text-slate-500">Definissez le contenu et l'identite de votre campagne.</p>
                        </div>
                        <div class="p-6 space-y-6">
                            <!-- Titre -->
                            <div>
                                <label for="title" class="block text-sm font-semibold text-slate-800 mb-1.5">Titre de la campagne</label>
                                <p class="text-xs text-slate-500 mb-2">Le titre permettra aux createurs de contenu d'identifier rapidement votre campagne.</p>
                                <input
                                    id="title"
                                    v-model="form.title"
                                    type="text"
                                    maxlength="255"
                                    placeholder="Ex : Lancement produit beaute printemps 2026"
                                    class="block w-full rounded-2xl border-slate-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 sm:text-sm"
                                />
                                <p v-if="form.errors.title" class="mt-1.5 text-sm text-red-600">{{ form.errors.title }}</p>
                            </div>

                            <!-- Type de destination -->
                            <div>
                                <label class="block text-sm font-semibold text-slate-800 mb-1.5">Destination du trafic</label>
                                <p class="text-xs text-slate-500 mb-3">Ou souhaitez-vous rediriger les visiteurs apportes par les createurs de contenu ?</p>
                                <div class="flex flex-wrap gap-2 mb-3">
                                    <button type="button"
                                        class="inline-flex items-center gap-1.5 rounded-2xl border-2 px-4 py-2.5 text-sm font-medium transition"
                                        :class="form.destination_type === 'shop' ? 'border-purple-500 bg-purple-50 text-purple-700' : 'border-slate-200 bg-white text-slate-600 hover:border-slate-300'"
                                        @click="selectDestination('shop')"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 21v-7.5a.75.75 0 01.75-.75h3a.75.75 0 01.75.75V21m-4.5 0H2.36m11.14 0H18m0 0h3.64m-1.39 0V9.349m-16.5 11.65V9.35m0 0a3.001 3.001 0 003.75-.615A2.993 2.993 0 009.75 9.75c.896 0 1.7-.393 2.25-1.016a2.993 2.993 0 002.25 1.016c.896 0 1.7-.393 2.25-1.016a3.001 3.001 0 003.75.614m-16.5 0a3.004 3.004 0 01-.621-4.72L4.318 3.44A1.5 1.5 0 015.378 3h13.243a1.5 1.5 0 011.06.44l1.19 1.189a3 3 0 01-.621 4.72m-13.5 8.65h3.75a.75.75 0 00.75-.75V13.5a.75.75 0 00-.75-.75H6.75a.75.75 0 00-.75.75v3.15c0 .415.336.75.75.75z" />
                                        </svg>
                                        Ma boutique MANTOTA
                                    </button>
                                    <button type="button"
                                        class="inline-flex items-center gap-1.5 rounded-2xl border-2 px-4 py-2.5 text-sm font-medium transition"
                                        :class="form.destination_type === 'product' ? 'border-purple-500 bg-purple-50 text-purple-700' : 'border-slate-200 bg-white text-slate-600 hover:border-slate-300'"
                                        @click="selectDestination('product')"
                                        :disabled="products.length === 0"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 10-7.5 0v4.5m11.356-1.993l1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 01-1.12-1.243l1.264-12A1.125 1.125 0 015.513 7.5h12.974c.576 0 1.059.435 1.119 1.007zM8.625 10.5a.375.375 0 11-.75 0 .375.375 0 01.75 0zm7.5 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" />
                                        </svg>
                                        Produit specifique
                                    </button>
                                </div>
                                <!-- Select Produit -->
                                <div v-if="form.destination_type === 'product'">
                                    <select
                                        v-model="form.product_id"
                                        class="block w-full rounded-2xl border-slate-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 sm:text-sm"
                                    >
                                        <option value="" disabled>Selectionnez un produit...</option>
                                        <option v-for="p in products" :key="p.id" :value="p.id">
                                            {{ p.name }} — {{ formatCurrency(p.price) }} ({{ p.commission_percent || 0 }}% commission)
                                        </option>
                                    </select>
                                    <div v-if="selectedProduct" class="mt-2 flex items-center gap-2 rounded-2xl bg-purple-50 border border-purple-200 px-3 py-2 text-xs text-purple-700">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75M15 10.5a3 3 0 11-6 0 3 3 0 016 0zm3 0h.008v.008H18V10.5zm-12 0h.008v.008H6V10.5z" />
                                        </svg>
                                        Commission createur de contenu : <strong>{{ selectedProduct.commission_percent || 0 }}%</strong> par vente generee (CPA)
                                    </div>
                                    <p v-if="form.errors.product_id" class="mt-1.5 text-sm text-red-600">{{ form.errors.product_id }}</p>
                                </div>
                                <!-- Boutique : info -->
                                <div v-if="form.destination_type === 'shop'" class="flex items-center gap-2 rounded-2xl bg-purple-50 border border-purple-200 px-3 py-2 text-xs text-purple-700">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 21v-7.5a.75.75 0 01.75-.75h3a.75.75 0 01.75.75V21m-4.5 0H2.36m11.14 0H18m0 0h3.64m-1.39 0V9.349m-16.5 11.65V9.35m0 0a3.001 3.001 0 003.75-.615A2.993 2.993 0 009.75 9.75c.896 0 1.7-.393 2.25-1.016a2.993 2.993 0 002.25 1.016c.896 0 1.7-.393 2.25-1.016a3.001 3.001 0 003.75.614m-16.5 0a3.004 3.004 0 01-.621-4.72L4.318 3.44A1.5 1.5 0 015.378 3h13.243a1.5 1.5 0 011.06.44l1.19 1.189a3 3 0 01-.621 4.72m-13.5 8.65h3.75a.75.75 0 00.75-.75V13.5a.75.75 0 00-.75-.75H6.75a.75.75 0 00-.75.75v3.15c0 .415.336.75.75.75z" />
                                    </svg>
                                    Les createurs de contenu seront rediriges vers votre boutique MANTOTA. Le suivi des ventes (CPA) est actif.
                                </div>
                            </div>

                            <hr class="border-slate-200" />

                            <!-- Niche -->
                            <div>
                                <label for="niche" class="block text-sm font-semibold text-slate-800 mb-1.5">Niche / Categorie</label>
                                <p class="text-xs text-slate-500 mb-2">Ciblez les createurs de contenu specialises dans cette categorie pour un meilleur taux de conversion.</p>
                                <select
                                    id="niche"
                                    v-model="form.niche"
                                    class="block w-full rounded-2xl border-slate-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 sm:text-sm"
                                >
                                    <option value="" disabled>Selectionnez une niche...</option>
                                    <option v-for="n in available_niches" :key="n.value" :value="n.value">{{ n.label }}</option>
                                </select>
                                <p v-if="form.errors.niche" class="mt-1.5 text-sm text-red-600">{{ form.errors.niche }}</p>
                            </div>

                            <hr class="border-slate-200" />

                            <!-- Consignes pour les createurs de contenu -->
                            <div>
                                <label for="instructions" class="block text-sm font-semibold text-slate-800 mb-1.5">Consignes pour les createurs de contenu</label>
                                <p class="text-xs text-slate-500 mb-2">Instructions que les createurs de contenu verront avant et apres avoir genere leur lien (ex : ton a adopter, hashtags, mentions obligatoires...).</p>
                                <textarea
                                    id="instructions"
                                    v-model="form.instructions"
                                    rows="4"
                                    maxlength="2000"
                                    placeholder="Ex : Mentionnez @mantota dans votre publication. Utilisez le hashtag #MantotaDeal. Ton decontracte et authentique."
                                    class="block w-full rounded-2xl border-slate-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 sm:text-sm"
                                ></textarea>
                                <p v-if="form.errors.instructions" class="mt-1.5 text-sm text-red-600">{{ form.errors.instructions }}</p>
                            </div>

                            <hr class="border-slate-200" />

                            <!-- Media -->
                            <div>
                                <label class="block text-sm font-semibold text-slate-800 mb-1.5">Media de la campagne</label>
                                <p class="text-xs text-slate-500 mb-3">Image ou video que les createurs de contenu utiliseront pour promouvoir votre campagne.</p>
                                <div v-if="!mediaPreviewUrl" class="rounded-2xl border-2 border-dashed border-slate-300 p-8 text-center transition" :class="isDragOver ? 'border-purple-500 bg-purple-50' : ''" @dragover.prevent="isDragOver = true" @dragleave.prevent="isDragOver = false" @drop.prevent="handleDrop">
                                    <label class="cursor-pointer">
                                        <div class="flex flex-col items-center gap-2">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 16.5V9.75m0 0l3 3m-3-3l-3 3M6.75 19.5a4.5 4.5 0 01-1.41-8.775 5.25 5.25 0 0110.233-2.33A3 3 0 0116.5 19.5H6.75z" />
                                            </svg>
                                            <p class="text-sm font-medium text-slate-700">Deposez votre fichier ou cliquez ici</p>
                                            <p class="text-xs text-slate-400">PNG, JPG, MP4 (max 50MB)</p>
                                        </div>
                                        <input
                                            type="file"
                                            accept="image/*,video/*"
                                            @change="handleFileSelected"
                                            class="hidden"
                                        />
                                    </label>
                                </div>
                                <div v-else class="space-y-3">
                                    <div v-if="mediaPreviewType === 'image'" class="rounded-2xl overflow-hidden bg-slate-100">
                                        <img :src="mediaPreviewUrl" class="max-h-64 w-full object-cover" />
                                    </div>
                                    <video v-else :src="mediaPreviewUrl" class="max-h-64 w-full rounded-2xl object-cover" controls></video>
                                    <button type="button" @click="removeMedia" class="inline-flex items-center gap-1.5 rounded-2xl bg-red-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-red-700">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                                        </svg>
                                        Supprimer
                                    </button>
                                </div>
                                <p v-if="form.errors.media" class="mt-1.5 text-sm text-red-600">{{ form.errors.media }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- ═══════ CARD 2 — Ciblage & Budget ═══════ -->
                    <div class="rounded-2xl border border-slate-200 bg-white shadow-md overflow-hidden">
                        <div class="bg-gradient-to-r from-purple-50 to-slate-50 px-6 py-4 border-b border-slate-200">
                            <h3 class="flex items-center gap-2 text-lg font-bold text-slate-900">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-purple-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                Ciblage & Budget
                            </h3>
                            <p class="mt-1 text-sm text-slate-500">Definissez votre audience cible et votre investissement.</p>
                        </div>
                        <div class="p-6 space-y-6">
                            <!-- Pays cibles -->
                            <div>
                                <label class="block text-sm font-semibold text-slate-800 mb-1.5">Pays cibles</label>
                                <p class="text-xs text-slate-500 mb-3">Dans quels pays souhaitez-vous cibler les createurs de contenu ?</p>
                                <div class="mb-3 max-h-40 overflow-y-auto rounded-lg border border-slate-200">
                                    <label v-for="country in targetCountries" :key="country.code" class="flex items-center gap-2 cursor-pointer border-b border-slate-100 px-4 py-2.5 hover:bg-slate-50 transition last:border-b-0">
                                        <input
                                            type="checkbox"
                                            :checked="form.target_country.includes(country.code)"
                                            @change="toggleCountry(country.code)"
                                            class="h-4 w-4 rounded border-slate-300 text-purple-600 focus:ring-purple-500"
                                        />
                                        <span class="text-sm text-slate-700">{{ country.name }}</span>
                                    </label>
                                </div>
                                <div v-if="form.target_country.length > 0" class="flex flex-wrap gap-1.5">
                                    <span v-for="countryCode in form.target_country" :key="countryCode" class="inline-flex items-center gap-1 rounded-full bg-purple-100 px-2 py-1 text-xs font-medium text-purple-700">
                                        {{ locations[countryCode]?.name || countryCode }}
                                        <button type="button" @click="toggleCountry(countryCode)" class="hover:text-purple-900">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                            </svg>
                                        </button>
                                    </span>
                                </div>
                                <p v-if="form.errors.target_country" class="mt-1.5 text-sm text-red-600">{{ form.errors.target_country }}</p>
                            </div>

                            <hr class="border-slate-200" />

                            <!-- Plateformes -->
                            <div>
                                <label class="block text-sm font-semibold text-slate-800 mb-1.5">Plateformes cibles</label>
                                <p class="text-xs text-slate-500 mb-3">Les reseaux sociaux sur lesquels vos createurs de contenu partageront votre campagne.</p>
                                <div class="space-y-2 mb-3">
                                    <label v-for="p in platformOptions" :key="p.key" class="flex items-center gap-2 cursor-pointer rounded-lg border-2 px-3 py-2.5 transition" :class="form.platforms.includes(p.key) ? 'border-purple-500 bg-purple-50' : 'border-slate-200 hover:border-slate-300'">
                                        <input
                                            type="checkbox"
                                            :checked="form.platforms.includes(p.key)"
                                            @change="togglePlatform(p.key)"
                                            class="h-4 w-4 rounded border-slate-300 text-purple-600 focus:ring-purple-500"
                                        />
                                        <span class="text-sm font-medium" :class="form.platforms.includes(p.key) ? 'text-purple-700' : 'text-slate-700'">{{ p.label }}</span>
                                    </label>
                                </div>
                                <div v-if="form.platforms.length > 0" class="flex flex-wrap gap-1.5">
                                    <span v-for="p in form.platforms" :key="p" class="inline-flex items-center gap-1 rounded-full bg-purple-100 px-2 py-1 text-xs font-medium text-purple-700">
                                        {{ platformOptions.find(x => x.key === p)?.label || p }}
                                        <button type="button" @click="togglePlatform(p)" class="hover:text-purple-900">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                            </svg>
                                        </button>
                                    </span>
                                </div>
                                <p v-if="form.errors.platforms" class="mt-1.5 text-sm text-red-600">{{ form.errors.platforms }}</p>
                            </div>

                            <hr class="border-slate-200" />

                            <!-- CPC -->
                            <div>
                                <label for="click_price" class="block text-sm font-semibold text-slate-800 mb-1.5">Prix par Clic (CPC)</label>
                                <p class="text-xs text-slate-500 mb-2">Combien payerez-vous a chaque visiteur unique apporte par le createur de contenu. Minimum : {{ min_cpc }} FCFA.</p>
                                <input
                                    id="click_price"
                                    v-model="form.click_price"
                                    type="number"
                                    :min="min_cpc"
                                    step="1"
                                    :placeholder="String(min_cpc)"
                                    class="block w-full rounded-2xl border-slate-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 sm:text-sm"
                                />
                                <p v-if="form.errors.click_price" class="mt-1.5 text-sm text-red-600">{{ form.errors.click_price }}</p>
                            </div>

                            <!-- Bandeau 100% Performance -->
                            <div class="rounded-2xl border border-purple-200 bg-gradient-to-r from-purple-50 to-purple-50 p-4">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-purple-500">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-white" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z" />
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="text-sm font-semibold text-purple-800">Modele 100% Performance</p>
                                        <p class="text-xs text-slate-600">Vous ne payez que les resultats reels : clics (CPC) et ventes (CPA) generes par les createurs de contenu.</p>
                                    </div>
                                </div>
                            </div>

                            <hr class="border-slate-200" />

                            <!-- Budget -->
                            <div>
                                <label for="total_budget" class="block text-sm font-semibold text-slate-800 mb-1.5">Budget total</label>
                                <p class="text-xs text-slate-500 mb-2">Le montant sera debite immediatement de votre solde wallet.</p>
                                <input
                                    id="total_budget"
                                    v-model="form.total_budget"
                                    type="number"
                                    min="1000"
                                    step="500"
                                    placeholder="10000"
                                    class="block w-full rounded-2xl border-slate-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 sm:text-sm"
                                />
                                <p v-if="form.errors.total_budget" class="mt-1.5 text-sm text-red-600">{{ form.errors.total_budget }}</p>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- COLONNE DROITE — SIMULATEUR (1/3) -->
                <div class="lg:col-span-1 space-y-6">
                    <!-- Simulateur Budget & Tier -->
                    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-md sticky top-6">
                        <h3 class="text-base font-semibold text-slate-900 mb-4">Simulateur</h3>
                        <!-- Tier actuel -->
                        <div v-if="currentTierData" class="mb-6 rounded-2xl p-4" :class="'bg-gradient-to-r ' + currentTierData.gradient">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-xs font-semibold text-slate-700">TIER ACTUEL</span>
                                <span class="inline-block rounded-full px-2.5 py-1 text-xs font-bold" :class="currentTierData.badge">{{ currentTierData.label }}</span>
                            </div>
                            <p class="text-sm font-medium text-slate-900 mb-1">{{ currentTierData.type }}</p>
                            <p class="text-xs text-slate-600">{{ currentTierData.desc }}</p>
                            <p class="text-xs text-slate-500 mt-2">Suiveurs: {{ currentTierData.range }}</p>
                        </div>
                        <!-- Budget Progress -->
                        <div class="mb-6">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-xs font-semibold text-slate-700">PROGRESSION</span>
                                <span class="text-xs font-semibold text-slate-600">{{ Math.round(tierProgress) }}%</span>
                            </div>
                            <div class="h-2 w-full rounded-full bg-slate-200 overflow-hidden">
                                <div class="h-full rounded-full transition-all" :class="currentTierData?.bar || 'bg-amber-500'" :style="{ width: tierProgress + '%' }"></div>
                            </div>
                            <div class="mt-2 flex justify-between text-xs text-slate-500">
                                <span>0 F</span>
                                <span v-if="currentTier !== 'or'">{{ formatCurrency(currentTier === 'argent' ? TIER_OR : TIER_ARGENT) }}</span>
                            </div>
                        </div>
                        <!-- Estimations -->
                        <div class="space-y-3 border-t pt-4">
                            <div class="flex justify-between">
                                <span class="text-sm text-slate-600">Budget:</span>
                                <span class="font-semibold text-slate-900">{{ formatCurrency(parseFloat(form.total_budget) || 0) }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-sm text-slate-600">CPC:</span>
                                <span class="font-semibold text-slate-900">{{ formatCurrency(parseFloat(form.click_price) || 0) }}</span>
                            </div>
                            <div class="border-t pt-3 flex justify-between">
                                <span class="font-medium text-slate-900">Clics estimes:</span>
                                <span class="font-bold text-purple-600">{{ estimatedClicks }}</span>
                            </div>
                        </div>
                        <!-- Solde -->
                        <div class="mt-6 rounded-2xl bg-slate-50 p-4">
                            <p class="text-xs text-slate-600 mb-1">Solde apres debit:</p>
                            <p class="text-lg font-bold" :class="isBalanceSufficient ? 'text-purple-600' : 'text-red-600'">
                                {{ formatCurrency(balanceAfterDebit) }}
                            </p>
                            <p v-if="!isBalanceSufficient" class="mt-2 text-xs text-red-600 font-medium">Solde insuffisant</p>
                        </div>
                        <!-- Open Sea Toggle -->
                        <div class="mt-6 border-t pt-4">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm font-semibold text-slate-900">Ouvrir a tous les paliers</p>
                                    <p class="text-xs text-slate-500 mt-0.5">Accessible aux createurs de contenu de tous niveaux</p>
                                </div>
                                <button
                                    type="button"
                                    @click="form.open_sea = !form.open_sea"
                                    :class="form.open_sea ? 'bg-purple-600' : 'bg-slate-300'"
                                    class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors focus:outline-none focus:ring-2 focus:ring-purple-500 focus:ring-offset-2"
                                >
                                    <span
                                        :class="form.open_sea ? 'translate-x-6' : 'translate-x-1'"
                                        class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform"
                                    ></span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
            <!-- Bouton de soumission -->
            <div v-if="isKycApproved" class="flex items-center justify-between gap-4 mt-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-md">
                <Link
                    :href="route('vendor.dashboard')"
                    class="inline-flex items-center gap-1.5 rounded-2xl border border-slate-300 bg-white px-6 py-3 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
                    </svg>
                    Annuler
                </Link>
                <button
                    type="submit"
                    form="campaign-form"
                    :disabled="!isBalanceSufficient || form.processing"
                    class="inline-flex items-center gap-2 rounded-2xl bg-gradient-to-r from-purple-600 to-purple-700 px-8 py-3.5 text-base font-bold text-white shadow-lg transition hover:from-purple-700 hover:to-purple-800 disabled:opacity-50 disabled:cursor-not-allowed"
                >
                    <svg v-if="form.processing" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 animate-spin" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12a7.5 7.5 0 0015 0m0 0a7.5 7.5 0 01-15 0m15 0a7.5 7.5 0 00-15 0m0 0a7.5 7.5 0 0115 0" />
                    </svg>
                    <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.59 14.37a6 6 0 01-5.84 7.38v-4.8m5.84-2.58a14.98 14.98 0 006.16-12.12A14.98 14.98 0 009.631 8.41m5.96 5.96a14.926 14.926 0 01-5.841 2.58m-.119-8.54a6 6 0 00-7.381 5.84h4.8m2.581-5.84a14.927 14.927 0 00-2.58 5.84m2.699 2.7c-.103.021-.207.041-.311.06a15.09 15.09 0 01-2.448-2.448 14.9 14.9 0 01.06-.312m-2.24 2.39a4.493 4.493 0 00-1.757 4.306 4.493 4.493 0 004.306-1.758M16.5 9a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0z" />
                    </svg>
                    <span v-if="!form.processing">Lancer la campagne</span>
                    <span v-else>Creation en cours...</span>
                </button>
            </div>
        </div>
    </VendorLayout>
</template>
