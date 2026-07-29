<script setup>
import VendorLayout from '../../Layouts/VendorLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({
    order: { type: Object, required: true },
});

// ── Modal d'annulation ──
const showCancelModal = ref(false);
const cancelForm = useForm({ cancel_reason: '' });

function submitCancel() {
    cancelForm.post(route('vendor.orders.cancel', props.order.id), {
        onSuccess: () => { showCancelModal.value = false; cancelForm.reset(); },
    });
}

// ── Peut-on annuler cette commande ? ──
const canCancel = computed(() => {
    return ['pending', 'shipped'].includes(props.order.status) && props.order.status !== 'cancelled';
});

function formatCurrency(amount) {
    const v = parseFloat(amount);
    if (!v || v < 0) return '0 FCFA';
    return new Intl.NumberFormat('fr-FR', { style: 'decimal', minimumFractionDigits: 0, maximumFractionDigits: 0 }).format(v) + ' FCFA';
}

function formatDate(dateStr) {
    if (!dateStr) return '\u2014';
    return new Date(dateStr).toLocaleDateString('fr-FR', {
        day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit',
    });
}

const statusConfig = {
    pending:   { label: 'En attente',  bgClass: 'bg-amber-100 text-amber-700' },
    shipped:   { label: 'Expediee',    bgClass: 'bg-purple-100 text-purple-700' },
    delivered: { label: 'Livree',      bgClass: 'bg-emerald-100 text-emerald-700' },
    disputed:  { label: 'Litige',      bgClass: 'bg-red-100 text-red-700' },
    cancelled: { label: 'Annulee',     bgClass: 'bg-slate-100 text-slate-500' },
    disputed_resolved: { label: 'Litige resolu', bgClass: 'bg-slate-100 text-slate-600' },
};
</script>

