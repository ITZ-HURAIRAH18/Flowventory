<script setup>
const props = defineProps({
  loading: { type: Boolean, default: false },
  disabled: { type: Boolean, default: false },
  type: { type: String, default: 'button' },
  variant: { type: String, default: 'primary' }, // primary, secondary, danger, ghost
  icon: { type: String, default: '' },
  fullWidth: { type: Boolean, default: false }
})
</script>

<template>
  <button
    :type="type"
    class="base-btn"
    :class="[`btn-${variant}`, { 'is-loading': loading, 'w-full': fullWidth }]"
    :disabled="disabled || loading"
  >
    <span v-if="loading" class="btn-spinner"></span>
    <span v-else-if="icon" class="material-symbols-outlined btn-icon">{{ icon }}</span>
    <slot></slot>
  </button>
</template>

<style scoped>
@import "@/styles/ui/BaseButton.css";
</style>
