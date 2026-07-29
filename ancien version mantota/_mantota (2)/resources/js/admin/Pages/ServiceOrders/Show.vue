<script setup>
import Layout from '../Layout.vue';
import { Head, Link } from '@inertiajs/vue3';

const props = defineProps({
    order: { type: Object, required: true },
});

function formatCurrency(amount) {
    const v = parseFloat(amount);
    if (!v || v < 0) return '0 FCFA';
    return new Intl.NumberFormat('fr-FR', { style: 'decimal', minimumFractionDigits: 0, maximumFractionDigits: 0 }).format(v) + ' FCFA';
}

function formatTime(dateStr) {
    if (!dateStr) return '';
    const d = new Date(dateStr);
    return d.toLocaleDateString('fr-FR', { day: '2-digit', month: 'short' }) + ' ' + d.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' });
}

function isImage(path) {
    if (!path) return false;
    return /\.(jpg|jpeg|png|webp|gif)$/i.test(path);
}

const statusConfig = {
    pending:             { label: 'En attente',        bg: 'bg-amber-50',   text: 'text-amber-700',   ring: 'ring-amber-600/10' },
    shooting:            { label: 'En production',     bg: 'bg-teal-50',    text: 'text-teal-700',    ring: 'ring-teal-600/10' },
    delivered:           { label: 'Livree',            bg: 'bg-purple-50',  text: 'text-purple-700',  ring: 'ring-purple-600/10' },
    revision_requested:  { label: 'Retouche demandee', bg: 'bg-amber-50',   text: 'text-amber-700',   ring: 'ring-amber-600/10' },
    completed:           { label: 'Terminee',          bg: 'bg-teal-50',    text: 'text-teal-700',    ring: 'ring-teal-600/10' },
    disputed:            { label: 'Litige',            bg: 'bg-red-50',     text: 'text-red-700',     ring: 'ring-red-600/10' },
    approved:            { label: 'Approuvee',         bg: 'bg-emerald-50', text: 'text-emerald-700', ring: 'ring-emerald-600/10' },
    rejected:            { label: 'Rejetee',           bg: 'bg-red-50',     text: 'text-red-700',     ring: 'ring-red-600/10' },
    cancelled:           { label: 'Annulee',           bg: 'bg-slate-50',   text: 'text-slate-600',   ring: 'ring-slate-600/10' },
};

function getStatus(s) {
    return statusConfig[s] || { label: s, bg: 'bg-slate-50', text: 'text-slate-700', ring: 'ring-slate-600/10' };
}

function senderBadge(role) {
    if (role === 'admin') return { label: 'ADMIN', bg: 'bg-purple-600', text: 'text-white' };
    if (role === 'vendor') return { label: 'VENDEUR', bg: 'bg-teal-100', text: 'text-teal-700' };
    if (role === 'influencer') return { label: 'CREATEUR DE CONTENU', bg: 'bg-purple-100', text: 'text-purple-700' };
    return { label: role?.toUpperCase() ?? '?', bg: 'bg-slate-100', text: 'text-slate-600' };
}
</script>

