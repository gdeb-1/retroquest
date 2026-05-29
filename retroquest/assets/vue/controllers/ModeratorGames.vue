<template>
  <div class="moderator-games-container">
    <div class="card shadow-sm border-0 bg-white p-3">
      <div class="table-responsive">
        <DataTable 
          :data="games" 
          :columns="columns" 
          :options="options" 
          class="table table-striped table-hover border align-middle w-100"
          @click="handleTableClick"
        />
      </div>
    </div>

    <Teleport to="body">
      <div 
        v-if="gameToToggle" 
        class="modal fade show" 
        style="display: block; background-color: rgba(0, 0, 0, 0.5);" 
        tabindex="-1" 
        role="dialog"
      >
        <div class="modal-dialog modal-dialog-centered" role="document">
          <div class="modal-content shadow-lg border-0 rounded-4">
            <div class="modal-header border-bottom-0 pb-0">
              <h5 class="modal-title fw-bold text-dark">Confirmer le changement</h5>
              <button 
                type="button" 
                class="btn-close" 
                @click="gameToToggle = null" 
                aria-label="Close"
              ></button>
            </div>
            <div class="modal-body py-3">
              <p class="text-secondary small lh-base">
                Voulez-vous vraiment changer la visibilité du jeu <strong>{{ gameToToggle.title }}</strong> ?
                Il sera rendu {{ gameToToggle.isHidden ? 'visible' : 'masqué' }} dans le catalogue public.
              </p>
            </div>
            <div class="modal-footer border-top-0 pt-0">
              <button 
                type="button" 
                class="btn btn-secondary rounded-3" 
                @click="gameToToggle = null"
              >
                Annuler
              </button>
              <button 
                type="button" 
                :class="gameToToggle.isHidden ? 'btn btn-success rounded-3' : 'btn btn-warning rounded-3'" 
                @click="confirmToggle"
              >
                {{ gameToToggle.isHidden ? 'Rendre visible' : 'Masquer' }}
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
  games: {
    type: Array,
    required: true
  }
});

const gameToToggle = ref(null);

const columns = [
  { data: 'title', title: 'Titre', className: 'ps-4 fw-medium' },
  { data: 'console', title: 'Console' },
  { data: 'releaseYear', title: 'Année' },
  { 
    data: 'isHidden', 
    title: 'Statut',
    render: (data) => {
      return data 
        ? '<span class="badge bg-danger">Masqué</span>' 
        : '<span class="badge bg-success">Visible</span>';
    }
  },
  {
    data: null,
    title: 'Actions',
    orderable: false,
    className: 'text-end pe-4',
    render: (data, type, row) => {
      if (row.isHidden) {
        return `<button class="btn btn-sm btn-outline-success btn-toggle-visibility" data-id="${row.id}"><i class="bi bi-eye"></i> Rendre visible</button>`;
      } else {
        return `<button class="btn btn-sm btn-outline-warning btn-toggle-visibility" data-id="${row.id}"><i class="bi bi-eye-slash"></i> Masquer</button>`;
      }
    }
  }
];

const options = {
  paging: false,
  language: {
    search: "Rechercher :",
    info: "Affichage de _START_ à _END_ sur _TOTAL_ éléments",
    infoEmpty: "Affichage de 0 à 0 sur 0 élément",
    infoFiltered: "(filtré de _MAX_ éléments au total)",
    zeroRecords: "Aucun résultat trouvé"
  },
  order: [[0, 'asc']]
};

const handleTableClick = (event) => {
  const button = event.target.closest('.btn-toggle-visibility');
  if (!button) return;
  const gameId = button.dataset.id;
  const game = props.games.find(g => g.id == gameId);
  if (game) {
    gameToToggle.value = game;
  }
};

const confirmToggle = () => {
  if (!gameToToggle.value) return;

  const form = document.createElement('form');
  form.method = 'POST';
  form.action = path('app_moderator_toggle_game_visibility', { id: gameToToggle.value.id });

  const csrfInput = document.createElement('input');
  csrfInput.type = 'hidden';
  csrfInput.name = '_token';
  csrfInput.value = gameToToggle.value.csrfToken;
  form.appendChild(csrfInput);

  document.body.appendChild(form);
  form.submit();
};

const path = (name, params = {}) => {
  return Routing.generate(name, params);
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

table.dataTable {
  border-collapse: collapse !important;
  margin-top: 1rem !important;
  margin-bottom: 1rem !important;
}

table.dataTable th {
  border-bottom: 2px solid #dee2e6 !important;
}
</style>
