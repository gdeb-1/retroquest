<template>
  <div class="navbar-nav align-items-center">
    <template v-if="isCollector">
      <a class="nav-link text-secondary fw-medium ms-3" :href="getRoute('app_collector_guild_catalog')">Catalogue de la Guilde</a>
      
      <div class="nav-item dropdown ms-3">
        <a 
          class="nav-link dropdown-toggle text-secondary fw-medium" 
          href="#" 
          role="button" 
          data-bs-toggle="dropdown" 
          aria-expanded="false"
        >
          Ma Collection
        </a>
        <ul class="dropdown-menu border-0 shadow-sm">
          <li><a class="dropdown-item py-2" :href="getRoute('app_collector_my_collection')">Gérer ma collection</a></li>
          <li><a class="dropdown-item py-2" href="#">Estimer ma collection</a></li>
        </ul>
      </div>

      <div class="nav-item dropdown ms-3">
        <a 
          class="nav-link dropdown-toggle text-secondary fw-medium" 
          href="#" 
          role="button" 
          data-bs-toggle="dropdown" 
          aria-expanded="false"
        >
          Échanges
        </a>
        <ul class="dropdown-menu border-0 shadow-sm">
          <li><a class="dropdown-item py-2" :href="getRoute('app_collector_exchange_search')">Rechercher un échange</a></li>
          <li><a class="dropdown-item py-2" :href="getRoute('app_collector_exchange_sent')">Mes demandes envoyées</a></li>
          <li><a class="dropdown-item py-2" href="#">Mes demandes reçues</a></li>
        </ul>
      </div>
    </template>

    <template v-if="isModerator">
      <a class="nav-link text-secondary fw-medium ms-3" :href="getRoute('app_moderator_reviews')">Modérer les avis</a>
      <a class="nav-link text-secondary fw-medium ms-3" :href="getRoute('app_moderator_games')">Gérer les jeux</a>
    </template>

    <template v-if="isAdmin">
      <a class="nav-link text-secondary fw-medium ms-3" :href="getRoute('app_administrator_users')">Gérer les droits de l'utilisateur</a>
    </template>
  </div>
</template>

<script setup>
import Routing from 'fos-router';

defineProps({
  isCollector: {
    type: Boolean,
    default: false
  },
  isModerator: {
    type: Boolean,
    default: false
  },
  isAdmin: {
    type: Boolean,
    default: false
  }
});
const getRoute = (routeName) => {
  return Routing.generate(routeName);
}
</script>

<style scoped>
.nav-link {
  transition: color 0.15s ease-in-out;
}
.nav-link:hover {
  color: var(--bs-primary) !important;
}
.dropdown-item {
  transition: background-color 0.15s ease-in-out, color 0.15s ease-in-out;
}
.dropdown-item:hover {
  background-color: var(--bs-light);
  color: var(--bs-primary);
}
</style>
