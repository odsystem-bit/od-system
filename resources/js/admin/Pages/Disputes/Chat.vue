<script setup>
import Layout from '../Layout.vue';
import { Link, useForm } from '@inertiajs/vue3';
import { ref, computed, nextTick, onMounted } from 'vue';

defineOptions({ layout: Layout });

const props = defineProps({
    order: { type: Object, required: true },
    authId: { type: Number, required: true },
});

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

function isAdmin(msg) {
    return msg.sender?.role === 'admin';
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
};

function getStatus(s) {
    return statusConfig[s] || { label: s, bg: 'bg-slate-50', text: 'text-slate-700', ring: 'ring-slate-600/10' };
}

// ──────────────────────────────────────────────
//  Reveal original flagged message
// ──────────────────────────────────────────────

const revealedMessages = ref(new Set());

function toggleReveal(msgId) {
    if (revealedMessages.value.has(msgId)) {
        revealedMessages.value.delete(msgId);
    } else {
        revealedMessages.value.add(msgId);
    }
}

const flaggedCount = computed(() => {
    return (props.order.messages ?? []).filter(m => m.is_flagged).length;
});

// ──────────────────────────────────────────────
//  Chat
// ──────────────────────────────────────────────

const chatContainer = ref(null);
const fileInput     = ref(null);
const chatForm      = useForm({ message: '', attachment: null });
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
    chatForm.post(route('admin.disputes.chat.store', props.order.id), {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => { chatForm.reset(); clearAttachment(); scrollToBottom(); },
    });
}
</script>

