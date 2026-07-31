<script setup>
import AdminLayout from '../Layout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    vendors: { type: Array, default: () => [] },
    year: { type: Number, default: new Date().getFullYear() },
    availableYears: { type: Array, default: () => [] },
});

const selectedYear = ref(props.year);

function changeYear() {
    router.get(route('admin.rankings.vendors'), { year: selectedYear.value }, { preserveScroll: true });
}

function formatCurrency(v) {
    return new Intl.NumberFormat('fr-FR').format(Number(v ?? 0)) + ' FCFA';
}

const medalColors = ['bg-yellow-100 text-yellow-700', 'bg-slate-200 text-slate-700', 'bg-amber-100 text-amber-700'];
</script>

<template>
    <Head title="Top 100 Vendeurs — Administration" />
    <AdminLayout>
        <div class="space-y-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-xl font-bold text-slate-900">Top 100 Vendeurs</h1>
                    <p class="mt-1 text-sm text-slate-500">Classement par chiffre d'affaires (vendor_earnings).</p>
                </div>
                <select v-model="selectedYear" @change="changeYear"
                    class="rounded-xl border-slate-300 text-sm">
                    <option v-for="y in availableYears" :key="y" :value="y">{{ y }}</option>
                </select>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">#</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Vendeur</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Pays</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold text-slate-500 uppercase">Revenu</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold text-slate-500 uppercase">Volume ventes</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold text-slate-500 uppercase">Commandes</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold text-slate-500 uppercase">Createurs</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold text-slate-500 uppercase">Campagnes</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="v in vendors" :key="v.id" class="hover:bg-slate-50">
                                <td class="px-4 py-3">
                                    <span v-if="v.rank <= 3" class="inline-flex h-7 w-7 items-center justify-center rounded-full text-xs font-bold" :class="medalColors[v.rank - 1]">{{ v.rank }}</span>
                                    <span v-else class="text-sm text-slate-400">{{ v.rank }}</span>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-3">
                                        <img v-if="v.profile_photo" :src="'/storage/' + v.profile_photo" class="h-8 w-8 rounded-full object-cover" />
                                        <div v-else class="h-8 w-8 rounded-full bg-slate-200 flex items-center justify-center text-xs font-bold text-slate-500">{{ (v.name || '?')[0] }}</div>
                                        <div>
                                            <p class="text-sm font-medium text-slate-900">{{ v.shop_name || v.business_name || v.name }}</p>
                                            <p v-if="v.is_ambassador" class="text-xs text-purple-600">Ambassadeur</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-sm text-slate-600">{{ v.country ?? '—' }}</td>
                                <td class="px-4 py-3 text-sm font-bold text-emerald-600">{{ formatCurrency(v.total_revenue) }}</td>
                                <td class="px-4 py-3 text-sm text-slate-600">{{ formatCurrency(v.total_sales_volume) }}</td>
                                <td class="px-4 py-3 text-sm font-semibold text-slate-900">{{ v.total_orders }}</td>
                                <td class="px-4 py-3 text-sm text-slate-600">{{ v.creators_worked_with }}</td>
                                <td class="px-4 py-3 text-sm text-slate-600">{{ v.total_campaigns }}</td>
                            </tr>
                            <tr v-if="!vendors.length">
                                <td colspan="8" class="px-4 py-8 text-center text-sm text-slate-400">Aucune donnee pour {{ year }}.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
