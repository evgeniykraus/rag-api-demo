<template>
  <div class="min-h-screen bg-gray-50">
    <!-- Header -->
    <header class="bg-white shadow-sm border-b border-gray-200">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center h-16">
          <!-- Logo -->
          <div class="flex items-center">
            <div class="flex-shrink-0">
              <h1 class="text-xl font-bold text-gray-900">
                Система обращений
              </h1>
            </div>
          </div>

          <!-- Theme toggle -->
          <div class="flex items-center space-x-4">
            <button
              @click="toggleTheme"
              class="p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100"
            >
              <SunIcon v-if="theme === 'dark'" class="h-5 w-5" />
              <MoonIcon v-else class="h-5 w-5" />
            </button>
          </div>
        </div>
      </div>
    </header>

    <!-- Main content -->
    <div class="flex">
      <!-- Sidebar -->
      <div
        v-if="sidebarOpen"
        class="fixed inset-0 z-40 lg:hidden"
        @click="setSidebarOpen(false)"
      >
        <div class="fixed inset-0 bg-gray-600 bg-opacity-75"></div>
      </div>

      <div
        :class="[
          'fixed inset-y-0 left-0 z-50 w-64 bg-white shadow-lg transform transition-transform duration-300 ease-in-out lg:translate-x-0 lg:static lg:inset-0',
          sidebarOpen ? 'translate-x-0' : '-translate-x-full'
        ]"
      >
        <div class="flex flex-col h-full">
          <div class="flex items-center justify-between h-16 px-4 border-b border-gray-200">
            <h2 class="text-lg font-semibold text-gray-900">Меню</h2>
            <button
              @click="setSidebarOpen(false)"
              class="p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100"
            >
              <XMarkIcon class="h-5 w-5" />
            </button>
          </div>
          
          <nav class="flex-1 px-4 py-4 space-y-2">
            <RouterLink
              to="/"
              @click="setSidebarOpen(false)"
              class="flex items-center px-3 py-2 text-sm font-medium rounded-md text-gray-600 hover:text-gray-900 hover:bg-gray-50"
              :class="{ 'text-primary-600 bg-primary-50': $route.name === 'Dashboard' }"
            >
              <HomeIcon class="mr-3 h-5 w-5" />
              Главная
            </RouterLink>
            <RouterLink
              to="/proposals"
              @click="setSidebarOpen(false)"
              class="flex items-center px-3 py-2 text-sm font-medium rounded-md text-gray-600 hover:text-gray-900 hover:bg-gray-50"
              :class="{ 'text-primary-600 bg-primary-50': $route.name === 'Proposals' }"
            >
              <DocumentTextIcon class="mr-3 h-5 w-5" />
              Обращения
            </RouterLink>
            <RouterLink
              to="/search"
              @click="setSidebarOpen(false)"
              class="flex items-center px-3 py-2 text-sm font-medium rounded-md text-gray-600 hover:text-gray-900 hover:bg-gray-50"
              :class="{ 'text-primary-600 bg-primary-50': $route.name === 'Search' }"
            >
              <MagnifyingGlassIcon class="mr-3 h-5 w-5" />
              Поиск
            </RouterLink>
            <RouterLink
              to="/analytics"
              @click="setSidebarOpen(false)"
              class="flex items-center px-3 py-2 text-sm font-medium rounded-md text-gray-600 hover:text-gray-900 hover:bg-gray-50"
              :class="{ 'text-primary-600 bg-primary-50': $route.name === 'Analytics' }"
            >
              <ChartBarIcon class="mr-3 h-5 w-5" />
              Аналитика
            </RouterLink>
          </nav>
        </div>
      </div>

      <!-- Page content -->
      <main class="flex-1 lg:ml-0">
        <div class="py-6">
          <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <slot />
          </div>
        </div>
      </main>
    </div>

    <!-- Notifications -->
    <NotificationContainer />
  </div>
</template>

<script setup lang="ts">
import { onMounted } from 'vue'
import { useUIStore } from '@/stores/ui'
import {
  XMarkIcon,
  HomeIcon,
  DocumentTextIcon,
  MagnifyingGlassIcon,
  ChartBarIcon,
  SunIcon,
  MoonIcon
} from '@heroicons/vue/24/outline'
import NotificationContainer from './NotificationContainer.vue'

const uiStore = useUIStore()
const { sidebarOpen, theme, setSidebarOpen, toggleTheme, initTheme } = uiStore

onMounted(() => {
  initTheme()
})
</script>

