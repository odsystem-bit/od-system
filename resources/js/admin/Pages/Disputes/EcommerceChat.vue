<script setup>
import Layout from '../Layout.vue';
import ConfirmModal from '../../../Components/ConfirmModal.vue';
import { Link, useForm, router } from '@inertiajs/vue3';
import { ref, nextTick, onMounted, onUnmounted } from 'vue';
import { useConfirm } from '../../../Composables/useConfirm.js';

const { visible: confirmVisible, title: confirmTitle, message: confirmMessage, variant: confirmVariant, confirmLabel, cancelLabel, ask, onConfirm, onCancel } = useConfirm();

defineOptions({ layout: Layout });

const props = defineProps({
    order:    { type: Object, required: true },
    messages: { type: Array, default: () => [] },
});

const isActive = props.order.status === 'disputed';
const localMessages = ref([...props.messages]);
const chatContainer = ref(null);

function scrollToBottom() {
    nextTick(() => {
        if (chatContainer.value) {
            chatContainer.value.scrollTop = chatContainer.value.scrollHeight;
        }
    });
}

onMounted(() => {
    scrollToBottom();

    if (isActive && window.Echo) {
        window.Echo.private(`dispute.${props.order.id}`)
            .listen('NewDisputeMessage', (e) => {
                localMessages.value.push({
                    id:          Date.now(),
                    sender_type: e.sender_type,
                    sender_name: e.sender_name,
                    message:     e.message,
                    created_at:  e.created_at,
                });
                scrollToBottom();
            });
    }
});

onUnmounted(() => {
    if (window.Echo) {
        window.Echo.leave(`dispute.${props.order.id}`);
    }
});

const form = useForm({ message: '' });

function send() {
    if (!form.message.trim()) return;
    form.post(route('admin.disputes.ecommerce-chat.store', props.order.id), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset('message');
            scrollToBottom();
        },
    });
}

function formatCurrency(amount) {
    const v = parseFloat(amount);
    if (!v || v < 0) return '0 FCFA';
    return new Intl.NumberFormat('fr-FR', { style: 'decimal', minimumFractionDigits: 0 }).format(v) + ' FCFA';
}

function formatTime(dateStr) {
    if (!dateStr) return '';
    const d = new Date(dateStr);
    return d.toLocaleDateString('fr-FR', { day: '2-digit', month: 'short' }) + ' ' + d.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' });
}

async function refundClient() {
    if (!await ask({ title: 'Rembourser le client', message: 'Rembourser le client et clore le litige ? Les fonds seront retires des soldes en attente.', variant: 'danger', confirmLabel: 'Rembourser' })) return;
    router.patch(route('admin.disputes.refund', props.order.id), {}, { preserveScroll: true });
}

async function favorVendor() {
    if (!await ask({ title: 'Donner raison au vendeur', message: 'Donner raison au vendeur et clore le litige ? Les fonds seront liberes vers les soldes disponibles.', variant: 'info', confirmLabel: 'Confirmer' })) return;
    router.patch(route('admin.disputes.favor-vendor', props.order.id), {}, { preserveScroll: true });
}
</script>

