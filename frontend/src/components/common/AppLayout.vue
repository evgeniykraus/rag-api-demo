<template>
  <div class="min-h-screen bg-gray-50 dark:bg-gray-900 flex flex-col overflow-x-hidden">
    <!-- Header -->
    <header class="bg-white shadow-sm border-b border-gray-200 dark:bg-gray-800 dark:border-gray-700 flex-shrink-0 overflow-x-hidden">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-16 relative min-w-0">
          <!-- Left side - Mobile menu button -->
          <div class="flex items-center space-x-2 flex-shrink-0">
            <button
              @click="setSidebarOpen(true)"
              class="lg:hidden relative inline-flex items-center justify-center p-2 rounded-lg text-gray-500 hover:text-gray-700 hover:bg-gray-100 dark:text-gray-400 dark:hover:text-gray-200 dark:hover:bg-gray-700 transition-all duration-200 focus:outline-none"
              title="Открыть меню"
            >
              <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
              </svg>
            </button>
          </div>

          <!-- Center - Logo -->
          <div class="flex items-center min-w-0 flex-1 justify-center">
            <div class="flex-shrink-0">
              <h1 class="text-xl font-bold text-gray-900 dark:text-gray-100 truncate">
                Система обращений
              </h1>
            </div>
          </div>

          <!-- Right side - Theme toggle -->
          <div class="flex items-center space-x-2 flex-shrink-0">
            <button
              @click="toggleTheme"
              class="relative inline-flex items-center justify-center p-2 rounded-lg text-gray-500 hover:text-gray-700 hover:bg-gray-100 dark:text-gray-400 dark:hover:text-gray-200 dark:hover:bg-gray-700 transition-all duration-200 focus:outline-none"
              :title="tooltipText"
            >
              <component :is="currentIcon" class="h-5 w-5 transition-all duration-300 rotate-0" />
            </button>
          </div>
        </div>
      </div>
    </header>

    <!-- Main content -->
    <div class="flex flex-1 min-h-0 overflow-x-hidden">
      <!-- Sidebar -->
      <div
        v-if="sidebarOpen"
        class="fixed inset-0 z-40 lg:hidden"
        @click="setSidebarOpen(false)"
      >
        <div class="fixed inset-0 bg-gray-600 bg-opacity-75 dark:bg-gray-900 dark:bg-opacity-90"></div>
      </div>

      <div
        :class="[
          'fixed inset-y-0 left-0 z-50 w-64 bg-white shadow-lg transform transition-transform duration-300 ease-in-out lg:translate-x-0 lg:static lg:inset-0 dark:bg-gray-800',
          sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'
        ]"
      >
        <div class="flex flex-col h-full">
          <div class="flex items-center justify-between h-16 px-4 border-b border-gray-200 dark:border-gray-700">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Меню</h2>
            <button
              @click="setSidebarOpen(false)"
              class="lg:hidden p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 dark:text-gray-500 dark:hover:text-gray-400 dark:hover:bg-gray-700 focus:outline-none"
              type="button"
            >
              <XMarkIcon class="h-5 w-5" />
            </button>
          </div>

          <nav class="flex-1 px-4 py-4 space-y-2">
            <RouterLink
              to="/"
              @click="closeSidebarOnMobile"
              class="flex items-center px-3 py-2 text-sm font-medium rounded-md text-gray-600 hover:text-gray-900 hover:bg-gray-50 dark:text-gray-300 dark:hover:text-gray-100 dark:hover:bg-gray-700"
              :class="{ 'text-primary-600 bg-primary-50 dark:text-primary-400 dark:bg-primary-900': $route.name === 'Dashboard' }"
            >
              <HomeIcon class="mr-3 h-5 w-5" />
              Главная
            </RouterLink>
            <RouterLink
              to="/proposals"
              @click="closeSidebarOnMobile"
              class="flex items-center px-3 py-2 text-sm font-medium rounded-md text-gray-600 hover:text-gray-900 hover:bg-gray-50 dark:text-gray-300 dark:hover:text-gray-100 dark:hover:bg-gray-700"
              :class="{ 'text-primary-600 bg-primary-50 dark:text-primary-400 dark:bg-primary-900': $route.name === 'Proposals' }"
            >
              <DocumentTextIcon class="mr-3 h-5 w-5" />
              Обращения
            </RouterLink>
            <RouterLink
              v-if="false"
              to="/search"
              @click="closeSidebarOnMobile"
              class="flex items-center px-3 py-2 text-sm font-medium rounded-md text-gray-600 hover:text-gray-900 hover:bg-gray-50"
              :class="{ 'text-primary-600 bg-primary-50': $route.name === 'Search' }"
            >
              <span class="sr-only">Поиск (удалено)</span>
            </RouterLink>
            <RouterLink
              to="/analytics"
              @click="closeSidebarOnMobile"
              class="flex items-center px-3 py-2 text-sm font-medium rounded-md text-gray-600 hover:text-gray-900 hover:bg-gray-50 dark:text-gray-300 dark:hover:text-gray-100 dark:hover:bg-gray-700"
              :class="{ 'text-primary-600 bg-primary-50 dark:text-primary-400 dark:bg-primary-900': $route.name === 'Analytics' }"
            >
              <ChartBarIcon class="mr-3 h-5 w-5" />
              Аналитика
            </RouterLink>
          </nav>
        </div>
      </div>

      <!-- Page content -->
      <main class="flex-1 lg:ml-0 flex flex-col min-h-0 overflow-x-hidden">
        <div class="py-6 flex-1 min-h-0">
          <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-full overflow-x-hidden">
            <slot />
          </div>
        </div>
      </main>
    </div>

    <!-- Footer -->
    <footer class="bg-white border-t border-gray-200 dark:bg-gray-800 dark:border-gray-700 flex-shrink-0 overflow-x-hidden">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
          <!-- Company Info -->
          <div class="space-y-4">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
              Система обращений
            </h3>
            <p class="text-sm text-gray-600 dark:text-gray-400">
              Современная платформа для управления обращениями граждан с использованием искусственного интеллекта.
            </p>
          </div>

          <!-- Quick Links -->
          <div class="space-y-4">
            <h4 class="text-sm font-semibold text-gray-900 dark:text-gray-100 uppercase tracking-wider">
              Навигация
            </h4>
            <div class="space-y-2">
              <RouterLink to="/" class="block text-sm text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-100 transition-colors">
                Главная
              </RouterLink>
              <RouterLink to="/proposals" class="block text-sm text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-100 transition-colors">
                Обращения
              </RouterLink>
              <RouterLink to="/analytics" class="block text-sm text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-100 transition-colors">
                Аналитика
              </RouterLink>
            </div>
          </div>

          <!-- Contact Info -->
          <div class="space-y-4">
            <h4 class="text-sm font-semibold text-gray-900 dark:text-gray-100 uppercase tracking-wider">
              Контакты
            </h4>
            <div class="space-y-2">
              <div class="text-sm text-gray-600 dark:text-gray-400">
                Техническая поддержка
              </div>
              <div class="text-sm text-gray-600 dark:text-gray-400">
                support@example.com
              </div>
              <div class="text-sm text-gray-600 dark:text-gray-400">
                +7 (800) 123-45-67
              </div>
            </div>
          </div>
        </div>

        <!-- Bottom Bar -->
        <div class="mt-8 pt-6 border-t border-gray-200 dark:border-gray-700">
          <div class="flex flex-col md:flex-row justify-between items-center space-y-4 md:space-y-0">
            <div class="text-sm text-gray-500 dark:text-gray-400">
              © 2025 Система обращений граждан. Все права защищены.
            </div>
            <div class="flex items-center space-x-6">
