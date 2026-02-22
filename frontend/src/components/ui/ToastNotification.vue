<script setup>
import { ref, computed, onMounted } from 'vue'

const props = defineProps({
  type: {
    type: String,
    default: 'info',
    validator: (value) => ['success', 'error', 'warning', 'info'].includes(value)
  },
  title: { type: String, required: true },
  message: { type: String, default: '' },
  duration: { type: Number, default: 5000 },
  showIcon: { type: Boolean, default: true },
  closable: { type: Boolean, default: true }
})

const emit = defineEmits(['close'])

const visible = ref(false)
const timeoutId = ref(null)

const iconMap = {
  success: 'check_circle',
  error: 'error',
  warning: 'warning',
  info: 'info'
}

const icon = computed(() => iconMap[props.type])

const toastClass = computed(() => ({
  [`toast-${props.type}`]: true,
  'toast-visible': visible.value
}))

const startTimer = () => {
  if (props.duration > 0) {
    timeoutId.value = setTimeout(() => {
      close()
    }, props.duration)
  }
}

const clearTimer = () => {
  if (timeoutId.value) {
    clearTimeout(timeoutId.value)
    timeoutId.value = null
  }
}

const close = () => {
  visible.value = false
  clearTimer()
  setTimeout(() => {
    emit('close')
  }, 300)
}

const handleMouseEnter = () => {
  clearTimer()
}

const handleMouseLeave = () => {
  startTimer()
}

onMounted(() => {
  visible.value = true
  startTimer()
})
</script>

<template>
  <Transition name="toast">
    <div
      v-if="visible"
      class="toast-item"
      :class="toastClass"
      @mouseenter="handleMouseEnter"
      @mouseleave="handleMouseLeave"
    >
      <div class="toast-content">
        <div v-if="showIcon" class="toast-icon">
          <span class="material-symbols-outlined">{{ icon }}</span>
        </div>
        
        <div class="toast-text">
          <h4 class="toast-title">{{ title }}</h4>
          <p v-if="message" class="toast-message">{{ message }}</p>
        </div>
        
        <button
          v-if="closable"
          class="toast-close"
          @click="close"
          aria-label="Close notification"
        >
          <span class="material-symbols-outlined">close</span>
        </button>
      </div>
      
      <!-- Progress bar for duration -->
      <div
        v-if="duration > 0"
        class="toast-progress"
        :style="{ animationDuration: `${duration}ms` }"
      ></div>
    </div>
  </Transition>
</template>

<style scoped>
.toast-item {
  background: white;
  border-radius: 8px;
  box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
  border: 1px solid #e5e7eb;
  margin-bottom: 0.5rem;
  min-width: 320px;
  max-width: 480px;
  overflow: hidden;
  transform: translateX(100%);
  opacity: 0;
  transition: all 0.3s ease-out;
  position: relative;
}

.toast-visible {
  transform: translateX(0);
  opacity: 1;
}

.toast-content {
  display: flex;
  align-items: flex-start;
  gap: 0.75rem;
  padding: 1rem;
}

.toast-icon {
  flex-shrink: 0;
  width: 24px;
  height: 24px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.toast-icon .material-symbols-outlined {
  font-size: 20px;
}

.toast-text {
  flex: 1;
  min-width: 0;
}

.toast-title {
  margin: 0;
  font-size: 0.875rem;
  font-weight: 600;
  line-height: 1.25;
  color: #111827;
}

.toast-message {
  margin: 0.25rem 0 0;
  font-size: 0.8rem;
  line-height: 1.4;
  color: #6b7280;
}

.toast-close {
  flex-shrink: 0;
  background: none;
  border: none;
  padding: 0;
  width: 20px;
  height: 20px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #9ca3af;
  cursor: pointer;
  border-radius: 4px;
  transition: color 0.2s ease;
}

.toast-close:hover {
  color: #6b7280;
}

.toast-close .material-symbols-outlined {
  font-size: 16px;
}

.toast-progress {
  position: absolute;
  bottom: 0;
  left: 0;
  height: 3px;
  background: currentColor;
  width: 100%;
  transform-origin: left;
  animation: toast-progress linear forwards;
  opacity: 0.3;
}

@keyframes toast-progress {
  from { transform: scaleX(1); }
  to { transform: scaleX(0); }
}

/* Type-specific styles */
.toast-success {
  border-left: 4px solid #10b981;
}

.toast-success .toast-icon {
  color: #10b981;
}

.toast-success .toast-progress {
  background: #10b981;
}

.toast-error {
  border-left: 4px solid #ef4444;
}

.toast-error .toast-icon {
  color: #ef4444;
}

.toast-error .toast-progress {
  background: #ef4444;
}

.toast-warning {
  border-left: 4px solid #f59e0b;
}

.toast-warning .toast-icon {
  color: #f59e0b;
}

.toast-warning .toast-progress {
  background: #f59e0b;
}

.toast-info {
  border-left: 4px solid #3b82f6;
}

.toast-info .toast-icon {
  color: #3b82f6;
}

.toast-info .toast-progress {
  background: #3b82f6;
}

/* Transitions */
.toast-enter-active {
  transition: all 0.3s ease-out;
}

.toast-leave-active {
  transition: all 0.3s ease-in;
}

.toast-enter-from {
  transform: translateX(100%);
  opacity: 0;
}

.toast-leave-to {
  transform: translateX(100%);
  opacity: 0;
}

/* Dark mode support */
@media (prefers-color-scheme: dark) {
  .toast-item {
    background: #1f2937;
    border-color: #374151;
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.3), 0 4px 6px -2px rgba(0, 0, 0, 0.2);
  }
  
  .toast-title {
    color: #f9fafb;
  }
  
  .toast-message {
    color: #d1d5db;
  }
  
  .toast-close {
    color: #9ca3af;
  }
  
  .toast-close:hover {
    color: #d1d5db;
  }
}
</style>