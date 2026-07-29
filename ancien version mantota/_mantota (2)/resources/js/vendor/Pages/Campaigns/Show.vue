<script setup>
import VendorLayout from '../../Layouts/VendorLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    campaign: Object,
    paidClicks: Number,
    totalClicks: Number,
    influencerStats: { type: Array, default: () => [] },
    available_balance: { type: Number, default: 0 },
});

const c = computed(() => props.campaign);

function formatCurrency(v) {
    return new Intl.NumberFormat('fr-FR').format(Math.round(v)) + ' FCFA';
}

const budgetConsumed = computed(() => {
    const total = parseFloat(c.value.total_budget);
    const remaining = parseFloat(c.value.remaining_budget);
    return Math.max(0, total - remaining);
});

const budgetPercent = computed(() => {
    const total = parseFloat(c.value.total_budget);
    if (!total || total <= 0) return 0;
    return Math.min(100, Math.round((budgetConsumed.value / total) * 100));
});

const conversionRate = computed(() => {
    if (!props.totalClicks || props.totalClicks === 0) return 0;
    return Math.round((props.paidClicks / props.totalClicks) * 100);
});

function statusLabel(status) {
    const map = {
        active: 'Active',
        paused: 'En pause',
        completed: 'Terminee',
        draft: 'Brouillon',
        expired: 'Expiree',
        rejected: 'Rejetee',
    };
    return map[status] ?? status;
}

function statusColor(status) {
    const map = {
        active: 'bg-purple-100 text-purple-700 border-purple-200',
        paused: 'bg-amber-100 text-amber-700 border-amber-200',
        completed: 'bg-slate-100 text-slate-600 border-slate-200',
        draft: 'bg-slate-100 text-slate-500 border-slate-200',
        expired: 'bg-red-100 text-red-700 border-red-200',
        rejected: 'bg-red-100 text-red-700 border-red-200',
    };
    return map[status] ?? 'bg-slate-100 text-slate-500 border-slate-200';
}

// ── Pause / Reprendre ──
const pauseProcessing = ref(false);

function togglePause() {
    pauseProcessing.value = true;
    router.post(route('vendor.campaigns.toggle-pause', c.value.id), {}, {
        preserveScroll: true,
        onFinish: () => { pauseProcessing.value = false; },
    });
}

// ── Modal Ajout Budget ──
const budgetModal = ref(false);
const budgetAmount = ref('');
const budgetProcessing = ref(false);

function openBudgetModal() {
    budgetAmount.value = '';
    budgetModal.value = true;
}

function closeBudgetModal() {
    budgetModal.value = false;
    budgetAmount.value = '';
}

function submitBudget() {
    if (!budgetAmount.value || parseFloat(budgetAmount.value) < 500) return;
    budgetProcessing.value = true;
    router.post(route('vendor.campaigns.add-budget', c.value.id), {
        amount: budgetAmount.value,
    }, {
        preserveScroll: true,
        onFinish: () => {
            budgetProcessing.value = false;
            closeBudgetModal();
        },
    });
}

// ── Confirmation Suppression ──
const deleteModal = ref(false);

function confirmDelete() {
    router.delete(route('vendor.campaigns.destroy', c.value.id), {
        preserveScroll: true,
        onFinish: () => { deleteModal.value = false; },
    });
}

function formatFollowers(n) {
    if (n >= 1000000) return (n / 1000000).toFixed(1).replace(/\.0$/, '') + 'M';
    if (n >= 1000) return (n / 1000).toFixed(1).replace(/\.0$/, '') + 'K';
    return n.toString();
}

const socialPlatforms = [
    { key: 'tiktok',    label: 'TikTok',    color: 'text-slate-700' },
    { key: 'instagram', label: 'Instagram',  color: 'text-pink-600' },
    { key: 'facebook',  label: 'Facebook',   color: 'text-blue-600' },
    { key: 'youtube',   label: 'YouTube',    color: 'text-red-600' },
    { key: 'snapchat',  label: 'Snapchat',   color: 'text-yellow-500' },
];

