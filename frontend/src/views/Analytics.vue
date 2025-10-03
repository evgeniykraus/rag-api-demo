<template>
  <AppLayout>
    <div class="space-y-6">
      <!-- Header -->
      <div class="md:flex md:items-center md:justify-between">
        <div class="flex-1 min-w-0">
          <h2 class="text-2xl font-bold leading-7 text-gray-900 sm:text-3xl sm:truncate">
            Аналитика
          </h2>
          <p class="mt-1 text-sm text-gray-500">
            Статистика и аналитика по обращениям граждан
          </p>
        </div>
        <div class="mt-4 flex space-x-3 md:mt-0 md:ml-4">
          <select
            v-model="selectedPeriod"
            class="input"
          >
            <option value="week">За неделю</option>
            <option value="month">За месяц</option>
            <option value="quarter">За квартал</option>
            <option value="year">За год</option>
          </select>
        </div>
      </div>

      <!-- Stats Overview -->
      <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
        <div class="bg-white overflow-hidden shadow rounded-lg">
          <div class="p-5">
            <div class="flex items-center">
              <div class="flex-shrink-0">
                <DocumentTextIcon class="h-6 w-6 text-gray-400" />
              </div>
              <div class="ml-5 w-0 flex-1">
                <dl>
                  <dt class="text-sm font-medium text-gray-500 truncate">
                    Всего обращений
                  </dt>
                  <dd class="text-lg font-medium text-gray-900">
                    {{ analytics.totalProposals }}
                  </dd>
                </dl>
              </div>
            </div>
          </div>
        </div>

        <div class="bg-white overflow-hidden shadow rounded-lg">
          <div class="p-5">
            <div class="flex items-center">
              <div class="flex-shrink-0">
                <TrendingUpIcon class="h-6 w-6 text-green-400" />
              </div>
              <div class="ml-5 w-0 flex-1">
                <dl>
                  <dt class="text-sm font-medium text-gray-500 truncate">
                    Рост за период
                  </dt>
                  <dd class="text-lg font-medium text-gray-900">
                    +{{ analytics.growth }}%
                  </dd>
                </dl>
              </div>
            </div>
          </div>
        </div>

        <div class="bg-white overflow-hidden shadow rounded-lg">
          <div class="p-5">
            <div class="flex items-center">
              <div class="flex-shrink-0">
                <BuildingOfficeIcon class="h-6 w-6 text-blue-400" />
              </div>
              <div class="ml-5 w-0 flex-1">
                <dl>
                  <dt class="text-sm font-medium text-gray-500 truncate">
                    Активных городов
                  </dt>
                  <dd class="text-lg font-medium text-gray-900">
                    {{ analytics.activeCities }}
                  </dd>
                </dl>
              </div>
            </div>
          </div>
        </div>

        <div class="bg-white overflow-hidden shadow rounded-lg">
          <div class="p-5">
            <div class="flex items-center">
              <div class="flex-shrink-0">
                <ClockIcon class="h-6 w-6 text-yellow-400" />
              </div>
              <div class="ml-5 w-0 flex-1">
                <dl>
                  <dt class="text-sm font-medium text-gray-500 truncate">
                    Среднее время ответа
                  </dt>
                  <dd class="text-lg font-medium text-gray-900">
                    {{ analytics.avgResponseTime }}ч
                  </dd>
                </dl>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Charts Row -->
      <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        <!-- Proposals by Month -->
        <div class="bg-white shadow rounded-lg p-6">
          <h3 class="text-lg font-medium text-gray-900 mb-4">
            Обращения по месяцам
          </h3>
          <div class="h-64">
            <canvas ref="monthlyChart" class="w-full h-full"></canvas>
          </div>
        </div>

        <!-- Proposals by Category -->
        <div class="bg-white shadow rounded-lg p-6">
          <h3 class="text-lg font-medium text-gray-900 mb-4">
            Распределение по категориям
          </h3>
          <div class="h-64">
            <canvas ref="categoryChart" class="w-full h-full"></canvas>
          </div>
        </div>
      </div>

      <!-- Top Cities and Categories -->
      <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        <!-- Top Cities -->
        <div class="bg-white shadow rounded-lg">
          <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-medium text-gray-900">Топ городов по обращениям</h3>
          </div>
          <div class="px-6 py-4">
            <div class="space-y-4">
              <div
                v-for="(city, index) in topCities"
                :key="city.name"
                class="flex items-center justify-between"
              >
                <div class="flex items-center">
                  <div class="flex-shrink-0 w-8 h-8 bg-primary-100 rounded-full flex items-center justify-center">
                    <span class="text-sm font-medium text-primary-600">{{ index + 1 }}</span>
                  </div>
                  <div class="ml-3">
                    <p class="text-sm font-medium text-gray-900">{{ city.name }}</p>
                  </div>
                </div>
                <div class="flex items-center">
                  <div class="w-32 bg-gray-200 rounded-full h-2 mr-3">
                    <div
                      class="bg-primary-600 h-2 rounded-full"
                      :style="{ width: `${(city.count / topCities[0].count) * 100}%` }"
                    ></div>
                  </div>
                  <span class="text-sm font-medium text-gray-900">{{ city.count }}</span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Top Categories -->
        <div class="bg-white shadow rounded-lg">
          <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-medium text-gray-900">Топ категорий</h3>
          </div>
          <div class="px-6 py-4">
            <div class="space-y-4">
              <div
                v-for="(category, index) in topCategories"
                :key="category.name"
                class="flex items-center justify-between"
              >
                <div class="flex items-center">
                  <div class="flex-shrink-0 w-8 h-8 bg-green-100 rounded-full flex items-center justify-center">
                    <span class="text-sm font-medium text-green-600">{{ index + 1 }}</span>
                  </div>
                  <div class="ml-3">
                    <p class="text-sm font-medium text-gray-900">{{ category.name }}</p>
                  </div>
                </div>
                <div class="flex items-center">
                  <div class="w-32 bg-gray-200 rounded-full h-2 mr-3">
                    <div
                      class="bg-green-600 h-2 rounded-full"
                      :style="{ width: `${(category.count / topCategories[0].count) * 100}%` }"
                    ></div>
                  </div>
                  <span class="text-sm font-medium text-gray-900">{{ category.count }}</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Recent Activity -->
      <div class="bg-white shadow rounded-lg">
        <div class="px-6 py-4 border-b border-gray-200">
          <h3 class="text-lg font-medium text-gray-900">Последняя активность</h3>
        </div>
        <div class="px-6 py-4">
          <div class="flow-root">
            <ul class="-mb-8">
              <li
                v-for="(activity, index) in recentActivity"
                :key="activity.id"
                class="relative pb-8"
              >
                <div v-if="index !== recentActivity.length - 1" class="absolute top-4 left-4 -ml-px h-full w-0.5 bg-gray-200"></div>
                <div class="relative flex space-x-3">
                  <div>
                    <span class="h-8 w-8 rounded-full bg-primary-100 flex items-center justify-center ring-8 ring-white">
                      <DocumentTextIcon class="h-5 w-5 text-primary-600" />
                    </span>
                  </div>
                  <div class="min-w-0 flex-1 pt-1.5 flex justify-between space-x-4">
                    <div>
                      <p class="text-sm text-gray-500">
                        Новое обращение <span class="font-medium text-gray-900">#{{ activity.id }}</span>
                        в городе <span class="font-medium text-gray-900">{{ activity.city }}</span>
                      </p>
                    </div>
                    <div class="text-right text-sm whitespace-nowrap text-gray-500">
                      {{ formatDate(activity.created_at) }}
                    </div>
                  </div>
                </div>
              </li>
            </ul>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, watch, nextTick } from 'vue'
