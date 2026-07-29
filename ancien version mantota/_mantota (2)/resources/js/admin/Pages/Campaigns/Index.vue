<script setup>
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import Layout from '../Layout.vue';

defineOptions({ layout: Layout });

const props = defineProps({
    campaigns: { type: Array, required: true },
    summary:   { type: Object, default: () => ({}) },
});

const page = usePage();
const flash = computed(() => page.props.flash?.success ?? null);

const confirmingDelete = ref(null);
const search = ref('');
const statusFilter = ref('all');

const filteredCampaigns = computed(() => {
    let list = props.campaigns;
    if (statusFilter.value !== 'all') {
        list = list.filter(c => c.status === statusFilter.value);
    }
    if (search.value.trim()) {
        const q = search.value.toLowerCase();
        list = list.filter(c => c.title.toLowerCase().includes(q) || (c.target_url && c.target_url.toLowerCase().includes(q)));
    }
    return list;
});

function fmt(n) {
    return new Intl.NumberFormat('fr-FR').format(n);
}

function budgetPercent(c) {
    if (!c.total_budget) return 0;
    return Math.round(((c.total_budget - c.remaining_budget) / c.total_budget) * 100);
}

function statusLabel(s) {
    const map = { active: 'Active', paused: 'En pause', completed: 'Epuisee', draft: 'Brouillon', deleted: 'Supprimee', expired: 'Expiree' };
    return map[s] ?? s;
}

function statusDot(s) {
    const map = { active: 'bg-emerald-500', paused: 'bg-amber-500', completed: 'bg-slate-400', draft: 'bg-slate-300', deleted: 'bg-red-400', expired: 'bg-orange-400' };
    return map[s] ?? 'bg-slate-300';
}

function statusClasses(s) {
    const map = {
        active: 'bg-emerald-50 text-emerald-700 ring-emerald-600/20',
        paused: 'bg-amber-50 text-amber-700 ring-amber-600/20',
        completed: 'bg-slate-100 text-slate-600 ring-slate-500/20',
        draft: 'bg-slate-50 text-slate-500 ring-slate-400/20',
        deleted: 'bg-red-50 text-red-600 ring-red-500/20',
        expired: 'bg-orange-50 text-orange-600 ring-orange-500/20',
    };
    return map[s] ?? 'bg-slate-50 text-slate-500 ring-slate-400/20';
}

function togglePause(c) {
    router.post(route('admin.campaigns.toggle-pause', c.id), {}, { preserveScroll: true });
}

function deleteCampaign(c) {
    router.delete(route('admin.campaigns.destroy', c.id), { preserveScroll: true });
    confirmingDelete.value = null;
}
</script>