function totalFollowers(stat) {
    if (!stat.social_followers) return 0;
    return Object.values(stat.social_followers).reduce((s, v) => s + v, 0);
}
</script>

<template>
    <Head :title="c.title" />

    <VendorLayout>
        <div class="space-y-6">

            <!-- Breadcrumb & Header -->
            <div>
                <Link :href="route('vendor.campaigns.index')" class="inline-flex items-center gap-1 text-sm text-slate-500 transition hover:text-purple-600">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" /></svg>
                    Retour aux campagnes
                </Link>

                <div class="mt-3 flex flex-wrap items-center gap-3">
                    <h1 class="text-xl font-bold text-slate-900">{{ c.title }}</h1>
                    <span class="inline-flex items-center rounded-full border px-3 py-1 text-xs font-semibold" :class="statusColor(c.status)">
                        {{ statusLabel(c.status) }}
                    </span>
                    <span v-if="c.open_sea" class="inline-flex items-center rounded-full border border-purple-200 bg-purple-100 px-3 py-1 text-xs font-semibold text-purple-700">
                        Open Sea
                    </span>

                    <div class="ml-auto flex items-center gap-2">
                        <!-- Modifier -->
                        <Link :href="route('vendor.campaigns.edit', c.id)" class="inline-flex items-center gap-1.5 rounded-full border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-600 shadow-sm transition hover:bg-slate-50 hover:text-purple-600">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" /></svg>
                            Modifier
                        </Link>

                        <!-- Pause / Reprendre -->
                        <button
                            v-if="c.status === 'active' || c.status === 'paused'"
                            @click="togglePause"
                            :disabled="pauseProcessing"
                            class="inline-flex items-center gap-1.5 rounded-full border px-3 py-1.5 text-xs font-semibold shadow-sm transition disabled:opacity-50"
                            :class="c.status === 'active'
                                ? 'border-amber-200 bg-amber-50 text-amber-700 hover:bg-amber-100'
                                : 'border-purple-200 bg-purple-50 text-purple-700 hover:bg-purple-100'"
                        >
                            <!-- Pause icon -->
                            <svg v-if="c.status === 'active'" xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25v13.5m-7.5-13.5v13.5" /></svg>
                            <!-- Play icon -->
                            <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M5.25 5.653c0-.856.917-1.398 1.667-.986l11.54 6.348a1.125 1.125 0 010 1.971l-11.54 6.347a1.125 1.125 0 01-1.667-.985V5.653z" /></svg>
                            {{ c.status === 'active' ? 'Mettre en pause' : 'Reprendre' }}
                        </button>

                        <!-- Ajouter Budget -->
                        <button
                            v-if="c.status === 'active' || c.status === 'paused' || c.status === 'completed'"
                            @click="openBudgetModal"
                            class="inline-flex items-center gap-1.5 rounded-full border border-purple-200 bg-purple-50 px-3 py-1.5 text-xs font-semibold text-purple-700 shadow-sm transition hover:bg-purple-100"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            Ajouter du budget
                        </button>

                        <!-- Supprimer -->
                        <button
                            v-if="c.status !== 'rejected'"
                            @click="deleteModal = true"
                            class="inline-flex items-center gap-1.5 rounded-full border border-red-200 bg-red-50 px-3 py-1.5 text-xs font-semibold text-red-600 shadow-sm transition hover:bg-red-100"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" /></svg>
                            Supprimer
                        </button>
                    </div>
                </div>
            </div>

            <!-- Banniere Rejet -->
            <div v-if="c.status === 'rejected'" class="rounded-2xl border border-red-200 bg-red-50 p-5">
                <div class="flex items-start gap-3">
                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-red-100">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-red-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" /></svg>
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-red-800">Campagne rejetee par le Robot Douanier</h3>
                        <p v-if="c.rejection_reason" class="mt-1 text-sm text-red-700">{{ c.rejection_reason }}</p>
                        <p class="mt-2 text-xs text-red-600">Modifiez votre campagne pour supprimer le contenu interdit, elle sera automatiquement reactvee.</p>
                        <Link :href="route('vendor.campaigns.edit', c.id)" class="mt-3 inline-flex items-center gap-1.5 rounded-lg bg-red-600 px-4 py-2 text-xs font-semibold text-white shadow-sm transition hover:bg-red-700">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" /></svg>
                            Modifier la campagne
                        </Link>
                    </div>
                </div>
            </div>

            <!-- KPI Cards -->
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <!-- Budget Consomme -->
                <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-purple-100">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-purple-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75M15 10.5a3 3 0 11-6 0 3 3 0 016 0zm3 0h.008v.008H18V10.5zm-12 0h.008v.008H6V10.5z" /></svg>
                        </div>
                        <p class="text-xs font-medium text-slate-500">Budget consomme</p>
                    </div>
                    <p class="text-lg font-bold text-slate-900">{{ formatCurrency(budgetConsumed) }}</p>
                    <p class="text-xs text-slate-400 mt-1">sur {{ formatCurrency(c.total_budget) }} total</p>
                </div>

                <!-- Budget Restant -->
                <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-purple-100">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-purple-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M21 12a2.25 2.25 0 00-2.25-2.25H15a3 3 0 11-6 0H5.25A2.25 2.25 0 003 12m18 0v6a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 18v-6m18 0V9M3 12V9m18 0a2.25 2.25 0 00-2.25-2.25H5.25A2.25 2.25 0 003 9m18 0V6a2.25 2.25 0 00-2.25-2.25H5.25A2.25 2.25 0 003 6v3" /></svg>
                        </div>
                        <p class="text-xs font-medium text-slate-500">Budget restant</p>
                    </div>
                    <p class="text-lg font-bold text-slate-900">{{ formatCurrency(c.remaining_budget) }}</p>
                    <p class="text-xs text-slate-400 mt-1">CPC : {{ formatCurrency(c.click_price) }}</p>
                </div>

                <!-- Clics Payes -->
                <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-purple-100">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-purple-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.042 21.672L13.684 16.6m0 0l-2.51 2.225.569-9.47 5.227 7.917-3.286-.672zM12 2.25V4.5m5.834.166l-1.591 1.591M20.25 10.5H18M7.757 14.743l-1.59 1.59M6 10.5H3.75m4.007-4.243l-1.59-1.59" /></svg>
                        </div>
                        <p class="text-xs font-medium text-slate-500">Clics payes</p>
                    </div>
                    <p class="text-lg font-bold text-slate-900">{{ paidClicks.toLocaleString('fr-FR') }}</p>
                    <p class="text-xs text-slate-400 mt-1">{{ totalClicks.toLocaleString('fr-FR') }} clics totaux</p>
                </div>

                <!-- Taux de Conversion (Clics valides) -->
                <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-amber-100">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-amber-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" /></svg>
                        </div>
                        <p class="text-xs font-medium text-slate-500">Taux de validite</p>
                    </div>
                    <p class="text-lg font-bold text-slate-900">{{ conversionRate }}%</p>
                    <p class="text-xs text-slate-400 mt-1">Clics valides / totaux</p>
                </div>
            </div>

            <!-- Progress Gauge - Budget -->
            <div class="rounded-2xl border border-slate-200/80 bg-white p-6 shadow-sm">
                <div class="flex items-center justify-between mb-2">
                    <h2 class="text-sm font-semibold text-slate-900">Progression du budget</h2>
                    <span class="text-sm font-bold" :class="budgetPercent >= 90 ? 'text-red-600' : budgetPercent >= 60 ? 'text-amber-600' : 'text-purple-600'">
                        {{ budgetPercent }}%
                    </span>
                </div>
                <div class="h-4 w-full overflow-hidden rounded-full bg-slate-100">
                    <div
                        class="h-full rounded-full transition-all duration-700"
                        :class="budgetPercent >= 90 ? 'bg-red-500' : budgetPercent >= 60 ? 'bg-amber-500' : 'bg-purple-500'"
                        :style="{ width: budgetPercent + '%' }"
                    />
                </div>
                <div class="mt-2 flex justify-between text-xs text-slate-500">
                    <span>{{ formatCurrency(budgetConsumed) }} consomme</span>
                    <span>{{ formatCurrency(c.remaining_budget) }} restant</span>
                </div>
            </div>

            <!-- Details Campagne -->
            <div class="rounded-2xl border border-slate-200/80 bg-white shadow-sm overflow-hidden">
                <div class="border-b border-slate-100 px-6 py-4">
                    <h2 class="text-sm font-semibold text-slate-900">Informations de la campagne</h2>
                </div>
                <div class="divide-y divide-slate-100">
                    <div class="grid grid-cols-3 px-6 py-3">
                        <span class="text-sm text-slate-500">URL cible</span>
                        <span class="col-span-2 text-sm text-slate-900 truncate">{{ c.target_url }}</span>
                    </div>
                    <div class="grid grid-cols-3 px-6 py-3">
                        <span class="text-sm text-slate-500">Palier</span>
                        <span class="col-span-2 text-sm text-slate-900">{{ c.tier ?? 'N/A' }}</span>
                    </div>
                    <div class="grid grid-cols-3 px-6 py-3">
                        <span class="text-sm text-slate-500">Niche</span>
                        <span class="col-span-2 text-sm text-slate-900 capitalize">{{ c.niche ?? 'N/A' }}</span>
                    </div>
                    <div class="grid grid-cols-3 px-6 py-3">
                        <span class="text-sm text-slate-500">Plateformes</span>
                        <span class="col-span-2 text-sm text-slate-900">
                            <template v-if="c.platforms && c.platforms.length">
                                <span v-for="(p, i) in c.platforms" :key="p" class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-medium text-slate-600 mr-1 capitalize">{{ p }}</span>
                            </template>
                            <template v-else>N/A</template>
                        </span>
                    </div>
                    <div class="grid grid-cols-3 px-6 py-3">
                        <span class="text-sm text-slate-500">Pays cible</span>
                        <span class="col-span-2 text-sm text-slate-900">{{ Array.isArray(c.target_country) ? c.target_country.join(', ') : (c.target_country ?? 'N/A') }}</span>
                    </div>
                    <div class="grid grid-cols-3 px-6 py-3">
                        <span class="text-sm text-slate-500">Liens SmartLink</span>
                        <span class="col-span-2 text-sm font-semibold text-slate-900">{{ c.smart_links_count ?? 0 }}</span>
                    </div>
                    <div v-if="c.media_path" class="grid grid-cols-3 px-6 py-3">
                        <span class="text-sm text-slate-500">Media</span>
                        <div class="col-span-2">
                            <img v-if="c.media_type === 'image'" :src="'/storage/' + c.media_path" alt="Media campagne" class="h-20 w-auto rounded-lg object-cover" />
                            <video v-else-if="c.media_type === 'video'" :src="'/storage/' + c.media_path" controls class="h-24 w-auto rounded-lg" />
                        </div>
                    </div>
                </div>
            </div>

            <!-- ══════════════════════════════════════════════ -->
            <!--  Tableau de Transparence — ROI par Influenceur -->
            <!-- ══════════════════════════════════════════════ -->
            <div class="rounded-2xl border border-slate-200/80 bg-white shadow-sm overflow-hidden">
                <div class="border-b border-slate-100 px-6 py-4 flex items-center gap-3">
                    <div class="flex h-8 w-8 items-center justify-center rounded-xl bg-purple-100">
                        <!-- Heroicon: ChartBar -->
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-purple-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" />
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-sm font-semibold text-slate-900">Performances par Createur de Contenu</h2>
                        <p class="text-xs text-slate-500">Transparence complete sur les resultats generes par chaque createur de contenu.</p>
                    </div>
                </div>

                <!-- Etat vide -->
                <div v-if="!influencerStats.length" class="px-6 py-12 text-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="mx-auto h-10 w-10 text-slate-300" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" />
                    </svg>
                    <p class="mt-3 text-sm font-medium text-slate-600">Aucun createur de contenu n'a encore genere de lien</p>
                    <p class="mt-1 text-xs text-slate-400">Les statistiques apparaitront des qu'un createur de contenu generera un SmartLink.</p>
                </div>

                <!-- Tableau ROI -->
                <div v-else class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-100">
                        <thead class="bg-gradient-to-r from-slate-50 to-purple-50/30">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Createur de Contenu</th>
                                <th scope="col" class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-slate-500">Abonnes</th>
                                <th scope="col" class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-slate-500">Clics</th>
                                <th scope="col" class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-slate-500">Ventes</th>
                                <th scope="col" class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-500">Total paye</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50">
                            <tr v-for="stat in influencerStats" :key="stat.influencer_id" class="hover:bg-slate-50/50 transition">
                                <!-- Avatar & Nom -->
                                <td class="whitespace-nowrap px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="flex h-9 w-9 items-center justify-center rounded-full text-sm font-bold text-white"
                                             :class="stat.influencer_tier === 'or' ? 'bg-purple-600' : stat.influencer_tier === 'argent' ? 'bg-purple-600' : 'bg-amber-500'">
                                            {{ stat.influencer_name.charAt(0).toUpperCase() }}
                                        </div>
                                        <div>
                                            <Link v-if="stat.influencer_slug" :href="route('vendor.influencer.show', stat.influencer_id)" class="text-sm font-semibold text-slate-900 hover:text-purple-600 transition">
                                                {{ stat.influencer_name }}
                                            </Link>
                                            <p v-else class="text-sm font-semibold text-slate-900">{{ stat.influencer_name }}</p>
                                            <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider ring-1 ring-inset"
                                                  :class="stat.influencer_tier === 'or' ? 'bg-purple-50 text-purple-700 ring-purple-500/30' : stat.influencer_tier === 'argent' ? 'bg-purple-50 text-purple-700 ring-purple-500/30' : 'bg-amber-50 text-amber-700 ring-amber-500/30'">
                                                {{ stat.influencer_tier === 'or' ? 'Or' : stat.influencer_tier === 'argent' ? 'Argent' : 'Bronze' }}
                                            </span>
                                        </div>
                                    </div>
                                </td>
                                <!-- Abonnes par reseau social -->
                                <td class="whitespace-nowrap px-4 py-4">
                                    <div class="flex flex-col gap-1">
                                        <p class="text-sm font-bold text-slate-900 mb-1">{{ formatFollowers(totalFollowers(stat)) }}</p>
                                        <div class="flex flex-wrap gap-1.5">
                                            <template v-for="p in socialPlatforms" :key="p.key">
                                                <span
                                                    v-if="stat.social_followers && stat.social_followers[p.key] > 0"
                                                    class="inline-flex items-center gap-1 rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-medium text-slate-600"
                                                    :title="p.label"
                                                >
                                                    <span :class="p.color">{{ p.label.substring(0, 2) }}</span>
                                                    {{ formatFollowers(stat.social_followers[p.key]) }}
                                                </span>
                                            </template>
                                        </div>
                                    </div>
                                </td>
                                <!-- Clics -->
                                <td class="whitespace-nowrap px-4 py-4 text-center">
                                    <p class="text-sm font-bold text-slate-900">{{ stat.paid_clicks.toLocaleString('fr-FR') }}</p>
                                    <p class="text-[10px] text-slate-400">{{ stat.total_clicks.toLocaleString('fr-FR') }} totaux</p>
                                </td>
                                <!-- Ventes -->
                                <td class="whitespace-nowrap px-4 py-4 text-center">
                                    <p class="text-sm font-bold" :class="stat.orders_count > 0 ? 'text-purple-700' : 'text-slate-400'">
                                        {{ stat.orders_count }}
                                    </p>
                                </td>
                                <!-- Total Paye -->
                                <td class="whitespace-nowrap px-4 py-4 text-right">
                                    <p class="text-sm font-bold text-slate-900">{{ formatCurrency(stat.total_paid) }}</p>
                                    <p class="text-[10px] text-slate-400">
                                        CPC: {{ formatCurrency(stat.cpc_earnings) }}
                                        <span v-if="stat.cpa_earnings > 0"> + CPA: {{ formatCurrency(stat.cpa_earnings) }}</span>
                                    </p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- ══════════════════════════════════════════════ -->
            <!--  Modal — Ajouter du Budget                    -->
            <!-- ══════════════════════════════════════════════ -->
            <Teleport to="body">
                <div v-if="budgetModal" class="fixed inset-0 z-50 flex items-center justify-center">
                    <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm" @click="closeBudgetModal"></div>
                    <div class="relative w-full max-w-md rounded-2xl bg-white p-6 shadow-xl">
                        <h3 class="text-lg font-bold text-slate-900 mb-1">Ajouter du budget</h3>
                        <p class="text-sm text-slate-500 mb-4">Le montant sera debite de votre solde et ajoute au budget restant de la campagne.</p>

                        <div class="mb-4 flex items-center gap-2 rounded-xl bg-slate-50 px-4 py-3">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-slate-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M21 12a2.25 2.25 0 00-2.25-2.25H15a3 3 0 11-6 0H5.25A2.25 2.25 0 003 12m18 0v6a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 18v-6m18 0V9M3 12V9m18 0a2.25 2.25 0 00-2.25-2.25H5.25A2.25 2.25 0 003 9m18 0V6a2.25 2.25 0 00-2.25-2.25H5.25A2.25 2.25 0 003 6v3" /></svg>
                            <span class="text-sm text-slate-600">Solde disponible : <strong class="text-slate-900">{{ formatCurrency(available_balance) }}</strong></span>
                        </div>

                        <div class="mb-5">
                            <label for="budget_amount" class="block text-sm font-medium text-slate-700 mb-1">Montant (FCFA)</label>
                            <input
                                id="budget_amount"
                                v-model.number="budgetAmount"
                                type="number"
                                min="500"
                                step="500"
                                class="w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-purple-500 focus:ring-purple-500"
                                placeholder="Minimum 500 FCFA"
                            />
                        </div>

                        <div class="flex items-center gap-3 justify-end">
                            <button @click="closeBudgetModal" class="rounded-xl border border-slate-200 px-4 py-2 text-sm font-medium text-slate-600 transition hover:bg-slate-50">
                                Annuler
                            </button>
                            <button
                                @click="submitBudget"
                                :disabled="budgetProcessing || !budgetAmount || parseFloat(budgetAmount) < 500"
                                class="rounded-xl bg-purple-600 px-5 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-purple-700 disabled:opacity-50"
                            >
                                {{ budgetProcessing ? 'Traitement...' : 'Confirmer' }}
                            </button>
                        </div>
                    </div>
                </div>
            </Teleport>

            <!-- ══════════════════════════════════════════════ -->
            <!--  Modal — Confirmation Suppression             -->
            <!-- ══════════════════════════════════════════════ -->
            <Teleport to="body">
                <div v-if="deleteModal" class="fixed inset-0 z-50 flex items-center justify-center">
                    <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm" @click="deleteModal = false"></div>
                    <div class="relative w-full max-w-md rounded-2xl bg-white p-6 shadow-xl">
                        <div class="flex items-start gap-4">
                            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-red-100">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-red-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" /></svg>
                            </div>
                            <div>
                                <h3 class="text-lg font-bold text-slate-900">Supprimer cette campagne ?</h3>
                                <p class="mt-1 text-sm text-slate-500">Cette action est irreversible. La campagne sera definitivement supprimee et le budget restant ne sera pas rembourse.</p>
                            </div>
                        </div>
                        <div class="mt-5 flex items-center gap-3 justify-end">
                            <button @click="deleteModal = false" class="rounded-xl border border-slate-200 px-4 py-2 text-sm font-medium text-slate-600 transition hover:bg-slate-50">
                                Annuler
                            </button>
                            <button @click="confirmDelete" class="rounded-xl bg-red-600 px-5 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-red-700">
                                Supprimer definitivement
                            </button>
                        </div>
                    </div>
                </div>
            </Teleport>
        </div>
    </VendorLayout>
</template>
