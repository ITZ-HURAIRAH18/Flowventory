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
@import "../../styles/ui/ToastContainer.css";
</style>