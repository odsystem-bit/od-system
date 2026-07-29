<script setup>
import InfluencerLayout from '../../Layouts/InfluencerLayout.vue';
import ConfirmModal from '../../../Components/ConfirmModal.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { ref, computed, nextTick, onMounted } from 'vue';
import { router } from '@inertiajs/vue3';
import { useConfirm } from '../../../Composables/useConfirm.js';

const { visible: confirmVisible, title: confirmTitle, message: confirmMessage, variant: confirmVariant, confirmLabel, cancelLabel, ask, onConfirm, onCancel } = useConfirm();

const props = defineProps({
    order: { type: Object, required: true },
    authId: { type: Number, required: true },
});

const page = usePage();
const flash = computed(() => page.props.flash ?? {});

// ──────────────────────────────────────────────
//  Helpers
// ──────────────────────────────────────────────

function formatCurrency(amount) {
    const v = parseFloat(amount);
    if (!v || v < 0) return '0 FCFA';
    return new Intl.NumberFormat('fr-FR', { style: 'decimal', minimumFractionDigits: 0, maximumFractionDigits: 0 }).format(v) + ' FCFA';
}

function formatTime(dateStr) {
    if (!dateStr) return '';
    const d = new Date(dateStr);
    return d.toLocaleDateString('fr-FR', { day: '2-digit', month: 'short' }) + ' ' + d.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' });
}

function isImage(path) {
    if (!path) return false;
    return /\.(jpg|jpeg|png|webp|gif)$/i.test(path);
}

const statusConfig = {
    pending:             { label: 'En attente',         bg: 'bg-amber-50',   text: 'text-amber-700',   ring: 'ring-amber-600/10' },
    shooting:            { label: 'En production',      bg: 'bg-teal-50',    text: 'text-teal-700',    ring: 'ring-teal-600/10' },
    delivered:           { label: 'Livree',             bg: 'bg-purple-50',  text: 'text-purple-700',  ring: 'ring-purple-600/10' },
    revision_requested:  { label: 'Retouche demandee',  bg: 'bg-amber-50',   text: 'text-amber-700',   ring: 'ring-amber-600/10' },
    completed:           { label: 'Terminee',           bg: 'bg-teal-50',    text: 'text-teal-700',    ring: 'ring-teal-600/10' },
    disputed:            { label: 'Litige',             bg: 'bg-red-50',     text: 'text-red-700',     ring: 'ring-red-600/10' },
    approved:            { label: 'Approuvee',          bg: 'bg-emerald-50', text: 'text-emerald-700', ring: 'ring-emerald-600/10' },
    rejected:            { label: 'Rejetee',            bg: 'bg-red-50',     text: 'text-red-700',     ring: 'ring-red-600/10' },
    cancelled:           { label: 'Annulee',            bg: 'bg-red-50',     text: 'text-red-700',     ring: 'ring-red-600/10' },
};

function getStatus(s) {
    return statusConfig[s] || { label: s, bg: 'bg-slate-50', text: 'text-slate-700', ring: 'ring-slate-600/10' };
}

const sampleStatusConfig = {
    not_required:     { label: 'Non requis',         bg: 'bg-slate-50',   text: 'text-slate-600',  ring: 'ring-slate-600/10' },
    pending_shipment: { label: 'En attente d\'envoi', bg: 'bg-amber-50',  text: 'text-amber-700',  ring: 'ring-amber-600/10' },
    shipped:          { label: 'Expedie',            bg: 'bg-blue-50',    text: 'text-blue-700',   ring: 'ring-blue-600/10' },
    received:         { label: 'Recu',               bg: 'bg-teal-50',    text: 'text-teal-700',   ring: 'ring-teal-600/10' },
};

function getSampleStatus(s) {
    return sampleStatusConfig[s] || { label: s, bg: 'bg-slate-50', text: 'text-slate-600', ring: 'ring-slate-600/10' };
}

const isRevisionRequested = computed(() => props.order.status === 'revision_requested');
const isShooting          = computed(() => props.order.status === 'shooting');
const canUpload           = computed(() => isShooting.value || isRevisionRequested.value);
const needsSample         = computed(() => props.order.sample_status && props.order.sample_status !== 'not_required');
const sampleIsShipped     = computed(() => props.order.sample_status === 'shipped');
const sampleIsReceived    = computed(() => props.order.sample_status === 'received');
const sampleReady         = computed(() => props.order.sample_status === 'received' || props.order.sample_status === 'not_required');

