<template>
  <div class="card h-100 p-3 shadow-sm border border-light-subtle rounded-4 bg-white d-flex flex-column justify-content-between collection-item-card hover-shadow transition-all">
    <div>
      <div class="d-flex justify-content-between align-items-center mb-2">
        <span class="badge bg-secondary-subtle text-secondary">{{ game.console }}</span>
        <span class="small text-muted">{{ game.releaseYear }}</span>
      </div>

      <h5 class="card-title mb-3 fw-semibold text-dark">{{ game.title }}</h5>

      <div class="d-flex flex-column gap-2 py-2 border-top border-light-subtle text-secondary small">
        <div class="d-flex justify-content-between align-items-center">
          <span>État :</span>
          <span class="badge" :class="getStateBadgeClass(state)">{{ state }}</span>
        </div>
        <div class="d-flex justify-content-between align-items-center">
          <span>Prix d'achat :</span>
          <span class="fw-semibold text-dark">{{ formatCurrency(currency) }}{{ formatPrice(acquisitionPrice) }}</span>
        </div>
      </div>
    </div>

    <div class="d-flex gap-2 mt-3 pt-2 border-top border-light-subtle">
      <button 
        @click="$emit('exchange', id)"
        class="btn btn-outline-primary btn-sm flex-grow-1 d-flex align-items-center justify-content-center gap-1 py-2 fw-semibold rounded-3"
      >
        <i class="bi bi-arrow-left-right"></i>
        <span>Échanger</span>
      </button>
      <button 
        @click="$emit('delete', id)"
        class="btn btn-outline-danger btn-sm flex-grow-1 d-flex align-items-center justify-content-center gap-1 py-2 fw-semibold rounded-3"
      >
        <i class="bi bi-trash"></i>
        <span>Supprimer</span>
      </button>
    </div>
  </div>
</template>

<script setup>
defineProps({
  id: {
    type: Number,
    required: true
  },
  state: {
    type: String,
    required: true
  },
  acquisitionPrice: {
    type: Number,
    required: true
  },
  currency: {
    type: String,
    required: true
  },
  game: {
    type: Object,
    required: true
  }
});

defineEmits(['exchange', 'delete']);

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

const getStateBadgeClass = (state) => {
  switch (state) {
    case 'Mint': return 'bg-success';
    case 'Good': return 'bg-primary';
    case 'Fair': return 'bg-warning text-dark';
    case 'Poor': return 'bg-danger';
    default: return 'bg-secondary';
  }
};
</script>

<style scoped>
.collection-item-card {
  transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.collection-item-card:hover {
  transform: translateY(-4px);
}

.hover-shadow:hover {
  box-shadow: 0 10px 20px rgba(0, 0, 0, 0.08) !important;
}

.btn {
  transition: all 0.2s ease-in-out;
}
</style>
