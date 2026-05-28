<template>
  <div class="card h-100 p-3 shadow-sm">
    <div class="d-flex justify-content-between align-items-center mb-2">
      <span class="badge bg-secondary-subtle text-secondary">{{ console }}</span>
      <span class="small text-muted">{{ releaseYear }}</span>
    </div>
    <h5 class="card-title mb-2 fw-semibold text-dark">{{ title }}</h5>
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
</style>
