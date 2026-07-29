<script setup>
import VendorLayout from '../../Layouts/VendorLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    campaigns: Object,
    available_balance: Number,
});

// ── Modal Ajout Budget ──
const budgetModal = ref(false);
const budgetCampaignId = ref(null);
const budgetAmount = ref('');
const budgetProcessing = ref(false);

function openBudgetModal(campaignId) {
    budgetCampaignId.value = campaignId;
    budgetAmount.value = '';
    budgetModal.value = true;
}

function closeBudgetModal() {
    budgetModal.value = false;
    budgetCampaignId.value = null;
    budgetAmount.value = '';
}

function submitBudget() {
    if (!budgetAmount.value || parseFloat(budgetAmount.value) < 500) return;
    budgetProcessing.value = true;
    router.post(route('vendor.campaigns.add-budget', budgetCampaignId.value), {
        amount: budgetAmount.value,
    }, {
        preserveScroll: true,
        onFinish: () => {
            budgetProcessing.value = false;
            closeBudgetModal();
        },
    });
}

// ── Pause / Resume ──
function togglePause(campaignId) {
    router.post(route('vendor.campaigns.toggle-pause', campaignId), {}, {
        preserveScroll: true,
    });
}

// ── Helpers ──
function formatCurrency(v) {
    return new Intl.NumberFormat('fr-FR').format(Math.round(v)) + ' FCFA';
}

function statusLabel(status) {
    const map = {
        active: 'Active',
        paused: 'En pause (Reprise auto dans < 1h)',
        completed: 'Terminee',
        draft: 'Brouillon',
        expired: 'Expiree',
    };
    return map[status] ?? status;
}

function statusColor(status) {
    const map = {
        active: 'bg-purple-100 text-purple-700',
        paused: 'bg-amber-100 text-amber-700',
        completed: 'bg-slate-100 text-slate-600',
        draft: 'bg-slate-100 text-slate-500',
        expired: 'bg-red-100 text-red-700',
    };
    return map[status] ?? 'bg-slate-100 text-slate-500';
}

function budgetPercent(campaign) {
    const total = parseFloat(campaign.total_budget);
    const remaining = parseFloat(campaign.remaining_budget);
    if (!total || total <= 0) return 0;
    return Math.min(100, Math.round(((total - remaining) / total) * 100));
}
</script>

