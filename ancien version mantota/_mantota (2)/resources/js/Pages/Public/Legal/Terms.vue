<script setup>
import { Head, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage();
const gs = computed(() => page.props.global_settings || {});
</script>

<template>
    <Head title="Conditions Generales de Vente" />

    <div class="min-h-screen bg-slate-50">
        <!-- Header -->
        <header class="border-b border-slate-200 bg-white">
            <div class="mx-auto flex h-16 max-w-4xl items-center justify-between px-4 sm:px-6">
                <a :href="route('home')" class="flex items-center gap-3">
                    <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-gradient-to-br from-teal-500 to-teal-700 shadow-sm">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-white" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z" /></svg>
                    </div>
                    <span class="text-lg font-bold text-slate-900">{{ gs.company_name || 'MANTOTA' }}</span>
                </a>
                <a :href="route('home')" class="text-sm font-medium text-teal-600 hover:text-teal-700">Retour a l'accueil</a>
            </div>
        </header>

        <main class="mx-auto max-w-4xl px-4 py-10 sm:px-6">
            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="px-6 py-8 sm:px-10 sm:py-12">
                    <h1 class="mb-2 text-3xl font-bold text-slate-900">Conditions Generales de Vente</h1>
                    <p class="mb-8 text-sm text-slate-500">Derniere mise a jour : Mars 2025</p>

                    <div class="prose prose-slate max-w-none prose-headings:text-slate-900 prose-p:text-slate-600 prose-li:text-slate-600">
                        <h2>1. Objet</h2>
                        <p>Les presentes conditions generales de vente (ci-apres "CGV") regissent l'ensemble des relations commerciales entre la societe {{ gs.company_name || 'MANTOTA' }}, immatriculee sous le RCCM {{ gs.rccm || 'N/A' }} et l'IFU {{ gs.ifu || 'N/A' }}, dont le siege social est situe a {{ gs.physical_address || 'N/A' }}, et toute personne physique ou morale (ci-apres "l'Utilisateur") accedant a la plateforme.</p>

                        <h2>2. Acces a la plateforme</h2>
                        <p>L'acces a la plateforme est ouvert a toute personne disposant d'une connexion Internet. L'inscription en tant que Vendeur ou Createur de Contenu est soumise a l'acceptation des presentes CGV. La plateforme se reserve le droit de refuser l'inscription de tout utilisateur sans avoir a motiver sa decision.</p>
                        <p>L'utilisateur s'engage a fournir des informations exactes et a jour lors de son inscription. Toute utilisation frauduleuse du compte pourra entrainer la suspension ou la suppression immediate du compte.</p>

                        <h2>3. Services proposes</h2>
                        <p>La plateforme propose un service de mise en relation entre vendeurs de produits et createurs de contenu pour la promotion desdits produits via des campagnes publicitaires. Les services incluent notamment :</p>
                        <ul>
                            <li>La creation et la gestion de campagnes publicitaires par les vendeurs</li>
                            <li>La participation des createurs de contenu aux campagnes via des liens de promotion</li>
                            <li>Le suivi des performances (clics, conversions) en temps reel</li>
                            <li>La gestion des paiements et des remunerations</li>
                        </ul>

                        <h2>4. Tarification et paiement</h2>
                        <p>Les tarifs applicables sont indiques sur la plateforme au moment de la souscription a un service. La plateforme se reserve le droit de modifier ses tarifs a tout moment, etant entendu que les modifications ne s'appliqueront qu'aux nouvelles commandes.</p>
                        <p>Les paiements sont effectues via les passerelles de paiement integrees a la plateforme. Les fonds collectes sont credites sur le portefeuille virtuel de l'utilisateur apres validation par la plateforme.</p>

                        <h2>5. Retrait des fonds</h2>
                        <p>Les utilisateurs peuvent demander le retrait de leurs fonds disponibles sous reserve d'atteindre le montant minimum de retrait fixe par la plateforme. Les demandes de retrait sont traitees dans un delai raisonnable. Des frais de retrait peuvent s'appliquer conformement aux conditions en vigueur.</p>

                        <h2>6. Obligations des utilisateurs</h2>
                        <p>Chaque utilisateur s'engage a :</p>
                        <ul>
                            <li>Respecter les lois et reglements en vigueur</li>
                            <li>Ne pas utiliser la plateforme a des fins frauduleuses ou illicites</li>
                            <li>Ne pas manipuler artificiellement les metriques de performance</li>
                            <li>Respecter les droits de propriete intellectuelle de tiers</li>
                            <li>Maintenir la confidentialite de ses identifiants de connexion</li>
                        </ul>

                        <h2>7. Responsabilite</h2>
                        <p>La plateforme met en oeuvre les moyens necessaires pour assurer un service de qualite. Toutefois, la plateforme ne saurait etre tenue responsable des dommages directs ou indirects resultant de l'utilisation du service, notamment en cas d'interruption temporaire du service pour maintenance ou pour toute autre raison technique.</p>

                        <h2>8. Propriete intellectuelle</h2>
                        <p>L'ensemble des elements constituant la plateforme (textes, images, logos, logiciels, base de donnees) est protege par les dispositions legales en vigueur relatives a la propriete intellectuelle. Toute reproduction, representation ou exploitation non autorisee est strictement interdite.</p>

                        <h2>9. Protection des donnees personnelles</h2>
                        <p>La collecte et le traitement des donnees personnelles sont effectues conformement a notre Politique de confidentialite, accessible depuis la plateforme. L'utilisateur dispose d'un droit d'acces, de rectification et de suppression de ses donnees personnelles.</p>

                        <h2>10. Modification des CGV</h2>
                        <p>La plateforme se reserve le droit de modifier les presentes CGV a tout moment. Les utilisateurs seront informes de toute modification substantielle. La poursuite de l'utilisation de la plateforme apres modification vaut acceptation des nouvelles conditions.</p>

                        <h2>11. Contact</h2>
                        <p>Pour toute question relative aux presentes CGV, vous pouvez nous contacter a l'adresse email {{ gs.contact_email || 'contact@mantota.com' }} ou via notre formulaire de support.</p>
                    </div>
                </div>
            </div>
        </main>
    </div>
</template>
