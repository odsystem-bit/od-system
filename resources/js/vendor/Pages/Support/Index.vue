<script setup>
import { Head, Link } from '@inertiajs/vue3';
import VendorLayout from '../../Layouts/VendorLayout.vue';

defineOptions({ layout: VendorLayout });

const props = defineProps({
    tickets: Object,
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

function formatDate(date) {
    return new Date(date).toLocaleDateString('fr-FR', {
        day: 'numeric',
        month: 'short',
        hour: '2-digit',
        minute: '2-digit',
    });
}
</script>

<template>
    <Head title="Support" />

    <div class="px-4 py-6 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-purple-50">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-purple-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.712 4.33a9.027 9.027 0 011.652 1.306c.51.51.944 1.064 1.306 1.652M16.712 4.33l-3.448 4.138m3.448-4.138a9.014 9.014 0 00-9.424 0M19.67 7.288l-4.138 3.448m4.138-3.448a9.014 9.014 0 010 9.424m-4.138-5.976a3.736 3.736 0 00-.88-1.388 3.737 3.737 0 00-1.388-.88m2.268 2.268a3.765 3.765 0 010 2.528m-2.268-4.796l-4.138-3.448m4.138 3.448a3.736 3.736 0 011.388-.88m-5.526-.568a9.014 9.014 0 00-9.424 0m9.424 0a3.737 3.737 0 00-1.388.88M4.33 7.288l4.138 3.448M4.33 7.288a9.014 9.014 0 000 9.424m4.138-5.976a3.737 3.737 0 00-.88 1.388m0 0a3.765 3.765 0 000 2.528m0-2.528l-4.138-3.448m4.138 5.976l-4.138 3.448m4.138-3.448a3.737 3.737 0 00.88 1.388m-.88-1.388a3.737 3.737 0 01.88 1.388m0 0a9.027 9.027 0 001.306 1.652c.51.51 1.064.944 1.652 1.306m-2.958-2.958l-4.138 3.448m2.958 2.958a9.014 9.014 0 009.424 0m-9.424 0a3.737 3.737 0 001.388.88m5.078-.88a3.737 3.737 0 01-1.388.88m0 0l3.448 4.138m-3.448-4.138a3.765 3.765 0 01-2.528 0m5.976 4.138a9.014 9.014 0 000-9.424m0 9.424l-3.448-4.138" /></svg>
                </div>
                <div>
                    <h1 class="text-xl font-bold text-slate-900">Support</h1>
                    <p class="text-sm text-slate-500">{{ tickets.total }} ticket(s)</p>
                </div>
            </div>
            <Link :href="route('vendor.support.create')" class="inline-flex items-center gap-2 rounded-xl bg-teal-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-teal-700">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                Nouveau ticket
            </Link>
        </div>

        <!-- Empty state -->
        <div v-if="!tickets.data.length" class="rounded-2xl border border-slate-200 bg-white p-12 text-center shadow-sm">
            <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-100">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 8.511c.884.284 1.5 1.128 1.5 2.097v4.286c0 1.136-.847 2.1-1.98 2.193-.34.027-.68.052-1.02.072v3.091l-3-3c-1.354 0-2.694-.055-4.02-.163a2.115 2.115 0 01-.825-.242m9.345-8.334a2.126 2.126 0 00-.476-.095 48.64 48.64 0 00-8.048 0c-1.131.094-1.976 1.057-1.976 2.192v4.286c0 .837.46 1.58 1.155 1.951m9.345-8.334V6.637c0-1.621-1.152-3.026-2.76-3.235A48.455 48.455 0 0011.25 3c-2.115 0-4.198.137-6.24.402-1.608.209-2.76 1.614-2.76 3.235v6.226c0 1.621 1.152 3.026 2.76 3.235.577.075 1.157.14 1.74.194V21l4.155-4.155" /></svg>
            </div>
            <p class="text-sm text-slate-500">Aucun ticket pour le moment.</p>
            <Link :href="route('vendor.support.create')" class="mt-4 inline-flex items-center gap-2 rounded-xl bg-teal-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-teal-700">
                Contacter le support
            </Link>
        </div>

        <!-- Tickets list -->
        <div v-else class="space-y-3">
            <Link
                v-for="ticket in tickets.data"
                :key="ticket.id"
                :href="route('vendor.support.show', ticket.id)"
                class="block overflow-hidden rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:border-slate-300 hover:shadow-md"
            >
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-2.5">
                            <h3 class="truncate text-sm font-semibold text-slate-900">{{ ticket.subject }}</h3>
                            <span :class="statusColors[ticket.status]" class="inline-flex shrink-0 items-center rounded-full px-2 py-0.5 text-xs font-medium ring-1 ring-inset">
                                {{ statusLabels[ticket.status] }}
                            </span>
                        </div>
                        <div class="mt-1.5 flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-slate-400">
                            <span class="font-mono">{{ ticket.reference_code }}</span>
                            <span>{{ categoryLabels[ticket.category] }}</span>
                            <span>{{ ticket.messages_count }} message(s)</span>
                            <span>{{ formatDate(ticket.created_at) }}</span>
                        </div>
                    </div>
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0 text-slate-300" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" /></svg>
                </div>
            </Link>

            <!-- Pagination -->
            <div v-if="tickets.last_page > 1" class="flex items-center justify-between pt-4">
                <p class="text-sm text-slate-500">Page {{ tickets.current_page }} / {{ tickets.last_page }}</p>
                <div class="flex gap-2">
                    <Link v-if="tickets.prev_page_url" :href="tickets.prev_page_url" class="rounded-lg border border-slate-200 px-3 py-1.5 text-sm text-slate-600 hover:bg-slate-50">Precedent</Link>
                    <Link v-if="tickets.next_page_url" :href="tickets.next_page_url" class="rounded-lg border border-slate-200 px-3 py-1.5 text-sm text-slate-600 hover:bg-slate-50">Suivant</Link>
                </div>
            </div>
        </div>
    </div>
</template>
