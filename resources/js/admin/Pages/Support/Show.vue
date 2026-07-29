<script setup>
import { Head, useForm, router } from '@inertiajs/vue3';
import Layout from '../Layout.vue';

defineOptions({ layout: Layout });

const props = defineProps({
    ticket: Object,
    messages: Array,
});

const form = useForm({
    message: '',
});

const statusColors = {
    open: 'bg-amber-50 text-amber-700 ring-amber-200',
    pending: 'bg-blue-50 text-blue-700 ring-blue-200',
    resolved: 'bg-emerald-50 text-emerald-700 ring-emerald-200',
};

const statusLabels = {
    open: 'Ouvert',
    pending: 'En cours',
    resolved: 'Resolu',
};

const categoryLabels = {
    question: 'Question',
    bug: 'Bug',
    paiement: 'Paiement',
};

function reply() {
    form.post(route('admin.support.reply', props.ticket.id), {
        preserveScroll: true,
        onSuccess: () => form.reset('message'),
    });
}

function resolve() {
    router.patch(route('admin.support.resolve', props.ticket.id), {}, {
        preserveScroll: true,
    });
}

function formatDate(date) {
    return new Date(date).toLocaleDateString('fr-FR', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
}
</script>

<template>
    <Head :title="'Ticket ' + ticket.reference_code" />

    <div class="px-4 py-6 sm:px-6 lg:px-8">
        <!-- Back link + header -->
        <div class="mb-6">
            <a :href="route('admin.support.index')" class="mb-3 inline-flex items-center gap-1 text-sm text-slate-500 hover:text-slate-700">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" /></svg>
                Retour aux tickets
            </a>
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <div class="flex items-center gap-3">
                        <h1 class="text-xl font-bold text-slate-900">{{ ticket.subject }}</h1>
                        <span :class="statusColors[ticket.status]" class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 ring-inset">
                            {{ statusLabels[ticket.status] }}
                        </span>
                    </div>
                    <div class="mt-1 flex flex-wrap items-center gap-x-4 gap-y-1 text-sm text-slate-500">
                        <span class="font-mono">{{ ticket.reference_code }}</span>
                        <span>{{ categoryLabels[ticket.category] }}</span>
                        <span>{{ ticket.user?.name || ticket.guest_name }} ({{ ticket.user?.email || ticket.guest_email }})</span>
                        <span>{{ formatDate(ticket.created_at) }}</span>
                    </div>
                </div>
                <button
                    v-if="ticket.status !== 'resolved'"
                    @click="resolve"
                    class="inline-flex items-center gap-2 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-2 text-sm font-medium text-emerald-700 transition hover:bg-emerald-100"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    Marquer resolu
                </button>
            </div>
        </div>

        <!-- Messages -->
        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-100 px-6 py-3">
                <h2 class="text-sm font-semibold text-slate-700">Conversation ({{ messages.length }} messages)</h2>
            </div>

            <div class="max-h-[32rem] space-y-4 overflow-y-auto p-6">
                <div v-for="msg in messages" :key="msg.id" :class="msg.is_admin ? 'flex justify-end' : 'flex justify-start'">
                    <div :class="msg.is_admin ? 'bg-teal-600 text-white' : 'bg-slate-100 text-slate-800'" class="max-w-[75%] rounded-2xl px-4 py-3">
                        <div class="mb-1 flex items-center gap-2">
                            <span class="text-xs font-semibold" :class="msg.is_admin ? 'text-teal-100' : 'text-slate-600'">
                                {{ msg.is_admin ? 'Admin' : (ticket.user?.name || ticket.guest_name) }}
                            </span>
                            <span class="text-xs" :class="msg.is_admin ? 'text-teal-200' : 'text-slate-400'">{{ formatDate(msg.created_at) }}</span>
                        </div>
                        <p class="whitespace-pre-wrap text-sm leading-relaxed">{{ msg.body }}</p>
                    </div>
                </div>
            </div>

            <!-- Reply form -->
            <div v-if="ticket.status !== 'resolved'" class="border-t border-slate-100 p-6">
                <form @submit.prevent="reply" class="flex gap-3">
                    <textarea
                        v-model="form.message"
                        rows="2"
                        placeholder="Repondre au ticket..."
                        class="flex-1 rounded-xl border border-slate-200 px-4 py-2.5 text-sm text-slate-900 shadow-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500"
                        required
                    ></textarea>
                    <button type="submit" :disabled="form.processing" class="self-end rounded-xl bg-teal-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-teal-700 disabled:opacity-50">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 12L3.269 3.126A59.768 59.768 0 0121.485 12 59.77 59.77 0 013.27 20.876L5.999 12zm0 0h7.5" /></svg>
                    </button>
                </form>
                <p v-if="form.errors.message" class="mt-2 text-xs text-red-600">{{ form.errors.message }}</p>
            </div>

            <div v-else class="border-t border-slate-100 bg-emerald-50 px-6 py-4 text-center">
                <p class="text-sm text-emerald-700">Ce ticket est resolu.</p>
            </div>
        </div>
    </div>
</template>
