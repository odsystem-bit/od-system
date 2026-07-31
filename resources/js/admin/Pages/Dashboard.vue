<script setup>
import { computed } from 'vue';
import { router } from '@inertiajs/vue3';
import Layout from './Layout.vue';

defineOptions({ layout: Layout });

const props = defineProps({
    totalUsers: { type: Number, default: 0 },
    totalVendors: { type: Number, default: 0 },
    totalInfluencers: { type: Number, default: 0 },
    totalDeposits: { type: Number, default: 0 },
    totalWithdrawals: { type: Number, default: 0 },
    totalEscrow: { type: Number, default: 0 },
    mantotaProfits: { type: Number, default: 0 },
    sparkDeposits: { type: Array, default: () => [] },
    sparkVendors: { type: Array, default: () => [] },
    sparkInfluencers: { type: Array, default: () => [] },
    countryStats: { type: Array, default: () => [] },
    pendingKycCount: { type: Number, default: 0 },
    pendingVipCount: { type: Number, default: 0 },
    pendingWithdrawalCount: { type: Number, default: 0 },
    flaggedMessagesCount: { type: Number, default: 0 },
    latestPendingKyc: { type: Array, default: () => [] },
    latestPendingWithdrawals: { type: Array, default: () => [] },
    latestPendingVip: { type: Array, default: () => [] },
    deliveryStats: { type: Array, default: () => [] },
    totalShippedOrders: { type: Number, default: 0 },
    revenueMonths: { type: Array, default: () => [] },
    yearlyTotal: { type: Number, default: 0 },
    currentYear: { type: Number, default: new Date().getFullYear() },
    ambassadors: { type: Array, default: () => [] },
});

function formatCurrency(value) {
    return new Intl.NumberFormat('fr-FR', { style: 'decimal', minimumFractionDigits: 0 }).format(Number(value ?? 0)) + ' FCFA';
}

function formatDate(dateString) {
    if (!dateString) return '\u2014';
    return new Date(dateString).toLocaleDateString('fr-FR', { day: '2-digit', month: 'short', year: 'numeric' });
}

const platformRevenue = computed(() => props.totalDeposits - props.totalWithdrawals);

/* ── Sparkline SVG path builder ── */
function sparklinePath(data, width = 120, height = 32) {
    if (!data || data.length < 2) return '';
    const max = Math.max(...data, 1);
    const step = width / (data.length - 1);
    return data.map((v, i) => {
        const x = i * step;
        const y = height - (v / max) * height * 0.85 - 2;
        return `${i === 0 ? 'M' : 'L'}${x.toFixed(1)},${y.toFixed(1)}`;
    }).join(' ');
}

function sparklineArea(data, width = 120, height = 32) {
    const path = sparklinePath(data, width, height);
    if (!path) return '';
    const step = width / (data.length - 1);
    return `${path} L${((data.length - 1) * step).toFixed(1)},${height} L0,${height} Z`;
}

/* ── KPI Cards ── */
const kpiCards = computed(() => [
    {
        label: 'Profits MANTOTA',
        formatted: formatCurrency(props.mantotaProfits),
        sub: 'Commissions + markups percus',
        color: 'teal',
        icon: 'chart-bar',
        spark: [],
    },
    {
        label: 'Volume Escrow',
        formatted: formatCurrency(props.totalEscrow),
        sub: 'Fonds en sequestre actifs',
        color: 'violet',
        icon: 'lock-closed',
        spark: [],
    },
    {
        label: 'Nouveaux Vendeurs',
        formatted: props.totalVendors.toLocaleString('fr-FR'),
        sub: 'Total inscrits',
        color: 'emerald',
        icon: 'building-storefront',
        spark: props.sparkVendors,
    },
    {
        label: 'Nouveaux Createurs de Contenu',
        formatted: props.totalInfluencers.toLocaleString('fr-FR'),
        sub: 'Total inscrits',
        color: 'purple',
        icon: 'megaphone',
        spark: props.sparkInfluencers,
    },
]);

/* ── Alert badges ── */
const alertBadges = computed(() => [
    { label: 'KYC en attente', count: props.pendingKycCount, color: 'amber' },
    { label: 'Demandes VIP', count: props.pendingVipCount, color: 'purple' },
    { label: 'Retraits en attente', count: props.pendingWithdrawalCount, color: 'red' },
    { label: 'Messages suspects', count: props.flaggedMessagesCount, color: 'orange' },
]);

