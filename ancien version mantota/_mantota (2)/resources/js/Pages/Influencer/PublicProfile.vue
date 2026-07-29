<template>
  <div class="bg-gray-50 min-h-screen">
    <!-- Hero Profil avec gradient -->
    <div class="bg-gradient-to-r from-purple-600 to-pink-600 py-16">
      <div class="max-w-6xl mx-auto px-4">
        <div class="flex flex-col md:flex-row items-start md:items-center gap-8">
          <!-- Avatar -->
          <div class="flex-shrink-0">
            <img 
              :src="influencer.profile_photo || '/images/avatar-default.png'" 
              :alt="influencer.name"
              class="w-32 h-32 rounded-full border-4 border-white shadow-lg object-cover"
            >
          </div>

          <!-- Info Profil -->
          <div class="flex-1 text-white">
            <h1 class="text-4xl font-bold mb-2">{{ influencer.name }}</h1>
            <p class="text-xl opacity-90 mb-4">@{{ influencer.username }}</p>

            <!-- Badges Tier + VIP -->
            <div class="flex flex-wrap gap-3 items-center">
              <span 
                v-if="stats.tier === 'bronze'"
                class="inline-flex items-center gap-2 px-4 py-2 bg-orange-500 rounded-full font-semibold"
              >
                <span class="text-2xl">🥉</span> Bronze
              </span>
              <span 
                v-else-if="stats.tier === 'argent'"
                class="inline-flex items-center gap-2 px-4 py-2 bg-gray-300 text-gray-900 rounded-full font-semibold"
              >
                <span class="text-2xl">🥈</span> Argent
              </span>
              <span 
                v-else-if="stats.tier === 'or'"
                class="inline-flex items-center gap-2 px-4 py-2 bg-yellow-400 text-yellow-900 rounded-full font-semibold"
              >
                <span class="text-2xl">🥇</span> Or
              </span>

              <span 
                v-if="stats.is_vip"
                class="inline-flex items-center gap-2 px-4 py-2 bg-red-500 rounded-full font-semibold"
              >
                <span class="text-2xl">⭐</span> VIP
              </span>
            </div>

            <!-- Bio -->
            <p v-if="influencer.bio" class="mt-4 text-white/90 max-w-lg">
              {{ influencer.bio }}
            </p>
          </div>
        </div>
      </div>
    </div>

    <!-- Statistiques principales -->
    <div class="max-w-6xl mx-auto px-4 py-12">
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4 mb-12">
        <!-- Total Followers -->
        <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
          <div class="text-center">
            <p class="text-3xl sm:text-4xl font-bold text-purple-600">
              {{ formatNumber(stats.total_followers) }}
            </p>
            <p class="text-sm text-gray-600 mt-2">Abonnés Total</p>
          </div>
        </div>

        <!-- Commandes Complétées -->
        <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
          <div class="text-center">
            <p class="text-3xl sm:text-4xl font-bold text-green-600">
              {{ stats.completed_orders }}
            </p>
            <p class="text-sm text-gray-600 mt-2">Commandes</p>
          </div>
        </div>

        <!-- Services Créés -->
        <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
          <div class="text-center">
            <p class="text-3xl sm:text-4xl font-bold text-blue-600">
              {{ stats.services_created }}
            </p>
            <p class="text-sm text-gray-600 mt-2">Services UGC</p>
          </div>
        </div>

        <!-- Trust Score -->
        <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
          <div class="text-center">
            <p class="text-3xl sm:text-4xl font-bold text-yellow-600">
              {{ (stats.trust_score * 100).toFixed(0) }}%
            </p>
            <p class="text-sm text-gray-600 mt-2">Trust Score</p>
          </div>
        </div>

        <!-- Badge VIP/Tier -->
        <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
          <div class="text-center">
            <p class="text-lg font-bold text-gray-700 mb-2">
              Vérification
            </p>
            <span v-if="influencer.kyc_status === 'approved'" class="inline-block px-3 py-1 bg-green-100 text-green-800 text-sm font-semibold rounded-full">
              ✓ Vérifiée
            </span>
            <span v-else class="inline-block px-3 py-1 bg-gray-100 text-gray-800 text-sm font-semibold rounded-full">
              En attente
            </span>
          </div>
        </div>
      </div>

      <!-- Réseaux Sociaux -->
      <div v-if="Object.keys(socials).length > 0" class="bg-white p-8 rounded-lg shadow-sm border border-gray-200 mb-12">
        <h2 class="text-2xl font-bold mb-6 text-gray-900">Présence Sociale</h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-5 gap-4">
          <a 
            v-for="(social, platform) in socials"
            :key="platform"
            :href="social.url"
            target="_blank"
            rel="noopener noreferrer"
            class="group p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors text-center border border-gray-200"
          >
            <p class="font-semibold text-gray-900 capitalize mb-2 group-hover:text-purple-600">
              {{ platform }}
            </p>
            <p class="text-2xl font-bold text-purple-600 group-hover:text-pink-600">
              {{ formatNumber(social.followers) }}
            </p>
            <p class="text-xs text-gray-600 mt-2">Abonnés</p>
          </a>
        </div>
      </div>

      <!-- Campagnes Passées -->
      <div class="mb-12">
        <h2 class="text-2xl font-bold mb-6 text-gray-900">Campagnes Passées</h2>
        
        <div v-if="campaigns.data && campaigns.data.length > 0" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
          <div 
            v-for="campaign in campaigns.data"
            :key="campaign.id"
            class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden hover:shadow-md transition-shadow"
          >
            <!-- Campaign Image Placeholder -->
            <div class="aspect-square bg-gray-200 flex items-center justify-center">
              <span class="text-gray-400 text-4xl">📷</span>
            </div>

            <!-- Campaign Info -->
            <div class="p-4">
              <h3 class="font-semibold text-gray-900 mb-1 line-clamp-2">
                {{ campaign.product?.name || 'Sans titre' }}
              </h3>
              <p class="text-sm text-gray-600 mb-3">
                {{ formatDate(campaign.created_at) }}
              </p>
              <a 
                href="#"
                class="text-sm font-medium text-purple-600 hover:text-purple-700"
              >
                Voir plus →
              </a>
            </div>
          </div>
        </div>

        <div v-else class="bg-white p-12 rounded-lg shadow-sm border border-gray-200 text-center">
          <p class="text-gray-600 text-lg">Aucune campagne complétée pour le moment</p>
        </div>

        <!-- Pagination -->
        <div v-if="campaigns.links && campaigns.links.length > 3" class="mt-8 flex justify-center gap-2">
          <a 
            v-for="link in campaigns.links"
            :key="link.label"
            :href="link.url || '#'"
            :disabled="!link.url"
            :class="[
              'px-4 py-2 rounded-lg transition-colors',
              link.active 
                ? 'bg-purple-600 text-white' 
                : 'bg-white border border-gray-300 text-gray-700 hover:bg-gray-50'
            ]"
            v-html="link.label"
          />
        </div>
      </div>

      <!-- CTA Pour vendeurs -->
      <div class="bg-gradient-to-r from-purple-600 to-pink-600 rounded-lg p-8 text-white text-center mb-12">
        <h3 class="text-2xl font-bold mb-4">Collaborer avec ce createur de contenu ?</h3>
        <p class="mb-6 text-white/90 max-w-2xl mx-auto">
          Engagez {{ influencer.name }} pour promouvoir vos produits et atteindre ses {{ formatNumber(stats.total_followers) }} abonnés.
        </p>
        <a 
          href="/"
          class="inline-block px-6 py-3 bg-white text-purple-600 font-semibold rounded-lg hover:bg-gray-100 transition-colors"
        >
          Devenir Vendeur
        </a>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  influencer: Object,
  stats: Object,
  socials: Object,
  campaigns: Object,
});

const formatNumber = (num) => {
  if (!num) return '0';
  return new Intl.NumberFormat('fr-FR').format(num);
};

const formatDate = (date) => {
  if (!date) return '';
  return new Intl.DateTimeFormat('fr-FR', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
  }).format(new Date(date));
};
</script>
