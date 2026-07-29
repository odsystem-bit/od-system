<script setup>
import { router } from '@inertiajs/vue3';
import { Link } from '@inertiajs/vue3';
import Layout from '../Layout.vue';
import ConfirmModal from '../../../Components/ConfirmModal.vue';
import { useConfirm } from '../../../Composables/useConfirm.js';

defineOptions({ layout: Layout });

const { visible: confirmVisible, title: confirmTitle, message: confirmMessage, variant: confirmVariant, confirmLabel, cancelLabel, ask, onConfirm, onCancel } = useConfirm();

const props = defineProps({
    disputes: Object,
    serviceDisputes: Object,
});

function formatCurrency(value) {
    return new Intl.NumberFormat('fr-FR', { style: 'decimal', minimumFractionDigits: 0 }).format(value) + ' FCFA';
}

function formatDate(d) {
    if (!d) return '\u2014';
    return new Date(d).toLocaleDateString('fr-FR', { day: '2-digit', month: 'short', year: 'numeric' });
}

async function refundClient(orderId) {
    if (!await ask({ title: 'Rembourser le client', message: 'Rembourser le client et annuler la commande ? Les fonds seront retires des soldes en attente.', variant: 'danger', confirmLabel: 'Rembourser' })) return;
    router.patch(route('admin.disputes.refund', orderId), {}, { preserveScroll: true });
}

async function favorVendor(orderId) {
    if (!await ask({ title: 'Donner raison au vendeur', message: 'Donner raison au vendeur ? Les fonds seront liberes vers les soldes disponibles.', variant: 'info', confirmLabel: 'Confirmer' })) return;
    router.patch(route('admin.disputes.favor-vendor', orderId), {}, { preserveScroll: true });
}

async function refundVendorService(serviceOrderId) {
    if (!await ask({ title: 'Rembourser le vendeur', message: 'Rembourser le vendeur ? Le montant escrow sera restitue dans son solde.', variant: 'danger', confirmLabel: 'Rembourser' })) return;
    router.patch(route('admin.disputes.service.refund-vendor', serviceOrderId), {}, { preserveScroll: true });
}

async function favorInfluencerService(serviceOrderId) {
    if (!await ask({ title: 'Donner raison au createur de contenu', message: 'Donner raison au createur de contenu ? Il sera paye (moins la commission MANTOTA).', variant: 'info', confirmLabel: 'Confirmer' })) return;
    router.patch(route('admin.disputes.service.favor-influencer', serviceOrderId), {}, { preserveScroll: true });
}
</script>

