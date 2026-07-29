<script setup>
import VendorLayout from '../../Layouts/VendorLayout.vue';
import { Head, useForm, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage();
const flashError = computed(() => page.props.flash?.error);

const props = defineProps({
    service:  { type: Object, default: null },
    products: { type: Array, default: () => [] },
    ugc_studio_fee_percent: { type: Number, default: 15 },
});

const form = useForm({
    service_id: props.service?.id ?? '',
    product_id: '',
    brief: '',
});

function formatCurrency(amount) {
    const v = parseFloat(amount);
    if (!v || v < 0) return '0 FCFA';
    return new Intl.NumberFormat('fr-FR', { style: 'decimal', minimumFractionDigits: 0, maximumFractionDigits: 0 }).format(v) + ' FCFA';
}

const typeLabels = { ugc_humain: 'UGC Humain', video_pub_ia: 'Video Pub IA' };
const durationLabels = { '15s': '15 secondes', '30s': '30 secondes', '60s': '60 secondes', 'long': 'Long format' };

const commissionRate = computed(() => props.ugc_studio_fee_percent / 100);

const servicePrice = computed(() => parseFloat(props.service?.price ?? 0));
const mantotaFees  = computed(() => servicePrice.value * commissionRate.value);
const totalPrice   = computed(() => servicePrice.value + mantotaFees.value);

function submit() {
    form.post(route('vendor.service-orders.store'), {
        preserveScroll: true,
    });
}
</script>

<template>
    <Head title="Commander un service" />

    <VendorLayout>
        <div class="mx-auto max-w-3xl px-4 py-6 sm:px-6 space-y-6">

            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-xl font-bold text-slate-900">Commander un service</h1>
                    <p class="mt-1 text-sm text-slate-500">Decrivez votre besoin, le montant sera bloque en escrow.</p>
                </div>
                <Link
                    :href="route('vendor.service-orders.index')"
                    class="inline-flex items-center gap-1.5 rounded-full border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-600 shadow-sm transition hover:bg-slate-50"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
                    </svg>
                    Retour
                </Link>
            </div>

            <div v-if="flashError" class="rounded-xl bg-red-50 border border-red-200 p-4 mb-4 text-sm text-red-700 flex items-start gap-3">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mt-0.5 shrink-0 text-red-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" /></svg>
                <span>{{ flashError }}</span>
            </div>

            <form @submit.prevent="submit" class="space-y-6">

                <!-- Service selectionne -->
                <div v-if="service" class="rounded-2xl border border-slate-200/80 bg-white p-6 shadow-sm">
                    <h3 class="flex items-center gap-2 text-base font-semibold text-slate-900 mb-4">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-purple-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" d="M15.75 10.5l4.72-4.72a.75.75 0 011.28.53v11.38a.75.75 0 01-1.28.53l-4.72-4.72M4.5 18.75h9a2.25 2.25 0 002.25-2.25v-9a2.25 2.25 0 00-2.25-2.25h-9A2.25 2.25 0 002.25 7.5v9a2.25 2.25 0 002.25 2.25z" />
                        </svg>
                        Service selectionne
                    </h3>

                    <div class="rounded-lg border border-purple-200 bg-purple-50/50 p-4">
                        <!-- Couverture du service -->
                        <div v-if="service.image_path" class="mb-4 rounded-lg overflow-hidden">
                            <img :src="`/storage/${service.image_path}`" :alt="service.title" class="w-full h-40 object-cover" />
                        </div>

                        <div class="flex items-start justify-between">
                            <div class="flex items-start gap-3">
                                <!-- Avatar influenceur -->
                                <div v-if="service.influencer" class="shrink-0">
                                    <img v-if="service.influencer.profile_photo" :src="`/storage/${service.influencer.profile_photo}`" :alt="service.influencer.name" class="h-10 w-10 rounded-full object-cover ring-2 ring-white" />
                                    <div v-else class="flex h-10 w-10 items-center justify-center rounded-full bg-purple-100 text-sm font-bold text-purple-600 ring-2 ring-white">
                                        {{ (service.influencer.name ?? 'I')[0].toUpperCase() }}
                                    </div>
                                </div>
                                <div>
                                    <h4 class="text-sm font-semibold text-slate-900">{{ service.title }}</h4>
                                    <p class="mt-1 text-xs text-slate-500">
                                        {{ typeLabels[service.type] || service.type }}
                                        <span v-if="service.influencer" class="ml-2">par <Link :href="route('vendor.influencer.show', service.influencer.id)" class="font-medium text-purple-600 hover:text-purple-800 underline">{{ service.influencer.name }}</Link></span>
                                    </p>
                                    <!-- Pays + Abonnes -->
                                    <div v-if="service.influencer" class="mt-1.5 flex flex-wrap items-center gap-3 text-xs text-slate-500">
                                        <span v-if="service.influencer.country" class="flex items-center gap-1">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z" /></svg>
                                            {{ service.influencer.country }}
                                        </span>
                                        <span class="flex items-center gap-1">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" /></svg>
                                            {{ ((service.influencer.tiktok_followers ?? 0) + (service.influencer.instagram_followers ?? 0) + (service.influencer.facebook_followers ?? 0) + (service.influencer.youtube_followers ?? 0) + (service.influencer.snapchat_followers ?? 0)).toLocaleString('fr-FR') }} abonnes
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Couverture du service -->
                        <div class="mt-4 grid grid-cols-2 gap-3">
                            <div class="rounded-lg bg-white border border-slate-200 p-3">
                                <p class="text-[10px] font-semibold uppercase tracking-wider text-slate-400">Duree</p>
                                <p class="mt-0.5 text-sm font-bold text-slate-900">{{ durationLabels[service.duration] || service.duration }}</p>
                            </div>
                            <div class="rounded-lg bg-white border border-slate-200 p-3">
                                <p class="text-[10px] font-semibold uppercase tracking-wider text-slate-400">Retouches incluses</p>
                                <p class="mt-0.5 text-sm font-bold text-slate-900">{{ service.included_revisions ?? 0 }}</p>
                            </div>
                            <div class="rounded-lg bg-white border border-slate-200 p-3">
                                <p class="text-[10px] font-semibold uppercase tracking-wider text-slate-400">Format video</p>
                                <p class="mt-0.5 text-sm font-bold text-slate-900">{{ typeLabels[service.type] || service.type }}</p>
                            </div>
                            <div class="rounded-lg bg-white border border-slate-200 p-3">
                                <p class="text-[10px] font-semibold uppercase tracking-wider text-slate-400">Delai estimatif</p>
                                <p class="mt-0.5 text-sm font-bold text-slate-900">{{ service.duration === 'long' ? '5-7 jours' : '2-4 jours' }}</p>
                            </div>
                        </div>

                        <!-- Decomposition du prix -->
                        <div class="mt-4 rounded-lg bg-white border border-slate-200 p-4 space-y-2">
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-slate-600">Prix du service</span>
                                <span class="font-medium text-slate-900">{{ formatCurrency(servicePrice) }}</span>
                            </div>
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-slate-600">Frais MANTOTA ({{ ugc_studio_fee_percent }}%)</span>
                                <span class="font-medium text-slate-900">{{ formatCurrency(mantotaFees) }}</span>
                            </div>
                            <div class="border-t border-slate-100 pt-2 flex items-center justify-between text-sm">
                                <span class="font-semibold text-slate-900">Total a payer</span>
                                <span class="font-bold text-purple-700 text-base">{{ formatCurrency(totalPrice) }}</span>
                            </div>
                        </div>
                    </div>
                    <p v-if="form.errors.service_id" class="mt-2 text-sm text-red-600">{{ form.errors.service_id }}</p>
                </div>

                <!-- Produit associe (optionnel) -->
                <div class="rounded-2xl border border-slate-200/80 bg-white p-6 shadow-sm">
                    <h3 class="flex items-center gap-2 text-base font-semibold text-slate-900 mb-4">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-purple-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 10-7.5 0v4.5m11.356-1.993l1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 01-1.12-1.243l1.264-12A1.125 1.125 0 015.513 7.5h12.974c.576 0 1.059.435 1.119 1.007zM8.625 10.5a.375.375 0 11-.75 0 .375.375 0 01.75 0zm7.5 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" />
                        </svg>
                        Produit a mettre en avant (optionnel)
                    </h3>

                    <select v-model="form.product_id"
                        class="block w-full rounded-lg border-slate-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 sm:text-sm">
                        <option value="">Aucun produit</option>
                        <option v-for="p in products" :key="p.id" :value="p.id">{{ p.name }} ({{ p.type }})</option>
                    </select>
                    <p class="mt-1 text-xs text-slate-400">Si vous souhaitez que la video mette en avant un de vos produits.</p>
                </div>

                <!-- Brief -->
                <div class="rounded-2xl border border-slate-200/80 bg-white p-6 shadow-sm">
                    <h3 class="flex items-center gap-2 text-base font-semibold text-slate-900 mb-4">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-purple-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                        </svg>
                        Votre brief
                    </h3>

                    <textarea
                        v-model="form.brief"
                        rows="6"
                        maxlength="5000"
                        placeholder="Decrivez en detail ce que vous attendez : ton de la video, messages cles, produit a montrer, audience ciblee, contraintes de format..."
                        class="block w-full rounded-lg border-slate-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 sm:text-sm"
                    />
                    <p v-if="form.errors.brief" class="mt-1.5 text-sm text-red-600">{{ form.errors.brief }}</p>

                    <!-- Info escrow -->
                    <div class="mt-4 rounded-lg border border-purple-200 bg-purple-50/50 p-4">
                        <div class="flex items-start gap-3">
                            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-purple-100">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-purple-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
                                </svg>
                            </div>
                            <div class="text-sm">
                                <p class="font-medium text-purple-800">Paiement securise (Escrow)</p>
                                <p class="mt-1 text-purple-700/80">
                                    Le montant sera bloque dans un escrow securise. Le createur de contenu ne sera paye
                                    que lorsque vous aurez approuve la video livree. En cas de rejet, le montant
                                    est rembourse integralement dans votre solde.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex items-center justify-end gap-3">
                    <Link
                        :href="route('vendor.service-orders.index')"
                        class="rounded-lg border border-slate-300 bg-white px-5 py-2.5 text-sm font-medium text-slate-700 shadow-sm transition hover:bg-slate-50"
                    >
                        Annuler
                    </Link>
                    <button
                        type="submit"
                        :disabled="form.processing || !form.service_id || !form.brief"
                        class="inline-flex items-center gap-2 rounded-full bg-gradient-to-r from-purple-500 to-violet-600 px-6 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:from-purple-600 hover:to-violet-700 disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        <svg v-if="form.processing" class="h-4 w-4 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                        </svg>
                        <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
                        </svg>
                        {{ form.processing ? 'Commande en cours...' : 'Commander et bloquer le paiement' }}
                    </button>
                </div>
            </form>
        </div>
    </VendorLayout>
</template>
