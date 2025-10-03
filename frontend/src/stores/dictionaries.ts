import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import apiClient from '@/services/api'
import type { City, CategoryTree } from '@/types'

export const useDictionariesStore = defineStore('dictionaries', () => {
  // State
  const cities = ref<City[]>([])
  const categories = ref<CategoryTree[]>([])
  const loading = ref(false)
  const error = ref<string | null>(null)

  // Getters
  const citiesOptions = computed(() => 
    cities.value.map(city => ({
      value: city.id,
      label: city.name
    }))
  )

  const categoriesOptions = computed(() => {
    const flattenCategories = (cats: CategoryTree[]): Array<{ value: number; label: string; parent?: string }> => {
      const result: Array<{ value: number; label: string; parent?: string }> = []
      
      cats.forEach(cat => {
        result.push({
          value: cat.id,
          label: cat.name,
          parent: undefined
        })
        
        if (cat.children && cat.children.length > 0) {
          cat.children.forEach(child => {
            result.push({
              value: child.id,
              label: child.name,
              parent: cat.name
            })
          })
        }
      })
      
      return result
    }
    
    return flattenCategories(categories.value)
  })

  const categoriesTree = computed(() => categories.value)

  // Actions
  async function fetchCities() {
    if (cities.value.length > 0) return // Cache cities
    
    try {
      loading.value = true
      error.value = null
      
      cities.value = await apiClient.getCities()
    } catch (err: any) {
      error.value = err.message || 'Ошибка загрузки городов'
      console.error('Error fetching cities:', err)
    } finally {
      loading.value = false
    }
  }

  async function fetchCategories() {
    if (categories.value.length > 0) return // Cache categories
    
    try {
      loading.value = true
      error.value = null
      
      categories.value = await apiClient.getCategories()
    } catch (err: any) {
      error.value = err.message || 'Ошибка загрузки категорий'
      console.error('Error fetching categories:', err)
    } finally {
      loading.value = false
    }
  }

  async function loadDictionaries() {
    await Promise.all([
      fetchCities(),
      fetchCategories()
    ])
  }

  function getCityById(id: number): City | undefined {
    return cities.value.find(city => city.id === id)
  }

  function getCategoryById(id: number): CategoryTree | undefined {
    const findInTree = (cats: CategoryTree[]): CategoryTree | undefined => {
      for (const cat of cats) {
        if (cat.id === id) return cat
        if (cat.children) {
          const found = findInTree(cat.children)
          if (found) return found
        }
      }
      return undefined
    }
    
    return findInTree(categories.value)
  }

  function getCategoryPath(id: number): string[] {
    const findPath = (cats: CategoryTree[], targetId: number, path: string[] = []): string[] | null => {
      for (const cat of cats) {
        const currentPath = [...path, cat.name]
        
        if (cat.id === targetId) {
          return currentPath
        }
        
        if (cat.children) {
          const found = findPath(cat.children, targetId, currentPath)
          if (found) return found
        }
      }
      return null
    }
    
    return findPath(categories.value, id) || []
  }

  function clearError() {
    error.value = null
  }

  return {
    // State
    cities,
    categories,
    loading,
    error,
    
    // Getters
    citiesOptions,
    categoriesOptions,
    categoriesTree,
    
    // Actions
    fetchCities,
    fetchCategories,
    loadDictionaries,
    getCityById,
    getCategoryById,
    getCategoryPath,
    clearError
  }
})

