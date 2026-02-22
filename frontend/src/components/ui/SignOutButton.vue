<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useToast } from '@/composables/useToast'
import BaseButton from './BaseButton.vue'
import ConfirmDialog from './ConfirmDialog.vue'
import api from '@/services/api'

const props = defineProps({
  variant: { type: String, default: 'ghost' },
  icon: { type: String, default: 'logout' },
  showText: { type: Boolean, default: true },
  showConfirmDialog: { type: Boolean, default: true },
  redirectPath: { type: String, default: '/login' }
})

const emit = defineEmits(['signout-start', 'signout-success', 'signout-error'])

const router = useRouter()
const { showSuccess, showError } = useToast()

const showConfirm = ref(false)
const isSigningOut = ref(false)
const signoutError = ref(null)

const initiateSignOut = () => {
  if (props.showConfirmDialog) {
    showConfirm.value = true
  } else {
    performSignOut()
  }
}

const performSignOut = async () => {
  isSigningOut.value = true
  signoutError.value = null
  
  try {
    emit('signout-start')
    
    // Call API logout endpoint
    await api.post('/logout')
    
    // Clear local storage
    localStorage.removeItem('token')
    localStorage.removeItem('user')
    
    // Show success message
    showSuccess(
      'Signed out successfully',
      'You have been safely logged out of your account'
    )
    
    emit('signout-success')
    
    // Close dialog
    showConfirm.value = false
    
    // Redirect after a brief delay to show the toast
    setTimeout(() => {
      router.push(props.redirectPath)
    }, 1000)
    
  } catch (error) {
    console.error('Sign out error:', error)
    
    const errorMessage = error.response?.data?.message || 
      'Failed to sign out properly. Please try again.'
    
    signoutError.value = errorMessage
    
    showError(
      'Sign out failed',
      errorMessage,
      { duration: 7000 }
    )
    
    emit('signout-error', error)
  } finally {
    isSigningOut.value = false
  }
}

const cancelSignOut = () => {
  showConfirm.value = false
  signoutError.value = null
}

// Expose methods for parent components
defineExpose({
  initiateSignOut,
  isSigningOut
})
</script>

<template>
  <div class="signout-wrapper">
    <BaseButton
      :variant="variant"
      :icon="icon"
      :loading="isSigningOut"
      @click="initiateSignOut"
      class="signout-btn"
    >
      <span v-if="showText">Sign Out</span>
    </BaseButton>
    
    <ConfirmDialog
      :show="showConfirm"
      title="Sign Out"
      message="Are you sure you want to sign out? You'll need to log in again to access your dashboard."
      confirm-text="Sign Out"
      cancel-text="Cancel"
      confirm-variant="primary"
      :loading="isSigningOut"
      @confirm="performSignOut"
      @cancel="cancelSignOut"
      @close="cancelSignOut"
    />
  </div>
</template>

<style scoped>
.signout-wrapper {
  display: contents;
}

.signout-btn {
  transition: all 0.2s ease;
}

.signout-btn:hover:not(:disabled) {
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.signout-btn:active:not(:disabled) {
  transform: translateY(0);
}
</style>