const hasProduct       = computed(() => !!props.order.product);
const productImages    = computed(() => props.order.product?.images ?? []);
const selectedImageIndex = ref(0);
const selectedImage = computed(() => {
    if (productImages.value.length) return productImages.value[selectedImageIndex.value];
    if (props.order.product?.image_path) return { path: props.order.product.image_path };
    return null;
});

// ──────────────────────────────────────────────
//  Actions
// ──────────────────────────────────────────────

const processing = ref(false);

function accept() {
    processing.value = true;
    router.patch(route('influencer.service-orders.accept', props.order.id), {}, {
        preserveScroll: true, onFinish: () => (processing.value = false),
    });
}

const canCancel = computed(() => ['pending', 'shooting'].includes(props.order.status));

async function cancelOrder() {
    if (!await ask({ title: 'Annuler la commande', message: 'Annuler cette commande ? Le vendeur sera rembourse.', variant: 'danger', confirmLabel: 'Annuler la commande' })) return;
    processing.value = true;
    router.patch(route('influencer.service-orders.cancel', props.order.id), {}, {
        preserveScroll: true, onFinish: () => (processing.value = false),
    });
}

async function confirmSampleReceived() {
    if (!await ask({ title: 'Confirmer la reception', message: 'Confirmer la reception de l\'echantillon produit ?', variant: 'info', confirmLabel: 'Confirmer' })) return;
    processing.value = true;
    router.patch(route('influencer.service-orders.sample-received', props.order.id), {}, {
        preserveScroll: true, onFinish: () => (processing.value = false),
    });
}

const deliverForm = useForm({ video: null });
function deliverVideo(event) {
    const file = event.target.files?.[0];
    if (!file) return;
    deliverForm.video = file;
    deliverForm.post(route('influencer.service-orders.deliver', props.order.id), {
        preserveScroll: true, forceFormData: true,
        onFinish: () => deliverForm.reset(),
    });
}

// ──────────────────────────────────────────────
//  Chat
// ──────────────────────────────────────────────

const chatContainer  = ref(null);
const fileInput      = ref(null);
const chatForm       = useForm({ message: '', attachment: null });
const attachmentName = ref('');

function scrollToBottom() {
    nextTick(() => {
        if (chatContainer.value) chatContainer.value.scrollTop = chatContainer.value.scrollHeight;
    });
}
onMounted(scrollToBottom);

function pickFile() { fileInput.value?.click(); }

function onFileSelected(e) {
    const file = e.target.files?.[0];
    if (!file) return;
    chatForm.attachment = file;
    attachmentName.value = file.name;
}

function clearAttachment() {
    chatForm.attachment = null;
    attachmentName.value = '';
    if (fileInput.value) fileInput.value.value = '';
}

function sendMessage() {
    if (!chatForm.message.trim() && !chatForm.attachment) return;
    chatForm.post(route('influencer.service-orders.messages.store', props.order.id), {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => { chatForm.reset(); clearAttachment(); scrollToBottom(); },
    });
}
</script>

