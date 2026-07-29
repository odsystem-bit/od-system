<script setup>
import { Head, useForm, Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    product:       { type: Object, required: true },
    target_country: { type: String, default: null }, // Code pays de la campagne si venant d'un SmartLink
    influencer_id: { default: null },
});

// ═══════════════════════════════════════════════════════════════════
// MISSION 1 : DICTIONNAIRE STATIQUE PAYS/VILLES (Zero BD)
// ═══════════════════════════════════════════════════════════════════
const locations = {
    'BJ': { 
        name: 'Bénin', 
        cities: ['Cotonou', 'Porto-Novo', 'Abomey-Calavi', 'Parakou', 'Bohicon', 'Autre...'] 
    },
    'CI': { 
        name: 'Côte d\'Ivoire', 
        cities: ['Abidjan', 'Bouaké', 'Daloa', 'Yamoussoukro', 'San-Pédro', 'Autre...'] 
    },
    'SN': { 
        name: 'Sénégal', 
        cities: ['Dakar', 'Thiès', 'Rufisque', 'Touba', 'Ziguinchor', 'Autre...'] 
    },
    'TG': { 
        name: 'Togo', 
        cities: ['Lomé', 'Sokodé', 'Kara', 'Kpalimé', 'Atakpamé', 'Autre...'] 
    },
    'CM': { 
        name: 'Cameroun', 
        cities: ['Douala', 'Yaoundé', 'Garoua', 'Bamenda', 'Maroua', 'Autre...'] 
    }
};

const form = useForm({
    country: props.target_country || '', // Code pays (BJ, CI, etc.)
    city: '',                              // Ville sélectionnée ou "Autre..."
    custom_city: '',                       // Champ libre si "Autre..." est choisi
    customer_name: '',
    customer_phone: '',
    customer_whatsapp: '',
    customer_email: '',
    landmark_indication: '',
});

// ═══════════════════════════════════════════════════════════════════
// COMPUTED : Pays disponibles
// ═══════════════════════════════════════════════════════════════════
const countryOptions = computed(() => {
    return Object.entries(locations).map(([code, data]) => ({
        code,
        name: data.name,
    }));
});

// ═══════════════════════════════════════════════════════════════════
// COMPUTED : Villes du pays sélectionné (CASCADE)
// ═══════════════════════════════════════════════════════════════════
const cities = computed(() => {
    if (!form.country) return [];
    return locations[form.country]?.cities || [];
});

// ═══════════════════════════════════════════════════════════════════
// COMPUTED : Afficher le champ "Autre ville" si "Autre..." est sélectionné
// ═══════════════════════════════════════════════════════════════════
const showCustomCityInput = computed(() => {
    return form.city === 'Autre...';
});

// ═══════════════════════════════════════════════════════════════════
// COMPUTED : Délai de livraison (24h pour Cotonou/Abomey-Calavi, 72h sinon)
// ═══════════════════════════════════════════════════════════════════
const deliveryDelay = computed(() => {
    if (!form.city || form.city === 'Autre...') {
        return '48-72 heures (a confirmer)';
    }
    const fastCities = ['Cotonou', 'Abomey-Calavi'];
    return fastCities.includes(form.city) ? '24 heures' : '72 heures';
});

// ═══════════════════════════════════════════════════════════════════
// COMPUTED : Type de produit
// ═══════════════════════════════════════════════════════════════════
const isDigital = computed(() => props.product.type === 'digital');

// ═══════════════════════════════════════════════════════════════════
// COMPUTED : Frais de livraison
// ═══════════════════════════════════════════════════════════════════
const deliveryFee = computed(() => {
    if (isDigital.value) return 0;
    if (props.product.delivery_type === 'fixed' && props.product.delivery_fee) {
        return parseFloat(props.product.delivery_fee);
    }
    return 0;
});

// ═══════════════════════════════════════════════════════════════════
// COMPUTED : Total à payer en ligne
// ═══════════════════════════════════════════════════════════════════
const totalOnline = computed(() => {
    return parseFloat(props.product.price) + deliveryFee.value;
});

