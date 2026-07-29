<script setup>
import InfluencerLayout from '../../Layouts/InfluencerLayout.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

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
        required: true,
    },
    min_withdrawal: {
        type: Number,
        default: 1000,
    },
    withdrawal_fee_percent: {
        type: Number,
        default: 20,
    },
    platform_commission_rate: {
        type: Number,
        default: 20,
    },
    gateway_fee_percent: {
        type: Number,
        default: 1.5,
    },
    momo_number: {
        type: String,
        default: '',
    },
});

const page = usePage();
const flashSuccess = computed(() => page.props.flash?.success ?? null);
const kycApproved = computed(() => props.kyc_status === 'approved');

const form = useForm({
    amount: '',
    momo_number: props.momo_number || '',
});

const withdrawalPreview = computed(() => {
    const amount = parseFloat(form.amount) || 0;
    if (amount < 1000) {
        return { commission: 0, gatewayFee: 0, netPayout: 0, valid: false };
    }

    const feePercentage = props.withdrawal_fee_percent / 100;
    const commission = Math.round(amount * feePercentage * 100) / 100;
    const afterCommission = Math.round((amount - commission) * 100) / 100;
    const gatewayFee = Math.round(afterCommission * (props.gateway_fee_percent / 100) * 100) / 100;
    const netPayout = Math.round((afterCommission - gatewayFee) * 100) / 100;

    return { commission, gatewayFee, netPayout, valid: true };
});

function submitWithdrawal() {
    form.post(route('influencer.wallet.withdraw'), {
        preserveScroll: true,
        onSuccess: () => form.reset(),
    });
}

function formatCurrency(amount) {
    return new Intl.NumberFormat('fr-FR', {
        style: 'decimal',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    }).format(amount) + ' FCFA';
}

function txStatusClasses(status) {
    const map = {
        pending: 'bg-amber-50 text-amber-700 ring-amber-600/20',
        completed: 'bg-emerald-50 text-emerald-700 ring-emerald-600/20',
        rejected: 'bg-red-50 text-red-700 ring-red-600/20',
        failed: 'bg-red-50 text-red-700 ring-red-600/20',
    };
    return map[status] ?? 'bg-slate-50 text-slate-600 ring-slate-500/20';
}

function txStatusLabel(status) {
    const map = {
        pending: 'En attente',
        completed: 'Valide',
        rejected: 'Rejete',
        failed: 'Echoue',
    };
    return map[status] ?? status;
}

function txTypeLabel(type) {
    const map = {
        deposit: 'Depot',
        withdrawal: 'Retrait',
        earning: 'Gain',
        fee: 'Frais',
    };
    return map[type] ?? type;
}
</script>

