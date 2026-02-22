<script setup>
import { ref, watch } from 'vue'
import BaseButton from './BaseButton.vue'

const props = defineProps({
  show: { type: Boolean, default: false },
  title: { type: String, default: 'Confirm Action' },
  message: { type: String, required: true },
  confirmText: { type: String, default: 'Confirm' },
  cancelText: { type: String, default: 'Cancel' },
  confirmVariant: { type: String, default: 'danger' },
  loading: { type: Boolean, default: false }
})

const emit = defineEmits(['confirm', 'cancel', 'close'])

const dialogRef = ref(null)

// Handle escape key
const handleKeydown = (e) => {
  if (e.key === 'Escape' && props.show && !props.loading) {
    emit('cancel')
  }
}

watch(() => props.show, (newVal) => {
  if (newVal) {
    document.addEventListener('keydown', handleKeydown)
    // Focus trap
    setTimeout(() => {
      dialogRef.value?.querySelector('.confirm-cancel-btn')?.focus()
    }, 100)
  } else {
    document.removeEventListener('keydown', handleKeydown)
  }
})

const handleConfirm = () => {
  if (!props.loading) {
    emit('confirm')
  }
}

const handleCancel = () => {
  if (!props.loading) {
    emit('cancel')
  }
}

const handleBackdropClick = (e) => {
  if (e.target === e.currentTarget && !props.loading) {
    emit('cancel')
  }
}
</script>

<template>
  <Teleport to="body">
    <Transition name="modal">
      <div
        v-if="show"
        class="confirm-backdrop"
        @click="handleBackdropClick"
      >
        <div ref="dialogRef" class="confirm-dialog" role="dialog" :aria-labelledby="title">
          <div class="confirm-header">
            <h3 class="confirm-title">{{ title }}</h3>
          </div>
          
          <div class="confirm-body">
            <p class="confirm-message">{{ message }}</p>
          </div>
          
          <div class="confirm-footer">
            <BaseButton
              ref="cancelBtn"
              class="confirm-cancel-btn"
              variant="ghost"
              @click="handleCancel"
              :disabled="loading"
            >
              {{ cancelText }}
            </BaseButton>
            <BaseButton
              :variant="confirmVariant"
              :loading="loading"
              @click="handleConfirm"
            >
              {{ confirmText }}
            </BaseButton>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<style scoped>
.confirm-backdrop {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
  backdrop-filter: blur(4px);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
  padding: 1rem;
}

.confirm-dialog {
  background: white;
  border-radius: 12px;
  box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
  width: 100%;
  max-width: 400px;
  transform-origin: center;
}

.confirm-header {
  padding: 1.5rem 1.5rem 0;
  border-bottom: 1px solid #e5e7eb;
  margin-bottom: 1rem;
}

.confirm-title {
  font-size: 1.125rem;
  font-weight: 600;
  color: #111827;
  margin: 0;
  padding-bottom: 1rem;
}

.confirm-body {
  padding: 0 1.5rem;
}

.confirm-message {
  color: #6b7280;
  line-height: 1.5;
  margin: 0;
}

.confirm-footer {
  display: flex;
  justify-content: flex-end;
  gap: 0.75rem;
  padding: 1.5rem;
  border-top: 1px solid #f3f4f6;
  margin-top: 1.5rem;
}

/* Transitions */
.modal-enter-active {
  transition: all 0.2s ease-out;
}

.modal-leave-active {
  transition: all 0.15s ease-in;
}

.modal-enter-from {
  opacity: 0;
}

.modal-enter-from .confirm-dialog {
  transform: scale(0.95) translateY(-10px);
}

.modal-leave-to {
  opacity: 0;
}

.modal-leave-to .confirm-dialog {
  transform: scale(0.95);
}

/* Dark mode support */
@media (prefers-color-scheme: dark) {
  .confirm-dialog {
    background: #1f2937;
    border: 1px solid #374151;
  }
  
  .confirm-title {
    color: #f9fafb;
  }
  
  .confirm-message {
    color: #d1d5db;
  }
  
  .confirm-header {
    border-bottom-color: #374151;
  }
  
  .confirm-footer {
    border-top-color: #374151;
  }
}
</style>