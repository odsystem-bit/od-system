<script setup>
import VendorLayout from '../../Layouts/VendorLayout.vue';
import ConfirmModal from '../../../Components/ConfirmModal.vue';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { ref, computed, nextTick, onMounted } from 'vue';
import { useConfirm } from '../../../Composables/useConfirm.js';

const props = defineProps({
    order: { type: Object, required: true },
    authId: { type: Number, required: true },
    ugc_studio_fee_percent: { type: Number, default: 15 },
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
    shooting:            { label: 'En production',      bg: 'bg-purple-50',    text: 'text-purple-700',    ring: 'ring-purple-600/10' },
    delivered:           { label: 'Livree',             bg: 'bg-purple-50',  text: 'text-purple-700',  ring: 'ring-purple-600/10' },
    revision_requested:  { label: 'Retouche demandee',  bg: 'bg-amber-50',   text: 'text-amber-700',   ring: 'ring-amber-600/10' },
    completed:           { label: 'Terminee',           bg: 'bg-purple-50',    text: 'text-purple-700',    ring: 'ring-purple-600/10' },
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
    received:         { label: 'Recu',               bg: 'bg-purple-50',    text: 'text-purple-700',   ring: 'ring-purple-600/10' },
};

function getSampleStatus(s) {
    return sampleStatusConfig[s] || { label: s, bg: 'bg-slate-50', text: 'text-slate-600', ring: 'ring-slate-600/10' };
}

const isDelivered        = computed(() => props.order.status === 'delivered');
const canRequestRevision = computed(() => isDelivered.value && props.order.revisions_used < props.order.revisions_allowed);
const canDispute         = computed(() => isDelivered.value && props.order.revisions_used >= props.order.revisions_allowed);
const needsSample        = computed(() => props.order.sample_status && props.order.sample_status !== 'not_required');
const canShipSample      = computed(() => props.order.sample_status === 'pending_shipment' && !['cancelled', 'completed', 'disputed'].includes(props.order.status));
const sampleIsShipped    = computed(() => props.order.sample_status === 'shipped');
const sampleIsReceived   = computed(() => props.order.sample_status === 'received');

// ──────────────────────────────────────────────
//  Actions
// ──────────────────────────────────────────────

const processing = ref(false);

const { visible: confirmVisible, title: confirmTitle, message: confirmMessage, variant: confirmVariant, confirmLabel, cancelLabel, ask, onConfirm, onCancel } = useConfirm();

async function approve() {
    if (!await ask({ title: 'Approuver la video', message: 'Approuver cette video et payer le createur de contenu ?', variant: 'info', confirmLabel: 'Approuver' })) return;
    processing.value = true;
    router.post(route('vendor.service-orders.approve', props.order.id), {}, {
        preserveScroll: true, onFinish: () => (processing.value = false),
    });
}

async function openDispute() {
    if (!await ask({ title: 'Ouvrir un litige', message: 'Ouvrir un litige ? L\'equipe MANTOTA examinera le dossier.', variant: 'warning', confirmLabel: 'Ouvrir le litige' })) return;
    processing.value = true;
    router.post(route('vendor.service-orders.dispute', props.order.id), {}, {
        preserveScroll: true, onFinish: () => (processing.value = false),
    });
}

async function cancelOrder() {
    if (!await ask({ title: 'Annuler la demande', message: 'Annuler cette demande ? Votre solde sera recredite.', variant: 'danger', confirmLabel: 'Annuler' })) return;
    processing.value = true;
    router.post(route('vendor.service-orders.cancel', props.order.id), {}, {
        preserveScroll: true, onFinish: () => (processing.value = false),
    });
}

// ── Revision modal ──
const showRevisionModal = ref(false);
const revisionForm = useForm({ revision_feedback: '' });
function submitRevision() {
    revisionForm.post(route('vendor.service-orders.revision', props.order.id), {
        preserveScroll: true,
        onSuccess: () => { showRevisionModal.value = false; revisionForm.reset(); },
    });
}

// ── Ship sample modal ──
const showShipModal = ref(false);
const shipForm = useForm({ delivery_name: '', delivery_phone: '' });
function submitShipSample() {
    shipForm.post(route('vendor.service-orders.ship-sample', props.order.id), {
        preserveScroll: true,
        onSuccess: () => { showShipModal.value = false; shipForm.reset(); },
    });
}

// ──────────────────────────────────────────────
//  Chat
// ──────────────────────────────────────────────

const chatContainer = ref(null);
const fileInput     = ref(null);
const chatForm      = useForm({ message: '', attachment: null });
const attachmentName = ref('');
const chatUploadProgress = ref(0);

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
    chatUploadProgress.value = 0;
    chatForm.post(route('vendor.service-orders.messages.store', props.order.id), {
        preserveScroll: true,
        forceFormData: true,
        onProgress: (progress) => { chatUploadProgress.value = progress.percentage ?? 0; },
        onSuccess: () => { chatForm.reset(); clearAttachment(); chatUploadProgress.value = 0; scrollToBottom(); },
        onFinish: () => { chatUploadProgress.value = 0; },
    });
}
</script>

