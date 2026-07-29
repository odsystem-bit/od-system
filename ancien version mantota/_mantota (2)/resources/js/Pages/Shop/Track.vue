<script setup>
import { Head, router, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({
    order: { type: Object, required: true },
    token: { type: String, required: true },
});

const page = usePage();
const flashSuccess = computed(() => page.props.flash?.success ?? null);
const flashErrors  = computed(() => page.props.errors ?? {});

const isDigital = computed(() => props.order.product?.type === 'digital');

const showConfirmModal = ref(false);
const processing       = ref(false);
const pinCode          = ref('');

function formatCurrency(amount) {
    const v = parseFloat(amount);
    if (!v || v < 0) return '0 FCFA';
    return new Intl.NumberFormat('fr-FR', {
        style: 'decimal',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    }).format(v) + ' FCFA';
}

function formatDate(dateStr) {
    if (!dateStr) return null;
    return new Date(dateStr).toLocaleDateString('fr-FR', {
        day: '2-digit', month: 'long', year: 'numeric', hour: '2-digit', minute: '2-digit',
    });
}

const statusConfig = {
    pending:   { label: 'En attente d\'expedition', bgClass: 'bg-amber-100 text-amber-700', iconColor: 'text-amber-500' },
    shipped:   { label: 'Expediee — en route',      bgClass: 'bg-teal-100 text-teal-700',   iconColor: 'text-teal-500' },
    delivered: { label: 'Livree — fonds liberes',    bgClass: 'bg-emerald-100 text-emerald-700', iconColor: 'text-emerald-500' },
    disputed:  { label: 'Litige en cours',           bgClass: 'bg-red-100 text-red-700',     iconColor: 'text-red-500' },
    cancelled: { label: 'Annulee',                   bgClass: 'bg-slate-100 text-slate-500', iconColor: 'text-slate-400' },
};

const canConfirm = computed(() => props.order.status === 'shipped');
const canDispute = computed(() => ['pending', 'shipped'].includes(props.order.status));

function openConfirmModal() {
    pinCode.value = '';
    showConfirmModal.value = true;
}

function closeConfirmModal() {
    showConfirmModal.value = false;
}

function submitConfirmation() {
    processing.value = true;
    router.post(route('order.track.confirm', props.order.id), {
        token: props.token,
        pin: pinCode.value,
    }, {
        preserveScroll: true,
        onSuccess: () => {
            closeConfirmModal();
        },
        onFinish: () => {
            processing.value = false;
        },
    });
}

// ── Dispute ──
const showDisputeModal = ref(false);
const disputeReason    = ref('');
const disputeProcessing = ref(false);

const disputeReasons = [
    'Le vendeur ne repond pas',
    'Produit non conforme a la description',
    'Delai de livraison depasse',
    'Colis endommage',
    'Autre probleme',
];

function openDisputeModal() {
    disputeReason.value = '';
    showDisputeModal.value = true;
}

function closeDisputeModal() {
    showDisputeModal.value = false;
}

function submitDispute() {
    if (!disputeReason.value) return;
    disputeProcessing.value = true;
    router.post(route('order.track.dispute', props.order.id), {
        token: props.token,
        reason: disputeReason.value,
    }, {
        preserveScroll: true,
        onSuccess: () => { closeDisputeModal(); },
        onFinish: () => { disputeProcessing.value = false; },
    });
}
</script>

<template>
    <Head title="Suivi de commande" />

    <div class="min-h-screen bg-gradient-to-br from-teal-50 via-white to-purple-50">
        <!-- Header -->
        <header class="border-b border-slate-200 bg-white/80 backdrop-blur-sm">
            <div class="mx-auto flex max-w-3xl items-center justify-between px-4 py-4 sm:px-6">
                <span class="text-lg font-bold tracking-tight text-slate-900">MANTOTA</span>
                <span class="text-sm text-slate-500">Suivi de commande</span>
            </div>
        </header>

        <main class="mx-auto max-w-2xl px-4 py-10 sm:px-6">

            <!-- Flash success -->
            <div
                v-if="flashSuccess"
                class="mb-6 flex items-center gap-3 rounded-xl border border-green-200 bg-green-50 px-5 py-4 text-sm text-green-800"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0 text-green-500" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                </svg>
                <span class="font-medium">{{ flashSuccess }}</span>
            </div>

            <!-- Flash errors -->
            <div
                v-if="flashErrors.order"
                class="mb-6 flex items-center gap-3 rounded-xl border border-red-200 bg-red-50 px-5 py-4 text-sm text-red-800"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0 text-red-500" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                </svg>
                <span>{{ flashErrors.order }}</span>
            </div>

            <!-- Flash erreur PIN -->
            <div
                v-if="flashErrors.pin"
                class="mb-6 flex items-center gap-3 rounded-xl border border-red-200 bg-red-50 px-5 py-4 text-sm text-red-800"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0 text-red-500" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                </svg>
                <span>{{ flashErrors.pin }}</span>
            </div>

            <!-- Titre + Reference -->
            <div class="text-center">
                <div class="inline-flex items-center justify-center rounded-full bg-teal-100 p-3">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-teal-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 10-7.5 0v4.5m11.356-1.993l1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 01-1.12-1.243l1.264-12A1.125 1.125 0 015.513 7.5h12.974c.576 0 1.059.435 1.119 1.007zM8.625 10.5a.375.375 0 11-.75 0 .375.375 0 01.75 0zm7.5 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" />
                    </svg>
                </div>
                <h1 class="mt-4 text-2xl font-bold text-slate-900">Suivi de votre commande</h1>
                <p class="mt-1 text-base font-semibold text-teal-600">{{ order.reference }}</p>
            </div>

            <!-- Statut actuel -->
            <div class="mt-8 flex items-center justify-center">
                <span
                    class="inline-flex items-center gap-2 rounded-full px-4 py-2 text-sm font-bold"
                    :class="statusConfig[order.status]?.bgClass || 'bg-slate-100 text-slate-500'"
                >
                    <!-- Icone dynamique par statut -->
                    <svg v-if="order.status === 'pending'" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <svg v-else-if="order.status === 'shipped'" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 01-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 00-3.213-9.193 2.056 2.056 0 00-1.58-.86H14.25M16.5 18.75h-2.25m0-11.177v-.958c0-.568-.422-1.048-.987-1.106a48.554 48.554 0 00-10.026 0 1.106 1.106 0 00-.987 1.106v7.635m12-6.677v6.677m0 4.5v-4.5m0 0h-12" />
                    </svg>
                    <svg v-else-if="order.status === 'delivered'" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    {{ statusConfig[order.status]?.label || order.status }}
                </span>
            </div>

            <!-- Barre de progression (produits physiques) -->
            <div v-if="!isDigital" class="mt-6 flex items-center justify-center gap-0">
                <!-- Etape 1 : Commande -->
                <div class="flex flex-col items-center">
                    <div class="flex h-10 w-10 items-center justify-center rounded-full"
                         :class="['pending','shipped','delivered'].includes(order.status) ? 'bg-emerald-500' : 'bg-slate-200'">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-white" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                        </svg>
                    </div>
                    <span class="mt-1.5 text-xs font-medium text-slate-600">Commandee</span>
                </div>
                <div class="mx-1 h-0.5 w-12 sm:w-20"
                     :class="['shipped','delivered'].includes(order.status) ? 'bg-emerald-400' : 'bg-slate-200'"></div>
                <!-- Etape 2 : Expediee -->
                <div class="flex flex-col items-center">
                    <div class="flex h-10 w-10 items-center justify-center rounded-full"
                         :class="['shipped','delivered'].includes(order.status) ? 'bg-emerald-500' : 'bg-slate-200'">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-white" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 01-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 00-3.213-9.193 2.056 2.056 0 00-1.58-.86H14.25M16.5 18.75h-2.25m0-11.177v-.958c0-.568-.422-1.048-.987-1.106a48.554 48.554 0 00-10.026 0 1.106 1.106 0 00-.987 1.106v7.635m12-6.677v6.677m0 4.5v-4.5m0 0h-12" />
                        </svg>
                    </div>
                    <span class="mt-1.5 text-xs font-medium text-slate-600">Expediee</span>
                </div>
                <div class="mx-1 h-0.5 w-12 sm:w-20"
                     :class="order.status === 'delivered' ? 'bg-emerald-400' : 'bg-slate-200'"></div>
                <!-- Etape 3 : Livree -->
                <div class="flex flex-col items-center">
                    <div class="flex h-10 w-10 items-center justify-center rounded-full"
                         :class="order.status === 'delivered' ? 'bg-emerald-500' : 'bg-slate-200'">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-white" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <span class="mt-1.5 text-xs font-medium text-slate-600">Livree</span>
                </div>
            </div>

            <!-- Barre de progression (produits digitaux) -->
            <div v-if="isDigital" class="mt-6 flex items-center justify-center gap-0">
                <div class="flex flex-col items-center">
                    <div class="flex h-10 w-10 items-center justify-center rounded-full bg-emerald-500">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-white" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                        </svg>
                    </div>
                    <span class="mt-1.5 text-xs font-medium text-slate-600">Achat confirme</span>
                </div>
                <div class="mx-1 h-0.5 w-16 sm:w-24 bg-emerald-400"></div>
                <div class="flex flex-col items-center">
                    <div class="flex h-10 w-10 items-center justify-center rounded-full bg-emerald-500">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-white" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
                        </svg>
                    </div>
                    <span class="mt-1.5 text-xs font-medium text-slate-600">Acces immediat</span>
                </div>
            </div>

            <!-- Details de la commande -->
            <div class="mt-8 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-100 bg-slate-50 px-5 py-3">
                    <h2 class="text-sm font-bold text-slate-800">Details de la commande</h2>
                </div>

                <div class="divide-y divide-slate-100">
                    <!-- Produit -->
                    <div class="flex items-center gap-4 px-5 py-4">
                        <div class="h-14 w-14 shrink-0 overflow-hidden rounded-xl bg-slate-100">
                            <img
                                v-if="order.product?.image_path"
                                :src="`/storage/${order.product.image_path}`"
                                :alt="order.product?.name"
                                class="h-full w-full object-cover"
                            />
                            <div v-else class="flex h-full w-full items-center justify-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-slate-300" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 7.5l-9-5.25L3 7.5m18 0l-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-9v9" />
                                </svg>
                            </div>
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-slate-900">{{ order.product?.name || 'Produit' }}</p>
                            <p class="mt-0.5 text-base font-bold text-teal-600">{{ formatCurrency(order.amount_paid) }}</p>
                        </div>
                    </div>

                    <!-- Date de commande -->
                    <div class="flex items-center justify-between px-5 py-3">
                        <span class="text-sm text-slate-500">Date de commande</span>
                        <span class="text-sm font-medium text-slate-900">{{ formatDate(order.created_at) }}</span>
                    </div>

                    <!-- Ville (physique uniquement) -->
                    <div v-if="!isDigital" class="flex items-center justify-between px-5 py-3">
                        <span class="text-sm text-slate-500">Livraison a</span>
                        <span class="text-sm font-medium text-slate-900">{{ order.city }}</span>
                    </div>

                    <!-- Type (digital) -->
                    <div v-if="isDigital" class="flex items-center justify-between px-5 py-3">
                        <span class="text-sm text-slate-500">Type</span>
                        <span class="text-sm font-medium text-teal-600">Produit digital — acces immediat</span>
                    </div>

                    <!-- Vendeur -->
                    <div class="flex items-center justify-between px-5 py-3">
                        <span class="text-sm text-slate-500">Vendeur</span>
                        <span class="text-sm font-medium text-slate-900">{{ order.vendor?.shop_name || order.vendor?.business_name || order.vendor?.name || '—' }}</span>
                    </div>

                    <!-- Paiement -->
                    <div class="flex items-center justify-between px-5 py-3">
                        <span class="text-sm text-slate-500">Securite du paiement</span>
                        <span
                            v-if="order.status === 'delivered'"
                            class="inline-flex items-center gap-1.5 rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-semibold text-emerald-700"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 10.5V6.75a4.5 4.5 0 119 0v3.75M3.75 21.75h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H3.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
                            </svg>
                            Fonds liberes au vendeur
                        </span>
                        <span
                            v-else
                            class="inline-flex items-center gap-1.5 rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-semibold text-amber-700"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
                            </svg>
                            Fonds en escrow — securises
                        </span>
                    </div>
                </div>
            </div>

            <!-- Bouton Acceder au produit digital -->
            <div v-if="isDigital && order.product?.access_url" class="mt-8">
                <a
                    :href="order.product.access_url"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="flex w-full items-center justify-center gap-3 rounded-2xl bg-teal-600 px-6 py-4 text-base font-bold text-white shadow-lg transition hover:bg-teal-700 hover:shadow-xl focus:outline-none focus:ring-4 focus:ring-teal-200"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
                    </svg>
                    Acceder a mon produit digital
                </a>
                <p class="mt-2 text-center text-xs text-slate-500">
                    Vous pouvez acceder a votre produit autant de fois que necessaire via ce lien.
                </p>
            </div>

            <!-- Infos livraison (si shipped ou delivered, physique uniquement) -->
            <div
                v-if="!isDigital && ['shipped', 'delivered'].includes(order.status) && order.delivery_guy_name"
                class="mt-4 overflow-hidden rounded-2xl border border-teal-200 bg-teal-50"
            >
                <div class="border-b border-teal-200 bg-teal-100/50 px-5 py-3">
                    <h3 class="flex items-center gap-2 text-sm font-bold text-teal-800">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-teal-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 01-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 00-3.213-9.193 2.056 2.056 0 00-1.58-.86H14.25M16.5 18.75h-2.25m0-11.177v-.958c0-.568-.422-1.048-.987-1.106a48.554 48.554 0 00-10.026 0 1.106 1.106 0 00-.987 1.106v7.635m12-6.677v6.677m0 4.5v-4.5m0 0h-12" />
                        </svg>
                        Informations de livraison
                    </h3>
                </div>
                <div class="px-5 py-4 space-y-3">
                    <!-- Societe de livraison -->
                    <div v-if="order.delivery_company" class="flex items-center gap-2 text-sm text-teal-700">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-teal-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21" />
                        </svg>
                        <span><span class="font-medium">Societe :</span> {{ order.delivery_company }}</span>
                    </div>
                    <!-- Nom du livreur -->
                    <div class="flex items-center gap-2 text-sm text-teal-700">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-teal-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />
                        </svg>
                        <span><span class="font-medium">Livreur :</span> {{ order.delivery_guy_name }}</span>
                    </div>
                    <!-- Telephone du livreur -->
                    <div class="flex items-center gap-2 text-sm text-teal-700">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-teal-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 002.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 01-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 00-1.091-.852H4.5A2.25 2.25 0 002.25 4.5v2.25z" />
                        </svg>
                        <span><span class="font-medium">Telephone :</span> {{ order.delivery_guy_phone }}</span>
                    </div>
                    <!-- Message du vendeur -->
                    <div v-if="order.vendor_shipping_note" class="mt-2 rounded-lg bg-white/60 border border-teal-200 p-3">
                        <p class="text-xs font-semibold text-teal-600 mb-1">Message du vendeur</p>
                        <p class="text-sm text-teal-800 leading-relaxed">{{ order.vendor_shipping_note }}</p>
                    </div>
                </div>
            </div>

            <!-- Bouton de confirmation (uniquement si shipped, physique) -->
            <div v-if="!isDigital && canConfirm" class="mt-8">
                <button
                    @click="openConfirmModal"
                    class="flex w-full items-center justify-center gap-3 rounded-2xl bg-emerald-600 px-6 py-4 text-base font-bold text-white shadow-lg transition hover:bg-emerald-700 hover:shadow-xl focus:outline-none focus:ring-4 focus:ring-emerald-200"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    Confirmer la reception de la commande
                </button>
                <p class="mt-2 text-center text-xs text-slate-500">
                    En confirmant, vous attestez avoir recu votre colis en bon etat. Les fonds seront liberes au vendeur.
                </p>
            </div>

            <!-- Message si pending -->
            <div v-if="!isDigital && order.status === 'pending'" class="mt-8 rounded-xl border border-amber-200 bg-amber-50 px-5 py-4">
                <div class="flex gap-3">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0 text-amber-500 mt-0.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <div class="text-sm text-amber-800">
                        <p class="font-semibold">En attente d'expedition</p>
                        <p class="mt-1 text-amber-700">
                            Le vendeur est en train de preparer votre commande. Vous pourrez confirmer la reception une fois le colis expedie.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Message si delivered (physique) -->
            <div v-if="!isDigital && order.status === 'delivered'" class="mt-8 rounded-xl border border-emerald-200 bg-emerald-50 px-5 py-4">
                <div class="flex gap-3">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0 text-emerald-500 mt-0.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <div class="text-sm text-emerald-800">
                        <p class="font-semibold">Livraison confirmee</p>
                        <p class="mt-1 text-emerald-700">
                            Merci d'avoir confirme la reception. Les fonds ont ete liberes au vendeur. Bonne utilisation de votre produit !
                        </p>
                    </div>
                </div>
            </div>

            <!-- Message si disputed -->
            <div v-if="order.status === 'disputed'" class="mt-8 rounded-xl border border-red-200 bg-red-50 px-5 py-4">
                <div class="flex gap-3">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0 text-red-500 mt-0.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m0 0h.008v.008H12v-.008zm9-3.75a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <div class="text-sm text-red-800">
                        <p class="font-semibold">Litige en cours</p>
                        <p class="mt-1 text-red-700">
                            Votre signalement a ete pris en compte. Echangez avec le vendeur et l'equipe MANTOTA via le chat de mediation.
                        </p>
                        <a
                            :href="route('public.dispute.chat', { order: order.id, token: token })"
                            class="mt-3 inline-flex items-center gap-2 rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-red-700"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 8.511c.884.284 1.5 1.128 1.5 2.097v4.286c0 1.136-.847 2.1-1.98 2.193-.34.027-.68.052-1.02.072v3.091l-3-3c-1.354 0-2.694-.055-4.02-.163a2.115 2.115 0 01-.825-.242m9.345-8.334a2.126 2.126 0 00-.476-.095 48.64 48.64 0 00-8.048 0c-1.131.094-1.976 1.057-1.976 2.192v4.286c0 .837.46 1.58 1.155 1.951m9.345-8.334V6.637c0-1.621-1.152-3.026-2.76-3.235A48.455 48.455 0 0011.25 3c-2.115 0-4.198.137-6.24.402-1.608.209-2.76 1.614-2.76 3.235v6.226c0 1.621 1.152 3.026 2.76 3.235.577.075 1.157.14 1.74.194V21l4.155-4.155" />
                            </svg>
                            Acceder au chat de mediation
                        </a>
                    </div>
                </div>
            </div>

            <!-- Signaler un probleme -->
            <div v-if="canDispute" class="mt-6 text-center">
                <button
                    @click="openDisputeModal"
                    class="inline-flex items-center gap-2 rounded-lg border border-amber-300 bg-amber-50 px-4 py-2.5 text-sm font-medium text-amber-700 transition hover:bg-amber-100 hover:border-amber-400"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                    </svg>
                    Signaler un probleme
                </button>
            </div>
        </main>
    </div>

    <!-- ===== Modal de confirmation ===== -->
    <Teleport to="body">
        <div v-if="showConfirmModal" class="fixed inset-0 z-50 flex items-center justify-center">
            <!-- Backdrop -->
            <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" @click="closeConfirmModal"></div>

            <!-- Panel -->
            <div class="relative w-full max-w-md rounded-2xl bg-white p-6 shadow-xl ring-1 ring-slate-200">
                <div class="flex justify-center">
                    <div class="flex h-14 w-14 items-center justify-center rounded-full bg-amber-100">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7 text-amber-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                        </svg>
                    </div>
                </div>

                <h3 class="mt-4 text-center text-lg font-bold text-slate-900">Confirmer la reception</h3>
                <p class="mt-2 text-center text-sm text-slate-600 leading-relaxed">
                    En confirmant, vous attestez avoir recu votre commande en bon etat.
                    <span class="font-semibold text-slate-800">Cette action debloquera le paiement pour le vendeur.</span>
                    Elle est irreversible.
                </p>

                <!-- Champ Code Secret de Livraison (OTP 4 chiffres) -->
                <div class="mt-5">
                    <label for="delivery_pin" class="block text-sm font-semibold text-slate-700 text-center">Code Secret de Livraison</label>
                    <input
                        id="delivery_pin"
                        v-model="pinCode"
                        type="text"
                        inputmode="numeric"
                        maxlength="4"
                        autocomplete="off"
                        placeholder="_ _ _ _"
                        class="mt-2 block w-full rounded-xl border border-slate-300 px-4 py-3 text-center text-3xl font-black tracking-[0.4em] text-slate-900 shadow-sm focus:border-teal-500 focus:ring-teal-500"
                    />
                    <p class="mt-2 text-center text-xs text-slate-500">Entrez le code a 4 chiffres recu lors de votre commande.</p>
                </div>

                <div class="mt-6 flex items-center justify-end gap-3">
                    <button
                        type="button"
                        @click="closeConfirmModal"
                        class="rounded-lg px-4 py-2.5 text-sm font-medium text-slate-600 transition hover:bg-slate-100"
                    >
                        Annuler
                    </button>
                    <button
                        @click="submitConfirmation"
                        :disabled="processing"
                        class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-700 disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        <svg v-if="processing" class="h-4 w-4 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                        </svg>
                        <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                        </svg>
                        Oui, je confirme la reception
                    </button>
                </div>
            </div>
        </div>
    </Teleport>
    <!-- ===== Modal de signalement ===== -->
    <Teleport to="body">
        <div v-if="showDisputeModal" class="fixed inset-0 z-50 flex items-center justify-center">
            <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" @click="closeDisputeModal"></div>
            <div class="relative w-full max-w-md rounded-2xl bg-white p-6 shadow-xl ring-1 ring-slate-200">
                <div class="flex justify-center">
                    <div class="flex h-14 w-14 items-center justify-center rounded-full bg-amber-100">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7 text-amber-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                        </svg>
                    </div>
                </div>

                <h3 class="mt-4 text-center text-lg font-bold text-slate-900">Signaler un probleme</h3>
                <p class="mt-2 text-center text-sm text-slate-600">Selectionnez le motif de votre signalement. L'equipe MANTOTA examinera votre dossier.</p>

                <div class="mt-5 space-y-2">
                    <label v-for="reason in disputeReasons" :key="reason"
                        class="flex cursor-pointer items-center gap-3 rounded-lg border px-4 py-3 transition"
                        :class="disputeReason === reason ? 'border-amber-400 bg-amber-50 ring-2 ring-amber-400/20' : 'border-slate-200 bg-white hover:border-slate-300'">
                        <input type="radio" v-model="disputeReason" :value="reason" class="h-4 w-4 text-amber-600 border-slate-300 focus:ring-amber-500" />
                        <span class="text-sm text-slate-700">{{ reason }}</span>
                    </label>
                </div>

                <div class="mt-6 flex items-center justify-end gap-3">
                    <button type="button" @click="closeDisputeModal"
                        class="rounded-lg px-4 py-2.5 text-sm font-medium text-slate-600 transition hover:bg-slate-100">
                        Annuler
                    </button>
                    <button @click="submitDispute" :disabled="disputeProcessing || !disputeReason"
                        class="inline-flex items-center gap-2 rounded-lg bg-amber-500 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-amber-600 disabled:opacity-50 disabled:cursor-not-allowed">
                        <svg v-if="disputeProcessing" class="h-4 w-4 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" /><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" /></svg>
                        <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" /></svg>
                        Envoyer le signalement
                    </button>
                </div>
            </div>
        </div>
    </Teleport>
</template>