<!--              <div class="text-sm text-gray-500 dark:text-gray-400">-->
<!--                Версия 1.0.0-->
<!--              </div>-->
              <div class="text-sm text-gray-500 dark:text-gray-400">
                Powered by [SE]Team
              </div>
            </div>
          </div>
        </div>
      </div>
    </footer>

    <!-- Notifications -->
    <NotificationContainer />
  </div>
</template>

<script setup lang="ts">
import { onMounted, onUnmounted, computed, nextTick, ref } from 'vue'
import { useUIStore } from '@/stores/ui'
import { storeToRefs } from 'pinia'
import {
  XMarkIcon,
  HomeIcon,
  DocumentTextIcon,
  ChartBarIcon,
  SunIcon,
  MoonIcon
} from '@heroicons/vue/24/outline'
import NotificationContainer from './NotificationContainer.vue'

const uiStore = useUIStore()
const { setSidebarOpen, toggleTheme, initTheme } = uiStore
const { theme } = storeToRefs(uiStore)

// Use computed for sidebarOpen to ensure reactivity
const sidebarOpen = computed(() => {
  return uiStore.sidebarOpen
})

// Screen size detection
const isMobile = ref(false)

// Computed properties for reactive icons
const currentIcon = computed(() => {
  return theme.value === 'dark' ? MoonIcon : SunIcon
})

const themeText = computed(() => {
  return theme.value === 'dark' ? 'Светлая тема' : 'Тёмная тема'
})

const tooltipText = computed(() => {
  return theme.value === 'dark' ? 'Переключить на светлую тему' : 'Переключить на тёмную тему'
})

// Function to close sidebar on mobile
function closeSidebarOnMobile() {
  // Always close sidebar on mobile devices
  if (typeof window !== 'undefined' && window.innerWidth < 1024) {
    setSidebarOpen(false)
  }
}

// Function to check screen size
function checkScreenSize() {
  if (typeof window !== 'undefined') {
    isMobile.value = window.innerWidth < 1024
  }
}

onMounted(() => {
  initTheme()
  setSidebarOpen(false)
  checkScreenSize()

  // Listen for window resize
  window.addEventListener('resize', checkScreenSize)
})

onUnmounted(() => {
  if (typeof window !== 'undefined') {
    window.removeEventListener('resize', checkScreenSize)
  }
})
</script>

