<script setup>
import { ref, reactive, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import Layout from '../Layout.vue';

defineOptions({ layout: Layout });

const props = defineProps({
    settings: { type: Object, default: () => ({}) },
});

const activeTab = ref('finances');

const tabs = [
    { key: 'finances', label: 'Taxes & Finances', icon: 'banknotes' },
    { key: 'tiers', label: 'Paliers Campagne', icon: 'layers' },
    { key: 'videos', label: 'Videos YouTube', icon: 'play' },
    { key: 'moderation', label: 'Moderation', icon: 'shield' },
    { key: 'entreprise', label: 'Entreprise', icon: 'building' },
    { key: 'logo', label: 'Logo', icon: 'photo' },
    { key: 'cms', label: 'Site Vitrine (CMS)', icon: 'globe' },
    { key: 'popups', label: 'Popups Bienvenue', icon: 'chat' },
    { key: 'ambassadeur', label: 'Ambassadeurs', icon: 'star' },
    { key: 'parrainage', label: 'Parrainage', icon: 'users' },
    { key: 'securite', label: 'Securite', icon: 'lock' },
];

const defaultSettings = {
    withdrawal_fee_percent: { value: '20', type: 'integer' },
    min_withdrawal_amount: { value: '1000', type: 'integer' },
    ugc_studio_fee_percent: { value: '15', type: 'integer' },
    min_cpc_price: { value: '25', type: 'integer' },
    platform_commission_rate: { value: '20', type: 'integer' },
    deposit_markup_percent: { value: '1.5', type: 'float' },
    campaign_commission_percent: { value: '5', type: 'float' },
    fedapay_fee_percent: { value: '1.5', type: 'float' },
    paydunya_fee_percent: { value: '2.0', type: 'float' },
    tier_argent_threshold: { value: '25000', type: 'integer' },
    tier_or_threshold: { value: '100000', type: 'integer' },
    tier_cost_bronze: { value: '2000', type: 'integer' },
    tier_cost_argent: { value: '5000', type: 'integer' },
    tier_cost_or: { value: '15000', type: 'integer' },
    tier_followers_bronze_min: { value: '1000', type: 'integer' },
    tier_followers_bronze_max: { value: '9999', type: 'integer' },
    tier_followers_argent_min: { value: '10000', type: 'integer' },
    tier_followers_argent_max: { value: '99999', type: 'integer' },
    tier_followers_or_min: { value: '100000', type: 'integer' },
    tier_followers_or_max: { value: '10000000', type: 'integer' },
    video_vendor_guide: { value: '', type: 'string' },
    video_influencer_guide: { value: '', type: 'string' },
    video_buyer_guide: { value: '', type: 'string' },
    video_welcome: { value: '', type: 'string' },
    banned_keywords: { value: '[]', type: 'json' },
    home_hero_title: { value: '', type: 'string' },
    home_hero_subtitle: { value: '', type: 'string' },
    home_step1_title: { value: '', type: 'string' },
    home_step1_desc: { value: '', type: 'string' },
    home_step2_title: { value: '', type: 'string' },
    home_step2_desc: { value: '', type: 'string' },
    home_step3_title: { value: '', type: 'string' },
    home_step3_desc: { value: '', type: 'string' },
    home_vendor_title: { value: '', type: 'string' },
    home_vendor_desc: { value: '', type: 'string' },
    home_influencer_title: { value: '', type: 'string' },
    home_influencer_desc: { value: '', type: 'string' },
    about_mission: { value: '', type: 'string' },
    about_why: { value: '', type: 'string' },
    doc_vendor_intro: { value: '', type: 'string' },
    doc_influencer_intro: { value: '', type: 'string' },
    doc_general_intro: { value: '', type: 'string' },
    company_name: { value: 'MANTOTA', type: 'string' },
    contact_email: { value: '', type: 'string' },
    whatsapp_phone: { value: '', type: 'string' },
    rccm: { value: '', type: 'string' },
    ifu: { value: '', type: 'string' },
    physical_address: { value: '', type: 'string' },
    social_facebook: { value: '', type: 'string' },
    social_instagram: { value: '', type: 'string' },
    social_tiktok: { value: '', type: 'string' },
    social_twitter: { value: '', type: 'string' },
    welcome_popup_vendor: { value: '', type: 'string' },
    welcome_popup_influencer: { value: '', type: 'string' },
    logo_width: { value: '140', type: 'integer' },
    logo_height: { value: '40', type: 'integer' },
    ambassador_badge_price: { value: '5000', type: 'integer' },
    ambassador_sale_enabled: { value: '0', type: 'boolean' },
    ambassador_subscription_duration: { value: '30', type: 'integer' },
    ambassador_commission_discount: { value: '50', type: 'integer' },
    ambassador_min_sales: { value: '50', type: 'integer' },
    ambassador_min_clicks: { value: '1000', type: 'integer' },
    restricted_circle_multiplier: { value: '1.5', type: 'float' },
    referral_enabled: { value: '1', type: 'boolean' },
    referral_bonus_amount: { value: '500', type: 'integer' },
    referral_transfer_threshold: { value: '10000', type: 'integer' },
    admin_recovery_code: { value: '', type: 'string' },
};

const form = reactive({});
for (const [key, defaults] of Object.entries(defaultSettings)) {
    const existing = props.settings[key];
    form[key] = {
        value: existing?.value ?? defaults.value,
        type: existing?.type ?? defaults.type,
    };
}
for (const [key, data] of Object.entries(props.settings)) {
    if (!form[key]) {
        form[key] = { value: data.value ?? '', type: data.type ?? 'string' };
    }
}

const saving = ref(false);

// Banned keywords: parse JSON array to/from textarea lines
const bannedKeywordsText = computed({
    get() {
        try {
            const arr = JSON.parse(form.banned_keywords?.value || '[]');
            return Array.isArray(arr) ? arr.join('\n') : '';
        } catch { return ''; }
    },
    set(val) {
        const arr = val.split('\n').map(s => s.trim()).filter(Boolean);
        form.banned_keywords.value = JSON.stringify(arr);
    },
});

function save() {
    saving.value = true;
    router.put(route('admin.settings.update'), { settings: form }, {
        preserveScroll: true,
        onFinish: () => { saving.value = false; },
    });
}

const financeKeys = ['withdrawal_fee_percent', 'min_withdrawal_amount', 'min_deposit_amount', 'ugc_studio_fee_percent', 'min_cpc_price', 'platform_commission_rate', 'deposit_markup_percent', 'campaign_commission_percent', 'fedapay_fee_percent', 'paydunya_fee_percent'];
const tierKeys = ['tier_argent_threshold', 'tier_or_threshold', 'tier_cost_bronze', 'tier_cost_argent', 'tier_cost_or', 'tier_followers_bronze_min', 'tier_followers_bronze_max', 'tier_followers_argent_min', 'tier_followers_argent_max', 'tier_followers_or_min', 'tier_followers_or_max'];
const videoKeys = ['video_vendor_guide', 'video_influencer_guide', 'video_buyer_guide', 'video_welcome'];
const entrepriseKeys = ['company_name', 'contact_email', 'whatsapp_phone', 'rccm', 'ifu', 'physical_address', 'social_facebook', 'social_instagram', 'social_tiktok', 'social_twitter'];
const cmsHomeKeys = ['home_hero_title', 'home_hero_subtitle', 'home_hero_image', 'home_step1_title', 'home_step1_desc', 'home_step2_title', 'home_step2_desc', 'home_step3_title', 'home_step3_desc', 'home_vendor_title', 'home_vendor_desc', 'home_vendor_image', 'home_influencer_title', 'home_influencer_desc', 'home_influencer_image'];
const cmsAboutKeys = ['about_mission', 'about_why'];
const cmsDocKeys = ['doc_vendor_intro', 'doc_influencer_intro', 'doc_general_intro'];
const cmsKeys = [...cmsHomeKeys, ...cmsAboutKeys, ...cmsDocKeys];
const ambassadorKeys = ['ambassador_badge_price', 'ambassador_sale_enabled', 'ambassador_subscription_duration', 'ambassador_commission_discount', 'ambassador_min_sales', 'ambassador_min_clicks', 'restricted_circle_multiplier'];
const parrainageKeys = ['referral_enabled', 'referral_bonus_amount', 'referral_transfer_threshold'];
const securiteKeys = ['admin_recovery_code'];

const fieldLabels = {
    withdrawal_fee_percent: 'Commission retrait (%)',
    min_withdrawal_amount: 'Montant minimum retrait (FCFA)',
    min_deposit_amount: 'Montant minimum d\u00e9p\u00f4t (FCFA)',
    ugc_studio_fee_percent: 'Commission UGC Studio (%)',
    min_cpc_price: 'CPC minimum (FCFA)',
    platform_commission_rate: 'Commission plateforme (%)',
    deposit_markup_percent: 'Marge depot (%)',
    campaign_commission_percent: 'Commission campagne (%)',
    fedapay_fee_percent: 'Frais FedaPay (%)',
    paydunya_fee_percent: 'Frais PayDunya (%)',
    tier_argent_threshold: 'Seuil palier Argent (FCFA)',
    tier_or_threshold: 'Seuil palier Or (FCFA)',
    tier_cost_bronze: 'Cout/participation Bronze (FCFA)',
    tier_cost_argent: 'Cout/participation Argent (FCFA)',
    tier_cost_or: 'Cout/participation Or (FCFA)',
    tier_followers_bronze_min: 'Abonnes Bronze min',
    tier_followers_bronze_max: 'Abonnes Bronze max',
    tier_followers_argent_min: 'Abonnes Argent min',
    tier_followers_argent_max: 'Abonnes Argent max',
    tier_followers_or_min: 'Abonnes Or min',
    tier_followers_or_max: 'Abonnes Or max',
    video_vendor_guide: 'Video guide Vendeur (YouTube)',
    video_influencer_guide: 'Video guide Createur (YouTube)',
    video_buyer_guide: 'Video guide Acheteur (YouTube)',
    video_welcome: 'Video de bienvenue (YouTube)',
    home_hero_title: 'Titre Hero page d\'accueil',
    home_hero_subtitle: 'Sous-titre Hero',
    home_step1_title: 'Etape 1 : Titre',
    home_step1_desc: 'Etape 1 : Description',
    home_step2_title: 'Etape 2 : Titre',
    home_step2_desc: 'Etape 2 : Description',
    home_step3_title: 'Etape 3 : Titre',
    home_step3_desc: 'Etape 3 : Description',
    home_vendor_title: 'Section Vendeur : Titre',
    home_vendor_desc: 'Section Vendeur : Description',
    home_influencer_title: 'Section Createur de Contenu : Titre',
    home_influencer_desc: 'Section Createur de Contenu : Description',
    home_hero_image: 'Image Hero (URL)',
    home_vendor_image: 'Image Section Vendeur (URL)',
    home_influencer_image: 'Image Section Createur (URL)',
    about_mission: 'Texte "Notre mission"',
    about_why: 'Texte "Pourquoi MANTOTA"',
    doc_vendor_intro: 'Intro documentation Vendeurs',
    doc_influencer_intro: 'Intro documentation Createurs de Contenu',
    doc_general_intro: 'Intro documentation General',
    company_name: 'Nom de l\'entreprise',
    contact_email: 'Email de contact',
    whatsapp_phone: 'Telephone WhatsApp',
    rccm: 'Numero RCCM',
    ifu: 'Numero IFU',
    physical_address: 'Adresse physique',
    social_facebook: 'Lien Facebook',
    social_instagram: 'Lien Instagram',
    social_tiktok: 'Lien TikTok',
    social_twitter: 'Lien X (Twitter)',
    welcome_popup_vendor: 'Message popup Vendeurs',
    welcome_popup_influencer: 'Message popup Createurs de Contenu',
    ambassador_badge_price: 'Prix abonnement mensuel Ambassadeur (FCFA)',
    ambassador_sale_enabled: 'Abonnement Ambassadeur active (0/1)',
    ambassador_subscription_duration: 'Duree abonnement (jours)',
    ambassador_commission_discount: 'Reduction commission campagne (%)',
    ambassador_min_sales: 'Ventes min. pour auto-promotion',
    ambassador_min_clicks: 'Clics valides min. pour auto-promotion',
    restricted_circle_multiplier: 'Multiplicateur Cercle Restreint',
    referral_enabled: 'Parrainage active (0/1)',
    referral_bonus_amount: 'Bonus parrainage (FCFA)',
    referral_transfer_threshold: 'Seuil transfert parrainage (FCFA)',
    admin_recovery_code: 'Code secret de recuperation IP',
};

const fieldDescriptions = {
    withdrawal_fee_percent: 'Pourcentage preleve sur chaque retrait createur de contenu.',
    min_withdrawal_amount: 'Montant minimum que les utilisateurs peuvent retirer (ex: 1000 FCFA).',
    ugc_studio_fee_percent: 'Pourcentage preleve sur les commandes MANTOTA Studios.',
    min_cpc_price: 'Cout minimum par clic pour les campagnes CPC.',
    platform_commission_rate: 'Pourcentage preleve par MANTOTA sur les retraits et les litiges UGC.',
    deposit_markup_percent: 'Marge MANTOTA prelevee sur chaque depot (en plus des frais gateway).',
    campaign_commission_percent: 'Pourcentage preleve par MANTOTA sur chaque lancement ou rechargement de campagne. Debite en plus du budget campagne.',
    fedapay_fee_percent: 'Valeur de secours si FedaPay n\'est pas configure dans le panel Gateways. Les frais reels sont lus depuis la table Gateways.',
    paydunya_fee_percent: 'Valeur de secours si PayDunya n\'est pas configure dans le panel Gateways. Les frais reels sont lus depuis la table Gateways.',
    tier_argent_threshold: 'Budget minimum (FCFA) pour qu\'une campagne soit classee Argent.',
    tier_or_threshold: 'Budget minimum (FCFA) pour qu\'une campagne soit classee Or.',
    tier_cost_bronze: 'Cout par participation pour les campagnes Bronze (FCFA).',
    tier_cost_argent: 'Cout par participation pour les campagnes Argent (FCFA).',
    tier_cost_or: 'Cout par participation pour les campagnes Or (FCFA).',
    tier_followers_bronze_min: 'Nombre minimum d\'abonnes pour le palier Bronze.',
    tier_followers_bronze_max: 'Nombre maximum d\'abonnes pour le palier Bronze.',
    tier_followers_argent_min: 'Nombre minimum d\'abonnes pour le palier Argent.',
    tier_followers_argent_max: 'Nombre maximum d\'abonnes pour le palier Argent.',
    tier_followers_or_min: 'Nombre minimum d\'abonnes pour le palier Or.',
    tier_followers_or_max: 'Nombre maximum d\'abonnes pour le palier Or.',
    video_vendor_guide: 'URL YouTube expliquant comment les vendeurs utilisent la plateforme.',
    video_influencer_guide: 'URL YouTube expliquant comment les createurs de contenu utilisent la plateforme.',
    video_buyer_guide: 'URL YouTube expliquant comment les acheteurs utilisent la plateforme.',
    video_welcome: 'URL YouTube de la video de bienvenue presentant MANTOTA.',
    home_hero_title: 'Titre principal affiche sur la page d\'accueil publique.',
    home_hero_subtitle: 'Texte sous le titre hero (description de la plateforme).',
    home_step1_title: 'Titre de la premiere etape (Comment ca marche).',
    home_step1_desc: 'Description de la premiere etape.',
    home_step2_title: 'Titre de la deuxieme etape.',
    home_step2_desc: 'Description de la deuxieme etape.',
    home_step3_title: 'Titre de la troisieme etape.',
    home_step3_desc: 'Description de la troisieme etape.',
    home_vendor_title: 'Titre de la section vendeur sur la page d\'accueil.',
    home_vendor_desc: 'Description de la section vendeur.',
    home_influencer_title: 'Titre de la section createur de contenu sur la page d\'accueil.',
    home_influencer_desc: 'Description de la section createur de contenu.',
    home_hero_image: 'URL de l\'image de fond de la section hero (accueil).',
    home_vendor_image: 'URL de l\'image illustrative de la section vendeur.',
    home_influencer_image: 'URL de l\'image illustrative de la section createur de contenu.',
    about_mission: 'Texte de la section "Notre mission" sur la page A propos.',
    about_why: 'Texte de la section "Pourquoi MANTOTA" sur la page A propos.',
    doc_vendor_intro: 'Texte d\'introduction affiche en haut de la doc Vendeurs.',
    doc_influencer_intro: 'Texte d\'introduction affiche en haut de la doc Createurs de Contenu.',
    doc_general_intro: 'Texte d\'introduction affiche en haut de la doc General',
    company_name: 'Nom affiche dans le footer et les documents legaux.',
    contact_email: 'Email visible par le public pour le support.',
    whatsapp_phone: 'Numero WhatsApp au format international (ex: +229 97 00 00 00).',
    rccm: 'Registre du Commerce et du Credit Mobilier.',
    ifu: 'Identifiant Fiscal Unique de l\'entreprise.',
    physical_address: 'Adresse du siege social.',
    social_facebook: 'URL complete de la page Facebook.',
    social_instagram: 'URL complete du profil Instagram.',
    social_tiktok: 'URL complete du profil TikTok.',
    social_twitter: 'URL complete du profil X (Twitter).',
    welcome_popup_vendor: 'Message affiche aux nouveaux vendeurs lors de leur premiere connexion.',
    welcome_popup_influencer: 'Message affiche aux nouveaux createurs de contenu lors de leur premiere connexion.',
    ambassador_badge_price: 'Montant mensuel que les utilisateurs paient pour l\'abonnement Ambassadeur.',
    ambassador_sale_enabled: 'Activez 1 pour permettre aux utilisateurs de s\'abonner au badge Ambassadeur. 0 = desactive.',
    ambassador_subscription_duration: 'Duree de chaque abonnement en jours (ex: 30 = 1 mois).',
    ambassador_commission_discount: 'Reduction en % sur la commission campagne pour les vendeurs Ambassadeurs (ex: 50 = paie 2.5% au lieu de 5%).',
    ambassador_min_sales: 'Nombre minimum de commandes completees pour qu\'un vendeur soit auto-promu Ambassadeur.',
    ambassador_min_clicks: 'Nombre minimum de clics valides pour qu\'un createur de contenu soit auto-promu Ambassadeur.',
    restricted_circle_multiplier: 'Multiplicateur applique au CPC pour les campagnes Cercle Restreint (ex: 1.5 = CPC x 1.5).',
    referral_enabled: 'Activez 1 pour permettre le systeme de parrainage. 0 = desactive.',
    referral_bonus_amount: 'Montant en FCFA credite au parrain lorsqu\'un filleul s\'inscrit.',
    referral_transfer_threshold: 'Montant minimum pour transferer le solde parrainage vers le portefeuille principal.',
};

/* ── Logo Upload ── */
const logoLightFile = ref(null);
const logoDarkFile = ref(null);
const logoWidth = ref(form.logo_width?.value || props.settings.logo_width?.value || '140');
const logoHeight = ref(form.logo_height?.value || props.settings.logo_height?.value || '40');
const logoLightPreview = ref(props.settings.site_logo_light?.value || '/images/logo-white.png');
const logoDarkPreview = ref(props.settings.site_logo_dark?.value || '/images/logo-dark.png');
const savingLogo = ref(false);

function onLogoSelected(e, type) {
    const file = e.target.files[0];
    if (!file) return;
    const preview = URL.createObjectURL(file);
    if (type === 'site_logo_light') {
        logoLightFile.value = file;
        logoLightPreview.value = preview;
    } else {
        logoDarkFile.value = file;
        logoDarkPreview.value = preview;
    }
}

function uploadLogo(type) {
    const file = type === 'site_logo_light' ? logoLightFile.value : logoDarkFile.value;
    if (!file) return;
    savingLogo.value = true;
    const formData = new FormData();
    formData.append('logo_type', type);
    formData.append('logo', file);
    formData.append('logo_width', logoWidth.value);
    formData.append('logo_height', logoHeight.value);
    router.post(route('admin.settings.logo'), formData, {
        preserveScroll: true,
        onFinish: () => { savingLogo.value = false; },
    });
}

function saveDimensions() {
    savingLogo.value = true;
    router.put(route('admin.settings.update'), {
        settings: {
            logo_width: { value: String(logoWidth.value), type: 'integer' },
            logo_height: { value: String(logoHeight.value), type: 'integer' },
        }
    }, {
        preserveScroll: true,
        onFinish: () => { savingLogo.value = false; },
    });
}
</script>

<template>
    <div class="space-y-6">

        <!-- Header -->
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">Parametres</h1>
            <p class="mt-1 text-sm text-slate-500">Configuration globale de la plateforme MANTOTA.</p>
        </div>

        <!-- Tabs -->
        <div class="flex gap-1 rounded-xl bg-slate-100 p-1">
            <button
                v-for="tab in tabs"
                :key="tab.key"
                @click="activeTab = tab.key"
                class="flex flex-1 items-center justify-center gap-2 rounded-lg px-4 py-2 text-sm font-medium transition"
                :class="activeTab === tab.key
                    ? 'bg-white text-slate-900 shadow-sm'
                    : 'text-slate-500 hover:text-slate-700'"
            >
                <svg v-if="tab.icon === 'banknotes'" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75M15 10.5a3 3 0 11-6 0 3 3 0 016 0zm3 0h.008v.008H18V10.5zm-12 0h.008v.008H6V10.5z" /></svg>
                <svg v-else-if="tab.icon === 'shield'" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" /></svg>
                <svg v-else-if="tab.icon === 'building'" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21" /></svg>
                <svg v-else-if="tab.icon === 'globe'" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9.004 9.004 0 008.716-6.747M12 21a9.004 9.004 0 01-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 017.843 4.582M12 3a8.997 8.997 0 00-7.843 4.582m15.686 0A11.953 11.953 0 0112 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0121 12c0 .778-.099 1.533-.284 2.253m0 0A17.919 17.919 0 0112 16.5c-3.162 0-6.133-.815-8.716-2.247m0 0A9.015 9.015 0 013 12c0-1.605.42-3.113 1.157-4.418" /></svg>
                <svg v-else-if="tab.icon === 'chat'" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M8.625 9.75a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H8.25m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H12m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0h-.375m-13.5 3.01c0 1.6 1.123 2.994 2.707 3.227 1.087.16 2.185.283 3.293.369V21l4.184-4.183a1.14 1.14 0 01.778-.332 48.294 48.294 0 005.83-.498c1.585-.233 2.708-1.626 2.708-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0012 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018z" /></svg>
                <svg v-else-if="tab.icon === 'layers'" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6.429 9.75L2.25 12l4.179 2.25m0-4.5l5.571 3 5.571-3m-11.142 0L2.25 7.5 12 2.25l9.75 5.25-4.179 2.25m0 0L12 12.75 6.429 9.75m11.142 0l4.179 2.25-9.75 5.25-9.75-5.25 4.179-2.25" /></svg>
                <svg v-else-if="tab.icon === 'play'" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M5.25 5.653c0-.856.917-1.398 1.667-.986l11.54 6.347a1.125 1.125 0 010 1.972l-11.54 6.347a1.125 1.125 0 01-1.667-.986V5.653z" /></svg>
                <svg v-else-if="tab.icon === 'photo'" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Zm10.5-11.25h.008v.008h-.008V8.25Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" /></svg>
                <svg v-else-if="tab.icon === 'star'" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M11.48 3.499a.562.562 0 011.04 0l2.125 5.111a.563.563 0 00.475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 00-.182.557l1.285 5.385a.562.562 0 01-.84.61l-4.725-2.885a.562.562 0 00-.586 0L6.982 20.54a.562.562 0 01-.84-.61l1.285-5.386a.562.562 0 00-.182-.557l-4.204-3.602a.562.562 0 01.321-.988l5.518-.442a.563.563 0 00.475-.345L11.48 3.5z" /></svg>
                <svg v-else-if="tab.icon === 'users'" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" /></svg>
                {{ tab.label }}
            </button>
        </div>

        <!-- Settings form -->
        <form @submit.prevent="save" class="space-y-6">

            <!-- Finances tab -->
            <div v-show="activeTab === 'finances'" class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-sm">
                <div class="border-b border-slate-100 px-6 py-4">
                    <div class="flex items-center gap-3">
                        <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-teal-50">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-teal-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75M15 10.5a3 3 0 11-6 0 3 3 0 016 0zm3 0h.008v.008H18V10.5zm-12 0h.008v.008H6V10.5z" /></svg>
                        </div>
                        <div>
                            <h2 class="text-base font-semibold text-slate-900">Taxes & Finances</h2>
                            <p class="text-xs text-slate-500">Commissions et seuils financiers de la plateforme.</p>
                        </div>
                    </div>
                </div>
                <div class="divide-y divide-slate-100">
                    <!-- Info gateway fees -->
                    <div class="px-6 py-4 bg-blue-50 border-b border-blue-100">
                        <div class="flex items-start gap-3">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0 text-blue-500 mt-0.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" />
                            </svg>
                            <div>
                                <p class="text-xs font-semibold text-blue-800">Frais de gateway en temps reel</p>
                                <p class="text-xs text-blue-700 mt-0.5">
                                    Les frais reels par gateway (FeexPay, FedaPay, CinetPay, Flutterwave, PayDunya) sont configures directement dans le
                                    <a href="/admin/gateways" class="font-semibold underline hover:text-blue-900">panel Gateways</a>.
                                    Les champs "Frais FedaPay/PayDunya" ci-dessous ne servent que de valeurs de secours.
                                </p>
                            </div>
                        </div>
                    </div>
                    <div v-for="key in financeKeys" :key="key" class="flex flex-col gap-1 px-6 py-5 sm:flex-row sm:items-center sm:justify-between sm:gap-6">
                        <div class="min-w-0 flex-1">
                            <label :for="'field-' + key" class="text-sm font-medium text-slate-900">{{ fieldLabels[key] || key }}</label>
                            <p class="text-xs text-slate-500">{{ fieldDescriptions[key] || '' }}</p>
                        </div>
                        <div class="w-full sm:w-48">
                            <input v-if="form[key]" :id="'field-' + key" v-model="form[key].value" type="text" class="w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm text-slate-900 shadow-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500" />
                        </div>
                    </div>
                </div>
            </div>

            <!-- Paliers Campagne tab -->
            <div v-show="activeTab === 'tiers'" class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-sm">
                <div class="border-b border-slate-100 px-6 py-4">
                    <div class="flex items-center gap-3">
                        <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-orange-50">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-orange-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6.429 9.75L2.25 12l4.179 2.25m0-4.5l5.571 3 5.571-3m-11.142 0L2.25 7.5 12 2.25l9.75 5.25-4.179 2.25m0 0L12 12.75 6.429 9.75m11.142 0l4.179 2.25-9.75 5.25-9.75-5.25 4.179-2.25" /></svg>
                        </div>
                        <div>
                            <h2 class="text-base font-semibold text-slate-900">Paliers de campagne</h2>
                            <p class="text-xs text-slate-500">Seuils de budget, couts par participation et plages d'abonnes pour Bronze / Argent / Or.</p>
                        </div>
                    </div>
                </div>
                <div class="divide-y divide-slate-100">
                    <div v-for="key in tierKeys" :key="key" class="flex flex-col gap-1 px-6 py-5 sm:flex-row sm:items-center sm:justify-between sm:gap-6">
                        <div class="min-w-0 flex-1">
                            <label :for="'field-' + key" class="text-sm font-medium text-slate-900">{{ fieldLabels[key] || key }}</label>
                            <p class="text-xs text-slate-500">{{ fieldDescriptions[key] || '' }}</p>
                        </div>
                        <div class="w-full sm:w-48">
                            <input v-if="form[key]" :id="'field-' + key" v-model="form[key].value" type="text" class="w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm text-slate-900 shadow-sm focus:border-orange-500 focus:outline-none focus:ring-1 focus:ring-orange-500" />
                        </div>
                    </div>
                </div>
            </div>

            <!-- Videos YouTube tab -->
            <div v-show="activeTab === 'videos'" class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-sm">
                <div class="border-b border-slate-100 px-6 py-4">
                    <div class="flex items-center gap-3">
                        <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-red-50">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-red-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M5.25 5.653c0-.856.917-1.398 1.667-.986l11.54 6.347a1.125 1.125 0 010 1.972l-11.54 6.347a1.125 1.125 0 01-1.667-.986V5.653z" /></svg>
                        </div>
                        <div>
                            <h2 class="text-base font-semibold text-slate-900">Videos YouTube</h2>
                            <p class="text-xs text-slate-500">Liens YouTube pour expliquer le fonctionnement de la plateforme a chaque role. Collez l'URL complete (ex: https://www.youtube.com/watch?v=...).</p>
                        </div>
                    </div>
                </div>
                <div class="divide-y divide-slate-100">
                    <div v-for="key in videoKeys" :key="key" class="px-6 py-5 space-y-2">
                        <label :for="'field-' + key" class="block text-sm font-medium text-slate-900">{{ fieldLabels[key] || key }}</label>
                        <p class="text-xs text-slate-500">{{ fieldDescriptions[key] || '' }}</p>
                        <input v-if="form[key]" :id="'field-' + key" v-model="form[key].value" type="url" class="w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm text-slate-900 shadow-sm focus:border-red-500 focus:outline-none focus:ring-1 focus:ring-red-500" placeholder="https://www.youtube.com/watch?v=..." />
                        <div v-if="form[key]?.value" class="mt-3 aspect-video max-w-lg overflow-hidden rounded-xl border border-slate-200">
                            <iframe :src="'https://www.youtube.com/embed/' + form[key].value.replace(/.*(?:youtu\.be\/|v=)/, '').replace(/[&?].*/, '')" class="h-full w-full" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Entreprise tab -->
            <div v-show="activeTab === 'moderation'" class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-sm">
                <div class="border-b border-slate-100 px-6 py-4">
                    <div class="flex items-center gap-3">
                        <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-red-50">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-red-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" /></svg>
                        </div>
                        <div>
                            <h2 class="text-base font-semibold text-slate-900">Robot Douanier</h2>
                            <p class="text-xs text-slate-500">Mots-cles interdits pour la moderation automatique des campagnes.</p>
                        </div>
                    </div>
                </div>
                <div class="px-6 py-5">
                    <label for="banned-keywords" class="block text-sm font-medium text-slate-900 mb-1">Mots-cles interdits</label>
                    <p class="text-xs text-slate-500 mb-3">Un mot par ligne. Toute campagne contenant un de ces mots sera automatiquement rejetee.</p>
                    <textarea
                        id="banned-keywords"
                        v-model="bannedKeywordsText"
                        rows="8"
                        class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm focus:border-red-500 focus:outline-none focus:ring-1 focus:ring-red-500"
                        placeholder="arnaque&#10;scam&#10;faux&#10;contrefacon"
                    ></textarea>
                </div>
            </div>

            <!-- Entreprise tab -->
            <div v-show="activeTab === 'entreprise'" class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-sm">
                <div class="border-b border-slate-100 px-6 py-4">
                    <div class="flex items-center gap-3">
                        <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-slate-100">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-slate-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21" /></svg>
                        </div>
                        <div>
                            <h2 class="text-base font-semibold text-slate-900">Informations de l'entreprise</h2>
                            <p class="text-xs text-slate-500">Coordonnees, immatriculation et reseaux sociaux.</p>
                        </div>
                    </div>
                </div>
                <div class="divide-y divide-slate-100">
                    <div v-for="key in entrepriseKeys" :key="key" class="flex flex-col gap-1 px-6 py-5 sm:flex-row sm:items-center sm:justify-between sm:gap-6">
                        <div class="min-w-0 flex-1">
                            <label :for="'field-' + key" class="text-sm font-medium text-slate-900">{{ fieldLabels[key] || key }}</label>
                            <p class="text-xs text-slate-500">{{ fieldDescriptions[key] || '' }}</p>
                        </div>
                        <div class="w-full sm:w-80">
                            <input v-if="form[key]" :id="'field-' + key" v-model="form[key].value" type="text" class="w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm text-slate-900 shadow-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500" :placeholder="fieldLabels[key]" />
                        </div>
                    </div>
                </div>
            </div>

            <!-- CMS tab -->
            <div v-show="activeTab === 'cms'" class="space-y-6">
                <!-- Page d'accueil -->
                <div class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-sm">
                    <div class="border-b border-slate-100 px-6 py-4">
                        <div class="flex items-center gap-3">
                            <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-teal-50">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-teal-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m2.25 12 8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" /></svg>
                            </div>
                            <div>
                                <h2 class="text-base font-semibold text-slate-900">Page d'accueil</h2>
                                <p class="text-xs text-slate-500">Textes editables de la page d'accueil publique (laissez vide pour le texte par defaut).</p>
                            </div>
                        </div>
                    </div>
                    <div class="divide-y divide-slate-100">
                        <div v-for="key in cmsHomeKeys" :key="key" class="px-6 py-5 space-y-2">
                            <label :for="'field-' + key" class="block text-sm font-medium text-slate-900">{{ fieldLabels[key] || key }}</label>
                            <p class="text-xs text-slate-500">{{ fieldDescriptions[key] || '' }}</p>
                            <!-- Image fields with preview -->
                            <div v-if="key.includes('_image')" class="space-y-2">
                                <input v-if="form[key]" :id="'field-' + key" v-model="form[key].value" type="text" class="w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm text-slate-900 shadow-sm focus:border-purple-500 focus:outline-none focus:ring-1 focus:ring-purple-500" placeholder="https://..." />
                                <div v-if="form[key]?.value" class="relative rounded-lg overflow-hidden border border-slate-200">
                                    <img :src="form[key].value" alt="Apercu" class="w-full max-h-40 object-cover" @error="form[key].value = ''" />
                                    <button type="button" @click="form[key].value = ''" class="absolute top-2 right-2 rounded-full bg-red-600 p-1 text-white hover:bg-red-700">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                                    </button>
                                </div>
                                <p v-else class="text-xs text-slate-400 italic">Aucune image definie</p>
                            </div>
                            <!-- Text fields -->
                            <template v-else>
                                <textarea v-if="form[key] && (key.includes('desc') || key.includes('subtitle'))" :id="'field-' + key" v-model="form[key].value" rows="3" class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm focus:border-purple-500 focus:outline-none focus:ring-1 focus:ring-purple-500" :placeholder="fieldLabels[key]" />
                                <input v-else-if="form[key]" :id="'field-' + key" v-model="form[key].value" type="text" class="w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm text-slate-900 shadow-sm focus:border-purple-500 focus:outline-none focus:ring-1 focus:ring-purple-500" :placeholder="fieldLabels[key]" />
                            </template>
                        </div>
                    </div>
                </div>

                <!-- Page A propos -->
                <div class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-sm">
                    <div class="border-b border-slate-100 px-6 py-4">
                        <div class="flex items-center gap-3">
                            <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-purple-50">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-purple-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198l.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719m12 0a5.971 5.971 0 00-.941-3.197m0 0A5.995 5.995 0 0012 12.75a5.995 5.995 0 00-5.058 2.772m0 0a3 3 0 00-4.681 2.72 8.986 8.986 0 003.74.477m.94-3.197a5.971 5.971 0 00-.94 3.197M15 6.75a3 3 0 11-6 0 3 3 0 016 0zm6 3a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0zm-13.5 0a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z" /></svg>
                            </div>
                            <div>
                                <h2 class="text-base font-semibold text-slate-900">Page A propos</h2>
                                <p class="text-xs text-slate-500">Textes de la page "A propos".</p>
                            </div>
                        </div>
                    </div>
                    <div class="divide-y divide-slate-100">
                        <div v-for="key in cmsAboutKeys" :key="key" class="px-6 py-5 space-y-2">
                            <label :for="'field-' + key" class="block text-sm font-medium text-slate-900">{{ fieldLabels[key] || key }}</label>
                            <p class="text-xs text-slate-500">{{ fieldDescriptions[key] || '' }}</p>
                            <textarea :id="'field-' + key" v-model="form[key].value" rows="4" class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm focus:border-purple-500 focus:outline-none focus:ring-1 focus:ring-purple-500" :placeholder="fieldLabels[key]" />
                        </div>
                    </div>
                </div>

                <!-- Page Documentation -->
                <div class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-sm">
                    <div class="border-b border-slate-100 px-6 py-4">
                        <div class="flex items-center gap-3">
                            <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-amber-50">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-amber-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25" /></svg>
                            </div>
                            <div>
                                <h2 class="text-base font-semibold text-slate-900">Page Documentation</h2>
                                <p class="text-xs text-slate-500">Textes d'introduction affiches en haut de chaque section de la documentation.</p>
                            </div>
                        </div>
                    </div>
                    <div class="divide-y divide-slate-100">
                        <div v-for="key in cmsDocKeys" :key="key" class="px-6 py-5 space-y-2">
                            <label :for="'field-' + key" class="block text-sm font-medium text-slate-900">{{ fieldLabels[key] || key }}</label>
                            <p class="text-xs text-slate-500">{{ fieldDescriptions[key] || '' }}</p>
                            <textarea :id="'field-' + key" v-model="form[key].value" rows="3" class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm focus:border-purple-500 focus:outline-none focus:ring-1 focus:ring-purple-500" :placeholder="fieldLabels[key]" />
                        </div>
                    </div>
                </div>
            </div>

            <!-- Logo tab -->
            <div v-show="activeTab === 'logo'" class="space-y-6">
                <div class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-sm">
                    <div class="border-b border-slate-100 px-6 py-4">
                        <div class="flex items-center gap-3">
                            <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-indigo-50">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Zm10.5-11.25h.008v.008h-.008V8.25Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" /></svg>
                            </div>
                            <div>
                                <h2 class="text-base font-semibold text-slate-900">Gestion du Logo</h2>
                                <p class="text-xs text-slate-500">Uploadez vos logos et configurez les dimensions d'affichage.</p>
                            </div>
                        </div>
                    </div>
                    <div class="divide-y divide-slate-100">
                        <!-- Logo Clair (fond sombre) -->
                        <div class="px-6 py-5 space-y-4">
                            <div>
                                <h3 class="text-sm font-medium text-slate-900">Logo texte blanc (fond sombre)</h3>
                                <p class="text-xs text-slate-500">Utilise dans les barres laterales, navbars sur fond fonce et les pages d'authentification.</p>
                            </div>
                            <div class="flex items-center gap-6">
                                <div class="flex h-20 w-48 items-center justify-center rounded-xl bg-slate-900 p-3">
                                    <img :src="logoLightPreview" alt="Logo clair" class="max-h-full max-w-full object-contain" />
                                </div>
                                <div class="space-y-2">
                                    <input type="file" accept="image/png,image/jpeg,image/svg+xml,image/webp" @change="onLogoSelected($event, 'site_logo_light')" class="block w-full text-sm text-slate-500 file:mr-4 file:rounded-lg file:border-0 file:bg-indigo-50 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-indigo-700 hover:file:bg-indigo-100" />
                                    <button v-if="logoLightFile" type="button" @click="uploadLogo('site_logo_light')" :disabled="savingLogo" class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-xs font-semibold text-white transition hover:bg-indigo-700 disabled:opacity-50">
                                        <svg v-if="savingLogo" class="h-3.5 w-3.5 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" /><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" /></svg>
                                        Enregistrer ce logo
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Logo Sombre (fond clair) -->
                        <div class="px-6 py-5 space-y-4">
                            <div>
                                <h3 class="text-sm font-medium text-slate-900">Logo texte noir (fond clair)</h3>
                                <p class="text-xs text-slate-500">Utilise sur les pages avec fond blanc ou clair si besoin.</p>
                            </div>
                            <div class="flex items-center gap-6">
                                <div class="flex h-20 w-48 items-center justify-center rounded-xl border border-slate-200 bg-white p-3">
                                    <img :src="logoDarkPreview" alt="Logo sombre" class="max-h-full max-w-full object-contain" />
                                </div>
                                <div class="space-y-2">
                                    <input type="file" accept="image/png,image/jpeg,image/svg+xml,image/webp" @change="onLogoSelected($event, 'site_logo_dark')" class="block w-full text-sm text-slate-500 file:mr-4 file:rounded-lg file:border-0 file:bg-indigo-50 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-indigo-700 hover:file:bg-indigo-100" />
                                    <button v-if="logoDarkFile" type="button" @click="uploadLogo('site_logo_dark')" :disabled="savingLogo" class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-xs font-semibold text-white transition hover:bg-indigo-700 disabled:opacity-50">
                                        <svg v-if="savingLogo" class="h-3.5 w-3.5 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" /><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" /></svg>
                                        Enregistrer ce logo
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Dimensions -->
                        <div class="px-6 py-5 space-y-4">
                            <div>
                                <h3 class="text-sm font-medium text-slate-900">Dimensions d'affichage</h3>
                                <p class="text-xs text-slate-500">Largeur et hauteur maximales du logo en pixels (affecte tous les emplacements).</p>
                            </div>
                            <div class="flex items-end gap-4">
                                <div>
                                    <label class="block text-xs font-medium text-slate-600 mb-1">Largeur max (px)</label>
                                    <input v-model="logoWidth" type="number" min="20" max="500" class="w-28 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm text-slate-900 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500" />
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-slate-600 mb-1">Hauteur max (px)</label>
                                    <input v-model="logoHeight" type="number" min="20" max="200" class="w-28 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm text-slate-900 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500" />
                                </div>
                                <button type="button" @click="saveDimensions" :disabled="savingLogo" class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2.5 text-xs font-semibold text-white transition hover:bg-indigo-700 disabled:opacity-50">
                                    Enregistrer les dimensions
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Popups tab -->
            <div v-show="activeTab === 'popups'" class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-sm">
                <div class="border-b border-slate-100 px-6 py-4">
                    <div class="flex items-center gap-3">
                        <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-amber-50">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-amber-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M8.625 9.75a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H8.25m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H12m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0h-.375m-13.5 3.01c0 1.6 1.123 2.994 2.707 3.227 1.087.16 2.185.283 3.293.369V21l4.184-4.183a1.14 1.14 0 01.778-.332 48.294 48.294 0 005.83-.498c1.585-.233 2.708-1.626 2.708-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0012 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018z" /></svg>
                        </div>
                        <div>
                            <h2 class="text-base font-semibold text-slate-900">Popups de bienvenue</h2>
                            <p class="text-xs text-slate-500">Messages affiches aux nouveaux utilisateurs lors de leur premiere connexion.</p>
                        </div>
                    </div>
                </div>
                <div class="divide-y divide-slate-100">
                    <div class="px-6 py-5 space-y-2">
                        <label for="popup-vendor" class="block text-sm font-medium text-slate-900">{{ fieldLabels['welcome_popup_vendor'] }}</label>
                        <p class="text-xs text-slate-500">{{ fieldDescriptions['welcome_popup_vendor'] }}</p>
                        <textarea
                            id="popup-vendor"
                            v-model="form.welcome_popup_vendor.value"
                            rows="5"
                            class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm focus:border-amber-500 focus:outline-none focus:ring-1 focus:ring-amber-500"
                            placeholder="Message pour les vendeurs..."
                        ></textarea>
                    </div>
                    <div class="px-6 py-5 space-y-2">
                        <label for="popup-influencer" class="block text-sm font-medium text-slate-900">{{ fieldLabels['welcome_popup_influencer'] }}</label>
                        <p class="text-xs text-slate-500">{{ fieldDescriptions['welcome_popup_influencer'] }}</p>
                        <textarea
                            id="popup-influencer"
                            v-model="form.welcome_popup_influencer.value"
                            rows="5"
                            class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm focus:border-amber-500 focus:outline-none focus:ring-1 focus:ring-amber-500"
                            placeholder="Message pour les createurs de contenu..."
                        ></textarea>
                    </div>
                </div>
            </div>

            <!-- Ambassadeurs tab -->
            <div v-show="activeTab === 'ambassadeur'" class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-sm">
                <div class="border-b border-slate-100 px-6 py-4">
                    <div class="flex items-center gap-3">
                        <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-cyan-50">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-cyan-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M11.48 3.499a.562.562 0 011.04 0l2.125 5.111a.563.563 0 00.475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 00-.182.557l1.285 5.385a.562.562 0 01-.84.61l-4.725-2.885a.562.562 0 00-.586 0L6.982 20.54a.562.562 0 01-.84-.61l1.285-5.386a.562.562 0 00-.182-.557l-4.204-3.602a.562.562 0 01.321-.988l5.518-.442a.563.563 0 00.475-.345L11.48 3.5z" /></svg>
                        </div>
                        <div>
                            <h2 class="text-base font-semibold text-slate-900">Programme Ambassadeur</h2>
                            <p class="text-xs text-slate-500">Configuration de l'abonnement mensuel Ambassadeur, commissions et seuils d'auto-promotion.</p>
                        </div>
                    </div>
                </div>
                <div class="divide-y divide-slate-100">
                    <div v-for="key in ambassadorKeys" :key="key" class="flex flex-col gap-1 px-6 py-5 sm:flex-row sm:items-center sm:justify-between sm:gap-6">
                        <div class="min-w-0 flex-1">
                            <label :for="'field-' + key" class="text-sm font-medium text-slate-900">{{ fieldLabels[key] || key }}</label>
                            <p class="text-xs text-slate-500">{{ fieldDescriptions[key] || '' }}</p>
                        </div>
                        <div class="w-full sm:w-48">
                            <input v-if="form[key]" :id="'field-' + key" v-model="form[key].value" type="text" class="w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm text-slate-900 shadow-sm focus:border-cyan-500 focus:outline-none focus:ring-1 focus:ring-cyan-500" />
                        </div>
                    </div>
                </div>
            </div>

            <!-- Parrainage tab -->
            <div v-show="activeTab === 'parrainage'" class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-sm">
                <div class="border-b border-slate-100 px-6 py-4">
                    <div class="flex items-center gap-3">
                        <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-indigo-50">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" /></svg>
                        </div>
                        <div>
                            <h2 class="text-base font-semibold text-slate-900">Parrainage</h2>
                            <p class="text-xs text-slate-500">Configuration du systeme de parrainage et bonus filleuls.</p>
                        </div>
                    </div>
                </div>
                <div class="divide-y divide-slate-100">
                    <div v-for="key in parrainageKeys" :key="key" class="flex flex-col gap-1 px-6 py-5 sm:flex-row sm:items-center sm:justify-between sm:gap-6">
                        <div class="min-w-0 flex-1">
                            <label :for="'field-' + key" class="text-sm font-medium text-slate-900">{{ fieldLabels[key] || key }}</label>
                            <p class="text-xs text-slate-500">{{ fieldDescriptions[key] || '' }}</p>
                        </div>
                        <div class="w-full sm:w-48">
                            <input v-if="form[key]" :id="'field-' + key" v-model="form[key].value" type="text" class="w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm text-slate-900 shadow-sm focus:border-cyan-500 focus:outline-none focus:ring-1 focus:ring-cyan-500" />
                        </div>
                    </div>
                </div>
            </div>

            <!-- Securite tab -->
            <div v-show="activeTab === 'securite'" class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-sm">
                <div class="border-b border-slate-100 px-6 py-4">
                    <div class="flex items-center gap-3">
                        <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-red-50">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-red-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" /></svg>
                        </div>
                        <div>
                            <h2 class="text-base font-semibold text-slate-900">Securite</h2>
                            <p class="text-xs text-slate-500">Code secret de recuperation pour restaurer l'acces admin si votre IP change.</p>
                        </div>
                    </div>
                </div>
                <div class="divide-y divide-slate-100">
                    <div v-for="key in securiteKeys" :key="key" class="flex flex-col gap-1 px-6 py-5 sm:flex-row sm:items-center sm:justify-between sm:gap-6">
                        <div class="min-w-0 flex-1">
                            <label :for="'field-' + key" class="text-sm font-medium text-slate-900">{{ fieldLabels[key] || key }}</label>
                            <p class="text-xs text-slate-500">Definissez un code secret. Si votre IP change, allez sur <code class="bg-slate-100 px-1 py-0.5 rounded text-xs">/admin/recover-ip</code> et entrez ce code pour restaurer votre acces.</p>
                        </div>
                        <div class="w-full sm:w-48">
                            <input v-if="form[key]" :id="'field-' + key" v-model="form[key].value" type="text" class="w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm text-slate-900 shadow-sm focus:border-cyan-500 focus:outline-none focus:ring-1 focus:ring-cyan-500" placeholder="Ex: MonCodeSecret123" />
                        </div>
                    </div>
                </div>
                <div class="px-6 py-4 bg-amber-50 border-t border-amber-100">
                    <p class="text-xs text-amber-700"><strong>Comment ca marche :</strong> Quand votre IP publique change (ex: redemarrage WiFi), vous perdez l'acces admin. Allez sur <code class="bg-amber-100 px-1 py-0.5 rounded">/admin/recover-ip</code>, entrez votre code secret, et votre nouvelle IP sera automatiquement ajoutee a la whitelist.</p>
                </div>
            </div>

            <!-- Save button -->
            <div class="flex justify-end">
                <button type="submit" :disabled="saving" class="inline-flex items-center gap-2 rounded-xl bg-teal-600 px-6 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-teal-700 focus:outline-none focus:ring-2 focus:ring-teal-500 focus:ring-offset-2 disabled:opacity-50">
                    <svg v-if="saving" class="h-4 w-4 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" /><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" /></svg>
                    <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.325.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.241-.438.613-.43.992a7.723 7.723 0 010 .255c-.008.378.137.75.43.991l1.004.827c.424.35.534.955.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.47 6.47 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.281c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.019-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.991a6.932 6.932 0 010-.255c.007-.38-.138-.751-.43-.992l-1.004-.827a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.086.22-.128.332-.183.582-.495.644-.869l.214-1.28z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                    Enregistrer
                </button>
            </div>
        </form>
    </div>
</template>
