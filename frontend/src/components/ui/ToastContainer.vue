<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useToast } from '@/composables/useToast'
import ToastNotification from './ToastNotification.vue'

const { toasts, removeToast } = useToast()

// Listen for global toast events (backward compatibility)
const handleToastEvent = (event) => {
  const { addToast } = useToast()
  addToast(event.detail)
}

onMounted(() => {
  window.addEventListener('show-toast', handleToastEvent)
})

onUnmounted(() => {
  window.removeEventListener('show-toast', handleToastEvent)
})
</script>

<template>
  <Teleport to="body">
    <div
      v-if="toasts.length > 0"
      class="toast-container"
    >
      <ToastNotification
        v-for="toast in toasts"
        :key="toast.id"
        :type="toast.type"
        :title="toast.title"
        :message="toast.message"
        :duration="toast.duration"
        :show-icon="toast.showIcon"
        :closable="toast.closable"
        @close="removeToast(toast.id)"
      />
    </div>
  </Teleport>
</template>

<style scoped>
.toast-container {
  position: fixed;
  top: 1rem;
  right: 1rem;
  z-index: 9999; /* Increase z-index to ensure it's above everything */
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
  max-height: calc(100vh - 2rem);
  overflow-y: auto;
  overflow-x: hidden; /* Prevent horizontal scrolling */
  pointer-events: none;
  min-width: 320px; /* Ensure minimum width for proper display */
  max-width: 480px; /* Prevent container from getting too wide */
}

.toast-container > * {
  pointer-events: auto;
}

/* Responsive adjustments */
@media (max-width: 640px) {
  .toast-container {
    top: 0.5rem;
    right: 0.5rem;
    left: 0.5rem;
    max-height: calc(100vh - 1rem);
    min-width: auto;
    max-width: none;
  }
}
</style>