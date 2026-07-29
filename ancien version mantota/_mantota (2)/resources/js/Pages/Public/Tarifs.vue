<script setup>
import { Head } from '@inertiajs/vue3';
import PublicLayout from '../../Components/PublicLayout.vue';

const props = defineProps({
    min_cpc:                { type: Number, default: 25 },
    withdrawal_fee_percent: { type: Number, default: 20 },
    min_withdrawal_amount:  { type: Number, default: 1000 },
    ugc_studio_fee_percent: { type: Number, default: 15 },
    tier_thresholds:        { type: Object, default: () => ({ argent: 25000, or: 100000 }) },
});

function fmt(n) {
    return new Intl.NumberFormat('fr-FR').format(n);
}
</script>

<template>
    <Head title="Tarifs" />

    <PublicLayout>
        <!-- Hero -->
        <section class="relative overflow-hidden pt-12 pb-10 sm:pt-16 sm:pb-14">
            <div class="absolute inset-0 bg-[radial-gradient(ellipse_80%_50%_at_50%_-20%,rgba(20,184,166,0.12),transparent)]" />
            <div class="relative mx-auto max-w-5xl px-4 text-center sm:px-6">
                <div data-reveal="fade-up" class="mb-4 inline-flex items-center gap-2 rounded-full border border-teal-500/20 bg-teal-500/10 px-4 py-1.5 text-sm text-teal-300">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75M15 10.5a3 3 0 11-6 0 3 3 0 016 0zm3 0h.008v.008H18V10.5zm-12 0h.008v.008H6V10.5z" /></svg>
                    Tarification
                </div>
                <h1 data-reveal="fade-up" data-delay="100" class="text-2xl font-bold tracking-tight text-white sm:text-3xl md:text-4xl">Tarifs transparents</h1>
                <p data-reveal="fade-up" data-delay="200" class="mx-auto mt-4 max-w-2xl text-base text-slate-400 sm:text-lg">
                    Aucun abonnement, aucun frais caches. Vous ne payez que les resultats.
                    Les tarifs ci-dessous sont mis a jour en temps reel par l'administration.
                </p>
            </div>
        </section>

        <!-- Section 1 : CPC Vendeurs -->
        <section class="py-10 sm:py-14">
            <div class="mx-auto max-w-5xl px-4 sm:px-6">
                <div data-reveal="fade-up" class="rounded-2xl border border-slate-800 bg-slate-900/50 overflow-hidden">
                    <div class="flex items-center gap-3 border-b border-slate-800 bg-teal-500/5 px-6 py-5">
                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-teal-500/10">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-teal-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75M15 10.5a3 3 0 11-6 0 3 3 0 016 0zm3 0h.008v.008H18V10.5zm-12 0h.008v.008H6V10.5z" /></svg>
                        </div>
                        <div>
                            <h2 class="text-lg font-bold text-white">Cout par Clic (CPC) -- Vendeurs</h2>
                            <p class="text-sm text-slate-500">Budget publicitaire pour campagnes d'influence</p>
                        </div>
                    </div>
                    <div class="p-6">
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div class="rounded-xl border border-teal-500/20 bg-teal-500/5 p-4">
                                <p class="text-sm font-medium text-slate-400">Prix minimum par clic</p>
                                <p class="mt-1 text-2xl font-bold text-teal-400">{{ fmt(min_cpc) }} FCFA</p>
                                <p class="mt-1 text-xs text-slate-500">Le vendeur fixe son CPC au-dessus de ce seuil</p>
                            </div>
                            <div class="rounded-xl border border-slate-700/50 bg-slate-800/50 p-4">
                                <p class="text-sm font-medium text-slate-400">Commission sur vente</p>
                                <p class="mt-1 text-2xl font-bold text-white">Variable</p>
                                <p class="mt-1 text-xs text-slate-500">Definie par produit (% du prix de vente)</p>
                            </div>
                        </div>

                        <div class="mt-6">
                            <h3 class="mb-3 text-sm font-semibold uppercase tracking-wider text-slate-500">Paliers de campagne</h3>
                            <div class="grid gap-3 sm:grid-cols-3">
                                <div class="rounded-xl border border-amber-500/20 bg-gradient-to-br from-amber-500/5 to-transparent p-4">
                                    <div class="mb-2 inline-flex items-center rounded-full bg-amber-500/10 px-2.5 py-0.5 text-xs font-semibold text-amber-400">Bronze</div>
                                    <p class="text-sm text-slate-300">Budget &lt; {{ fmt(tier_thresholds.argent) }} FCFA</p>
                                    <p class="mt-1 text-xs text-slate-500">Nano-createurs de contenu (1k - 10k abonnes)</p>
                                </div>
                                <div class="rounded-xl border border-purple-500/20 bg-gradient-to-br from-purple-500/5 to-transparent p-4">
                                    <div class="mb-2 inline-flex items-center rounded-full bg-purple-500/10 px-2.5 py-0.5 text-xs font-semibold text-purple-400">Argent</div>
                                    <p class="text-sm text-slate-300">{{ fmt(tier_thresholds.argent) }} - {{ fmt(tier_thresholds.or) }} FCFA</p>
                                    <p class="mt-1 text-xs text-slate-500">Micro/Macro (10k - 100k abonnes)</p>
                                </div>
                                <div class="rounded-xl border border-yellow-500/20 bg-gradient-to-br from-yellow-500/5 to-transparent p-4">
                                    <div class="mb-2 inline-flex items-center rounded-full bg-yellow-500/10 px-2.5 py-0.5 text-xs font-semibold text-yellow-400">Or</div>
                                    <p class="text-sm text-slate-300">&ge; {{ fmt(tier_thresholds.or) }} FCFA</p>
                                    <p class="mt-1 text-xs text-slate-500">Elite / Stars (100k - 1M+ abonnes)</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Section 2 : Retraits Influenceurs -->
        <section class="py-10 sm:py-14">
            <div class="mx-auto max-w-5xl px-4 sm:px-6">
                <div data-reveal="fade-up" class="rounded-2xl border border-slate-800 bg-slate-900/50 overflow-hidden">
                    <div class="flex items-center gap-3 border-b border-slate-800 bg-purple-500/5 px-6 py-5">
                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-purple-500/10">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-purple-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" /></svg>
                        </div>
                        <div>
                            <h2 class="text-lg font-bold text-white">Retraits -- Createurs de Contenu</h2>
                            <p class="text-sm text-slate-500">Frais appliques lors du retrait de vos gains</p>
                        </div>
                    </div>
                    <div class="p-6">
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div class="rounded-xl border border-purple-500/20 bg-purple-500/5 p-4">
                                <p class="text-sm font-medium text-slate-400">Frais de retrait</p>
                                <p class="mt-1 text-2xl font-bold text-purple-400">{{ withdrawal_fee_percent }}%</p>
                                <p class="mt-1 text-xs text-slate-500">Preleves sur le montant retire</p>
                            </div>
                            <div class="rounded-xl border border-slate-700/50 bg-slate-800/50 p-4">
                                <p class="text-sm font-medium text-slate-400">Montant minimum de retrait</p>
                                <p class="mt-1 text-2xl font-bold text-white">{{ fmt(min_withdrawal_amount) }} FCFA</p>
                                <p class="mt-1 text-xs text-slate-500">Seuil minimum pour demander un retrait</p>
                            </div>
                        </div>
                        <div class="mt-4 rounded-xl border border-purple-500/10 bg-purple-500/5 px-4 py-3">
                            <p class="text-sm text-purple-300">
                                <strong>Exemple :</strong> Pour un retrait de {{ fmt(10000) }} FCFA, les frais sont de
                                {{ fmt(10000 * withdrawal_fee_percent / 100) }} FCFA. Vous recevez
                                {{ fmt(10000 - 10000 * withdrawal_fee_percent / 100) }} FCFA.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Section 3 : Studio UGC -->
        <section class="py-10 sm:py-14">
            <div class="mx-auto max-w-5xl px-4 sm:px-6">
                <div data-reveal="fade-up" class="rounded-2xl border border-slate-800 bg-slate-900/50 overflow-hidden">
                    <div class="flex items-center gap-3 border-b border-slate-800 bg-rose-500/5 px-6 py-5">
                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-rose-500/10">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-rose-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m15.75 10.5 4.72-4.72a.75.75 0 0 1 1.28.53v11.38a.75.75 0 0 1-1.28.53l-4.72-4.72M4.5 18.75h9a2.25 2.25 0 0 0 2.25-2.25v-9a2.25 2.25 0 0 0-2.25-2.25h-9A2.25 2.25 0 0 0 2.25 7.5v9a2.25 2.25 0 0 0 2.25 2.25Z" /></svg>
                        </div>
                        <div>
                            <h2 class="text-lg font-bold text-white">Studio UGC</h2>
                            <p class="text-sm text-slate-500">Commission sur les commandes de contenu UGC</p>
                        </div>
                    </div>
                    <div class="p-6">
                        <div class="rounded-xl border border-rose-500/20 bg-rose-500/5 p-4">
                            <p class="text-sm font-medium text-slate-400">Commission plateforme</p>
                            <p class="mt-1 text-2xl font-bold text-rose-400">{{ ugc_studio_fee_percent }}%</p>
                            <p class="mt-1 text-xs text-slate-500">Prelevee sur chaque commande UGC terminee</p>
                        </div>
                        <div class="mt-4 rounded-xl border border-rose-500/10 bg-rose-500/5 px-4 py-3">
                            <p class="text-sm text-rose-300">
                                <strong>Exemple :</strong> Pour une commande UGC de {{ fmt(50000) }} FCFA, la commission est de
                                {{ fmt(50000 * ugc_studio_fee_percent / 100) }} FCFA. Le createur de contenu recoit
                                {{ fmt(50000 - 50000 * ugc_studio_fee_percent / 100) }} FCFA.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Section 4 : Resume -->
        <section class="py-10 sm:py-14">
            <div class="mx-auto max-w-5xl px-4 sm:px-6">
                <div data-reveal="fade-up" class="rounded-2xl border border-slate-800 bg-slate-900/50 overflow-hidden">
                    <div class="border-b border-slate-800 bg-slate-800/30 px-6 py-5">
                        <h2 class="text-lg font-bold text-white">Resume des frais</h2>
                    </div>
                    <div class="p-6 overflow-x-auto">
                        <table class="w-full text-left text-sm">
                            <thead>
                                <tr class="border-b border-slate-700/50">
                                    <th class="pb-3 font-semibold text-slate-400">Element</th>
                                    <th class="pb-3 font-semibold text-slate-400">Montant</th>
                                    <th class="hidden pb-3 font-semibold text-slate-400 sm:table-cell">Concerne</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-800">
                                <tr><td class="py-3 text-slate-300">CPC minimum</td><td class="py-3 font-medium text-white">{{ fmt(min_cpc) }} FCFA</td><td class="hidden py-3 text-slate-500 sm:table-cell">Vendeurs</td></tr>
                                <tr><td class="py-3 text-slate-300">Palier Bronze</td><td class="py-3 font-medium text-white">&lt; {{ fmt(tier_thresholds.argent) }} FCFA</td><td class="hidden py-3 text-slate-500 sm:table-cell">Vendeurs</td></tr>
                                <tr><td class="py-3 text-slate-300">Palier Argent</td><td class="py-3 font-medium text-white">{{ fmt(tier_thresholds.argent) }} -- {{ fmt(tier_thresholds.or) }} FCFA</td><td class="hidden py-3 text-slate-500 sm:table-cell">Vendeurs</td></tr>
                                <tr><td class="py-3 text-slate-300">Palier Or</td><td class="py-3 font-medium text-white">&ge; {{ fmt(tier_thresholds.or) }} FCFA</td><td class="hidden py-3 text-slate-500 sm:table-cell">Vendeurs</td></tr>
                                <tr><td class="py-3 text-slate-300">Frais de retrait</td><td class="py-3 font-medium text-white">{{ withdrawal_fee_percent }}%</td><td class="hidden py-3 text-slate-500 sm:table-cell">Createurs de Contenu</td></tr>
                                <tr><td class="py-3 text-slate-300">Retrait minimum</td><td class="py-3 font-medium text-white">{{ fmt(min_withdrawal_amount) }} FCFA</td><td class="hidden py-3 text-slate-500 sm:table-cell">Createurs de Contenu</td></tr>
                                <tr><td class="py-3 text-slate-300">Commission Studio UGC</td><td class="py-3 font-medium text-white">{{ ugc_studio_fee_percent }}%</td><td class="hidden py-3 text-slate-500 sm:table-cell">Createurs de Contenu</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </section>

        <!-- CTA -->
        <section class="py-10 sm:py-14">
            <div class="mx-auto max-w-5xl px-4 text-center sm:px-6">
                <p data-reveal="fade-up" class="mb-5 text-sm text-slate-400">Pret a commencer ?</p>
                <div data-reveal="fade-up" data-delay="100" class="flex flex-col items-center justify-center gap-3 sm:flex-row sm:gap-4">
                    <a :href="route('vendor.register')" class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-teal-500 to-teal-600 px-6 py-3 text-sm font-semibold text-white shadow-lg shadow-teal-500/25 transition-all hover:shadow-teal-500/40 hover:brightness-110 active:scale-[0.97] sm:w-auto">
                        Devenir Vendeur
                    </a>
                    <a :href="route('influencer.register')" class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-purple-500 to-purple-600 px-6 py-3 text-sm font-semibold text-white shadow-lg shadow-purple-500/25 transition-all hover:shadow-purple-500/40 hover:brightness-110 active:scale-[0.97] sm:w-auto">
                        Devenir Createur de Contenu
                    </a>
                </div>
            </div>
        </section>
    </PublicLayout>
</template>
