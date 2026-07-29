<script setup>
import VendorLayout from '../../Layouts/VendorLayout.vue';
import { Head, useForm, Link, router } from '@inertiajs/vue3';
import { computed, ref, onUnmounted } from 'vue';
import axios from 'axios';

/**
 * Props transmises par Vendor\WalletController@index.
 *
 * - wallet       : Objet Wallet (balance, pending_balance, escrow_balance).
 * - transactions : Collection paginée des transactions.
 */
const props = defineProps({
    wallet: {
        type: Object,
        default: null,
    },
    transactions: {
        type: Object,
        required: true,
    },
    kyc_status: {
        type: String,
        default: 'pending',
    },
    min_withdrawal: {
        type: Number,
        default: 1000,
    },
    withdrawal_fee_percent: {
        type: Number,
        default: 20,
    },
    deposit_markup_percent: {
        type: Number,
        default: 1.5,
    },
    gateway_fee_percent: {
        type: Number,
        default: 1.5,
    },
    platform_commission_rate: {
        type: Number,
        default: 20,
    },
    momo_number: {
        type: String,
        default: '',
    },
});

const isKycApproved = computed(() => props.kyc_status === 'approved');

// ── Dépôt modal ──
const showDepositModal   = ref(false);
const depositLoading     = ref(false);
const depositError       = ref('');
const depositAmountError = ref('');
const depositAmountInput = ref('');
const paymentState       = ref('idle'); // idle | loading | popup | success | failed
const paymentPopup       = ref(null);
const pollTimer          = ref(null);

// garde le montant en form pour la décomposition
const depositForm = useForm({ amount_target: '' });

const depositBreakdown = computed(() => {
    const target = parseFloat(depositAmountInput.value);
    if (!target || target < 1000) return null;
    const feeRate       = props.gateway_fee_percent / 100;
    const gatewayFee    = Math.round((target / (1 - feeRate) - target) * 100) / 100;
    const mantotaMarkup = Math.round(target * (props.deposit_markup_percent / 100) * 100) / 100;
    const total         = Math.round((target + gatewayFee + mantotaMarkup) * 100) / 100;
    return { target, gatewayFee, mantotaMarkup, total };
});

async function submitDeposit() {
    depositAmountError.value = '';
    depositError.value       = '';
    const target = parseFloat(depositAmountInput.value);
    if (!target || target < 1000) {
        depositAmountError.value = 'Le montant minimum est 1 000 FCFA.';
        return;
    }
    paymentState.value   = 'loading';
    depositLoading.value = true;

    try {
        const res = await axios.post(route('vendor.deposit'), {
            amount_target: depositAmountInput.value,
        }, {
            headers: { 'Accept': 'application/json' },
        });

        const { payment_url, transaction_id } = res.data;

        // Ouvrir la page de paiement dans un popup centré
        const w = 520, h = 720;
        const left = Math.round(window.screenX + (window.outerWidth  - w) / 2);
        const top  = Math.round(window.screenY + (window.outerHeight - h) / 2);
        paymentPopup.value = window.open(
            payment_url,
            'mantota_payment',
            `width=${w},height=${h},left=${left},top=${top},menubar=no,toolbar=no,location=yes,status=no,scrollbars=yes`
        );

        paymentState.value = 'popup';

        // Polling toutes les 2.5 secondes pour détecter la confirmation
        pollTimer.value = setInterval(async () => {
            // Si le popup a été fermé manuellement avant confirmation
            if (paymentPopup.value && paymentPopup.value.closed && paymentState.value === 'popup') {
                clearInterval(pollTimer.value);
                paymentState.value   = 'idle';
                depositLoading.value = false;
                return;
            }
            try {
                const check = await axios.get(route('vendor.deposit.status', { transaction: transaction_id }));
                if (check.data.status === 'completed') {
                    clearInterval(pollTimer.value);
                    if (paymentPopup.value && !paymentPopup.value.closed) paymentPopup.value.close();
                    paymentState.value   = 'success';
                    depositLoading.value = false;
                    // Rafraîchir les données du portefeuille via Inertia
                    setTimeout(() => router.reload({ only: ['wallet', 'transactions'] }), 800);
                } else if (check.data.status === 'failed') {
                    clearInterval(pollTimer.value);
                    if (paymentPopup.value && !paymentPopup.value.closed) paymentPopup.value.close();
                    paymentState.value   = 'failed';
                    depositLoading.value = false;
                }
            } catch (_) { /* ignorer les erreurs de polling */ }
        }, 2500);

    } catch (err) {
        depositLoading.value = false;
        paymentState.value   = 'idle';
        if (err.response?.status === 422) {
            const errors = err.response.data.errors || {};
            depositAmountError.value = errors.amount_target?.[0] ?? '';
            depositError.value       = err.response.data.message ?? '';
        } else {
            depositError.value = 'Erreur lors de la connexion au service de paiement. Réessayez.';
        }
    }
}