<template>
    <div class="space-y-5">

        <!-- Header -->
        <div class="flex items-center gap-4">
            <Link :href="route('admin.disputes.index')"
                class="inline-flex items-center gap-1.5 text-sm font-medium text-slate-500 transition hover:text-slate-700">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" /></svg>
                Tribunal
            </Link>
            <h1 class="text-xl font-bold text-slate-900">Chat -- Commande UGC #{{ order.id }}</h1>
            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold ring-1"
                  :class="[getStatus(order.status).bg, getStatus(order.status).text, getStatus(order.status).ring]">
                {{ getStatus(order.status).label }}
            </span>
            <span v-if="flaggedCount > 0" class="inline-flex items-center gap-1 rounded-full bg-red-50 px-2.5 py-0.5 text-xs font-semibold text-red-700 ring-1 ring-red-600/10">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 3v1.5M3 21v-6m0 0l2.77-.693a9 9 0 016.208.682l.108.054a9 9 0 006.086.71l3.114-.732a48.524 48.524 0 01-.005-10.499l-3.11.732a9 9 0 01-6.085-.711l-.108-.054a9 9 0 00-6.208-.682L3 4.5M3 15V4.5" /></svg>
                {{ flaggedCount }} message(s) suspect(s)
            </span>
        </div>

        <!-- 2-column layout -->
        <div class="flex flex-col lg:flex-row gap-6">

            <!-- LEFT SIDEBAR -->
            <aside class="w-full lg:w-[30%] space-y-5 shrink-0">

                <!-- Order summary -->
                <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm space-y-4">
                    <h2 class="text-sm font-semibold text-slate-900">Resume de la commande</h2>
                    <div class="space-y-3 text-sm">
                        <div><p class="text-xs text-slate-500">Service</p><p class="font-semibold text-slate-900">{{ order.service?.title ?? '-' }}</p></div>
                        <div><p class="text-xs text-slate-500">Vendeur</p><p class="font-semibold text-slate-900">{{ order.vendor?.name ?? '-' }}</p><p class="text-xs text-slate-400">{{ order.vendor?.email ?? '' }}</p></div>
                        <div><p class="text-xs text-slate-500">Createur de Contenu</p><p class="font-semibold text-slate-900">{{ order.influencer?.name ?? '-' }}</p><p class="text-xs text-slate-400">{{ order.influencer?.email ?? '' }}</p></div>
                        <div><p class="text-xs text-slate-500">Produit</p><p class="font-semibold text-slate-900">{{ order.product?.name ?? 'Aucun' }}</p></div>
                        <div><p class="text-xs text-slate-500">Montant (Escrow)</p><p class="font-semibold text-slate-900">{{ formatCurrency(order.amount) }}</p></div>
                        <div class="flex items-center gap-2 text-xs text-slate-500">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182M20.982 4.356v4.993" /></svg>
                            Retouches : {{ order.revisions_used }} / {{ order.revisions_allowed }}
                        </div>
                    </div>
                    <div v-if="order.brief" class="rounded-lg border border-slate-100 bg-slate-50 p-3">
                        <p class="text-xs font-medium text-slate-500 mb-1">Brief</p>
                        <p class="text-xs text-slate-700 whitespace-pre-line">{{ order.brief }}</p>
                    </div>
                </div>

                <!-- Moderation stats -->
                <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm space-y-3">
                    <h2 class="text-sm font-semibold text-slate-900 flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-purple-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" />
                        </svg>
                        Moderation
                    </h2>
                    <div class="grid grid-cols-2 gap-3 text-center">
                        <div class="rounded-lg bg-slate-50 p-3">
                            <p class="text-lg font-bold text-slate-900">{{ order.messages?.length ?? 0 }}</p>
                            <p class="text-[10px] text-slate-500">Messages totaux</p>
                        </div>
                        <div class="rounded-lg p-3" :class="flaggedCount > 0 ? 'bg-red-50' : 'bg-teal-50'">
                            <p class="text-lg font-bold" :class="flaggedCount > 0 ? 'text-red-700' : 'text-teal-700'">{{ flaggedCount }}</p>
                            <p class="text-[10px]" :class="flaggedCount > 0 ? 'text-red-500' : 'text-teal-500'">Suspects</p>
                        </div>
                    </div>
                    <p class="text-[10px] text-slate-400">Cliquez sur l'icone oeil sur les messages suspects pour voir l'original.</p>
                </div>
            </aside>

            <!-- RIGHT: CHAT -->
            <section class="flex-1 flex flex-col rounded-xl border border-slate-200 bg-white shadow-sm overflow-hidden" style="min-height: 600px;">

                <!-- Admin banner -->
                <div class="flex items-center gap-2.5 border-b border-slate-100 bg-slate-900 px-5 py-3">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0 text-purple-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" />
                    </svg>
                    <p class="text-xs font-medium text-white">TOUR DE CONTROLE ADMIN -- Vous avez acces a l'historique complet. Vos messages sont visibles par les deux parties.</p>
                </div>

                <!-- Messages -->
                <div ref="chatContainer" class="flex-1 overflow-y-auto px-5 py-4 space-y-4">
                    <div v-if="!order.messages?.length" class="flex flex-col items-center justify-center h-full text-center text-slate-400">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 mb-2 text-slate-300" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H8.25m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H12m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 01-2.555-.337A5.972 5.972 0 015.41 20.97a5.969 5.969 0 01-.474-.065 4.48 4.48 0 00.978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25z" /></svg>
                        <p class="text-sm">Aucun message dans cette conversation.</p>
                    </div>

                    <template v-for="msg in order.messages" :key="msg.id">

                        <!-- ADMIN message: full-width bar -->
                        <div v-if="isAdmin(msg)" class="w-full">
                            <div class="rounded-xl bg-slate-900 border-l-4 border-purple-500 px-5 py-4 space-y-2">
                                <div class="flex items-center gap-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-purple-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" />
                                    </svg>
                                    <span class="text-xs font-bold uppercase tracking-wider text-purple-400">ADMINISTRATION MANTOTA (MODERATION)</span>
                                    <span class="ml-auto text-[10px] text-slate-500">{{ formatTime(msg.created_at) }}</span>
                                </div>
                                <p v-if="msg.message" class="text-sm text-white whitespace-pre-line leading-relaxed">{{ msg.message }}</p>
                                <div v-if="msg.attachment_path" class="mt-2">
                                    <img v-if="isImage(msg.attachment_path)" :src="`/storage/${msg.attachment_path}`"
                                        class="max-w-full max-h-48 rounded-lg border border-purple-500/30" alt="Piece jointe" />
                                    <a v-else :href="`/storage/${msg.attachment_path}`" target="_blank"
                                        class="inline-flex items-center gap-1.5 rounded-lg bg-purple-900 px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-purple-800">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M18.375 12.739l-7.693 7.693a4.5 4.5 0 01-6.364-6.364l10.94-10.94A3 3 0 1119.5 7.372L8.552 18.32m.009-.01l-.01.01m5.699-9.941l-7.81 7.81a1.5 1.5 0 002.112 2.13" /></svg>
                                        Telecharger
                                    </a>
                                </div>
                            </div>
                        </div>

                        <!-- User message (vendor/influencer) -->
                        <div v-else class="flex" :class="msg.sender_id === order.vendor_id ? 'justify-start' : 'justify-end'">
                            <div class="max-w-[75%] space-y-1">
                                <div class="flex items-center gap-2 px-1">
                                    <p class="text-[10px] font-medium" :class="msg.sender_id === order.vendor_id ? 'text-slate-500' : 'text-teal-600'">
                                        {{ msg.sender?.name ?? 'Utilisateur' }}
                                        <span class="text-[9px] text-slate-400">({{ msg.sender_id === order.vendor_id ? 'Vendeur' : 'Createur de Contenu' }})</span>
                                    </p>
                                    <!-- Flag indicator -->
                                    <span v-if="msg.is_flagged" class="inline-flex items-center gap-0.5 rounded-full bg-red-50 px-1.5 py-0.5 text-[9px] font-semibold text-red-600 ring-1 ring-red-600/10">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 3v1.5M3 21v-6m0 0l2.77-.693a9 9 0 016.208.682l.108.054a9 9 0 006.086.71l3.114-.732a48.524 48.524 0 01-.005-10.499l-3.11.732a9 9 0 01-6.085-.711l-.108-.054a9 9 0 00-6.208-.682L3 4.5M3 15V4.5" /></svg>
                                        SUSPECT
                                    </span>
                                </div>
                                <div class="rounded-2xl px-4 py-2.5 text-sm leading-relaxed"
                                     :class="msg.sender_id === order.vendor_id ? 'bg-gray-100 text-slate-800 rounded-bl-md' : 'bg-teal-600 text-white rounded-br-md'">
                                    <p v-if="msg.message" class="whitespace-pre-line">{{ msg.message }}</p>
                                    <div v-if="msg.attachment_path" class="mt-2">
                                        <img v-if="isImage(msg.attachment_path)" :src="`/storage/${msg.attachment_path}`"
                                            class="max-w-full max-h-48 rounded-lg border" :class="msg.sender_id === order.vendor_id ? 'border-slate-200' : 'border-teal-500/30'" alt="Piece jointe" />
                                        <a v-else :href="`/storage/${msg.attachment_path}`" target="_blank"
                                            class="inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-semibold transition"
                                            :class="msg.sender_id === order.vendor_id ? 'bg-white text-slate-700 border border-slate-200 hover:bg-slate-50' : 'bg-teal-700 text-white hover:bg-teal-800'">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M18.375 12.739l-7.693 7.693a4.5 4.5 0 01-6.364-6.364l10.94-10.94A3 3 0 1119.5 7.372L8.552 18.32m.009-.01l-.01.01m5.699-9.941l-7.81 7.81a1.5 1.5 0 002.112 2.13" /></svg>
                                            Telecharger le fichier
                                        </a>
                                    </div>
                                </div>

                                <!-- Reveal original button (admin only sees this for flagged messages) -->
                                <div v-if="msg.is_flagged && msg.original_message" class="px-1">
                                    <button @click="toggleReveal(msg.id)"
                                        class="inline-flex items-center gap-1 text-[10px] font-semibold text-red-600 transition hover:text-red-800">
                                        <!-- Heroicon: EyeSlash / Eye -->
                                        <svg v-if="!revealedMessages.has(msg.id)" xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" /></svg>
                                        <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178zM15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                                        {{ revealedMessages.has(msg.id) ? 'Masquer l\'original' : 'Voir l\'original' }}
                                    </button>
                                    <div v-if="revealedMessages.has(msg.id)" class="mt-1 rounded-lg border border-red-200 bg-red-50 p-3">
                                        <p class="text-xs text-red-800 whitespace-pre-line">{{ msg.original_message }}</p>
                                    </div>
                                </div>

                                <p class="text-[10px] px-1 text-slate-400" :class="msg.sender_id !== order.vendor_id ? 'text-right' : ''">{{ formatTime(msg.created_at) }}</p>
                            </div>
                        </div>
                    </template>
                </div>

                <!-- Attachment indicator -->
                <div v-if="attachmentName" class="flex items-center gap-2 border-t border-slate-100 bg-slate-50 px-5 py-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M18.375 12.739l-7.693 7.693a4.5 4.5 0 01-6.364-6.364l10.94-10.94A3 3 0 1119.5 7.372L8.552 18.32m.009-.01l-.01.01m5.699-9.941l-7.81 7.81a1.5 1.5 0 002.112 2.13" /></svg>
                    <span class="text-xs text-slate-600 truncate flex-1">{{ attachmentName }}</span>
                    <button @click="clearAttachment" class="text-slate-400 hover:text-red-500 transition">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                <!-- Admin input area -->
                <div class="border-t border-slate-200 bg-slate-900 px-4 py-3">
                    <div class="flex items-center gap-2 mb-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-purple-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" />
                        </svg>
                        <span class="text-[10px] font-bold uppercase tracking-wider text-purple-400">Intervention Admin</span>
                    </div>
                    <form @submit.prevent="sendMessage" class="flex items-end gap-2">
                        <input type="file" ref="fileInput" class="hidden" accept="image/*,.pdf" @change="onFileSelected" />
                        <button type="button" @click="pickFile" class="shrink-0 rounded-full p-2 text-slate-400 transition hover:bg-slate-800 hover:text-slate-200">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M18.375 12.739l-7.693 7.693a4.5 4.5 0 01-6.364-6.364l10.94-10.94A3 3 0 1119.5 7.372L8.552 18.32m.009-.01l-.01.01m5.699-9.941l-7.81 7.81a1.5 1.5 0 002.112 2.13" /></svg>
                        </button>
                        <textarea v-model="chatForm.message" rows="1"
                            class="flex-1 resize-none rounded-xl border border-slate-700 bg-slate-800 px-4 py-2.5 text-sm text-white shadow-sm transition placeholder:text-slate-500 focus:border-purple-500 focus:ring-purple-500"
                            placeholder="Message d'intervention admin..." @keydown.enter.exact.prevent="sendMessage" />
                        <button type="submit" :disabled="chatForm.processing"
                            class="shrink-0 rounded-full bg-purple-600 p-2.5 text-white shadow-sm transition hover:bg-purple-700 disabled:opacity-50">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 12L3.269 3.126A59.768 59.768 0 0121.485 12 59.77 59.77 0 013.27 20.876L5.999 12zm0 0h7.5" /></svg>
                        </button>
                    </form>
                    <p v-if="chatForm.errors.message" class="mt-1 text-xs text-red-400">{{ chatForm.errors.message }}</p>
                    <p v-if="chatForm.errors.attachment" class="mt-1 text-xs text-red-400">{{ chatForm.errors.attachment }}</p>
                </div>
            </section>
        </div>
    </div>
</template>
