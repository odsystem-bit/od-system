<script setup>
import { ref, computed } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import Layout from '../Layout.vue';

defineOptions({ layout: Layout });

const props = defineProps({
    withdrawals: Object,
    status: String,
    pendingCount: Number,
    completedCount: Number,
    failedCount: Number,
    trustScores: Object,
    recentWithdrawals: Object,
});

const expandedTrust = ref(null);
function toggleTrust(userId) {
    expandedTrust.value = expandedTrust.value === userId ? null : userId;
}
function trustFor(userId) {
    return props.trustScores?.[userId] ?? null;
}
function recentFor(userId) {
    return props.recentWithdrawals?.[userId] ?? [];
}

const page = usePage();
const flash = computed(() => page.props.flash ?? {});

const filters = [
    { key: 'pending', label: 'En attente' },
    { key: 'completed', label: 'Approuves' },
    { key: 'failed', label: 'Rejetes' },
    { key: 'all', label: 'Tous' },
];

function countFor(key) {
    if (key === 'pending') return props.pendingCount;
    if (key === 'completed') return props.completedCount;
    if (key === 'failed') return props.failedCount;
    return props.pendingCount + props.completedCount + props.failedCount;
}

function filterBy(status) {
    router.get(route('admin.withdrawals.index'), { status }, { preserveState: true, replace: true });
}

// Confirmation modal state
const showConfirmModal = ref(false);
const confirmAction = ref(null); // 'approve' | 'reject'
const confirmWithdrawal = ref(null);
const confirmProcessing = ref(false);

function recipientPhone(w) {
    return w.recipient_phone || w.user?.momo_number || w.user?.phone || '-';
}

function openConfirm(action, w) {
    confirmAction.value = action;
    confirmWithdrawal.value = w;
    showConfirmModal.value = true;
}

function closeConfirm() {
    showConfirmModal.value = false;
    confirmAction.value = null;
    confirmWithdrawal.value = null;
    confirmProcessing.value = false;
}

function executeConfirm() {
    if (!confirmWithdrawal.value) return;
    confirmProcessing.value = true;
    const txId = confirmWithdrawal.value.id;
    const routeName = confirmAction.value === 'approve' ? 'admin.withdrawal.approve' : 'admin.withdrawal.reject';
    router.patch(route(routeName, txId), {}, {
        preserveScroll: true,
        onFinish: () => closeConfirm(),
    });
}

function formatAmount(val) {
    return Number(val ?? 0).toLocaleString('fr-FR');
}

function statusBadge(s) {
    if (s === 'pending') return { class: 'bg-amber-50 text-amber-700', label: 'En attente' };
    if (s === 'completed') return { class: 'bg-teal-50 text-teal-700', label: 'Approuve' };
    return { class: 'bg-red-50 text-red-700', label: 'Rejete' };
}
</script>

