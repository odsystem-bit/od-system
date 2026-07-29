<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import Layout from '../Layout.vue';

defineOptions({ layout: Layout });

const props = defineProps({
    tickets: Object,
    filters: Object,
});

const status = ref(props.filters?.status || '');
const category = ref(props.filters?.category || '');

function applyFilters() {
    router.get(route('admin.support.index'), {
        status: status.value || undefined,
        category: category.value || undefined,
    }, { preserveState: true, replace: true });
}

watch([status, category], applyFilters);

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
    <Head title="Support - Tickets" />

    <div class="px-4 py-6 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-teal-50">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-teal-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.712 4.33a9.027 9.027 0 011.652 1.306c.51.51.944 1.064 1.306 1.652M16.712 4.33l-3.448 4.138m3.448-4.138a9.014 9.014 0 00-9.424 0M19.67 7.288l-4.138 3.448m4.138-3.448a9.014 9.014 0 010 9.424m-4.138-5.976a3.736 3.736 0 00-.88-1.388 3.737 3.737 0 00-1.388-.88m2.268 2.268a3.765 3.765 0 010 2.528m-2.268-4.796l-4.138-3.448m4.138 3.448a3.736 3.736 0 011.388-.88m-5.526-.568a9.014 9.014 0 00-9.424 0m9.424 0a3.737 3.737 0 00-1.388.88M4.33 7.288l4.138 3.448M4.33 7.288a9.014 9.014 0 000 9.424m4.138-5.976a3.737 3.737 0 00-.88 1.388m0 0a3.765 3.765 0 000 2.528m0-2.528l-4.138-3.448m4.138 5.976l-4.138 3.448m4.138-3.448a3.737 3.737 0 00.88 1.388m-.88-1.388a3.737 3.737 0 01.88 1.388m0 0a9.027 9.027 0 001.306 1.652c.51.51 1.064.944 1.652 1.306m-2.958-2.958l-4.138 3.448m2.958 2.958a9.014 9.014 0 009.424 0m-9.424 0a3.737 3.737 0 001.388.88m5.078-.88a3.737 3.737 0 01-1.388.88m0 0l3.448 4.138m-3.448-4.138a3.765 3.765 0 01-2.528 0m5.976 4.138a9.014 9.014 0 000-9.424m0 9.424l-3.448-4.138" /></svg>
                </div>
                <div>
                    <h1 class="text-xl font-bold text-slate-900">Support</h1>
                    <p class="text-sm text-slate-500">{{ tickets.total }} ticket(s) au total</p>
                </div>
            </div>

            <!-- Filters -->
            <div class="flex items-center gap-3">
                <select v-model="status" class="rounded-xl border border-slate-200 px-3 py-2 text-sm text-slate-700 shadow-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500">
                    <option value="">Tous les statuts</option>
                    <option value="open">Ouvert</option>
                    <option value="pending">En cours</option>
                    <option value="resolved">Resolu</option>
                </select>
                <select v-model="category" class="rounded-xl border border-slate-200 px-3 py-2 text-sm text-slate-700 shadow-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500">
                    <option value="">Toutes categories</option>
                    <option value="question">Question</option>
                    <option value="bug">Bug</option>
                    <option value="paiement">Paiement</option>
                </select>
            </div>
        </div>

        <!-- Table -->
        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-100">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Reference</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Sujet</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Categorie</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Statut</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Auteur</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Messages</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Date</th>
                            <th class="px-6 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        <tr v-for="ticket in tickets.data" :key="ticket.id" class="transition hover:bg-slate-50/60">
                            <td class="whitespace-nowrap px-6 py-4 font-mono text-sm font-medium text-slate-900">{{ ticket.reference_code }}</td>
                            <td class="max-w-[200px] truncate px-6 py-4 text-sm text-slate-700">{{ ticket.subject }}</td>
                            <td class="whitespace-nowrap px-6 py-4 text-sm text-slate-500">{{ categoryLabels[ticket.category] }}</td>
                            <td class="whitespace-nowrap px-6 py-4">
                                <span :class="statusColors[ticket.status]" class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 ring-inset">
                                    {{ statusLabels[ticket.status] }}
                                </span>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-sm text-slate-500">{{ ticket.user?.name || ticket.guest_name }}</td>
                            <td class="whitespace-nowrap px-6 py-4 text-sm text-slate-500">{{ ticket.messages_count }}</td>
                            <td class="whitespace-nowrap px-6 py-4 text-sm text-slate-400">{{ formatDate(ticket.created_at) }}</td>
                            <td class="whitespace-nowrap px-6 py-4 text-right">
                                <Link :href="route('admin.support.show', ticket.id)" class="text-sm font-medium text-teal-600 hover:text-teal-700">
                                    Voir
                                </Link>
                            </td>
                        </tr>
                        <tr v-if="!tickets.data.length">
                            <td colspan="8" class="px-6 py-12 text-center text-sm text-slate-400">Aucun ticket pour le moment.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div v-if="tickets.last_page > 1" class="flex items-center justify-between border-t border-slate-100 px-6 py-3">
                <p class="text-sm text-slate-500">Page {{ tickets.current_page }} sur {{ tickets.last_page }}</p>
                <div class="flex gap-2">
                    <Link v-if="tickets.prev_page_url" :href="tickets.prev_page_url" class="rounded-lg border border-slate-200 px-3 py-1.5 text-sm text-slate-600 hover:bg-slate-50">Precedent</Link>
                    <Link v-if="tickets.next_page_url" :href="tickets.next_page_url" class="rounded-lg border border-slate-200 px-3 py-1.5 text-sm text-slate-600 hover:bg-slate-50">Suivant</Link>
                </div>
            </div>
        </div>
    </div>
</template>
