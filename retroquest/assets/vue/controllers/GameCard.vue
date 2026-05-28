<template>
  <div class="card h-100 p-3 shadow-sm">
    <div class="d-flex justify-content-between align-items-center mb-2">
      <span class="badge bg-secondary-subtle text-secondary">{{ console }}</span>
      <span class="small text-muted">{{ releaseYear }}</span>
    </div>
    <h5 class="card-title mb-2 fw-semibold text-dark">{{ title }}</h5>
    <div v-if="description" class="mb-3">
      <p class="card-text small text-secondary description-text mb-1">
        {{ description }}
      </p>
      <button 
        type="button" 
        class="btn btn-link p-0 text-decoration-none small fw-semibold text-primary"
        data-bs-toggle="modal" 
        :data-bs-target="'#gameModal-' + id"
      >
        Lire la suite
      </button>

      <Teleport to="body">
        <div 
          class="modal fade" 
          :id="'gameModal-' + id" 
          tabindex="-1" 
          aria-hidden="true"
        >
          <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content shadow-lg border-0 rounded-4">
              <div class="modal-header border-bottom-0 pb-0">
                <h5 class="modal-title fw-bold text-dark">{{ title }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
              </div>
              <div class="modal-body py-3">
                <div class="mb-3">
                  <span class="badge bg-secondary-subtle text-secondary me-2">{{ console }}</span>
                  <span class="small text-muted">{{ releaseYear }}</span>
                </div>
                <p class="text-secondary small lh-base" style="white-space: pre-line;">
                  {{ description }}
                </p>
              </div>
              <div class="modal-footer border-top-0 pt-0">
                <button type="button" class="btn btn-secondary rounded-3" data-bs-dismiss="modal">Fermer</button>
              </div>
            </div>
          </div>
        </div>
      </Teleport>
    </div>
    <div class="d-flex flex-column gap-2 small text-secondary">
      <div class="d-flex justify-content-between align-items-center">
        <span>Popularité :</span>
        <span class="fw-medium text-dark">{{ additionsCount }} ajout(s) ce mois</span>
      </div>
      <div class="d-flex flex-column align-items-start gap-1 w-100">
        <span>Prix moyen :</span>
        <div class="d-flex flex-wrap gap-1 w-100 mt-1" v-if="averagePrices && Object.keys(averagePrices).length > 0">
          <div 
            v-for="(price, cur) in averagePrices" 
            :key="cur" 
            class="price-badge d-flex align-items-center gap-1 rounded-2 px-2 py-1 fw-semibold"
            :class="cur.toLowerCase()"
          >
            <span>{{ formatCurrency(cur) }}</span>
            <span>{{ formatPrice(price) }}</span>
          </div>
        </div>
        <span class="fw-medium text-muted" v-else>N/A</span>
      </div>
    </div>
  </div>
</template>

<script setup>
defineProps({
  id: {
    type: Number,
    required: true
  },
  title: {
    type: String,
    required: true
  },
  console: {
    type: String,
    required: true
  },
  releaseYear: {
    type: Number,
    required: true
  },
  additionsCount: {
    type: Number,
    required: true
  },
  averagePrices: {
    type: Object,
    default: () => ({})
  },
  description: {
    type: String,
    default: null
  }
});

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

.description-text {
  display: -webkit-box;
  -webkit-line-clamp: 3;
  line-clamp: 3;
  -webkit-box-orient: vertical;
  overflow: hidden;
  text-overflow: ellipsis;
  line-height: 1.4;
}
</style>
