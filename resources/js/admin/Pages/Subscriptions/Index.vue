<script setup>
import AdminLayout from '../Layout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    subscribers: { type: Object, default: () => ({ data: [], links: [] }) },
    filter: { type: String, default: 'all' },
    search: { type: String, default: '' },
    stats: { type: Object, default: () => ({}) },
});

const searchInput = ref(props.search);

function applyFilter(f) {
    router.get(route('admin.subscriptions.index'), { filter: f, search: searchInput.value }, { preserveScroll: true });
}

function doSearch() {
    router.get(route('admin.subscriptions.index'), { filter: props.filter, search: searchInput.value }, { preserveScroll: true, preserveState: true });
}

const extendForm = useForm({ days: 30 });
const extendingId = ref(null);

function extend(user) {
    extendingId.value = user.id;
    extendForm.post(route('admin.subscriptions.extend', user.id), {
        preserveScroll: true,
        onSuccess: () => { extendingId.value = null; extendForm.reset(); },
        onError: () => { extendingId.value = null; },
    });
}

function revoke(user) {
    if (confirm(`Revoquer l'abonnement de ${user.name} ?`)) {
        router.post(route('admin.subscriptions.revoke', user.id));
    }
}

function formatDate(d) {
    if (!d) return '—';
    return new Date(d).toLocaleDateString('fr-FR', { day: '2-digit', month: 'short', year: 'numeric' });
}

function formatCurrency(v) {
    return new Intl.NumberFormat('fr-FR').format(Number(v ?? 0)) + ' FCFA';
}

const tierConfig = {
    bronze: 'bg-amber-100 text-amber-700',
    argent: 'bg-slate-200 text-slate-700',
    or: 'bg-yellow-100 text-yellow-700',
};
</script>

<template>
    <Head title="Abonnements Ambassadeur — Administration" />
    <AdminLayout>
        <div class="space-y-6">
            <div>
                <h1 class="text-xl font-bold text-slate-900">Abonnements Ambassadeur</h1>
                <p class="mt-1 text-sm text-slate-500">Gerer les abonnements au programme ambassadeur.</p>
            </div>

            <!-- Stats -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-xs font-medium text-slate-500">Abonnes actifs</p>
                    <p class="text-2xl font-bold text-emerald-600 mt-1">{{ stats.active ?? 0 }}</p>
                </div>
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-xs font-medium text-slate-500">Expires</p>
                    <p class="text-2xl font-bold text-slate-900 mt-1">{{ stats.expired ?? 0 }}</p>
                </div>
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-xs font-medium text-slate-500">Revenu mensuel estime</p>
                    <p class="text-2xl font-bold text-purple-600 mt-1">{{ formatCurrency(stats.monthly_revenue ?? 0) }}</p>
                </div>
            </div>

            <!-- Filters -->
            <div class="flex flex-wrap items-center gap-3">
                <div class="flex gap-2">
                    <button @click="applyFilter('all')" class="rounded-xl border-2 px-4 py-2 text-sm font-medium transition"
                        :class="filter === 'all' ? 'border-purple-500 bg-purple-50 text-purple-700' : 'border-slate-200 text-slate-600 hover:border-slate-300'">Tous</button>
                    <button @click="applyFilter('active')" class="rounded-xl border-2 px-4 py-2 text-sm font-medium transition"
                        :class="filter === 'active' ? 'border-emerald-500 bg-emerald-50 text-emerald-700' : 'border-slate-200 text-slate-600 hover:border-slate-300'">Actifs</button>
                    <button @click="applyFilter('expired')" class="rounded-xl border-2 px-4 py-2 text-sm font-medium transition"
                        :class="filter === 'expired' ? 'border-red-500 bg-red-50 text-red-700' : 'border-slate-200 text-slate-600 hover:border-slate-300'">Expires</button>
                </div>
                <input v-model="searchInput" @keyup.enter="doSearch" type="text" placeholder="Rechercher..."
                    class="flex-1 min-w-[200px] rounded-xl border-slate-300 text-sm" />
                <button @click="doSearch" class="rounded-xl bg-slate-700 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800">Rechercher</button>
            </div>

            <!-- Table -->
            <div class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Utilisateur</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Tier</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Source</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Souscrit le</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Expire le</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Statut</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold text-slate-500 uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="sub in (subscribers.data || [])" :key="sub.id" class="hover:bg-slate-50">
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-3">
                                        <img v-if="sub.profile_photo" :src="'/storage/' + sub.profile_photo" class="h-8 w-8 rounded-full object-cover" />
                                        <div v-else class="h-8 w-8 rounded-full bg-slate-200 flex items-center justify-center text-xs font-bold text-slate-500">{{ (sub.name || '?')[0] }}</div>
                                        <div>
                                            <p class="text-sm font-medium text-slate-900">{{ sub.name }}</p>
                                            <p class="text-xs text-slate-400">{{ sub.email }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium" :class="tierConfig[sub.ambassador_tier] || 'bg-slate-100 text-slate-600'">
                                        {{ sub.ambassador_tier ?? '—' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-sm text-slate-600">{{ sub.ambassador_source ?? '—' }}</td>
                                <td class="px-4 py-3 text-sm text-slate-500">{{ formatDate(sub.ambassador_subscribed_at) }}</td>
                                <td class="px-4 py-3 text-sm text-slate-500">{{ formatDate(sub.ambassador_expires_at) }}</td>
                                <td class="px-4 py-3">
                                    <span v-if="sub.is_ambassador && new Date(sub.ambassador_expires_at) > new Date()"
                                        class="inline-flex rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-medium text-emerald-700">Actif</span>
                                    <span v-else class="inline-flex rounded-full bg-red-100 px-2 py-0.5 text-xs font-medium text-red-700">Expire</span>
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <div class="inline-flex items-center gap-2">
                                        <input v-if="extendingId === sub.id" v-model.number="extendForm.days" type="number" min="1" max="365"
                                            class="w-16 rounded-lg border-slate-300 text-xs" placeholder="jours" />
                                        <button v-if="extendingId === sub.id" @click="extend(sub)" :disabled="extendForm.processing"
                                            class="text-xs font-semibold text-emerald-600 hover:text-emerald-800">Confirmer</button>
                                        <button v-else @click="extendingId = sub.id"
                                            class="text-xs font-medium text-emerald-600 hover:text-emerald-800">Prolonger</button>
                                        <button @click="revoke(sub)"
                                            class="text-xs font-medium text-red-600 hover:text-red-800">Revoquer</button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="!(subscribers.data || []).length">
                                <td colspan="7" class="px-4 py-8 text-center text-sm text-slate-400">Aucun abonne.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div v-if="subscribers.links && subscribers.links.length > 3" class="flex items-center justify-between border-t px-4 py-3">
                    <div class="flex gap-1">
                        <button v-for="(link, i) in subscribers.links" :key="i" :disabled="!link.url" @click="router.visit(link.url)" v-html="link.label"
                            class="rounded-lg px-3 py-1.5 text-sm" :class="link.active ? 'bg-purple-600 text-white' : 'text-slate-600 hover:bg-slate-100'" />
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
