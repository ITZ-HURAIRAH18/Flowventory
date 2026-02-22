<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useRoute } from 'vue-router'
import { useAuth } from '@/composables/useAuth'
import SignOutButton from '@/components/ui/SignOutButton.vue'

const route = useRoute()
const { 
  user, 
  userInitials, 
  roleLabel, 
  roleBadgeClass, 
  navigationLinks,
  logout
} = useAuth()

const mobileMenuOpen = ref(false)
const userMenuOpen = ref(false)
const scrolled = ref(false)

onMounted(() => {
  window.addEventListener('scroll', handleScroll)
})

onUnmounted(() => {
  window.removeEventListener('scroll', handleScroll)
})

const handleScroll = () => {
  scrolled.value = window.scrollY > 10
}

// ─── Check if a link is active ───
const isActive = (path) => {
  if (path === '/dashboard') return route.path === '/dashboard'
  return route.path.startsWith(path)
}

// ─── Toggle mobile menu ───
const toggleMobileMenu = () => {
  mobileMenuOpen.value = !mobileMenuOpen.value
}

// ─── Toggle user dropdown ───
const toggleUserMenu = () => {
  userMenuOpen.value = !userMenuOpen.value
}

// Close user menu on click outside
const closeUserMenu = () => {
  userMenuOpen.value = false
}

// Handle successful logout from SignOutButton
const handleSignOutSuccess = () => {
  userMenuOpen.value = false
  mobileMenuOpen.value = false
}

// Emergency fallback logout for mobile
const emergencyLogout = async () => {
  try {
    await logout()
    mobileMenuOpen.value = false
  } catch (error) {
    console.error('Emergency logout failed:', error)
  }
}
</script>

<template>
  <header class="app-header" :class="{ 'header-scrolled': scrolled }">
    <div class="header-inner">

      <!-- ═══ Logo / Brand ═══ -->
      <router-link to="/dashboard" class="brand">
        <span class="brand-icon">
          <span class="material-symbols-outlined">inventory</span>
        </span>
        <span class="brand-text">Flow<span class="brand-accent">ventory</span></span>
      </router-link>

      <!-- ═══ Desktop Navigation ═══ -->
      <nav class="nav-desktop">
        <router-link
          v-for="link in navigationLinks"
          :key="link.path"
          :to="link.path"
          class="nav-link"
          :class="{ active: isActive(link.path) }"
        >
          <span class="material-symbols-outlined nav-icon">{{ link.icon }}</span>
          <span class="nav-label">{{ link.label }}</span>
        </router-link>
      </nav>

      <!-- ═══ Right Section: User Info ═══ -->
      <div class="header-right">

        <!-- Role badge -->
        <span class="role-badge" :class="roleBadgeClass">
          {{ roleLabel }}
        </span>

        <!-- User avatar + dropdown -->
        <div class="user-menu-wrapper" @click.stop="toggleUserMenu" v-if="user">
          <div class="user-avatar">
            {{ userInitials }}
          </div>

          <!-- Dropdown -->
          <transition name="dropdown">
            <div v-if="userMenuOpen" class="user-dropdown" @click.stop>
              <div class="dropdown-header">
                <div class="dropdown-avatar">{{ userInitials }}</div>
                <div class="dropdown-info">
                  <span class="dropdown-name">{{ user.name }}</span>
                  <span class="dropdown-email">{{ user.email }}</span>
                </div>
              </div>
              <div class="dropdown-divider"></div>
              <div class="dropdown-actions">
                <SignOutButton 
                  variant="ghost"
                  :show-text="true"
                  class="dropdown-signout"
                  @signout-success="handleSignOutSuccess"
                />
              </div>
            </div>
          </transition>
        </div>

        <!-- Mobile hamburger -->
        <button class="mobile-toggle" @click="toggleMobileMenu" aria-label="Toggle menu">
          <span class="material-symbols-outlined">
            {{ mobileMenuOpen ? 'close' : 'menu' }}
          </span>
        </button>
      </div>
    </div>

    <!-- ═══ Mobile Navigation Drawer ═══ -->
    <transition name="slide">
      <div v-if="mobileMenuOpen" class="nav-mobile">
        <router-link
          v-for="link in navigationLinks"
          :key="link.path"
          :to="link.path"
          class="nav-link-mobile"
          :class="{ active: isActive(link.path) }"
          @click="mobileMenuOpen = false"
        >
          <span class="material-symbols-outlined">{{ link.icon }}</span>
          {{ link.label }}
        </router-link>

        <div class="mobile-divider"></div>

        <div class="mobile-signout">
          <SignOutButton 
            variant="ghost"
            :show-text="true"
            class="mobile-signout-btn"
            @signout-success="handleSignOutSuccess"
          />
        </div>
      </div>
    </transition>
  </header>

  <!-- Click-outside overlay to close user menu -->
  <div v-if="userMenuOpen" class="overlay" @click="closeUserMenu"></div>
</template>

<style scoped>
@import "../styles/components/AppHeader.css";
</style>
