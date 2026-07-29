<script setup>
import InfluencerLayout from '../../Layouts/InfluencerLayout.vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    orders: { type: Object, default: () => ({ data: [] }) },
});

// ──────────────────────────────────────────────
//  Helpers
// ──────────────────────────────────────────────

function formatCurrency(amount) {
    const v = parseFloat(amount);
    if (!v || v < 0) return '0 FCFA';
    return new Intl.NumberFormat('fr-FR', { style: 'decimal', minimumFractionDigits: 0, maximumFractionDigits: 0 }).format(v) + ' FCFA';
}

const statusConfig = {
    pending:             { label: 'En attente',         bg: 'bg-amber-50',   text: 'text-amber-700',   ring: 'ring-amber-600/10' },
    shooting:            { label: 'En production',      bg: 'bg-teal-50',    text: 'text-teal-700',    ring: 'ring-teal-600/10' },
    delivered:           { label: 'Livree',             bg: 'bg-purple-50',  text: 'text-purple-700',  ring: 'ring-purple-600/10' },
    revision_requested:  { label: 'Retouche demandee',  bg: 'bg-amber-50',   text: 'text-amber-700',   ring: 'ring-amber-600/10' },
    completed:           { label: 'Terminee',           bg: 'bg-teal-50',    text: 'text-teal-700',    ring: 'ring-teal-600/10' },
    disputed:            { label: 'Litige',             bg: 'bg-red-50',     text: 'text-red-700',     ring: 'ring-red-600/10' },
    approved:            { label: 'Approuvee',          bg: 'bg-emerald-50', text: 'text-emerald-700', ring: 'ring-emerald-600/10' },
    rejected:            { label: 'Rejetee',            bg: 'bg-red-50',     text: 'text-red-700',     ring: 'ring-red-600/10' },
};

function getStatus(s) {
    return statusConfig[s] || { label: s, bg: 'bg-slate-50', text: 'text-slate-700', ring: 'ring-slate-600/10' };
}

const typeLabels = { ugc_humain: 'UGC Humain', video_pub_ia: 'Video Pub IA' };

// ──────────────────────────────────────────────
//  Actions
// ──────────────────────────────────────────────

const processingId = ref(null);

function accept(order) {
    processingId.value = order.id;
    router.patch(route('influencer.service-orders.accept', order.id), {}, {
        preserveScroll: true,
        onFinish: () => (processingId.value = null),
    });
}

// ──────────────────────────────────────────────
//  Upload video
// ──────────────────────────────────────────────

const deliverForm = useForm({ video: null });
const deliveringId = ref(null);

function deliverVideo(order, event) {
    const file = event.target.files?.[0];
    if (!file) return;

    deliveringId.value = order.id;
    deliverForm.video = file;

    deliverForm.post(route('influencer.service-orders.deliver', order.id), {
        preserveScroll: true,
        forceFormData: true,
        onFinish: () => {
            deliveringId.value = null;
            deliverForm.reset();
        },
    });
}
</script>

<template>
    <Head title="Commandes recues" />

    <InfluencerLayout>
        <div class="mx-auto max-w-5xl px-4 py-6 sm:px-6 space-y-6">

            <!-- Header -->
            <div>
                <h1 class="text-xl font-bold text-slate-900">Commandes recues</h1>
                <p class="mt-1 text-sm text-slate-500">Les vendeurs qui ont commande vos services de contenu video.</p>
            </div>

            <!-- ─────────────────────────────────────
                 Liste des commandes
            ───────────────────────────────────── -->
            <div v-if="orders.data && orders.data.length" class="space-y-4">
                <div
                    v-for="order in orders.data"
                    :key="order.id"
                    class="group rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm transition-all duration-500 hover:-translate-y-1 hover:shadow-xl hover:shadow-teal-500/5"
                >
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                        <div class="flex-1">
                            <div class="flex items-center gap-3">
                                <h3 class="text-sm font-semibold text-slate-900">{{ order.service?.title ?? 'Service' }}</h3>
                                <span
                                    class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-semibold ring-1"
                                    :class="[getStatus(order.status).bg, getStatus(order.status).text, getStatus(order.status).ring]"
                                >
                                    {{ getStatus(order.status).label }}
                                </span>
                            </div>
                            <div class="mt-2 flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-slate-500">
                                <span class="flex items-center gap-1">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 21v-7.5a.75.75 0 01.75-.75h3a.75.75 0 01.75.75V21m-4.5 0H2.36m11.14 0H18m0 0h3.64m-1.39 0V9.349m-16.5 11.65V9.35m0 0a3.001 3.001 0 003.75-.615A2.993 2.993 0 009.75 9.75c.896 0 1.7-.393 2.25-1.016a2.993 2.993 0 002.25 1.016c.896 0 1.7-.393 2.25-1.016A3.001 3.001 0 0021 9.349m-18 0a2.999 2.999 0 00.62-1.1L5.25 3h13.5l1.63 5.25c.108.35.272.674.492.96" />
                                    </svg>
                                    {{ order.vendor?.shop_name || order.vendor?.business_name || order.vendor?.name || '—' }}
                                </span>
                                <span v-if="order.product" class="flex items-center gap-1">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 10-7.5 0v4.5m11.356-1.993l1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 01-1.12-1.243l1.264-12A1.125 1.125 0 015.513 7.5h12.974c.576 0 1.059.435 1.119 1.007zM8.625 10.5a.375.375 0 11-.75 0 .375.375 0 01.75 0zm7.5 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" />
                                    </svg>
                                    {{ order.product.name }}
                                </span>
                                <span class="font-semibold text-violet-700">{{ formatCurrency(order.amount) }}</span>
                            </div>

                            <!-- Brief -->
                            <div class="mt-3 rounded-lg border border-slate-100 bg-slate-50 p-3">
                                <p class="text-xs font-medium text-slate-500 mb-1">Brief du vendeur</p>
                                <p class="text-sm text-slate-700 whitespace-pre-line">{{ order.brief }}</p>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="flex shrink-0 flex-col gap-2">
                            <!-- Lien vers le detail -->
                            <Link
                                :href="route('influencer.service-orders.show', order.id)"
                                class="inline-flex items-center gap-1.5 rounded-full border border-teal-200 bg-white px-4 py-2 text-xs font-semibold text-slate-700 transition-all duration-300 hover:bg-teal-50 hover:border-teal-300 hover:-translate-y-0.5"
                            >
                                Voir
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                                </svg>
                            </Link>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Etat vide -->
            <div v-else class="rounded-2xl border-2 border-dashed border-teal-200 bg-gradient-to-br from-teal-50/50 to-cyan-50/30 p-12 text-center">
                <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-teal-50">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-teal-400" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor">
                        <path stroke-linecap="round" d="M15.75 10.5l4.72-4.72a.75.75 0 011.28.53v11.38a.75.75 0 01-1.28.53l-4.72-4.72M4.5 18.75h9a2.25 2.25 0 002.25-2.25v-9a2.25 2.25 0 00-2.25-2.25h-9A2.25 2.25 0 002.25 7.5v9a2.25 2.25 0 002.25 2.25z" />
                    </svg>
                </div>
                <h3 class="mt-4 text-base font-semibold text-slate-900">Aucune commande recue</h3>
                <p class="mt-1.5 text-sm text-slate-500">
                    Les vendeurs pourront commander vos services une fois que vous les aurez publies.
                </p>
            </div>
        </div>
    </InfluencerLayout>
</template>
