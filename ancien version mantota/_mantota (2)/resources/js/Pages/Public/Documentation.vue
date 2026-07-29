<script setup>
import { Head, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import PublicLayout from '../../Components/PublicLayout.vue';

const props = defineProps({
    doc_vendor_intro: { type: String, default: '' },
    doc_influencer_intro: { type: String, default: '' },
    doc_general_intro: { type: String, default: '' },
});

const page = usePage();
const gs = computed(() => page.props.global_settings || {});
const activeTab = ref('vendeur');

const tabs = [
    { key: 'vendeur', label: 'Vendeurs', color: 'teal' },
    { key: 'influenceur', label: 'Createurs de Contenu', color: 'purple' },
    { key: 'general', label: 'General', color: 'slate' },
];
</script>

<template>
    <Head title="Documentation" />

    <PublicLayout>
        <!-- Hero -->
        <section class="relative overflow-hidden pt-12 pb-10 sm:pt-16 sm:pb-14">
            <div class="absolute inset-0 bg-[radial-gradient(ellipse_80%_50%_at_50%_-20%,rgba(20,184,166,0.12),transparent)]" />
            <div class="relative mx-auto max-w-5xl px-4 text-center sm:px-6">
                <div data-reveal="fade-up" class="mb-4 inline-flex items-center gap-2 rounded-full border border-teal-500/20 bg-teal-500/10 px-4 py-1.5 text-sm text-teal-300">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25" /></svg>
                    Documentation
                </div>
                <h1 data-reveal="fade-up" data-delay="100" class="text-2xl font-bold tracking-tight text-white sm:text-3xl md:text-4xl">Documentation</h1>
                <p data-reveal="fade-up" data-delay="200" class="mx-auto mt-4 max-w-2xl text-base text-slate-400 sm:text-lg">
                    Tout ce que vous devez savoir pour utiliser {{ gs.company_name || 'MANTOTA' }} efficacement.
                </p>
            </div>
        </section>

        <!-- Tabs + Content -->
        <section class="py-10 sm:py-14">
            <div class="mx-auto max-w-5xl px-4 sm:px-6">
                <!-- Tab bar -->
                <div data-reveal="fade-up" class="mb-8 flex justify-center">
                    <div class="inline-flex rounded-xl border border-slate-800 bg-slate-900/80 p-1">
                        <button
                            v-for="t in tabs"
                            :key="t.key"
                            @click="activeTab = t.key"
                            :class="[
                                'rounded-lg px-5 py-2 text-sm font-medium transition-all',
                                activeTab === t.key
                                    ? t.color === 'teal' ? 'bg-teal-600 text-white shadow-sm' : t.color === 'purple' ? 'bg-purple-600 text-white shadow-sm' : 'bg-slate-700 text-white shadow-sm'
                                    : 'text-slate-400 hover:text-white',
                            ]"
                        >{{ t.label }}</button>
                    </div>
                </div>

                <!-- ═══ VENDEUR ═══ -->
                <div v-if="activeTab === 'vendeur'" class="space-y-5">
                    <p v-if="doc_vendor_intro" data-reveal="fade-up" class="mb-4 text-sm leading-relaxed text-slate-400">{{ doc_vendor_intro }}</p>

                    <div v-for="(s, i) in [
                        { title: '1. Creer un compte vendeur', items: ['Rendez-vous sur la page d\'inscription vendeur.', 'Fournissez : Nom complet, email, Nom de la boutique (slug unique), Mot de passe (min. 8 caracteres).', 'Apres inscription, votre compte doit etre verifie (KYC) par l\'administration.'] },
                        { title: '2. Ajouter des produits', items: ['Depuis votre tableau de bord, allez dans Produits > Nouveau produit.', 'Ajoutez un nom, une description, un prix et des images.', 'Definissez le pourcentage de commission que le createur de contenu recevra sur chaque vente.', 'Choisissez le type : physique (avec livraison) ou digital.'] },
                        { title: '3. Lancer une campagne', items: ['Titre : Donnez un nom clair a votre campagne.', 'Destination : Promouvoir votre boutique ou un produit specifique.', 'Media : Uploadez une image ou video (JPG, PNG, MP4, max 50 Mo).', 'CPC : Le prix que vous payez par clic (minimum dynamique).', 'Budget : Le montant total deduit de votre wallet.', 'Pays cibles, Reseaux sociaux (TikTok, Facebook, Instagram, YouTube, Snapchat), Niche.', 'Le palier (Bronze, Argent, Or) est calcule automatiquement selon le budget.'] },
                        { title: '4. Suivre les performances', items: ['Nombre de clics payes vs clics totaux.', 'Createurs de contenu participants et performances individuelles.', 'Commandes et ventes generees par chaque createur de contenu.', 'Budget restant et depenses CPC + CPA.'] },
                    ]" :key="i" data-reveal="fade-up" :data-delay="i * 80" class="rounded-2xl border border-slate-800 bg-slate-900/50 overflow-hidden">
                        <div class="border-b border-slate-800 bg-teal-500/5 px-6 py-4">
                            <h2 class="text-base font-bold text-white">{{ s.title }}</h2>
                        </div>
                        <div class="p-6">
                            <ul class="ml-4 list-disc space-y-2 text-sm leading-relaxed text-slate-400">
                                <li v-for="(item, j) in s.items" :key="j">{{ item }}</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- ═══ INFLUENCEUR ═══ -->
                <div v-if="activeTab === 'influenceur'" class="space-y-5">
                    <p v-if="doc_influencer_intro" data-reveal="fade-up" class="mb-4 text-sm leading-relaxed text-slate-400">{{ doc_influencer_intro }}</p>

                    <div v-for="(s, i) in [
                        { title: '1. Creer un compte createur de contenu', items: ['Fournissez : Nom, email, Reseaux sociaux avec nombre d\'abonnes, Vos niches (jusqu\'a 3 categories).', 'Votre palier (Bronze, Argent, Or) est calcule automatiquement selon vos abonnes totaux.'] },
                        { title: '2. Parcourir les campagnes', items: ['Le moteur Ads vous montre les campagnes correspondant a votre palier et vos niches.', 'Consultez le CPC offert, le budget restant et la commission sur vente.', 'Verifiez que la campagne cible vos pays et reseaux.', 'Generez un SmartLink unique pour suivre vos performances.'] },
                        { title: '3. Gagner de l\'argent', items: ['CPC (Cout par Clic) : Chaque clic verifie sur votre SmartLink vous rapporte le montant CPC defini par le vendeur.', 'Commission sur vente : Si un acheteur passe commande via votre lien, vous recevez un % du prix de vente.', 'Vos gains sont credites automatiquement dans votre wallet.'] },
                        { title: '4. Retirer ses gains', items: ['Allez dans Wallet > Demander un retrait.', 'Le montant minimum est affiche sur la page Tarifs.', 'Des frais de retrait s\'appliquent (pourcentage indique sur la page Tarifs).', 'Le paiement est effectue via Mobile Money / Virement.'] },
                        { title: '5. Studio UGC', items: ['Le Studio UGC permet aux vendeurs de commander du contenu video/photo aupres des createurs de contenu.', 'Creez un service (video TikTok, reel Instagram, etc.) avec votre prix et vos delais.', 'Gerez les commandes, soumettez vos creations, et recevez le paiement une fois valide.'] },
                    ]" :key="i" data-reveal="fade-up" :data-delay="i * 80" class="rounded-2xl border border-slate-800 bg-slate-900/50 overflow-hidden">
                        <div class="border-b border-slate-800 bg-purple-500/5 px-6 py-4">
                            <h2 class="text-base font-bold text-white">{{ s.title }}</h2>
                        </div>
                        <div class="p-6">
                            <ul class="ml-4 list-disc space-y-2 text-sm leading-relaxed text-slate-400">
                                <li v-for="(item, j) in s.items" :key="j">{{ item }}</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- ═══ GENERAL ═══ -->
                <div v-if="activeTab === 'general'" class="space-y-5">
                    <p v-if="doc_general_intro" data-reveal="fade-up" class="mb-4 text-sm leading-relaxed text-slate-400">{{ doc_general_intro }}</p>

                    <div v-for="(s, i) in [
                        { title: 'Modele de paiement', items: ['Aucun abonnement mensuel ou annuel.', 'Aucun frais d\'inscription.', 'Les vendeurs ne paient que les clics verifies et les commissions sur ventes reelles.', 'Les createurs de contenu touchent leur remuneration automatiquement.', 'Consultez la page Tarifs pour les montants exacts et a jour.'] },
                        { title: 'Verification KYC', items: ['Vendeurs : obligatoire avant de lancer des campagnes.', 'Createurs de contenu : obligatoire avant de demander des retraits.', 'Soumettez une piece d\'identite valide. Verification sous 24 a 48 heures.'] },
                        { title: 'Support et assistance', items: ['Ticket de support : Depuis votre dashboard ou la page support publique.', gs.whatsapp_phone ? 'WhatsApp : ' + gs.whatsapp_phone : null, gs.contact_email ? 'Email : ' + gs.contact_email : null].filter(Boolean) },
                        { title: 'Securite et anti-fraude', items: ['Verification de clics : Seuls les clics uniques et verifies sont factures.', 'Geo-ciblage : Les clics hors zone cible ne sont pas comptabilises.', 'Preuve de publication : Les createurs de contenu soumettent une preuve pour chaque campagne.', 'Systeme de litige : Les acheteurs peuvent ouvrir un litige sur les commandes problematiques.'] },
                    ]" :key="i" data-reveal="fade-up" :data-delay="i * 80" class="rounded-2xl border border-slate-800 bg-slate-900/50 overflow-hidden">
                        <div class="border-b border-slate-800 bg-slate-800/30 px-6 py-4">
                            <h2 class="text-base font-bold text-white">{{ s.title }}</h2>
                        </div>
                        <div class="p-6">
                            <ul class="ml-4 list-disc space-y-2 text-sm leading-relaxed text-slate-400">
                                <li v-for="(item, j) in s.items" :key="j">{{ item }}</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- CTA -->
                <div class="mt-10 text-center">
                    <p data-reveal="fade-up" class="mb-4 text-sm text-slate-400">Vous avez encore des questions ?</p>
                    <a data-reveal="fade-up" data-delay="100" :href="route('support.create')" class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-teal-500 to-teal-600 px-6 py-3 text-sm font-semibold text-white shadow-lg shadow-teal-500/25 transition-all hover:shadow-teal-500/40 hover:brightness-110 active:scale-[0.97]">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H8.25m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H12m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 01-2.555-.337A5.972 5.972 0 015.41 20.97a5.969 5.969 0 01-.474-.065 4.48 4.48 0 00.978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25z" /></svg>
                        Contacter le support
                    </a>
                </div>
            </div>
        </section>
    </PublicLayout>
</template>
