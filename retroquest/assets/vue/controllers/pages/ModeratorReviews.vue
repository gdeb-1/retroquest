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

    <!-- Teleport Modal for Delete Confirmation -->
    <Teleport to="body">
      <div 
        v-if="reviewToDelete" 
        class="modal fade show" 
        style="display: block; background-color: rgba(0, 0, 0, 0.5);" 
        tabindex="-1" 
        role="dialog"
      >
        <div class="modal-dialog modal-dialog-centered" role="document">
          <div class="modal-content shadow-lg border-0 rounded-4">
            <div class="modal-header border-bottom-0 pb-0">
              <h5 class="modal-title fw-bold text-dark">Confirmer la suppression</h5>
              <button 
                type="button" 
                class="btn-close" 
                @click="reviewToDelete = null" 
                aria-label="Close"
              ></button>
            </div>
            <div class="modal-body py-3">
              <p class="text-secondary small lh-base">
                Voulez-vous vraiment supprimer définitivement cet avis ?
              </p>
              <div class="p-3 bg-light rounded-3 text-secondary small border-start border-danger border-3">
                <strong>{{ reviewToDelete.authorEmail }}</strong> sur <em>{{ reviewToDelete.gameTitle }}</em> :<br/>
                <span class="fst-italic">"{{ reviewToDelete.comment }}"</span>
              </div>
              <p class="text-danger small mt-2 mb-0 fw-semibold">
                Cette action est irréversible.
              </p>
            </div>
            <div class="modal-footer border-top-0 pt-0">
              <button 
                type="button" 
                class="btn btn-secondary rounded-3 px-3 py-2 fw-medium" 
                @click="reviewToDelete = null"
              >
                Annuler
              </button>
              <button 
                type="button" 
                class="btn btn-danger rounded-3 px-3 py-2 fw-medium shadow-sm" 
                @click="confirmActionDelete"
              >
                Supprimer l'avis
              </button>
            </div>
          </div>
        </div>
      </div>
    </Teleport>
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

const reviewToDelete = ref(null);

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
      buttons += `<button class="btn btn-sm btn-outline-danger btn-delete w-100" data-id="${row.id}"><i class="bi bi-trash"></i> Supprimer</button>`;
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
  const deleteButton = event.target.closest('.btn-delete');

  if (validateButton) {
    const reviewId = validateButton.dataset.id;
    const review = props.reviews.find(r => r.id == reviewId);
    if (review) {
      handleValidate(review);
    }
  } else if (deleteButton) {
    const reviewId = deleteButton.dataset.id;
    const review = props.reviews.find(r => r.id == reviewId);
    if (review) {
      confirmDelete(review);
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

const confirmDelete = (review) => {
  reviewToDelete.value = review;
};

const confirmActionDelete = () => {
  if (!reviewToDelete.value) return;

  const form = document.createElement('form');
  form.method = 'POST';
  form.action = path('app_moderator_delete_review', { id: reviewToDelete.value.id });

  const csrfInput = document.createElement('input');
  csrfInput.type = 'hidden';
  csrfInput.name = '_token';
  csrfInput.value = reviewToDelete.value.csrfTokenDelete;
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