/* ── Country chart ── */
const maxCountryTotal = computed(() => {
    if (!props.countryStats.length) return 1;
    return Math.max(...props.countryStats.map(c => c.total), 1);
});

/* ── Delivery chart ── */
const maxDeliveryTotal = computed(() => {
    if (!props.deliveryStats.length) return 1;
    return Math.max(...props.deliveryStats.map(d => d.total), 1);
});

/* ── Revenue chart ── */
const maxRevenueTotal = computed(() => {
    if (!props.revenueMonths.length) return 1;
    return Math.max(...props.revenueMonths.map(m => m.total), 1);
});

const deliveryColors = {
    'Gozem': 'bg-blue-500',
    'Yango': 'bg-red-500',
    'Rema':  'bg-green-500',
    'Kaba':  'bg-amber-500',
    'Autre': 'bg-slate-400',
};

/* ── Actions ── */
function approveKyc(id)        { router.patch(route('admin.kyc.approve', id), {}, { preserveScroll: true }); }
function rejectKyc(id)         { router.patch(route('admin.kyc.reject', id), {}, { preserveScroll: true }); }
function approveVip(id)        { router.patch(route('admin.vip.approve', id), {}, { preserveScroll: true }); }
function rejectVip(id)         { router.patch(route('admin.vip.reject', id), {}, { preserveScroll: true }); }
function approveWithdrawal(id) { router.patch(route('admin.withdrawal.approve', id), {}, { preserveScroll: true }); }
function rejectWithdrawal(id)  { router.patch(route('admin.withdrawal.reject', id), {}, { preserveScroll: true }); }

function getSocialSummary(user) {
    const nets = [];
    if (user.tiktok_url) nets.push('TikTok (' + (user.tiktok_followers || 0).toLocaleString('fr-FR') + ')');
    if (user.instagram_url) nets.push('Instagram (' + (user.instagram_followers || 0).toLocaleString('fr-FR') + ')');
    if (user.facebook_url) nets.push('Facebook (' + (user.facebook_followers || 0).toLocaleString('fr-FR') + ')');
    if (user.youtube_url) nets.push('YouTube (' + (user.youtube_followers || 0).toLocaleString('fr-FR') + ')');
    if (user.snapchat_url) nets.push('Snapchat (' + (user.snapchat_followers || 0).toLocaleString('fr-FR') + ')');
    return nets.join(', ') || '\u2014';
}
</script>

