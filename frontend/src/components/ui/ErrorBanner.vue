<script setup>
defineProps({
  error: String,
  show: { type: Boolean, default: false }
})
</script>

<template>
  <Transition name="expand">
    <div v-if="show && error" class="error-banner">
      <div class="banner-inner">
        <span class="material-symbols-outlined banner-icon">error</span>
        <span class="banner-text">{{ error }}</span>
      </div>
    </div>
  </Transition>
</template>

<style scoped>
@import "@/styles/ui/ErrorBanner.css";
</style>