<template>
    <div class="space-y-6">
        <!-- Header -->
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-slate-900">Retraits</h1>
                <p class="mt-1 text-sm text-slate-500">Gestion des demandes de retrait. Approuvez apres transfert mobile money ou rejetez pour recrediter le wallet.</p>
            </div>
            <a
                :href="route('admin.withdrawals.export')"
                class="inline-flex items-center gap-2 rounded-xl bg-slate-700 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-slate-800"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m.75 12l3 3m0 0l3-3m-3 3v-6m-1.5-9H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" /></svg>
                Exporter CSV
            </a>
        </div>

        <!-- Flash -->
        <div v-if="flash.success" class="rounded-xl border border-teal-200 bg-teal-50 px-4 py-3 text-sm font-medium text-teal-800">
            {{ flash.success }}
        </div>

        <!-- Filters -->
        <div class="flex flex-wrap gap-2">
            <button
                v-for="f in filters"
                :key="f.key"
                @click="filterBy(f.key)"
                class="rounded-lg px-3 py-1.5 text-xs font-semibold transition-colors"
                :class="status === f.key
                    ? 'bg-teal-600 text-white shadow-sm'
                    : 'bg-white text-slate-600 border border-slate-200 hover:bg-slate-50'"
            >
                {{ f.label }} ({{ countFor(f.key) }})
            </button>
        </div>

        <!-- Table -->
        <div v-if="withdrawals.data.length === 0" class="rounded-xl border border-slate-200 bg-white px-6 py-12 text-center">
            <p class="text-sm text-slate-500">Aucun retrait dans cette categorie.</p>
        </div>
        <div v-else class="overflow-hidden rounded-xl border border-slate-200 bg-white">
            <table class="w-full text-left text-sm">
                <thead class="border-b border-slate-100 bg-slate-50">
                    <tr>
                        <th class="px-4 py-3 font-medium text-slate-600">Utilisateur</th>
                        <th class="px-4 py-3 font-medium text-slate-600">Telephone</th>
                        <th class="px-4 py-3 font-medium text-slate-600">Montant brut</th>
                        <th class="px-4 py-3 font-medium text-slate-600">Frais</th>
                        <th class="px-4 py-3 font-medium text-slate-600">Net</th>
                        <th class="px-4 py-3 font-medium text-slate-600">Statut</th>
                        <th class="px-4 py-3 font-medium text-slate-600">Date</th>
                        <th class="px-4 py-3 font-medium text-slate-600 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <template v-for="w in withdrawals.data" :key="w.id">
                        <tr class="hover:bg-slate-50/60">
                            <td class="px-4 py-3">
                                <p class="font-medium text-slate-900">{{ w.user?.name ?? '-' }}</p>
                                <p class="text-xs text-slate-500">{{ w.user?.email ?? '' }}</p>
                            </td>
                            <td class="px-4 py-3 text-sm text-slate-700 font-mono">{{ recipientPhone(w) }}</td>
                            <td class="px-4 py-3 font-medium text-slate-800">{{ formatAmount(w.amount_target) }} FCFA</td>
                            <td class="px-4 py-3 text-slate-500">{{ formatAmount(w.fee_amount) }} FCFA</td>
                            <td class="px-4 py-3 font-semibold text-slate-900">{{ formatAmount(w.amount_total) }} FCFA</td>
                            <td class="px-4 py-3">
                                <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-semibold" :class="statusBadge(w.status).class">
                                    {{ statusBadge(w.status).label }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-slate-500">{{ new Date(w.created_at).toLocaleDateString('fr-FR') }}</td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <button v-if="trustFor(w.user_id)" @click="toggleTrust(w.user_id)" class="rounded-lg border border-slate-200 px-2.5 py-1.5 text-xs font-medium text-slate-600 transition hover:bg-slate-100" :title="expandedTrust === w.user_id ? 'Masquer fiabilite' : 'Score de fiabilite'">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" /></svg>
                                    </button>
                                    <template v-if="w.status === 'pending'">
                                        <button @click="openConfirm('approve', w)" class="rounded-lg bg-teal-600 px-3 py-1.5 text-xs font-semibold text-white shadow-sm transition hover:bg-teal-500">
                                            Approuver
                                        </button>
                                        <button @click="openConfirm('reject', w)" class="rounded-lg border border-slate-300 px-3 py-1.5 text-xs font-semibold text-slate-700 transition hover:bg-red-50 hover:border-red-300 hover:text-red-700">
                                            Rejeter
                                        </button>
                                    </template>
                                    <span v-else class="text-xs text-slate-400">--</span>
                                </div>
                            </td>
                        </tr>
                        <!-- Trust Score Panel -->
                        <tr v-if="expandedTrust === w.user_id && trustFor(w.user_id)">
                            <td colspan="8" class="bg-slate-50/50 px-4 py-3">
                                <div class="rounded-lg border border-teal-200 bg-teal-50/60 px-4 py-3">
                                    <div class="flex items-center gap-2 mb-3">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-teal-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" /></svg>
                                        <p class="text-xs font-semibold text-teal-800">Score de fiabilite</p>
                                    </div>
                                    <div class="grid grid-cols-2 gap-3 mb-3">
                                        <div class="rounded-md bg-white/70 px-3 py-2">
                                            <p class="text-xs text-slate-500">Retraits reussis</p>
                                            <p class="text-lg font-bold text-teal-700">{{ trustFor(w.user_id).count }}</p>
                                        </div>
                                        <div class="rounded-md bg-white/70 px-3 py-2">
                                            <p class="text-xs text-slate-500">Total retire</p>
                                            <p class="text-lg font-bold text-slate-900">{{ formatAmount(trustFor(w.user_id).total) }} FCFA</p>
                                        </div>
                                    </div>
                                    <div v-if="recentFor(w.user_id).length">
                                        <p class="text-xs font-medium text-slate-500 mb-1.5">3 derniers retraits</p>
                                        <div class="space-y-1">
                                            <div v-for="r in recentFor(w.user_id)" :key="r.id" class="flex items-center justify-between rounded-md bg-white/70 px-3 py-1.5 text-xs">
                                                <span class="font-medium text-slate-700">{{ formatAmount(r.amount_total) }} FCFA</span>
                                                <span class="text-slate-400">{{ new Date(r.created_at).toLocaleDateString('fr-FR') }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>

        <!-- Confirmation Modal -->
        <Teleport to="body">
            <Transition
                enter-active-class="transition duration-200 ease-out"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="transition duration-150 ease-in"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div
                    v-if="showConfirmModal && confirmWithdrawal"
                    class="fixed inset-0 z-50 flex items-center justify-center p-4"
                >
                    <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" @click="closeConfirm" />
                    <div class="relative w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl ring-1 ring-slate-200">
                        <button type="button" class="absolute right-4 top-4 rounded-lg p-1 text-slate-400 transition hover:bg-slate-100 hover:text-slate-600" @click="closeConfirm">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                        </button>

                        <div class="flex items-center gap-3 mb-6">
                            <div
                                class="flex h-10 w-10 items-center justify-center rounded-xl"
                                :class="confirmAction === 'approve' ? 'bg-teal-50' : 'bg-red-50'"
                            >
                                <svg v-if="confirmAction === 'approve'" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-teal-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                                <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-red-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                            </div>
                            <div>
                                <h3 class="text-lg font-semibold text-slate-900">
                                    {{ confirmAction === 'approve' ? 'Confirmer l\'approbation' : 'Confirmer le rejet' }}
                                </h3>
                                <p class="text-sm text-slate-500">Verification avant action</p>
                            </div>
                        </div>

                        <div class="space-y-3 rounded-xl border border-slate-200 bg-slate-50 p-4 mb-6">
                            <div class="flex justify-between text-sm">
                                <span class="text-slate-500">Utilisateur</span>
                                <span class="font-medium text-slate-900">{{ confirmWithdrawal.user?.name ?? '-' }}</span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-slate-500">Telephone Mobile Money</span>
                                <span class="font-semibold text-slate-900 font-mono">{{ recipientPhone(confirmWithdrawal) }}</span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-slate-500">Montant brut</span>
                                <span class="font-medium text-slate-900">{{ formatAmount(confirmWithdrawal.amount_target) }} FCFA</span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-slate-500">Frais</span>
                                <span class="text-slate-600">{{ formatAmount(confirmWithdrawal.fee_amount) }} FCFA</span>
                            </div>
                            <div class="border-t border-slate-200 pt-2 flex justify-between text-sm font-semibold">
                                <span class="text-slate-900">Net a envoyer</span>
                                <span :class="confirmAction === 'approve' ? 'text-teal-700' : 'text-red-600'">{{ formatAmount(confirmWithdrawal.amount_total) }} FCFA</span>
                            </div>
                        </div>

                        <p v-if="confirmAction === 'approve'" class="mb-4 text-sm text-amber-700 bg-amber-50 border border-amber-200 rounded-lg px-3 py-2">
                            Assurez-vous d'avoir effectue le transfert Mobile Money vers le numero ci-dessus avant de confirmer.
                        </p>
                        <p v-else class="mb-4 text-sm text-red-700 bg-red-50 border border-red-200 rounded-lg px-3 py-2">
                            Le montant sera recredite dans le portefeuille de l'utilisateur.
                        </p>

                        <div class="flex items-center gap-3">
                            <button
                                type="button"
                                :disabled="confirmProcessing"
                                class="flex-1 inline-flex items-center justify-center gap-2 rounded-full px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition disabled:opacity-50 disabled:cursor-not-allowed"
                                :class="confirmAction === 'approve' ? 'bg-teal-600 hover:bg-teal-500' : 'bg-red-600 hover:bg-red-500'"
                                @click="executeConfirm"
                            >
                                <svg v-if="confirmProcessing" class="h-4 w-4 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                                </svg>
                                {{ confirmAction === 'approve' ? 'Confirmer l\'approbation' : 'Confirmer le rejet' }}
                            </button>
                            <button
                                type="button"
                                class="rounded-full border border-slate-300 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 shadow-sm transition hover:bg-slate-50"
                                @click="closeConfirm"
                            >
                                Annuler
                            </button>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>
    </div>
</template>
