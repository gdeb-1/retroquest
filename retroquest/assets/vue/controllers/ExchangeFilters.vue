<template>
  <div class="d-flex flex-wrap gap-2 mb-4 bg-white p-2 rounded-4 shadow-sm border border-light-subtle">
    <button 
      v-for="filter in filters" 
      :key="filter.value"
      type="button"
      class="btn btn-sm rounded-3 px-3 py-2 fw-semibold transition-all d-flex align-items-center gap-2"
      :class="modelValue === filter.value ? 'btn-primary shadow-sm' : 'btn-light border-0 text-secondary hover-bg-light'"
      @click="emit('update:modelValue', filter.value)"
    >
      <span>{{ filter.label }}</span>
      <span class="badge" :class="modelValue === filter.value ? 'bg-white text-primary' : 'bg-secondary-subtle text-secondary'">
        {{ getCountForFilter(filter.value) }}
      </span>
    </button>
  </div>
</template>

<script setup>
const props = defineProps({
  modelValue: {
    type: String,
    required: true
  },
  exchanges: {
    type: Array,
    required: true
  },
  filters: {
    type: Array,
    default: () => [
      { label: 'Tous', value: 'all' },
      { label: 'En attente', value: 'pending' },
      { label: 'Acceptés', value: 'accepted' },
      { label: 'Refusés', value: 'rejected' },
      { label: 'Annulés', value: 'cancelled' }
    ]
  }
});

const emit = defineEmits(['update:modelValue']);

const getCountForFilter = (filterVal) => {
  if (filterVal === 'all') {
    return props.exchanges.length;
  }
  return props.exchanges.filter(e => e.status === filterVal).length;
};
</script>

<style scoped>
.hover-bg-light:hover {
  background-color: var(--bs-light) !important;
  color: var(--bs-primary) !important;
}
</style>
