<script setup>
import { computed } from 'vue';
import { router } from '@inertiajs/vue3';
import Layout from '../Layout.vue';

defineOptions({ layout: Layout });

const props = defineProps({
    messages:      { type: Array, default: () => [] },
    currentSource: { type: String, default: 'all' },
    counts:        { type: Object, default: () => ({}) },
});

const filters = [
    { key: 'all',      label: 'Tous' },
    { key: 'disputes',  label: 'Litiges' },
    { key: 'studios',   label: 'Studios UGC' },
    { key: 'support',   label: 'Support' },
];

function switchSource(source) {
    router.get(route('admin.flagged-messages.index'), { source }, { preserveState: true });
}

function formatDate(iso) {
    return new Date(iso).toLocaleString('fr-FR', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' });
}
</script>

<template>
    <div class="space-y-6">
        <!-- Header -->
        <div>
            <h1 class="text-xl font-bold text-slate-900">Messages suspects</h1>
            <p class="mt-1 text-sm text-slate-500">Messages detectes et masques par le Robot Moderateur dans les conversations.</p>
        </div>

        <!-- Filters -->
        <div class="flex flex-wrap gap-2">
            <button
                v-for="f in filters"
                :key="f.key"
                @click="switchSource(f.key)"
                class="inline-flex items-center gap-1.5 rounded-full border px-4 py-1.5 text-xs font-semibold transition"
                :class="currentSource === f.key
                    ? 'border-purple-300 bg-purple-50 text-purple-700'
                    : 'border-slate-200 bg-white text-slate-600 hover:border-purple-200 hover:bg-purple-50/50'"
            >
                {{ f.label }}
                <span class="inline-flex items-center justify-center rounded-full px-1.5 py-0.5 text-[10px] font-bold"
                    :class="currentSource === f.key ? 'bg-purple-200 text-purple-800' : 'bg-slate-100 text-slate-500'">
                    {{ counts[f.key] ?? 0 }}
                </span>
            </button>
        </div>

        <!-- Empty state -->
        <div v-if="!messages.length" class="rounded-2xl border border-slate-200 bg-white p-12 text-center">
            <svg xmlns="http://www.w3.org/2000/svg" class="mx-auto h-12 w-12 text-slate-300" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" />
            </svg>
            <p class="mt-3 text-sm font-medium text-slate-600">Aucun message suspect detecte</p>
            <p class="mt-1 text-xs text-slate-400">Les messages seront affiches ici lorsqu'ils seront detectes par le Robot Moderateur.</p>
        </div>

        <!-- Table -->
        <div v-else class="rounded-2xl border border-slate-200/80 bg-white shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-100">
                    <thead class="bg-gradient-to-r from-slate-50 to-orange-50/30">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Source</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Reference</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Expediteur</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Message original</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Date</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        <tr v-for="msg in messages" :key="msg.source_key + '-' + msg.id" class="hover:bg-orange-50/30 transition">
                            <!-- Source -->
                            <td class="whitespace-nowrap px-6 py-4">
                                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium"
                                    :class="msg.source_key === 'disputes' ? 'bg-red-50 text-red-700 ring-1 ring-red-200' : msg.source_key === 'studios' ? 'bg-purple-50 text-purple-700 ring-1 ring-purple-200' : 'bg-blue-50 text-blue-700 ring-1 ring-blue-200'">
                                    {{ msg.source }}
                                </span>
                            </td>
                            <!-- Reference -->
                            <td class="whitespace-nowrap px-4 py-4 text-sm text-slate-700 font-medium">
                                {{ msg.reference }}
                            </td>
                            <!-- Sender -->
                            <td class="whitespace-nowrap px-4 py-4">
                                <div class="text-sm font-medium text-slate-900">{{ msg.sender_name }}</div>
                                <div class="text-xs text-slate-400">{{ msg.sender_type }}</div>
                            </td>
                            <!-- Original Message -->
                            <td class="px-4 py-4 max-w-md">
                                <p class="text-sm text-orange-800 bg-orange-50 rounded-lg px-3 py-2 border border-orange-200 break-words line-clamp-3">
                                    {{ msg.original_message ?? msg.masked_message }}
                                </p>
                            </td>
                            <!-- Date -->
                            <td class="whitespace-nowrap px-4 py-4 text-xs text-slate-500">
                                {{ formatDate(msg.created_at) }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>