function closeDepositModal() {
    clearInterval(pollTimer.value);
    if (paymentPopup.value && !paymentPopup.value.closed) paymentPopup.value.close();
    showDepositModal.value = false;
    depositAmountInput.value = '';
    depositAmountError.value = '';
    depositError.value       = '';
    depositLoading.value     = false;
    paymentState.value       = 'idle';
    depositForm.reset();
}

onUnmounted(() => { clearInterval(pollTimer.value); });

// ── Retrait modal ──
const showWithdrawModal = ref(false);

const withdrawForm = useForm({
    amount: '',
    momo_number: props.momo_number || '',
});

const withdrawBreakdown = computed(() => {
    const amount = parseFloat(withdrawForm.amount);
    if (!amount || amount < 1000) return null;
    const feePercentage = props.withdrawal_fee_percent / 100;
    const mantotaCommission = Math.round(amount * feePercentage * 100) / 100;
    const afterCommission   = Math.round((amount - mantotaCommission) * 100) / 100;
    const feeRate           = props.gateway_fee_percent / 100;
    const gatewayFee        = Math.round(afterCommission * feeRate * 100) / 100;
    const netPayout         = Math.round((afterCommission - gatewayFee) * 100) / 100;
    return { amount, mantotaCommission, gatewayFee, netPayout };
});

function submitWithdraw() {
    withdrawForm.post(route('vendor.wallet.withdraw'), {
        preserveScroll: true,
        onSuccess: () => closeWithdrawModal(),
    });
}

function closeWithdrawModal() {
    showWithdrawModal.value = false;
    withdrawForm.reset();
    withdrawForm.clearErrors();
}

function formatCurrency(amount) {
    const v = parseFloat(amount);
    if (!v && v !== 0) return '0 FCFA';
    return new Intl.NumberFormat('fr-FR', {
        style: 'decimal',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    }).format(v) + ' FCFA';
}

function txTypeLabel(type) {
    const map = { deposit: 'Depot', earning: 'Gain', withdrawal: 'Retrait', fee: 'Frais' };
    return map[type] ?? type;
}

function txTypeClasses(type) {
    const map = {
        deposit:    'bg-green-50 text-green-700 ring-green-600/20',
        earning:    'bg-purple-50 text-purple-700 ring-purple-600/20',
        withdrawal: 'bg-red-50 text-red-700 ring-red-600/20',
        fee:        'bg-slate-50 text-slate-700 ring-slate-600/20',
    };
    return map[type] ?? 'bg-slate-50 text-slate-700 ring-slate-600/20';
}

function txStatusLabel(status) {
    const map = { completed: 'Complete', pending: 'En attente', failed: 'Echoue' };
    return map[status] ?? status;
}

