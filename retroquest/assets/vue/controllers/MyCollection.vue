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
          @exchange="onExchange"
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
  </div>
</template>

<script setup>
import CollectionItemCard from './CollectionItemCard.vue';
import Routing from 'fos-router';

const props = defineProps({
  items: {
    type: Array,
    required: true
  }
});

const onExchange = (id) => {
  const item = props.items.find(i => i.id === id);
  const title = item ? item.game.title : 'ce jeu';
  console.log('Action Échanger déclenchée pour l\'item:', id);
  alert(`Fonctionnalité d'échange à venir pour "${title}" !`);
};

const onDelete = (id) => {
  const item = props.items.find(i => i.id === id);
  const title = item ? item.game.title : 'ce jeu';
  console.log('Action Supprimer déclenchée pour l\'item:', id);
  alert(`Action de suppression demandée pour "${title}" (ID: ${id}).`);
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