<template>
    <div class="space-y-6">
        <!-- Back + Header -->
        <div class="flex items-center gap-4">
            <Link :href="route('admin.disputes.index')" class="flex items-center gap-1.5 text-sm font-medium text-slate-500 transition hover:text-slate-700">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" /></svg>
                Retour
            </Link>
            <div class="flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-full bg-red-100">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-red-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                    </svg>
                </div>
                <div>
                    <h1 class="text-xl font-bold text-slate-900">Tribunal -- {{ order.reference }}</h1>
                    <p class="text-sm text-slate-500">Chat de mediation e-commerce</p>
                </div>
            </div>
        </div>

        <div class="flex flex-col lg:flex-row gap-6">
            <!-- Sidebar -->
            <aside class="w-full lg:w-[300px] shrink-0 space-y-4">
                <!-- Commande -->
                <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm space-y-3">
                    <h2 class="text-sm font-semibold text-slate-900">Resume</h2>
                    <div class="space-y-2 text-sm">
                        <div><p class="text-xs text-slate-500">Produit</p><p class="font-semibold text-slate-900">{{ order.product?.name ?? '-' }}</p></div>
                        <div><p class="text-xs text-slate-500">Montant</p><p class="font-semibold text-slate-900">{{ formatCurrency(order.amount_paid) }}</p></div>
                    </div>
                </div>

                <!-- Parties -->
                <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm space-y-3">
                    <h2 class="text-sm font-semibold text-slate-900">Parties</h2>
                    <div class="space-y-2 text-sm">
                        <div class="flex items-start gap-2">
                            <span class="mt-0.5 h-2 w-2 rounded-full bg-slate-400 shrink-0"></span>
                            <div><p class="text-xs text-slate-500">Client</p><p class="font-medium text-slate-800">{{ order.customer_name }}</p></div>
                        </div>
                        <div class="flex items-start gap-2">
                            <span class="mt-0.5 h-2 w-2 rounded-full bg-teal-500 shrink-0"></span>
                            <div><p class="text-xs text-slate-500">Vendeur</p><p class="font-medium text-slate-800">{{ order.vendor?.business_name || order.vendor?.name || '-' }}</p><p class="text-[10px] text-slate-400">{{ order.vendor?.email }}</p></div>
                        </div>
                        <div v-if="order.influencer" class="flex items-start gap-2">
                            <span class="mt-0.5 h-2 w-2 rounded-full bg-cyan-500 shrink-0"></span>
                            <div><p class="text-xs text-slate-500">Createur de Contenu</p><p class="font-medium text-slate-800">{{ order.influencer?.name }}</p></div>
                        </div>
                    </div>
                </div>

                <!-- Motif -->
                <div v-if="order.dispute_reason" class="rounded-2xl border border-red-200 bg-red-50 p-5 shadow-sm">
                    <p class="text-xs font-semibold text-red-700">Motif du client</p>
                    <p class="mt-1 text-sm text-red-800">{{ order.dispute_reason }}</p>
                </div>

                <!-- Defense -->
                <div v-if="order.vendor_defense_message" class="rounded-2xl border border-teal-200 bg-teal-50 p-5 shadow-sm">
                    <p class="text-xs font-semibold text-teal-700">Defense du vendeur</p>
                    <p class="mt-1 text-sm text-teal-800">{{ order.vendor_defense_message }}</p>
                </div>

                <!-- Verdict buttons -->
                <div v-if="isActive" class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm space-y-2">
                    <h2 class="text-sm font-semibold text-slate-900">Verdict</h2>
                    <button @click="refundClient" class="flex w-full items-center justify-center gap-2 rounded-lg border border-red-300 bg-red-50 px-4 py-2.5 text-sm font-medium text-red-700 transition hover:bg-red-100">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 15L3 9m0 0l6-6M3 9h12a6 6 0 010 12h-3" /></svg>
                        Rembourser le client
                    </button>
                    <button @click="favorVendor" class="flex w-full items-center justify-center gap-2 rounded-lg border border-teal-300 bg-teal-50 px-4 py-2.5 text-sm font-medium text-teal-700 transition hover:bg-teal-100">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        Donner raison au vendeur
                    </button>
                </div>
                <div v-else class="rounded-2xl border border-violet-200 bg-violet-50 p-5 shadow-sm text-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-violet-500 mx-auto mb-1" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" />
                    </svg>
                    <p class="text-sm font-semibold text-violet-700">Litige resolu</p>
                </div>
            </aside>

            <!-- Chat area -->
            <div class="flex-1 rounded-2xl border border-slate-200/80 bg-white shadow-sm overflow-hidden flex flex-col" style="min-height: 36rem;">
                <!-- Messages -->
                <div ref="chatContainer" class="flex-1 overflow-y-auto px-5 py-4 space-y-3">
                    <div v-if="localMessages.length === 0" class="flex flex-col items-center justify-center h-full text-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-slate-300 mb-3" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 8.511c.884.284 1.5 1.128 1.5 2.097v4.286c0 1.136-.847 2.1-1.98 2.193-.34.027-.68.052-1.02.072v3.091l-3-3c-1.354 0-2.694-.055-4.02-.163a2.115 2.115 0 01-.825-.242m9.345-8.334a2.126 2.126 0 00-.476-.095 48.64 48.64 0 00-8.048 0c-1.131.094-1.976 1.057-1.976 2.192v4.286c0 .837.46 1.58 1.155 1.951m9.345-8.334V6.637c0-1.621-1.152-3.026-2.76-3.235A48.455 48.455 0 0011.25 3c-2.115 0-4.198.137-6.24.402-1.608.209-2.76 1.614-2.76 3.235v6.226c0 1.621 1.152 3.026 2.76 3.235.577.075 1.157.14 1.74.194V21l4.155-4.155" />
                        </svg>
                        <p class="text-sm text-slate-400">Aucun message dans ce litige.</p>
                    </div>

                    <template v-for="msg in localMessages" :key="msg.id">
                        <!-- Customer bubble (left, gray) -->
                        <div v-if="msg.sender_type === 'customer'" class="flex justify-start">
                            <div class="max-w-[75%]">
                                <div class="rounded-2xl rounded-bl-md bg-slate-100 px-4 py-2.5">
                                    <p class="text-sm text-slate-800 whitespace-pre-line">{{ msg.message }}</p>
                                </div>
                                <p class="mt-1 text-[10px] text-slate-400">{{ msg.sender_name }} (Client) -- {{ formatTime(msg.created_at) }}</p>
                            </div>
                        </div>

                        <!-- Vendor bubble (left, teal) -->
                        <div v-else-if="msg.sender_type === 'vendor'" class="flex justify-start">
                            <div class="max-w-[75%]">
                                <div class="rounded-2xl rounded-bl-md bg-teal-50 border border-teal-100 px-4 py-2.5">
                                    <p class="text-sm text-teal-900 whitespace-pre-line">{{ msg.message }}</p>
                                </div>
                                <p class="mt-1 text-[10px] text-slate-400">{{ msg.sender_name }} (Vendeur) -- {{ formatTime(msg.created_at) }}</p>
                            </div>
                        </div>

                        <!-- Admin bubble (right, dark/purple "God Mode") -->
                        <div v-else-if="msg.sender_type === 'admin'" class="flex justify-end">
                            <div class="max-w-[80%]">
                                <div class="rounded-2xl rounded-br-md bg-slate-900 border-2 border-violet-500 px-4 py-2.5">
                                    <div class="flex items-center gap-1.5 mb-1">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-violet-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" />
                                        </svg>
                                        <span class="text-[10px] font-bold text-violet-400 uppercase tracking-wider">Administration MANTOTA</span>
                                    </div>
                                    <p class="text-sm text-white whitespace-pre-line">{{ msg.message }}</p>
                                </div>
                                <p class="mt-1 text-right text-[10px] text-slate-400">{{ formatTime(msg.created_at) }}</p>
                            </div>
                        </div>
                    </template>
                </div>

                <!-- Admin input (always active — admin can always intervene) -->
                <div class="border-t border-slate-100 px-4 py-3">
                    <div class="flex items-center gap-2 mb-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-violet-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" />
                        </svg>
                        <span class="text-[10px] font-bold text-violet-600 uppercase tracking-wider">Intervention Admin</span>
                    </div>
                    <form v-if="isActive" @submit.prevent="send" class="flex gap-2">
                        <input
                            v-model="form.message"
                            type="text"
                            placeholder="Ecrivez votre intervention..."
                            maxlength="2000"
                            class="flex-1 rounded-lg border border-violet-300 px-3 py-2 text-sm shadow-sm focus:border-violet-500 focus:ring-violet-500"
                        />
                        <button
                            type="submit"
                            :disabled="form.processing || !form.message.trim()"
                            class="inline-flex items-center gap-1.5 rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-slate-800 disabled:opacity-50 disabled:cursor-not-allowed border-2 border-violet-500"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 12L3.269 3.126A59.768 59.768 0 0121.485 12 59.77 59.77 0 013.27 20.876L5.999 12zm0 0h7.5" />
                            </svg>
                            Envoyer
                        </button>
                    </form>
                    <p v-else class="text-sm text-slate-500 text-center py-1">Litige resolu. Historique en lecture seule.</p>
                    <p v-if="form.errors.message" class="mt-1 text-xs text-red-600">{{ form.errors.message }}</p>
                </div>
            </div>
        </div>
    </div>

    <ConfirmModal :show="confirmVisible" :title="confirmTitle" :message="confirmMessage" :variant="confirmVariant" :confirm-label="confirmLabel" :cancel-label="cancelLabel" @confirmed="onConfirm" @cancelled="onCancel" />
</template>
