<script setup>
import { ref, watch } from 'vue';
import { router, Link } from '@inertiajs/vue3';
import Layout from '../Layout.vue';

defineOptions({ layout: Layout });

const props = defineProps({
    orders: Object,
    filters: { type: Object, default: () => ({}) },
});

const search = ref(props.filters.search ?? '');
const status = ref(props.filters.status ?? '');
const deliveryCompany = ref(props.filters.delivery_company ?? '');

function applyFilters() {
    router.get(route('admin.orders.index'), {
        search: search.value || undefined,
        status: status.value || undefined,
        delivery_company: deliveryCompany.value || undefined,
    }, { preserveState: true, replace: true });
}

let debounce;
watch([search], () => {
    clearTimeout(debounce);
    debounce = setTimeout(applyFilters, 350);
});

watch([status, deliveryCompany], () => applyFilters());

function formatCurrency(v) {
    if (!v) return '0 FCFA';
    return new Intl.NumberFormat('fr-FR', { style: 'decimal', minimumFractionDigits: 0 }).format(v) + ' FCFA';
}

function formatDate(d) {
    if (!d) return '\u2014';
    return new Date(d).toLocaleDateString('fr-FR', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' });
}

const statusBadge = {
    pending:   { label: 'En attente', cls: 'bg-amber-100 text-amber-700' },
    shipped:   { label: 'Expediee', cls: 'bg-blue-100 text-blue-700' },
    delivered: { label: 'Livree', cls: 'bg-emerald-100 text-emerald-700' },
    disputed:  { label: 'Litige', cls: 'bg-red-100 text-red-700' },
    cancelled: { label: 'Annulee', cls: 'bg-slate-200 text-slate-600' },
    disputed_resolved: { label: 'Litige resolu', cls: 'bg-teal-100 text-teal-700' },
};

function getBadge(s) {
    return statusBadge[s] ?? { label: s, cls: 'bg-slate-100 text-slate-600' };
}
</script>

<template>
    <div class="space-y-6">

        <!-- En-tete -->
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-slate-900">Toutes les Commandes</h1>
                <p class="mt-1 text-sm text-slate-500">Vision omnisciente de toutes les transactions e-commerce.</p>
            </div>
            <a
                :href="route('admin.orders.export')"
                class="inline-flex items-center gap-2 rounded-xl bg-slate-700 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-slate-800"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m.75 12l3 3m0 0l3-3m-3 3v-6m-1.5-9H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" /></svg>
                Exporter CSV
            </a>
        </div>

        <!-- Filtres -->
        <div class="flex flex-wrap items-center gap-3">
            <div class="relative flex-1 min-w-[220px] max-w-sm">
                <svg xmlns="http://www.w3.org/2000/svg" class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" /></svg>
                <input
                    v-model="search"
                    type="text"
                    placeholder="Rechercher par ref, client, vendeur, createur de contenu..."
                    class="w-full rounded-xl border border-slate-200 bg-white py-2.5 pl-10 pr-4 text-sm text-slate-900 placeholder-slate-400 shadow-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500"
                />
            </div>
            <select v-model="status" class="rounded-xl border border-slate-200 bg-white py-2.5 px-4 text-sm text-slate-700 shadow-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500">
                <option value="">Tous les statuts</option>
                <option value="pending">En attente</option>
                <option value="shipped">Expediee</option>
                <option value="delivered">Livree</option>
                <option value="disputed">Litige</option>
                <option value="cancelled">Annulee</option>
            </select>
            <select v-model="deliveryCompany" class="rounded-xl border border-slate-200 bg-white py-2.5 px-4 text-sm text-slate-700 shadow-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500">
                <option value="">Tous les livreurs</option>
                <option value="Gozem">Gozem</option>
                <option value="Yango">Yango</option>
                <option value="Rema">Rema</option>
                <option value="Kaba">Kaba</option>
                <option value="Autre">Autre</option>
            </select>
            <span class="ml-auto text-sm text-slate-500">{{ orders.total }} commande(s)</span>
        </div>

        <!-- Tableau -->
        <div class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-sm">
            <div v-if="orders.data.length === 0" class="px-6 py-16 text-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="mx-auto h-10 w-10 text-slate-300" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z" /></svg>
                <p class="mt-3 text-sm text-slate-500">Aucune commande trouvee.</p>
            </div>
            <div v-else class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-slate-100 bg-slate-50/50 text-left">
                            <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wider text-slate-500">Reference</th>
                            <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wider text-slate-500">Client</th>
                            <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wider text-slate-500">Vendeur</th>
                            <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wider text-slate-500">Createur de Contenu</th>
                            <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wider text-slate-500">Produit</th>
                            <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wider text-slate-500 text-right">Montant</th>
                            <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wider text-slate-500">Livreur</th>
                            <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wider text-slate-500">Statut</th>
                            <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wider text-slate-500">Date</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="o in orders.data" :key="o.id" class="transition hover:bg-slate-50/60">
                            <td class="whitespace-nowrap px-4 py-3 font-mono text-xs font-bold text-teal-700">{{ o.reference }}</td>
                            <td class="px-4 py-3">
                                <p class="text-sm font-medium text-slate-900 truncate max-w-[140px]">{{ o.customer_name || '\u2014' }}</p>
                                <p class="text-[11px] text-slate-400">{{ o.customer_phone || '' }}</p>
                            </td>
                            <td class="px-4 py-3">
                                <p class="text-sm text-slate-700 truncate max-w-[130px]">{{ o.vendor?.name || '\u2014' }}</p>
                            </td>
                            <td class="px-4 py-3">
                                <p class="text-sm text-slate-700 truncate max-w-[130px]">{{ o.influencer?.name || '\u2014' }}</p>
                            </td>
                            <td class="px-4 py-3">
                                <p class="text-sm text-slate-700 truncate max-w-[130px]">{{ o.product?.name || '\u2014' }}</p>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-right text-sm font-bold text-slate-900">{{ formatCurrency(o.amount_paid) }}</td>
                            <td class="whitespace-nowrap px-4 py-3">
                                <span v-if="o.delivery_company" class="inline-flex rounded-full bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-700">{{ o.delivery_company }}</span>
                                <span v-else class="text-xs text-slate-400">&mdash;</span>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3">
                                <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-semibold" :class="getBadge(o.status).cls">{{ getBadge(o.status).label }}</span>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-xs text-slate-500">{{ formatDate(o.created_at) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Pagination -->
        <div v-if="orders.last_page > 1" class="flex items-center justify-between rounded-2xl border border-slate-200/80 bg-white px-6 py-3 shadow-sm">
            <p class="text-xs text-slate-500">{{ orders.from }}-{{ orders.to }} sur {{ orders.total }}</p>
            <div class="flex gap-1">
                <Link
                    v-for="link in orders.links"
                    :key="link.label"
                    :href="link.url ?? '#'"
                    class="rounded-lg px-3 py-1.5 text-xs font-medium transition"
                    :class="link.active
                        ? 'bg-teal-600 text-white'
                        : link.url
                            ? 'text-slate-600 hover:bg-slate-100'
                            : 'pointer-events-none text-slate-300'"
                    v-html="link.label"
                />
            </div>
        </div>
    </div>
</template>