import { useProposalsStore } from '@/stores/proposals'
import { useDictionariesStore } from '@/stores/dictionaries'
import AppLayout from '@/components/common/AppLayout.vue'
import {
  DocumentTextIcon,
  TrendingUpIcon,
  BuildingOfficeIcon,
  ClockIcon
} from '@heroicons/vue/24/outline'
import dayjs from 'dayjs'
import Chart from 'chart.js/auto'

const proposalsStore = useProposalsStore()
const dictionariesStore = useDictionariesStore()

const selectedPeriod = ref('month')
const monthlyChart = ref<HTMLCanvasElement>()
const categoryChart = ref<HTMLCanvasElement>()

// Mock analytics data
const analytics = computed(() => ({
  totalProposals: proposalsStore.pagination.total,
  growth: 12.5,
  activeCities: dictionariesStore.cities.length,
  avgResponseTime: 24
}))

const topCities = computed(() => [
  { name: 'Кемерово', count: 1250 },
  { name: 'Новокузнецк', count: 980 },
  { name: 'Прокопьевск', count: 750 },
  { name: 'Междуреченск', count: 620 },
  { name: 'Анжеро-Судженск', count: 480 }
])

const topCategories = computed(() => [
  { name: 'Дворовые и общественные территории', count: 850 },
  { name: 'Жилищно-коммунальное хозяйство', count: 720 },
  { name: 'Автомобильные дороги', count: 650 },
  { name: 'Экология', count: 420 },
  { name: 'Безопасность', count: 380 }
])

const recentActivity = computed(() => 
  proposalsStore.proposals.slice(0, 5).map(proposal => ({
    id: proposal.id,
    city: proposal.city.name,
    created_at: proposal.created_at
  }))
)

function formatDate(date: string) {
  return dayjs(date).format('DD.MM.YYYY HH:mm')
}

function createCharts() {
  nextTick(() => {
    // Monthly chart
    if (monthlyChart.value) {
      new Chart(monthlyChart.value, {
        type: 'line',
        data: {
          labels: ['Янв', 'Фев', 'Мар', 'Апр', 'Май', 'Июн'],
          datasets: [{
            label: 'Обращения',
            data: [120, 150, 180, 160, 200, 220],
            borderColor: 'rgb(59, 130, 246)',
            backgroundColor: 'rgba(59, 130, 246, 0.1)',
            tension: 0.4
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: {
              display: false
            }
          },
          scales: {
            y: {
              beginAtZero: true
            }
          }
        }
      })
    }

    // Category chart
    if (categoryChart.value) {
      new Chart(categoryChart.value, {
        type: 'doughnut',
        data: {
          labels: topCategories.value.map(c => c.name),
          datasets: [{
            data: topCategories.value.map(c => c.count),
            backgroundColor: [
              'rgb(59, 130, 246)',
              'rgb(16, 185, 129)',
              'rgb(245, 158, 11)',
              'rgb(239, 68, 68)',
              'rgb(139, 92, 246)'
            ]
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: {
              position: 'bottom'
            }
          }
        }
      })
    }
  })
}

watch(selectedPeriod, () => {
  createCharts()
})

onMounted(async () => {
  await Promise.all([
    proposalsStore.fetchProposals(),
    dictionariesStore.loadDictionaries()
  ])
  createCharts()
})
</script>

