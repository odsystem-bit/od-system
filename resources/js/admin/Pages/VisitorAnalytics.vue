<script setup>
import AdminLayout from './Layout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({
    range: { type: String, default: '7d' },
    totalViews: { type: Number, default: 0 },
    uniqueVisitors: { type: Number, default: 0 },
    avgTimeSpent: { type: Number, default: 0 },
    bounceRate: { type: Number, default: 0 },
    totalSessions: { type: Number, default: 0 },
    dailyTraffic: { type: Array, default: () => [] },
    hourlyTraffic: { type: Array, default: () => [] },
    topCountries: { type: Array, default: () => [] },
    topPages: { type: Array, default: () => [] },
    devices: { type: Array, default: () => [] },
    browsers: { type: Array, default: () => [] },
    topReferrers: { type: Array, default: () => [] },
    recentVisitors: { type: Array, default: () => [] },
    recommendations: { type: Array, default: () => [] },
});

const ranges = [
    { key: 'today', label: "Aujourd'hui" },
    { key: '7d', label: '7 jours' },
    { key: '30d', label: '30 jours' },
    { key: '90d', label: '90 jours' },
];

function changeRange(r) {
    router.get(route('admin.visitors.index'), { range: r }, { preserveScroll: true });
}

function formatTime(seconds) {
    if (!seconds || seconds === 0) return '—';
    if (seconds < 60) return seconds + 's';
    const m = Math.floor(seconds / 60);
    const s = seconds % 60;
    return `${m}m${s > 0 ? ' ' + s + 's' : ''}`;
}

// Simple SVG sparkline for daily traffic
const maxDailyViews = computed(() => Math.max(...(props.dailyTraffic || []).map(d => d.views), 1));
const chartWidth = 600;
const chartHeight = 120;

const dailyPath = computed(() => {
    const data = props.dailyTraffic || [];
    if (data.length < 2) return '';
    const step = chartWidth / (data.length - 1);
    return data.map((d, i) => {
        const x = i * step;
        const y = chartHeight - (d.views / maxDailyViews.value) * (chartHeight - 10) - 5;
        return `${i === 0 ? 'M' : 'L'} ${x.toFixed(1)} ${y.toFixed(1)}`;
    }).join(' ');
});

const deviceIcons = {
    mobile: '📱',
    desktop: '🖥️',
    tablet: '📋',
};
</script>

