<template>
  <div class="game-card">
    <div class="game-card-header">
      <span class="console-badge">{{ console }}</span>
      <span class="year-badge">{{ releaseYear }}</span>
    </div>
    <h3 class="game-title">{{ title }}</h3>
    <div class="game-card-body">
      <div class="stat-row">
        <span class="stat-label">Popularité :</span>
        <span class="stat-value">{{ additionsCount }} ajout(s) ce mois</span>
      </div>
      <div class="stat-row prices-section">
        <span class="stat-label">Prix moyen :</span>
        <div class="prices-list" v-if="averagePrices && Object.keys(averagePrices).length > 0">
          <div 
            v-for="(price, cur) in averagePrices" 
            :key="cur" 
            class="price-badge"
            :class="cur.toLowerCase()"
          >
            <span class="currency-symbol">{{ formatCurrency(cur) }}</span>
            <span class="price-val">{{ formatPrice(price) }}</span>
          </div>
        </div>
        <span class="stat-value price-value text-muted" v-else>N/A</span>
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
.game-card {
  background: #ffffff;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  padding: 16px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  display: flex;
  flex-direction: column;
  gap: 12px;
  transition: transform 0.2s, box-shadow 0.2s;
}

.game-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.game-card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.console-badge {
  background: #edf2f7;
  color: #4a5568;
  padding: 4px 8px;
  border-radius: 4px;
  font-size: 0.75rem;
  font-weight: bold;
}

.year-badge {
  color: #718096;
  font-size: 0.75rem;
}

.game-title {
  margin: 0;
  font-size: 1.1rem;
  color: #1a202c;
  font-weight: 600;
}

.game-card-body {
  display: flex;
  flex-direction: column;
  gap: 8px;
  font-size: 0.875rem;
}

.stat-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  color: #4a5568;
}

.prices-section {
  display: flex;
  flex-direction: column;
  gap: 6px;
  align-items: flex-start;
}

.prices-list {
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
  width: 100%;
  margin-top: 2px;
}

.price-badge {
  display: flex;
  align-items: center;
  gap: 4px;
  background: #f7fafc;
  border: 1px solid #e2e8f0;
  border-radius: 6px;
  padding: 3px 8px;
  font-size: 0.8rem;
  font-weight: 600;
  transition: all 0.2s ease;
}

.price-badge:hover {
  transform: translateY(-1px);
}

.price-badge.eur {
  background: #ebf8ff;
  border-color: #bee3f8;
  color: #2b6cb0;
}

.price-badge.usd {
  background: #f0fff4;
  border-color: #c6f6d5;
  color: #2f855a;
}

.price-badge.gbp {
  background: #faf5ff;
  border-color: #e9d8fd;
  color: #6b46c1;
}

.currency-symbol {
  font-size: 0.85rem;
}

.price-val {
  font-family: inherit;
}

.stat-value {
  font-weight: 500;
}

.price-value {
  color: #718096;
  font-weight: 500;
}

.text-muted {
  color: #a0aec0;
}
</style>
