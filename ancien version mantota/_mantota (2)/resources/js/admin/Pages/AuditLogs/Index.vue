<script setup>
import { ref, computed } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import Layout from '../Layout.vue';

defineOptions({ layout: Layout });

const props = defineProps({
    logs: Object,
    admins: Array,
    actions: Array,
    modelTypes: Array,
    filters: Object,
});

const page = usePage();

// ── Filtres locaux ──
const selectedAction = ref(props.filters?.action || '');
const selectedAdmin = ref(props.filters?.admin_id || '');
const selectedModel = ref(props.filters?.model_type || '');
const dateFrom = ref(props.filters?.date_from || '');
const dateTo = ref(props.filters?.date_to || '');

function applyFilters() {
    const params = {};
    if (selectedAction.value) params.action = selectedAction.value;
    if (selectedAdmin.value) params.admin_id = selectedAdmin.value;
    if (selectedModel.value) params.model_type = selectedModel.value;
    if (dateFrom.value) params.date_from = dateFrom.value;
    if (dateTo.value) params.date_to = dateTo.value;

    router.get(route('admin.audit-logs.index'), params, { preserveState: true });
}

function resetFilters() {
    selectedAction.value = '';
    selectedAdmin.value = '';
    selectedModel.value = '';
    dateFrom.value = '';
    dateTo.value = '';
    router.get(route('admin.audit-logs.index'));
}

// ── Détail expandable ──
const expandedLog = ref(null);
function toggleExpand(logId) {
    expandedLog.value = expandedLog.value === logId ? null : logId;
}

// ── Labels lisibles des actions ──
const actionLabels = {
    approve_kyc: 'KYC Approuvé',
    reject_kyc: 'KYC Rejeté',
    approve_vip: 'VIP Approuvé',
    reject_vip: 'VIP Rejeté',
    approve_withdrawal: 'Retrait Approuvé',
    reject_withdrawal: 'Retrait Rejeté',
    refund_client: 'Client Remboursé',
    favor_vendor: 'Vendeur Validé',
    refund_vendor_ugc: 'Vendeur Remboursé (UGC)',
    favor_influencer_ugc: 'Createur de Contenu Payé (UGC)',
    update_socials: 'Audit Social',
    ban_user: 'Utilisateur Banni',
    unban_user: 'Utilisateur Débanni',
    create_admin: 'Admin Créé',
    delete_admin: 'Admin Supprimé',
};

function getActionLabel(action) {
    return actionLabels[action] || action;
}

// ── Couleurs par type d'action ──
function getActionColor(action) {
    if (action.includes('approve') || action.includes('favor')) return 'bg-green-100 text-green-800';
    if (action.includes('reject') || action.includes('ban') || action.includes('delete')) return 'bg-red-100 text-red-800';
    if (action.includes('refund')) return 'bg-yellow-100 text-yellow-800';
    if (action.includes('update') || action.includes('create')) return 'bg-blue-100 text-blue-800';
    return 'bg-gray-100 text-gray-800';
}

function formatDate(dateStr) {
    if (!dateStr) return '-';
    return new Intl.DateTimeFormat('fr-FR', {
        day: '2-digit', month: '2-digit', year: 'numeric',
        hour: '2-digit', minute: '2-digit', second: '2-digit',
    }).format(new Date(dateStr));
}

function formatJson(obj) {
    if (!obj) return '-';
    return JSON.stringify(obj, null, 2);
}
</script>

