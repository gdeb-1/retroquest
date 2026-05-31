<template>
  <div class="received-exchanges-container">
    <ExchangeFilters v-model="activeFilter" :exchanges="exchanges" />

    <!-- Exchanges list -->
    <div v-if="filteredExchanges.length > 0" class="d-flex flex-column gap-4">
      <div 
        v-for="exchange in filteredExchanges" 
        :key="exchange.id"
        class="card border-0 rounded-4 shadow-sm overflow-hidden hover-shadow transition-all"
      >
        <!-- Card Header -->
        <div class="card-header bg-white border-bottom border-light-subtle py-3 px-4 d-flex justify-content-between align-items-center flex-wrap gap-2">
          <div class="d-flex align-items-center gap-2.5">
            <div>
              <div class="text-secondary small fw-medium">Demande reçue de</div>
              <div class="fw-bold text-dark">{{ exchange.proposer.email }}</div>
            </div>
          </div>
          <div class="d-flex align-items-center gap-3">
            <span class="text-secondary small">Proposé le {{ exchange.propositionDate }}</span>
            <span class="badge px-2.5 py-1.5 rounded-3 fw-bold text-uppercase fs-7" :class="getStatusBadgeClass(exchange.status)">
              {{ getStatusLabel(exchange.status) }}
            </span>
          </div>
        </div>

        <!-- Card Body -->
        <div class="card-body p-4 bg-light-subtle">
          <div class="row align-items-center g-4">
            
            <!-- Offered Items (Proposer Items - what they offer us) -->
            <div class="col-md-5">
              <div class="p-3 bg-white rounded-3 border border-light-subtle h-100 shadow-xs">
                <h3 class="h6 text-success fw-bold text-uppercase mb-3 d-flex align-items-center gap-2">
                  <i class="bi bi-box-arrow-in-down"></i>
                  <span>Ce qu'on vous propose</span>
                </h3>
                <ul class="list-unstyled mb-0 d-flex flex-column gap-2">
                  <li 
                    v-for="item in exchange.offeredItems" 
                    :key="item.id"
                    class="d-flex align-items-start gap-2.5"
                  >
                    <div class="mt-1">
                      <span class="badge badge-dot" :class="getStateBadgeClass(item.state)"></span>
                    </div>
                    <div>
                      <div class="fw-bold text-dark lh-sm mb-0.5">{{ item.game.title }}</div>
                      <div class="text-secondary small d-flex flex-wrap align-items-center gap-2">
                        <span>{{ item.game.console }}</span>
                        <span class="text-light-emphasis">•</span>
                        <span class="badge bg-secondary-subtle text-secondary-emphasis fs-8">{{ item.state }}</span>
                        <span class="text-light-emphasis">•</span>
                        <span class="fw-medium text-dark-emphasis">{{ formatCurrency(item.currency) }}{{ formatPrice(item.acquisitionPrice) }}</span>
                      </div>
                    </div>
                  </li>
                </ul>
              </div>
            </div>

            <!-- Transfer Arrow -->
            <div class="col-md-2 text-center d-none d-md-block">
              <div class="transfer-icon-container">
                <i class="bi bi-arrow-left-right fs-4 text-primary animate-pulse"></i>
              </div>
            </div>

            <!-- Requested Items (Receiver Items - what they want from us) -->
            <div class="col-md-5">
              <div class="p-3 bg-white rounded-3 border border-light-subtle h-100 shadow-xs">
                <h3 class="h6 text-primary fw-bold text-uppercase mb-3 d-flex align-items-center gap-2">
                  <i class="bi bi-box-arrow-up"></i>
                  <span>Ce qu'on vous demande</span>
                </h3>
                <ul class="list-unstyled mb-0 d-flex flex-column gap-2">
                  <li 
                    v-for="item in exchange.requestedItems" 
                    :key="item.id"
                    class="d-flex align-items-start gap-2.5"
                  >
                    <div class="mt-1">
                      <span class="badge badge-dot" :class="getStateBadgeClass(item.state)"></span>
                    </div>
                    <div>
                      <div class="fw-bold text-dark lh-sm mb-0.5">{{ item.game.title }}</div>
                      <div class="text-secondary small d-flex flex-wrap align-items-center gap-2">
                        <span>{{ item.game.console }}</span>
                        <span class="text-light-emphasis">•</span>
                        <span class="badge bg-secondary-subtle text-secondary-emphasis fs-8">{{ item.state }}</span>
                        <span class="text-light-emphasis">•</span>
                        <span class="fw-medium text-dark-emphasis">{{ formatCurrency(item.currency) }}{{ formatPrice(item.acquisitionPrice) }}</span>
                      </div>
                    </div>
                  </li>
                </ul>
              </div>
            </div>

          </div>
        </div>
      </div>
    </div>

    <!-- Empty state -->
    <div v-else class="text-center py-5 px-4 border border-dashed rounded-4 bg-white shadow-sm">
      <div class="mb-3 display-4 text-muted">
        <i class="bi bi-arrow-left-right"></i>
      </div>
      <h3 class="fw-bold text-dark mb-2">Aucune demande trouvée</h3>
      <p class="text-secondary mx-auto mb-4" style="max-width: 450px;">
        {{ activeFilter === 'all' 
          ? "Vous n'avez pas encore reçu de proposition d'échange de la part d'autres collectionneurs." 
          : "Aucune des demandes reçues ne correspond à ce filtre de statut." 
        }}
      </p>
      <a :href="path('app_collector_exchange_search')" class="btn btn-primary px-4 py-2 rounded-3 shadow-sm hover-lift">
        Rechercher un échange
      </a>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import Routing from 'fos-router';
