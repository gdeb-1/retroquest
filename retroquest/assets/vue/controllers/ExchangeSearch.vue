<template>
  <div class="card shadow-sm border-0 bg-white p-3">
    <div class="table-responsive">
      <DataTable 
        :data="items" 
        :columns="columns" 
        :options="options" 
        class="table table-striped table-hover border align-middle w-100"
      />
    </div>

    <Teleport to="body">
      <div 
        v-if="selectedItem" 
        class="modal fade show" 
        style="display: block; background-color: rgba(0, 0, 0, 0.5);" 
        tabindex="-1" 
        role="dialog"
      >
        <div class="modal-dialog modal-dialog-centered" role="document">
          <div class="modal-content shadow-lg border-0 rounded-4">
            <div class="modal-header border-bottom-0 pb-0">
              <h5 class="modal-title fw-bold text-dark">Proposer un échange</h5>
              <button 
                type="button" 
                class="btn-close" 
                @click="selectedItem = null" 
                aria-label="Close"
              ></button>
            </div>
            <div class="modal-body py-3">
              <p class="text-secondary small lh-base mb-0">
                Vous vous apprêtez à proposer un échange avec <strong>{{ selectedItem.collector.email }}</strong> pour le jeu <strong>{{ selectedItem.game.title }}</strong> ({{ selectedItem.game.console }}).
              </p>
            </div>
            <div class="modal-footer border-top-0 pt-0">
              <button 
                type="button" 
                class="btn btn-secondary rounded-3" 
                @click="selectedItem = null"
              >
                Annuler
              </button>
              <a 
                :href="`/collector/exchange/propose/${selectedItem.id}`" 
                class="btn btn-primary rounded-3"
              >
                Confirmer et choisir mes contreparties
              </a>
            </div>
          </div>
        </div>
      </div>
    </Teleport>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import DataTable from 'datatables.net-vue3';
import DataTablesCore from 'datatables.net-dt';

DataTable.use(DataTablesCore);

const props = defineProps({
  items: {
    type: Array,
    required: true
  }
});

const selectedItem = ref(null);

const handleProposeExchange = (itemId) => {
  const item = props.items.find(i => i.id === itemId);
  if (item) {
    selectedItem.value = item;
  }
};

onMounted(() => {
  window.handleProposeExchange = handleProposeExchange;
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

const getStateBadgeClass = (state) => {
  switch (state) {
    case 'Mint': return 'bg-success';
    case 'Good': return 'bg-primary';
    case 'Fair': return 'bg-warning text-dark';
    case 'Poor': return 'bg-danger';
    default: return 'bg-secondary';
  }
};

const columns = [
  { data: 'game.title', title: 'Jeu' },
  { data: 'game.console', title: 'Console' },
  { 
    data: 'state', 
    title: 'État',
    render: (data) => {
      const badgeClass = getStateBadgeClass(data);
      return `<span class="badge ${badgeClass}">${data}</span>`;
    }
  },
  {
    data: null,
    title: 'Valeur estimée',
    render: (data, type, row) => {
      return `${formatCurrency(row.currency)}${formatPrice(row.acquisitionPrice)}`;
    }
  },
  { data: 'collector.email', title: 'Propriétaire' },
  {
    data: null,
    title: 'Actions',
    orderable: false,
    className: 'text-end pe-4',
    render: (data, type, row) => {
      return `<button class="btn btn-sm btn-outline-primary" onclick="window.handleProposeExchange(${row.id})"><i class="bi bi-arrow-left-right"></i> Échanger</button>`;
    }
  }
];

const options = {
  language: {
    search: "Rechercher :",
    lengthMenu: "Afficher _MENU_ éléments",
    info: "Affichage de _START_ à _END_ sur _TOTAL_ éléments",
    infoEmpty: "Affichage de 0 à 0 sur 0 élément",
    infoFiltered: "(filtré de _MAX_ éléments au total)",
    paginate: {
      first: "Premier",
      previous: "Précédent",
      next: "Suivant",
      last: "Dernier"
    },
    zeroRecords: "Aucun résultat trouvé"
  },
  pageLength: 10,
  order: [[0, 'asc']]
};
</script>

<style>
@import 'datatables.net-dt';

.dt-container .dt-search input {
  border: 1px solid #dee2e6;
  border-radius: 4px;
  padding: 0.375rem 0.75rem;
  font-size: 0.875rem;
  outline: none;
  background-color: #fff;
  transition: border-color 0.15s ease-in-out;
}

.dt-container .dt-search input:focus {
  border-color: #86b7fe;
  box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
}

.dt-container .dt-length select {
  border: 1px solid #dee2e6;
  border-radius: 4px;
  padding: 0.375rem 1.75rem 0.375rem 0.75rem;
  font-size: 0.875rem;
}

.dt-container .dt-paging .dt-paging-button {
  border: 1px solid #dee2e6 !important;
  border-radius: 4px !important;
  background: #fff !important;
  color: #0d6efd !important;
  padding: 0.375rem 0.75rem !important;
  margin: 0 2px !important;
  transition: all 0.2s;
}

.dt-container .dt-paging .dt-paging-button.current,
.dt-container .dt-paging .dt-paging-button.current:hover {
  background: #0d6efd !important;
  color: #fff !important;
  border-color: #0d6efd !important;
}

.dt-container .dt-paging .dt-paging-button:hover {
  background: #e9ecef !important;
  color: #0a58ca !important;
}

.dt-container .dt-paging .dt-paging-button.disabled {
  background: #fff !important;
  color: #6c757d !important;
  border-color: #dee2e6 !important;
  cursor: not-allowed;
}

table.dataTable {
  border-collapse: collapse !important;
  margin-top: 1rem !important;
  margin-bottom: 1rem !important;
}

table.dataTable th {
  border-bottom: 2px solid #dee2e6 !important;
}
</style>
