<script setup>
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref, nextTick, onMounted, onUnmounted } from 'vue';

const props = defineProps({
    order:    { type: Object, required: true },
    messages: { type: Array, default: () => [] },
    isActive: { type: Boolean, required: true },
    token:    { type: String, required: true },
});

const chatContainer = ref(null);
let pollInterval = null;

function scrollToBottom() {
    nextTick(() => {
        if (chatContainer.value) {
            chatContainer.value.scrollTop = chatContainer.value.scrollHeight;
        }
    });
}

onMounted(() => {
    scrollToBottom();

    // Le client n'a pas de session auth => polling leger toutes les 10s
    if (props.isActive) {
        pollInterval = setInterval(() => {
            router.reload({ only: ['messages'], preserveScroll: true, onSuccess: scrollToBottom });
        }, 10000);
    }
});

onUnmounted(() => {
    if (pollInterval) clearInterval(pollInterval);
});

const form = useForm({ message: '', token: props.token });

function send() {
    if (!form.message.trim()) return;
    form.post(route('public.dispute.chat.store', props.order.id), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset('message');
            scrollToBottom();
        },
    });
}

function formatTime(dateStr) {
    if (!dateStr) return '';
    const d = new Date(dateStr);
    return d.toLocaleDateString('fr-FR', { day: '2-digit', month: 'short' }) + ' ' + d.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' });
}
</script>

<template>
    <Head :title="`Litige ${order.reference}`" />

    <div class="flex min-h-screen items-center justify-center bg-gradient-to-br from-slate-50 to-slate-100 px-4 py-8">
        <div class="w-full max-w-2xl">

            <!-- Header -->
            <div class="mb-4 flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-full bg-red-100">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-red-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                    </svg>
                </div>
                <div>
                    <h1 class="text-lg font-bold text-slate-900">Litige -- {{ order.reference }}</h1>
                    <p class="text-sm text-slate-500">Mediation entre vous, le vendeur et l'administration</p>
                </div>
            </div>

            <!-- LOCKED STATE -->
            <div v-if="!isActive" class="rounded-2xl border border-slate-200 bg-white p-12 shadow-sm text-center">
                <div class="mx-auto mb-6 flex h-20 w-20 items-center justify-center rounded-full bg-slate-100">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
                    </svg>
                </div>
                <h2 class="text-xl font-bold text-slate-900">Ce litige est clos</h2>
                <p class="mt-2 text-sm text-slate-500 max-w-sm mx-auto">
                    L'acces a cette conversation est definitivement ferme.
                    La decision de l'administration a ete rendue.
                </p>
            </div>

            <!-- ACTIVE CHAT -->
            <div v-else class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">

                <!-- Dispute reason banner -->
                <div class="border-b border-slate-100 bg-red-50 px-5 py-3">
                    <p class="text-xs font-semibold text-red-700 uppercase tracking-wide">Motif du signalement</p>
                    <p class="mt-0.5 text-sm text-red-800">{{ order.dispute_reason }}</p>
                </div>

                <!-- Messages area -->
                <div ref="chatContainer" class="h-[28rem] overflow-y-auto px-5 py-4 space-y-3">
                    <div v-if="messages.length === 0" class="flex flex-col items-center justify-center h-full text-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-slate-300 mb-3" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 8.511c.884.284 1.5 1.128 1.5 2.097v4.286c0 1.136-.847 2.1-1.98 2.193-.34.027-.68.052-1.02.072v3.091l-3-3c-1.354 0-2.694-.055-4.02-.163a2.115 2.115 0 01-.825-.242m9.345-8.334a2.126 2.126 0 00-.476-.095 48.64 48.64 0 00-8.048 0c-1.131.094-1.976 1.057-1.976 2.192v4.286c0 .837.46 1.58 1.155 1.951m9.345-8.334V6.637c0-1.621-1.152-3.026-2.76-3.235A48.455 48.455 0 0011.25 3c-2.115 0-4.198.137-6.24.402-1.608.209-2.76 1.614-2.76 3.235v6.226c0 1.621 1.152 3.026 2.76 3.235.577.075 1.157.14 1.74.194V21l4.155-4.155" />
                        </svg>
                        <p class="text-sm text-slate-400">Aucun message pour le moment.</p>
                    </div>

                    <template v-for="msg in messages" :key="msg.id">
                        <!-- Customer bubble (right, gray) -->
                        <div v-if="msg.sender_type === 'customer'" class="flex justify-end">
                            <div class="max-w-[75%]">
                                <div class="rounded-2xl rounded-br-md bg-slate-100 px-4 py-2.5">
                                    <p class="text-sm text-slate-800 whitespace-pre-line">{{ msg.message }}</p>
                                </div>
                                <p class="mt-1 text-right text-[10px] text-slate-400">{{ msg.sender_name }} -- {{ formatTime(msg.created_at) }}</p>
                            </div>
                        </div>

                        <!-- Vendor bubble (left, teal) -->
                        <div v-else-if="msg.sender_type === 'vendor'" class="flex justify-start">
                            <div class="max-w-[75%]">
                                <div class="rounded-2xl rounded-bl-md bg-teal-50 border border-teal-100 px-4 py-2.5">
                                    <p class="text-sm text-teal-900 whitespace-pre-line">{{ msg.message }}</p>
                                </div>
                                <p class="mt-1 text-[10px] text-slate-400">{{ msg.sender_name }} -- {{ formatTime(msg.created_at) }}</p>
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

                <!-- Input area -->
                <div class="border-t border-slate-100 px-4 py-3">
                    <form @submit.prevent="send" class="flex gap-2">
                        <input
                            v-model="form.message"
                            type="text"
                            placeholder="Ecrivez votre message..."
                            maxlength="2000"
                            class="flex-1 rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500"
                        />
                        <button
                            type="submit"
                            :disabled="form.processing || !form.message.trim()"
                            class="inline-flex items-center gap-1.5 rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-slate-800 disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 12L3.269 3.126A59.768 59.768 0 0121.485 12 59.77 59.77 0 013.27 20.876L5.999 12zm0 0h7.5" />
                            </svg>
                            Envoyer
                        </button>
                    </form>
                    <p v-if="form.errors.message" class="mt-1 text-xs text-red-600">{{ form.errors.message }}</p>
                </div>
            </div>

            <!-- Link back to tracking -->
            <div class="mt-4 text-center">
                <a :href="route('order.track', { order: order.id, token: token })" class="text-sm font-medium text-slate-500 hover:text-slate-700 transition">
                    Retour au suivi de commande
                </a>
            </div>
        </div>
    </div>
</template>
