<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import api from '@/services/api'

const router = useRouter()
const route = useRoute()

const user = ref(null)
const mobileMenuOpen = ref(false)
const userMenuOpen = ref(false)
const scrolled = ref(false)

// ─── Load user from localStorage ───
onMounted(() => {
  const stored = localStorage.getItem('user')
  if (stored) {
    user.value = JSON.parse(stored)
  }
  window.addEventListener('scroll', handleScroll)
})

onUnmounted(() => {
  window.removeEventListener('scroll', handleScroll)
})

const handleScroll = () => {
  scrolled.value = window.scrollY > 10
}

// ─── Computed: current role name ───
const roleName = computed(() => user.value?.role?.name || '')

// ─── Computed: human-readable role label ───
const roleLabel = computed(() => {
  const map = {
    super_admin: 'Super Admin',
    branch_manager: 'Branch Manager',
    sales_user: 'Sales User'
  }
  return map[roleName.value] || roleName.value
})

// ─── Computed: role badge color class ───
const roleBadgeClass = computed(() => {
  const map = {
    super_admin: 'badge-admin',
    branch_manager: 'badge-manager',
    sales_user: 'badge-sales'
  }
  return map[roleName.value] || ''
})

// ─── Computed: user initials for avatar ───
const userInitials = computed(() => {
  if (!user.value?.name) return '?'
  return user.value.name
    .split(' ')
    .map(w => w[0])
    .join('')
    .toUpperCase()
    .slice(0, 2)
})

// ─── Navigation links per role ───
// Matches the assignment's Role Permissions table exactly:
//   Feature           | Admin | Manager              | Sales
//   Manage branches   | Yes   | No                   | No
//   Manage products   | Yes   | No                   | No
//   Manage inventory  | Yes   | Yes (own branch only) | No
//   Create orders     | No    | Yes                  | Yes
//   View reports      | Yes   | Yes (own branch only) | No
const navLinks = computed(() => {
  const links = []

  // Dashboard — visible to all authenticated users
  links.push({ label: 'Dashboard', path: '/dashboard', icon: 'dashboard' })

  if (roleName.value === 'super_admin') {
    links.push({ label: 'Branches', path: '/branches', icon: 'store' })
    links.push({ label: 'Products', path: '/products', icon: 'inventory_2' })
    links.push({ label: 'Users', path: '/users', icon: 'group' })
    links.push({ label: 'Inventory', path: '/inventory', icon: 'warehouse' })
    links.push({ label: 'Reports', path: '/reports', icon: 'bar_chart' })
    // Note: Admin does NOT have "Create Orders" per assignment spec
  }

  if (roleName.value === 'branch_manager') {
    links.push({ label: 'Inventory', path: '/inventory', icon: 'warehouse' })
    links.push({ label: 'Orders', path: '/orders/create', icon: 'receipt_long' })
    links.push({ label: 'Reports', path: '/reports', icon: 'bar_chart' })
  }

  if (roleName.value === 'sales_user') {
    links.push({ label: 'Create Order', path: '/orders/create', icon: 'receipt_long' })
  }

  return links
})

// ─── Check if a link is active ───
const isActive = (path) => {
  if (path === '/dashboard') return route.path === '/dashboard'
  return route.path.startsWith(path)
}