<template>
    <Head title="Analytics Visiteurs — Administration" />
    <AdminLayout>
        <div class="space-y-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-xl font-bold text-slate-900">Analytics Visiteurs</h1>
                    <p class="mt-1 text-sm text-slate-500">Statistiques de frequentation de la plateforme.</p>
                </div>
                <div class="flex gap-2">
                    <button v-for="r in ranges" :key="r.key" @click="changeRange(r.key)"
                        class="rounded-xl border-2 px-3 py-1.5 text-xs font-medium transition"
                        :class="range === r.key ? 'border-purple-500 bg-purple-50 text-purple-700' : 'border-slate-200 text-slate-600 hover:border-slate-300'">
                        {{ r.label }}
                    </button>
                </div>
            </div>

            <!-- KPI Cards -->
            <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-xs font-medium text-slate-500">Total vues</p>
                    <p class="text-2xl font-bold text-slate-900 mt-1">{{ totalViews.toLocaleString('fr-FR') }}</p>
                </div>
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-xs font-medium text-slate-500">Visiteurs uniques</p>
                    <p class="text-2xl font-bold text-purple-600 mt-1">{{ uniqueVisitors.toLocaleString('fr-FR') }}</p>
                </div>
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-xs font-medium text-slate-500">Sessions</p>
                    <p class="text-2xl font-bold text-slate-900 mt-1">{{ totalSessions.toLocaleString('fr-FR') }}</p>
                </div>
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-xs font-medium text-slate-500">Temps moyen</p>
                    <p class="text-2xl font-bold text-slate-900 mt-1">{{ formatTime(avgTimeSpent) }}</p>
                </div>
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-xs font-medium text-slate-500">Taux de rebond</p>
                    <p class="text-2xl font-bold mt-1" :class="bounceRate > 60 ? 'text-red-600' : 'text-emerald-600'">{{ bounceRate }}%</p>
                </div>
            </div>

            <!-- Recommendations -->
            <div v-if="recommendations.length" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div v-for="(rec, i) in recommendations" :key="i" class="flex items-start gap-3 rounded-2xl border border-amber-200 bg-amber-50 p-4">
                    <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-amber-100">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-amber-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 18v-5.25m0 0a6.01 6.01 0 001.5-.189m-1.5.189a6.01 6.01 0 01-1.5-.189m3.75 7.478a12.06 12.06 0 01-4.5 0m3.75 2.383a14.406 14.406 0 01-3 0M14.25 18v-.192c0-.983.658-1.823 1.508-2.192a11.25 11.25 0 00-3.75-2.192m-3.75 4.384c0 .983.658 1.823 1.508 2.192a11.25 11.25 0 003.75.384M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-amber-800">{{ rec.title }}</p>
                        <p class="text-xs text-amber-700 mt-0.5">{{ rec.desc }}</p>
                    </div>
                </div>
            </div>

            <!-- Daily Traffic Chart -->
            <div v-if="dailyTraffic.length" class="rounded-2xl border border-slate-200 bg-white shadow-sm p-6">
                <h3 class="text-sm font-bold text-slate-900 mb-4">Trafic quotidien</h3>
                <svg :viewBox="`0 0 ${chartWidth} ${chartHeight}`" class="w-full h-32">
                    <path :d="dailyPath" fill="none" stroke="rgb(168 85 247)" stroke-width="2" />
                </svg>
                <div class="flex justify-between mt-2 text-xs text-slate-400">
                    <span>{{ dailyTraffic[0]?.day }}</span>
                    <span>{{ dailyTraffic[dailyTraffic.length - 1]?.day }}</span>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Top Countries -->
                <div class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
                    <div class="border-b border-slate-200 px-6 py-4">
                        <h3 class="text-sm font-bold text-slate-900">Top pays</h3>
                    </div>
                    <div class="p-4 space-y-2">
                        <div v-for="country in topCountries" :key="country.country_code" class="flex items-center justify-between text-sm">
                            <span class="text-slate-700">{{ country.country ?? '—' }}</span>
                            <div class="flex items-center gap-3">
                                <span class="text-xs text-slate-400">{{ country.visitors }} visiteurs</span>
                                <span class="font-semibold text-slate-900">{{ country.views }} vues</span>
                            </div>
                        </div>
                        <p v-if="!topCountries.length" class="text-sm text-slate-400">Aucune donnee.</p>
                    </div>
                </div>

                <!-- Top Pages -->
                <div class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
                    <div class="border-b border-slate-200 px-6 py-4">
                        <h3 class="text-sm font-bold text-slate-900">Top pages</h3>
                    </div>
                    <div class="p-4 space-y-2">
                        <div v-for="page in topPages" :key="page.page_url" class="flex items-center justify-between text-sm">
                            <span class="text-slate-700 truncate max-w-[200px]">{{ page.page_url }}</span>
                            <div class="flex items-center gap-3">
                                <span class="text-xs text-slate-400">{{ page.visitors }} visiteurs</span>
                                <span class="font-semibold text-slate-900">{{ page.views }} vues</span>
                            </div>
                        </div>
                        <p v-if="!topPages.length" class="text-sm text-slate-400">Aucune donnee.</p>
                    </div>
                </div>

                <!-- Devices -->
                <div class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
                    <div class="border-b border-slate-200 px-6 py-4">
                        <h3 class="text-sm font-bold text-slate-900">Appareils</h3>
                    </div>
                    <div class="p-4 space-y-2">
                        <div v-for="device in devices" :key="device.device_type" class="flex items-center justify-between text-sm">
                            <span class="text-slate-700">{{ deviceIcons[device.device_type] || '💻' }} {{ device.device_type ?? 'Inconnu' }}</span>
                            <span class="font-semibold text-slate-900">{{ device.total }}</span>
                        </div>
                        <p v-if="!devices.length" class="text-sm text-slate-400">Aucune donnee.</p>
                    </div>
                </div>

                <!-- Browsers -->
                <div class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
                    <div class="border-b border-slate-200 px-6 py-4">
                        <h3 class="text-sm font-bold text-slate-900">Navigateurs</h3>
                    </div>
                    <div class="p-4 space-y-2">
                        <div v-for="browser in browsers" :key="browser.browser" class="flex items-center justify-between text-sm">
                            <span class="text-slate-700">{{ browser.browser ?? 'Inconnu' }}</span>
                            <span class="font-semibold text-slate-900">{{ browser.total }}</span>
                        </div>
                        <p v-if="!browsers.length" class="text-sm text-slate-400">Aucune donnee.</p>
                    </div>
                </div>
            </div>

            <!-- Recent Visitors -->
            <div class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
                <div class="border-b border-slate-200 px-6 py-4">
                    <h3 class="text-sm font-bold text-slate-900">Visiteurs recents</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Page</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Pays</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Appareil</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Utilisateur</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Quand</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="v in recentVisitors" :key="v.id" class="hover:bg-slate-50">
                                <td class="px-4 py-3 text-sm text-slate-600 truncate max-w-[200px]">{{ v.page_url }}</td>
                                <td class="px-4 py-3 text-sm text-slate-600">{{ v.country ?? '—' }}</td>
                                <td class="px-4 py-3 text-sm text-slate-600">{{ v.device_type ?? '—' }}</td>
                                <td class="px-4 py-3 text-sm text-slate-600">{{ v.user_name ?? 'Anonyme' }}</td>
                                <td class="px-4 py-3 text-sm text-slate-500">{{ v.created_at }}</td>
                            </tr>
                            <tr v-if="!recentVisitors.length">
                                <td colspan="5" class="px-4 py-8 text-center text-sm text-slate-400">Aucun visiteur.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