<template>
    <div class="space-y-6">

        <!-- Header -->
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-bold text-slate-900">Audit Log (Boîte Noire)</h1>
                <p class="text-sm text-slate-500">Traçabilité complète des actions critiques de l'équipe admin</p>
            </div>
            <div class="text-sm text-slate-500">
                {{ logs.total }} entrées au total
            </div>
        </div>

        <!-- Filtres -->
        <div class="rounded-lg border border-slate-200 bg-white p-4">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-5">
                <!-- Action -->
                <div>
                    <label class="mb-1 block text-xs font-medium text-slate-600">Action</label>
                    <select v-model="selectedAction" @change="applyFilters"
                        class="w-full rounded-md border-slate-300 text-sm focus:border-teal-500 focus:ring-teal-500">
                        <option value="">Toutes</option>
                        <option v-for="action in actions" :key="action" :value="action">
                            {{ getActionLabel(action) }}
                        </option>
                    </select>
                </div>

                <!-- Admin -->
                <div>
                    <label class="mb-1 block text-xs font-medium text-slate-600">Admin</label>
                    <select v-model="selectedAdmin" @change="applyFilters"
                        class="w-full rounded-md border-slate-300 text-sm focus:border-teal-500 focus:ring-teal-500">
                        <option value="">Tous</option>
                        <option v-for="admin in admins" :key="admin.id" :value="admin.id">
                            {{ admin.name }}
                        </option>
                    </select>
                </div>

                <!-- Modèle -->
                <div>
                    <label class="mb-1 block text-xs font-medium text-slate-600">Type</label>
                    <select v-model="selectedModel" @change="applyFilters"
                        class="w-full rounded-md border-slate-300 text-sm focus:border-teal-500 focus:ring-teal-500">
                        <option value="">Tous</option>
                        <option v-for="model in modelTypes" :key="model" :value="model">
                            {{ model }}
                        </option>
                    </select>
                </div>

                <!-- Date début -->
                <div>
                    <label class="mb-1 block text-xs font-medium text-slate-600">Du</label>
                    <input type="date" v-model="dateFrom" @change="applyFilters"
                        class="w-full rounded-md border-slate-300 text-sm focus:border-teal-500 focus:ring-teal-500">
                </div>

                <!-- Date fin -->
                <div>
                    <label class="mb-1 block text-xs font-medium text-slate-600">Au</label>
                    <input type="date" v-model="dateTo" @change="applyFilters"
                        class="w-full rounded-md border-slate-300 text-sm focus:border-teal-500 focus:ring-teal-500">
                </div>
            </div>

            <div class="mt-3 flex justify-end">
                <button @click="resetFilters" class="text-sm text-slate-500 hover:text-slate-700 underline">
                    Réinitialiser les filtres
                </button>
            </div>
        </div>

        <!-- Tableau -->
        <div class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Date</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Admin</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Action</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Modèle</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">ID</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">IP</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-slate-600">Détails</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <template v-for="log in logs.data" :key="log.id">
                        <tr class="hover:bg-slate-50 transition-colors cursor-pointer" @click="toggleExpand(log.id)">
                            <td class="whitespace-nowrap px-4 py-3 text-sm text-slate-600">
                                {{ formatDate(log.created_at) }}
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-sm font-medium text-slate-900">
                                {{ log.admin?.name || 'Système' }}
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-sm">
                                <span :class="['inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold', getActionColor(log.action)]">
                                    {{ getActionLabel(log.action) }}
                                </span>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-sm text-slate-600">
                                {{ log.model_type }}
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-sm text-slate-500">
                                #{{ log.model_id }}
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-xs text-slate-400 font-mono">
                                {{ log.ip_address || '-' }}
                            </td>
                            <td class="px-4 py-3 text-center">
                                <button class="text-slate-400 hover:text-teal-600 transition-colors">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 transition-transform" :class="{ 'rotate-180': expandedLog === log.id }" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                                    </svg>
                                </button>
                            </td>
                        </tr>

                        <!-- Détails expandable -->
                        <tr v-if="expandedLog === log.id">
                            <td colspan="7" class="bg-slate-50 px-6 py-4">
                                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                    <!-- Anciennes valeurs -->
                                    <div>
                                        <h4 class="mb-2 text-xs font-semibold uppercase text-slate-500">Anciennes valeurs</h4>
                                        <pre v-if="log.old_values" class="whitespace-pre-wrap rounded-md bg-red-50 p-3 text-xs text-red-800 font-mono border border-red-200">{{ formatJson(log.old_values) }}</pre>
                                        <p v-else class="text-sm text-slate-400">Aucune</p>
                                    </div>

                                    <!-- Nouvelles valeurs -->
                                    <div>
                                        <h4 class="mb-2 text-xs font-semibold uppercase text-slate-500">Nouvelles valeurs</h4>
                                        <pre v-if="log.new_values" class="whitespace-pre-wrap rounded-md bg-green-50 p-3 text-xs text-green-800 font-mono border border-green-200">{{ formatJson(log.new_values) }}</pre>
                                        <p v-else class="text-sm text-slate-400">Aucune</p>
                                    </div>
                                </div>

                                <!-- User Agent -->
                                <div v-if="log.user_agent" class="mt-3">
                                    <h4 class="mb-1 text-xs font-semibold uppercase text-slate-500">User Agent</h4>
                                    <p class="text-xs text-slate-400 font-mono break-all">{{ log.user_agent }}</p>
                                </div>
                            </td>
                        </tr>
                    </template>

                    <!-- État vide -->
                    <tr v-if="!logs.data || logs.data.length === 0">
                        <td colspan="7" class="px-6 py-12 text-center text-sm text-slate-500">
                            Aucun log d'audit trouvé pour les filtres sélectionnés.
                        </td>
                    </tr>
                </tbody>
            </table>

            <!-- Pagination -->
            <div v-if="logs.links && logs.links.length > 3" class="flex items-center justify-between border-t border-slate-200 bg-white px-4 py-3">
                <div class="text-sm text-slate-500">
                    {{ logs.from }}–{{ logs.to }} sur {{ logs.total }}
                </div>
                <div class="flex gap-1">
                    <template v-for="link in logs.links" :key="link.label">
                        <button
                            v-if="link.url"
                            @click="router.get(link.url, {}, { preserveState: true })"
                            :class="[
                                'rounded-md px-3 py-1.5 text-sm transition-colors',
                                link.active
                                    ? 'bg-teal-600 text-white font-semibold'
                                    : 'bg-slate-100 text-slate-600 hover:bg-slate-200'
                            ]"
                            v-html="link.label"
                        />
                        <span v-else class="rounded-md px-3 py-1.5 text-sm text-slate-300" v-html="link.label" />
                    </template>
                </div>
            </div>
        </div>
    </div>
</template>
