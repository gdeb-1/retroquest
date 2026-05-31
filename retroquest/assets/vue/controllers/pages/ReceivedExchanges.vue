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

        <!-- Card Footer -->
        <div 
          v-if="exchange.status === 'pending'" 
          class="card-footer bg-white border-top border-light-subtle py-3 px-4 d-flex justify-content-end gap-2"
        >
          <button 
            type="button" 
            class="btn btn-outline-danger btn-sm rounded-3 px-3 py-2 fw-semibold d-flex align-items-center gap-2 hover-lift"
            @click="triggerReject(exchange)"
          >
            <i class="bi bi-x-circle"></i>
            <span>Refuser la proposition</span>
          </button>
          <button 
            type="button" 
            class="btn btn-success btn-sm rounded-3 px-3 py-2 fw-semibold d-flex align-items-center gap-2 hover-lift"
            @click="triggerValidate(exchange)"
          >
            <i class="bi bi-check-circle"></i>
            <span>Accepter la proposition</span>
          </button>
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
    <!-- Teleport Modal for Rejection Confirmation -->
    <Teleport to="body">
      <div 
        v-if="exchangeToReject" 
        class="modal fade show" 
        style="display: block; background-color: rgba(0, 0, 0, 0.5);" 
        tabindex="-1" 
        role="dialog"
      >
        <div class="modal-dialog modal-dialog-centered" role="document">
          <div class="modal-content shadow-lg border-0 rounded-4">
            <div class="modal-header border-bottom-0 pb-0">
              <h5 class="modal-title fw-bold text-dark">Refuser la proposition</h5>
              <button 
                type="button" 
                class="btn-close" 
                @click="exchangeToReject = null" 
                aria-label="Close"
              ></button>
            </div>
            <div class="modal-body py-3">
              <p class="text-secondary small lh-base">
                Êtes-vous sûr de vouloir refuser cette proposition d'échange de la part de <strong>{{ exchangeToReject.proposer.email }}</strong> ?
              </p>
              
              <div class="p-3 bg-light rounded-3 text-secondary small border-start border-danger border-3 mb-3">
                <div class="fw-bold text-dark-emphasis mb-1">Résumé de l'échange :</div>
                <div class="mb-1">
                  <strong>On vous propose :</strong> {{ exchangeToReject.offeredItems.map(i => i.game.title).join(', ') }}
                </div>
                <div>
                  <strong>On vous demande :</strong> {{ exchangeToReject.requestedItems.map(i => i.game.title).join(', ') }}
                </div>
              </div>

              <p class="text-danger small mt-2 mb-0 fw-semibold">
                Cette action est définitive et l'échange sera marqué comme refusé.
              </p>
            </div>
            <div class="modal-footer border-top-0 pt-0">
              <button 
                type="button" 
                class="btn btn-secondary rounded-3 px-3 py-2 fw-medium" 
                @click="exchangeToReject = null"
              >
                Conserver la demande
              </button>
              <button 
                type="button" 
                class="btn btn-danger rounded-3 px-3 py-2 fw-medium shadow-sm" 
                @click="confirmReject"
              >
                Confirmer le refus
              </button>
            </div>
          </div>
        </div>
      </div>
    </Teleport>

    <!-- Teleport Modal for Validation Confirmation -->
    <Teleport to="body">
      <div 
        v-if="exchangeToValidate" 
        class="modal fade show" 
        style="display: block; background-color: rgba(0, 0, 0, 0.5);" 
        tabindex="-1" 
        role="dialog"
      >
        <div class="modal-dialog modal-dialog-centered" role="document">
          <div class="modal-content shadow-lg border-0 rounded-4">
            <div class="modal-header border-bottom-0 pb-0">
              <h5 class="modal-title fw-bold text-dark">Accepter la proposition</h5>
              <button 
                type="button" 
                class="btn-close" 
                @click="exchangeToValidate = null" 
                aria-label="Close"
              ></button>
            </div>
            <div class="modal-body py-3">
              <p class="text-secondary small lh-base">
                Êtes-vous sûr de vouloir accepter cette proposition d'échange de la part de <strong>{{ exchangeToValidate.proposer.email }}</strong> ?
              </p>
              
              <div class="p-3 bg-light rounded-3 text-secondary small border-start border-success border-3 mb-3">
                <div class="fw-bold text-dark-emphasis mb-1">Résumé de l'échange :</div>
                <div class="mb-1">
                  <strong>Vous allez recevoir :</strong> {{ exchangeToValidate.offeredItems.map(i => i.game.title).join(', ') }}
                </div>
                <div>
                  <strong>Vous allez céder :</strong> {{ exchangeToValidate.requestedItems.map(i => i.game.title).join(', ') }}
                </div>
              </div>

              <div class="alert alert-warning p-2.5 rounded-3 mb-0 border-0 d-flex align-items-start gap-2.5">
                <i class="bi bi-exclamation-triangle-fill text-warning fs-5"></i>
                <div class="text-warning-emphasis small">
                  <strong>Attention :</strong> Cette action validera l'échange de manière définitive, transférera la propriété des jeux dans vos collections respectives, et annulera automatiquement les autres demandes d'échange en attente contenant ces mêmes jeux.
                </div>
              </div>
            </div>
            <div class="modal-footer border-top-0 pt-0">
              <button 
                type="button" 
                class="btn btn-secondary rounded-3 px-3 py-2 fw-medium" 
                @click="exchangeToValidate = null"
              >
                Annuler
              </button>
              <button 
                type="button" 
                class="btn btn-success rounded-3 px-3 py-2 fw-medium shadow-sm" 
                @click="confirmValidate"
              >
                Confirmer l'échange
              </button>
            </div>
          </div>
        </div>
      </div>
    </Teleport>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import Routing from 'fos-router';
import ExchangeFilters from '../components/ExchangeFilters.vue';

const props = defineProps({
  exchanges: {
    type: Array,
    required: true
  }
});

const activeFilter = ref('all');
const exchangeToReject = ref(null);
const exchangeToValidate = ref(null);

const triggerReject = (exchange) => {
  exchangeToReject.value = exchange;
};

const confirmReject = () => {
  if (!exchangeToReject.value) return;

  const form = document.createElement('form');
  form.method = 'POST';
  form.action = path('app_collector_exchange_reject', { id: exchangeToReject.value.id });

  const csrfInput = document.createElement('input');
  csrfInput.type = 'hidden';
  csrfInput.name = '_token';
  csrfInput.value = exchangeToReject.value.csrfTokenReject;
  form.appendChild(csrfInput);

  document.body.appendChild(form);
  form.submit();
};

const triggerValidate = (exchange) => {
  exchangeToValidate.value = exchange;
};

const confirmValidate = () => {
  if (!exchangeToValidate.value) return;

  const form = document.createElement('form');
  form.method = 'POST';
  form.action = path('app_collector_exchange_validate', { id: exchangeToValidate.value.id });

  const csrfInput = document.createElement('input');
  csrfInput.type = 'hidden';
  csrfInput.name = '_token';
  csrfInput.value = exchangeToValidate.value.csrfTokenValidate;
  form.appendChild(csrfInput);

  document.body.appendChild(form);
  form.submit();
};


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