import ExchangeFilters from './ExchangeFilters.vue';

const props = defineProps({
  exchanges: {
    type: Array,
    required: true
  }
});

const activeFilter = ref('all');


const filteredExchanges = computed(() => {
  if (activeFilter.value === 'all') {
    return props.exchanges;
  }
  return props.exchanges.filter(e => e.status === activeFilter.value);
});

const getStatusLabel = (status) => {
  const labels = {
    'pending': 'En attente',
    'accepted': 'Accepté',
    'rejected': 'Refusé',
    'cancelled': 'Annulé'
  };
  return labels[status] || status;
};

const getStatusBadgeClass = (status) => {
  const classes = {
    'pending': 'bg-warning-subtle text-warning border border-warning-subtle',
    'accepted': 'bg-success-subtle text-success border border-success-subtle',
    'rejected': 'bg-danger-subtle text-danger border border-danger-subtle',
    'cancelled': 'bg-secondary-subtle text-secondary border border-secondary-subtle'
  };
  return classes[status] || 'bg-secondary text-white';
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

const path = (name, params = {}) => {
  return Routing.generate(name, params);
};
</script>

<style scoped>
.hover-shadow {
  transition: box-shadow 0.25s ease-in-out, transform 0.25s ease-in-out;
}
.hover-shadow:hover {
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.08) !important;
  transform: translateY(-2px);
}
.hover-lift {
  transition: all 0.2s ease-in-out;
}
.hover-lift:hover {
  transform: translateY(-1px);
}
.badge-dot {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  display: inline-block;
  padding: 0;
}
.fs-7 {
  font-size: 0.75rem;
}
.fs-8 {
  font-size: 0.65rem;
}
.hover-bg-light:hover {
  background-color: var(--bs-light) !important;
  color: var(--bs-primary) !important;
}
.shadow-xs {
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
}
.transfer-icon-container {
  width: 48px;
  height: 48px;
  border-radius: 50%;
  background-color: white;
  border: 1px solid #dee2e6;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
}
@keyframes pulse {
  0% { transform: scale(1); }
  50% { transform: scale(1.05); }
  100% { transform: scale(1); }
}
.animate-pulse {
  animation: pulse 2.5s infinite ease-in-out;
}
</style>
