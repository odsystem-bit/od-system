<script setup>
import VendorLayout from '../../Layouts/VendorLayout.vue';
import { Link, useForm } from '@inertiajs/vue3';
import { ref, nextTick, onMounted, onUnmounted } from 'vue';

defineOptions({ layout: VendorLayout });

const props = defineProps({
    order:    { type: Object, required: true },
    messages: { type: Array, default: () => [] },
    isActive: { type: Boolean, required: true },
});

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

    if (props.isActive && window.Echo) {
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
    form.post(route('vendor.orders.dispute-chat.store', props.order.id), {
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
</script>

<template>
    <div class="space-y-6">
        <!-- Back + Header -->
        <div class="flex items-center gap-4">
            <Link :href="route('vendor.orders.index')" class="flex items-center gap-1.5 text-sm font-medium text-slate-500 transition hover:text-slate-700">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" /></svg>
                Retour
            </Link>
            <div>
                <h1 class="text-xl font-bold text-slate-900">Chat de mediation</h1>
                <p class="text-sm text-slate-500">{{ order.reference }} -- {{ order.customer_name }}</p>
            </div>
        </div>

        <div class="flex flex-col lg:flex-row gap-6">
            <!-- Sidebar : Resume commande -->
            <aside class="w-full lg:w-[280px] shrink-0 space-y-4">
                <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm space-y-3">
                    <h2 class="text-sm font-semibold text-slate-900">Resume de la commande</h2>
                    <div class="space-y-2 text-sm">
                        <div><p class="text-xs text-slate-500">Produit</p><p class="font-semibold text-slate-900">{{ order.product?.name ?? '-' }}</p></div>
                        <div><p class="text-xs text-slate-500">Montant</p><p class="font-semibold text-slate-900">{{ formatCurrency(order.amount_paid) }}</p></div>
                        <div><p class="text-xs text-slate-500">Client</p><p class="font-semibold text-slate-900">{{ order.customer_name }}</p></div>
                        <div><p class="text-xs text-slate-500">Statut</p>
                            <span :class="order.status === 'disputed' ? 'bg-red-50 text-red-700' : 'bg-slate-50 text-slate-700'" class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium ring-1 ring-inset ring-current/10">
                                {{ order.status === 'disputed' ? 'Litige en cours' : order.status === 'disputed_resolved' ? 'Litige resolu' : order.status }}
                            </span>
                        </div>
                    </div>
                    <div v-if="order.dispute_reason" class="rounded-lg border border-red-200 bg-red-50 p-3">
                        <p class="text-xs font-semibold text-red-700">Motif du client</p>
                        <p class="mt-0.5 text-xs text-red-800">{{ order.dispute_reason }}</p>
                    </div>
                </div>
            </aside>

            <!-- Chat area -->
            <div class="flex-1 rounded-2xl border border-slate-200/80 bg-white shadow-sm overflow-hidden flex flex-col" style="min-height: 32rem;">
                <!-- Messages -->
                <div ref="chatContainer" class="flex-1 overflow-y-auto px-5 py-4 space-y-3">
                    <div v-if="localMessages.length === 0" class="flex flex-col items-center justify-center h-full text-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-slate-300 mb-3" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 8.511c.884.284 1.5 1.128 1.5 2.097v4.286c0 1.136-.847 2.1-1.98 2.193-.34.027-.68.052-1.02.072v3.091l-3-3c-1.354 0-2.694-.055-4.02-.163a2.115 2.115 0 01-.825-.242m9.345-8.334a2.126 2.126 0 00-.476-.095 48.64 48.64 0 00-8.048 0c-1.131.094-1.976 1.057-1.976 2.192v4.286c0 .837.46 1.58 1.155 1.951m9.345-8.334V6.637c0-1.621-1.152-3.026-2.76-3.235A48.455 48.455 0 0011.25 3c-2.115 0-4.198.137-6.24.402-1.608.209-2.76 1.614-2.76 3.235v6.226c0 1.621 1.152 3.026 2.76 3.235.577.075 1.157.14 1.74.194V21l4.155-4.155" />
                        </svg>
                        <p class="text-sm text-slate-400">Aucun message pour le moment.</p>
                    </div>

                    <template v-for="msg in localMessages" :key="msg.id">
                        <!-- Vendor bubble (right, teal) -->
                        <div v-if="msg.sender_type === 'vendor'" class="flex justify-end">
                            <div class="max-w-[75%]">
                                <div class="rounded-2xl rounded-br-md bg-teal-50 border border-teal-100 px-4 py-2.5">
                                    <p class="text-sm text-teal-900 whitespace-pre-line">{{ msg.message }}</p>
                                </div>
                                <p class="mt-1 text-right text-[10px] text-slate-400">Vous -- {{ formatTime(msg.created_at) }}</p>
                            </div>
                        </div>

                        <!-- Customer bubble (left, gray) -->
                        <div v-else-if="msg.sender_type === 'customer'" class="flex justify-start">
                            <div class="max-w-[75%]">
                                <div class="rounded-2xl rounded-bl-md bg-slate-100 px-4 py-2.5">
                                    <p class="text-sm text-slate-800 whitespace-pre-line">{{ msg.message }}</p>
                                </div>
                                <p class="mt-1 text-[10px] text-slate-400">{{ msg.sender_name }} (Client) -- {{ formatTime(msg.created_at) }}</p>
                            </div>
                        </div>

                        <!-- Admin bubble (left, dark/purple "God Mode") -->
                        <div v-else-if="msg.sender_type === 'admin'" class="flex justify-start">
                            <div class="max-w-[80%]">
                                <div class="rounded-2xl rounded-bl-md bg-slate-900 border-2 border-violet-500 px-4 py-2.5">
                                    <div class="flex items-center gap-1.5 mb-1">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-violet-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" />
                                        </svg>
                                        <span class="text-[10px] font-bold text-violet-400 uppercase tracking-wider">Administration MANTOTA</span>
                                    </div>
                                    <p class="text-sm text-white whitespace-pre-line">{{ msg.message }}</p>
                                </div>
                                <p class="mt-1 text-[10px] text-slate-400">{{ formatTime(msg.created_at) }}</p>
                            </div>
                        </div>
                    </template>
                </div>

                <!-- Input area (active) or read-only banner -->
                <div v-if="isActive" class="border-t border-slate-100 px-4 py-3">
                    <form @submit.prevent="send" class="flex gap-2">
                        <input
                            v-model="form.message"
                            type="text"
                            placeholder="Repondez au client..."
                            maxlength="2000"
                            class="flex-1 rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-purple-500 focus:ring-purple-500"
                        />
                        <button
                            type="submit"
                            :disabled="form.processing || !form.message.trim()"
                            class="inline-flex items-center gap-1.5 rounded-full bg-gradient-to-r from-purple-500 to-violet-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:from-purple-600 hover:to-violet-700 disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 12L3.269 3.126A59.768 59.768 0 0121.485 12 59.77 59.77 0 013.27 20.876L5.999 12zm0 0h7.5" />
                            </svg>
                            Envoyer
                        </button>
                    </form>
                    <p v-if="form.errors.message" class="mt-1 text-xs text-red-600">{{ form.errors.message }}</p>
                </div>
                <div v-else class="border-t border-slate-100 bg-slate-50 px-5 py-4 text-center">
                    <div class="flex items-center justify-center gap-2 text-sm text-slate-500">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
                        </svg>
                        Ce litige est clos. La conversation est en lecture seule.
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
