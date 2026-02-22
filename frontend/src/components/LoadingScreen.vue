<template>
  <Teleport to="body">
    <transition name="loader-fade">
    <div v-if="visible" class="ls-screen" role="status" aria-label="Loading">

      <!-- Background particles -->
      <div class="ls-particle ls-p1"></div>
      <div class="ls-particle ls-p2"></div>
      <div class="ls-particle ls-p3"></div>
      <div class="ls-particle ls-p4"></div>
      <div class="ls-particle ls-p5"></div>
      <div class="ls-particle ls-p6"></div>

      <!-- Central content — no card box, cube is the hero -->
      <div class="ls-wrapper">

        <!-- ── Large 3-D Rotating Cube ── -->
        <div class="ls-scene">
          <div class="ls-cube">
            <div class="ls-face ls-front">
              <span class="material-symbols-outlined">inventory</span>
            </div>
            <div class="ls-face ls-back">
              <span class="material-symbols-outlined">package_2</span>
            </div>
            <div class="ls-face ls-right">
              <span class="material-symbols-outlined">warehouse</span>
            </div>
            <div class="ls-face ls-left">
              <span class="material-symbols-outlined">local_shipping</span>
            </div>
            <div class="ls-face ls-top">
              <span class="material-symbols-outlined">bar_chart</span>
            </div>
            <div class="ls-face ls-bottom">
              <span class="material-symbols-outlined">store</span>
            </div>
          </div>

          <!-- Rolling ground shadow -->
          <div class="ls-shadow"></div>
        </div>

        <!-- Brand -->
        <div class="ls-brand">
          <span class="ls-brand-text">Flow<span class="ls-accent">ventory</span></span>
        </div>

        <!-- Progress bar -->
        <div class="ls-bar-track">
          <div class="ls-bar-fill"></div>
        </div>

        <!-- Message + dots -->
        <div class="ls-status">
          <p class="ls-message">{{ message }}</p>
          <div class="ls-dots">
            <span class="ls-dot"></span>
            <span class="ls-dot"></span>
            <span class="ls-dot"></span>
          </div>
        </div>

      </div>
    </div>
    </transition>
  </Teleport>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue'

const props = defineProps({
  message: {
    type: String,
    default: 'Preparing your workspace…'
  },
  duration: {
    type: Number,
    default: 2800
  },
  show: {
    type: Boolean,
    default: true
  }
})

const emit = defineEmits(['update:show', 'done'])
const visible = ref(props.duration > 0 ? true : props.show)

watch(() => props.show, (v) => { if (props.duration === 0) visible.value = v })

onMounted(() => {
  if (props.duration > 0) {
    setTimeout(() => {
      visible.value = false
      emit('update:show', false)
      emit('done')
    }, props.duration)
  }
})
</script>

<style scoped>
@import "../styles/components/LoadingScreen.css";
</style>
