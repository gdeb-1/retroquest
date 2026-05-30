<template>
  <div class="moderator-reviews-container">
    <div class="card shadow-sm border-0 bg-white p-3">
      <div class="table-responsive">
        <DataTable 
          :data="reviews" 
          :columns="columns" 
          :options="options" 
          class="table table-striped table-hover border align-middle w-100"
          @click="handleTableClick"
        />
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import DataTable from 'datatables.net-vue3';
import DataTablesCore from 'datatables.net-dt';
import Routing from 'fos-router';

DataTable.use(DataTablesCore);

const props = defineProps({
  reviews: {
    type: Array,
    required: true
  }
});

const columns = [
  { data: 'gameTitle', title: 'Jeu', className: 'fw-semibold' },
  { data: 'gameConsole', title: 'Console' },
  { data: 'authorEmail', title: 'Auteur' },
  { data: 'createdAt', title: 'Date' },
  { 
    data: 'comment', 
    title: 'Commentaire',
    render: (data) => {
      return `<span class="text-secondary fst-italic">"${data}"</span>`;
    }
  },
  { 
    data: 'isValid', 
    title: 'Statut',
    render: (data) => {
      return data 
        ? '<span class="badge bg-success w-100">Validé</span>' 
        : '<span class="badge bg-warning text-dark w-100">En attente</span>';
    }
  },
  {
    data: null,
    title: 'Actions',
    orderable: false,
    className: 'text-end pe-4',
    render: (data, type, row) => {
      let buttons = '';
      if (!row.isValid) {
        buttons += `<button class="btn btn-sm btn-success me-2 btn-validate w-100 mb-1" data-id="${row.id}"><i class="bi bi-check-lg"></i> Valider</button>`;
      }
      return buttons;
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
  order: [[3, 'desc']]
};

const path = (name, params = {}) => {
  return Routing.generate(name, params);
};

const handleTableClick = (event) => {
  const validateButton = event.target.closest('.btn-validate');

  if (validateButton) {
    const reviewId = validateButton.dataset.id;
    const review = props.reviews.find(r => r.id == reviewId);
    if (review) {
      handleValidate(review);
    }
  }
};

const handleValidate = (review) => {
  const form = document.createElement('form');
  form.method = 'POST';
  form.action = path('app_moderator_validate_review', { id: review.id });

  const csrfInput = document.createElement('input');
  csrfInput.type = 'hidden';
  csrfInput.name = '_token';
  csrfInput.value = review.csrfTokenValidate;
  form.appendChild(csrfInput);

  document.body.appendChild(form);
  form.submit();
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
