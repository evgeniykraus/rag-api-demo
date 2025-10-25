<template>
  <AppLayout>
    <div class="space-y-6">
      <!-- Header -->
      <div class="md:flex md:items-center md:justify-between">
        <div class="flex-1 min-w-0">
          <h2 class="text-2xl font-bold leading-7 text-gray-900 dark:text-gray-100 sm:text-3xl sm:truncate">
            Главная панель
          </h2>
          <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
            Обзор системы управления обращениями граждан
          </p>
        </div>
        <div class="mt-4 flex md:mt-0 md:ml-4">
          <RouterLink
            to="/proposals/create"
            class="btn btn-primary btn-md"
          >
            <PlusIcon class="h-5 w-5 mr-2" />
            Создать обращение
          </RouterLink>
        </div>
      </div>

      <!-- Stats -->
      <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
        <div class="bg-white overflow-hidden shadow rounded-lg dark:bg-gray-800">
          <div class="p-5">
            <div class="flex items-center">
              <div class="flex-shrink-0">
                <DocumentTextIcon class="h-6 w-6 text-gray-400 dark:text-gray-500" />
              </div>
              <div class="ml-5 w-0 flex-1">
                <dl>
                  <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">
                    Всего обращений
                  </dt>
                  <dd class="text-lg font-medium text-gray-900 dark:text-gray-100">
                    {{ stats.totalProposals }}
                  </dd>
                </dl>
              </div>
            </div>
          </div>
        </div>

        <div class="bg-white overflow-hidden shadow rounded-lg dark:bg-gray-800">
          <div class="p-5">
            <div class="flex items-center">
              <div class="flex-shrink-0">
                <BuildingOfficeIcon class="h-6 w-6 text-gray-400 dark:text-gray-500" />
              </div>
              <div class="ml-5 w-0 flex-1">
                <dl>
                  <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">
                    Городов
                  </dt>
                  <dd class="text-lg font-medium text-gray-900 dark:text-gray-100">
                    {{ stats.totalCities }}
                  </dd>
                </dl>
              </div>
            </div>
          </div>
        </div>

        <div class="bg-white overflow-hidden shadow rounded-lg dark:bg-gray-800">
          <div class="p-5">
            <div class="flex items-center">
              <div class="flex-shrink-0">
                <TagIcon class="h-6 w-6 text-gray-400 dark:text-gray-500" />
              </div>
              <div class="ml-5 w-0 flex-1">
                <dl>
                  <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">
                    Подкатегорий
                  </dt>
                  <dd class="text-lg font-medium text-gray-900 dark:text-gray-100">
                    {{ stats.totalCategories }}
                  </dd>
                </dl>
              </div>
            </div>
          </div>
        </div>

        <div class="bg-white overflow-hidden shadow rounded-lg dark:bg-gray-800">
          <div class="p-5">
            <div class="flex items-center">
              <div class="flex-shrink-0">
                <ClockIcon class="h-6 w-6 text-gray-400 dark:text-gray-500" />
              </div>
              <div class="ml-5 w-0 flex-1">
                <dl>
                  <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">
                    За сегодня
                  </dt>
                  <dd class="text-lg font-medium text-gray-900 dark:text-gray-100">
                    {{ stats.todayProposals }}
                  </dd>
                </dl>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Recent Proposals -->
      <div class="bg-white shadow rounded-lg dark:bg-gray-800">
        <div class="px-4 py-5 sm:p-6">
          <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-gray-100 mb-4">
            Последние обращения
          </h3>
          <div v-if="loading" class="flex justify-center py-8">
            <LoadingSpinner message="Загрузка обращений..." />
          </div>
          <div v-else-if="recentProposals.length === 0" class="text-center py-8">
            <DocumentTextIcon class="mx-auto h-12 w-12 text-gray-400 dark:text-gray-500" />
            <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-gray-100">Нет обращений</h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Начните с создания нового обращения.</p>
          </div>
          <div v-else class="space-y-4">
            <div
              v-for="proposal in recentProposals"
              :key="proposal.id"
              class="border border-gray-200 rounded-lg p-4 hover:bg-gray-50 dark:border-gray-700 dark:hover:bg-gray-700"
            >
              <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between">
                <div class="flex-1 min-w-0">
                  <p class="text-sm text-gray-900 dark:text-gray-100 line-clamp-2">
                    {{ proposal.content }}
                  </p>
                  <div class="mt-2 flex flex-col sm:flex-row sm:items-center sm:space-x-4 space-y-2 sm:space-y-0 text-xs text-gray-500 dark:text-gray-400">
                    <span class="flex items-center">
                      <BuildingOfficeIcon class="h-4 w-4 mr-1 flex-shrink-0" />
                      <span class="truncate">{{ proposal.city.name }}</span>
                    </span>
                    <span class="flex items-center">
                      <TagIcon class="h-4 w-4 mr-1 flex-shrink-0" />
                      <span class="truncate">{{ proposal.category.name }}</span>
                    </span>
                    <span class="flex items-center">
                      <ClockIcon class="h-4 w-4 mr-1 flex-shrink-0" />
                      <span class="truncate">{{ formatDate(proposal.created_at) }}</span>
                    </span>
                  </div>
                </div>
                <div class="mt-2 sm:mt-0 sm:ml-4 flex-shrink-0">
                  <RouterLink
                    :to="`/proposals/${proposal.id}`"
                    class="text-primary-600 hover:text-primary-900 text-sm font-medium"
                  >
                    Подробнее
                  </RouterLink>
                </div>
              </div>
            </div>
          </div>
          <div v-if="recentProposals.length > 0" class="mt-4">
            <RouterLink
              to="/proposals"
              class="text-primary-600 hover:text-primary-900 text-sm font-medium"
            >
              Посмотреть все обращения →
            </RouterLink>
          </div>
        </div>
      </div>

      <!-- Quick Actions -->
      <div class="bg-white shadow rounded-lg dark:bg-gray-800">
        <div class="px-4 py-5 sm:p-6">
          <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-gray-100 mb-4">
            Быстрые действия
          </h3>
          <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <RouterLink
              to="/proposals/create"
              class="relative rounded-lg border border-gray-300 bg-white px-6 py-5 shadow-sm flex items-center space-x-3 hover:border-gray-400 focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:hover:border-gray-500"
            >
              <div class="flex-shrink-0">
                <PlusIcon class="h-6 w-6 text-primary-600" />
              </div>
              <div class="flex-1 min-w-0">
                <span class="absolute inset-0" aria-hidden="true" />
                <p class="text-sm font-medium text-gray-900 dark:text-gray-100">Создать обращение</p>
                <p class="text-sm text-gray-500 dark:text-gray-400">Добавить новое обращение в систему</p>
              </div>
            </RouterLink>

            <RouterLink
              to="/search"
              class="relative rounded-lg border border-gray-300 bg-white px-6 py-5 shadow-sm flex items-center space-x-3 hover:border-gray-400 focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:hover:border-gray-500"
            >
              <div class="flex-shrink-0">
                <MagnifyingGlassIcon class="h-6 w-6 text-primary-600" />
              </div>
              <div class="flex-1 min-w-0">
                <span class="absolute inset-0" aria-hidden="true" />
                <p class="text-sm font-medium text-gray-900 dark:text-gray-100">Поиск</p>
                <p class="text-sm text-gray-500 dark:text-gray-400">Найти обращения по содержимому</p>
              </div>
            </RouterLink>

            <RouterLink
              to="/analytics"
              class="relative rounded-lg border border-gray-300 bg-white px-6 py-5 shadow-sm flex items-center space-x-3 hover:border-gray-400 focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:hover:border-gray-500"
            >
              <div class="flex-shrink-0">
                <ChartBarIcon class="h-6 w-6 text-primary-600" />
              </div>
              <div class="flex-1 min-w-0">
                <span class="absolute inset-0" aria-hidden="true" />
                <p class="text-sm font-medium text-gray-900 dark:text-gray-100">Аналитика</p>
                <p class="text-sm text-gray-500 dark:text-gray-400">Статистика и отчеты</p>
              </div>
            </RouterLink>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { useProposalsStore } from '@/stores/proposals'
