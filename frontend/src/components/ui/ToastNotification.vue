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
  clearTimer()
  visible.value = false
  // Emit close event immediately for better responsiveness
  emit('close')
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
@import "../../styles/ui/ToastNotification.css";
</style>