<template>
    <Head :title="`Commande #${order.id}`" />

    <InfluencerLayout>
        <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6">

            <!-- Header -->
            <div class="mb-5 flex items-center gap-4">
                <Link :href="route('influencer.service-orders.index')"
                    class="inline-flex items-center gap-1.5 text-sm font-medium text-slate-500 transition hover:text-slate-700">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" /></svg>
                    Retour
                </Link>
                <h1 class="text-xl font-bold text-slate-900">Commande #{{ order.id }}</h1>
                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold ring-1"
                      :class="[getStatus(order.status).bg, getStatus(order.status).text, getStatus(order.status).ring]">
                    {{ getStatus(order.status).label }}
                </span>
            </div>

            <!-- Flash warning (anti-fraud) -->
            <div v-if="flash.warning" class="mb-4 rounded-lg border border-amber-200 bg-amber-50 p-4 flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0 text-amber-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
                </svg>
                <p class="text-sm font-semibold text-amber-800">{{ flash.warning }}</p>
            </div>

            <!-- Revision feedback banner -->
            <div v-if="isRevisionRequested" class="mb-4 rounded-xl border-2 border-amber-300 bg-amber-50 p-5 space-y-3">
                <div class="flex items-start gap-3">
                    <svg xmlns="http://www.w3.org/2000/svg" class="mt-0.5 h-6 w-6 shrink-0 text-amber-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182M20.982 4.356v4.993" /></svg>
                    <div>
                        <h2 class="text-base font-bold text-amber-800">Le vendeur a demande une modification</h2>
                        <div class="mt-2 rounded-lg border border-amber-200 bg-white p-4">
                            <p class="text-sm text-amber-900 whitespace-pre-line">{{ order.revision_feedback }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 2-COLUMN LAYOUT -->
            <div class="flex flex-col lg:flex-row gap-6">

                <!-- LEFT SIDEBAR (30%) -->
                <aside class="w-full lg:w-[30%] space-y-5 shrink-0">

                    <!-- Resume -->
                    <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm space-y-4">
                        <h2 class="text-sm font-semibold text-slate-900">Resume de la commande</h2>
                        <div class="space-y-3 text-sm">
                            <div><p class="text-xs text-slate-500">Service</p><p class="font-semibold text-slate-900">{{ order.service?.title ?? '-' }}</p></div>
                            <div><p class="text-xs text-slate-500">Vendeur</p><p class="font-semibold text-slate-900">{{ order.vendor?.shop_name || order.vendor?.business_name || order.vendor?.name || '-' }}</p></div>
                            <div><p class="text-xs text-slate-500">Produit</p><p class="font-semibold text-slate-900">{{ order.product?.name ?? 'Aucun' }}</p></div>
                            <div><p class="text-xs text-slate-500">Montant</p><p class="font-semibold text-slate-900">{{ formatCurrency(order.amount) }}</p></div>
                            <div class="flex items-center gap-2 text-xs text-slate-500">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182M20.982 4.356v4.993" /></svg>
                                Retouches : {{ order.revisions_used }} / {{ order.revisions_allowed }}
                            </div>
                        </div>
                        <div class="rounded-lg border border-slate-100 bg-slate-50 p-3">
                            <p class="text-xs font-medium text-slate-500 mb-1">Brief du vendeur</p>
                            <p class="text-xs text-slate-700 whitespace-pre-line">{{ order.brief }}</p>
                        </div>
                    </div>

                    <!-- Produit -->
                    <div v-if="hasProduct" class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm space-y-3">
                        <h2 class="text-sm font-semibold text-slate-900 flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-purple-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 10-7.5 0v4.5m11.356-1.993l1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 01-1.12-1.243l1.264-12A1.125 1.125 0 015.513 7.5h12.974c.576 0 1.059.435 1.119 1.007zM8.625 10.5a.375.375 0 11-.75 0 .375.375 0 01.75 0zm7.5 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" /></svg>
                            Produit
                        </h2>
                        <div v-if="selectedImage" class="space-y-2">
                            <div class="relative aspect-square overflow-hidden rounded-lg border border-slate-200 bg-slate-50">
                                <img :src="`/storage/${selectedImage.path}`" :alt="order.product.name" class="h-full w-full object-cover" />
                                <a :href="`/storage/${selectedImage.path}`" :download="order.product.name"
                                    class="absolute bottom-2 right-2 inline-flex items-center gap-1 rounded-lg bg-white/90 px-2 py-1 text-[10px] font-semibold text-slate-700 shadow-sm ring-1 ring-slate-200 backdrop-blur transition hover:bg-white">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" /></svg>
                                    Telecharger
                                </a>
                            </div>
                            <div v-if="productImages.length > 1" class="flex gap-1.5 overflow-x-auto">
                                <button v-for="(img, idx) in productImages" :key="img.id || idx" @click="selectedImageIndex = idx"
                                    class="h-10 w-10 shrink-0 overflow-hidden rounded-md border-2 transition"
                                    :class="selectedImageIndex === idx ? 'border-purple-500 ring-1 ring-purple-500/30' : 'border-slate-200 hover:border-slate-300'">
                                    <img :src="`/storage/${img.path}`" class="h-full w-full object-cover" :alt="`Image ${idx + 1}`" />
                                </button>
                            </div>
                        </div>
                        <h3 class="text-sm font-bold text-slate-900">{{ order.product.name }}</h3>
                        <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-semibold ring-1"
                              :class="order.product.type === 'physical' ? 'bg-teal-50 text-teal-700 ring-teal-600/10' : 'bg-purple-50 text-purple-700 ring-purple-600/10'">
                            {{ order.product.type === 'physical' ? 'Produit physique' : 'Produit digital' }}
                        </span>
                        <p v-if="order.product.description" class="text-xs text-slate-600 whitespace-pre-line line-clamp-4">{{ order.product.description }}</p>
                    </div>

                    <!-- Echantillon -->
                    <div v-if="needsSample" class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm space-y-3">
                        <div class="flex items-center justify-between">
                            <h2 class="text-sm font-semibold text-slate-900 flex items-center gap-2">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-teal-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 01-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 00-3.213-9.193 2.056 2.056 0 00-1.58-.86H14.25M16.5 18.75h-2.25m0-11.177v-.958c0-.568-.422-1.048-.987-1.106a48.554 48.554 0 00-10.026 0 1.106 1.106 0 00-.987 1.106v7.635m12-6.677v6.677m0 4.5v-4.5m0 0h-12" /></svg>
                                Echantillon
                            </h2>
                            <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-semibold ring-1"
                                  :class="[getSampleStatus(order.sample_status).bg, getSampleStatus(order.sample_status).text, getSampleStatus(order.sample_status).ring]">
                                {{ getSampleStatus(order.sample_status).label }}
                            </span>
                        </div>

                        <!-- Stepper -->
                        <div class="flex items-center gap-2">
                            <div class="flex h-7 w-7 items-center justify-center rounded-full" :class="order.sample_status !== 'pending_shipment' ? 'bg-teal-100' : 'bg-amber-100'">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" :class="order.sample_status !== 'pending_shipment' ? 'text-teal-600' : 'text-amber-600'" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M21 7.5l-2.25-1.313M21 7.5v2.25m0-2.25l-2.25 1.313M3 7.5l2.25-1.313M3 7.5l2.25 1.313M3 7.5v2.25m9 3l2.25-1.313M12 12.75l-2.25-1.313M12 12.75V15m0 6.75l2.25-1.313M12 21.75V15m0 0l-2.25 1.313" /></svg>
                            </div>
                            <div class="h-px flex-1" :class="order.sample_status === 'pending_shipment' ? 'bg-slate-200' : 'bg-teal-300'" />
                            <div class="flex h-7 w-7 items-center justify-center rounded-full" :class="['shipped','received'].includes(order.sample_status) ? 'bg-teal-100' : 'bg-slate-100'">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" :class="['shipped','received'].includes(order.sample_status) ? 'text-teal-600' : 'text-slate-400'" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 01-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 00-3.213-9.193 2.056 2.056 0 00-1.58-.86H14.25M16.5 18.75h-2.25m0-11.177v-.958c0-.568-.422-1.048-.987-1.106a48.554 48.554 0 00-10.026 0 1.106 1.106 0 00-.987 1.106v7.635m12-6.677v6.677m0 4.5v-4.5m0 0h-12" /></svg>
                            </div>
                            <div class="h-px flex-1" :class="order.sample_status === 'received' ? 'bg-teal-300' : 'bg-slate-200'" />
                            <div class="flex h-7 w-7 items-center justify-center rounded-full" :class="order.sample_status === 'received' ? 'bg-teal-100' : 'bg-slate-100'">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" :class="order.sample_status === 'received' ? 'text-teal-600' : 'text-slate-400'" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            </div>
                        </div>

                        <!-- Delivery info -->
                        <div v-if="order.sample_delivery_guy_name && (sampleIsShipped || sampleIsReceived)" class="rounded-lg border border-teal-200 bg-teal-50 p-3 space-y-1.5">
                            <p class="text-xs font-medium text-teal-600">Livreur</p>
                            <p class="text-sm font-semibold text-slate-900">{{ order.sample_delivery_guy_name }}</p>
                            <a :href="'tel:' + order.sample_delivery_guy_phone" class="text-sm font-semibold text-teal-700 underline hover:text-teal-900">{{ order.sample_delivery_guy_phone }}</a>
                        </div>

                        <button v-if="sampleIsShipped" @click="confirmSampleReceived" :disabled="processing"
                            class="w-full inline-flex items-center justify-center gap-2 rounded-lg bg-teal-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-teal-700 disabled:opacity-50">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            J'ai recu l'echantillon (Demarrer le chrono)
                        </button>

                        <div v-if="order.sample_status === 'pending_shipment'" class="rounded-lg bg-amber-50 p-3 text-xs text-amber-800 font-medium">
                            Le vendeur n'a pas encore expedie l'echantillon.
                        </div>
                        <div v-if="sampleIsReceived" class="rounded-lg bg-teal-50 p-3 text-xs text-teal-800 font-medium">
                            Echantillon recu. Vous pouvez commencer la production.
                        </div>
                    </div>

                    <!-- Video -->
                    <div v-if="order.video_path" class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm">
                        <h2 class="mb-3 text-sm font-semibold text-slate-900 flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-teal-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" d="M15.75 10.5l4.72-4.72a.75.75 0 011.28.53v11.38a.75.75 0 01-1.28.53l-4.72-4.72M4.5 18.75h9a2.25 2.25 0 002.25-2.25v-9a2.25 2.25 0 00-2.25-2.25h-9A2.25 2.25 0 002.25 7.5v9a2.25 2.25 0 002.25 2.25z" /></svg>
                            Votre video
                        </h2>
                        <video :src="`/storage/${order.video_path}`" controls class="w-full rounded-lg border border-slate-200 bg-black" />
                    </div>

                    <!-- Actions -->
                    <div v-if="order.status === 'pending'" class="space-y-2">
                        <button @click="accept" :disabled="processing"
                            class="w-full inline-flex items-center justify-center gap-2 rounded-full bg-gradient-to-r from-teal-500 to-cyan-500 px-4 py-2.5 text-sm font-semibold text-white shadow-md shadow-teal-500/20 transition-all duration-300 hover:shadow-lg hover:shadow-teal-500/30 disabled:opacity-50">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                            Accepter la commande
                        </button>
                    </div>

                    <!-- Cancel -->
                    <div v-if="canCancel" class="mt-2">
                        <button @click="cancelOrder" :disabled="processing"
                            class="w-full inline-flex items-center justify-center gap-2 rounded-lg bg-red-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-red-700 disabled:opacity-50">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m9.75 9.75 4.5 4.5m0-4.5-4.5 4.5M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
                            Annuler la commande
                        </button>
                    </div>

                    <div v-if="canUpload && sampleReady" class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm">
                        <h2 class="mb-3 text-sm font-semibold text-slate-900">
                            {{ isRevisionRequested ? 'Envoyer la version corrigee' : 'Livrer la video' }}
                        </h2>
                        <label class="inline-flex w-full cursor-pointer items-center justify-center gap-2 rounded-lg bg-teal-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-teal-700"
                            :class="{ 'opacity-50 pointer-events-none': deliverForm.processing }">
                            <svg v-if="!deliverForm.processing" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5" /></svg>
                            <svg v-else class="h-4 w-4 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" /><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" /></svg>
                            {{ deliverForm.processing ? 'Envoi en cours...' : 'Choisir la video' }}
                            <input type="file" accept="video/mp4,video/quicktime,video/webm" class="hidden" @change="deliverVideo($event)" />
                        </label>
                        <p v-if="deliverForm.errors.video" class="mt-2 text-xs text-red-600">{{ deliverForm.errors.video }}</p>
                        <p class="mt-2 text-xs text-slate-500">MP4, MOV, WebM. Max 100 Mo.</p>
                    </div>

                    <div v-if="canUpload && needsSample && !sampleIsReceived" class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm font-semibold text-amber-800">
                        Vous devez recevoir l'echantillon avant de commencer la production.
                    </div>

                    <!-- Final statuses -->
                    <div v-if="order.status === 'completed'" class="rounded-xl border border-teal-200 bg-teal-50 p-4 text-sm font-semibold text-teal-800">Commande terminee. Vous avez ete paye.</div>
                    <div v-if="order.status === 'delivered'" class="rounded-xl border border-purple-200 bg-purple-50 p-4 text-sm font-semibold text-purple-800">Video livree. En attente de validation du vendeur.</div>
                    <div v-if="order.status === 'disputed'" class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm font-semibold text-red-800">Litige en cours d'examen.</div>
                    <div v-if="order.status === 'cancelled'" class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm font-semibold text-red-800">Commande annulee. Le vendeur a ete rembourse.</div>
                </aside>

                <!-- RIGHT COLUMN: CHAT (70%) -->
                <section class="flex-1 flex flex-col rounded-2xl border border-slate-200/80 bg-white shadow-sm overflow-hidden" style="min-height: 600px;">

                    <!-- Security banner -->
                    <div class="flex items-center gap-2.5 border-b border-slate-100 bg-purple-50 px-5 py-3">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0 text-purple-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" />
                        </svg>
                        <p class="text-xs font-medium text-purple-800">MANTOTA securise vos fonds. Ne communiquez jamais en dehors de cet espace.</p>
                    </div>

                    <!-- Messages area -->
                    <div ref="chatContainer" class="flex-1 overflow-y-auto px-5 py-4 space-y-4">
                        <div v-if="!order.messages?.length" class="flex flex-col items-center justify-center h-full text-center text-slate-400">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 mb-2 text-slate-300" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H8.25m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H12m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 01-2.555-.337A5.972 5.972 0 015.41 20.97a5.969 5.969 0 01-.474-.065 4.48 4.48 0 00.978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25z" /></svg>
                            <p class="text-sm">Aucun message pour le moment.</p>
                            <p class="text-xs mt-1">Commencez la conversation avec le vendeur.</p>
                        </div>

                        <template v-for="msg in order.messages" :key="msg.id">
                            <!-- ADMIN message: full-width authoritative bar -->
                            <div v-if="msg.sender?.role === 'admin'" class="w-full">
                                <div class="rounded-xl bg-slate-900 border-l-4 border-purple-500 px-5 py-4 space-y-2">
                                    <div class="flex items-center gap-2">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-purple-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" /></svg>
                                        <span class="text-xs font-bold uppercase tracking-wider text-purple-400">ADMINISTRATION MANTOTA (MODERATION)</span>
                                        <span class="ml-auto text-[10px] text-slate-500">{{ formatTime(msg.created_at) }}</span>
                                    </div>
                                    <p v-if="msg.message" class="text-sm text-white whitespace-pre-line leading-relaxed">{{ msg.message }}</p>
                                    <div v-if="msg.attachment_path" class="mt-2">
                                        <img v-if="isImage(msg.attachment_path)" :src="`/storage/${msg.attachment_path}`" class="max-w-full max-h-48 rounded-lg border border-purple-500/30" alt="Piece jointe" />
                                        <a v-else :href="`/storage/${msg.attachment_path}`" target="_blank" class="inline-flex items-center gap-1.5 rounded-lg bg-purple-900 px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-purple-800">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M18.375 12.739l-7.693 7.693a4.5 4.5 0 01-6.364-6.364l10.94-10.94A3 3 0 1119.5 7.372L8.552 18.32m.009-.01l-.01.01m5.699-9.941l-7.81 7.81a1.5 1.5 0 002.112 2.13" /></svg>
                                            Telecharger
                                        </a>
                                    </div>
                                </div>
                            </div>
                            <!-- User message (vendor/influencer) -->
                            <div v-else class="flex" :class="msg.sender_id === authId ? 'justify-end' : 'justify-start'">
                                <div class="max-w-[75%] space-y-1">
                                    <p class="text-[10px] font-medium px-1" :class="msg.sender_id === authId ? 'text-right text-teal-600' : 'text-slate-500'">
                                        {{ msg.sender?.name ?? 'Utilisateur' }}
                                    </p>
                                    <div class="rounded-2xl px-4 py-2.5 text-sm leading-relaxed"
                                         :class="msg.sender_id === authId ? 'bg-teal-600 text-white rounded-br-md' : 'bg-gray-100 text-slate-800 rounded-bl-md'">
                                        <p v-if="msg.message" class="whitespace-pre-line">{{ msg.message }}</p>
                                        <div v-if="msg.attachment_path" class="mt-2">
                                            <img v-if="isImage(msg.attachment_path)" :src="`/storage/${msg.attachment_path}`"
                                                class="max-w-full max-h-48 rounded-lg border" :class="msg.sender_id === authId ? 'border-teal-500/30' : 'border-slate-200'" alt="Piece jointe" />
                                            <a v-else :href="`/storage/${msg.attachment_path}`" target="_blank"
                                                class="inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-semibold transition"
                                                :class="msg.sender_id === authId ? 'bg-teal-700 text-white hover:bg-teal-800' : 'bg-white text-slate-700 border border-slate-200 hover:bg-slate-50'">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M18.375 12.739l-7.693 7.693a4.5 4.5 0 01-6.364-6.364l10.94-10.94A3 3 0 1119.5 7.372L8.552 18.32m.009-.01l-.01.01m5.699-9.941l-7.81 7.81a1.5 1.5 0 002.112 2.13" /></svg>
                                                Telecharger le fichier
                                            </a>
                                        </div>
                                    </div>
                                    <p class="text-[10px] px-1" :class="msg.sender_id === authId ? 'text-right text-slate-400' : 'text-slate-400'">{{ formatTime(msg.created_at) }}</p>
                                </div>
                            </div>
                        </template>
                    </div>

                    <!-- Active chat: attachment indicator + input -->
                    <template v-if="!['completed', 'cancelled', 'disputed_resolved'].includes(order.status)">
                        <!-- Attachment indicator -->
                        <div v-if="attachmentName" class="flex items-center gap-2 border-t border-slate-100 bg-slate-50 px-5 py-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M18.375 12.739l-7.693 7.693a4.5 4.5 0 01-6.364-6.364l10.94-10.94A3 3 0 1119.5 7.372L8.552 18.32m.009-.01l-.01.01m5.699-9.941l-7.81 7.81a1.5 1.5 0 002.112 2.13" /></svg>
                            <span class="text-xs text-slate-600 truncate flex-1">{{ attachmentName }}</span>
                            <button @click="clearAttachment" class="text-slate-400 hover:text-red-500 transition">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                            </button>
                        </div>

                        <!-- Input area -->
                        <div class="border-t border-slate-200 bg-slate-50 px-4 py-3">
                            <form @submit.prevent="sendMessage" class="flex items-end gap-2">
                                <input type="file" ref="fileInput" class="hidden" accept="image/*,.pdf" @change="onFileSelected" />
                                <button type="button" @click="pickFile" class="shrink-0 rounded-full p-2 text-slate-400 transition hover:bg-slate-200 hover:text-slate-600">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M18.375 12.739l-7.693 7.693a4.5 4.5 0 01-6.364-6.364l10.94-10.94A3 3 0 1119.5 7.372L8.552 18.32m.009-.01l-.01.01m5.699-9.941l-7.81 7.81a1.5 1.5 0 002.112 2.13" /></svg>
                                </button>
                                <textarea v-model="chatForm.message" rows="1"
                                    class="flex-1 resize-none rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm shadow-sm transition placeholder:text-slate-400 focus:border-teal-500 focus:ring-teal-500"
                                    placeholder="Ecrivez votre message..." @keydown.enter.exact.prevent="sendMessage" />
                                <button type="submit" :disabled="chatForm.processing"
                                    class="shrink-0 rounded-full bg-teal-600 p-2.5 text-white shadow-sm transition hover:bg-teal-700 disabled:opacity-50">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 12L3.269 3.126A59.768 59.768 0 0121.485 12 59.77 59.77 0 013.27 20.876L5.999 12zm0 0h7.5" /></svg>
                                </button>
                            </form>
                            <p v-if="chatForm.errors.message" class="mt-1 text-xs text-red-600">{{ chatForm.errors.message }}</p>
                            <p v-if="chatForm.errors.attachment" class="mt-1 text-xs text-red-600">{{ chatForm.errors.attachment }}</p>
                        </div>
                    </template>

                    <!-- Locked chat banner -->
                    <div v-else class="border-t border-slate-300 bg-slate-100 px-6 py-4">
                        <div class="flex items-center justify-center gap-2 text-slate-600">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
                            </svg>
                            <span class="text-sm font-medium">Commande validee. Cette conversation est definitivement fermee.</span>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </InfluencerLayout>

    <ConfirmModal :show="confirmVisible" :title="confirmTitle" :message="confirmMessage" :variant="confirmVariant" :confirm-label="confirmLabel" :cancel-label="cancelLabel" @confirmed="onConfirm" @cancelled="onCancel" />
</template>
