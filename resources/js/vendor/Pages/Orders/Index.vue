<script setup>
import VendorLayout from '../../Layouts/VendorLayout.vue';
import { Head, Link, router, usePage, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    orders: { type: Object, required: true },
});

const page = usePage();
const flashSuccess = computed(() => page.props.flash?.success ?? null);
const flashErrors  = computed(() => page.props.errors ?? {});

const processingId = ref(null);

// ── Modal expedition ──
const showShipModal   = ref(false);
const shipOrderTarget = ref(null);

const shipForm = useForm({
    delivery_guy_name:  '',
    delivery_guy_phone: '',
    delivery_company:   '',
    vendor_shipping_note: '',
});

// ── Modal defense litige ──
const showDefenseModal   = ref(false);
const defenseOrderTarget = ref(null);

const defenseForm = useForm({
    vendor_defense_message: '',
    vendor_defense_proof: null,
});

function openDefenseModal(order) {
    defenseOrderTarget.value = order;
    defenseForm.vendor_defense_message = order.vendor_defense_message || '';
    defenseForm.vendor_defense_proof = null;
    defenseForm.clearErrors();
    showDefenseModal.value = true;
}

function closeDefenseModal() {
    showDefenseModal.value = false;
    defenseOrderTarget.value = null;
}

function submitDefenseForm() {
    if (!defenseOrderTarget.value) return;
    defenseForm.post(route('vendor.orders.defense', defenseOrderTarget.value.id), {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => closeDefenseModal(),
    });
}

function openShipModal(order) {
    shipOrderTarget.value = order;
    shipForm.delivery_guy_name  = '';
    shipForm.delivery_guy_phone = '';
    shipForm.delivery_company   = '';
    shipForm.vendor_shipping_note = '';
    shipForm.clearErrors();
    showShipModal.value = true;
}

function closeShipModal() {
    showShipModal.value = false;
    shipOrderTarget.value = null;
}

function submitShipForm() {
    if (!shipOrderTarget.value) return;
    processingId.value = shipOrderTarget.value.id;
    shipForm.post(route('vendor.orders.ship', shipOrderTarget.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            closeShipModal();
        },
        onFinish: () => {
            processingId.value = null;
        },
    });
}

function formatCurrency(amount) {
    const v = parseFloat(amount);
    if (!v || v < 0) return '0 FCFA';
    return new Intl.NumberFormat('fr-FR', { style: 'decimal', minimumFractionDigits: 0, maximumFractionDigits: 0 }).format(v) + ' FCFA';
}

function formatDate(dateStr) {
    if (!dateStr) return '—';
    return new Date(dateStr).toLocaleDateString('fr-FR', {
        day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit',
    });
}

/**
 * Calcule le temps restant avant la deadline de livraison.
 */
function timeRemaining(deadline) {
    if (!deadline) return null;
    const now = new Date();
    const target = new Date(deadline);
    const diffMs = target - now;

    if (diffMs <= 0) return { text: 'Delai depasse', overdue: true };

    const hours = Math.floor(diffMs / (1000 * 60 * 60));
    const minutes = Math.floor((diffMs % (1000 * 60 * 60)) / (1000 * 60));

    if (hours >= 24) {
        const days = Math.floor(hours / 24);
        const remainingHours = hours % 24;
        return { text: `${days}j ${remainingHours}h`, overdue: false };
    }

    return { text: `${hours}h ${minutes}min`, overdue: false };
}

const statusConfig = {
    pending:   { label: 'En attente',  bgClass: 'bg-amber-100 text-amber-700' },
    shipped:   { label: 'Expediee',    bgClass: 'bg-purple-100 text-purple-700' },
    delivered: { label: 'Livree',      bgClass: 'bg-purple-100 text-purple-700' },
    disputed:  { label: 'Litige',      bgClass: 'bg-red-100 text-red-700' },
    cancelled: { label: 'Annulee',     bgClass: 'bg-slate-100 text-slate-500' },
};

// ── WhatsApp Click-to-Chat ──

/**
 * Nettoie un numero : ne garde que les chiffres.
 */
function cleanPhone(phone) {
    if (!phone) return '';
    return phone.replace(/[^0-9]/g, '');
}

/**
 * Lien WhatsApp "Confirmer la commande" (vendeur -> client, statut pending).
 */
