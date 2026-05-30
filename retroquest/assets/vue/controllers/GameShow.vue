<template>
  <div class="game-show-container py-2">
    <div class="mb-4">
      <a :href="getRoute('app_collector_guild_catalog')" class="btn btn-outline-secondary btn-sm d-inline-flex align-items-center gap-2 rounded-3 px-3">
        <i class="bi bi-arrow-left"></i>
        <span>Retour au catalogue</span>
      </a>
    </div>

    <div class="card shadow-sm border-0 rounded-4 overflow-hidden bg-white p-4">
      <div class="row g-4">
        <!-- Main Game Info (Left) -->
        <div class="col-12 col-lg-8">
          <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
            <span class="badge bg-primary-subtle text-primary rounded-pill px-3 py-2 fw-semibold">{{ game.console }}</span>
            <span class="badge bg-secondary-subtle text-secondary rounded-pill px-3 py-2 fw-semibold">Sortie : {{ game.releaseYear }}</span>
          </div>

          <h1 class="display-5 fw-bold text-dark mb-4">{{ game.title }}</h1>

          <div class="description-section">
            <h5 class="fw-bold text-dark mb-3">Description</h5>
            <div v-if="description" class="text-secondary lh-base game-description" style="white-space: pre-line;">
              {{ description }}
            </div>
            <div v-else class="text-muted fst-italic">
              Aucune description disponible pour ce jeu.
            </div>
          </div>
        </div>

        <!-- Sidebar / Statistics (Right) -->
        <div class="col-12 col-lg-4">
          <div class="p-3 bg-light rounded-4 border border-light-subtle h-100">
            <h5 class="fw-bold text-dark mb-4">Statistiques de la Guilde</h5>

            <!-- Ownership Count -->
            <div class="stat-card mb-4 bg-white p-3 rounded-3 border border-light">
              <div class="d-flex align-items-center gap-3">
                <div class="icon-wrapper bg-primary-subtle text-primary rounded-3 p-2 d-flex align-items-center justify-content-center">
                  <i class="bi bi-controller fs-4"></i>
                </div>
                <div>
                  <div class="text-muted small">Dans les collections</div>
                  <div class="fw-bold text-dark fs-5">
                    {{ collectionCount }} {{ collectionCount > 1 ? 'collectionneurs' : 'collectionneur' }}
                  </div>
                </div>
              </div>
            </div>

            <!-- Average Price -->
            <div class="stat-card bg-white p-3 rounded-3 border border-light">
              <div class="d-flex align-items-center gap-3 mb-3">
                <div class="icon-wrapper bg-success-subtle text-success rounded-3 p-2 d-flex align-items-center justify-content-center">
                  <i class="bi bi-tags fs-4"></i>
                </div>
                <div>
                  <div class="text-muted small">Prix moyen estimé</div>
                </div>
              </div>

              <div class="d-flex flex-column gap-2 mt-2" v-if="averagePrices && Object.keys(averagePrices).length > 0">
                <div 
                  v-for="(price, cur) in averagePrices" 
                  :key="cur" 
                  class="price-badge d-flex justify-content-between align-items-center rounded-2 px-3 py-2 fw-semibold"
                  :class="cur.toLowerCase()"
                >
                  <span class="text-secondary">{{ cur }}</span>
                  <span class="fs-6 text-dark">{{ formatPrice(price) }} {{ formatCurrency(cur) }}</span>
                </div>
              </div>
              <div class="text-muted fst-italic px-1" v-else>
                Aucune estimation de prix disponible.
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Reviews Section -->
      <hr class="my-4 text-muted opacity-25">

      <div class="reviews-section">
        <h4 class="fw-bold text-dark mb-4 d-flex align-items-center gap-2">
          <i class="bi bi-chat-text text-primary"></i>
          <span>Avis de la communauté</span>
          <span class="badge bg-secondary-subtle text-secondary fs-6 ms-2" v-if="reviews.length > 0">{{ reviews.length }}</span>
        </h4>

        <div class="row g-3" v-if="reviews.length > 0">
          <div class="col-12" v-for="review in reviews" :key="review.id">
            <div class="card border border-light-subtle rounded-3 p-3 bg-light bg-opacity-25">
              <div class="d-flex justify-content-between align-items-start mb-2">
                <span class="fw-semibold text-dark">{{ maskEmail(review.authorEmail) }}</span>
                <span class="text-muted small">{{ review.createdAt }}</span>
              </div>
              <p class="text-secondary mb-0 fst-italic">
                "{{ review.comment }}"
              </p>
            </div>
          </div>
        </div>
        <div class="text-center py-4 bg-light rounded-3 border border-dashed text-secondary" v-else>
          <i class="bi bi-chat-square-text fs-2 mb-2 d-block text-muted"></i>
          <span>Aucun avis n'a encore été publié pour ce jeu.</span>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import Routing from 'fos-router';

defineProps({
  game: {
    type: Object,
    required: true
  },
  description: {
    type: String,
    default: null
  },
  collectionCount: {
    type: Number,
    required: true
  },
  averagePrices: {
    type: Object,
    default: () => ({})
  },
  reviews: {
    type: Array,
    default: () => []
  }
});

const getRoute = (routeName, params = {}) => {
  return Routing.generate(routeName, params);
};

const maskEmail = (email) => {
  if (!email) return '';
  const [name, domain] = email.split('@');
  if (!domain) return email;
  const maskedName = name.length > 2 ? name[0] + '***' + name[name.length - 1] : name[0] + '***';
  return `${maskedName}@${domain}`;
};

const formatPrice = (price) => {
  return (price / 100).toFixed(2);
};

const formatCurrency = (currencyCode) => {
  const symbols = {
    'EUR': '€',
    'USD': '$',
    'GBP': '£'
  };
  return symbols[currencyCode] || currencyCode;
};
</script>

<style scoped>
.game-description {
  max-height: 400px;
  overflow-y: auto;
  padding-right: 8px;
}

.game-description::-webkit-scrollbar {
  width: 6px;
}

.game-description::-webkit-scrollbar-track {
  background: #f1f1f1;
  border-radius: 4px;
}

.game-description::-webkit-scrollbar-thumb {
  background: #cbd5e0;
  border-radius: 4px;
}

.game-description::-webkit-scrollbar-thumb:hover {
  background: #a0aec0;
}

.icon-wrapper {
  width: 44px;
  height: 44px;
}

.price-badge.eur {
  background: #ebf8ff;
  border: 1px solid #bee3f8;
  color: #2b6cb0;
}

.price-badge.usd {
  background: #f0fff4;
  border: 1px solid #c6f6d5;
  color: #2f855a;
}

.price-badge.gbp {
  background: #faf5ff;
  border: 1px solid #e9d8fd;
  color: #6b46c1;
}
</style>
