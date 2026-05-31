<template>
  <div class="my-collection-container">
    <div class="mb-4 d-flex justify-content-between align-items-center flex-wrap gap-3">
      <div>
        <h1 class="display-6 fw-bold text-dark mb-1">Ma Collection</h1>
        <p class="text-secondary mb-0">
          Vous avez <span class="fw-semibold text-primary">{{ items.length }}</span> jeu(x) dans votre collection.
        </p>
      </div>
      <a :href="path('app_collector_add_collection_item')" class="btn btn-primary d-flex align-items-center gap-2 px-3 py-2 rounded-3 shadow-sm transition-all hover-lift">
        <i class="bi bi-plus-lg"></i>
        <span>Ajouter un jeu</span>
      </a>
    </div>

    <div v-if="items.length > 0" class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-5 g-4">
      <div 
        v-for="item in items" 
        :key="item.id" 
        class="col d-flex"
      >
        <CollectionItemCard 
          v-bind="item"
          @delete="onDelete"
        />
      </div>
    </div>

    <div v-else class="text-center py-5 px-4 border border-dashed rounded-4 bg-white shadow-sm mt-4">
      <div class="mb-3 display-4 text-muted">
        <i class="bi bi-controller"></i>
      </div>
      <h3 class="fw-bold text-dark mb-2">Votre collection est vide</h3>
      <p class="text-secondary mx-auto mb-4" style="max-width: 400px;">
        Commencez à ajouter des jeux ou des consoles ou consulter le catalogue de la guilde.
      </p>
      <a :href="path('app_collector_guild_catalog')" class="btn btn-primary px-4 py-2 rounded-3 shadow-sm">
        Découvrir le catalogue
      </a>
    </div>

    <Teleport to="body">
      <div 
        v-if="itemToDelete" 
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
                @click="itemToDelete = null" 
                aria-label="Close"
              ></button>
            </div>
            <div class="modal-body py-3">
              <p class="text-secondary small lh-base">
                Voulez-vous vraiment retirer le jeu <strong>{{ itemToDelete.game.title }}</strong> de votre collection ?
              </p>
            </div>
            <div class="modal-footer border-top-0 pt-0">
              <button 
                type="button" 
                class="btn btn-secondary rounded-3" 
                @click="itemToDelete = null"
              >
                Annuler
              </button>
              <button 
                type="button" 
                class="btn btn-danger rounded-3" 
                @click="confirmDelete"
              >
                Supprimer
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
import CollectionItemCard from './CollectionItemCard.vue';
import Routing from 'fos-router';

const props = defineProps({
  items: {
    type: Array,
    required: true
  },
  csrfToken: {
    type: String,
    required: true
  }
});

const itemToDelete = ref(null);


const onDelete = (id) => {
  const item = props.items.find(i => i.id === id);
  if (item) {
    itemToDelete.value = item;
  }
};

const confirmDelete = () => {
  if (!itemToDelete.value) return;

  const form = document.createElement('form');
  form.method = 'POST';
  form.action = path('app_collector_delete_collection_item', { id: itemToDelete.value.id });

  const csrfInput = document.createElement('input');
  csrfInput.type = 'hidden';
  csrfInput.name = '_token';
  csrfInput.value = props.csrfToken;
  form.appendChild(csrfInput);

  document.body.appendChild(form);
  form.submit();
};

const path = (name, params = {}) => {
  return Routing.generate(name, params);
};
</script>

<style scoped>
.hover-lift {
  transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.hover-lift:hover {
  transform: translateY(-1px);
  box-shadow: 0 4px 10px rgba(13, 110, 253, 0.15) !important;
}

.btn {
  transition: all 0.2s ease-in-out;
}
</style>