<template>
    <Head title="Mes Campagnes" />

    <VendorLayout>
        <div class="space-y-6">

            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-xl font-bold text-slate-900">Mes Campagnes</h1>
                    <p class="mt-1 text-sm text-slate-500">Gerez vos campagnes publicitaires CPC.</p>
                </div>
                <Link
                    :href="route('vendor.campaigns.create')"
                    class="inline-flex items-center gap-2 rounded-full bg-gradient-to-r from-purple-500 to-violet-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:from-purple-600 hover:to-violet-700"
                >
                    <!-- Heroicon: Plus -->
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                    Nouvelle campagne
                </Link>
            </div>

            <!-- Liste -->
            <div v-if="campaigns.data.length === 0" class="rounded-2xl border border-slate-200/80 bg-white py-16 text-center shadow-sm">
                <svg xmlns="http://www.w3.org/2000/svg" class="mx-auto h-12 w-12 text-slate-300" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3v11.25A2.25 2.25 0 006 16.5h2.25M3.75 3h-1.5m1.5 0h16.5m0 0h1.5m-1.5 0v11.25A2.25 2.25 0 0118 16.5h-2.25m-7.5 0h7.5m-7.5 0l-1 3m8.5-3l1 3m0 0l.5 1.5m-.5-1.5h-9.5m0 0l-.5 1.5" /></svg>
                <p class="mt-4 text-sm text-slate-500">Aucune campagne pour le moment.</p>
                <Link :href="route('vendor.campaigns.index')" class="mt-4 inline-flex items-center gap-1.5 rounded-full bg-purple-600 px-4 py-2 text-sm font-medium text-white shadow-sm transition hover:bg-purple-700">
                    Lancer ma premiere campagne
                </Link>
            </div>

            <div v-else class="space-y-4">
                <div
                    v-for="c in campaigns.data"
                    :key="c.id"
                    class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm transition-all duration-500 hover:-translate-y-1 hover:shadow-xl hover:shadow-purple-500/5"
                >
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                        <!-- Infos -->
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-3">
                                <Link :href="route('vendor.campaigns.show', c.id)" class="text-base font-semibold text-slate-900 truncate hover:text-purple-600 transition">
                                    {{ c.title }}
                                </Link>
                                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium" :class="statusColor(c.status)">
                                    {{ statusLabel(c.status) }}
                                </span>
                                <span v-if="c.open_sea" class="inline-flex items-center rounded-full bg-purple-100 px-2.5 py-0.5 text-xs font-medium text-purple-700">
                                    Open Sea
                                </span>
                            </div>

                            <div class="mt-2 flex flex-wrap items-center gap-x-5 gap-y-1 text-xs text-slate-500">
                                <span>CPC : {{ formatCurrency(c.click_price) }}</span>
                                <span>Palier : {{ c.tier ?? '-' }}</span>
                                <span>Liens : {{ c.smart_links_count ?? 0 }}</span>
                            </div>

                            <!-- Budget gauge -->
                            <div class="mt-3 max-w-sm">
                                <div class="flex items-center justify-between text-xs text-slate-500 mb-1">
                                    <span>Budget consomme</span>
                                    <span class="font-medium text-slate-700">{{ budgetPercent(c) }}%</span>
                                </div>
                                <div class="h-2 w-full overflow-hidden rounded-full bg-slate-100">
                                    <div
                                        class="h-full rounded-full transition-all duration-500"
                                        :class="budgetPercent(c) >= 90 ? 'bg-red-500' : budgetPercent(c) >= 60 ? 'bg-amber-500' : 'bg-purple-500'"
                                        :style="{ width: budgetPercent(c) + '%' }"
                                    />
                                </div>
                                <div class="mt-1 flex justify-between text-[11px] text-slate-400">
                                    <span>Restant : {{ formatCurrency(c.remaining_budget) }}</span>
                                    <span>Total : {{ formatCurrency(c.total_budget) }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="flex items-center gap-2 shrink-0">
                            <!-- Pause / Resume -->
                            <button
                                v-if="c.status === 'active' || c.status === 'paused'"
                                @click="togglePause(c.id)"
                                class="inline-flex items-center gap-1.5 rounded-full border px-3 py-2 text-xs font-medium shadow-sm transition"
                                :class="c.status === 'active'
                                    ? 'border-amber-300 bg-amber-50 text-amber-700 hover:bg-amber-100'
                                    : 'border-purple-300 bg-purple-50 text-purple-700 hover:bg-purple-100'"
                            >
                                <!-- Heroicon: Pause or Play -->
                                <svg v-if="c.status === 'active'" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25v13.5m-7.5-13.5v13.5" /></svg>
                                <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M5.25 5.653c0-.856.917-1.398 1.667-.986l11.54 6.347a1.125 1.125 0 010 1.972l-11.54 6.347a1.125 1.125 0 01-1.667-.986V5.653z" /></svg>
                                {{ c.status === 'active' ? 'Pause' : 'Reprendre' }}
                            </button>

                            <!-- Ajouter Budget -->
                            <button
                                v-if="c.status === 'active' || c.status === 'paused' || c.status === 'completed'"
                                @click="openBudgetModal(c.id)"
                                class="inline-flex items-center gap-1.5 rounded-full border border-purple-300 bg-purple-50 px-3 py-2 text-xs font-medium text-purple-700 shadow-sm transition hover:bg-purple-100"
                            >
                                <!-- Heroicon: Plus -->
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                                Budget
                            </button>

                            <!-- Modifier -->
                            <Link
                                :href="route('vendor.campaigns.edit', c.id)"
                                class="inline-flex items-center gap-1.5 rounded-full border border-purple-300 bg-purple-50 px-3 py-2 text-xs font-medium text-purple-700 shadow-sm transition hover:bg-purple-100"
                            >
                                <!-- Heroicon: Pencil -->
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" /></svg>
                            </Link>

                            <!-- Voir details -->
                            <Link
                                :href="route('vendor.campaigns.show', c.id)"
                                class="inline-flex items-center gap-1.5 rounded-full border border-slate-300 bg-white px-3 py-2 text-xs font-medium text-slate-600 shadow-sm transition hover:bg-slate-50"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                                Details
                            </Link>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Pagination -->
            <div v-if="campaigns.links && campaigns.links.length > 3" class="flex items-center justify-center gap-1">
                <template v-for="link in campaigns.links" :key="link.label">
                    <Link
                        v-if="link.url"
                        :href="link.url"
                        class="rounded-full px-3 py-1.5 text-xs font-medium transition"
                        :class="link.active ? 'bg-purple-600 text-white' : 'text-slate-600 hover:bg-slate-100'"
                        v-html="link.label"
                    />
                    <span v-else class="px-3 py-1.5 text-xs text-slate-300" v-html="link.label" />
                </template>
            </div>
        </div>

        <!-- ═══════════════════════════════════════════
             MODAL — Ajouter du Budget
        ═══════════════════════════════════════════ -->
        <Teleport to="body">
            <Transition
                enter-active-class="transition duration-200 ease-out"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="transition duration-150 ease-in"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div v-if="budgetModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
                    <div class="w-full max-w-md rounded-2xl border border-slate-200 bg-white p-6 shadow-2xl" @click.stop>
                        <h3 class="text-lg font-bold text-slate-900">Ajouter du budget</h3>
                        <p class="mt-1 text-sm text-slate-500">
                            Solde disponible : <span class="font-semibold text-purple-600">{{ formatCurrency(available_balance) }}</span>
                        </p>

                        <div class="mt-5">
                            <label for="budget_amount" class="block text-sm font-medium text-slate-700 mb-1">Montant (FCFA)</label>
                            <input
                                id="budget_amount"
                                v-model="budgetAmount"
                                type="number"
                                min="500"
                                step="100"
                                placeholder="5 000"
                                class="block w-full rounded-lg border-slate-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 sm:text-sm"
                                @keyup.enter="submitBudget"
                            />
                            <p class="mt-1 text-xs text-slate-400">Minimum 500 FCFA. Le montant sera debite de votre portefeuille.</p>
                        </div>

                        <div class="mt-6 flex items-center justify-end gap-3">
                            <button @click="closeBudgetModal" class="rounded-full border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-600 transition hover:bg-slate-50">
                                Annuler
                            </button>
                            <button
                                @click="submitBudget"
                                :disabled="budgetProcessing || !budgetAmount || parseFloat(budgetAmount) < 500"
                                class="inline-flex items-center gap-2 rounded-full bg-purple-600 px-5 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-purple-700 disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                                <svg v-if="budgetProcessing" class="h-4 w-4 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" /><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" /></svg>
                                {{ budgetProcessing ? 'Traitement...' : 'Confirmer' }}
                            </button>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>

    </VendorLayout>
</template>
