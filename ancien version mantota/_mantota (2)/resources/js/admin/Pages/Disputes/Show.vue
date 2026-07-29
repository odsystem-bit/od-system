<script setup>
import { router } from '@inertiajs/vue3';
import { Link } from '@inertiajs/vue3';
import Layout from '../Layout.vue';
import ConfirmModal from '../../../Components/ConfirmModal.vue';
import { useConfirm } from '../../../Composables/useConfirm.js';

defineOptions({ layout: Layout });

const { visible: confirmVisible, title: confirmTitle, message: confirmMessage, variant: confirmVariant, confirmLabel, cancelLabel, ask, onConfirm, onCancel } = useConfirm();

const props = defineProps({
    order: Object,
    timeline: Array,
});

function formatCurrency(value) {
    return new Intl.NumberFormat('fr-FR', { style: 'decimal', minimumFractionDigits: 0 }).format(value) + ' FCFA';
}

function formatDate(d) {
    if (!d) return '\u2014';
    return new Date(d).toLocaleDateString('fr-FR', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' });
}

const colorClasses = {
    teal: { dot: 'bg-teal-500', ring: 'ring-teal-200', bg: 'bg-teal-50', text: 'text-teal-800' },
    slate: { dot: 'bg-slate-400', ring: 'ring-slate-200', bg: 'bg-slate-50', text: 'text-slate-700' },
    blue: { dot: 'bg-blue-500', ring: 'ring-blue-200', bg: 'bg-blue-50', text: 'text-blue-800' },
    red: { dot: 'bg-red-500', ring: 'ring-red-200', bg: 'bg-red-50', text: 'text-red-800' },
};

function getColor(c) {
    return colorClasses[c] ?? colorClasses.slate;
}

async function refundClient() {
    if (!await ask({ title: 'Rembourser le client', message: 'Rembourser le client et annuler la commande ? Les fonds seront retires des soldes en attente.', variant: 'danger', confirmLabel: 'Rembourser' })) return;
    router.patch(route('admin.disputes.refund', props.order.id), {}, { preserveScroll: true });
}

async function favorVendor() {
    if (!await ask({ title: 'Donner raison au vendeur', message: 'Donner raison au vendeur ? Les fonds seront liberes vers les soldes disponibles.', variant: 'info', confirmLabel: 'Confirmer' })) return;
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
            <div>
                <h1 class="text-2xl font-bold text-slate-900">Dossier Litige</h1>
                <p class="text-sm text-slate-500">Commande <span class="font-mono font-semibold">{{ order.reference }}</span></p>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <!-- LEFT: Order Summary -->
            <div class="lg:col-span-1 space-y-4">
                <!-- Order Card -->
                <div class="rounded-xl border border-slate-200 bg-white p-5 space-y-4">
                    <h2 class="text-sm font-semibold text-slate-900 flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 10-7.5 0v4.5m11.356-1.993l1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 01-1.12-1.243l1.264-12A1.125 1.125 0 015.513 7.5h12.974c.576 0 1.059.435 1.119 1.007zM8.625 10.5a.375.375 0 11-.75 0 .375.375 0 01.75 0zm7.5 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" /></svg>
                        Commande
                    </h2>

                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span class="text-slate-500">Reference</span>
                            <span class="font-mono font-semibold text-slate-900">{{ order.reference }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-500">Produit</span>
                            <span class="font-medium text-slate-900">{{ order.product?.name ?? '\u2014' }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-500">Montant paye</span>
                            <span class="font-semibold text-slate-900">{{ formatCurrency(order.amount_paid) }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-500">Statut</span>
                            <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-semibold bg-red-50 text-red-700">{{ order.status }}</span>
                        </div>
                    </div>
                </div>

                <!-- Acheteur -->
                <div class="rounded-xl border border-slate-200 bg-white p-5 space-y-3">
                    <h2 class="text-sm font-semibold text-slate-900 flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" /></svg>
                        Acheteur
                    </h2>
                    <div class="text-sm space-y-2">
                        <div class="flex justify-between">
                            <span class="text-slate-500">Nom</span>
                            <span class="font-medium text-slate-900">{{ order.customer_name ?? '\u2014' }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-500">Telephone</span>
                            <span class="font-medium text-slate-900">{{ order.customer_phone ?? '\u2014' }}</span>
                        </div>
                        <div v-if="order.city" class="flex justify-between">
                            <span class="text-slate-500">Ville</span>
                            <span class="font-medium text-slate-900">{{ order.city }}</span>
                        </div>
                        <div v-if="order.landmark_indication">
                            <span class="text-slate-500">Repere</span>
                            <p class="mt-1 font-medium text-slate-900">{{ order.landmark_indication }}</p>
                        </div>
                    </div>
                </div>

                <!-- Parties -->
                <div class="rounded-xl border border-slate-200 bg-white p-5 space-y-3">
                    <h2 class="text-sm font-semibold text-slate-900 flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" /></svg>
                        Parties
                    </h2>
                    <div class="text-sm space-y-2">
                        <div>
                            <p class="text-xs text-slate-500">Vendeur</p>
                            <p class="font-medium text-slate-900">{{ order.vendor?.name ?? '\u2014' }}</p>
                        </div>
                        <div v-if="order.influencer">
                            <p class="text-xs text-slate-500">Createur de Contenu</p>
                            <p class="font-medium text-slate-900">{{ order.influencer.name }}</p>
                        </div>
                    </div>
                </div>

                <!-- Logistique -->
                <div class="rounded-xl border border-slate-200 bg-white p-5 space-y-3">
                    <h2 class="text-sm font-semibold text-slate-900 flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 01-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 00-3.213-9.193 2.056 2.056 0 00-1.58-.86H14.25M16.5 18.75h-2.25m0-11.177v-.958c0-.568-.422-1.048-.987-1.106a48.554 48.554 0 00-10.026 0 1.106 1.106 0 00-.987 1.106v7.635m12-6.677v6.677m0 4.5v-4.5m0 0h-12" /></svg>
                        Logistique
                    </h2>
                    <template v-if="order.delivery_guy_name">
                        <div class="text-sm space-y-2">
                            <div class="flex justify-between">
                                <span class="text-slate-500">Societe</span>
                                <span class="font-medium text-slate-900">{{ order.delivery_company ?? '\u2014' }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-slate-500">Livreur</span>
                                <span class="font-medium text-slate-900">{{ order.delivery_guy_name }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-slate-500">Telephone</span>
                                <span class="font-medium text-slate-900">{{ order.delivery_guy_phone ?? '\u2014' }}</span>
                            </div>
                        </div>
                    </template>
                    <template v-else>
                        <p class="text-sm text-slate-500 italic">Aucune information de livraison.</p>
                    </template>
                </div>

                <!-- Communication -->
                <div class="rounded-xl border border-slate-200 bg-white p-5 space-y-3">
                    <h2 class="text-sm font-semibold text-slate-900 flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M8.625 9.75a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H8.25m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H12m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0h-.375m-13.5 3.01c0 1.6 1.123 2.994 2.707 3.227 1.087.16 2.185.283 3.293.369V21l4.184-4.183a1.14 1.14 0 01.778-.332 48.294 48.294 0 005.83-.498c1.585-.233 2.708-1.626 2.708-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0012 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018z" /></svg>
                        Communication
                    </h2>
                    <div v-if="order.vendor_shipping_note" class="rounded-lg bg-blue-50 border-l-4 border-blue-400 p-4">
                        <p class="text-xs font-semibold text-blue-700 mb-1">Message du vendeur au client</p>
                        <p class="text-sm text-blue-800 leading-relaxed">{{ order.vendor_shipping_note }}</p>
                    </div>
                    <p v-else class="text-sm text-slate-500 italic">Aucun message du vendeur.</p>
                </div>

                <!-- Plainte du client -->
                <div v-if="order.dispute_reason" class="rounded-xl border border-red-200 bg-red-50 p-5 space-y-2">
                    <h2 class="text-sm font-semibold text-red-800 flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-red-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" /></svg>
                        Plainte du client
                    </h2>
                    <p class="text-sm text-red-700">{{ order.dispute_reason }}</p>
                </div>

                <!-- Defense du vendeur -->
                <div v-if="order.vendor_defense_message" class="rounded-xl border border-teal-200 bg-teal-50 p-5 space-y-2">
                    <h2 class="text-sm font-semibold text-teal-800 flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-teal-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m0-10.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.75c0 5.592 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.75h-.152c-3.196 0-6.1-1.249-8.25-3.286zm0 13.036h.008v.008H12v-.008z" /></svg>
                        Defense du vendeur
                    </h2>
                    <p class="text-sm text-teal-700">{{ order.vendor_defense_message }}</p>
                    <a v-if="order.vendor_defense_proof" :href="`/storage/${order.vendor_defense_proof}`" target="_blank" class="mt-2 inline-flex items-center gap-1.5 text-xs font-medium text-teal-600 hover:text-teal-800 transition">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909M3.75 21h16.5A2.25 2.25 0 0022.5 18.75V5.25A2.25 2.25 0 0020.25 3H3.75A2.25 2.25 0 001.5 5.25v13.5A2.25 2.25 0 003.75 21z" /></svg>
                        Voir la preuve jointe
                    </a>
                </div>

                <div v-if="!order.vendor_defense_message && order.status === 'disputed'" class="rounded-xl border border-slate-200 bg-slate-50 p-5">
                    <p class="text-sm text-slate-500 italic">Le vendeur n'a pas encore soumis de defense.</p>
                </div>

                <!-- Chat de mediation -->
                <div class="rounded-xl border border-violet-200 bg-violet-50 p-5">
                    <Link
                        :href="route('admin.disputes.ecommerce-chat', order.id)"
                        class="flex w-full items-center justify-center gap-2 rounded-lg bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-slate-800"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 8.511c.884.284 1.5 1.128 1.5 2.097v4.286c0 1.136-.847 2.1-1.98 2.193-.34.027-.68.052-1.02.072v3.091l-3-3c-1.354 0-2.694-.055-4.02-.163a2.115 2.115 0 01-.825-.242m9.345-8.334a2.126 2.126 0 00-.476-.095 48.64 48.64 0 00-8.048 0c-1.131.094-1.976 1.057-1.976 2.192v4.286c0 1.136.845 2.1 1.976 2.193 1.07.089 2.15.137 3.224.137l3 3v-3.091c1.354-.089 2.694-.248 4.02-.479" /></svg>
                        Ouvrir le chat de mediation
                    </Link>
                </div>

                <!-- Actions -->
                <div v-if="order.status === 'disputed'" class="rounded-xl border border-slate-200 bg-white p-5 space-y-3">
                    <h2 class="text-sm font-semibold text-slate-900">Verdict</h2>
                    <div class="space-y-2">
                        <button @click="refundClient" class="w-full rounded-lg bg-red-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-red-500 flex items-center justify-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 15L3 9m0 0l6-6M3 9h12a6 6 0 010 12h-3" /></svg>
                            Rembourser le client
                        </button>
                        <button @click="favorVendor" class="w-full rounded-lg bg-teal-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-teal-500 flex items-center justify-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                            Donner raison au vendeur
                        </button>
                    </div>
                </div>
            </div>

            <!-- RIGHT: Timeline -->
            <div class="lg:col-span-2">
                <div class="rounded-xl border border-slate-200 bg-white p-6">
                    <h2 class="text-sm font-semibold text-slate-900 mb-6 flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        Chronologie de la commande
                    </h2>

                    <div v-if="timeline.length === 0" class="text-center py-8">
                        <p class="text-sm text-slate-500">Aucun evenement enregistre.</p>
                    </div>

                    <div v-else class="relative">
                        <!-- Vertical line -->
                        <div class="absolute left-3 top-2 bottom-2 w-px bg-slate-200"></div>

                        <div class="space-y-6">
                            <div v-for="(event, idx) in timeline" :key="idx" class="relative pl-10">
                                <!-- Dot -->
                                <div class="absolute left-1.5 top-1 h-3 w-3 rounded-full ring-2" :class="[getColor(event.color).dot, getColor(event.color).ring]"></div>

                                <!-- Card -->
                                <div class="rounded-lg px-4 py-3" :class="getColor(event.color).bg">
                                    <div class="flex items-start justify-between gap-2">
                                        <p class="text-sm font-semibold" :class="getColor(event.color).text">{{ event.label }}</p>
                                        <span class="text-xs text-slate-500 whitespace-nowrap">{{ formatDate(event.date) }}</span>
                                    </div>
                                    <p v-if="event.detail" class="mt-1 text-xs text-slate-600">{{ event.detail }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <ConfirmModal :show="confirmVisible" :title="confirmTitle" :message="confirmMessage" :variant="confirmVariant" :confirm-label="confirmLabel" :cancel-label="cancelLabel" @confirmed="onConfirm" @cancelled="onCancel" />
</template>
