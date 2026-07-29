<script setup>
import { Head, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import PublicLayout from '../../Components/PublicLayout.vue';

const props = defineProps({
    about_mission: { type: String, default: '' },
    about_why: { type: String, default: '' },
});

const page = usePage();
const gs = computed(() => page.props.global_settings || {});
</script>

<template>
    <Head title="A propos" />

    <PublicLayout>
        <!-- Hero -->
        <section class="relative overflow-hidden pt-12 pb-10 sm:pt-16 sm:pb-14">
            <div class="absolute inset-0 bg-[radial-gradient(ellipse_80%_50%_at_50%_-20%,rgba(20,184,166,0.12),transparent)]" />
            <div class="relative mx-auto max-w-4xl px-4 text-center sm:px-6">
                <div data-reveal="fade-up" class="mb-4 inline-flex items-center gap-2 rounded-full border border-teal-500/20 bg-teal-500/10 px-4 py-1.5 text-sm text-teal-300">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198l.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719m12 0a5.971 5.971 0 00-.941-3.197m0 0A5.995 5.995 0 0012 12.75a5.995 5.995 0 00-5.058 2.772m0 0a3 3 0 00-4.681 2.72 8.986 8.986 0 003.74.477m.94-3.197a5.971 5.971 0 00-.94 3.197M15 6.75a3 3 0 11-6 0 3 3 0 016 0zm6 3a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0zm-13.5 0a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z" /></svg>
                    A propos
                </div>
                <h1 data-reveal="fade-up" data-delay="100" class="text-2xl font-bold tracking-tight text-white sm:text-3xl md:text-4xl">A propos de {{ gs.company_name || 'MANTOTA' }}</h1>
                <p data-reveal="fade-up" data-delay="200" class="mx-auto mt-4 max-w-2xl text-base text-slate-400 sm:text-lg">Notre mission, notre vision, nos valeurs.</p>
            </div>
        </section>

        <!-- Mission -->
        <section class="py-12 sm:py-16">
            <div class="mx-auto max-w-4xl px-4 sm:px-6">
                <div data-reveal="fade-up" class="rounded-2xl border border-slate-800 bg-slate-900/50 p-6 sm:p-10">
                    <h2 class="mb-4 text-xl font-bold text-white sm:text-2xl">Notre mission</h2>
                    <p class="leading-relaxed text-slate-400">
                        {{ about_mission || (gs.company_name || 'MANTOTA') + ' est un reseau publicitaire 100% performance ne en Afrique francophone. Notre mission est de connecter les vendeurs et les createurs de contenu pour generer des resultats concrets : pas d\'abonnement, pas de frais caches — vous ne payez que les clics et les ventes reelles.' }}
                    </p>
                </div>
            </div>
        </section>

        <!-- Comment ca marche -->
        <section class="py-12 sm:py-16">
            <div class="mx-auto max-w-4xl px-4 sm:px-6">
                <h2 data-reveal="fade-up" class="mb-8 text-center text-xl font-bold text-white sm:text-2xl">Comment ca marche</h2>
                <div class="grid gap-5 sm:grid-cols-3">
                    <div data-reveal="fade-up" data-delay="0" class="group rounded-2xl border border-slate-800 bg-slate-900/50 p-5 transition-all duration-300 hover:border-teal-500/30 hover:bg-slate-900/80">
                        <div class="mb-4 flex h-10 w-10 items-center justify-center rounded-xl bg-teal-500/10"><span class="text-lg font-bold text-teal-400">1</span></div>
                        <h3 class="text-sm font-semibold text-white">Creez une campagne</h3>
                        <p class="mt-2 text-xs leading-relaxed text-slate-500">Le vendeur definit son budget, son CPC et selectionne son audience cible.</p>
                    </div>
                    <div data-reveal="fade-up" data-delay="150" class="group rounded-2xl border border-slate-800 bg-slate-900/50 p-5 transition-all duration-300 hover:border-purple-500/30 hover:bg-slate-900/80">
                        <div class="mb-4 flex h-10 w-10 items-center justify-center rounded-xl bg-purple-500/10"><span class="text-lg font-bold text-purple-400">2</span></div>
                        <h3 class="text-sm font-semibold text-white">Partagez les liens</h3>
                        <p class="mt-2 text-xs leading-relaxed text-slate-500">Les createurs de contenu generent des SmartLinks et partagent sur leurs reseaux.</p>
                    </div>
                    <div data-reveal="fade-up" data-delay="300" class="group rounded-2xl border border-slate-800 bg-slate-900/50 p-5 transition-all duration-300 hover:border-amber-500/30 hover:bg-slate-900/80">
                        <div class="mb-4 flex h-10 w-10 items-center justify-center rounded-xl bg-amber-500/10"><span class="text-lg font-bold text-amber-400">3</span></div>
                        <h3 class="text-sm font-semibold text-white">Gagnez ensemble</h3>
                        <p class="mt-2 text-xs leading-relaxed text-slate-500">Les clics et ventes sont traces. Tout le monde est paye a la performance.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Nos valeurs -->
        <section class="py-12 sm:py-16">
            <div class="mx-auto max-w-4xl px-4 sm:px-6">
                <h2 data-reveal="fade-up" class="mb-8 text-center text-xl font-bold text-white sm:text-2xl">Nos valeurs</h2>
                <div class="grid gap-5 sm:grid-cols-2">
                    <div v-for="(v, i) in [
                        { title: 'Transparence', desc: 'Tous les tarifs, frais et commissions sont publics et mis a jour en temps reel.', color: 'teal' },
                        { title: 'Performance', desc: 'Zero abonnement, zero frais caches. Vous ne payez que les resultats (CPC + commissions).', color: 'purple' },
                        { title: 'Equite', desc: 'Un systeme equitable ou vendeurs et createurs de contenu reussissent ensemble.', color: 'teal' },
                        { title: 'Innovation africaine', desc: 'Concu pour la realite du marche africain — paiements mobiles, reseaux sociaux locaux, logistique adaptee.', color: 'amber' },
                    ]" :key="i" data-reveal="fade-up" :data-delay="i * 100" class="flex items-start gap-4 rounded-2xl border border-slate-800 bg-slate-900/50 p-5 transition-all duration-300 hover:border-slate-700 hover:bg-slate-900/80">
                        <div class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-full" :class="v.color === 'teal' ? 'bg-teal-500/10' : v.color === 'purple' ? 'bg-purple-500/10' : 'bg-amber-500/10'">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" :class="v.color === 'teal' ? 'text-teal-400' : v.color === 'purple' ? 'text-purple-400' : 'text-amber-400'" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" /></svg>
                        </div>
                        <div>
                            <h3 class="text-sm font-semibold text-white">{{ v.title }}</h3>
                            <p class="mt-1 text-sm text-slate-400">{{ v.desc }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Pourquoi -->
        <section class="py-12 sm:py-16">
            <div class="mx-auto max-w-4xl px-4 sm:px-6">
                <div data-reveal="fade-up" class="rounded-2xl border border-slate-800 bg-slate-900/50 p-6 sm:p-10">
                    <h2 class="mb-4 text-xl font-bold text-white sm:text-2xl">Pourquoi {{ gs.company_name || 'MANTOTA' }} ?</h2>
                    <p class="leading-relaxed text-slate-400">
                        {{ about_why || 'Dans un marche ou le marketing d\'influence est souvent opaque et couteux, ' + (gs.company_name || 'MANTOTA') + ' apporte la transparence et la performance. Nous avons cree cette plateforme pour que chaque franc investi par un vendeur se traduise en resultats mesurables, et que chaque clic genere par un createur de contenu soit justement remunere.' }}
                    </p>
                </div>
            </div>
        </section>

        <!-- Contact -->
        <section class="py-12 sm:py-16">
            <div class="mx-auto max-w-4xl px-4 sm:px-6">
                <div data-reveal="fade-up" class="rounded-2xl border border-slate-800 bg-slate-900/60 p-6 sm:p-10">
                    <h2 class="mb-5 text-lg font-bold text-white sm:text-xl">Nous contacter</h2>
                    <div class="space-y-2 text-sm text-slate-400">
                        <p v-if="gs.contact_email"><span class="font-medium text-slate-300">Email :</span> {{ gs.contact_email }}</p>
                        <p v-if="gs.whatsapp_phone"><span class="font-medium text-slate-300">WhatsApp :</span> {{ gs.whatsapp_phone }}</p>
                        <p v-if="gs.physical_address"><span class="font-medium text-slate-300">Adresse :</span> {{ gs.physical_address }}</p>
                        <p v-if="gs.rccm"><span class="font-medium text-slate-300">RCCM :</span> {{ gs.rccm }}</p>
                        <p v-if="gs.ifu"><span class="font-medium text-slate-300">IFU :</span> {{ gs.ifu }}</p>
                    </div>
                    <div class="mt-6">
                        <a :href="route('support.create')" class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-teal-500 to-teal-600 px-5 py-3 text-sm font-semibold text-white shadow-lg shadow-teal-500/25 transition-all hover:shadow-teal-500/40 hover:brightness-110 active:scale-[0.97]">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H8.25m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H12m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 01-2.555-.337A5.972 5.972 0 015.41 20.97a5.969 5.969 0 01-.474-.065 4.48 4.48 0 00.978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25z" /></svg>
                            Contacter le support
                        </a>
                    </div>
                </div>
            </div>
        </section>
    </PublicLayout>
</template>
