<script setup>
import { Head, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage();
const gs = computed(() => page.props.global_settings || {});
</script>

<template>
    <Head title="Politique de Confidentialite" />

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
                    <h1 class="mb-2 text-3xl font-bold text-slate-900">Politique de Confidentialite</h1>
                    <p class="mb-8 text-sm text-slate-500">Derniere mise a jour : Mars 2025</p>

                    <div class="prose prose-slate max-w-none prose-headings:text-slate-900 prose-p:text-slate-600 prose-li:text-slate-600">
                        <h2>1. Introduction</h2>
                        <p>La societe {{ gs.company_name || 'MANTOTA' }} accorde une grande importance a la protection des donnees personnelles de ses utilisateurs. La presente politique de confidentialite a pour objet d'informer les utilisateurs de la plateforme sur la maniere dont leurs donnees personnelles sont collectees, traitees et protegees.</p>

                        <h2>2. Donnees collectees</h2>
                        <p>Dans le cadre de l'utilisation de la plateforme, nous sommes amenes a collecter les categories de donnees suivantes :</p>
                        <ul>
                            <li><strong>Donnees d'identification :</strong> nom, prenom, adresse email, numero de telephone</li>
                            <li><strong>Donnees de connexion :</strong> adresse IP, type de navigateur, pages consultees, horodatage</li>
                            <li><strong>Donnees financieres :</strong> informations relatives aux transactions, historique des paiements et retraits</li>
                            <li><strong>Donnees professionnelles :</strong> informations relatives a l'activite commerciale (pour les vendeurs) ou a l'audience (pour les createurs de contenu)</li>
                        </ul>

                        <h2>3. Finalites du traitement</h2>
                        <p>Les donnees collectees sont traitees pour les finalites suivantes :</p>
                        <ul>
                            <li>La gestion des comptes utilisateurs et l'authentification</li>
                            <li>La fourniture et l'amelioration des services de la plateforme</li>
                            <li>Le traitement des transactions financieres</li>
                            <li>La communication avec les utilisateurs (notifications, support)</li>
                            <li>Le respect des obligations legales et reglementaires</li>
                            <li>La prevention de la fraude et la securite de la plateforme</li>
                        </ul>

                        <h2>4. Base legale du traitement</h2>
                        <p>Le traitement des donnees personnelles repose sur les bases legales suivantes :</p>
                        <ul>
                            <li>L'execution du contrat liant l'utilisateur a la plateforme</li>
                            <li>Le consentement de l'utilisateur</li>
                            <li>L'interet legitime de la plateforme</li>
                            <li>Le respect d'obligations legales</li>
                        </ul>

                        <h2>5. Destinataires des donnees</h2>
                        <p>Les donnees personnelles peuvent etre communiquees aux destinataires suivants :</p>
                        <ul>
                            <li>Les equipes internes de la plateforme (support, technique, finance)</li>
                            <li>Les prestataires de services de paiement</li>
                            <li>Les autorites competentes en cas de requisition legale</li>
                        </ul>
                        <p>Les donnees ne sont jamais vendues a des tiers a des fins commerciales.</p>

                        <h2>6. Duree de conservation</h2>
                        <p>Les donnees personnelles sont conservees pendant la duree necessaire a l'accomplissement des finalites pour lesquelles elles ont ete collectees. A l'issue de cette duree, les donnees sont supprimees ou anonymisees de maniere irreversible.</p>

                        <h2>7. Securite des donnees</h2>
                        <p>La plateforme met en oeuvre des mesures techniques et organisationnelles appropriees pour proteger les donnees personnelles contre tout acces non autorise, toute modification, divulgation ou destruction. Ces mesures incluent le chiffrement des donnees sensibles, la securisation des communications et le controle des acces.</p>

                        <h2>8. Droits des utilisateurs</h2>
                        <p>Conformement a la reglementation en vigueur, chaque utilisateur dispose des droits suivants :</p>
                        <ul>
                            <li><strong>Droit d'acces :</strong> obtenir la confirmation du traitement de ses donnees et en recevoir une copie</li>
                            <li><strong>Droit de rectification :</strong> demander la correction de donnees inexactes ou incompletes</li>
                            <li><strong>Droit a l'effacement :</strong> demander la suppression de ses donnees dans les cas prevus par la loi</li>
                            <li><strong>Droit a la portabilite :</strong> recevoir ses donnees dans un format structure et couramment utilise</li>
                            <li><strong>Droit d'opposition :</strong> s'opposer au traitement de ses donnees pour des motifs legitimes</li>
                        </ul>
                        <p>Pour exercer ces droits, l'utilisateur peut nous contacter a l'adresse {{ gs.contact_email || 'contact@mantota.com' }}.</p>

                        <h2>9. Cookies</h2>
                        <p>La plateforme utilise des cookies necessaires au bon fonctionnement du service (cookies de session, cookies d'authentification). Ces cookies sont essentiels et ne necessitent pas de consentement prealable. Aucun cookie de tracking publicitaire n'est utilise.</p>

                        <h2>10. Modification de la politique</h2>
                        <p>La presente politique de confidentialite peut etre modifiee a tout moment. Les utilisateurs seront informes de toute modification substantielle. La date de derniere mise a jour est indiquee en haut de ce document.</p>

                        <h2>11. Contact</h2>
                        <p>Pour toute question relative a la protection de vos donnees personnelles, vous pouvez nous contacter :</p>
                        <ul>
                            <li>Par email : {{ gs.contact_email || 'contact@mantota.com' }}</li>
                            <li>Par courrier : {{ gs.physical_address || 'Adresse du siege social' }}</li>
                        </ul>
                    </div>
                </div>
            </div>
        </main>
    </div>
</template>