<template>
    <div class="space-y-6">

        <!-- Header -->
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">Tribunal -- Litiges</h1>
            <p class="mt-1 text-sm text-slate-500">Commandes en litige. Decidez du sort des fonds bloques en escrow.</p>
        </div>

        <!-- Table -->
        <div class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-sm">

            <div v-if="disputes.data.length === 0" class="px-6 py-16 text-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="mx-auto h-10 w-10 text-slate-300" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <p class="mt-3 text-sm font-medium text-slate-600">Aucun litige en cours</p>
                <p class="mt-1 text-xs text-slate-400">Toutes les commandes contestees ont ete traitees.</p>
            </div>

            <div v-else class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-slate-100 bg-slate-50/50 text-left">
                            <th class="px-6 py-3 text-xs font-semibold uppercase tracking-wider text-slate-500">Reference</th>
                            <th class="px-6 py-3 text-xs font-semibold uppercase tracking-wider text-slate-500">Vendeur</th>
                            <th class="px-6 py-3 text-xs font-semibold uppercase tracking-wider text-slate-500">Client</th>
                            <th class="px-6 py-3 text-xs font-semibold uppercase tracking-wider text-slate-500">Createur de Contenu</th>
                            <th class="px-6 py-3 text-xs font-semibold uppercase tracking-wider text-slate-500">Montant total</th>
                            <th class="px-6 py-3 text-xs font-semibold uppercase tracking-wider text-slate-500">Gains Vendeur</th>
                            <th class="px-6 py-3 text-xs font-semibold uppercase tracking-wider text-slate-500">Commission</th>
                            <th class="px-6 py-3 text-xs font-semibold uppercase tracking-wider text-slate-500">Date</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-500">Verdict</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="order in disputes.data" :key="order.id" class="transition hover:bg-slate-50/60">
                            <td class="px-6 py-3.5">
                                <span class="font-mono text-xs font-semibold text-slate-900">{{ order.reference }}</span>
                            </td>
                            <td class="px-6 py-3.5">
                                <p class="font-medium text-slate-900">{{ order.vendor?.name ?? '\u2014' }}</p>
                                <p class="text-xs text-slate-400">{{ order.vendor?.email ?? '' }}</p>
                            </td>
                            <td class="px-6 py-3.5">
                                <p class="font-medium text-slate-900">{{ order.customer_name ?? '\u2014' }}</p>
                                <p class="text-xs text-slate-400">{{ order.customer_phone ?? '' }}</p>
                            </td>
                            <td class="px-6 py-3.5">
                                <template v-if="order.influencer">
                                    <p class="font-medium text-slate-900">{{ order.influencer.name }}</p>
                                    <p class="text-xs text-slate-400">{{ order.influencer.email }}</p>
                                </template>
                                <span v-else class="text-xs text-slate-400">\u2014</span>
                            </td>
                            <td class="px-6 py-3.5 font-medium text-slate-900">{{ formatCurrency(order.amount_paid) }}</td>
                            <td class="px-6 py-3.5 text-teal-700 font-medium">{{ formatCurrency(order.vendor_earnings) }}</td>
                            <td class="px-6 py-3.5 text-purple-700 font-medium">{{ formatCurrency(order.commission_amount) }}</td>
                            <td class="px-6 py-3.5 text-slate-500">{{ formatDate(order.updated_at) }}</td>
                            <td class="px-6 py-3.5 text-right">
                                <div class="inline-flex gap-2">
                                    <!-- Voir Dossier -->
                                    <Link
                                        :href="route('admin.disputes.show', order.id)"
                                        class="inline-flex items-center gap-1 rounded-lg bg-slate-100 px-3 py-1.5 text-xs font-semibold text-slate-700 transition hover:bg-slate-200"
                                        title="Voir le dossier complet"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m5.231 13.481L15 17.25m-4.5-15H5.625c-.621 0-1.125.504-1.125 1.125v16.5c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9zm3.75 11.625a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" /></svg>
                                        Voir Dossier
                                    </Link>
                                    <!-- Voir Chat -->
                                    <Link
                                        :href="route('admin.disputes.ecommerce-chat', order.id)"
                                        class="inline-flex items-center gap-1 rounded-lg bg-violet-50 px-3 py-1.5 text-xs font-semibold text-violet-700 transition hover:bg-violet-100"
                                        title="Ouvrir le chat de mediation"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 8.511c.884.284 1.5 1.128 1.5 2.097v4.286c0 1.136-.847 2.1-1.98 2.193-.34.027-.68.052-1.02.072v3.091l-3-3c-1.354 0-2.694-.055-4.02-.163a2.115 2.115 0 01-.825-.242m9.345-8.334a2.126 2.126 0 00-.476-.095 48.64 48.64 0 00-8.048 0c-1.131.094-1.976 1.057-1.976 2.192v4.286c0 1.136.845 2.1 1.976 2.193 1.07.089 2.15.137 3.224.137l3 3v-3.091c1.354-.089 2.694-.248 4.02-.479" /></svg>
                                        Voir Chat
                                    </Link>
                                    <!-- Rembourser le client -->
                                    <button
                                        @click="refundClient(order.id)"
                                        class="inline-flex items-center gap-1 rounded-lg bg-red-50 px-3 py-1.5 text-xs font-semibold text-red-700 transition hover:bg-red-100"
                                        title="Rembourser le client"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 15L3 9m0 0l6-6M3 9h12a6 6 0 010 12h-3" /></svg>
                                        Rembourser
                                    </button>
                                    <!-- Donner raison au vendeur -->
                                    <button
                                        @click="favorVendor(order.id)"
                                        class="inline-flex items-center gap-1 rounded-lg bg-teal-50 px-3 py-1.5 text-xs font-semibold text-teal-700 transition hover:bg-teal-100"
                                        title="Donner raison au vendeur"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                                        Valider vendeur
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div v-if="disputes.last_page > 1" class="flex items-center justify-between border-t border-slate-100 px-6 py-3">
                <p class="text-xs text-slate-500">{{ disputes.from }}-{{ disputes.to }} sur {{ disputes.total }}</p>
                <div class="flex gap-1">
                    <Link
                        v-for="link in disputes.links"
                        :key="link.label"
                        :href="link.url ?? '#'"
                        class="rounded-lg px-3 py-1.5 text-xs font-medium transition"
                        :class="link.active
                            ? 'bg-teal-600 text-white'
                            : link.url
                                ? 'text-slate-600 hover:bg-slate-100'
                                : 'pointer-events-none text-slate-300'"
                        v-html="link.label"
                    />
                </div>
            </div>
        </div>

        <!-- ─────────────────────────────────────
             Litiges UGC / MANTOTA Studios
        ───────────────────────────────────── -->
        <div>
            <h2 class="text-xl font-bold tracking-tight text-slate-900">Litiges UGC / Studios</h2>
            <p class="mt-1 text-sm text-slate-500">Commandes de videos en litige entre vendeurs et createurs de contenu.</p>
        </div>

        <div class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-sm">

            <div v-if="serviceDisputes.data.length === 0" class="px-6 py-16 text-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="mx-auto h-10 w-10 text-slate-300" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <p class="mt-3 text-sm font-medium text-slate-600">Aucun litige UGC en cours</p>
            </div>

            <div v-else class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-slate-100 bg-slate-50/50 text-left">
                            <th class="px-6 py-3 text-xs font-semibold uppercase tracking-wider text-slate-500">#</th>
                            <th class="px-6 py-3 text-xs font-semibold uppercase tracking-wider text-slate-500">Service</th>
                            <th class="px-6 py-3 text-xs font-semibold uppercase tracking-wider text-slate-500">Vendeur</th>
                            <th class="px-6 py-3 text-xs font-semibold uppercase tracking-wider text-slate-500">Createur de Contenu</th>
                            <th class="px-6 py-3 text-xs font-semibold uppercase tracking-wider text-slate-500">Montant (Escrow)</th>
                            <th class="px-6 py-3 text-xs font-semibold uppercase tracking-wider text-slate-500">Retouches</th>
                            <th class="px-6 py-3 text-xs font-semibold uppercase tracking-wider text-slate-500">Date</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-500">Verdict</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="so in serviceDisputes.data" :key="so.id" class="transition hover:bg-slate-50/60">
                            <td class="px-6 py-3.5">
                                <div class="flex items-center gap-1.5">
                                    <span class="font-mono text-xs font-semibold text-slate-900">#{{ so.id }}</span>
                                    <svg v-if="so.has_flagged_messages" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-red-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" title="Messages suspects detectes">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 3v1.5M3 21v-6m0 0l2.77-.693a9 9 0 016.208.682l.108.054a9 9 0 006.086.71l3.114-.732a48.524 48.524 0 01-.005-10.499l-3.11.732a9 9 0 01-6.085-.711l-.108-.054a9 9 0 00-6.208-.682L3 4.5M3 15V4.5" />
                                    </svg>
                                </div>
                            </td>
                            <td class="px-6 py-3.5">
                                <p class="font-medium text-slate-900">{{ so.service?.title ?? '-' }}</p>
                                <p v-if="so.service?.type" class="text-xs text-slate-400">{{ so.service.type === 'ugc_humain' ? 'UGC Humain' : so.service.type === 'video_pub_ia' ? 'Video Pub IA' : so.service.type }}</p>
                            </td>
                            <td class="px-6 py-3.5">
                                <p class="font-medium text-slate-900">{{ so.vendor?.name ?? '-' }}</p>
                                <p class="text-xs text-slate-400">{{ so.vendor?.email ?? '' }}</p>
                            </td>
                            <td class="px-6 py-3.5">
                                <p class="font-medium text-slate-900">{{ so.influencer?.name ?? '-' }}</p>
                                <p class="text-xs text-slate-400">{{ so.influencer?.email ?? '' }}</p>
                            </td>
                            <td class="px-6 py-3.5 font-medium text-slate-900">{{ formatCurrency(so.amount) }}</td>
                            <td class="px-6 py-3.5 text-slate-500">{{ so.revisions_used }} / {{ so.revisions_allowed }}</td>
                            <td class="px-6 py-3.5 text-slate-500">{{ formatDate(so.updated_at) }}</td>
                            <td class="px-6 py-3.5 text-right">
                                <div class="inline-flex gap-2">
                                    <Link
                                        :href="route('admin.disputes.chat.show', so.id)"
                                        class="inline-flex items-center gap-1 rounded-lg px-3 py-1.5 text-xs font-semibold transition"
                                        :class="so.has_flagged_messages ? 'bg-red-50 text-red-700 hover:bg-red-100' : 'bg-slate-100 text-slate-700 hover:bg-slate-200'"
                                        title="Voir la conversation"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12.76c0 1.6 1.123 2.994 2.707 3.227 1.068.157 2.148.279 3.238.364.466.037.893.281 1.153.671L12 21l2.652-3.978c.26-.39.687-.634 1.153-.671 1.09-.085 2.17-.207 3.238-.364 1.584-.233 2.707-1.626 2.707-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0012 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018z" /></svg>
                                        Voir Chat
                                    </Link>
                                    <button
                                        @click="refundVendorService(so.id)"
                                        class="inline-flex items-center gap-1 rounded-lg bg-red-50 px-3 py-1.5 text-xs font-semibold text-red-700 transition hover:bg-red-100"
                                        title="Rembourser le vendeur"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 15L3 9m0 0l6-6M3 9h12a6 6 0 010 12h-3" /></svg>
                                        Rembourser vendeur
                                    </button>
                                    <button
                                        @click="favorInfluencerService(so.id)"
                                        class="inline-flex items-center gap-1 rounded-lg bg-teal-50 px-3 py-1.5 text-xs font-semibold text-teal-700 transition hover:bg-teal-100"
                                        title="Payer le createur de contenu"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                                        Payer createur de contenu
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-if="serviceDisputes.last_page > 1" class="flex items-center justify-between border-t border-slate-100 px-6 py-3">
                <p class="text-xs text-slate-500">{{ serviceDisputes.from }}-{{ serviceDisputes.to }} sur {{ serviceDisputes.total }}</p>
                <div class="flex gap-1">
                    <Link
                        v-for="link in serviceDisputes.links"
                        :key="link.label"
                        :href="link.url ?? '#'"
                        class="rounded-lg px-3 py-1.5 text-xs font-medium transition"
                        :class="link.active
                            ? 'bg-teal-600 text-white'
                            : link.url
                                ? 'text-slate-600 hover:bg-slate-100'
                                : 'pointer-events-none text-slate-300'"
                        v-html="link.label"
                    />
                </div>
            </div>
        </div>
    </div>

    <ConfirmModal :show="confirmVisible" :title="confirmTitle" :message="confirmMessage" :variant="confirmVariant" :confirm-label="confirmLabel" :cancel-label="cancelLabel" @confirmed="onConfirm" @cancelled="onCancel" />
</template>
