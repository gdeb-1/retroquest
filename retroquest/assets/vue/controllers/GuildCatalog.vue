<template>
  <div class="card shadow-sm border-0 bg-white p-3">
    <div class="table-responsive">
      <DataTable 
        :data="catalogData" 
        :columns="columns" 
        :options="options" 
        class="table table-striped table-hover border align-middle w-100"
      >
        <template #column-state="props">
          <span class="badge" :class="getStateBadgeClass(props.cellData)">{{ props.cellData }}</span>
        </template>

        <template #column-price="props">
          <span class="fw-semibold text-dark">{{ formatPrice(props.cellData, props.rowData.currency) }}</span>
        </template>

        <template #column-date="props">
          <span>{{ formatDate(props.cellData) }}</span>
        </template>

        <template #column-collector="props">
          <span class="text-secondary small">{{ props.cellData }}</span>
        </template>
      </DataTable>
    </div>
  </div>
</template>

<script setup>
import DataTable from 'datatables.net-vue3';
import DataTablesCore from 'datatables.net-dt';

DataTable.use(DataTablesCore);

defineProps({
  catalogData: {
    type: Array,
    required: true
  }
});

const columns = [
  { data: 'title', title: 'Titre' },
  { data: 'console', title: 'Console' },
  { data: 'releaseYear', title: 'Année de sortie' },
  { data: 'state', title: 'État', name: 'state' },
  { data: 'acquisitionPrice', title: "Prix d'acquisition", name: 'price' },
  { data: 'acquisitionDate', title: "Date d'acquisition", name: 'date' },
  { data: 'collector', title: 'Collectionneur', name: 'collector' }
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

const getStateBadgeClass = (state) => {
  switch (state) {
    case 'Mint': return 'bg-success';
    case 'Good': return 'bg-primary';
    case 'Fair': return 'bg-warning text-dark';
    case 'Poor': return 'bg-danger';
    default: return 'bg-secondary';
  }
};

const formatPrice = (price, currency) => {
  const symbols = {
    'EUR': '€',
    'USD': '$',
    'GBP': '£'
  };
  const symbol = symbols[currency] || currency;
  return `${(price / 100).toFixed(2)} ${symbol}`;
};

const formatDate = (dateStr) => {
  if (!dateStr) return 'N/A';
  const date = new Date(dateStr);
  return date.toLocaleDateString('fr-FR', {
    year: 'numeric',
    month: 'numeric',
    day: 'numeric'
  });
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
