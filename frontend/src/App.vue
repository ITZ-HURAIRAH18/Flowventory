<script setup>
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import AppHeader from '@/components/AppHeader.vue'

const route = useRoute()

// Only show header on authenticated pages (not the login page)
const showHeader = computed(() => {
  return route.path !== '/login' && route.path !== '/'
})
</script>

<template>
  <div id="app">
    <AppHeader v-if="showHeader" />
    <main class="app-main" :class="{ 'with-header': showHeader }">
      <router-view />
    </main>
  </div>
</template>

<style>
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap');

#app {
  font-family: 'Inter', system-ui, -apple-system, sans-serif;
  min-height: 100vh;
  background: #0f1117;
  color: #e2e8f0;
}

.app-main {
  max-width: 1400px;
  margin: 0 auto;
  padding: 2rem 1.5rem;
}

.app-main.with-header {
  padding-top: 1.5rem;
}
</style>