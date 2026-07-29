<script setup>
import InfluencerLayout from '../Layouts/InfluencerLayout.vue';
import { Head, usePage, Link } from '@inertiajs/vue3';
import { computed, ref, onMounted, onUnmounted } from 'vue';

/**
 * Props transmises par Influencer\DashboardController@myLinks.
 *
 * - links : Collection paginee de SmartLinks avec campaign, paid_clicks_count.
 */
const props = defineProps({
    links: {
        type: Object,
        required: true,
    },
});

const page = usePage();
const flashSuccess = computed(() => page.props.flash?.success ?? null);
const flashErrors = computed(() => page.props.errors ?? {});

// ──────────────────────────────────────────────
//  Copy-to-clipboard
// ──────────────────────────────────────────────

const copiedLinkId = ref(null);
let copyTimeoutId = null;

function buildLinkUrl(hash) {
    return `${window.location.origin}/go/${hash}`;
}

function copyLink(link) {
    const url = buildLinkUrl(link.unique_hash);
    navigator.clipboard.writeText(url).then(() => {
        if (copyTimeoutId) clearTimeout(copyTimeoutId);
        copiedLinkId.value = link.id;
        copyTimeoutId = setTimeout(() => { copiedLinkId.value = null; }, 2000);
    });
}

// ──────────────────────────────────────────────
//  Chronometre 48h — Decompte en temps reel
// ──────────────────────────────────────────────

const countdowns = ref({});
let countdownIntervalId = null;

function computeCountdownLabel(expiresAt) {
    const remainingMilliseconds = new Date(expiresAt).getTime() - Date.now();
    if (remainingMilliseconds <= 0) return null;

    const remainingHours   = Math.floor(remainingMilliseconds / 3600000);
    const remainingMinutes = Math.floor((remainingMilliseconds % 3600000) / 60000);
    const remainingSeconds = Math.floor((remainingMilliseconds % 60000) / 1000);

    return [
        String(remainingHours).padStart(2, '0'),
        String(remainingMinutes).padStart(2, '0'),
        String(remainingSeconds).padStart(2, '0'),
    ].join(':');
}

function countdownProgressPercent(expiresAt) {
    const totalDuration = 48 * 60 * 60 * 1000;
    const remainingMilliseconds = new Date(expiresAt).getTime() - Date.now();
    if (remainingMilliseconds <= 0) return 0;
    return Math.min(100, (remainingMilliseconds / totalDuration) * 100);
}

function progressBarColor(expiresAt) {
    const percent = countdownProgressPercent(expiresAt);
    if (percent > 50) return 'bg-teal-500';
    if (percent > 20) return 'bg-amber-500';
    return 'bg-red-500';
}

function isExpiringWithin12Hours(expiresAt) {
    const remaining = new Date(expiresAt).getTime() - Date.now();
    return remaining > 0 && remaining < 12 * 60 * 60 * 1000;
}

function refreshCountdowns() {
    const updated = {};
    for (const link of props.links.data) {
        updated[link.id] = computeCountdownLabel(link.expires_at);
    }
    countdowns.value = updated;
}

onMounted(() => {
    refreshCountdowns();
    countdownIntervalId = setInterval(refreshCountdowns, 1000);
});

onUnmounted(() => {
    if (countdownIntervalId) clearInterval(countdownIntervalId);
    if (copyTimeoutId) clearTimeout(copyTimeoutId);
});

// ──────────────────────────────────────────────
//  Helpers d'affichage
// ──────────────────────────────────────────────

function linkStatus(link) {
    if (link.campaign?.status === 'paused') return 'paused';
    if (new Date(link.expires_at).getTime() > Date.now()) return 'active';
    return 'expired';
}

function statusBadgeClasses(status) {
    const map = {
        active:  'bg-teal-50 text-teal-700 ring-teal-600/20',
        expired: 'bg-red-50 text-red-700 ring-red-600/20',
        paused:  'bg-amber-50 text-amber-700 ring-amber-600/20',
    };
    return map[status] ?? 'bg-slate-50 text-slate-600 ring-slate-500/20';
}

function statusLabel(status) {
    const map = {
        active:  'Actif',
        expired: 'Expire',
        paused:  'En pause',
    };
    return map[status] ?? status;
}