<template>
    <Head :title="`Commande #${order.id}`" />

    <VendorLayout>
        <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6">

            <!-- Header -->
            <div class="mb-5 flex items-center gap-4">
                <Link :href="route('vendor.service-orders.index')"
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
                <!-- Heroicon: LockClosed -->
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0 text-amber-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
                </svg>
                <p class="text-sm font-semibold text-amber-800">{{ flash.warning }}</p>
            </div>

            <!-- ══════ 2-COLUMN LAYOUT ══════ -->
            <div class="flex flex-col lg:flex-row gap-6">

                <!-- ─── LEFT SIDEBAR (30%) ─── -->
                <aside class="w-full lg:w-[30%] space-y-5 shrink-0">

                    <!-- Resume -->
                    <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm space-y-4">
                        <h2 class="text-sm font-semibold text-slate-900">Resume de la commande</h2>
                        <div class="space-y-3 text-sm">
                            <div><p class="text-xs text-slate-500">Service</p><p class="font-semibold text-slate-900">{{ order.service?.title ?? '-' }}</p></div>
                            <div><p class="text-xs text-slate-500">Createur de Contenu</p><p class="font-semibold text-slate-900"><Link v-if="order.influencer" :href="route('vendor.influencer.show', order.influencer.id)" class="text-purple-600 hover:text-purple-800 underline">{{ order.influencer.name }}</Link><template v-else>-</template></p></div>
                            <div><p class="text-xs text-slate-500">Produit</p><p class="font-semibold text-slate-900">{{ order.product?.name ?? 'Aucun' }}</p></div>
                            <div><p class="text-xs text-slate-500">Montant</p><p class="font-semibold text-slate-900">{{ formatCurrency(order.amount) }}</p></div>
                            <div class="flex items-center gap-2 text-xs text-slate-500">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182M20.982 4.356v4.993" /></svg>
                                Retouches : {{ order.revisions_used }} / {{ order.revisions_allowed }}
                            </div>
                        </div>
                        <div class="rounded-lg border border-slate-100 bg-slate-50 p-3">
                            <p class="text-xs font-medium text-slate-500 mb-1">Brief</p>
                            <p class="text-xs text-slate-700 whitespace-pre-line">{{ order.brief }}</p>
                        </div>
                        <div v-if="order.revision_feedback" class="rounded-lg border border-amber-200 bg-amber-50 p-3">
                            <p class="text-xs font-medium text-amber-700 mb-1">Feedback retouche</p>
                            <p class="text-xs text-amber-800 whitespace-pre-line">{{ order.revision_feedback }}</p>
                        </div>
                    </div>

                    <!-- Echantillon -->
                    <div v-if="needsSample" class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm space-y-3">
                        <div class="flex items-center justify-between">
                            <h2 class="text-sm font-semibold text-slate-900 flex items-center gap-2">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-purple-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 01-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 00-3.213-9.193 2.056 2.056 0 00-1.58-.86H14.25M16.5 18.75h-2.25m0-11.177v-.958c0-.568-.422-1.048-.987-1.106a48.554 48.554 0 00-10.026 0 1.106 1.106 0 00-.987 1.106v7.635m12-6.677v6.677m0 4.5v-4.5m0 0h-12" /></svg>
                                Echantillon
                            </h2>
                            <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-semibold ring-1"
                                  :class="[getSampleStatus(order.sample_status).bg, getSampleStatus(order.sample_status).text, getSampleStatus(order.sample_status).ring]">
                                {{ getSampleStatus(order.sample_status).label }}
                            </span>
                        </div>
                        <button v-if="canShipSample" @click="showShipModal = true"
                            class="w-full inline-flex items-center justify-center gap-2 rounded-full bg-purple-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-purple-700">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 01-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 00-3.213-9.193 2.056 2.056 0 00-1.58-.86H14.25M16.5 18.75h-2.25m0-11.177v-.958c0-.568-.422-1.048-.987-1.106a48.554 48.554 0 00-10.026 0 1.106 1.106 0 00-.987 1.106v7.635m12-6.677v6.677m0 4.5v-4.5m0 0h-12" /></svg>
                            Expedier l'echantillon
                        </button>
                        <div v-if="sampleIsShipped" class="rounded-lg bg-blue-50 p-3 text-xs text-blue-800 font-medium">En cours de livraison</div>
                        <div v-if="sampleIsReceived" class="rounded-lg bg-purple-50 p-3 text-xs text-purple-800 font-medium">Echantillon recu</div>
                    </div>

                    <!-- Video -->
                    <div v-if="order.video_path" class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm">
                        <h2 class="mb-3 text-sm font-semibold text-slate-900 flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-purple-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" d="M15.75 10.5l4.72-4.72a.75.75 0 011.28.53v11.38a.75.75 0 01-1.28.53l-4.72-4.72M4.5 18.75h9a2.25 2.25 0 002.25-2.25v-9a2.25 2.25 0 00-2.25-2.25h-9A2.25 2.25 0 002.25 7.5v9a2.25 2.25 0 002.25 2.25z" /></svg>
                            Video livree
                        </h2>
                        <video :src="`/storage/${order.video_path}`" controls class="w-full rounded-lg border border-slate-200 bg-black" />
                    </div>

                    <!-- Action buttons -->
                    <div v-if="order.status === 'pending'" class="space-y-2">
                        <button @click="cancelOrder" :disabled="processing"
                            class="w-full inline-flex items-center justify-center gap-2 rounded-full bg-red-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-red-700 disabled:opacity-50">
                            <svg v-if="processing" class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m9.75 9.75 4.5 4.5m0-4.5-4.5 4.5M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
                            <span v-if="processing">Traitement...</span>
                            <span v-else>Annuler la demande</span>
                        </button>
                    </div>
                    <div v-if="isDelivered" class="space-y-2">
                        <button @click="approve" :disabled="processing"
                            class="w-full inline-flex items-center justify-center gap-2 rounded-full bg-purple-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-purple-700 disabled:opacity-50">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            Approuver la video
                        </button>
                        <button v-if="canRequestRevision" @click="showRevisionModal = true" :disabled="processing"
                            class="w-full inline-flex items-center justify-center gap-2 rounded-lg bg-amber-500 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-amber-600 disabled:opacity-50">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182M20.982 4.356v4.993" /></svg>
                            Demander une retouche
                        </button>
                        <button v-if="canDispute" @click="openDispute" :disabled="processing"
                            class="w-full inline-flex items-center justify-center gap-2 rounded-full bg-red-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-red-700 disabled:opacity-50">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" /></svg>
                            Ouvrir un litige
                        </button>
                    </div>

                    <!-- Final statuses -->
                    <div v-if="order.status === 'completed'" class="rounded-xl border border-purple-200 bg-purple-50 p-4 text-sm font-semibold text-purple-800">Commande terminee. Le createur de contenu a ete paye.</div>
                    <div v-if="order.status === 'disputed'" class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm font-semibold text-red-800">Litige en cours d'examen.</div>
                    <div v-if="order.status === 'cancelled'" class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm font-semibold text-red-800">Commande annulee. Votre solde a ete recredite.</div>
                    <div v-if="order.status === 'revision_requested'" class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm font-semibold text-amber-800">Retouche demandee. En attente de la nouvelle version.</div>
                </aside>

                <!-- ─── RIGHT COLUMN: CHAT (70%) ─── -->
                <section class="flex-1 flex flex-col rounded-2xl border border-slate-200/80 bg-white shadow-sm overflow-hidden" style="min-height: 600px;">

                    <!-- Security banner -->
                    <div class="flex items-center gap-2.5 border-b border-slate-100 bg-purple-50 px-5 py-3">
                        <!-- Heroicon: ShieldCheck -->
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
                            <p class="text-xs mt-1">Commencez la conversation avec le createur de contenu.</p>
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
                                        <img v-if="isImage(msg.attachment_path)" :src="`/storage/${msg.attachment_path}`" class="max-w-full max-h-48 rounded-lg border border-purple-500/30" alt="Piece jointe" loading="lazy" />
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
                                    <p class="text-[10px] font-medium px-1" :class="msg.sender_id === authId ? 'text-right text-purple-600' : 'text-slate-500'">
                                        {{ msg.sender?.name ?? 'Utilisateur' }}
                                    </p>
                                    <div class="rounded-2xl px-4 py-2.5 text-sm leading-relaxed"
                                         :class="msg.sender_id === authId ? 'bg-purple-600 text-white rounded-br-md' : 'bg-gray-100 text-slate-800 rounded-bl-md'">
                                        <p v-if="msg.message" class="whitespace-pre-line">{{ msg.message }}</p>
                                        <div v-if="msg.attachment_path" class="mt-2">
                                            <img v-if="isImage(msg.attachment_path)" :src="`/storage/${msg.attachment_path}`"
                                                class="max-w-full max-h-48 rounded-lg border" :class="msg.sender_id === authId ? 'border-purple-500/30' : 'border-slate-200'" alt="Piece jointe" loading="lazy" />
                                            <a v-else :href="`/storage/${msg.attachment_path}`" target="_blank"
                                                class="inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-semibold transition"
                                                :class="msg.sender_id === authId ? 'bg-purple-700 text-white hover:bg-purple-800' : 'bg-white text-slate-700 border border-slate-200 hover:bg-slate-50'">
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

                        <!-- Upload progress bar -->
                        <div v-if="chatForm.processing && chatUploadProgress > 0" class="border-t border-slate-100 bg-slate-50 px-5 py-2">
                            <div class="flex items-center gap-3">
                                <div class="flex-1 h-2 bg-slate-200 rounded-full overflow-hidden">
                                    <div class="h-full bg-purple-500 rounded-full transition-all duration-300" :style="{ width: chatUploadProgress + '%' }" />
                                </div>
                                <span class="text-xs font-semibold text-purple-700 tabular-nums">{{ Math.round(chatUploadProgress) }}%</span>
                            </div>
                            <p class="text-[10px] text-slate-500 mt-1">Envoi du fichier en cours...</p>
                        </div>

                        <!-- Input area -->
                        <div class="border-t border-slate-200 bg-slate-50 px-4 py-3">
                            <form @submit.prevent="sendMessage" class="flex items-end gap-2">
                                <input type="file" ref="fileInput" class="hidden" accept="image/*,.pdf" @change="onFileSelected" />
                                <!-- PaperClip -->
                                <button type="button" @click="pickFile" class="shrink-0 rounded-full p-2 text-slate-400 transition hover:bg-slate-200 hover:text-slate-600">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M18.375 12.739l-7.693 7.693a4.5 4.5 0 01-6.364-6.364l10.94-10.94A3 3 0 1119.5 7.372L8.552 18.32m.009-.01l-.01.01m5.699-9.941l-7.81 7.81a1.5 1.5 0 002.112 2.13" /></svg>
                                </button>
                                <textarea v-model="chatForm.message" rows="1"
                                    class="flex-1 resize-none rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm shadow-sm transition placeholder:text-slate-400 focus:border-purple-500 focus:ring-purple-500"
                                    placeholder="Ecrivez votre message..." @keydown.enter.exact.prevent="sendMessage" />
                                <!-- PaperAirplane -->
                                <button type="submit" :disabled="chatForm.processing"
                                    class="shrink-0 rounded-full bg-purple-600 p-2.5 text-white shadow-sm transition hover:bg-purple-700 disabled:opacity-50">
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

        <!-- ─── Revision Modal ─── -->
        <Teleport to="body">
            <div v-if="showRevisionModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
                <div class="w-full max-w-lg rounded-xl bg-white p-6 shadow-xl" @click.stop>
                    <h3 class="text-lg font-bold text-slate-900">Demander une retouche</h3>
                    <p class="mt-2 text-sm text-slate-500">Decrivez precisement les modifications souhaitees.</p>
                    <form @submit.prevent="submitRevision" class="mt-4 space-y-4">
                        <textarea v-model="revisionForm.revision_feedback" rows="4"
                            class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-amber-500 focus:ring-amber-500"
                            placeholder="Decrivez les modifications..." />
                        <p v-if="revisionForm.errors.revision_feedback" class="text-xs text-red-600">{{ revisionForm.errors.revision_feedback }}</p>
                        <div class="flex justify-end gap-3">
                            <button type="button" @click="showRevisionModal = false; revisionForm.reset()"
                                class="rounded-full border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Annuler</button>
                            <button type="submit" :disabled="revisionForm.processing"
                                class="rounded-lg bg-amber-500 px-4 py-2 text-sm font-semibold text-white hover:bg-amber-600 disabled:opacity-50">Envoyer</button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>

        <!-- ─── Ship sample Modal ─── -->
        <Teleport to="body">
            <div v-if="showShipModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
                <div class="w-full max-w-lg rounded-xl bg-white p-6 shadow-xl" @click.stop>
                    <h3 class="text-lg font-bold text-slate-900 flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-purple-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 01-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 00-3.213-9.193 2.056 2.056 0 00-1.58-.86H14.25M16.5 18.75h-2.25m0-11.177v-.958c0-.568-.422-1.048-.987-1.106a48.554 48.554 0 00-10.026 0 1.106 1.106 0 00-.987 1.106v7.635m12-6.677v6.677m0 4.5v-4.5m0 0h-12" /></svg>
                        Expedier l'echantillon
                    </h3>
                    <form @submit.prevent="submitShipSample" class="mt-4 space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700">Nom du livreur</label>
                            <input v-model="shipForm.delivery_name" type="text" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-purple-500 focus:ring-purple-500" placeholder="Ex : Moussa Diallo" />
                            <p v-if="shipForm.errors.delivery_name" class="mt-1 text-xs text-red-600">{{ shipForm.errors.delivery_name }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700">Telephone du livreur</label>
                            <input v-model="shipForm.delivery_phone" type="text" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-purple-500 focus:ring-purple-500" placeholder="Ex : +228 90 12 34 56" />
                            <p v-if="shipForm.errors.delivery_phone" class="mt-1 text-xs text-red-600">{{ shipForm.errors.delivery_phone }}</p>
                        </div>
                        <div class="rounded-lg border border-purple-200 bg-purple-50 p-4 flex gap-3">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0 text-purple-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" /></svg>
                            <div>
                                <p class="text-sm font-semibold text-purple-800">Echantillon Offert</p>
                                <p class="mt-1 text-xs text-purple-700">L'echantillon est considere comme offert a le createur de contenu. MANTOTA ne gere aucun retour apres le tournage.</p>
                            </div>
                        </div>
                        <div class="flex justify-end gap-3">
                            <button type="button" @click="showShipModal = false; shipForm.reset()"
                                class="rounded-full border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Annuler</button>
                            <button type="submit" :disabled="shipForm.processing"
                                class="rounded-full bg-purple-600 px-4 py-2 text-sm font-semibold text-white hover:bg-purple-700 disabled:opacity-50">
                                {{ shipForm.processing ? 'Envoi...' : 'Confirmer l\'expedition' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>
    </VendorLayout>

    <ConfirmModal :show="confirmVisible" :title="confirmTitle" :message="confirmMessage" :variant="confirmVariant" :confirm-label="confirmLabel" :cancel-label="cancelLabel" @confirmed="onConfirm" @cancelled="onCancel" />
</template>