function txStatusClasses(status) {
    const map = {
        completed: 'bg-emerald-50 text-emerald-700 ring-emerald-600/20',
        pending:   'bg-amber-50 text-amber-700 ring-amber-600/20',
        failed:    'bg-red-50 text-red-700 ring-red-600/20',
    };
    return map[status] ?? 'bg-slate-50 text-slate-700 ring-slate-600/20';
}
</script>

<template>
    <Head title="Portefeuille" />

    <VendorLayout>
        <template #header>
            <h2 class="text-xl font-bold leading-tight text-slate-800">
                Portefeuille
            </h2>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8 space-y-8">

                <!-- ────────────────────────────────────────────
                     Soldes — Principal + Escrow
                ──────────────────────────────────────────── -->
                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3">

                    <!-- Solde principal -->
                    <div class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white px-5 py-5 shadow-sm">
                        <div class="flex items-center gap-4">
                            <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-purple-50">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-purple-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 12a2.25 2.25 0 00-2.25-2.25H15a3 3 0 11-6 0H5.25A2.25 2.25 0 003 12m18 0v6a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 18v-6m18 0V9M3 12V9m18 0a2.25 2.25 0 00-2.25-2.25H5.25A2.25 2.25 0 013 9m18 0V6a2.25 2.25 0 00-2.25-2.25H5.25A2.25 2.25 0 013 6v3" />
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-slate-500">Solde principal</p>
                                <p class="text-2xl font-bold text-slate-900">{{ formatCurrency(wallet?.balance ?? 0) }}</p>
                                <p class="text-xs text-slate-400 mt-0.5">Disponible pour vos campagnes</p>
                            </div>
                        </div>
                    </div>

                    <!-- Solde escrow -->
                    <div class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white px-5 py-5 shadow-sm">
                        <div class="flex items-center gap-4">
                            <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-lg bg-amber-50">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-amber-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-slate-500">Solde escrow</p>
                                <p class="text-2xl font-bold text-slate-900">{{ formatCurrency(wallet?.escrow_balance ?? 0) }}</p>
                                <p class="text-xs text-slate-400 mt-0.5">Bloque pour commandes Studios en cours</p>
                            </div>
                        </div>
                    </div>

                    <!-- Actions depot + retrait -->
                    <div class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white px-5 py-5 shadow-sm flex flex-col items-center justify-center gap-3">
                        <button
                            type="button"
                            class="inline-flex w-full items-center justify-center gap-2 rounded-full bg-gradient-to-r from-purple-500 to-violet-600 px-6 py-3 text-sm font-semibold text-white shadow-sm transition hover:from-purple-600 hover:to-violet-700"
                            @click="showDepositModal = true"
                        >
                            <!-- Heroicon: plus -->
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                            </svg>
                            Recharger mon compte
                        </button>
                        <button
                            type="button"
                            class="inline-flex w-full items-center justify-center gap-2 rounded-full bg-emerald-600 px-6 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-700 disabled:opacity-50 disabled:cursor-not-allowed"
                            :disabled="!isKycApproved || !wallet || parseFloat(wallet.balance) < 1000"
                            @click="showWithdrawModal = true"
                        >
                            <!-- Heroicon: banknotes -->
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75M15 10.5a3 3 0 11-6 0 3 3 0 016 0zm3 0h.008v.008H18V10.5zm-12 0h.008v.008H6V10.5z" />
                            </svg>
                            Demander un retrait (Mobile Money)
                        </button>
                        <p v-if="!isKycApproved" class="text-xs text-amber-600 text-center">KYC requis pour les retraits</p>
                    </div>
                </div>

                <!-- ────────────────────────────────────────────
                     Historique des transactions
                ──────────────────────────────────────────── -->
                <div class="rounded-2xl border border-slate-200/80 bg-white shadow-sm overflow-hidden">

                    <div class="px-6 py-4 border-b border-slate-200">
                        <h3 class="text-sm font-semibold text-slate-900">Historique des transactions</h3>
                    </div>

                    <!-- Empty state -->
                    <div
                        v-if="!transactions.data.length"
                        class="px-6 py-16 text-center"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="mx-auto h-12 w-12 text-slate-300" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5z" />
                        </svg>
                        <h3 class="mt-4 text-sm font-semibold text-slate-900">Aucune transaction</h3>
                        <p class="mt-1 text-sm text-slate-500">Effectuez un depot pour commencer.</p>
                    </div>

                    <!-- Table -->
                    <table v-else class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-gradient-to-r from-slate-50 to-purple-50/30">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Type</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Description</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Montant credite</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Frais</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Total paye</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Statut</th>
                                <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-500">Reference</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="tx in transactions.data" :key="tx.id" class="transition hover:bg-slate-50/60">
                                <td class="whitespace-nowrap px-6 py-3 text-sm">
                                    <span
                                        class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium ring-1 ring-inset"
                                        :class="txTypeClasses(tx.type)"
                                    >
                                        {{ txTypeLabel(tx.type) }}
                                    </span>
                                </td>
                                <td class="whitespace-nowrap px-6 py-3 text-sm text-slate-500">
                                    {{ tx.description || txTypeLabel(tx.type) }}
                                </td>
                                <td class="whitespace-nowrap px-6 py-3 text-sm font-medium text-slate-900">
                                    {{ formatCurrency(tx.amount_target) }}
                                </td>
                                <td class="whitespace-nowrap px-6 py-3 text-sm text-slate-500">
                                    {{ formatCurrency(parseFloat(tx.gateway_fee || 0) + parseFloat(tx.mantota_markup || 0)) }}
                                </td>
                                <td class="whitespace-nowrap px-6 py-3 text-sm font-medium text-slate-900">
                                    {{ formatCurrency(tx.amount_total) }}
                                </td>
                                <td class="whitespace-nowrap px-6 py-3 text-sm">
                                    <span
                                        class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium ring-1 ring-inset"
                                        :class="txStatusClasses(tx.status)"
                                    >
                                        {{ txStatusLabel(tx.status) }}
                                    </span>
                                </td>
                                <td class="whitespace-nowrap px-6 py-3 text-right text-xs text-slate-400 font-mono">
                                    {{ tx.reference }}
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <!-- Pagination -->
                    <div
                        v-if="transactions.data.length && transactions.last_page > 1"
                        class="flex items-center justify-between border-t border-slate-200 bg-white px-6 py-3"
                    >
                        <p class="text-sm text-slate-500">
                            Page {{ transactions.current_page }} sur {{ transactions.last_page }}
                        </p>
                        <div class="flex gap-2">
                            <a
                                v-if="transactions.prev_page_url"
                                :href="transactions.prev_page_url"
                                class="inline-flex items-center gap-1 rounded-full border border-slate-300 bg-white px-3 py-1.5 text-sm font-medium text-slate-700 shadow-sm transition hover:bg-slate-50"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" />
                                </svg>
                                Precedent
                            </a>
                            <a
                                v-if="transactions.next_page_url"
                                :href="transactions.next_page_url"
                                class="inline-flex items-center gap-1 rounded-full border border-slate-300 bg-white px-3 py-1.5 text-sm font-medium text-slate-700 shadow-sm transition hover:bg-slate-50"
                            >
                                Suivant
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                                </svg>
                            </a>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <!-- ════════════════════════════════════════════════════
             MODAL — Depot / Recharge
        ════════════════════════════════════════════════════ -->
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
                    v-if="showDepositModal"
                    class="fixed inset-0 z-50 flex items-center justify-center p-4"
                >
                    <!-- Backdrop -->
                    <div
                        class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm"
                        @click="closeDepositModal"
                    />

                    <!-- Panel -->
                    <div class="relative w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl ring-1 ring-slate-200">

                        <!-- Close button -->
                        <button
                            type="button"
                            class="absolute right-4 top-4 rounded-lg p-1 text-slate-400 transition hover:bg-slate-100 hover:text-slate-600"
                            @click="closeDepositModal"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>

                        <!-- Header -->
                        <div class="flex items-center gap-3 mb-6">
                            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-purple-50">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-purple-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5z" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-lg font-semibold text-slate-900">Recharger le compte</h3>
                                <p class="text-sm text-slate-500">Depot securise</p>
                            </div>
                        </div>

                        <!-- ── Etat : popup ouverte ── -->
                        <div
                            v-if="paymentState === 'popup'"
                            class="flex flex-col items-center gap-4 py-6 text-center"
                        >
                            <div class="flex h-14 w-14 items-center justify-center rounded-full bg-purple-50">
                                <svg class="h-6 w-6 animate-spin text-purple-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-slate-800">Fenetre de paiement ouverte</p>
                                <p class="mt-1 text-xs text-slate-500">Completez votre paiement dans la fenetre popup.<br>Cette page se mettra a jour automatiquement.</p>
                            </div>
                            <button type="button" class="text-xs text-slate-400 underline hover:text-slate-600" @click="closeDepositModal">Annuler</button>
                        </div>

                        <!-- ── Etat : succes ── -->
                        <div
                            v-else-if="paymentState === 'success'"
                            class="flex flex-col items-center gap-4 py-6 text-center"
                        >
                            <div class="flex h-14 w-14 items-center justify-center rounded-full bg-emerald-50">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7 text-emerald-500" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-emerald-700">Paiement confirme !</p>
                                <p class="mt-1 text-xs text-slate-500">Votre solde a ete mis a jour.</p>
                            </div>
                        </div>

                        <!-- ── Formulaire (idle / loading / failed) ── -->
                        <form v-else @submit.prevent="submitDeposit" class="space-y-5">

                            <!-- Erreur generale -->
                            <div v-if="depositError" class="rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700">
                                {{ depositError }}
                            </div>

                            <!-- Montant souhaite -->
                            <div>
                                <label for="amount_target" class="block text-sm font-medium text-slate-700 mb-1">
                                    Montant souhaite (FCFA)
                                </label>
                                <input
                                    id="amount_target"
                                    v-model="depositAmountInput"
                                    type="number"
                                    min="1000"
                                    step="100"
                                    placeholder="Ex : 10000"
                                    :disabled="depositLoading"
                                    class="block w-full rounded-lg border-slate-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 sm:text-sm disabled:opacity-60"
                                    autofocus
                                />
                                <p v-if="depositAmountError" class="mt-1 text-sm text-red-600">
                                    {{ depositAmountError }}
                                </p>
                                <p class="mt-1 text-xs text-slate-400">Minimum : {{ formatCurrency(1000) }}</p>
                            </div>

                            <!-- Decomposition des frais -->
                            <Transition
                                enter-active-class="transition duration-200 ease-out"
                                enter-from-class="opacity-0 -translate-y-1"
                                enter-to-class="opacity-100 translate-y-0"
                            >
                                <div
                                    v-if="depositBreakdown"
                                    class="rounded-2xl border border-purple-100 bg-purple-50/20 p-4 space-y-2"
                                >
                                    <div class="flex justify-between text-sm">
                                        <span class="text-slate-600">Montant credite</span>
                                        <span class="font-medium text-slate-900">{{ formatCurrency(depositBreakdown.target) }}</span>
                                    </div>
                                    <div class="flex justify-between text-sm">
                                        <span class="text-slate-500">Frais de transaction (1.5%)</span>
                                        <span class="text-slate-600">+ {{ formatCurrency(depositBreakdown.gatewayFee) }}</span>
                                    </div>
                                    <div class="flex justify-between text-sm">
                                        <span class="text-slate-500">Frais MANTOTA (2%)</span>
                                        <span class="text-slate-600">+ {{ formatCurrency(depositBreakdown.mantotaMarkup) }}</span>
                                    </div>
                                    <div class="border-t border-slate-200 pt-2 flex justify-between text-sm font-semibold">
                                        <span class="text-slate-900">Total a payer</span>
                                        <span class="text-purple-600">{{ formatCurrency(depositBreakdown.total) }}</span>
                                    </div>
                                </div>
                            </Transition>

                            <!-- Submit -->
                            <div class="flex items-center gap-3 pt-1">
                                <button
                                    type="submit"
                                    :disabled="depositLoading || !depositBreakdown"
                                    class="flex-1 inline-flex items-center justify-center gap-2 rounded-full bg-gradient-to-r from-purple-500 to-violet-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:from-purple-600 hover:to-violet-700 disabled:opacity-50 disabled:cursor-not-allowed"
                                >
                                    <svg
                                        v-if="depositLoading"
                                        class="h-4 w-4 animate-spin"
                                        xmlns="http://www.w3.org/2000/svg"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                    >
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                                    </svg>
                                    {{ depositLoading ? 'Connexion en cours...' : 'Payer maintenant' }}
                                </button>
                                <button
                                    type="button"
                                    class="rounded-full border border-slate-300 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 shadow-sm transition hover:bg-slate-50"
                                    @click="closeDepositModal"
                                >
                                    Annuler
                                </button>
                            </div>
                        </form>

                        <!-- Info securite (visible seulement a l'etat initial) -->
                        <p v-if="paymentState === 'idle'" class="mt-4 flex items-center gap-1.5 text-xs text-slate-400">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" />
                            </svg>
                            Paiement dans une fenetre securisee. Cette page reste active.
                        </p>
                    </div>
                </div>
            </Transition>
        </Teleport>

        <!-- ════════════════════════════════════════════════════
             MODAL — Retrait / Mobile Money
        ════════════════════════════════════════════════════ -->
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
                    v-if="showWithdrawModal"
                    class="fixed inset-0 z-50 flex items-center justify-center p-4"
                >
                    <!-- Backdrop -->
                    <div
                        class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm"
                        @click="closeWithdrawModal"
                    />

                    <!-- Panel -->
                    <div class="relative w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl ring-1 ring-slate-200">

                        <!-- Close button -->
                        <button
                            type="button"
                            class="absolute right-4 top-4 rounded-lg p-1 text-slate-400 transition hover:bg-slate-100 hover:text-slate-600"
                            @click="closeWithdrawModal"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>

                        <!-- Header -->
                        <div class="flex items-center gap-3 mb-6">
                            <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-emerald-50">
                                <!-- Heroicon: banknotes -->
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75M15 10.5a3 3 0 11-6 0 3 3 0 016 0zm3 0h.008v.008H18V10.5zm-12 0h.008v.008H6V10.5z" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-lg font-semibold text-slate-900">Demander un retrait</h3>
                                <p class="text-sm text-slate-500">Mobile Money</p>
                            </div>
                        </div>

                        <!-- Solde disponible -->
                        <div class="mb-5 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3">
                            <p class="text-xs text-emerald-600">Solde disponible</p>
                            <p class="text-lg font-bold text-emerald-900">{{ formatCurrency(wallet?.balance ?? 0) }}</p>
                        </div>

                        <form @submit.prevent="submitWithdraw" class="space-y-5">

                            <!-- Montant -->
                            <div>
                                <label for="withdraw_amount" class="block text-sm font-medium text-slate-700 mb-1">
                                    Montant a retirer (FCFA)
                                </label>
                                <input
                                    id="withdraw_amount"
                                    v-model="withdrawForm.amount"
                                    type="number"
                                    min="1000"
                                    step="100"
                                    :max="parseFloat(wallet?.balance ?? 0)"
                                    placeholder="Ex : 5000"
                                    class="block w-full rounded-lg border-slate-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 sm:text-sm"
                                    autofocus
                                />
                                <p v-if="withdrawForm.errors.amount" class="mt-1 text-sm text-red-600">
                                    {{ withdrawForm.errors.amount }}
                                </p>
                                <p v-if="withdrawForm.errors.kyc" class="mt-1 text-sm text-red-600">
                                    {{ withdrawForm.errors.kyc }}
                                </p>
                                <p class="mt-1 text-xs text-slate-400">Minimum : {{ formatCurrency(min_withdrawal) }}</p>
                            </div>

                            <!-- Numero Mobile Money -->
                            <div>
                                <label for="momo_number" class="block text-sm font-medium text-slate-700 mb-1">
                                    Numero Mobile Money
                                </label>
                                <input
                                    id="momo_number"
                                    v-model="withdrawForm.momo_number"
                                    type="tel"
                                    placeholder="Ex : +22890123456"
                                    class="block w-full rounded-lg border-slate-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 sm:text-sm"
                                />
                                <p v-if="withdrawForm.errors.momo_number" class="mt-1 text-sm text-red-600">
                                    {{ withdrawForm.errors.momo_number }}
                                </p>
                                <p class="mt-1 text-xs text-slate-400">Format international (8 a 15 chiffres)</p>
                            </div>

                            <!-- Decomposition des frais de retrait -->
                            <Transition
                                enter-active-class="transition duration-200 ease-out"
                                enter-from-class="opacity-0 -translate-y-1"
                                enter-to-class="opacity-100 translate-y-0"
                            >
                                <div
                                    v-if="withdrawBreakdown"
                                    class="rounded-2xl border border-purple-100 bg-purple-50/20 p-4 space-y-2"
                                >
                                    <div class="flex justify-between text-sm">
                                        <span class="text-slate-600">Montant demande</span>
                                        <span class="font-medium text-slate-900">{{ formatCurrency(withdrawBreakdown.amount) }}</span>
                                    </div>
                                    <div class="flex justify-between text-sm">
                                        <span class="text-slate-500">Commission MANTOTA ({{ props.withdrawal_fee_percent }}%)</span>
                                        <span class="text-red-500">- {{ formatCurrency(withdrawBreakdown.mantotaCommission) }}</span>
                                    </div>
                                    <div class="flex justify-between text-sm">
                                        <span class="text-slate-500">Frais de transaction (1.5%)</span>
                                        <span class="text-red-500">- {{ formatCurrency(withdrawBreakdown.gatewayFee) }}</span>
                                    </div>
                                    <div class="border-t border-slate-200 pt-2 flex justify-between text-sm font-semibold">
                                        <span class="text-slate-900">Vous recevrez</span>
                                        <span class="text-emerald-600">{{ formatCurrency(withdrawBreakdown.netPayout) }}</span>
                                    </div>
                                </div>
                            </Transition>

                            <!-- Submit -->
                            <div class="flex items-center gap-3 pt-1">
                                <button
                                    type="submit"
                                    :disabled="withdrawForm.processing || !withdrawBreakdown"
                                    class="flex-1 inline-flex items-center justify-center gap-2 rounded-full bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-700 disabled:opacity-50 disabled:cursor-not-allowed"
                                >
                                    <svg
                                        v-if="withdrawForm.processing"
                                        class="h-4 w-4 animate-spin"
                                        xmlns="http://www.w3.org/2000/svg"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                    >
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                                    </svg>
                                    {{ withdrawForm.processing ? 'Envoi en cours...' : 'Soumettre la demande' }}
                                </button>
                                <button
                                    type="button"
                                    class="rounded-full border border-slate-300 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 shadow-sm transition hover:bg-slate-50"
                                    @click="closeWithdrawModal"
                                >
                                    Annuler
                                </button>
                            </div>
                        </form>

                        <!-- Info -->
                        <p class="mt-4 flex items-center gap-1.5 text-xs text-slate-400">
                            <!-- Heroicon: shield-check -->
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" />
                            </svg>
                            Retrait soumis a validation par l'administration. Delai : 24-48h.
                        </p>
                    </div>
                </div>
            </Transition>
        </Teleport>
    </VendorLayout>
</template>
