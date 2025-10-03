<template>
  <AppLayout>
    <div class="space-y-6">
      <!-- Header -->
      <div class="md:flex md:items-center md:justify-between">
        <div class="flex-1 min-w-0">
          <h2 class="text-2xl font-bold leading-7 text-gray-900 sm:text-3xl sm:truncate">
            Поиск обращений
          </h2>
          <p class="mt-1 text-sm text-gray-500">
            Семантический поиск по содержанию обращений
          </p>
        </div>
      </div>

      <!-- Search Form -->
      <div class="bg-white shadow rounded-lg p-6">
        <div class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
              Поисковый запрос
            </label>
            <div class="flex space-x-4">
              <div class="flex-1">
                <input
                  v-model="searchQuery"
                  type="text"
                  placeholder="Введите описание проблемы или ключевые слова..."
                  class="input"
                  @keyup.enter="handleSearch"
                />
              </div>
              <button
                @click="handleSearch"
                :disabled="loading || !searchQuery.trim()"
                class="btn btn-primary btn-md"
              >
                <MagnifyingGlassIcon class="h-5 w-5 mr-2" />
                Поиск
              </button>
            </div>
            <p class="mt-2 text-sm text-gray-500">
              Поиск работает по смыслу, а не только по ключевым словам
            </p>
          </div>

          <!-- Advanced Filters -->
          <div v-if="showAdvancedFilters" class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">
                Город
              </label>
              <select
                v-model="filters.city_id"
                class="input"
              >
                <option value="">Все города</option>
                <option
                  v-for="city in dictionariesStore.citiesOptions"
                  :key="city.value"
                  :value="city.value"
                >
                  {{ city.label }}
                </option>
              </select>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">
                Категория
              </label>
              <select
                v-model="filters.category_id"
                class="input"
              >
                <option value="">Все категории</option>
                <option
                  v-for="category in dictionariesStore.categoriesOptions"
                  :key="category.value"
                  :value="category.value"
                >
                  {{ category.parent ? `${category.parent} - ` : '' }}{{ category.label }}
                </option>
              </select>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">
                Период
              </label>
              <select
                v-model="filters.period"
                class="input"
              >
                <option value="">За все время</option>
                <option value="today">Сегодня</option>
                <option value="week">За неделю</option>
                <option value="month">За месяц</option>
                <option value="year">За год</option>
              </select>
            </div>
          </div>

          <div class="flex justify-between items-center">
            <button
              @click="showAdvancedFilters = !showAdvancedFilters"
              class="text-sm text-primary-600 hover:text-primary-900"
            >
              {{ showAdvancedFilters ? 'Скрыть' : 'Показать' }} расширенные фильтры
            </button>
            
            <div class="flex space-x-2">
              <button
                v-if="hasResults"
                @click="clearSearch"
                class="btn btn-secondary btn-sm"
              >
                Очистить
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Loading State -->
      <div v-if="loading" class="flex justify-center py-12">
        <LoadingSpinner message="Поиск обращений..." />
      </div>

      <!-- No Results -->
      <div v-else-if="!loading && searchResults.length === 0 && hasSearched" class="text-center py-12">
        <MagnifyingGlassIcon class="mx-auto h-12 w-12 text-gray-400" />
        <h3 class="mt-2 text-sm font-medium text-gray-900">Ничего не найдено</h3>
        <p class="mt-1 text-sm text-gray-500">
          Попробуйте изменить поисковый запрос или использовать другие фильтры
        </p>
      </div>

      <!-- Search Results -->
      <div v-else-if="searchResults.length > 0" class="space-y-4">
        <div class="flex items-center justify-between">
          <h3 class="text-lg font-medium text-gray-900">
            Результаты поиска ({{ searchResults.length }})
          </h3>
          <div class="flex items-center space-x-2">
            <label class="text-sm text-gray-700">Сортировка:</label>
            <select
              v-model="sortBy"
              class="input text-sm"
            >
              <option value="relevance">По релевантности</option>
              <option value="date">По дате</option>
              <option value="city">По городу</option>
            </select>
          </div>
        </div>

        <div class="space-y-4">
          <div
            v-for="(proposal, index) in sortedResults"
            :key="proposal.id"
            class="bg-white shadow rounded-lg p-6 hover:shadow-md transition-shadow"
          >
            <div class="flex items-start justify-between">
              <div class="flex-1 min-w-0">
                <div class="flex items-center space-x-2 mb-2">
                  <span class="text-sm font-medium text-gray-500">#{{ proposal.id }}</span>
                  <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                    {{ proposal.city.name }}
                  </span>
                  <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                    {{ proposal.category.name }}
                  </span>
                </div>
                
                <p class="text-gray-900 mb-3 line-clamp-3">
                  {{ proposal.content }}
                </p>
                
                <div class="flex items-center space-x-4 text-sm text-gray-500">
                  <span class="flex items-center">
                    <ClockIcon class="h-4 w-4 mr-1" />
                    {{ formatDate(proposal.created_at) }}
                  </span>
                  <span v-if="proposal.similarity" class="flex items-center">
                    <ChartBarIcon class="h-4 w-4 mr-1" />
                    Релевантность: {{ Math.round(proposal.similarity * 100) }}%
                  </span>
                </div>
              </div>
              
              <div class="ml-4 flex-shrink-0">
                <RouterLink
                  :to="`/proposals/${proposal.id}`"
                  class="btn btn-primary btn-sm"
                >
                  Подробнее
                </RouterLink>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Search Tips -->
      <div v-if="!hasSearched" class="bg-gray-50 rounded-lg p-6">
        <h3 class="text-lg font-medium text-gray-900 mb-4">Советы по поиску</h3>
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
          <div>
            <h4 class="text-sm font-medium text-gray-700 mb-2">Примеры запросов:</h4>
            <ul class="text-sm text-gray-600 space-y-1">
              <li>• "не вывозят мусор"</li>
              <li>• "разбитая дорога"</li>
              <li>• "отсутствует освещение"</li>
              <li>• "сломана детская площадка"</li>
            </ul>
          </div>
          <div>
            <h4 class="text-sm font-medium text-gray-700 mb-2">Возможности поиска:</h4>
            <ul class="text-sm text-gray-600 space-y-1">
              <li>• Поиск по смыслу, а не только по словам</li>
              <li>• Фильтрация по городу и категории</li>
              <li>• Сортировка по релевантности</li>
              <li>• Поиск по частичному совпадению</li>
            </ul>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useProposalsStore } from '@/stores/proposals'
