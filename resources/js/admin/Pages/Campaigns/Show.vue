<script setup>
import AdminLayout from '../Layout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    campaign: { type: Object, required: true },
    paidClicks: { type: Number, default: 0 },
    totalClicks: { type: Number, default: 0 },
    influencerStats: { type: Array, default: () => [] },
    invalidReasons: { type: Array, default: () => [] },
    topCountries: { type: Array, default: () => [] },
    vpnBlocked: { type: Number, default: 0 },
    deviceDuplicates: { type: Number, default: 0 },
    botsBlocked: { type: Number, default: 0 },
    uniqueDevices: { type: Number, default: 0 },
    recentFraudClicks: { type: Array, default: () => [] },
});

const c = computed(() => props.campaign);

function formatCurrency(v) {
    return new Intl.NumberFormat('fr-FR', { maximumFractionDigits: 0 }).format(Number(v ?? 0)) + ' FCFA';
}

function formatNumber(v) {
    return Number(v ?? 0).toLocaleString('fr-FR');
}

function formatDate(d) {
    if (!d) return '—';
    return new Date(d).toLocaleDateString('fr-FR', { day: '2-digit', month: 'short', year: 'numeric' });
}

function togglePause() {
    router.post(route('admin.campaigns.toggle-pause', c.value.id));
}

function destroyCampaign() {
    if (confirm('Supprimer cette campagne systeme ? Le budget restant sera rembourse.')) {
        router.delete(route('admin.campaigns.destroy', c.value.id));
    }
}

const statusConfig = {
    active: 'bg-emerald-100 text-emerald-700',
    paused: 'bg-amber-100 text-amber-700',
    completed: 'bg-slate-100 text-slate-700',
    draft: 'bg-slate-100 text-slate-500',
};

const platformLabels = {
    tiktok: 'TikTok', facebook: 'Facebook', instagram: 'Instagram', youtube: 'YouTube', snapchat: 'Snapchat',
};
</script>