<template>
    <div class="space-y-8">

        <!-- ═══════ Page Header ═══════ -->
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">Tableau de bord</h1>
            <p class="mt-1 text-sm text-slate-500">Vue d'ensemble de la plateforme MANTOTA.</p>
        </div>

        <!-- ═══════ KPI Cards with Sparklines ═══════ -->
        <div class="grid gap-5 sm:grid-cols-2 xl:grid-cols-4">
            <div
                v-for="card in kpiCards"
                :key="card.label"
                class="relative overflow-hidden rounded-2xl border border-slate-200/80 bg-white p-6 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:shadow-md"
            >
                <div class="flex items-start justify-between">
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-2">
                            <div
                                class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl"
                                :class="{
                                    'bg-teal-50 text-teal-600': card.color === 'teal',
                                    'bg-violet-50 text-violet-600': card.color === 'violet',
                                    'bg-emerald-50 text-emerald-600': card.color === 'emerald',
                                    'bg-purple-50 text-purple-600': card.color === 'purple',
                                }"
                            >
                                <!-- chart-bar -->
                                <svg v-if="card.icon === 'chart-bar'" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" />
                                </svg>
                                <!-- lock-closed -->
                                <svg v-else-if="card.icon === 'lock-closed'" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
                                </svg>
                                <!-- building-storefront -->
                                <svg v-else-if="card.icon === 'building-storefront'" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 21v-7.5a.75.75 0 01.75-.75h3a.75.75 0 01.75.75V21m-4.5 0H2.36m11.14 0H18m0 0h3.64m-1.39 0V9.349m-16.5 11.65V9.35m0 0a3.001 3.001 0 003.75-.615A2.993 2.993 0 009.75 9.75c.896 0 1.7-.393 2.25-1.016a2.993 2.993 0 002.25 1.016c.896 0 1.7-.393 2.25-1.016a3.001 3.001 0 003.75.614m-16.5 0a3.004 3.004 0 01-.621-4.72L4.318 3.44A1.5 1.5 0 015.378 3h13.243a1.5 1.5 0 011.06.44l1.19 1.189a3 3 0 01-.621 4.72m-13.5 8.65h3.75a.75.75 0 00.75-.75V13.5a.75.75 0 00-.75-.75H6.75a.75.75 0 00-.75.75v3.15c0 .415.336.75.75.75z" />
                                </svg>
                                <!-- megaphone -->
                                <svg v-else-if="card.icon === 'megaphone'" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.34 15.84c-.688-.06-1.386-.09-2.09-.09H7.5a4.5 4.5 0 110-9h.75c.704 0 1.402-.03 2.09-.09m0 9.18c.253.962.584 1.892.985 2.783.247.55.06 1.21-.463 1.511l-.657.38c-.551.318-1.26.117-1.527-.461a20.845 20.845 0 01-1.44-4.282m3.102.069a18.03 18.03 0 01-.59-4.59c0-1.586.205-3.124.59-4.59m0 9.18a23.848 23.848 0 018.835 2.535M10.34 6.66a23.847 23.847 0 008.835-2.535m0 0A23.74 23.74 0 0018.795 3m.38 1.125a23.91 23.91 0 011.014 5.395m-1.014 8.855c-.118.38-.245.754-.38 1.125m.38-1.125a23.91 23.91 0 001.014-5.395m0-3.46c.495.413.811 1.035.811 1.73 0 .695-.316 1.317-.811 1.73m0-3.46a24.347 24.347 0 010 3.46" />
                                </svg>
                            </div>
                            <p class="text-sm font-medium text-slate-500">{{ card.label }}</p>
                        </div>
                        <p class="mt-3 text-2xl font-bold tracking-tight text-slate-900">{{ card.formatted }}</p>
                        <p class="mt-0.5 text-xs text-slate-400">{{ card.sub }}</p>
                    </div>
                    <!-- Sparkline -->
                    <div v-if="card.spark && card.spark.length > 1" class="shrink-0 ml-3 mt-2">
                        <svg width="120" height="36" class="overflow-visible">
                            <defs>
                                <linearGradient :id="'grad-' + card.color" x1="0" y1="0" x2="0" y2="1">
                                    <stop offset="0%" :stop-color="card.color === 'teal' ? '#14b8a6' : card.color === 'violet' ? '#8b5cf6' : card.color === 'emerald' ? '#10b981' : '#a855f7'" stop-opacity="0.3" />
                                    <stop offset="100%" :stop-color="card.color === 'teal' ? '#14b8a6' : card.color === 'violet' ? '#8b5cf6' : card.color === 'emerald' ? '#10b981' : '#a855f7'" stop-opacity="0" />
                                </linearGradient>
                            </defs>
                            <path :d="sparklineArea(card.spark)" :fill="`url(#grad-${card.color})`" />
                            <path
                                :d="sparklinePath(card.spark)"
                                fill="none"
                                :stroke="card.color === 'teal' ? '#14b8a6' : card.color === 'violet' ? '#8b5cf6' : card.color === 'emerald' ? '#10b981' : '#a855f7'"
                                stroke-width="2"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                            />
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        <!-- ═══════ Alert Badges ═══════ -->

        <!-- ═══════ Revenus MANTOTA (Mensuel) ═══════ -->
        <section class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-sm">
            <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4">
                <div class="flex items-center gap-3">
                    <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-teal-50">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-teal-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-base font-semibold text-slate-900">Revenus MANTOTA</h2>
                        <p class="text-xs text-slate-500">Detail des gains de la plateforme par mois.</p>
                    </div>
                </div>
                <span class="rounded-full bg-teal-100 px-2.5 py-1 text-xs font-semibold text-teal-700">{{ currentYear }} : {{ formatCurrency(yearlyTotal) }}</span>
            </div>

            <div v-if="revenueMonths.length === 0" class="px-6 py-12 text-center">
                <p class="text-sm text-slate-500">Aucune donnee de revenus disponible.</p>
            </div>

            <div v-else class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-slate-100 bg-slate-50/50 text-left">
                            <th class="px-6 py-3 text-xs font-semibold uppercase tracking-wider text-slate-500">Mois</th>
                            <th class="px-6 py-3 text-xs font-semibold uppercase tracking-wider text-slate-500">Markups depots</th>
                            <th class="px-6 py-3 text-xs font-semibold uppercase tracking-wider text-slate-500">Comm. retraits</th>
                            <th class="px-6 py-3 text-xs font-semibold uppercase tracking-wider text-slate-500">Comm. commandes</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-500">Total</th>
                            <th class="px-6 py-3 text-xs font-semibold uppercase tracking-wider text-slate-500" style="min-width: 140px;"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="m in revenueMonths" :key="m.month" class="transition hover:bg-slate-50/60">
                            <td class="px-6 py-3.5 font-medium text-slate-900 capitalize">{{ m.label }}</td>
                            <td class="px-6 py-3.5 text-slate-600">{{ formatCurrency(m.markups) }}</td>
                            <td class="px-6 py-3.5 text-slate-600">{{ formatCurrency(m.withdrawal_commissions) }}</td>
                            <td class="px-6 py-3.5 text-slate-600">{{ formatCurrency(m.order_platform_fees) }}</td>
                            <td class="px-6 py-3.5 text-right font-semibold text-teal-700">{{ formatCurrency(m.total) }}</td>
                            <td class="px-6 py-3.5">
                                <div class="h-5 w-full bg-slate-100 rounded-full overflow-hidden">
                                    <div
                                        class="h-full bg-teal-500 rounded-full transition-all"
                                        :style="{ width: ((m.total / maxRevenueTotal) * 100).toFixed(1) + '%' }"
                                    />
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

        <!-- ═══════ Alert Badges (suite) ═══════ -->
        <div class="flex flex-wrap gap-3">
            <div
                v-for="badge in alertBadges"
                :key="badge.label"
                class="inline-flex items-center gap-2 rounded-full px-4 py-2 text-sm font-medium"
                :class="{
                    'bg-amber-100 text-amber-800': badge.color === 'amber',
                    'bg-purple-100 text-purple-800': badge.color === 'purple',
                    'bg-red-100 text-red-800': badge.color === 'red',
                }"
            >
                <span
                    class="flex h-5 w-5 items-center justify-center rounded-full text-xs font-bold text-white"
                    :class="{
                        'bg-amber-500': badge.color === 'amber',
                        'bg-purple-500': badge.color === 'purple',
                        'bg-red-500': badge.color === 'red',
                    }"
                >{{ badge.count }}</span>
                {{ badge.label }}
            </div>
        </div>

        <!-- ═══════ Geographic Distribution ═══════ -->
        <section class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-sm">
            <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4">
                <div class="flex items-center gap-3">
                    <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-teal-50">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-teal-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9.004 9.004 0 008.716-6.747M12 21a9.004 9.004 0 01-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 017.843 4.582M12 3a8.997 8.997 0 00-7.843 4.582m15.686 0A11.953 11.953 0 0112 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0121 12c0 .778-.099 1.533-.284 2.253m0 0A17.919 17.919 0 0112 16.5c-3.162 0-6.133-.815-8.716-2.247m0 0A9.015 9.015 0 013 12c0-1.605.42-3.113 1.157-4.418" />
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-base font-semibold text-slate-900">Repartition geographique</h2>
                        <p class="text-xs text-slate-500">Top 10 des pays par nombre d'utilisateurs.</p>
                    </div>
                </div>
            </div>

            <div v-if="countryStats.length === 0" class="px-6 py-12 text-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="mx-auto h-10 w-10 text-slate-300" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9.004 9.004 0 008.716-6.747M12 21a9.004 9.004 0 01-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 017.843 4.582M12 3a8.997 8.997 0 00-7.843 4.582m15.686 0A11.953 11.953 0 0112 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0121 12c0 .778-.099 1.533-.284 2.253m0 0A17.919 17.919 0 0112 16.5c-3.162 0-6.133-.815-8.716-2.247m0 0A9.015 9.015 0 013 12c0-1.605.42-3.113 1.157-4.418" />
                </svg>
                <p class="mt-2 text-sm text-slate-500">Aucune donnee geographique disponible.</p>
            </div>

            <div v-else class="px-6 py-5 space-y-3">
                <div v-for="cs in countryStats" :key="cs.country" class="flex items-center gap-4">
                    <span class="w-28 shrink-0 truncate text-sm font-medium text-slate-700">{{ cs.country }}</span>
                    <div class="flex-1 flex items-center gap-2">
                        <div class="flex-1 h-6 bg-slate-100 rounded-full overflow-hidden flex">
                            <div
                                class="h-full bg-teal-500 rounded-l-full transition-all"
                                :style="{ width: ((cs.vendors / maxCountryTotal) * 100).toFixed(1) + '%' }"
                            />
                            <div
                                class="h-full bg-purple-500 transition-all"
                                :style="{ width: ((cs.influencers / maxCountryTotal) * 100).toFixed(1) + '%' }"
                            />
                        </div>
                        <span class="w-12 shrink-0 text-right text-xs font-semibold text-slate-600">{{ cs.total }}</span>
                    </div>
                </div>
                <div class="flex items-center gap-4 pt-2 text-xs text-slate-500">
                    <span class="flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-full bg-teal-500"></span> Vendeurs</span>
                    <span class="flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-full bg-purple-500"></span> Createurs de Contenu</span>
                </div>
            </div>
        </section>

        <!-- ═══════ Rapport Livraison par service ═══════ -->
        <section class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-sm">
            <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4">
                <div class="flex items-center gap-3">
                    <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-blue-50">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-blue-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 01-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 00-3.213-9.193 2.056 2.056 0 00-1.58-.86H14.25M16.5 18.75h-2.25m0-11.177v-.958c0-.568-.422-1.048-.987-1.106a48.554 48.554 0 00-10.026 0 1.106 1.106 0 00-.987 1.106v7.635m12-6.677v6.677m0 4.5v-4.5m0 0h-12" />
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-base font-semibold text-slate-900">Rapport Livraison</h2>
                        <p class="text-xs text-slate-500">Volume de commandes par service de livraison.</p>
                    </div>
                </div>
                <span class="rounded-full bg-blue-100 px-2.5 py-1 text-xs font-semibold text-blue-700">{{ totalShippedOrders }} expediees</span>
            </div>

            <div v-if="deliveryStats.length === 0" class="px-6 py-12 text-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="mx-auto h-10 w-10 text-slate-300" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 01-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 00-3.213-9.193 2.056 2.056 0 00-1.58-.86H14.25M16.5 18.75h-2.25m0-11.177v-.958c0-.568-.422-1.048-.987-1.106a48.554 48.554 0 00-10.026 0 1.106 1.106 0 00-.987 1.106v7.635m12-6.677v6.677m0 4.5v-4.5m0 0h-12" />
                </svg>
                <p class="mt-2 text-sm text-slate-500">Aucune commande expediee pour le moment.</p>
            </div>

            <div v-else class="px-6 py-5 space-y-3">
                <div v-for="ds in deliveryStats" :key="ds.delivery_company" class="flex items-center gap-4">
                    <span class="w-20 shrink-0 truncate text-sm font-medium text-slate-700">{{ ds.delivery_company }}</span>
                    <div class="flex-1 flex items-center gap-2">
                        <div class="flex-1 h-6 bg-slate-100 rounded-full overflow-hidden">
                            <div
                                class="h-full rounded-full transition-all"
                                :class="deliveryColors[ds.delivery_company] || 'bg-slate-400'"
                                :style="{ width: ((ds.total / maxDeliveryTotal) * 100).toFixed(1) + '%' }"
                            />
                        </div>
                        <span class="w-16 shrink-0 text-right text-xs font-semibold text-slate-600">{{ ds.total }} cmd{{ ds.total > 1 ? 's' : '' }}</span>
                    </div>
                </div>
            </div>
        </section>

        <!-- ═══════ KYC en attente ═══════ -->
        <section id="kyc" class="scroll-mt-6 overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-sm">
            <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4">
                <div class="flex items-center gap-3">
                    <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-amber-50">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-amber-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 9h3.75M15 12h3.75M15 15h3.75M4.5 19.5h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5zm6-10.125a1.875 1.875 0 11-3.75 0 1.875 1.875 0 013.75 0zm1.294 6.336a6.721 6.721 0 01-3.17.789 6.721 6.721 0 01-3.168-.789 3.376 3.376 0 016.338 0z" />
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-base font-semibold text-slate-900">Verifications KYC en attente</h2>
                        <p class="text-xs text-slate-500">Dossiers d'identite soumis par les utilisateurs.</p>
                    </div>
                </div>
                <span class="rounded-full bg-amber-100 px-2.5 py-1 text-xs font-semibold text-amber-700">{{ pendingKycCount }}</span>
            </div>

            <div v-if="latestPendingKyc.length === 0" class="px-6 py-12 text-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="mx-auto h-10 w-10 text-slate-300" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <p class="mt-2 text-sm text-slate-500">Aucun dossier KYC en attente.</p>
            </div>

            <div v-else class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-slate-100 bg-slate-50/50 text-left">
                            <th class="px-6 py-3 text-xs font-semibold uppercase tracking-wider text-slate-500">Utilisateur</th>
                            <th class="px-6 py-3 text-xs font-semibold uppercase tracking-wider text-slate-500">Role</th>
                            <th class="px-6 py-3 text-xs font-semibold uppercase tracking-wider text-slate-500">Pays</th>
                            <th class="px-6 py-3 text-xs font-semibold uppercase tracking-wider text-slate-500">Date</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-500">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="u in latestPendingKyc" :key="u.id" class="transition hover:bg-slate-50/60">
                            <td class="px-6 py-3.5">
                                <p class="font-medium text-slate-900">{{ u.name }}</p>
                                <p class="text-xs text-slate-400">{{ u.email }}</p>
                            </td>
                            <td class="px-6 py-3.5">
                                <span
                                    class="inline-flex rounded-full px-2 py-0.5 text-xs font-semibold"
                                    :class="u.role === 'vendor' ? 'bg-blue-100 text-blue-700' : 'bg-purple-100 text-purple-700'"
                                >{{ u.role === 'vendor' ? 'Vendeur' : 'Createur de Contenu' }}</span>
                            </td>
                            <td class="px-6 py-3.5 text-slate-600">{{ u.country || '\u2014' }}</td>
                            <td class="px-6 py-3.5 text-slate-500">{{ formatDate(u.created_at) }}</td>
                            <td class="px-6 py-3.5 text-right">
                                <div class="inline-flex gap-2">
                                    <button @click="approveKyc(u.id)" class="inline-flex items-center gap-1 rounded-lg bg-emerald-50 px-3 py-1.5 text-xs font-semibold text-emerald-700 transition hover:bg-emerald-100">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                                        Approuver
                                    </button>
                                    <button @click="rejectKyc(u.id)" class="inline-flex items-center gap-1 rounded-lg bg-red-50 px-3 py-1.5 text-xs font-semibold text-red-700 transition hover:bg-red-100">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                                        Rejeter
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

        <!-- ═══════ Demandes VIP ═══════ -->
        <section id="vip" class="scroll-mt-6 overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-sm">
            <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4">
                <div class="flex items-center gap-3">
                    <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-purple-50">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-purple-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M11.48 3.499a.562.562 0 011.04 0l2.125 5.111a.563.563 0 00.475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 00-.182.557l1.285 5.385a.562.562 0 01-.84.61l-4.725-2.885a.562.562 0 00-.586 0L6.982 20.54a.562.562 0 01-.84-.61l1.285-5.386a.562.562 0 00-.182-.557l-4.204-3.602a.562.562 0 01.321-.988l5.518-.442a.563.563 0 00.475-.345L11.48 3.5z" />
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-base font-semibold text-slate-900">Demandes VIP en attente</h2>
                        <p class="text-xs text-slate-500">Createurs de contenu ayant declare leurs reseaux sociaux.</p>
                    </div>
                </div>
                <span class="rounded-full bg-purple-100 px-2.5 py-1 text-xs font-semibold text-purple-700">{{ pendingVipCount }}</span>
            </div>

            <div v-if="latestPendingVip.length === 0" class="px-6 py-12 text-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="mx-auto h-10 w-10 text-slate-300" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M11.48 3.499a.562.562 0 011.04 0l2.125 5.111a.563.563 0 00.475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 00-.182.557l1.285 5.385a.562.562 0 01-.84.61l-4.725-2.885a.562.562 0 00-.586 0L6.982 20.54a.562.562 0 01-.84-.61l1.285-5.386a.562.562 0 00-.182-.557l-4.204-3.602a.562.562 0 01.321-.988l5.518-.442a.563.563 0 00.475-.345L11.48 3.5z" />
                </svg>
                <p class="mt-2 text-sm text-slate-500">Aucune demande VIP en attente.</p>
            </div>

            <div v-else class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-slate-100 bg-slate-50/50 text-left">
                            <th class="px-6 py-3 text-xs font-semibold uppercase tracking-wider text-slate-500">Createur de Contenu</th>
                            <th class="px-6 py-3 text-xs font-semibold uppercase tracking-wider text-slate-500">Reseaux sociaux</th>
                            <th class="px-6 py-3 text-xs font-semibold uppercase tracking-wider text-slate-500">Date</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-500">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="u in latestPendingVip" :key="u.id" class="transition hover:bg-slate-50/60">
                            <td class="px-6 py-3.5">
                                <p class="font-medium text-slate-900">{{ u.name }}</p>
                                <p class="text-xs text-slate-400">{{ u.email }}</p>
                            </td>
                            <td class="px-6 py-3.5">
                                <p class="max-w-sm text-xs text-slate-600">{{ getSocialSummary(u) }}</p>
                            </td>
                            <td class="px-6 py-3.5 text-slate-500">{{ formatDate(u.created_at) }}</td>
                            <td class="px-6 py-3.5 text-right">
                                <div class="inline-flex gap-2">
                                    <button @click="approveVip(u.id)" class="inline-flex items-center gap-1 rounded-lg bg-emerald-50 px-3 py-1.5 text-xs font-semibold text-emerald-700 transition hover:bg-emerald-100">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                                        Approuver
                                    </button>
                                    <button @click="rejectVip(u.id)" class="inline-flex items-center gap-1 rounded-lg bg-red-50 px-3 py-1.5 text-xs font-semibold text-red-700 transition hover:bg-red-100">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                                        Rejeter
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

        <!-- ═══════ Retraits en attente ═══════ -->
        <section id="withdrawals" class="scroll-mt-6 overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-sm">
            <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4">
                <div class="flex items-center gap-3">
                    <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-red-50">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-red-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75M15 10.5a3 3 0 11-6 0 3 3 0 016 0zm3 0h.008v.008H18V10.5zm-12 0h.008v.008H6V10.5z" />
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-base font-semibold text-slate-900">Retraits en attente</h2>
                        <p class="text-xs text-slate-500">Demandes de transfert mobile money a traiter manuellement.</p>
                    </div>
                </div>
                <span class="rounded-full bg-red-100 px-2.5 py-1 text-xs font-semibold text-red-700">{{ pendingWithdrawalCount }}</span>
            </div>

            <div v-if="latestPendingWithdrawals.length === 0" class="px-6 py-12 text-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="mx-auto h-10 w-10 text-slate-300" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <p class="mt-2 text-sm text-slate-500">Aucun retrait en attente.</p>
            </div>

            <div v-else class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-slate-100 bg-slate-50/50 text-left">
                            <th class="px-6 py-3 text-xs font-semibold uppercase tracking-wider text-slate-500">Createur de Contenu</th>
                            <th class="px-6 py-3 text-xs font-semibold uppercase tracking-wider text-slate-500">Montant demande</th>
                            <th class="px-6 py-3 text-xs font-semibold uppercase tracking-wider text-slate-500">Net a verser</th>
                            <th class="px-6 py-3 text-xs font-semibold uppercase tracking-wider text-slate-500">Reference</th>
                            <th class="px-6 py-3 text-xs font-semibold uppercase tracking-wider text-slate-500">Date</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-500">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="t in latestPendingWithdrawals" :key="t.id" class="transition hover:bg-slate-50/60">
                            <td class="px-6 py-3.5">
                                <p class="font-medium text-slate-900">{{ t.user?.name || '\u2014' }}</p>
                                <p class="text-xs text-slate-400">{{ t.user?.email || '\u2014' }}</p>
                            </td>
                            <td class="px-6 py-3.5 font-medium text-slate-900">{{ formatCurrency(t.amount_target) }}</td>
                            <td class="px-6 py-3.5 font-medium text-emerald-600">{{ formatCurrency(t.net_payout || (t.amount_target - (t.mantota_markup || 0) - (t.gateway_fee || 0))) }}</td>
                            <td class="px-6 py-3.5 font-mono text-xs text-slate-500">{{ t.reference || '\u2014' }}</td>
                            <td class="px-6 py-3.5 text-slate-500">{{ formatDate(t.created_at) }}</td>
                            <td class="px-6 py-3.5 text-right">
                                <div class="inline-flex gap-2">
                                    <button @click="approveWithdrawal(t.id)" class="inline-flex items-center gap-1 rounded-lg bg-emerald-50 px-3 py-1.5 text-xs font-semibold text-emerald-700 transition hover:bg-emerald-100">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                                        Approuver
                                    </button>
                                    <button @click="rejectWithdrawal(t.id)" class="inline-flex items-center gap-1 rounded-lg bg-red-50 px-3 py-1.5 text-xs font-semibold text-red-700 transition hover:bg-red-100">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                                        Rejeter
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

        <!-- ══════ AMBASSADEURS CAROUSEL ══════ -->
        <section v-if="ambassadors && ambassadors.length" class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="flex items-center gap-3 border-b border-slate-100 px-6 py-4">
                <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-cyan-50">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-cyan-600" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="M8.603 3.799A4.49 4.49 0 0112 2.25c1.357 0 2.573.6 3.397 1.549a4.49 4.49 0 013.498 1.307 4.491 4.491 0 011.307 3.497A4.49 4.49 0 0121.75 12a4.49 4.49 0 01-1.549 3.397 4.491 4.491 0 01-1.307 3.497 4.491 4.491 0 01-3.497 1.307A4.49 4.49 0 0112 21.75a4.49 4.49 0 01-3.397-1.549 4.49 4.49 0 01-3.498-1.306 4.491 4.491 0 01-1.307-3.498A4.49 4.49 0 012.25 12c0-1.357.6-2.573 1.549-3.397a4.49 4.49 0 011.307-3.497 4.49 4.49 0 013.497-1.307zm7.007 6.387a.75.75 0 10-1.22-.872l-3.236 4.53L9.53 12.22a.75.75 0 00-1.06 1.06l2.25 2.25a.75.75 0 001.14-.094l3.75-5.25z" clip-rule="evenodd" /></svg>
                </div>
                <h3 class="text-sm font-semibold text-slate-900">Ambassadeurs MANTOTA</h3>
                <span class="ml-auto rounded-full bg-cyan-50 px-2.5 py-0.5 text-xs font-medium text-cyan-700">{{ ambassadors.length }}</span>
            </div>
            <div class="flex gap-4 overflow-x-auto px-6 py-4 scrollbar-thin scrollbar-thumb-slate-200">
                <div v-for="amb in ambassadors" :key="amb.id" class="flex shrink-0 items-center gap-3 rounded-xl border border-slate-100 bg-slate-50 px-4 py-3">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center overflow-hidden rounded-full bg-gradient-to-br from-cyan-500 to-teal-600 text-xs font-bold text-white">
                        <img v-if="amb.profile_photo" :src="'/storage/' + amb.profile_photo" :alt="amb.name" class="h-full w-full object-cover" />
                        <span v-else>{{ amb.name?.charAt(0)?.toUpperCase() }}</span>
                    </div>
                    <div class="min-w-0">
                        <p class="truncate text-sm font-medium text-slate-800">{{ amb.shop_name || amb.business_name || amb.name }}</p>
                        <div class="flex items-center gap-2">
                            <span class="flex items-center gap-1 text-xs text-cyan-600">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="M8.603 3.799A4.49 4.49 0 0112 2.25c1.357 0 2.573.6 3.397 1.549a4.49 4.49 0 013.498 1.307 4.491 4.491 0 011.307 3.497A4.49 4.49 0 0121.75 12a4.49 4.49 0 01-1.549 3.397 4.491 4.491 0 01-1.307 3.497 4.491 4.491 0 01-3.497 1.307A4.49 4.49 0 0112 21.75a4.49 4.49 0 01-3.397-1.549 4.49 4.49 0 01-3.498-1.306 4.491 4.491 0 01-1.307-3.498A4.49 4.49 0 012.25 12c0-1.357.6-2.573 1.549-3.397a4.49 4.49 0 011.307-3.497 4.49 4.49 0 013.497-1.307zm7.007 6.387a.75.75 0 10-1.22-.872l-3.236 4.53L9.53 12.22a.75.75 0 00-1.06 1.06l2.25 2.25a.75.75 0 001.14-.094l3.75-5.25z" clip-rule="evenodd" /></svg>
                                Ambassadeur
                            </span>
                            <span class="text-xs text-slate-400 capitalize">{{ amb.role === 'vendor' ? 'Vendeur' : 'Createur' }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </section>

    </div>
</template>
