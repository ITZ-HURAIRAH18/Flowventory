import { ref, readonly } from 'vue'

// Global toast state
const toasts = ref([])
let toastId = 0

// Toast management
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

// Convenience methods
const showSuccess = (title, message = '', options = {}) => {
  return addToast({ ...options, type: 'success', title, message })
}

const showError = (title, message = '', options = {}) => {
  return addToast({ ...options, type: 'error', title, message, duration: 7000 })
}

const showWarning = (title, message = '', options = {}) => {
  return addToast({ ...options, type: 'warning', title, message })
}

const showInfo = (title, message = '', options = {}) => {
  return addToast({ ...options, type: 'info', title, message })
}

// Create a singleton composable
export const useToast = () => {
  return {
    toasts: readonly(toasts),
    addToast,
    removeToast,
    clearAll,
    showSuccess,
    showError,
    showWarning,
    showInfo
  }
}

// Global toast emitter for use anywhere in the app
export const toast = {
  success: showSuccess,
  error: showError,
  warning: showWarning,
  info: showInfo,
  clear: clearAll
}

// For backward compatibility - window events
export const emitToast = (options) => {
  window.dispatchEvent(new CustomEvent('show-toast', {
    detail: options
  }))
}