import { useDictionariesStore } from '@/stores/dictionaries'
import AppLayout from '@/components/common/AppLayout.vue'
import LoadingSpinner from '@/components/common/LoadingSpinner.vue'
import {
  MagnifyingGlassIcon,
  ClockIcon,
  ChartBarIcon
} from '@heroicons/vue/24/outline'
import dayjs from 'dayjs'

const proposalsStore = useProposalsStore()
const dictionariesStore = useDictionariesStore()

const searchQuery = ref('')
const searchResults = ref<any[]>([])
const loading = ref(false)
const hasSearched = ref(false)
const showAdvancedFilters = ref(false)
const sortBy = ref('relevance')

const filters = ref({
  city_id: '',
  category_id: '',
  period: ''
})

const hasResults = computed(() => searchResults.value.length > 0)

const sortedResults = computed(() => {
  const results = [...searchResults.value]
  
  switch (sortBy.value) {
    case 'date':
      return results.sort((a, b) => new Date(b.created_at).getTime() - new Date(a.created_at).getTime())
    case 'city':
      return results.sort((a, b) => a.city.name.localeCompare(b.city.name))
    case 'relevance':
    default:
      return results.sort((a, b) => (b.similarity || 0) - (a.similarity || 0))
  }
})

function formatDate(date: string) {
  return dayjs(date).format('DD.MM.YYYY HH:mm')
}

async function handleSearch() {
  if (!searchQuery.value.trim()) return
  
  loading.value = true
  hasSearched.value = true
  
  try {
    await proposalsStore.searchProposals(searchQuery.value.trim())
    
    // Получаем результаты из store
    searchResults.value = proposalsStore.proposals
  } catch (error) {
    console.error('Search error:', error)
    searchResults.value = []
  } finally {
    loading.value = false
  }
}

function clearSearch() {
  searchQuery.value = ''
  searchResults.value = []
  hasSearched.value = false
  filters.value = {
    city_id: '',
    category_id: '',
    period: ''
  }
}

onMounted(async () => {
  await dictionariesStore.loadDictionaries()
})
</script>
