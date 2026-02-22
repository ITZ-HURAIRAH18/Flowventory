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
@import "../../styles/ui/ConfirmDialog.css";
</style>