<template>
    <Head :title="`Admin — Commande UGC #${order.id}`" />

    <Layout>
        <div class="mx-auto max-w-6xl px-4 py-6 sm:px-6 space-y-6">

            <!-- Header -->
            <div class="flex items-center gap-4">
                <Link :href="route('admin.disputes.index')"
                    class="inline-flex items-center gap-1.5 text-sm font-medium text-slate-500 transition hover:text-slate-700">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" /></svg>
                    Retour
                </Link>
                <h1 class="text-xl font-bold text-slate-900">Commande UGC #{{ order.id }}</h1>
                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold ring-1"
                      :class="[getStatus(order.status).bg, getStatus(order.status).text, getStatus(order.status).ring]">
                    {{ getStatus(order.status).label }}
                </span>
                <!-- Link to intervention chat -->
                <Link :href="route('admin.disputes.chat.show', order.id)"
                    class="ml-auto inline-flex items-center gap-1.5 rounded-lg border border-purple-300 bg-purple-50 px-3 py-2 text-xs font-semibold text-purple-700 transition hover:bg-purple-100">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M11.42 15.17l-5.384 3.234a1.125 1.125 0 01-1.603-.828V6.112a1.125 1.125 0 011.603-.828l5.384 3.234m5.098-.174l-1.395 5.66a1.125 1.125 0 01-1.594.696l-2.756-1.396m8.349-4.96a1.125 1.125 0 010 1.12l-1.177 2.043" /></svg>
                    Intervention Divine
                </Link>
            </div>

            <!-- READ-ONLY BANNER -->
            <div class="flex items-center gap-2.5 rounded-xl border border-slate-300 bg-slate-50 px-5 py-3">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0 text-slate-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
                <p class="text-sm font-medium text-slate-600">Mode lecture seule — Vous observez la conversation entre le vendeur et le createur de contenu.</p>
            </div>

            <!-- 2-COLUMN LAYOUT -->
            <div class="flex flex-col lg:flex-row gap-6">

                <!-- LEFT: Details (30%) -->
                <aside class="w-full lg:w-[30%] space-y-5 shrink-0">

                    <!-- Parties -->
                    <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm space-y-4">
                        <h2 class="text-sm font-semibold text-slate-900">Parties</h2>
                        <div class="space-y-3 text-sm">
                            <div>
                                <p class="text-xs text-slate-500">Vendeur</p>
                                <p class="font-semibold text-slate-900">{{ order.vendor?.name ?? '-' }}</p>
                                <p class="text-xs text-slate-400">{{ order.vendor?.email }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-slate-500">Createur de Contenu</p>
                                <p class="font-semibold text-slate-900">{{ order.influencer?.name ?? '-' }}</p>
                                <p class="text-xs text-slate-400">{{ order.influencer?.email }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Resume -->
                    <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm space-y-4">
                        <h2 class="text-sm font-semibold text-slate-900">Resume de la commande</h2>
                        <div class="space-y-3 text-sm">
                            <div><p class="text-xs text-slate-500">Service</p><p class="font-semibold text-slate-900">{{ order.service?.title ?? '-' }}</p></div>
                            <div><p class="text-xs text-slate-500">Type</p><p class="font-semibold text-slate-900">{{ order.service?.type === 'ugc_humain' ? 'UGC Humain' : order.service?.type === 'video_pub_ia' ? 'Video Pub IA' : order.service?.type ?? '-' }}</p></div>
                            <div><p class="text-xs text-slate-500">Duree</p><p class="font-semibold text-slate-900">{{ order.service?.duration ?? '-' }}</p></div>
                            <div><p class="text-xs text-slate-500">Produit</p><p class="font-semibold text-slate-900">{{ order.product?.name ?? 'Aucun' }}</p></div>
                            <div><p class="text-xs text-slate-500">Montant (Escrow)</p><p class="font-semibold text-teal-700">{{ formatCurrency(order.amount) }}</p></div>
                            <div class="flex items-center gap-2 text-xs text-slate-500">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182M20.982 4.356v4.993" /></svg>
                                Retouches : {{ order.revisions_used }} / {{ order.revisions_allowed }}
                            </div>
                        </div>
                        <div class="rounded-lg border border-slate-100 bg-slate-50 p-3">
                            <p class="text-xs font-medium text-slate-500 mb-1">Brief du vendeur</p>
                            <p class="text-xs text-slate-700 whitespace-pre-line">{{ order.brief }}</p>
                        </div>
                    </div>

                    <!-- Video -->
                    <div v-if="order.video_path" class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                        <h2 class="mb-3 text-sm font-semibold text-slate-900 flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-teal-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" d="M15.75 10.5l4.72-4.72a.75.75 0 011.28.53v11.38a.75.75 0 01-1.28.53l-4.72-4.72M4.5 18.75h9a2.25 2.25 0 002.25-2.25v-9a2.25 2.25 0 00-2.25-2.25h-9A2.25 2.25 0 002.25 7.5v9a2.25 2.25 0 002.25 2.25z" /></svg>
                            Video livree
                        </h2>
                        <video :src="`/storage/${order.video_path}`" controls class="w-full rounded-lg border border-slate-200 bg-black" />
                    </div>
                </aside>

                <!-- RIGHT: Chat (70%) — READ ONLY -->
                <section class="flex-1 flex flex-col rounded-xl border border-slate-200 bg-white shadow-sm overflow-hidden" style="min-height: 600px;">

                    <!-- Header -->
                    <div class="flex items-center gap-2.5 border-b border-slate-100 bg-purple-50 px-5 py-3">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0 text-purple-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        <p class="text-xs font-semibold text-purple-800">OMNISCIENCE — Historique complet des messages ({{ order.messages?.length ?? 0 }})</p>
                    </div>

                    <!-- Messages area -->
                    <div class="flex-1 overflow-y-auto px-5 py-4 space-y-4">
                        <div v-if="!order.messages?.length" class="flex flex-col items-center justify-center h-full text-center text-slate-400">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 mb-2 text-slate-300" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H8.25m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H12m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 01-2.555-.337A5.972 5.972 0 015.41 20.97a5.969 5.969 0 01-.474-.065 4.48 4.48 0 00.978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25z" /></svg>
                            <p class="text-sm">Aucun message echange.</p>
                        </div>

                        <template v-for="msg in order.messages" :key="msg.id">
                            <!-- ADMIN message -->
                            <div v-if="msg.sender?.role === 'admin'" class="w-full">
                                <div class="rounded-xl bg-slate-900 border-l-4 border-purple-500 px-5 py-4 space-y-2">
                                    <div class="flex items-center gap-2">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-purple-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" /></svg>
                                        <span class="text-xs font-bold uppercase tracking-wider text-purple-400">ADMIN ({{ msg.sender?.name }})</span>
                                        <span class="ml-auto text-[10px] text-slate-500">{{ formatTime(msg.created_at) }}</span>
                                    </div>
                                    <p v-if="msg.message" class="text-sm text-white whitespace-pre-line leading-relaxed">{{ msg.message }}</p>
                                    <div v-if="msg.attachment_path" class="mt-2">
                                        <img v-if="isImage(msg.attachment_path)" :src="`/storage/${msg.attachment_path}`" class="max-w-full max-h-48 rounded-lg border border-purple-500/30" alt="Piece jointe" />
                                        <a v-else :href="`/storage/${msg.attachment_path}`" target="_blank" class="inline-flex items-center gap-1.5 rounded-lg bg-purple-900 px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-purple-800">Telecharger</a>
                                    </div>
                                </div>
                            </div>

                            <!-- Vendor / Influencer messages -->
                            <div v-else class="flex" :class="msg.sender?.role === 'vendor' ? 'justify-start' : 'justify-end'">
                                <div class="max-w-[75%] space-y-1">
                                    <div class="flex items-center gap-2 px-1" :class="msg.sender?.role === 'vendor' ? '' : 'justify-end'">
                                        <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[9px] font-bold"
                                              :class="[senderBadge(msg.sender?.role).bg, senderBadge(msg.sender?.role).text]">
                                            {{ senderBadge(msg.sender?.role).label }}
                                        </span>
                                        <span class="text-[10px] font-medium text-slate-500">{{ msg.sender?.name ?? 'Utilisateur' }}</span>
                                    </div>
                                    <div class="rounded-2xl px-4 py-2.5 text-sm leading-relaxed"
                                         :class="msg.sender?.role === 'vendor' ? 'bg-teal-50 text-slate-800 rounded-bl-md border border-teal-200' : 'bg-purple-50 text-slate-800 rounded-br-md border border-purple-200'">
                                        <p v-if="msg.message" class="whitespace-pre-line">{{ msg.message }}</p>
                                        <div v-if="msg.attachment_path" class="mt-2">
                                            <img v-if="isImage(msg.attachment_path)" :src="`/storage/${msg.attachment_path}`"
                                                class="max-w-full max-h-48 rounded-lg border border-slate-200" alt="Piece jointe" />
                                            <a v-else :href="`/storage/${msg.attachment_path}`" target="_blank"
                                                class="inline-flex items-center gap-1.5 rounded-lg bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 border border-slate-200 transition hover:bg-slate-50">
                                                Telecharger le fichier
                                            </a>
                                        </div>
                                    </div>
                                    <p class="text-[10px] px-1 text-slate-400" :class="msg.sender?.role === 'vendor' ? '' : 'text-right'">{{ formatTime(msg.created_at) }}</p>
                                </div>
                            </div>
                        </template>
                    </div>

                    <!-- Locked footer — read only -->
                    <div class="border-t border-slate-300 bg-slate-100 px-6 py-4">
                        <div class="flex items-center justify-center gap-2 text-slate-500">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            <span class="text-sm font-medium">Mode lecture seule. Utilisez l'Intervention Divine pour repondre.</span>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </Layout>
</template>