import { useDictionariesStore } from '@/stores/dictionaries'
import { apiClient } from '@/services/api'
import AppLayout from '@/components/common/AppLayout.vue'
import LoadingSpinner from '@/components/common/LoadingSpinner.vue'
import {
  PlusIcon,
  DocumentTextIcon,
  BuildingOfficeIcon,
  TagIcon,
  ClockIcon,
  MagnifyingGlassIcon,
  ChartBarIcon
} from '@heroicons/vue/24/outline'
import dayjs from 'dayjs'
import utc from 'dayjs/plugin/utc'
dayjs.extend(utc)

const proposalsStore = useProposalsStore()
const dictionariesStore = useDictionariesStore()

const loading = ref(false)

const totalProposals = ref(0)
const todayProposals = ref(0)

const stats = computed(() => ({
  totalProposals: totalProposals.value,
  totalCities: dictionariesStore.cities.length,
  totalCategories: dictionariesStore.categories.reduce((total, category) => 
    total + (category.children?.length || 0), 0
  ),
  todayProposals: todayProposals.value
}))

const recentProposals = computed(() => 
  proposalsStore.proposals.slice(0, 5)
)

function formatDate(date: string) {
  // Parse ISO (Z) as UTC and display as-is (no local shift)
  return dayjs.utc(date).format('DD.MM.YYYY HH:mm')
}

onMounted(async () => {
  loading.value = true
  try {
    await Promise.all([
      proposalsStore.fetchProposals(1),
      dictionariesStore.loadDictionaries()
    ])
    // Total from pagination of proposals index (already requested)
    totalProposals.value = proposalsStore.pagination.total

    // Use overview for 'today' with server-local timestamps to avoid TZ drift
    const from = dayjs().startOf('day').format('YYYY-MM-DD HH:mm:ss')
    const to = dayjs().endOf('day').format('YYYY-MM-DD HH:mm:ss')
    const today = await apiClient.getAnalyticsOverview({ from, to })
    todayProposals.value = today.period_proposals
  } finally {
    loading.value = false
  }
})
</script>