function formatCurrency(amount) {
    const v = parseFloat(amount);
    if (!v || v < 0) return '0 FCFA';
    return new Intl.NumberFormat('fr-FR', { 
        style: 'decimal', 
        minimumFractionDigits: 0, 
        maximumFractionDigits: 0 
    }).format(v) + ' FCFA';
}

function submit() {
    form.post(route('shop.checkout.store', props.product.id), {
        preserveScroll: true,
    });
}

// Réinitialiser le pays si verrouillé (fraude geo)
if (props.target_country) {
    form.country = props.target_country;
}
</script>

<template>
    <Head :title="`Commander — ${product.name}`" />

    <div class="min-h-screen bg-gray-50">

        <!-- Header -->
        <header class="border-b border-slate-200 bg-white">
            <div class="mx-auto flex max-w-3xl items-center justify-between px-4 py-4 sm:px-6">
                <Link
                    :href="route('shop.show', product.vendor?.slug || product.vendor_id)"
                    class="inline-flex items-center gap-1.5 text-sm font-medium text-slate-500 transition hover:text-slate-700"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
                    </svg>
                    Retour a la boutique
                </Link>
                <div class="flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
                    </svg>
                    <span class="text-xs font-medium text-slate-500">Paiement securise</span>
                </div>
            </div>
        </header>

        <main class="mx-auto max-w-3xl px-4 py-8 sm:px-6">

            <div class="grid gap-6 lg:grid-cols-5">

                <!-- ═══════════════════════════════════════════
                     Formulaire de commande (3 cols)
                ═══════════════════════════════════════════ -->
                <div class="lg:col-span-3 space-y-5">

                    <h1 class="text-xl font-bold text-slate-900">Finaliser votre commande</h1>

                    <!-- Bandeau Escrow / Confiance -->
                    <div class="flex items-start gap-3 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3.5">
                        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-emerald-100">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" />
                            </svg>
                        </div>
                        <div>
                            <h4 class="text-sm font-semibold text-emerald-800">Votre paiement est securise par MANTOTA</h4>
                            <p class="mt-1 text-xs text-emerald-700 leading-relaxed">
                                Le vendeur ne sera paye que lorsque vous aurez confirme la reception de la commande.
                                Votre argent reste en sequestre jusqu'a la livraison.
                            </p>
                        </div>
                    </div>

                    <form @submit.prevent="submit" class="space-y-5">

                        <!-- Nom complet -->
                        <div>
                            <label for="customer_name" class="block text-sm font-medium text-slate-700 mb-1">
                                Nom complet
                            </label>
                            <input
                                id="customer_name"
                                v-model="form.customer_name"
                                type="text"
                                placeholder="Ex: Jean Koffi"
                                class="block w-full rounded-lg border-slate-300 shadow-sm focus:border-teal-500 focus:ring-teal-500 sm:text-sm"
                            />
                            <p v-if="form.errors.customer_name" class="mt-1 text-sm text-red-600">{{ form.errors.customer_name }}</p>
                        </div>

                        <!-- Telephone + WhatsApp -->
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <label for="customer_phone" class="block text-sm font-medium text-slate-700 mb-1">
                                    Telephone
                                </label>
                                <div class="relative">
                                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 002.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 01-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 00-1.091-.852H4.5A2.25 2.25 0 002.25 4.5v2.25z" />
                                        </svg>
                                    </div>
                                    <input
                                        id="customer_phone"
                                        v-model="form.customer_phone"
                                        type="tel"
                                        placeholder="+229 XX XX XX XX"
                                        class="block w-full rounded-lg border-slate-300 pl-10 shadow-sm focus:border-teal-500 focus:ring-teal-500 sm:text-sm"
                                    />
                                </div>
                                <p v-if="form.errors.customer_phone" class="mt-1 text-sm text-red-600">{{ form.errors.customer_phone }}</p>
                            </div>

                            <div>
                                <label for="customer_whatsapp" class="block text-sm font-medium text-slate-700 mb-1">
                                    WhatsApp
                                    <span v-if="!isDigital" class="text-xs text-slate-400 font-normal">(pour le livreur)</span>
                                </label>
                                <div class="relative">
                                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M8.625 9.75a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H8.25m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H12m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0h-.375m-13.5 3.01c0 1.6 1.123 2.994 2.707 3.227 1.087.16 2.185.283 3.293.369V21l4.184-4.183a1.14 1.14 0 01.778-.332 48.294 48.294 0 005.83-.498c1.585-.233 2.708-1.626 2.708-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0012 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018z" />
                                        </svg>
                                    </div>
                                    <input
                                        id="customer_whatsapp"
                                        v-model="form.customer_whatsapp"
                                        type="tel"
                                        placeholder="+229 XX XX XX XX"
                                        class="block w-full rounded-lg border-slate-300 pl-10 shadow-sm focus:border-teal-500 focus:ring-teal-500 sm:text-sm"
                                    />
                                </div>
                                <p v-if="form.errors.customer_whatsapp" class="mt-1 text-sm text-red-600">{{ form.errors.customer_whatsapp }}</p>
                            </div>
                        </div>

                        <!-- Email (obligatoire pour les produits digitaux) -->
                        <div v-if="isDigital">
                            <label for="customer_email" class="block text-sm font-medium text-slate-700 mb-1">
                                Adresse email
                                <span class="text-xs text-slate-400 font-normal">(pour recevoir votre produit)</span>
                            </label>
                            <div class="relative">
                                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75" />
                                    </svg>
                                </div>
                                <input
                                    id="customer_email"
                                    v-model="form.customer_email"
                                    type="email"
                                    placeholder="votre@email.com"
                                    class="block w-full rounded-lg border-slate-300 pl-10 shadow-sm focus:border-teal-500 focus:ring-teal-500 sm:text-sm"
                                />
                            </div>
                            <p v-if="form.errors.customer_email" class="mt-1 text-sm text-red-600">{{ form.errors.customer_email }}</p>
                        </div>

                        <!-- ═══ SECTION LIVRAISON (produits physiques uniquement) ═══ -->
                        <template v-if="!isDigital">

                        <!-- Pays de livraison (CASCADE) -->
                        <div>
                            <label for="country" class="block text-sm font-medium text-slate-700 mb-1 flex items-center gap-2">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9.004 9.004 0 008.716-6.747M12 21c-2.485 0-4.845-.889-6.604-2.553m0 0A9.005 9.005 0 0112 3c4.563 0 8.465 2.834 10.348 6.847M0 12a12 12 0 1024 0 12 12 0 0 0-24 0z" />
                                </svg>
                                Pays de livraison
                            </label>
                            <div class="relative">
                                <select
                                    id="country"
                                    v-model="form.country"
                                    :disabled="!!props.target_country"
                                    class="block w-full appearance-none rounded-lg border-slate-300 pr-10 shadow-sm focus:border-teal-500 focus:ring-teal-500 sm:text-sm disabled:bg-slate-100 disabled:cursor-not-allowed"
                                >
                                    <option value="" disabled>Selectionnez votre pays...</option>
                                    <option v-for="country in countryOptions" :key="country.code" :value="country.code">
                                        {{ country.name }}
                                    </option>
                                </select>
                                <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                                    </svg>
                                </div>
                            </div>
                            <p v-if="props.target_country" class="mt-1 text-xs text-slate-400">
                                Pays verrouille (campagne ciblee)
                            </p>
                            <p v-if="form.errors.country" class="mt-1 text-sm text-red-600">{{ form.errors.country }}</p>
                        </div>

                        <!-- Ville de livraison (CASCADE) -->
                        <div>
                            <label for="city" class="block text-sm font-medium text-slate-700 mb-1 flex items-center gap-2">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                                Ville de livraison
                            </label>
                            <div class="relative">
                                <select
                                    id="city"
                                    v-model="form.city"
                                    :disabled="!form.country"
                                    class="block w-full appearance-none rounded-lg border-slate-300 pr-10 shadow-sm focus:border-teal-500 focus:ring-teal-500 sm:text-sm disabled:bg-slate-100 disabled:cursor-not-allowed"
                                >
                                    <option value="" disabled>
                                        {{ form.country ? 'Selectionnez votre ville...' : 'Selectionnez d\'abord un pays' }}
                                    </option>
                                    <option v-for="c in cities" :key="c" :value="c">{{ c }}</option>
                                </select>
                                <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                                    </svg>
                                </div>
                            </div>
                            <p v-if="form.city && !showCustomCityInput" class="mt-1 text-xs text-teal-600">
                                Delai estim\u00e9 : {{ deliveryDelay }}
                            </p>
                            <p v-if="form.errors.city" class="mt-1 text-sm text-red-600">{{ form.errors.city }}</p>
                        </div>

                        <!-- FILET DE SECURITE : Champ "Autre ville" si "Autre..." est choisi -->
                        <div v-if="showCustomCityInput" class="relative">
                            <label for="custom_city" class="block text-sm font-medium text-slate-700 mb-1">
                                Precisez votre ville, quartier ou repere
                            </label>
                            <input
                                id="custom_city"
                                v-model="form.custom_city"
                                type="text"
                                placeholder="Ex: Adjame, Cocody, Plateau, etc."
                                class="block w-full rounded-lg border-slate-300 shadow-sm focus:border-teal-500 focus:ring-teal-500 sm:text-sm"
                            />
                            <p class="mt-1 text-xs text-emerald-600 font-medium">
                                Aucun client africain ne sera refuse pour manque de ville dans notre liste
                            </p>
                            <p v-if="form.errors.custom_city" class="mt-1 text-sm text-red-600">{{ form.errors.custom_city }}</p>
                        </div>

                        <!-- Quartier & Repere -->
                        <div>
                            <label for="landmark_indication" class="block text-sm font-medium text-slate-700 mb-1">
                                Quartier & Repere precis
                            </label>
                            <textarea
                                id="landmark_indication"
                                v-model="form.landmark_indication"
                                rows="3"
                                placeholder="Ex: Derriere l'ecole X, portail bleu a cote de la pharmacie du carrefour"
                                class="block w-full rounded-lg border-slate-300 shadow-sm focus:border-teal-500 focus:ring-teal-500 sm:text-sm"
                            />
                            <p class="mt-1 text-xs text-slate-400">
                                Soyez aussi precis que possible pour faciliter la livraison.
                            </p>
                            <p v-if="form.errors.landmark_indication" class="mt-1 text-sm text-red-600">{{ form.errors.landmark_indication }}</p>
                        </div>

                        </template>
                        <!-- ═══ FIN SECTION LIVRAISON ═══ -->

                        <!-- Erreur paiement -->
                        <div v-if="$page.props.flash?.error" class="rounded-xl bg-red-50 border border-red-200 p-4 mb-4 text-sm text-red-700 flex items-start gap-3">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mt-0.5 shrink-0 text-red-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" /></svg>
                            <span>{{ $page.props.flash.error }}</span>
                        </div>
                        <div v-if="form.errors.payment" class="rounded-xl bg-red-50 border border-red-200 p-3 mb-4 text-sm text-red-700">
                            {{ form.errors.payment }}
                        </div>
                        <!-- Bouton de soumission -->
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="flex w-full items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-teal-500 to-purple-600 px-6 py-3.5 text-sm font-semibold text-white shadow-sm transition hover:from-teal-600 hover:to-purple-700 disabled:opacity-50 disabled:cursor-not-allowed"
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
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
                            </svg>
                            {{ form.processing ? 'Traitement en cours...' : (isDigital ? 'Acheter ' : 'Payer ') + formatCurrency(totalOnline) }}
                        </button>

                        <!-- Erreur serveur globale -->
                        <p v-if="form.errors.product" class="text-sm text-red-600 text-center">{{ form.errors.product }}</p>
                    </form>
                </div>

                <!-- ═══════════════════════════════════════════
                     Recapitulatif produit (2 cols)
                ═══════════════════════════════════════════ -->
                <div class="lg:col-span-2">
                    <div class="sticky top-6 rounded-xl border border-slate-200 bg-white p-5 shadow-sm space-y-4">

                        <h3 class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wider text-slate-500">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 00-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 00-16.536-1.84M7.5 14.25L5.106 5.272M6 20.25a.75.75 0 11-1.5 0 .75.75 0 011.5 0zm12.75 0a.75.75 0 11-1.5 0 .75.75 0 011.5 0z" />
                            </svg>
                            Votre commande
                        </h3>

                        <!-- Image produit -->
                        <div class="overflow-hidden rounded-lg bg-slate-100">
                            <img
                                v-if="product.images?.length"
                                :src="`/storage/${product.images[0].path}`"
                                :alt="product.name"
                                class="h-40 w-full object-cover"
                            />
                            <img
                                v-else-if="product.image_path"
                                :src="`/storage/${product.image_path}`"
                                :alt="product.name"
                                class="h-40 w-full object-cover"
                            />
                            <div v-else class="flex h-40 items-center justify-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-slate-300" fill="none" viewBox="0 0 24 24" stroke-width="0.75" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909M2.25 18V6a2.25 2.25 0 012.25-2.25h15A2.25 2.25 0 0121.75 6v12A2.25 2.25 0 0119.5 20.25H4.5A2.25 2.25 0 012.25 18z" />
                                </svg>
                            </div>
                        </div>

                        <div>
                            <h4 class="text-sm font-semibold text-slate-900">{{ product.name }}</h4>
                            <p class="mt-1 text-xs text-slate-500 line-clamp-2">{{ product.description }}</p>
                        </div>

                        <div class="border-t border-slate-100 pt-3 space-y-2">
                            <div class="flex justify-between text-sm">
                                <span class="text-slate-500">Prix du produit</span>
                                <span class="font-semibold text-slate-900">{{ formatCurrency(product.price) }}</span>
                            </div>
                            <div v-if="!isDigital" class="flex justify-between text-sm">
                                <span class="text-slate-500">Frais de livraison</span>
                                <span v-if="!product.delivery_type || product.delivery_type === 'free'" class="font-medium text-emerald-600">Offerts</span>
                                <span v-else-if="product.delivery_type === 'fixed'" class="font-medium text-slate-900">+{{ formatCurrency(product.delivery_fee) }}</span>
                                <span v-else class="font-medium text-amber-600">A payer au livreur</span>
                            </div>
                            <div v-if="isDigital" class="flex justify-between text-sm">
                                <span class="text-slate-500">Livraison</span>
                                <span class="font-medium text-teal-600">Produit digital — acces immediat</span>
                            </div>
                            <div class="border-t border-slate-100 pt-2 flex justify-between">
                                <span class="text-sm font-semibold text-slate-900">Total a payer en ligne</span>
                                <span class="text-lg font-bold text-slate-900">{{ formatCurrency(totalOnline) }}</span>
                            </div>
                        </div>

                        <!-- Avertissement pay_on_delivery -->
                        <div v-if="!isDigital && product.delivery_type === 'pay_on_delivery'" class="flex items-start gap-2.5 rounded-lg border border-amber-200 bg-amber-50 p-3">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0 text-amber-500 mt-0.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                            </svg>
                            <p class="text-xs font-medium text-amber-800 leading-relaxed">
                                Prevoyez l'argent liquide pour le transporteur. Les frais de livraison seront a regler directement au livreur lors de la reception.
                            </p>
                        </div>

                        <!-- Note sequestre -->
                        <div class="flex items-start gap-2 rounded-lg border border-slate-200 bg-slate-50 p-3">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0 text-slate-400 mt-0.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" />
                            </svg>
                            <p class="text-xs text-slate-500 leading-relaxed">
                                Le paiement est bloque par MANTOTA jusqu'a confirmation de reception.
                                Aucun montant n'est verse au vendeur avant votre validation.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </main>

        <!-- Footer -->
        <footer class="border-t border-slate-200 bg-white py-6 mt-12">
            <div class="mx-auto max-w-3xl px-4 sm:px-6 text-center">
                <p class="text-xs text-slate-400">
                    Propulse par
                    <span class="font-semibold text-slate-600">MANTOTA</span>
                    — Paiement securise par sequestre
                </p>
            </div>
        </footer>
    </div>
</template>