function waConfirmLink(order) {
    const phone = cleanPhone(order.customer_whatsapp);
    if (!phone) return null;
    const msg = 'Bonjour ' + (order.customer_name || '') + ', c\'est la boutique. '
        + 'Nous avons bien recu votre commande MANTOTA pour '
        + (order.product?.name || 'votre produit') + '. '
        + 'Nous preparons votre colis !';
    return 'https://wa.me/' + phone + '?text=' + encodeURIComponent(msg);
}

/**
 * Lien WhatsApp "Infos livreur" (vendeur -> client, statut shipped).
 */
function waShippedLink(order) {
    const phone = cleanPhone(order.customer_whatsapp);
    if (!phone) return null;

    // URL de suivi publique (magic link) avec tracking_token
    const trackUrl = order.tracking_token
        ? route('order.track', { order: order.id }) + '?token=' + order.tracking_token
        : '';

    const msg = 'Votre commande MANTOTA est en route ! '
        + 'Le livreur ' + (order.delivery_guy_name || '') + ' arrive. '
        + 'Confirmez la reception de votre colis sur ce lien : '
        + trackUrl + ' '
        + '(Preparez votre code secret a 4 chiffres).';
    return 'https://wa.me/' + phone + '?text=' + encodeURIComponent(msg);
}


</script>