<template>
    <Head :title="`Commande ${order.reference}`" />

    <VendorLayout>
        <template #header>
            <div class="flex items-center gap-3">
                <Link :href="route('vendor.orders.index')" class="flex items-center gap-1.5 text-sm font-medium text-slate-500 transition hover:text-slate-700">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" /></svg>
                    Retour
                </Link>
                <h2 class="text-xl font-semibold leading-tight text-slate-800">
                    Commande {{ order.reference }}
                </h2>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-5xl sm:px-6 lg:px-8 space-y-6">

                <!-- En-tete: Produit + Statut -->
                <div class="rounded-2xl bg-white shadow-sm border border-slate-200/80 overflow-hidden">
                    <div class="flex flex-wrap items-center justify-between gap-4 px-6 py-5">
                        <div class="flex items-center gap-4">
                            <div class="h-14 w-14 shrink-0 overflow-hidden rounded-xl bg-slate-100">
                                <img
                                    v-if="order.product?.image_path"
                                    :src="`/storage/${order.product.image_path}`"
                                    :alt="order.product?.name"
                                    class="h-full w-full object-cover"
                                />
                                <div v-else class="flex h-full w-full items-center justify-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-slate-300" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 7.5l-9-5.25L3 7.5m18 0l-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-9v9" />
                                    </svg>
                                </div>
                            </div>
                            <div>
                                <p class="text-base font-bold text-slate-900">{{ order.product?.name || 'Produit' }}</p>
                                <p class="text-sm font-semibold text-purple-600">{{ formatCurrency(order.amount_paid) }}</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-3">
                            <span
                                class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold"
                                :class="statusConfig[order.status]?.bgClass || 'bg-slate-100 text-slate-500'"
                            >
                                {{ statusConfig[order.status]?.label || order.status }}
                            </span>
                            <span class="text-xs text-slate-500">{{ formatDate(order.created_at) }}</span>
                            <!-- Bouton annulation -->
                            <button
                                v-if="canCancel"
                                @click="showCancelModal = true"
                                class="inline-flex items-center gap-1.5 rounded-lg border border-red-300 bg-red-50 px-3 py-1.5 text-xs font-semibold text-red-600 hover:bg-red-100 transition"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                                Annuler
                            </button>
                        </div>
                    </div>
                </div>

                <!-- 3 cartes -->
                <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">

                    <!-- Carte 1 : Acheteur -->
                    <div class="rounded-2xl bg-white shadow-sm border border-slate-200/80 overflow-hidden">
                        <div class="border-b border-slate-100 bg-slate-50 px-5 py-3 flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />
                            </svg>
                            <h3 class="text-sm font-bold text-slate-800">Acheteur</h3>
                        </div>
                        <div class="divide-y divide-slate-100">
                            <div class="flex items-center justify-between px-5 py-3">
                                <span class="text-sm text-slate-500">Nom</span>
                                <span class="text-sm font-medium text-slate-900">{{ order.customer_name || '\u2014' }}</span>
                            </div>
                            <div class="flex items-center justify-between px-5 py-3">
                                <span class="text-sm text-slate-500">Telephone</span>
                                <span class="text-sm font-medium text-slate-900">{{ order.customer_phone || '\u2014' }}</span>
                            </div>
                            <div v-if="order.customer_whatsapp" class="flex items-center justify-between px-5 py-3">
                                <span class="text-sm text-slate-500">WhatsApp</span>
                                <a
                                    :href="`https://wa.me/${order.customer_whatsapp?.replace(/[^0-9+]/g, '')}`"
                                    target="_blank"
                                    class="text-sm font-medium text-emerald-600 hover:text-emerald-700 transition"
                                >
                                    {{ order.customer_whatsapp }}
                                </a>
                            </div>
                            <div class="flex items-center justify-between px-5 py-3">
                                <span class="text-sm text-slate-500">Ville</span>
                                <span class="text-sm font-medium text-slate-900">{{ order.city || '\u2014' }}</span>
                            </div>
                            <div v-if="order.landmark_indication" class="px-5 py-3">
                                <span class="text-sm text-slate-500">Repere</span>
                                <p class="mt-1 text-sm text-slate-900">{{ order.landmark_indication }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Carte 2 : Logistique -->
                    <div class="rounded-2xl bg-white shadow-sm border border-slate-200/80 overflow-hidden">
                        <div class="border-b border-slate-100 bg-slate-50 px-5 py-3 flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 01-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 00-3.213-9.193 2.056 2.056 0 00-1.58-.86H14.25M16.5 18.75h-2.25m0-11.177v-.958c0-.568-.422-1.048-.987-1.106a48.554 48.554 0 00-10.026 0 1.106 1.106 0 00-.987 1.106v7.635m12-6.677v6.677m0 4.5v-4.5m0 0h-12" />
                            </svg>
                            <h3 class="text-sm font-bold text-slate-800">Logistique</h3>
                        </div>

                        <template v-if="['shipped', 'delivered', 'disputed', 'disputed_resolved'].includes(order.status)">
                            <div class="divide-y divide-slate-100">
                                <div class="flex items-center justify-between px-5 py-3">
                                    <span class="text-sm text-slate-500">Societe de livraison</span>
                                    <span class="text-sm font-medium text-slate-900">{{ order.delivery_company || '\u2014' }}</span>
                                </div>
                                <div class="flex items-center justify-between px-5 py-3">
                                    <span class="text-sm text-slate-500">Nom du livreur</span>
                                    <span class="text-sm font-medium text-slate-900">{{ order.delivery_guy_name || '\u2014' }}</span>
                                </div>
                                <div class="flex items-center justify-between px-5 py-3">
                                    <span class="text-sm text-slate-500">Telephone du livreur</span>
                                    <span class="text-sm font-medium text-slate-900">{{ order.delivery_guy_phone || '\u2014' }}</span>
                                </div>
                            </div>
                        </template>

                        <template v-else>
                            <div class="px-5 py-8 text-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="mx-auto h-8 w-8 text-slate-300" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <p class="mt-2 text-sm text-slate-500">En attente d'expedition</p>
                            </div>
                        </template>
                    </div>

                    <!-- Carte 3 : Communication -->
                    <div class="rounded-2xl bg-white shadow-sm border border-slate-200/80 overflow-hidden">
                        <div class="border-b border-slate-100 bg-slate-50 px-5 py-3 flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8.625 9.75a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H8.25m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H12m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0h-.375m-13.5 3.01c0 1.6 1.123 2.994 2.707 3.227 1.087.16 2.185.283 3.293.369V21l4.184-4.183a1.14 1.14 0 01.778-.332 48.294 48.294 0 005.83-.498c1.585-.233 2.708-1.626 2.708-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0012 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018z" />
                            </svg>
                            <h3 class="text-sm font-bold text-slate-800">Communication</h3>
                        </div>

                        <div class="p-5 space-y-4">
                            <template v-if="order.vendor_shipping_note">
                                <div class="rounded-lg bg-blue-50 border-l-4 border-blue-400 p-4">
                                    <p class="text-xs font-semibold text-blue-700 mb-1">Votre message au client</p>
                                    <p class="text-sm text-blue-800 leading-relaxed">{{ order.vendor_shipping_note }}</p>
                                </div>
                            </template>
                            <template v-else>
                                <p class="text-sm text-slate-500 italic">Aucun message envoye au client.</p>
                            </template>

                            <!-- Dispute info -->
                            <div v-if="order.dispute_reason" class="rounded-lg bg-red-50 border-l-4 border-red-400 p-4">
                                <p class="text-xs font-semibold text-red-700 mb-1">Plainte du client</p>
                                <p class="text-sm text-red-800 leading-relaxed">{{ order.dispute_reason }}</p>
                            </div>

                            <div v-if="order.vendor_defense_message" class="rounded-lg bg-purple-50 border-l-4 border-purple-400 p-4">
                                <p class="text-xs font-semibold text-purple-700 mb-1">Votre defense</p>
                                <p class="text-sm text-purple-800 leading-relaxed">{{ order.vendor_defense_message }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Repartition financiere -->
                <div class="rounded-2xl bg-white shadow-sm border border-slate-200/80 overflow-hidden">
                    <div class="border-b border-slate-100 bg-slate-50 px-5 py-3 flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75M15 10.5a3 3 0 11-6 0 3 3 0 016 0zm3 0h.008v.008H18V10.5zm-12 0h.008v.008H6V10.5z" />
                        </svg>
                        <h3 class="text-sm font-bold text-slate-800">Repartition financiere</h3>
                    </div>
                    <div class="divide-y divide-slate-100">
                        <div class="flex items-center justify-between px-5 py-3">
                            <span class="text-sm text-slate-500">Montant total paye</span>
                            <span class="text-sm font-semibold text-slate-900">{{ formatCurrency(order.amount_paid) }}</span>
                        </div>
                        <div class="flex items-center justify-between px-5 py-3">
                            <span class="text-sm text-slate-500">Votre gain net</span>
                            <span class="text-sm font-semibold text-purple-700">{{ formatCurrency(order.vendor_earnings) }}</span>
                        </div>
                        <div v-if="parseFloat(order.commission_amount) > 0" class="flex items-center justify-between px-5 py-3">
                            <span class="text-sm text-slate-500">
                                Commission createur de contenu
                                <span v-if="order.influencer" class="text-slate-400">(<Link :href="route('vendor.influencer.show', order.influencer.id)" class="font-medium text-purple-600 hover:text-purple-800 underline">{{ order.influencer.name }}</Link>)</span>
                            </span>
                            <span class="text-sm font-medium text-slate-900">{{ formatCurrency(order.commission_amount) }}</span>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <!-- ══ Modal : Annulation commande ══ -->
        <Teleport to="body">
            <div v-if="showCancelModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 px-4">
                <div class="w-full max-w-md rounded-2xl bg-white shadow-2xl overflow-hidden">
                    <div class="border-b border-slate-100 bg-red-50 px-6 py-4 flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-red-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                            </svg>
                            <h3 class="text-base font-bold text-red-800">Annuler la commande {{ order.reference }}</h3>
                        </div>
                        <button @click="showCancelModal = false" class="text-slate-400 hover:text-slate-600 transition">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <form @submit.prevent="submitCancel" class="px-6 py-5 space-y-4">
                        <div class="rounded-xl bg-amber-50 border border-amber-200 px-4 py-3 text-sm text-amber-800 leading-relaxed">
                            <p><strong>Attention :</strong> Cette action est irréversible.</p>
                            <ul class="mt-1 ml-4 list-disc text-xs space-y-0.5">
                                <li>L'escrow vendeur et créateur de contenu sera libéré.</li>
                                <li>Le stock sera restauré (produit physique).</li>
                                <li>Le remboursement client doit être traité manuellement.</li>
                            </ul>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">
                                Raison de l'annulation <span class="text-red-500">*</span>
                            </label>
                            <textarea
                                v-model="cancelForm.cancel_reason"
                                rows="4"
                                placeholder="Ex: Produit en rupture de stock imprévue, problème de livraison dans cette zone..."
                                class="block w-full rounded-lg border-slate-300 shadow-sm focus:border-red-400 focus:ring-red-400 text-sm"
                                required
                            ></textarea>
                            <p v-if="cancelForm.errors.cancel_reason" class="mt-1 text-xs text-red-600">
                                {{ cancelForm.errors.cancel_reason }}
                            </p>
                        </div>

                        <div class="flex gap-3 pt-1">
                            <button
                                type="button"
                                @click="showCancelModal = false"
                                class="flex-1 rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 hover:bg-slate-50 transition"
                            >
                                Annuler
                            </button>
                            <button
                                type="submit"
                                :disabled="cancelForm.processing || !cancelForm.cancel_reason.trim()"
                                class="flex-1 rounded-lg bg-red-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-red-700 transition disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2"
                            >
                                <svg v-if="cancelForm.processing" class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <span v-if="cancelForm.processing">Traitement en cours...</span>
                                <span v-else>Confirmer l'annulation</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>
    </VendorLayout>
</template>