<template>
    <Head :title="'Campagne ' + (c?.title ?? '') + ' — Admin'" />
    <AdminLayout>
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <div class="flex items-center gap-3">
                        <h1 class="text-xl font-bold text-slate-900">{{ c?.title }}</h1>
                        <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-bold" :class="statusConfig[c?.status] || 'bg-slate-100 text-slate-600'">{{ c?.status }}</span>
                        <span v-if="c?.is_system_campaign" class="inline-flex rounded-full bg-purple-100 px-2.5 py-0.5 text-xs font-bold text-purple-700">Systeme</span>
                    </div>
                    <p class="mt-1 text-sm text-slate-500">Campagne admin — vue d'ensemble et performances.</p>
                </div>
                <div class="flex items-center gap-2">
                    <Link :href="route('admin.campaigns.edit', c?.id)"
                        class="inline-flex items-center gap-1.5 rounded-2xl border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-600 hover:bg-slate-50">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z" /></svg>
                        Modifier
                    </Link>
                    <button @click="togglePause"
                        class="inline-flex items-center gap-1.5 rounded-2xl border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-600 hover:bg-slate-50">
                        {{ c?.status === 'active' ? 'Pause' : 'Activer' }}
                    </button>
                    <button @click="destroyCampaign"
                        class="inline-flex items-center gap-1.5 rounded-2xl bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-700">
                        Supprimer
                    </button>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-xs font-medium text-slate-500">Clics payes</p>
                    <p class="text-2xl font-bold text-emerald-600 mt-1">{{ formatNumber(paidClicks) }}</p>
                </div>
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-xs font-medium text-slate-500">Clics totaux</p>
                    <p class="text-2xl font-bold text-slate-900 mt-1">{{ formatNumber(totalClicks) }}</p>
                </div>
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-xs font-medium text-slate-500">Budget total</p>
                    <p class="text-2xl font-bold text-slate-900 mt-1">{{ formatCurrency(c?.total_budget) }}</p>
                </div>
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-xs font-medium text-slate-500">Budget restant</p>
                    <p class="text-2xl font-bold text-purple-600 mt-1">{{ formatCurrency(c?.remaining_budget) }}</p>
                </div>
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-xs font-medium text-slate-500">CPC effectif</p>
                    <p class="text-2xl font-bold text-slate-900 mt-1">{{ formatCurrency(c?.effective_click_price) }}</p>
                </div>
            </div>

            <!-- Anti-fraud stats -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="rounded-2xl border border-red-200 bg-red-50 p-4">
                    <p class="text-xs font-medium text-red-600">VPN bloques</p>
                    <p class="text-xl font-bold text-red-700 mt-1">{{ vpnBlocked }}</p>
                </div>
                <div class="rounded-2xl border border-amber-200 bg-amber-50 p-4">
                    <p class="text-xs font-medium text-amber-600">Duplicatas appareil</p>
                    <p class="text-xl font-bold text-amber-700 mt-1">{{ deviceDuplicates }}</p>
                </div>
                <div class="rounded-2xl border border-red-200 bg-red-50 p-4">
                    <p class="text-xs font-medium text-red-600">Bots bloques</p>
                    <p class="text-xl font-bold text-red-700 mt-1">{{ botsBlocked }}</p>
                </div>
                <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                    <p class="text-xs font-medium text-slate-500">Appareils uniques</p>
                    <p class="text-xl font-bold text-slate-900 mt-1">{{ uniqueDevices }}</p>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Campaign Info -->
                <div class="rounded-2xl border border-slate-200 bg-white shadow-sm p-6 space-y-3">
                    <h3 class="text-sm font-bold text-slate-900 mb-2">Informations</h3>
                    <div class="flex justify-between text-sm"><span class="text-slate-500">Niche</span><span class="font-medium text-slate-900">{{ c?.niche ?? '—' }}</span></div>
                    <div class="flex justify-between text-sm"><span class="text-slate-500">Pays cibles</span><span class="font-medium text-slate-900">{{ (c?.target_country || []).join(', ') || '—' }}</span></div>
                    <div class="flex justify-between text-sm"><span class="text-slate-500">Plateformes</span><span class="font-medium text-slate-900">{{ (c?.platforms || []).map(p => platformLabels[p] || p).join(', ') || '—' }}</span></div>
                    <div class="flex justify-between text-sm"><span class="text-slate-500">Cree le</span><span class="font-medium text-slate-900">{{ formatDate(c?.created_at) }}</span></div>
                    <div v-if="c?.instructions" class="pt-2 border-t border-slate-100">
                        <p class="text-xs font-medium text-slate-500 mb-1">Consignes</p>
                        <p class="text-sm text-slate-700 whitespace-pre-wrap">{{ c.instructions }}</p>
                    </div>
                </div>

                <!-- Top Countries -->
                <div class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
                    <div class="border-b border-slate-200 px-6 py-4">
                        <h3 class="text-sm font-bold text-slate-900">Top pays (clics)</h3>
                    </div>
                    <div class="p-4 space-y-2">
                        <div v-for="country in topCountries" :key="country.clicker_country" class="flex items-center justify-between text-sm">
                            <span class="text-slate-700">{{ country.clicker_country }}</span>
                            <span class="font-semibold text-slate-900">{{ country.total }} clics</span>
                        </div>
                        <p v-if="!topCountries.length" class="text-sm text-slate-400">Aucune donnee.</p>
                    </div>
                </div>
            </div>

            <!-- Influencer Stats Table -->
            <div class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
                <div class="border-b border-slate-200 px-6 py-4">
                    <h3 class="text-sm font-bold text-slate-900">Performances par createur de contenu</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Createur</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold text-slate-500 uppercase">Clics payes</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold text-slate-500 uppercase">Clics totaux</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold text-slate-500 uppercase">Commandes</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold text-slate-500 uppercase">CPC gagne</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold text-slate-500 uppercase">CPA gagne</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold text-slate-500 uppercase">Total paye</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="stat in influencerStats" :key="stat.influencer_id" class="hover:bg-slate-50">
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-3">
                                        <img v-if="stat.influencer_photo" :src="'/storage/' + stat.influencer_photo" class="h-8 w-8 rounded-full object-cover" />
                                        <div v-else class="h-8 w-8 rounded-full bg-slate-200 flex items-center justify-center text-xs font-bold text-slate-500">{{ (stat.influencer_name || '?')[0] }}</div>
                                        <span class="text-sm font-medium text-slate-900">{{ stat.influencer_name }}</span>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-sm font-bold text-emerald-600">{{ formatNumber(stat.paid_clicks) }}</td>
                                <td class="px-4 py-3 text-sm text-slate-600">{{ formatNumber(stat.total_clicks) }}</td>
                                <td class="px-4 py-3 text-sm text-slate-600">{{ stat.orders_count }}</td>
                                <td class="px-4 py-3 text-sm text-slate-600">{{ formatCurrency(stat.cpc_earnings) }}</td>
                                <td class="px-4 py-3 text-sm text-slate-600">{{ formatCurrency(stat.cpa_earnings) }}</td>
                                <td class="px-4 py-3 text-sm font-bold text-slate-900">{{ formatCurrency(stat.total_paid) }}</td>
                            </tr>
                            <tr v-if="!influencerStats.length">
                                <td colspan="7" class="px-4 py-8 text-center text-sm text-slate-400">Aucun createur de contenu pour cette campagne.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Recent Fraud Clicks -->
            <div v-if="recentFraudClicks.length" class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
                <div class="border-b border-slate-200 px-6 py-4">
                    <h3 class="text-sm font-bold text-slate-900">Clics frauduleux recents</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">IP</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Pays</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Raison</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">VPN</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Date</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="click in recentFraudClicks" :key="click.id || click.ip_address + click.created_at" class="hover:bg-slate-50">
                                <td class="px-4 py-3 text-sm font-mono text-slate-600">{{ click.ip_address }}</td>
                                <td class="px-4 py-3 text-sm text-slate-600">{{ click.clicker_country ?? '—' }}</td>
                                <td class="px-4 py-3 text-sm"><span class="inline-flex rounded-full bg-red-100 px-2 py-0.5 text-xs text-red-700">{{ click.invalid_reason }}</span></td>
                                <td class="px-4 py-3 text-sm"><span v-if="click.is_vpn" class="text-red-600 font-bold">Oui</span><span v-else class="text-slate-400">Non</span></td>
                                <td class="px-4 py-3 text-sm text-slate-500">{{ formatDate(click.created_at) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