<template>
    <Head title="Mon portefeuille" />

    <InfluencerLayout>
        <template #header>
            <h2 class="text-xl font-bold leading-tight text-slate-800">
                Mon portefeuille
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8 space-y-8">

                <!-- Flash success -->
                <div
                    v-if="flashSuccess"
                    class="flex items-center gap-3 rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0 text-green-500" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                    </svg>
                    <span>{{ flashSuccess }}</span>
                </div>

                <!-- Cartes de solde -->
                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3">

                    <!-- Solde disponible : Gradient Turquoise -->
                    <div class="group overflow-hidden rounded-2xl bg-gradient-to-br from-teal-500 via-teal-600 to-cyan-700 px-5 py-6 shadow-lg shadow-teal-500/20 transition-all duration-500 hover:-translate-y-1 hover:shadow-xl hover:shadow-teal-500/30">
                        <div class="flex items-center gap-4">
                            <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-white/20">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 12a2.25 2.25 0 00-2.25-2.25H15a3 3 0 11-6 0H5.25A2.25 2.25 0 003 12m18 0v6a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 18v-6m18 0V9M3 12V9m18 0a2.25 2.25 0 00-2.25-2.25H5.25A2.25 2.25 0 013 9m18 0V6a2.25 2.25 0 00-2.25-2.25H5.25A2.25 2.25 0 013 6v3" />
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-teal-100">Solde disponible</p>
                                <p class="text-2xl font-bold text-white">{{ formatCurrency(wallet?.balance ?? 0) }}</p>
                            </div>
                        </div>
                        <p class="mt-3 text-xs text-teal-200">Gains CPC + CPA credites en temps reel</p>
                    </div>

                    <!-- Solde en attente : Gradient Violet -->
                    <div class="group relative overflow-hidden rounded-2xl bg-gradient-to-br from-purple-500 via-purple-600 to-violet-700 px-5 py-6 shadow-lg shadow-purple-500/20 transition-all duration-500 hover:-translate-y-1 hover:shadow-xl hover:shadow-purple-500/30">
                        <div class="flex items-center gap-4">
                            <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-white/20">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-purple-100">En attente</p>
                                <p class="text-2xl font-bold text-white">{{ formatCurrency(wallet?.escrow_balance ?? 0) }}</p>
                            </div>
                        </div>
                        <p class="mt-3 text-xs text-purple-200">
                            Commissions sur les commandes en cours de livraison
                        </p>
                        <!-- Tooltip info -->
                        <div class="absolute top-3 right-3 group">
                            <div class="flex h-6 w-6 items-center justify-center rounded-full bg-white/20 cursor-help">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-white" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" />
                                </svg>
                            </div>
                            <div class="invisible group-hover:visible absolute -left-32 top-8 z-10 w-64 rounded-xl border border-slate-200 bg-white p-3 text-xs text-slate-600 shadow-lg">
                                L'argent en attente correspond a vos commissions sur les commandes en cours de livraison. Il sera libere une fois la livraison confirmee.
                            </div>
                        </div>
                    </div>

                    <!-- Retrait en cours : Gradient Gris -->
                    <div class="group overflow-hidden rounded-2xl bg-gradient-to-br from-slate-500 via-slate-600 to-slate-700 px-5 py-6 shadow-lg shadow-slate-500/20 transition-all duration-500 hover:-translate-y-1 hover:shadow-xl hover:shadow-slate-500/30">
                        <div class="flex items-center gap-4">
                            <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-white/20">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-slate-200">Retrait en cours</p>
                                <p class="text-2xl font-bold text-white">{{ formatCurrency(wallet?.pending_balance ?? 0) }}</p>
                            </div>
                        </div>
                        <p class="mt-3 text-xs text-slate-300">Montant en attente de validation</p>
                    </div>
                </div>

                <!-- Zone de retrait -->
                <div class="rounded-2xl border border-slate-200/80 bg-white shadow-sm overflow-hidden">
                    <div class="px-6 py-5 border-b border-slate-100">
                        <h3 class="text-base font-semibold text-slate-900">Demander un retrait</h3>
                    </div>

                    <div class="px-6 py-6">

                        <!-- Banniere KYC -->
                        <div
                            v-if="!kycApproved"
                            class="flex items-start gap-4 rounded-xl border border-amber-200 bg-amber-50 px-5 py-4"
                        >
                            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-amber-100">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-amber-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                                </svg>
                            </div>
                            <div>
                                <h4 class="text-sm font-semibold text-amber-800">Verification d'identite requise</h4>
                                <p class="mt-1 text-sm text-amber-700">
                                    Vous devez faire verifier votre identite (KYC) avant de pouvoir effectuer un retrait.
                                    Rendez-vous dans vos parametres de profil pour soumettre vos documents.
                                </p>
                            </div>
                        </div>

                        <!-- Formulaire de retrait -->
                        <form
                            v-if="kycApproved"
                            @submit.prevent="submitWithdrawal"
                            class="space-y-5"
                        >
                            <div>
                                <label for="withdraw_amount" class="block text-sm font-medium text-slate-700 mb-1">
                                    Montant a retirer (FCFA)
                                </label>
                                <input
                                    id="withdraw_amount"
                                    v-model="form.amount"
                                    type="number"
                                    min="1000"
                                    step="100"
                                    placeholder="1000"
                                    class="block w-full rounded-xl border-slate-300 shadow-sm focus:border-teal-500 focus:ring-teal-500 sm:text-sm"
                                />
                                <p v-if="form.errors.amount" class="mt-1 text-sm text-red-600">{{ form.errors.amount }}</p>
                                <p v-if="form.errors.kyc" class="mt-1 text-sm text-red-600">{{ form.errors.kyc }}</p>
                            </div>

                            <!-- Numero Mobile Money -->
                            <div>
                                <label for="momo_number" class="block text-sm font-medium text-slate-700 mb-1">
                                    Numero Mobile Money
                                </label>
                                <input
                                    id="momo_number"
                                    v-model="form.momo_number"
                                    type="tel"
                                    placeholder="Ex : +22890123456"
                                    class="block w-full rounded-xl border-slate-300 shadow-sm focus:border-teal-500 focus:ring-teal-500 sm:text-sm"
                                />
                                <p v-if="form.errors.momo_number" class="mt-1 text-sm text-red-600">{{ form.errors.momo_number }}</p>
                                <p class="mt-1 text-xs text-slate-400">Format international (8 a 15 chiffres)</p>
                            </div>

                            <!-- Recapitulatif -->
                            <div
                                v-if="withdrawalPreview.valid"
                                class="rounded-2xl border border-teal-200/60 bg-gradient-to-br from-slate-50 to-teal-50/30 px-5 py-4 space-y-2"
                            >
                                <h4 class="text-sm font-semibold text-slate-700 mb-3">Recapitulatif</h4>
                                <div class="flex items-center justify-between text-sm">
                                    <span class="text-slate-500">Montant demande</span>
                                    <span class="font-medium text-slate-900">{{ formatCurrency(parseFloat(form.amount)) }}</span>
                                </div>
                                <div class="flex items-center justify-between text-sm">
                                    <span class="text-slate-500">Commission MANTOTA ({{ props.withdrawal_fee_percent }}%)</span>
                                    <span class="font-medium text-red-600">- {{ formatCurrency(withdrawalPreview.commission) }}</span>
                                </div>
                                <div class="flex items-center justify-between text-sm">
                                    <span class="text-slate-500">Frais de transfert (1.5%)</span>
                                    <span class="font-medium text-red-600">- {{ formatCurrency(withdrawalPreview.gatewayFee) }}</span>
                                </div>
                                <div class="border-t border-slate-200 pt-2 mt-2 flex items-center justify-between text-sm">
                                    <span class="font-semibold text-slate-700">Vous recevrez</span>
                                    <span class="text-lg font-bold text-teal-700">{{ formatCurrency(withdrawalPreview.netPayout) }}</span>
                                </div>
                            </div>

                            <div class="flex items-center gap-3 pt-1">
                                <button
                                    type="submit"
                                    :disabled="form.processing || !withdrawalPreview.valid"
                                    class="inline-flex items-center gap-2 rounded-full bg-gradient-to-r from-teal-500 to-cyan-500 px-5 py-2.5 text-sm font-semibold text-white shadow-md shadow-teal-500/20 transition-all duration-300 hover:shadow-lg hover:shadow-teal-500/30 hover:-translate-y-0.5 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-teal-600 disabled:opacity-50 disabled:cursor-not-allowed"
                                >
                                    <svg
                                        v-if="form.processing"
                                        class="h-4 w-4 animate-spin"
                                        xmlns="http://www.w3.org/2000/svg"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                    >
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                                    </svg>
                                    <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
                                    </svg>
                                    {{ form.processing ? 'Traitement en cours...' : 'Demander le retrait' }}
                                </button>
                            </div>
                        </form>

                        <!-- Formulaire grise si KYC non approuve -->
                        <div
                            v-if="!kycApproved"
                            class="mt-5 opacity-40 pointer-events-none select-none"
                        >
                            <label class="block text-sm font-medium text-slate-400 mb-1">Montant a retirer (FCFA)</label>
                            <input
                                type="number"
                                disabled
                                placeholder="1000"
                                class="block w-full rounded-xl border-slate-200 bg-slate-100 shadow-sm sm:text-sm cursor-not-allowed"
                            />
                            <div class="mt-4">
                                <button
                                    type="button"
                                    disabled
                                    class="inline-flex items-center gap-2 rounded-xl bg-teal-300 px-4 py-2.5 text-sm font-semibold text-white cursor-not-allowed"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
                                    </svg>
                                    Demander le retrait
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Historique des transactions -->
                <div class="rounded-2xl border border-slate-200/80 bg-white shadow-sm overflow-hidden">
                    <div class="px-6 py-5 border-b border-slate-100">
                        <h3 class="text-base font-semibold text-slate-900">Historique des transactions</h3>
                    </div>

                    <div
                        v-if="!transactions.data.length"
                        class="px-6 py-16 text-center"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="mx-auto h-12 w-12 text-slate-300" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75M15 10.5a3 3 0 11-6 0 3 3 0 016 0zm3 0h.008v.008H18V10.5zm-12 0h.008v.008H6V10.5z" />
                        </svg>
                        <h3 class="mt-4 text-sm font-semibold text-slate-900">Aucune transaction</h3>
                        <p class="mt-1 text-sm text-slate-500">Vos transactions apparaitront ici une fois que vous aurez commence a generer des revenus.</p>
                    </div>

                    <table v-else class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-gradient-to-r from-slate-50 to-teal-50/20">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Reference</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Type</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Description</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Montant</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Statut</th>
                                <th scope="col" class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-500">Date</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr
                                v-for="tx in transactions.data"
                                :key="tx.id"
                                class="transition hover:bg-slate-50/60"
                            >
                                <td class="whitespace-nowrap px-6 py-4">
                                    <span class="text-xs font-mono text-slate-500">{{ tx.reference }}</span>
                                </td>
                                <td class="whitespace-nowrap px-6 py-4">
                                    <div class="flex items-center gap-2">
                                        <svg
                                            v-if="tx.type === 'earning' || tx.type === 'deposit'"
                                            xmlns="http://www.w3.org/2000/svg"
                                            class="h-4 w-4 text-teal-500"
                                            fill="none"
                                            viewBox="0 0 24 24"
                                            stroke-width="1.5"
                                            stroke="currentColor"
                                        >
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 13.5L12 21m0 0l-7.5-7.5M12 21V3" />
                                        </svg>
                                        <svg
                                            v-else
                                            xmlns="http://www.w3.org/2000/svg"
                                            class="h-4 w-4 text-red-500"
                                            fill="none"
                                            viewBox="0 0 24 24"
                                            stroke-width="1.5"
                                            stroke="currentColor"
                                        >
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 10.5L12 3m0 0l7.5 7.5M12 3v18" />
                                        </svg>
                                        <span class="text-sm font-medium text-slate-700">{{ txTypeLabel(tx.type) }}</span>
                                    </div>
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-slate-500">
                                    {{ tx.description || txTypeLabel(tx.type) }}
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm font-medium text-slate-900">
                                    {{ formatCurrency(tx.amount_target) }}
                                </td>
                                <td class="whitespace-nowrap px-6 py-4">
                                    <span
                                        :class="txStatusClasses(tx.status)"
                                        class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 ring-inset"
                                    >
                                        {{ txStatusLabel(tx.status) }}
                                    </span>
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-right text-sm text-slate-500">
                                    {{ new Date(tx.created_at).toLocaleDateString('fr-FR', { day: '2-digit', month: 'short', year: 'numeric' }) }}
                                </td>
                            </tr>
                        </tbody>
                    </table>

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
                                class="inline-flex items-center gap-1 rounded-full border border-teal-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 shadow-sm transition-all duration-300 hover:bg-teal-50 hover:border-teal-300 hover:-translate-y-0.5"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" />
                                </svg>
                                Precedent
                            </a>
                            <a
                                v-if="transactions.next_page_url"
                                :href="transactions.next_page_url"
                                class="inline-flex items-center gap-1 rounded-full border border-teal-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 shadow-sm transition-all duration-300 hover:bg-teal-50 hover:border-teal-300 hover:-translate-y-0.5"
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
    </InfluencerLayout>
</template>