<template>
    <Head title="Campagnes Systeme" />

    <div class="space-y-6">
        <!-- Header -->
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-br from-teal-500 to-cyan-600 shadow-lg shadow-teal-500/25">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-white" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9.004 9.004 0 008.716-6.747M12 21a9.004 9.004 0 01-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 017.843 4.582M12 3a8.997 8.997 0 00-7.843 4.582m15.686 0A11.953 11.953 0 0112 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0121 12c0 .778-.099 1.533-.284 2.253m0 0A17.919 17.919 0 0112 16.5c-3.162 0-6.133-.815-8.716-2.247m0 0A9.015 9.015 0 013 12c0-1.605.42-3.113 1.157-4.418" />
                    </svg>
                </div>
                <div>
                    <h1 class="text-lg font-bold text-slate-800">Campagnes Systeme</h1>
                    <p class="text-xs text-slate-500">Budget virtuel &mdash; God Mode</p>
                </div>
            </div>
            <Link
                :href="route('admin.campaigns.create')"
                class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-teal-600 to-cyan-600 px-5 py-2.5 text-sm font-semibold text-white shadow-lg shadow-teal-500/25 transition hover:shadow-xl hover:shadow-teal-500/30"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                Nouvelle campagne
            </Link>
        </div>

        <!-- KPI Cards -->
        <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-6">
            <!-- Total campaigns -->
            <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <div class="flex items-center gap-2">
                    <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-slate-100">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-slate-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z" />
                        </svg>
                    </div>
                </div>
                <p class="mt-3 text-2xl font-bold text-slate-800">{{ summary.total_campaigns ?? 0 }}</p>
                <p class="text-xs text-slate-500">Campagnes</p>
            </div>
            <!-- Active -->
            <div class="rounded-2xl border border-emerald-200 bg-emerald-50/50 p-4 shadow-sm">
                <div class="flex items-center gap-2">
                    <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-emerald-100">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z" />
                        </svg>
                    </div>
                </div>
                <p class="mt-3 text-2xl font-bold text-emerald-700">{{ summary.active_campaigns ?? 0 }}</p>
                <p class="text-xs text-emerald-600">Actives</p>
            </div>
            <!-- Budget total -->
            <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <div class="flex items-center gap-2">
                    <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-blue-100">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-blue-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75M15 10.5a3 3 0 11-6 0 3 3 0 016 0zm3 0h.008v.008H18V10.5zm-12 0h.008v.008H6V10.5z" />
                        </svg>
                    </div>
                </div>
                <p class="mt-3 text-2xl font-bold text-slate-800">{{ fmt(summary.total_budget ?? 0) }}</p>
                <p class="text-xs text-slate-500">Budget total (F)</p>
            </div>
            <!-- Depense -->
            <div class="rounded-2xl border border-orange-200 bg-orange-50/50 p-4 shadow-sm">
                <div class="flex items-center gap-2">
                    <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-orange-100">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-orange-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6L9 12.75l4.286-4.286a11.948 11.948 0 014.306 6.43l.776 2.898m0 0l3.182-5.511m-3.182 5.51l-5.511-3.181" />
                        </svg>
                    </div>
                </div>
                <p class="mt-3 text-2xl font-bold text-orange-700">{{ fmt(summary.total_spent ?? 0) }}</p>
                <p class="text-xs text-orange-600">Depense (F)</p>
            </div>
            <!-- Restant -->
            <div class="rounded-2xl border border-teal-200 bg-teal-50/50 p-4 shadow-sm">
                <div class="flex items-center gap-2">
                    <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-teal-100">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-teal-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                </div>
                <p class="mt-3 text-2xl font-bold text-teal-700">{{ fmt(summary.total_remaining ?? 0) }}</p>
                <p class="text-xs text-teal-600">Restant (F)</p>
            </div>
            <!-- Clics valides -->
            <div class="rounded-2xl border border-purple-200 bg-purple-50/50 p-4 shadow-sm">
                <div class="flex items-center gap-2">
                    <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-purple-100">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-purple-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.042 21.672L13.684 16.6m0 0l-2.51 2.225.569-9.47 5.227 7.917-3.286-.672zM12 2.25V4.5m5.834.166l-1.591 1.591M20.25 10.5H18M7.757 14.743l-1.59 1.59M6 10.5H3.75m4.007-4.243l-1.59-1.59" />
                        </svg>
                    </div>
                </div>
                <p class="mt-3 text-2xl font-bold text-purple-700">{{ fmt(summary.total_valid_clicks ?? 0) }}</p>
                <p class="text-xs text-purple-600">Clics valides</p>
            </div>
        </div>

        <!-- Flash -->
        <div v-if="flash" class="flex items-center gap-3 rounded-2xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0 text-green-500" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
            </svg>
            {{ flash }}
        </div>

        <!-- Filters bar -->
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-center gap-2">
                <div class="relative">
                    <svg xmlns="http://www.w3.org/2000/svg" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                    </svg>
                    <input
                        v-model="search"
                        type="text"
                        placeholder="Rechercher..."
                        class="w-64 rounded-xl border border-slate-200 bg-white py-2 pl-10 pr-4 text-sm text-slate-700 placeholder-slate-400 transition focus:border-teal-400 focus:outline-none focus:ring-2 focus:ring-teal-400/20"
                    />
                </div>
                <select
                    v-model="statusFilter"
                    class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 transition focus:border-teal-400 focus:outline-none focus:ring-2 focus:ring-teal-400/20"
                >
                    <option value="all">Tous les statuts</option>
                    <option value="active">Active</option>
                    <option value="paused">En pause</option>
                    <option value="completed">Epuisee</option>
                    <option value="draft">Brouillon</option>
                </select>
            </div>
            <p class="text-xs text-slate-500">{{ filteredCampaigns.length }} campagne{{ filteredCampaigns.length !== 1 ? 's' : '' }}</p>
        </div>

        <!-- Empty state -->
        <div v-if="!campaigns.length" class="rounded-2xl border-2 border-dashed border-slate-300 bg-white p-16 text-center">
            <svg xmlns="http://www.w3.org/2000/svg" class="mx-auto h-12 w-12 text-slate-300" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10.34 15.84c-.688-.06-1.386-.09-2.09-.09H7.5a4.5 4.5 0 110-9h.75c.704 0 1.402-.03 2.09-.09m0 9.18c.253.962.584 1.892.985 2.783.247.55.06 1.21-.463 1.511l-.657.38c-.551.318-1.26.117-1.527-.461a20.845 20.845 0 01-1.44-4.282m3.102.069a18.03 18.03 0 01-.59-4.59c0-1.586.205-3.124.59-4.59m0 9.18a23.848 23.848 0 018.835 2.535M10.34 6.66a23.847 23.847 0 008.835-2.535m0 0A23.74 23.74 0 0018.795 3m.38 1.125a23.91 23.91 0 011.014 5.395m-1.014 8.855c-.118.38-.245.754-.38 1.125m.38-1.125a23.91 23.91 0 001.014-5.395m0-3.46c.495.413.811 1.035.811 1.73 0 .695-.316 1.317-.811 1.73m0-3.46a24.347 24.347 0 010 3.46" />
            </svg>
            <h3 class="mt-4 text-sm font-semibold text-slate-900">Aucune campagne systeme</h3>
            <p class="mt-1 text-sm text-slate-500">Creez votre premiere campagne officielle MANTOTA.</p>
        </div>

        <!-- Campaign Cards -->
        <div v-else class="space-y-3">
            <div
                v-for="c in filteredCampaigns"
                :key="c.id"
                class="group rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:border-slate-300 hover:shadow-md"
            >
                <!-- Top row: title + status + actions -->
                <div class="flex items-start justify-between gap-4">
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-2.5">
                            <span :class="statusDot(c.status)" class="h-2.5 w-2.5 shrink-0 rounded-full"></span>
                            <h3 class="truncate text-sm font-semibold text-slate-800">{{ c.title }}</h3>
                            <span :class="statusClasses(c.status)" class="inline-flex shrink-0 items-center rounded-full px-2 py-0.5 text-[10px] font-semibold ring-1 ring-inset">
                                {{ statusLabel(c.status) }}
                            </span>
                        </div>
                        <p class="mt-1 truncate text-xs text-slate-400 pl-5">{{ c.target_url }}</p>
                    </div>
                    <div class="flex shrink-0 items-center gap-1.5">
                        <Link
                            :href="route('admin.campaigns.edit', c.id)"
                            class="inline-flex items-center justify-center rounded-lg p-2 text-slate-400 transition hover:bg-teal-50 hover:text-teal-600"
                            title="Editer"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" />
                            </svg>
                        </Link>
                        <button
                            v-if="c.status === 'active' || c.status === 'paused'"
                            @click="togglePause(c)"
                            class="inline-flex items-center justify-center rounded-lg p-2 text-slate-400 transition hover:bg-amber-50 hover:text-amber-600"
                            :title="c.status === 'active' ? 'Mettre en pause' : 'Reactiver'"
                        >
                            <svg v-if="c.status === 'active'" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25v13.5m-7.5-13.5v13.5" />
                            </svg>
                            <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5.25 5.653c0-.856.917-1.398 1.667-.986l11.54 6.348a1.125 1.125 0 010 1.971l-11.54 6.347a1.125 1.125 0 01-1.667-.985V5.653z" />
                            </svg>
                        </button>
                        <button
                            v-if="confirmingDelete !== c.id"
                            @click="confirmingDelete = c.id"
                            class="inline-flex items-center justify-center rounded-lg p-2 text-slate-400 transition hover:bg-red-50 hover:text-red-600"
                            title="Supprimer"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                            </svg>
                        </button>
                        <div v-if="confirmingDelete === c.id" class="flex items-center gap-1">
                            <button @click="deleteCampaign(c)" class="rounded-lg bg-red-600 px-2.5 py-1 text-xs font-semibold text-white transition hover:bg-red-700">Confirmer</button>
                            <button @click="confirmingDelete = null" class="rounded-lg bg-slate-200 px-2.5 py-1 text-xs font-semibold text-slate-600 transition hover:bg-slate-300">Annuler</button>
                        </div>
                    </div>
                </div>

                <!-- Stats grid -->
                <div class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-4 lg:grid-cols-7">
                    <!-- Pays -->
                    <div>
                        <p class="text-[10px] font-medium uppercase tracking-wider text-slate-400">Pays</p>
                        <p class="mt-0.5 text-sm font-medium text-slate-700">{{ Array.isArray(c.target_country) ? c.target_country.join(', ') : c.target_country }}</p>
                    </div>
                    <!-- CPC -->
                    <div>
                        <p class="text-[10px] font-medium uppercase tracking-wider text-slate-400">CPC</p>
                        <p class="mt-0.5 text-sm font-bold text-purple-600">{{ c.click_price }} F</p>
                    </div>
                    <!-- Budget -->
                    <div>
                        <p class="text-[10px] font-medium uppercase tracking-wider text-slate-400">Budget</p>
                        <p class="mt-0.5 text-sm font-semibold text-slate-700">{{ fmt(c.total_budget) }} F</p>
                    </div>
                    <!-- Restant -->
                    <div>
                        <p class="text-[10px] font-medium uppercase tracking-wider text-slate-400">Restant</p>
                        <p class="mt-0.5 text-sm font-semibold" :class="c.remaining_budget > 0 ? 'text-teal-600' : 'text-red-600'">{{ fmt(c.remaining_budget) }} F</p>
                    </div>
                    <!-- Clics -->
                    <div>
                        <p class="text-[10px] font-medium uppercase tracking-wider text-slate-400">Clics valides</p>
                        <p class="mt-0.5">
                            <span class="text-sm font-bold text-slate-800">{{ fmt(c.valid_clicks) }}</span>
                            <span class="text-xs text-slate-400"> / {{ fmt(c.total_clicks) }}</span>
                        </p>
                    </div>
                    <!-- SmartLinks -->
                    <div>
                        <p class="text-[10px] font-medium uppercase tracking-wider text-slate-400">SmartLinks</p>
                        <p class="mt-0.5 text-sm font-semibold text-cyan-600">{{ c.smart_links_count }}</p>
                    </div>
                    <!-- Date -->
                    <div>
                        <p class="text-[10px] font-medium uppercase tracking-wider text-slate-400">Creee le</p>
                        <p class="mt-0.5 text-sm text-slate-600">{{ c.created_at }}</p>
                    </div>
                </div>

                <!-- Budget progress bar -->
                <div class="mt-3">
                    <div class="flex items-center justify-between text-[10px] text-slate-500 mb-1">
                        <span>Consommation budget</span>
                        <span class="font-semibold" :class="budgetPercent(c) > 90 ? 'text-red-600' : budgetPercent(c) > 60 ? 'text-amber-600' : 'text-teal-600'">{{ budgetPercent(c) }}%</span>
                    </div>
                    <div class="h-1.5 w-full overflow-hidden rounded-full bg-slate-100">
                        <div
                            class="h-full rounded-full transition-all duration-500"
                            :class="budgetPercent(c) > 90 ? 'bg-red-500' : budgetPercent(c) > 60 ? 'bg-amber-500' : 'bg-teal-500'"
                            :style="{ width: budgetPercent(c) + '%' }"
                        ></div>
                    </div>
                </div>

                <!-- Niche & Platforms tags -->
                <div v-if="c.niche || (c.platforms && c.platforms.length)" class="mt-3 flex flex-wrap items-center gap-1.5">
                    <span v-if="c.niche" class="inline-flex items-center rounded-md bg-slate-100 px-2 py-0.5 text-[10px] font-medium text-slate-600">{{ c.niche }}</span>
                    <span
                        v-for="p in (c.platforms || [])"
                        :key="p"
                        class="inline-flex items-center rounded-md bg-cyan-50 px-2 py-0.5 text-[10px] font-medium text-cyan-700"
                    >{{ p }}</span>
                </div>
            </div>
        </div>
    </div>
</template>
