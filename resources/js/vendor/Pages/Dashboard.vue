<script setup>
import VendorLayout from '../Layouts/VendorLayout.vue';
import { Head, useForm, usePage, Link, router } from '@inertiajs/vue3';
import { computed, ref, onUnmounted, onMounted } from 'vue';
import axios from 'axios';

/**
 * Props transmises par Vendor\DashboardController@index.
 *
 * - wallet       : Objet Wallet du vendor (balance, pending_balance, escrow_balance).
 * - stats        : { active_campaigns, total_spent, total_clicks, paid_clicks, click_rate, total_partner_sales, available_balance, escrow_balance }.
 * - campaigns    : Collection paginee des campagnes (avec smart_links_count).
 * - transactions : 10 dernières transactions du vendor.
 * - kyc_status   : Statut KYC du vendor (not_submitted | pending | approved | rejected).
 */
const props = defineProps({
    wallet: {
        type: Object,
        default: null,
    },
    stats: {
        type: Object,
        required: true,
    },
    campaigns: {
        type: Object,
        required: true,
    },
    transactions: {
        type: Array,
        default: () => [],
    },
    kyc_status: {
        type: String,
        default: 'not_submitted',
    },
    kyc_rejection_reason: {
        type: String,
        default: null,
    },
    deposit_markup_percent: {
        type: Number,
        default: 1.5,
    },
    gateway_fee_percent: {
        type: Number,
        default: 1.5,
    },
    ambassadors: {
        type: Array,
        default: () => [],
    },
});

const page = usePage();
const flashSuccess = computed(() => page.props.flash?.success ?? null);

// ── KYC gate ──
const isKycApproved = computed(() => props.kyc_status === 'approved');

// ── Popup rejet KYC ──
const showKycRejectedPopup = ref(false);
onMounted(() => {
    if (props.kyc_status === 'rejected') {
        showKycRejectedPopup.value = true;
    }
});

// ── Dépôt modal ──
const showDepositModal   = ref(false);
const depositLoading     = ref(false);
const depositError       = ref('');
const depositAmountError = ref('');
const depositAmountInput = ref('');
const paymentState       = ref('idle'); // idle | loading | popup | success | failed
const paymentPopup       = ref(null);
const pollTimer          = ref(null);

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

        const w = 520, h = 720;
        const left = Math.round(window.screenX + (window.outerWidth  - w) / 2);
        const top  = Math.round(window.screenY + (window.outerHeight - h) / 2);
        paymentPopup.value = window.open(
            payment_url,
            'mantota_payment',
            `width=${w},height=${h},left=${left},top=${top},menubar=no,toolbar=no,location=yes,status=no,scrollbars=yes`
        );

        paymentState.value = 'popup';

        pollTimer.value = setInterval(async () => {
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
                    setTimeout(() => router.reload({ only: ['wallet', 'stats', 'transactions'] }), 800);
                } else if (check.data.status === 'failed') {
                    clearInterval(pollTimer.value);
                    if (paymentPopup.value && !paymentPopup.value.closed) paymentPopup.value.close();
                    paymentState.value   = 'failed';
                    depositLoading.value = false;
                }
            } catch (_) {}
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
    showDepositModal.value   = false;
    depositAmountInput.value = '';
    depositAmountError.value = '';
    depositError.value       = '';
    depositLoading.value     = false;
    paymentState.value       = 'idle';
}

onUnmounted(() => { clearInterval(pollTimer.value); });

/**
 * Formate un montant numerique en FCFA lisible.
 */
function formatCurrency(amount) {
    return new Intl.NumberFormat('fr-FR', {
        style: 'decimal',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    }).format(Number(amount ?? 0)) + ' FCFA';
}

/**
 * Retourne les classes Tailwind du badge de statut.
 */
function statusBadgeClasses(status) {
    const map = {
        active:  'bg-emerald-50 text-emerald-700 ring-emerald-600/20',
        draft:   'bg-slate-50 text-slate-700 ring-slate-600/20',
        paused:  'bg-amber-50 text-amber-700 ring-amber-600/20',
        expired: 'bg-red-50 text-red-700 ring-red-600/20',
    };
    return map[status] ?? 'bg-slate-50 text-slate-700 ring-slate-600/20';
}

