import { defineStore } from 'pinia'
import { ref } from 'vue'
import type { Notification, LoadingState } from '@/types'

export const useUIStore = defineStore('ui', () => {
  // State
  const notifications = ref<Notification[]>([])
  const loading = ref<LoadingState>({ isLoading: false })
  const sidebarOpen = ref(false)
  const theme = ref<'light' | 'dark'>('light')

  // Actions
  function showNotification(notification: Omit<Notification, 'id'>) {
    const id = Date.now().toString()
    const newNotification: Notification = {
      id,
      duration: 5000,
      ...notification
    }
    
    notifications.value.push(newNotification)
    
    if (newNotification.duration && newNotification.duration > 0) {
      setTimeout(() => {
        removeNotification(id)
      }, newNotification.duration)
    }
  }

  function removeNotification(id: string) {
    const index = notifications.value.findIndex(n => n.id === id)
    if (index > -1) {
      notifications.value.splice(index, 1)
    }
  }

  function clearNotifications() {
    notifications.value = []
  }

  function setLoading(isLoading: boolean, message?: string) {
    loading.value = { isLoading, message }
  }

  function toggleSidebar() {
    sidebarOpen.value = !sidebarOpen.value
  }

  function setSidebarOpen(open: boolean) {
    sidebarOpen.value = open
  }

  function toggleTheme() {
    theme.value = theme.value === 'light' ? 'dark' : 'light'
    localStorage.setItem('theme', theme.value)
    updateTheme()
  }

  function setTheme(newTheme: 'light' | 'dark') {
    theme.value = newTheme
    localStorage.setItem('theme', newTheme)
    updateTheme()
  }

  function updateTheme() {
    if (theme.value === 'dark') {
      document.documentElement.classList.add('dark')
    } else {
      document.documentElement.classList.remove('dark')
    }
  }

  function initTheme() {
    const savedTheme = localStorage.getItem('theme') as 'light' | 'dark'
    if (savedTheme) {
      setTheme(savedTheme)
    } else {
      // Detect system preference
      const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches
      setTheme(prefersDark ? 'dark' : 'light')
    }
  }

  // Notification helpers
  function showSuccess(title: string, message?: string) {
    showNotification({ type: 'success', title, message })
  }

  function showError(title: string, message?: string) {
    showNotification({ type: 'error', title, message })
  }

  function showWarning(title: string, message?: string) {
    showNotification({ type: 'warning', title, message })
  }

  function showInfo(title: string, message?: string) {
    showNotification({ type: 'info', title, message })
  }

  return {
    // State
    notifications,
    loading,
    sidebarOpen,
    theme,
    
    // Actions
    showNotification,
    removeNotification,
    clearNotifications,
    setLoading,
    toggleSidebar,
    setSidebarOpen,
    toggleTheme,
    setTheme,
    initTheme,
    updateTheme,
    
    // Helpers
    showSuccess,
    showError,
    showWarning,
    showInfo
  }
})

