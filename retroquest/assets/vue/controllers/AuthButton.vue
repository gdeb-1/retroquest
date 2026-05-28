<template>
  <div class="d-flex align-items-center gap-3">
    <template v-if="isLoggedIn">
      <a :href="logoutUrl" class="btn btn-outline-danger px-3 py-2 fw-semibold btn-custom-hover">
        Déconnexion
      </a>
    </template>
    <template v-else>
      <a :href="loginUrl" class="btn btn-outline-primary px-3 py-2 fw-semibold btn-custom-hover">
        Connexion
      </a>
      <a :href="registerUrl" class="btn btn-primary px-3 py-2 fw-semibold btn-custom-hover">
        S'inscrire
      </a>
    </template>
  </div>
</template>

<script setup>
defineProps({
  isLoggedIn: {
    type: Boolean,
    required: true
  },
  loginUrl: {
    type: String,
    required: true
  },
  registerUrl: {
    type: String,
    required: true
  },
  logoutUrl: {
    type: String,
    required: true
  }
});
</script>