/**
 * Retourne le libelle lisible du statut.
 */
function statusLabel(status) {
    const map = {
        active:  'Active',
        draft:   'Brouillon',
        paused:  'En pause',
        expired: 'Expiree',
    };
    return map[status] ?? status;
}

/**
 * Retourne le libelle et les classes du badge KYC.
 */
function kycBadge() {
    const map = {
        approved:      { label: 'KYC verifie',   classes: 'bg-emerald-50 text-emerald-700 ring-emerald-600/20' },
        pending:       { label: 'KYC en attente', classes: 'bg-amber-50 text-amber-700 ring-amber-600/20' },
        rejected:      { label: 'KYC refuse',     classes: 'bg-red-50 text-red-700 ring-red-600/20' },
        not_submitted: { label: 'KYC requis',     classes: 'bg-slate-100 text-slate-600 ring-slate-500/20' },
    };
    return map[props.kyc_status] ?? map.not_submitted;
}
</script>

<template>
    <Head title="Tableau de bord vendeur" />

    <!-- ── Popup Rejet KYC ── -->
    <Teleport to="body">
        <div v-if="showKycRejectedPopup" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 px-4">
            <div class="w-full max-w-md rounded-2xl bg-white shadow-2xl">
                <div class="flex items-center gap-3 rounded-t-2xl bg-red-600 px-6 py-4">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 shrink-0 text-white" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                    </svg>
                    <h3 class="text-base font-bold text-white">Verification KYC rejetee</h3>
                </div>
                <div class="px-6 py-5 space-y-4">
                    <p class="text-sm text-slate-700">
                        Votre dossier de verification d'identite a ete examine et <strong>refuse</strong> par notre equipe de moderation.
                    </p>
                    <div v-if="kyc_rejection_reason" class="rounded-lg border border-red-200 bg-red-50 px-4 py-3">
                        <p class="text-xs font-semibold text-red-700 mb-1">Raison du refus :</p>
                        <p class="text-sm text-red-800">{{ kyc_rejection_reason }}</p>
                    </div>
                    <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3">
                        <p class="text-xs font-semibold text-amber-700 mb-1">Que faire ?</p>
                        <ul class="text-xs text-amber-800 space-y-1 list-disc list-inside">
                            <li>Verifiez que vos documents sont lisibles et au format correct.</li>
                            <li>Assurez-vous que la photo d'identite correspond a vos informations.</li>
                            <li>Re-soumettez un nouveau dossier en cliquant sur le bouton ci-dessous.</li>
                        </ul>
                    </div>
                    <p class="text-xs text-slate-400">Un email detaillant ces informations vous a ete envoye.</p>
                </div>
                <div class="flex justify-end gap-3 border-t border-slate-100 px-6 py-4">
                    <button @click="showKycRejectedPopup = false" class="rounded-lg border border-slate-200 px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50">
                        Fermer
                    </button>
                    <Link :href="route('vendor.kyc.index')" @click="showKycRejectedPopup = false" class="rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-red-700">
                        Soumettre a nouveau
                    </Link>
                </div>
            </div>
        </div>
    </Teleport>

    <VendorLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-bold leading-tight text-slate-800">
                    Tableau de bord
                </h2>
                <span
                    :class="kycBadge().classes"
                    class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 ring-inset"
                >
                    {{ kycBadge().label }}
                </span>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8 space-y-8">

                <!-- ────────────────────────────────────────────
                     Flash success
                ──────────────────────────────────────────── -->
                <div
                    v-if="flashSuccess"
                    class="flex items-center gap-3 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0 text-green-500" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                    </svg>
                    <span>{{ flashSuccess }}</span>
                </div>

                <!-- ────────────────────────────────────────────
                     Banniere KYC (si non approuve)
                ──────────────────────────────────────────── -->
                <div
                    v-if="!isKycApproved"
                    class="flex flex-col gap-3 rounded-2xl border border-amber-200/60 bg-gradient-to-r from-amber-50 to-orange-50/50 p-5 shadow-sm sm:flex-row sm:items-center sm:justify-between"
                >
                    <div class="flex items-start gap-3">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 shrink-0 text-amber-500 mt-0.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                        </svg>
                        <div>
                            <p class="text-sm font-semibold text-amber-800">Verification KYC requise</p>
                            <p class="mt-0.5 text-sm text-amber-700">
                                <template v-if="kyc_status === 'not_submitted'">
                                    Soumettez vos documents d'identite pour pouvoir creer des campagnes.
                                </template>
                                <template v-else-if="kyc_status === 'pending'">
                                    Vos documents sont en cours de verification. Vous serez notifie une fois le processus termine.
                                </template>
                                <template v-else-if="kyc_status === 'rejected'">
                                    Vos documents ont ete refuses. Veuillez les soumettre a nouveau.
                                </template>
                            </p>
                        </div>
                    </div>
                    <Link
                        v-if="kyc_status !== 'pending'"
                        :href="route('vendor.kyc.index')"
                        class="inline-flex items-center gap-2 rounded-lg bg-amber-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-amber-700 shrink-0"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />
                        </svg>
                        Soumettre mes documents
                    </Link>
                </div>

                <!-- ────────────────────────────────────────────
                     Header Stats — 7 KPI (spec vendeur)
                ──────────────────────────────────────────── -->
                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">

                    <!-- Campagnes actives -->
                    <div class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white px-5 py-5 shadow-sm transition-all duration-500 hover:-translate-y-2 hover:shadow-xl hover:shadow-purple-500/5">
                        <div class="flex items-center gap-4">
                            <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-purple-50">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-purple-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z" />
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-slate-500">Campagnes actives</p>
                                <p class="text-2xl font-bold text-slate-900">{{ stats.active_campaigns }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Total depense -->
                    <div class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white px-5 py-5 shadow-sm transition-all duration-500 hover:-translate-y-2 hover:shadow-xl hover:shadow-purple-500/5">
                        <div class="flex items-center gap-4">
                            <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-violet-50">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-violet-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75M15 10.5a3 3 0 11-6 0 3 3 0 016 0zm3 0h.008v.008H18V10.5zm-12 0h.008v.008H6V10.5z" />
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-slate-500">Total depense</p>
                                <p class="text-2xl font-bold text-slate-900">{{ formatCurrency(stats.total_spent) }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Total clics -->
                    <div class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white px-5 py-5 shadow-sm transition-all duration-500 hover:-translate-y-2 hover:shadow-xl hover:shadow-purple-500/5">
                        <div class="flex items-center gap-4">
                            <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-purple-50">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-purple-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z" />
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-slate-500">Total clics</p>
                                <p class="text-2xl font-bold text-slate-900">{{ (stats.total_clicks ?? 0).toLocaleString('fr-FR') }}</p>
                                <p class="text-xs text-slate-400 mt-0.5">{{ (stats.paid_clicks ?? 0).toLocaleString('fr-FR') }} clics payes</p>
                            </div>
                        </div>
                    </div>

                    <!-- Taux de clic (clics payes / totaux) -->
                    <div class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white px-5 py-5 shadow-sm transition-all duration-500 hover:-translate-y-2 hover:shadow-xl hover:shadow-purple-500/5">
                        <div class="flex items-center gap-4">
                            <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-cyan-50">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-cyan-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 14.25v2.25m3-4.5v4.5m3-6.75v6.75m3-9v9M6 20.25h12A2.25 2.25 0 0020.25 18V6A2.25 2.25 0 0018 3.75H6A2.25 2.25 0 003.75 6v12A2.25 2.25 0 006 20.25z" />
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-slate-500">Taux de clic</p>
                                <p class="text-2xl font-bold text-slate-900">{{ stats.click_rate }}%</p>
                                <p class="text-xs text-slate-400 mt-0.5">Clics payes / totaux</p>
                            </div>
                        </div>
                    </div>

                    <!-- Achats par affiliation -->
                    <div class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white px-5 py-5 shadow-sm transition-all duration-500 hover:-translate-y-2 hover:shadow-xl hover:shadow-purple-500/5">
                        <div class="flex items-center gap-4">
                            <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-amber-50">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-amber-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 00-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 00-16.536-1.84M7.5 14.25L5.106 5.272M6 20.25a.75.75 0 11-1.5 0 .75.75 0 011.5 0zm12.75 0a.75.75 0 11-1.5 0 .75.75 0 011.5 0z" />
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-slate-500">Achats (affiliation)</p>
                                <p class="text-2xl font-bold text-slate-900">{{ (stats.total_partner_sales ?? 0).toLocaleString('fr-FR') }}</p>
                                <p class="text-xs text-slate-400 mt-0.5">Commandes generees</p>
                            </div>
                        </div>
                    </div>

                    <!-- Taux de conversion (clics → achats) -->
                    <div class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white px-5 py-5 shadow-sm transition-all duration-500 hover:-translate-y-2 hover:shadow-xl hover:shadow-purple-500/5">
                        <div class="flex items-center gap-4">
                            <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-emerald-50">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" />
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-slate-500">Taux conversion</p>
                                <p class="text-2xl font-bold text-slate-900">{{ stats.total_clicks > 0 ? ((stats.total_partner_sales / stats.total_clicks) * 100).toFixed(1) + '%' : '—' }}</p>
                                <p class="text-xs text-slate-400 mt-0.5">Clics → achats</p>
                            </div>
                        </div>
                    </div>

                    <!-- Solde disponible + escrow -->
                    <div class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white px-5 py-5 shadow-sm transition-all duration-500 hover:-translate-y-2 hover:shadow-xl hover:shadow-purple-500/5">
                        <div class="flex items-center gap-4">
                            <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-purple-50">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-purple-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 12a2.25 2.25 0 00-2.25-2.25H15a3 3 0 11-6 0H5.25A2.25 2.25 0 003 12m18 0v6a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 18v-6m18 0V9M3 12V9m18 0a2.25 2.25 0 00-2.25-2.25H5.25A2.25 2.25 0 013 9m18 0V6a2.25 2.25 0 00-2.25-2.25H5.25A2.25 2.25 0 013 6v3" />
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-slate-500">Solde disponible</p>
                                <p class="text-2xl font-bold text-slate-900">{{ formatCurrency(stats.available_balance) }}</p>
                                <p class="text-xs text-slate-400 mt-0.5">Escrow : {{ formatCurrency(stats.escrow_balance) }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ────────────────────────────────────────────
                     Actions — Raccourcis rapides
                ──────────────────────────────────────────── -->
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center">

                    <!-- Creer une campagne — KYC gate -->
                    <Link
                        v-if="isKycApproved"
                        :href="route('vendor.campaigns.index')"
                        class="inline-flex items-center gap-2 rounded-full bg-gradient-to-r from-purple-500 to-violet-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition-all duration-300 hover:from-purple-600 hover:to-violet-700 hover:-translate-y-0.5 hover:shadow-md focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-violet-600"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                        </svg>
                        Creer une campagne
                    </Link>

                    <!-- Creer campagne — KYC non approuve : lien vers KYC -->
                    <Link
                        v-else
                        :href="route('vendor.kyc.index')"
                        class="inline-flex items-center gap-2 rounded-full bg-slate-300 px-5 py-2.5 text-sm font-semibold text-slate-600 shadow-sm cursor-not-allowed"
                        title="Completez votre KYC pour creer une campagne"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
                        </svg>
                        Creer une campagne (KYC requis)
                    </Link>

                    <!-- Ajouter un produit -->
                    <Link
                        :href="route('vendor.products.create')"
                        class="inline-flex items-center gap-2 rounded-full border border-emerald-300 bg-emerald-50 px-5 py-2.5 text-sm font-semibold text-emerald-700 shadow-sm transition-all duration-300 hover:bg-emerald-100 hover:-translate-y-0.5 hover:shadow-md focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-400"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 10-7.5 0v4.5m11.356-1.993l1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 01-1.12-1.243l1.264-12A1.125 1.125 0 015.513 7.5h12.974c.576 0 1.059.435 1.119 1.007zM8.625 10.5a.375.375 0 11-.75 0 .375.375 0 01.75 0zm7.5 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" />
                        </svg>
                        Ajouter un produit
                    </Link>

                    <!-- Recharger le compte — ouvre le modal depot -->
                    <button
                        type="button"
                        class="inline-flex items-center gap-2 rounded-full border border-slate-300 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition-all duration-300 hover:bg-slate-50 hover:-translate-y-0.5 hover:shadow-md focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-slate-400"
                        @click="showDepositModal = true"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-slate-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5z" />
                        </svg>
                        Recharger le compte
                    </button>

                    <!-- Voir mon portefeuille -->
                    <Link
                        :href="route('vendor.wallet.index')"
                        class="inline-flex items-center gap-2 rounded-full border border-slate-300 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition-all duration-300 hover:bg-slate-50 hover:-translate-y-0.5 hover:shadow-md"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-slate-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 12a2.25 2.25 0 00-2.25-2.25H15a3 3 0 11-6 0H5.25A2.25 2.25 0 003 12m18 0v6a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 18v-6m18 0V9M3 12V9m18 0a2.25 2.25 0 00-2.25-2.25H5.25A2.25 2.25 0 013 9m18 0V6a2.25 2.25 0 00-2.25-2.25H5.25A2.25 2.25 0 013 6v3" />
                        </svg>
                        Mon portefeuille
                    </Link>
                </div>

                <!-- ────────────────────────────────────────────
                     Tableau des campagnes
                ──────────────────────────────────────────── -->
                <div class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">

                    <!-- Empty state -->
                    <div
                        v-if="!campaigns.data.length"
                        class="px-10 py-20 text-center"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="mx-auto h-12 w-12 text-slate-300" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                        </svg>
                        <h3 class="mt-4 text-sm font-semibold text-slate-900">Aucune campagne</h3>
                        <p class="mt-1 text-sm text-slate-500">Creez votre premiere campagne pour commencer a toucher des createurs de contenu.</p>
                        <Link
                            v-if="isKycApproved"
                            :href="route('vendor.campaigns.index')"
                            class="mt-6 inline-flex items-center gap-2 rounded-lg bg-gradient-to-r from-purple-500 to-violet-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:from-purple-600 hover:to-violet-700"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                            </svg>
                            Creer une campagne
                        </Link>
                        <Link
                            v-else
                            :href="route('vendor.kyc.index')"
                            class="mt-6 inline-flex items-center gap-2 rounded-lg bg-amber-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-amber-700"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />
                            </svg>
                            Completer votre KYC d'abord
                        </Link>
                    </div>

                    <!-- Table -->
                    <table v-else class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-gradient-to-r from-slate-50 to-purple-50/30">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                    Titre
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                    Budget
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                    Statut
                                </th>
                                <th scope="col" class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-500">
                                    Liens generes
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr
                                v-for="campaign in campaigns.data"
                                :key="campaign.id"
                                class="transition hover:bg-slate-50/60"
                            >
                                <td class="whitespace-nowrap px-6 py-4">
                                    <div class="text-sm font-medium text-slate-900">{{ campaign.title }}</div>
                                    <div class="text-xs text-slate-400 truncate max-w-xs">{{ campaign.target_url }}</div>
                                </td>
                                <td class="whitespace-nowrap px-6 py-4">
                                    <div class="text-sm font-medium text-slate-700">{{ formatCurrency(campaign.total_budget) }}</div>
                                    <div class="mt-1.5 w-24">
                                        <div class="h-1.5 w-full rounded-full bg-slate-200">
                                            <div
                                                class="h-1.5 rounded-full bg-purple-500 transition-all duration-500"
                                                :style="{ width: Math.min(100, campaign.total_budget > 0 ? ((campaign.spent_budget || 0) / campaign.total_budget) * 100 : 0) + '%' }"
                                            />
                                        </div>
                                    </div>
                                </td>
                                <td class="whitespace-nowrap px-6 py-4">
                                    <span
                                        :class="statusBadgeClasses(campaign.status)"
                                        class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 ring-inset"
                                    >
                                        {{ statusLabel(campaign.status) }}
                                    </span>
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-right">
                                    <span class="inline-flex items-center gap-1.5 text-sm text-slate-700">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.19 8.688a4.5 4.5 0 011.242 7.244l-4.5 4.5a4.5 4.5 0 01-6.364-6.364l1.757-1.757m9.86-2.04a4.5 4.5 0 00-1.242-7.244l-4.5-4.5a4.5 4.5 0 00-6.364 6.364L4.343 8.28" />
                                        </svg>
                                        {{ campaign.smart_links_count }}
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <!-- Pagination -->
                    <div
                        v-if="campaigns.data.length && campaigns.last_page > 1"
                        class="flex items-center justify-between border-t border-slate-200 bg-white px-6 py-3"
                    >
                        <p class="text-sm text-slate-500">
                            Page {{ campaigns.current_page }} sur {{ campaigns.last_page }}
                        </p>
                        <div class="flex gap-2">
                            <a
                                v-if="campaigns.prev_page_url"
                                :href="campaigns.prev_page_url"
                                class="inline-flex items-center gap-1 rounded-full border border-purple-200 bg-white px-3 py-1.5 text-sm font-medium text-purple-700 shadow-sm transition hover:bg-purple-50"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" />
                                </svg>
                                Precedent
                            </a>
                            <a
                                v-if="campaigns.next_page_url"
                                :href="campaigns.next_page_url"
                                class="inline-flex items-center gap-1 rounded-full border border-purple-200 bg-white px-3 py-1.5 text-sm font-medium text-purple-700 shadow-sm transition hover:bg-purple-50"
                            >
                                Suivant
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                                </svg>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- ────────────────────────────────────────────
                     Dernieres transactions
                ──────────────────────────────────────────── -->
                <div v-if="transactions.length" class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
                    <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200">
                        <h3 class="text-sm font-semibold text-slate-900">Dernieres transactions</h3>
                        <Link
                            :href="route('vendor.wallet.index')"
                            class="text-xs font-medium text-purple-600 hover:text-purple-700"
                        >
                            Voir tout &rarr;
                        </Link>
                    </div>
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-gradient-to-r from-slate-50 to-purple-50/30">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Type</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Montant</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Statut</th>
                                <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-500">Reference</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="tx in transactions" :key="tx.id" class="transition hover:bg-slate-50/60">
                                <td class="whitespace-nowrap px-6 py-3 text-sm">
                                    <span
                                        class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium ring-1 ring-inset"
                                        :class="{
                                            'bg-green-50 text-green-700 ring-green-600/20': tx.type === 'deposit',
                                            'bg-purple-50 text-purple-700 ring-purple-600/20': tx.type === 'earning',
                                            'bg-red-50 text-red-700 ring-red-600/20': tx.type === 'withdrawal',
                                            'bg-slate-50 text-slate-700 ring-slate-600/20': tx.type === 'fee',
                                        }"
                                    >
                                        {{ tx.type === 'deposit' ? 'Depot' : tx.type === 'earning' ? 'Gain' : tx.type === 'withdrawal' ? 'Retrait' : 'Frais' }}
                                    </span>
                                </td>
                                <td class="whitespace-nowrap px-6 py-3 text-sm font-medium text-slate-900">
                                    {{ formatCurrency(tx.amount_target) }}
                                </td>
                                <td class="whitespace-nowrap px-6 py-3 text-sm">
                                    <span
                                        class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium ring-1 ring-inset"
                                        :class="{
                                            'bg-emerald-50 text-emerald-700 ring-emerald-600/20': tx.status === 'completed',
                                            'bg-amber-50 text-amber-700 ring-amber-600/20': tx.status === 'pending',
                                            'bg-red-50 text-red-700 ring-red-600/20': tx.status === 'failed',
                                        }"
                                    >
                                        {{ tx.status === 'completed' ? 'Complete' : tx.status === 'pending' ? 'En attente' : 'Echoue' }}
                                    </span>
                                </td>
                                <td class="whitespace-nowrap px-6 py-3 text-right text-xs text-slate-400 font-mono">
                                    {{ tx.reference }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

            </div>
        </div>

        <!-- ══════ AMBASSADEURS CAROUSEL ══════ -->
        <div v-if="ambassadors && ambassadors.length" class="mt-8 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="flex items-center gap-3 border-b border-slate-100 px-6 py-4">
                <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-cyan-50">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-cyan-600" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="M8.603 3.799A4.49 4.49 0 0112 2.25c1.357 0 2.573.6 3.397 1.549a4.49 4.49 0 013.498 1.307 4.491 4.491 0 011.307 3.497A4.49 4.49 0 0121.75 12a4.49 4.49 0 01-1.549 3.397 4.491 4.491 0 01-1.307 3.497 4.491 4.491 0 01-3.497 1.307A4.49 4.49 0 0112 21.75a4.49 4.49 0 01-3.397-1.549 4.49 4.49 0 01-3.498-1.306 4.491 4.491 0 01-1.307-3.498A4.49 4.49 0 012.25 12c0-1.357.6-2.573 1.549-3.397a4.49 4.49 0 011.307-3.497 4.49 4.49 0 013.497-1.307zm7.007 6.387a.75.75 0 10-1.22-.872l-3.236 4.53L9.53 12.22a.75.75 0 00-1.06 1.06l2.25 2.25a.75.75 0 001.14-.094l3.75-5.25z" clip-rule="evenodd" /></svg>
                </div>
                <h3 class="text-sm font-semibold text-slate-900">Ambassadeurs MANTOTA</h3>
            </div>
            <div class="flex gap-4 overflow-x-auto px-6 py-4 scrollbar-thin scrollbar-thumb-slate-200">
                <div v-for="amb in ambassadors" :key="amb.id" class="flex shrink-0 items-center gap-3 rounded-xl border border-slate-100 bg-slate-50 px-4 py-3">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center overflow-hidden rounded-full bg-gradient-to-br from-cyan-500 to-teal-600 text-xs font-bold text-white">
                        <img v-if="amb.profile_photo" :src="'/storage/' + amb.profile_photo" :alt="amb.name" class="h-full w-full object-cover" />
                        <span v-else>{{ amb.name?.charAt(0)?.toUpperCase() }}</span>
                    </div>
                    <div class="min-w-0">
                        <p class="truncate text-sm font-medium text-slate-800">{{ amb.shop_name || amb.business_name || amb.name }}</p>
                        <span class="flex items-center gap-1 text-xs text-cyan-600">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="M8.603 3.799A4.49 4.49 0 0112 2.25c1.357 0 2.573.6 3.397 1.549a4.49 4.49 0 013.498 1.307 4.491 4.491 0 011.307 3.497A4.49 4.49 0 0121.75 12a4.49 4.49 0 01-1.549 3.397 4.491 4.491 0 01-1.307 3.497 4.491 4.491 0 01-3.497 1.307A4.49 4.49 0 0112 21.75a4.49 4.49 0 01-3.397-1.549 4.49 4.49 0 01-3.498-1.306 4.491 4.491 0 01-1.307-3.498A4.49 4.49 0 012.25 12c0-1.357.6-2.573 1.549-3.397a4.49 4.49 0 011.307-3.497 4.49 4.49 0 013.497-1.307zm7.007 6.387a.75.75 0 10-1.22-.872l-3.236 4.53L9.53 12.22a.75.75 0 00-1.06 1.06l2.25 2.25a.75.75 0 001.14-.094l3.75-5.25z" clip-rule="evenodd" /></svg>
                            Ambassadeur
                        </span>
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
                                <p class="mt-1 text-xs text-slate-400">Minimum : 1 000 FCFA</p>
                            </div>

                            <!-- Decomposition des frais -->
                            <Transition
                                enter-active-class="transition duration-200 ease-out"
                                enter-from-class="opacity-0 -translate-y-1"
                                enter-to-class="opacity-100 translate-y-0"
                            >
                                <div
                                    v-if="depositBreakdown"
                                    class="rounded-2xl border border-purple-100 bg-purple-50/30 p-4 space-y-2"
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
                                    class="flex-1 inline-flex items-center justify-center gap-2 rounded-lg bg-gradient-to-r from-purple-500 to-violet-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:from-purple-600 hover:to-violet-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-violet-600 disabled:opacity-50 disabled:cursor-not-allowed"
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
    </VendorLayout>
</template>
