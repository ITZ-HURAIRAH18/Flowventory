<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import ToastNotification from './ToastNotification.vue'

const toasts = ref([])
let toastId = 0

const addToast = (options) => {
  const id = ++toastId
  const toast = {
    id,
    type: options.type || 'info',
    title: options.title,
    message: options.message || '',
    duration: options.duration !== undefined ? options.duration : 5000,
    showIcon: options.showIcon !== undefined ? options.showIcon : true,
    closable: options.closable !== undefined ? options.closable : true
  }
  
  toasts.value.push(toast)
  return id
}

const removeToast = (id) => {
  const index = toasts.value.findIndex(toast => toast.id === id)
  if (index > -1) {
    toasts.value.splice(index, 1)
  }
}

const clearAll = () => {
  toasts.value = []
}

// Expose methods to parent components
defineExpose({
  addToast,
  removeToast,
  clearAll,
  // Convenience methods
  success: (title, message, options = {}) => addToast({ ...options, type: 'success', title, message }),
  error: (title, message, options = {}) => addToast({ ...options, type: 'error', title, message }),
  warning: (title, message, options = {}) => addToast({ ...options, type: 'warning', title, message }),
  info: (title, message, options = {}) => addToast({ ...options, type: 'info', title, message })
})

// Listen for global toast events
const handleToastEvent = (event) => {
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
  z-index: 1100;
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
  max-height: calc(100vh - 2rem);
  overflow-y: auto;
  pointer-events: none;
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
  }
}
</style>