function formatDate(dateString) {
    return new Date(dateString).toLocaleDateString('fr-FR', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
}

function computeEarnings(link) {
    const clicks = link.paid_clicks_count ?? 0;
    const cpc = link.campaign?.click_price ?? 0;
    return (clicks * cpc).toLocaleString('fr-FR');
}
</script>

<template>
    <Head title="Mes liens generes" />

    <InfluencerLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-bold leading-tight text-slate-800">
                    Mes liens generes
                </h2>
                <Link
                    :href="route('influencer.dashboard')"
                    class="inline-flex items-center gap-2 rounded-full bg-gradient-to-r from-teal-500 to-cyan-500 px-4 py-2 text-sm font-semibold text-white shadow-md shadow-teal-500/20 transition-all duration-300 hover:-translate-y-0.5 hover:shadow-lg hover:shadow-teal-500/30"
                >
                    <!-- Heroicon: bolt -->
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z" />
                    </svg>
                    Voir les campagnes
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8 space-y-6">

                <!-- Flash success -->
                <div
                    v-if="flashSuccess"
                    class="flex items-center gap-3 rounded-2xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0 text-green-500" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                    </svg>
                    <span>{{ flashSuccess }}</span>
                </div>

                <!-- Flash errors -->
                <div
                    v-if="Object.keys(flashErrors).length"
                    class="flex items-center gap-3 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0 text-red-500" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                    </svg>
                    <ul class="list-inside list-disc">
                        <li v-for="(msg, field) in flashErrors" :key="field">{{ msg }}</li>
                    </ul>
                </div>

                <!-- Anti-fraud info banner -->
                <div class="flex items-start gap-3 rounded-2xl border border-teal-200/60 bg-gradient-to-r from-teal-50 to-cyan-50 px-5 py-4 text-sm text-slate-600 shadow-sm">
                    <!-- Heroicon: ShieldExclamation -->
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0 text-teal-500 mt-0.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m0-10.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.75c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.623 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" />
                    </svg>
                    <p>Les clics provenant d'un pays non cible par le vendeur sont filtres par notre systeme anti-fraude et ne sont pas remuneres. Seuls les <strong class="text-teal-700">clics valides</strong> comptent dans vos gains.</p>
                </div>

                <!-- Empty state -->
                <div
                    v-if="!links.data.length"
                    class="rounded-2xl border-2 border-dashed border-teal-200 bg-gradient-to-br from-teal-50/50 to-cyan-50/30 p-16 text-center"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="mx-auto h-12 w-12 text-teal-300" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.19 8.688a4.5 4.5 0 011.242 7.244l-4.5 4.5a4.5 4.5 0 01-6.364-6.364l1.757-1.757m9.86-2.04a4.5 4.5 0 00-1.242-7.244l-4.5-4.5a4.5 4.5 0 00-6.364 6.364L4.343 8.28" />
                    </svg>
                    <h3 class="mt-4 text-sm font-semibold text-slate-900">Aucun lien genere</h3>
                    <p class="mt-1 text-sm text-slate-500">
                        Explorez les campagnes disponibles et generez votre premier lien unique.
                    </p>
                    <Link
                        :href="route('influencer.dashboard')"
                        class="mt-6 inline-flex items-center gap-2 rounded-full bg-gradient-to-r from-teal-500 to-cyan-500 px-6 py-3 text-sm font-semibold text-white shadow-lg shadow-teal-500/25 transition-all duration-500 hover:-translate-y-1 hover:shadow-xl hover:shadow-teal-500/30"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z" />
                        </svg>
                        Voir les campagnes
                    </Link>
                </div>

                <!-- ────────────────────────────────────────────
                     Grille de cartes — Mes SmartLinks
                ──────────────────────────────────────────── -->
                <div
                    v-else
                    class="grid grid-cols-1 gap-5 md:grid-cols-2 xl:grid-cols-3"
                >
                    <div
                        v-for="link in links.data"
                        :key="link.id"
                        class="group flex flex-col rounded-2xl border bg-white shadow-sm transition-all duration-500 hover:-translate-y-2 hover:shadow-xl"
                        :class="{
                            'border-teal-200/60 hover:shadow-teal-500/10': linkStatus(link) === 'active',
                            'border-red-200/60 hover:shadow-red-500/10': linkStatus(link) === 'expired',
                            'border-amber-200/60 hover:shadow-amber-500/10 opacity-75': linkStatus(link) === 'paused',
                        }"
                    >
                        <!-- En-tete : nom campagne + badge statut -->
                        <div class="flex items-start justify-between gap-3 px-5 pt-5 pb-3">
                            <div class="flex-1 min-w-0">
                                <h3 class="text-sm font-semibold text-slate-900 truncate">
                                    {{ link.campaign?.title ?? 'Campagne' }}
                                </h3>
                            </div>
                            <span
                                :class="statusBadgeClasses(linkStatus(link))"
                                class="inline-flex shrink-0 items-center rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 ring-inset"
                            >
                                {{ statusLabel(linkStatus(link)) }}
                            </span>
                        </div>

                        <!-- Bloc URL avec bouton Copier -->
                        <div class="px-5 pb-3">
                            <div class="flex items-center gap-2 rounded-xl bg-gradient-to-r from-slate-50 to-teal-50/30 px-3 py-2.5 ring-1 ring-teal-100/50">
                                <!-- Heroicon: link -->
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0 text-teal-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.19 8.688a4.5 4.5 0 011.242 7.244l-4.5 4.5a4.5 4.5 0 01-6.364-6.364l1.757-1.757m9.86-2.04a4.5 4.5 0 00-1.242-7.244l-4.5-4.5a4.5 4.5 0 00-6.364 6.364L4.343 8.28" />
                                </svg>
                                <span class="flex-1 truncate text-xs font-mono text-slate-600">
                                    mantota.bj/go/{{ link.unique_hash }}
                                </span>
                                <button
                                    type="button"
                                    @click="copyLink(link)"
                                    class="inline-flex shrink-0 items-center gap-1 rounded-lg px-2 py-1 text-xs font-medium transition"
                                    :class="copiedLinkId === link.id
                                        ? 'bg-teal-100 text-teal-700'
                                        : 'bg-white text-slate-500 hover:bg-teal-50 hover:text-teal-600 shadow-sm border border-slate-200'"
                                >
                                    <!-- Heroicon: check (copied state) -->
                                    <svg v-if="copiedLinkId === link.id" xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                                    </svg>
                                    <!-- Heroicon: clipboard-document (copy state) -->
                                    <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.666 3.888A2.25 2.25 0 0013.5 2.25h-3c-1.03 0-1.9.693-2.166 1.638m7.332 0c.055.194.084.4.084.612v0a.75.75 0 01-.75.75H9.75a.75.75 0 01-.75-.75v0c0-.212.03-.418.084-.612m7.332 0c.646.049 1.288.11 1.927.184 1.1.128 1.907 1.077 1.907 2.185V19.5a2.25 2.25 0 01-2.25 2.25H6.75A2.25 2.25 0 014.5 19.5V6.257c0-1.108.806-2.057 1.907-2.185a48.208 48.208 0 011.927-.184" />
                                    </svg>
                                    {{ copiedLinkId === link.id ? 'Copie' : 'Copier' }}
                                </button>
                            </div>
                        </div>

                        <!-- Corps : Chronometre / statut -->
                        <div class="flex-1 px-5 pb-4 space-y-3">

                            <!-- Chronometre actif avec barre de progression -->
                            <div
                                v-if="linkStatus(link) === 'active' && countdowns[link.id]"
                                class="rounded-xl bg-gradient-to-br from-slate-50 to-amber-50/30 px-4 py-3 space-y-2 ring-1 ring-amber-100/50"
                            >
                                <div class="flex items-center gap-2">
                                    <!-- Heroicon: clock -->
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-amber-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <span
                                        class="text-lg font-bold tabular-nums"
                                        :class="isExpiringWithin12Hours(link.expires_at) ? 'text-red-600' : 'text-amber-700'"
                                    >
                                        {{ countdowns[link.id] }}
                                    </span>
                                    <span class="text-xs text-slate-400">restant</span>
                                </div>
                                <!-- Barre de progression -->
                                <div class="h-1.5 w-full rounded-full bg-slate-200 overflow-hidden">
                                    <div
                                        class="h-full rounded-full transition-all duration-1000"
                                        :class="progressBarColor(link.expires_at)"
                                        :style="{ width: countdownProgressPercent(link.expires_at) + '%' }"
                                    />
                                </div>
                                <p
                                    class="text-xs"
                                    :class="isExpiringWithin12Hours(link.expires_at) ? 'text-red-500 font-medium' : 'text-slate-400'"
                                >
                                    Expire le {{ formatDate(link.expires_at) }}
                                </p>
                            </div>

                            <!-- Lien expire -->
                            <div
                                v-if="linkStatus(link) === 'expired'"
                                class="rounded-xl bg-red-50 px-4 py-3"
                            >
                                <div class="flex items-center gap-2 text-xs font-medium text-red-600">
                                    <!-- Heroicon: exclamation-triangle -->
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                                    </svg>
                                    Delai de 48h depasse -- Lien expire
                                </div>
                            </div>

                            <!-- Campagne en pause -->
                            <div
                                v-if="linkStatus(link) === 'paused'"
                                class="rounded-xl bg-amber-50 px-4 py-3 ring-1 ring-amber-200/60"
                            >
                                <div class="flex items-center gap-2 text-xs font-medium text-amber-700">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25v13.5m-7.5-13.5v13.5" />
                                    </svg>
                                    Campagne suspendue par le vendeur -- Le lien ne genere plus de gains temporairement.
                                </div>
                            </div>
                        </div>

                        <!-- Stats : Clics + Gains -->
                        <div class="border-t border-slate-100/80 bg-gradient-to-r from-slate-50/50 to-teal-50/20 px-5 py-3">
                            <div class="grid grid-cols-4 gap-2 text-center">
                                <div>
                                    <div class="flex items-center justify-center gap-1 text-slate-400">
                                        <!-- Heroicon: cursor-arrow-rays -->
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.042 21.672L13.684 16.6m0 0l-2.51 2.225.569-9.47 5.227 7.917-3.286-.672zM12 2.25V4.5m5.834.166l-1.591 1.591M20.25 10.5H18M7.757 14.743l-1.59 1.59M6 10.5H3.75m4.007-4.243l-1.59-1.59" />
                                        </svg>
                                    </div>
                                    <p class="mt-0.5 text-sm font-bold text-slate-800">{{ link.total_clicks_count ?? 0 }}</p>
                                    <p class="text-[10px] text-slate-400">Totaux</p>
                                </div>
                                <div>
                                    <div class="flex items-center justify-center gap-1 text-slate-400">
                                        <!-- Heroicon: shield-check -->
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" />
                                        </svg>
                                    </div>
                                    <p class="mt-0.5 text-sm font-bold text-teal-700">{{ link.paid_clicks_count ?? 0 }}</p>
                                    <p class="text-[10px] text-slate-400">Valides</p>
                                </div>
                                <div>
                                    <div class="flex items-center justify-center gap-1 text-slate-400">
                                        <!-- Heroicon: banknotes -->
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75M15 10.5a3 3 0 11-6 0 3 3 0 016 0zm3 0h.008v.008H18V10.5zm-12 0h.008v.008H6V10.5z" />
                                        </svg>
                                    </div>
                                    <p class="mt-0.5 text-sm font-bold text-teal-700">{{ computeEarnings(link) }} F</p>
                                    <p class="text-[10px] text-slate-400">Gains</p>
                                </div>
                                <div>
                                    <div class="flex items-center justify-center gap-1 text-slate-400">
                                        <!-- Heroicon: tag -->
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9.568 3H5.25A2.25 2.25 0 003 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 005.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 009.568 3z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 6h.008v.008H6V6z" />
                                        </svg>
                                    </div>
                                    <p class="mt-0.5 text-sm font-bold text-purple-700">{{ link.campaign?.click_price ?? 0 }} F</p>
                                    <p class="text-[10px] text-slate-400">CPC</p>
                                </div>
                            </div>
                        </div>

                        <!-- Consignes + Media -->
                        <div v-if="link.campaign?.instructions || link.campaign?.media_path" class="border-t border-slate-100/80 px-5 py-3 space-y-2">
                            <!-- Consignes du vendeur -->
                            <div v-if="link.campaign?.instructions" class="rounded-xl bg-gradient-to-br from-amber-50 to-yellow-50 px-3 py-2.5 ring-1 ring-amber-200/60">
                                <div class="flex items-center gap-1.5 mb-1">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-amber-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                                    </svg>
                                    <p class="text-[10px] font-semibold text-amber-700 uppercase tracking-wide">Consignes</p>
                                </div>
                                <p class="text-xs text-amber-900 leading-relaxed whitespace-pre-line">{{ link.campaign.instructions }}</p>
                            </div>
                            <!-- Telecharger le media -->
                            <a
                                v-if="link.campaign?.media_path"
                                :href="`/storage/${link.campaign.media_path}`"
                                :download="link.campaign.media_path.split('/').pop()"
                                class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-indigo-50 to-purple-50 px-3 py-2.5 text-xs font-semibold text-indigo-700 ring-1 ring-indigo-200/60 transition hover:from-indigo-100 hover:to-purple-100"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
                                </svg>
                                Telecharger le media ({{ link.campaign.media_type === 'video' ? 'Video' : 'Image' }})
                            </a>
                        </div>
                    </div>
                </div>

                <!-- ────────────────────────────────────────────
                     Pagination
                ──────────────────────────────────────────── -->
                <div
                    v-if="links.data.length && links.last_page > 1"
                    class="flex items-center justify-between"
                >
                    <p class="text-sm text-slate-500">
                        Page {{ links.current_page }} sur {{ links.last_page }}
                    </p>
                    <div class="flex gap-2">
                        <Link
                            v-if="links.prev_page_url"
                            :href="links.prev_page_url"
                            class="inline-flex items-center gap-1 rounded-full border border-teal-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 shadow-sm transition-all duration-300 hover:bg-teal-50 hover:border-teal-300 hover:-translate-y-0.5"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" />
                            </svg>
                            Precedent
                        </Link>
                        <Link
                            v-if="links.next_page_url"
                            :href="links.next_page_url"
                            class="inline-flex items-center gap-1 rounded-full border border-teal-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 shadow-sm transition-all duration-300 hover:bg-teal-50 hover:border-teal-300 hover:-translate-y-0.5"
                        >
                            Suivant
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                            </svg>
                        </Link>
                    </div>
                </div>

            </div>
        </div>
    </InfluencerLayout>
</template>