// ─── Logout ───
const logout = async () => {
  try {
    await api.post('/logout')
  } catch (e) {
    // Ignore errors — token might be expired
  }
  localStorage.removeItem('token')
  localStorage.removeItem('user')
  router.push('/login')
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
          v-for="link in navLinks"
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
              <button class="dropdown-item" @click="logout">
                <span class="material-symbols-outlined">logout</span>
                Sign Out
              </button>
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
          v-for="link in navLinks"
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

        <button class="nav-link-mobile logout-mobile" @click="logout">
          <span class="material-symbols-outlined">logout</span>
          Sign Out
        </button>
      </div>
    </transition>
  </header>

  <!-- Click-outside overlay to close user menu -->
  <div v-if="userMenuOpen" class="overlay" @click="closeUserMenu"></div>
</template>

<style scoped>
/* ═══════════════════════════════════════════
   GOOGLE MATERIAL SYMBOLS (loaded in index.html)
   ═══════════════════════════════════════════ */

/* ═══════════════════════════════════════════
   HEADER BASE
   ═══════════════════════════════════════════ */
.app-header {
  position: sticky;
  top: 0;
  z-index: 1000;
  background: rgba(250, 248, 246, 0.92);
  backdrop-filter: blur(18px);
  -webkit-backdrop-filter: blur(18px);
  border-bottom: 1px solid #EDE8E4;
  transition: all 0.3s ease;
}

.header-scrolled {
  background: rgba(250, 248, 246, 0.98);
  box-shadow: 0 4px 24px rgba(93, 64, 55, 0.1);
  border-bottom-color: #D7CCC8;
}

.header-inner {
  max-width: 1400px;
  margin: 0 auto;
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0 1.5rem;
  height: 64px;
}

/* ═══════════════════════════════════════════
   BRAND
   ═══════════════════════════════════════════ */
.brand {
  display: flex;
  align-items: center;
  gap: 0.6rem;
  text-decoration: none;
  font-weight: 700;
  font-size: 1.25rem;
  letter-spacing: -0.02em;
}

.brand-icon {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 36px;
  height: 36px;
  border-radius: 10px;
  background: linear-gradient(135deg, #5D4037, #795548);
  box-shadow: 0 2px 12px rgba(93, 64, 55, 0.28);
}

.brand-icon .material-symbols-outlined {
  font-size: 20px;
  color: #fff;
}

.brand-text {
  color: #3E2723;
}

.brand-accent {
  color: #8D6E63;
}

/* ═══════════════════════════════════════════
   DESKTOP NAVIGATION
   ═══════════════════════════════════════════ */
.nav-desktop {
  display: flex;
  align-items: center;
  gap: 0.25rem;
}

.nav-link {
  display: flex;
  align-items: center;
  gap: 0.4rem;
  padding: 0.5rem 0.9rem;
  border-radius: 8px;
  text-decoration: none;
  color: #795548;
  font-size: 0.875rem;
  font-weight: 500;
  transition: all 0.2s ease;
  position: relative;
}

.nav-link:hover {
  color: #3E2723;
  background: rgba(93, 64, 55, 0.06);
}

.nav-link.active {
  color: #3E2723;
  background: rgba(93, 64, 55, 0.1);
  font-weight: 700;
}

.nav-link.active::after {
  content: '';
  position: absolute;
  bottom: -1px;
  left: 50%;
  transform: translateX(-50%);
  width: 20px;
  height: 2px;
  background: #5D4037;
  border-radius: 2px;
}

.nav-icon {
  font-size: 18px;
}

/* ═══════════════════════════════════════════
   RIGHT SECTION
   ═══════════════════════════════════════════ */
.header-right {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

/* ─── Role Badge ─── */
.role-badge {
  padding: 0.25rem 0.75rem;
  border-radius: 20px;
  font-size: 0.7rem;
  font-weight: 600;
  letter-spacing: 0.04em;
  text-transform: uppercase;
}

.badge-admin {
  background: rgba(93, 64, 55, 0.1);
  color: #5D4037;
  border: 1px solid rgba(93, 64, 55, 0.2);
}

.badge-manager {
  background: rgba(121, 85, 72, 0.1);
  color: #795548;
  border: 1px solid rgba(121, 85, 72, 0.2);
}

.badge-sales {
  background: rgba(141, 110, 99, 0.1);
  color: #8D6E63;
  border: 1px solid rgba(141, 110, 99, 0.2);
}

/* ─── User Avatar ─── */
.user-menu-wrapper {
  position: relative;
  cursor: pointer;
}

.user-avatar {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  background: linear-gradient(135deg, #5D4037, #8D6E63);
  display: flex;
  align-items: center;
  justify-content: center;
  color: #fff;
  font-size: 0.75rem;
  font-weight: 700;
  letter-spacing: 0.02em;
  transition: box-shadow 0.2s ease;
}

.user-avatar:hover {
  box-shadow: 0 0 0 3px rgba(93, 64, 55, 0.25);
}

/* ─── User Dropdown ─── */
.user-dropdown {
  position: absolute;
  top: calc(100% + 10px);
  right: 0;
  width: 260px;
  background: #fff;
  border: 1px solid #EDE8E4;
  border-radius: 14px;
  box-shadow: 0 16px 48px rgba(93, 64, 55, 0.18);
  padding: 0.5rem;
  z-index: 1100;
}

.dropdown-header {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.75rem;
}

.dropdown-avatar {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  background: linear-gradient(135deg, #5D4037, #8D6E63);
  display: flex;
  align-items: center;
  justify-content: center;
  color: #fff;
  font-size: 0.8rem;
  font-weight: 700;
  flex-shrink: 0;
}

.dropdown-info {
  display: flex;
  flex-direction: column;
  overflow: hidden;
}

.dropdown-name {
  color: #3E2723;
  font-weight: 600;
  font-size: 0.9rem;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.dropdown-email {
  color: #A1887F;
  font-size: 0.78rem;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.dropdown-divider {
  height: 1px;
  background: #EDE8E4;
  margin: 0.25rem 0;
}

.dropdown-item {
  display: flex;
  align-items: center;
  gap: 0.6rem;
  width: 100%;
  padding: 0.65rem 0.75rem;
  border: none;
  background: transparent;
  color: #dc2626;
  font-size: 0.85rem;
  font-weight: 600;
  border-radius: 8px;
  cursor: pointer;
  transition: background 0.15s ease;
  font-family: inherit;
}

.dropdown-item .material-symbols-outlined {
  font-size: 18px;
}

.dropdown-item:hover {
  background: rgba(239, 68, 68, 0.08);
}

/* ─── Overlay for closing menus ─── */
.overlay {
  position: fixed;
  inset: 0;
  z-index: 999;
}

/* ═══════════════════════════════════════════
   MOBILE
   ═══════════════════════════════════════════ */
.mobile-toggle {
  display: none;
  background: transparent;
  border: none;
  color: #795548;
  cursor: pointer;
  padding: 0.4rem;
  border-radius: 8px;
  transition: color 0.2s;
}

.mobile-toggle:hover {
  color: #3E2723;
}

.nav-mobile {
  display: none;
  flex-direction: column;
  padding: 0.5rem 1rem 1rem;
  border-top: 1px solid #EDE8E4;
  background: #FAF8F6;
}

.nav-link-mobile {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.7rem 1rem;
  border-radius: 8px;
  text-decoration: none;
  color: #795548;
  font-size: 0.9rem;
  font-weight: 500;
  transition: all 0.2s ease;
  border: none;
  background: none;
  cursor: pointer;
  width: 100%;
  text-align: left;
  font-family: inherit;
}

.nav-link-mobile:hover,
.nav-link-mobile.active {
  color: #3E2723;
  background: rgba(93, 64, 55, 0.08);
}

.nav-link-mobile .material-symbols-outlined {
  font-size: 20px;
}

.mobile-divider {
  height: 1px;
  background: #EDE8E4;
  margin: 0.5rem 0;
}

.logout-mobile {
  color: #dc2626;
}

.logout-mobile:hover {
  background: rgba(239, 68, 68, 0.08);
  color: #dc2626;
}

/* ═══════════════════════════════════════════
   TRANSITIONS
   ═══════════════════════════════════════════ */
.dropdown-enter-active,
.dropdown-leave-active {
  transition: all 0.2s ease;
}
.dropdown-enter-from,
.dropdown-leave-to {
  opacity: 0;
  transform: translateY(-8px) scale(0.96);
}

.slide-enter-active,
.slide-leave-active {
  transition: all 0.25s ease;
}
.slide-enter-from,
.slide-leave-to {
  opacity: 0;
  max-height: 0;
}
.slide-enter-to,
.slide-leave-from {
  max-height: 500px;
}

/* ═══════════════════════════════════════════
   RESPONSIVE
   ═══════════════════════════════════════════ */
@media (max-width: 768px) {
  .nav-desktop {
    display: none;
  }

  .role-badge {
    display: none;
  }

  .mobile-toggle {
    display: flex;
  }

  .nav-mobile {
    display: flex;
  }
}
</style>
