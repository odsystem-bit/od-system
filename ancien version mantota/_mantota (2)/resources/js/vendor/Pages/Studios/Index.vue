<script setup>
import VendorLayout from '../../Layouts/VendorLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    services: { type: Object, default: () => ({ data: [] }) },
    ugc_studio_fee_percent: { type: Number, default: 15 },
});

// ──────────────────────────────────────────────
//  Helpers
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

const typeConfig = {
    ugc_humain:    { label: 'UGC Humain', bg: 'bg-emerald-50',  text: 'text-emerald-700', ring: 'ring-emerald-600/10' },
    video_pub_ia:  { label: 'Video Pub IA', bg: 'bg-violet-50',  text: 'text-violet-700',  ring: 'ring-violet-600/10' },
};

function getType(t) {
    return typeConfig[t] || { label: t, bg: 'bg-slate-50', text: 'text-slate-700', ring: 'ring-slate-600/10' };
}
</script>

<template>
    <Head title="MANTOTA Studios — Catalogue" />

    <VendorLayout>
        <div class="space-y-6">

            <!-- Header -->
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-xl font-bold text-slate-900">MANTOTA Studios</h1>
                    <p class="mt-1 text-sm text-slate-500">
                        Parcourez le catalogue des services video proposes par nos createurs de contenu VIP.
                    </p>
                </div>
                <Link
                    :href="route('vendor.service-orders.index')"
                    class="inline-flex items-center gap-1.5 rounded-full border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-600 shadow-sm transition hover:bg-slate-50"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 6.75h12M8.25 12h12m-12 5.25h12M3.75 6.75h.007v.008H3.75V6.75zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zM3.75 12h.007v.008H3.75V12zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm-.375 5.25h.007v.008H3.75v-.008zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" />
                    </svg>
                    Mes commandes
                </Link>
            </div>

            <!-- ─────────────────────────────────────
                 Grille de services
            ───────────────────────────────────── -->
            <div v-if="services.data?.length" class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                <div
                    v-for="service in services.data"
                    :key="service.id"
                    class="group relative flex flex-col rounded-2xl border border-slate-200/80 bg-white shadow-sm transition-all duration-500 hover:shadow-xl hover:shadow-purple-500/5"
                >
                    <!-- Couverture du service -->
                    <div v-if="service.image_path" class="rounded-t-2xl overflow-hidden">
                        <img :src="`/storage/${service.image_path}`" :alt="service.title" class="w-full h-36 object-cover" />
                    </div>
                    <!-- En-tete avec type badge -->
                    <div class="flex items-start gap-4 p-5 pb-3">
                        <!-- Avatar influenceur -->
                        <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-purple-50">
                            <img
                                v-if="service.influencer?.profile_photo"
                                :src="`/storage/${service.influencer.profile_photo}`"
                                :alt="service.influencer?.name"
                                class="h-12 w-12 rounded-full object-cover ring-2 ring-white"
                            />
                            <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-purple-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />
                            </svg>
                        </div>
                        <div class="flex-1 min-w-0">
                            <h3 class="text-sm font-semibold text-slate-900 line-clamp-2">{{ service.title }}</h3>
                            <p class="mt-0.5 text-xs text-slate-500">
                                par <Link v-if="service.influencer" :href="route('vendor.influencer.show', service.influencer.id)" class="font-medium text-purple-600 hover:text-purple-800 underline">{{ service.influencer.name }}</Link><span v-else class="font-medium text-slate-700">Createur de Contenu</span>
                            </p>
                            <!-- Pays + Abonnes -->
                            <div v-if="service.influencer" class="mt-1 flex flex-wrap items-center gap-3 text-[11px] text-slate-400">
                                <span v-if="service.influencer.country" class="flex items-center gap-0.5">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z" /></svg>
                                    {{ service.influencer.country }}
                                </span>
                                <span class="flex items-center gap-0.5">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" /></svg>
                                    {{ ((service.influencer.tiktok_followers ?? 0) + (service.influencer.instagram_followers ?? 0) + (service.influencer.facebook_followers ?? 0) + (service.influencer.youtube_followers ?? 0) + (service.influencer.snapchat_followers ?? 0)).toLocaleString('fr-FR') }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Infos du service -->
                    <div class="flex-1 px-5 pb-3">
                        <p v-if="service.description" class="text-xs text-slate-500 line-clamp-3 mb-3">{{ service.description }}</p>

                        <div class="flex flex-wrap items-center gap-2">
                            <!-- Badge type -->
                            <span
                                class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-semibold ring-1"
                                :class="[getType(service.type).bg, getType(service.type).text, getType(service.type).ring]"
                            >
                                <svg v-if="service.type === 'ugc_humain'" xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />
                                </svg>
                                <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09zM18.259 8.715L18 9.75l-.259-1.035a3.375 3.375 0 00-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 002.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 002.455 2.456L21.75 6l-1.036.259a3.375 3.375 0 00-2.455 2.456z" />
                                </svg>
                                {{ getType(service.type).label }}
                            </span>

                            <!-- Duree -->
                            <span v-if="service.duration" class="inline-flex items-center gap-1 text-xs text-slate-500">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                {{ service.duration }}
                            </span>
                        </div>
                    </div>

                    <!-- Footer : Prix + Action -->
                    <div class="border-t border-slate-100 px-5 py-4 flex items-center justify-between">
                        <div>
                            <p class="text-xs text-slate-500">Prix</p>
                            <p class="text-base font-bold text-slate-900">{{ formatCurrency(service.price) }}</p>
                        </div>
                        <Link
                            :href="route('vendor.service-orders.create', service.id)"
                            class="inline-flex items-center gap-1.5 rounded-full bg-gradient-to-r from-purple-500 to-violet-600 px-4 py-2 text-xs font-semibold text-white shadow-sm transition hover:from-purple-600 hover:to-violet-700"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 10-7.5 0v4.5m11.356-1.993l1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 01-1.12-1.243l1.264-12A1.125 1.125 0 015.513 7.5h12.974c.576 0 1.059.435 1.119 1.007zM8.625 10.5a.375.375 0 11-.75 0 .375.375 0 01.75 0zm7.5 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" />
                            </svg>
                            Commander ce service
                        </Link>
                    </div>
                </div>
            </div>

            <!-- ─────────────────────────────────────
                 Etat vide
            ───────────────────────────────────── -->
            <div v-else class="rounded-xl border border-dashed border-slate-300 bg-white p-12 text-center">
                <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-purple-50">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-purple-400" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor">
                        <path stroke-linecap="round" d="M15.75 10.5l4.72-4.72a.75.75 0 011.28.53v11.38a.75.75 0 01-1.28.53l-4.72-4.72M4.5 18.75h9a2.25 2.25 0 002.25-2.25v-9a2.25 2.25 0 00-2.25-2.25h-9A2.25 2.25 0 002.25 7.5v9a2.25 2.25 0 002.25 2.25z" />
                    </svg>
                </div>
                <h3 class="mt-4 text-base font-semibold text-slate-900">Aucun service disponible</h3>
                <p class="mt-1.5 text-sm text-slate-500">
                    Les createurs de contenu VIP n'ont pas encore publie de services. Revenez bientot.
                </p>
            </div>

        </div>
    </VendorLayout>
</template>