<template>
    <Head title="Commandes" />

    <VendorLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-slate-800">
                Commandes e-commerce
            </h2>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8 space-y-6">

                <!-- Flash success -->
                <div
                    v-if="flashSuccess"
                    class="flex items-center gap-3 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0 text-green-500" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                    </svg>
                    <span>{{ flashSuccess }}</span>
                </div>

                <!-- Flash errors -->
                <div
                    v-if="flashErrors.order"
                    class="flex items-center gap-3 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0 text-red-500" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                    </svg>
                    <span>{{ flashErrors.order }}</span>
                </div>

                <!-- Commandes -->
                <div v-if="orders.data.length" class="space-y-4">
                    <div
                        v-for="order in orders.data"
                        :key="order.id"
                        class="rounded-2xl border border-slate-200/80 bg-white shadow-sm overflow-hidden"
                    >
                        <!-- En-tete de la commande -->
                        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-5 py-3 bg-slate-50">
                            <div class="flex items-center gap-3">
                                <span class="text-sm font-bold text-slate-900">{{ order.reference }}</span>
                                <span
                                    class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold"
                                    :class="statusConfig[order.status]?.bgClass || 'bg-slate-100 text-slate-500'"
                                >
                                    {{ statusConfig[order.status]?.label || order.status }}
                                </span>
                            </div>
                            <div class="flex items-center gap-4 text-xs text-slate-500">
                                <span>{{ formatDate(order.created_at) }}</span>
                                <!-- Countdown -->
                                <span
                                    v-if="['pending', 'shipped'].includes(order.status) && order.delivery_deadline"
                                    class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-semibold"
                                    :class="timeRemaining(order.delivery_deadline)?.overdue
                                        ? 'bg-red-100 text-red-700'
                                        : 'bg-purple-100 text-purple-700'"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    {{ timeRemaining(order.delivery_deadline)?.text }}
                                </span>
                            </div>
                        </div>

                        <!-- Corps de la commande -->
                        <div class="px-5 py-4">
                            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">

                                <!-- Produit -->
                                <div class="flex items-center gap-3">
                                    <div class="h-12 w-12 shrink-0 overflow-hidden rounded-lg bg-slate-100">
                                        <img
                                            v-if="order.product?.image_path"
                                            :src="`/storage/${order.product.image_path}`"
                                            :alt="order.product.name"
                                            class="h-full w-full object-cover"
                                        />
                                        <div v-else class="flex h-full w-full items-center justify-center">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-slate-300" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M21 7.5l-9-5.25L3 7.5m18 0l-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-9v9" />
                                            </svg>
                                        </div>
                                    </div>
                                    <div>
                                        <p class="text-sm font-semibold text-slate-900 line-clamp-1">{{ order.product?.name || '—' }}</p>
                                        <p class="text-sm font-bold text-purple-600">{{ formatCurrency(order.amount_paid) }}</p>
                                    </div>
                                </div>

                                <!-- Client & Livraison -->
                                <div class="space-y-1.5">
                                    <div class="flex items-center gap-2">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-slate-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />
                                        </svg>
                                        <span class="text-sm text-slate-700">{{ order.customer_name }}</span>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-slate-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 002.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 01-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 00-1.091-.852H4.5A2.25 2.25 0 002.25 4.5v2.25z" />
                                        </svg>
                                        <span class="text-sm text-slate-700">{{ order.customer_phone }}</span>
                                    </div>
                                    <a
                                        :href="`https://wa.me/${order.customer_whatsapp?.replace(/[^0-9+]/g, '')}`"
                                        target="_blank"
                                        class="inline-flex items-center gap-1.5 text-sm font-medium text-emerald-600 hover:text-emerald-700 transition"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M8.625 9.75a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H8.25m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H12m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0h-.375m-13.5 3.01c0 1.6 1.123 2.994 2.707 3.227 1.087.16 2.185.283 3.293.369V21l4.184-4.183a1.14 1.14 0 01.778-.332 48.294 48.294 0 005.83-.498c1.585-.233 2.708-1.626 2.708-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0012 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018z" />
                                        </svg>
                                        WhatsApp : {{ order.customer_whatsapp }}
                                    </a>
                                </div>

                                <!-- Localisation -->
                                <div class="space-y-1.5">
                                    <div class="flex items-center gap-2">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-slate-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z" />
                                        </svg>
                                        <span class="text-sm font-medium text-slate-700">{{ order.city }}</span>
                                    </div>
                                    <div class="rounded-lg border border-slate-100 bg-slate-50 px-3 py-2">
                                        <p class="text-xs text-slate-600 leading-relaxed">
                                            <span class="font-medium text-slate-700">Repere :</span>
                                            {{ order.landmark_indication }}
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <!-- Repartition financiere + Actions -->
                            <div class="mt-4 flex flex-wrap items-center justify-between gap-3 border-t border-slate-100 pt-4">
                                <div class="flex flex-wrap items-center gap-4 text-xs text-slate-500">
                                    <span>
                                        Gain net :
                                        <span class="font-semibold text-slate-900">{{ formatCurrency(order.vendor_earnings) }}</span>
                                    </span>
                                    <span v-if="parseFloat(order.commission_amount) > 0">
                                        Commission createur de contenu :
                                        <span class="font-semibold text-purple-600">{{ formatCurrency(order.commission_amount) }}</span>
                                    </span>
                                    <span v-if="order.influencer" class="text-slate-400">
                                        via <Link :href="route('vendor.influencer.show', order.influencer.id)" class="font-medium text-purple-600 hover:text-purple-800 underline">{{ order.influencer.name }}</Link>
                                    </span>
                                </div>

                                <div class="flex flex-wrap items-center gap-2">
                                    <!-- Voir details -->
                                    <Link
                                        :href="route('vendor.orders.show', order.id)"
                                        class="inline-flex items-center gap-1.5 rounded-full border border-slate-200 bg-white px-3.5 py-2 text-xs font-medium text-slate-700 shadow-sm transition hover:bg-slate-50"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        </svg>
                                        Voir details
                                    </Link>

                                    <!-- Marquer comme expediee — ouvre la modal -->
                                    <button
                                        v-if="order.status === 'pending'"
                                        @click="openShipModal(order)"
                                        class="inline-flex items-center gap-1.5 rounded-full bg-gradient-to-r from-purple-500 to-violet-600 px-3.5 py-2 text-xs font-semibold text-white shadow-sm transition hover:from-purple-600 hover:to-violet-700"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 01-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 00-3.213-9.193 2.056 2.056 0 00-1.58-.86H14.25M16.5 18.75h-2.25m0-11.177v-.958c0-.568-.422-1.048-.987-1.106a48.554 48.554 0 00-10.026 0 1.106 1.106 0 00-.987 1.106v7.635m12-6.677v6.677m0 4.5v-4.5m0 0h-12" />
                                        </svg>
                                        Marquer comme Livre
                                    </button>

                                    <!-- WhatsApp : Confirmer la commande (pending) -->
                                    <a
                                        v-if="order.status === 'pending' && waConfirmLink(order)"
                                        :href="waConfirmLink(order)"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        class="inline-flex items-center gap-1.5 rounded-full bg-emerald-600 px-3.5 py-2 text-xs font-semibold text-white shadow-sm transition hover:bg-emerald-700"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                                        </svg>
                                        Confirmer (WhatsApp)
                                    </a>

                                    <!-- Infos livreur (si shipped) -->
                                    <div v-if="order.status === 'shipped' && order.delivery_guy_name" class="flex items-center gap-2 text-xs text-slate-500">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-purple-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 01-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 00-3.213-9.193 2.056 2.056 0 00-1.58-.86H14.25M16.5 18.75h-2.25m0-11.177v-.958c0-.568-.422-1.048-.987-1.106a48.554 48.554 0 00-10.026 0 1.106 1.106 0 00-.987 1.106v7.635m12-6.677v6.677m0 4.5v-4.5m0 0h-12" />
                                        </svg>
                                        <span>Livreur : {{ order.delivery_guy_name }} — {{ order.delivery_guy_phone }}</span>
                                    </div>

                                    <!-- WhatsApp : Infos livreur (shipped) -->
                                    <a
                                        v-if="order.status === 'shipped' && order.delivery_guy_name && waShippedLink(order)"
                                        :href="waShippedLink(order)"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        class="inline-flex items-center gap-1.5 rounded-full bg-emerald-600 px-3.5 py-2 text-xs font-semibold text-white shadow-sm transition hover:bg-emerald-700"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                                        </svg>
                                        Infos Livreur (WhatsApp)
                                    </a>

                                    <!-- Badge Livree -->
                                    <span
                                        v-if="order.status === 'delivered'"
                                        class="inline-flex items-center gap-1 text-xs font-medium text-emerald-600"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        Livraison confirmee — Fonds liberes
                                    </span>

                                    <!-- Contester le litige -->
                                    <button
                                        v-if="order.status === 'disputed'"
                                        @click="openDefenseModal(order)"
                                        class="inline-flex items-center gap-1.5 rounded-full bg-red-600 px-3.5 py-2 text-xs font-semibold text-white shadow-sm transition hover:bg-red-700"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m0-10.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.75c0 5.592 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.75h-.152c-3.196 0-6.1-1.249-8.25-3.286zm0 13.036h.008v.008H12v-.008z" />
                                        </svg>
                                        Contester le litige
                                    </button>

                                    <!-- Chat de mediation -->
                                    <Link
                                        v-if="order.status === 'disputed'"
                                        :href="route('vendor.orders.dispute-chat', order.id)"
                                        class="inline-flex items-center gap-1.5 rounded-full bg-slate-900 px-3.5 py-2 text-xs font-semibold text-white shadow-sm transition hover:bg-slate-800"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 8.511c.884.284 1.5 1.128 1.5 2.097v4.286c0 1.136-.847 2.1-1.98 2.193-.34.027-.68.052-1.02.072v3.091l-3-3c-1.354 0-2.694-.055-4.02-.163a2.115 2.115 0 01-.825-.242m9.345-8.334a2.126 2.126 0 00-.476-.095 48.64 48.64 0 00-8.048 0c-1.131.094-1.976 1.057-1.976 2.192v4.286c0 1.136.845 2.1 1.976 2.193 1.07.089 2.15.137 3.224.137l3 3v-3.091c1.354-.089 2.694-.248 4.02-.479" />
                                        </svg>
                                        Chat litige
                                    </Link>
                                </div>
                            </div>
                            <!-- Alerte litige -->
                            <div v-if="order.status === 'disputed'" class="mt-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3">
                                <div class="flex items-start gap-3">
                                    <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-red-100">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-red-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="text-sm font-semibold text-red-800">Le client a signale un probleme. Les fonds sont geles.</p>
                                        <p v-if="order.dispute_reason" class="mt-1 text-xs text-red-700">Motif : {{ order.dispute_reason }}</p>
                                        <p v-if="order.vendor_defense_message" class="mt-2 text-xs text-red-700">
                                            <span class="font-semibold">Votre defense :</span> {{ order.vendor_defense_message }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Etat vide -->
                <div v-else class="rounded-2xl border border-slate-200/80 bg-white py-16 text-center shadow-sm">
                    <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-slate-100">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 10-7.5 0v4.5m11.356-1.993l1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 01-1.12-1.243l1.264-12A1.125 1.125 0 015.513 7.5h12.974c.576 0 1.059.435 1.119 1.007zM8.625 10.5a.375.375 0 11-.75 0 .375.375 0 01.75 0zm7.5 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" />
                        </svg>
                    </div>
                    <h3 class="mt-4 text-base font-semibold text-slate-900">Aucune commande</h3>
                    <p class="mt-2 text-sm text-slate-500">
                        Les commandes passees sur votre boutique apparaitront ici.
                    </p>
                </div>

                <!-- Pagination -->
                <div v-if="orders.last_page > 1" class="flex justify-center gap-1">
                    <template v-for="link in orders.links" :key="link.label">
                        <component
                            :is="link.url ? 'a' : 'span'"
                            :href="link.url"
                            class="inline-flex h-8 min-w-[2rem] items-center justify-center rounded-md border px-2 text-xs transition"
                            :class="[
                                link.active
                                    ? 'border-purple-500 bg-purple-50 text-purple-700 font-semibold'
                                    : link.url
                                        ? 'border-slate-200 bg-white text-slate-600 hover:bg-slate-50'
                                        : 'border-slate-100 bg-slate-50 text-slate-300 cursor-default',
                            ]"
                            v-html="link.label"
                            @click.prevent="link.url && router.get(link.url, {}, { preserveScroll: true })"
                        />
                    </template>
                </div>
            </div>
        </div>

        <!-- ===== Modal : Informations livreur ===== -->
        <Teleport to="body">
            <div v-if="showShipModal" class="fixed inset-0 z-50 flex items-center justify-center">
                <!-- Backdrop -->
                <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" @click="closeShipModal"></div>

                <!-- Panel -->
                <div class="relative w-full max-w-md rounded-2xl bg-white p-6 shadow-xl ring-1 ring-slate-200">
                    <h3 class="text-lg font-bold text-slate-800">Informations du livreur</h3>
                    <p class="mt-1 text-sm text-slate-500">Renseignez le nom et le telephone du livreur avant d'expedier cette commande.</p>

                    <form @submit.prevent="submitShipForm" class="mt-5 space-y-4">
                        <!-- Nom du livreur -->
                        <div>
                            <label for="delivery_guy_name" class="block text-sm font-medium text-slate-700">Nom du livreur</label>
                            <input
                                id="delivery_guy_name"
                                v-model="shipForm.delivery_guy_name"
                                type="text"
                                required
                                placeholder="Ex : Jean Koffi"
                                class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-purple-500 focus:ring-purple-500"
                            />
                            <p v-if="shipForm.errors.delivery_guy_name" class="mt-1 text-xs text-red-600">{{ shipForm.errors.delivery_guy_name }}</p>
                        </div>

                        <!-- Telephone du livreur -->
                        <div>
                            <label for="delivery_guy_phone" class="block text-sm font-medium text-slate-700">Telephone du livreur</label>
                            <input
                                id="delivery_guy_phone"
                                v-model="shipForm.delivery_guy_phone"
                                type="tel"
                                required
                                placeholder="Ex : +229 97 00 00 00"
                                class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-purple-500 focus:ring-purple-500"
                            />
                            <p v-if="shipForm.errors.delivery_guy_phone" class="mt-1 text-xs text-red-600">{{ shipForm.errors.delivery_guy_phone }}</p>
                        </div>

                        <!-- Societe de livraison -->
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-2">Societe de livraison</label>
                            <div class="grid grid-cols-2 gap-2">
                                <label
                                    v-for="company in ['Gozem', 'Yango', 'Rema', 'Kaba', 'Autre']"
                                    :key="company"
                                    class="flex items-center gap-2 rounded-lg border px-3 py-2 text-sm cursor-pointer transition"
                                    :class="shipForm.delivery_company === company ? 'border-purple-500 bg-purple-50 text-purple-700 font-medium' : 'border-slate-200 hover:border-slate-300 text-slate-700'"
                                >
                                    <input
                                        type="radio"
                                        :value="company"
                                        v-model="shipForm.delivery_company"
                                        class="text-purple-600 focus:ring-purple-500"
                                    />
                                    {{ company }}
                                </label>
                            </div>
                            <p v-if="shipForm.errors.delivery_company" class="mt-1 text-xs text-red-600">{{ shipForm.errors.delivery_company }}</p>
                        </div>

                        <!-- Message pour le client -->
                        <div>
                            <label for="vendor_shipping_note" class="block text-sm font-medium text-slate-700">Message pour le client</label>
                            <p class="mt-0.5 text-xs text-slate-400">Optionnel — visible par le client sur sa page de suivi.</p>
                            <textarea
                                id="vendor_shipping_note"
                                v-model="shipForm.vendor_shipping_note"
                                rows="3"
                                maxlength="1000"
                                placeholder="Ex : Votre colis sera livre entre 14h et 16h. Merci de rester joignable."
                                class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-purple-500 focus:ring-purple-500"
                            ></textarea>
                            <div class="mt-1 flex items-center justify-between">
                                <p v-if="shipForm.errors.vendor_shipping_note" class="text-xs text-red-600">{{ shipForm.errors.vendor_shipping_note }}</p>
                                <span class="ml-auto text-xs text-slate-400">{{ shipForm.vendor_shipping_note.length }}/1000</span>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="flex items-center justify-end gap-3 pt-2">
                            <button type="button" @click="closeShipModal" class="rounded-lg px-4 py-2 text-sm font-medium text-slate-600 transition hover:bg-slate-100">
                                Annuler
                            </button>
                            <button
                                type="submit"
                                :disabled="shipForm.processing"
                                class="inline-flex items-center gap-1.5 rounded-full bg-gradient-to-r from-purple-500 to-violet-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:from-purple-600 hover:to-violet-700 disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                                <svg v-if="shipForm.processing" class="h-4 w-4 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                                </svg>
                                Confirmer l'expedition
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>

        <!-- ===== Modal : Defense litige ===== -->
        <Teleport to="body">
            <div v-if="showDefenseModal" class="fixed inset-0 z-50 flex items-center justify-center">
                <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" @click="closeDefenseModal"></div>

                <div class="relative w-full max-w-lg rounded-2xl bg-white p-6 shadow-xl ring-1 ring-slate-200">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="flex h-10 w-10 items-center justify-center rounded-full bg-red-100">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-red-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m0-10.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.75c0 5.592 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.75h-.152c-3.196 0-6.1-1.249-8.25-3.286zm0 13.036h.008v.008H12v-.008z" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-slate-900">Contester le litige</h3>
                            <p class="text-sm text-slate-500">{{ defenseOrderTarget?.reference }}</p>
                        </div>
                    </div>

                    <div v-if="defenseOrderTarget?.dispute_reason" class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3">
                        <p class="text-xs font-semibold text-red-700">Motif du client :</p>
                        <p class="mt-1 text-sm text-red-800">{{ defenseOrderTarget.dispute_reason }}</p>
                    </div>

                    <form @submit.prevent="submitDefenseForm" class="space-y-4">
                        <div>
                            <label for="defense_message" class="block text-sm font-medium text-slate-700">Votre version des faits</label>
                            <textarea
                                id="defense_message"
                                v-model="defenseForm.vendor_defense_message"
                                rows="4"
                                required
                                placeholder="Expliquez votre point de vue, les preuves de livraison, etc."
                                class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-red-500 focus:ring-red-500"
                            ></textarea>
                            <p v-if="defenseForm.errors.vendor_defense_message" class="mt-1 text-xs text-red-600">{{ defenseForm.errors.vendor_defense_message }}</p>
                        </div>

                        <div>
                            <label for="defense_proof" class="block text-sm font-medium text-slate-700">Preuve (photo/bordereau)</label>
                            <input
                                id="defense_proof"
                                type="file"
                                accept="image/*"
                                @change="defenseForm.vendor_defense_proof = $event.target.files[0]"
                                class="mt-1 block w-full text-sm text-slate-500 file:mr-4 file:rounded-lg file:border-0 file:bg-slate-100 file:px-4 file:py-2 file:text-sm file:font-medium file:text-slate-700 hover:file:bg-slate-200"
                            />
                            <p v-if="defenseForm.errors.vendor_defense_proof" class="mt-1 text-xs text-red-600">{{ defenseForm.errors.vendor_defense_proof }}</p>
                        </div>

                        <div class="flex items-center justify-end gap-3 pt-2">
                            <button type="button" @click="closeDefenseModal" class="rounded-lg px-4 py-2 text-sm font-medium text-slate-600 transition hover:bg-slate-100">
                                Annuler
                            </button>
                            <button
                                type="submit"
                                :disabled="defenseForm.processing"
                                class="inline-flex items-center gap-1.5 rounded-full bg-red-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-red-700 disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                                <svg v-if="defenseForm.processing" class="h-4 w-4 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                                </svg>
                                Soumettre ma defense
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>
    </VendorLayout>
</template>
