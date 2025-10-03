<template>
  <AppLayout>
    <div class="space-y-6">
      <!-- Header -->
      <div class="md:flex md:items-center md:justify-between">
        <div class="flex-1 min-w-0">
          <h2 class="text-2xl font-bold leading-7 text-gray-900 sm:text-3xl sm:truncate">
            Обращения
          </h2>
          <p class="mt-1 text-sm text-gray-500">
            Управление обращениями граждан
          </p>
        </div>
        <div class="mt-4 flex space-x-3 md:mt-0 md:ml-4">
          <button
            @click="showFilters = !showFilters"
            class="btn btn-secondary btn-md"
          >
            <FunnelIcon class="h-5 w-5 mr-2" />
            Фильтры
          </button>
          <RouterLink
            to="/proposals/create"
            class="btn btn-primary btn-md"
          >
            <PlusIcon class="h-5 w-5 mr-2" />
            Создать обращение
          </RouterLink>
        </div>
      </div>

      <!-- Filters -->
      <div v-if="showFilters" class="bg-white shadow rounded-lg p-6">
        <h3 class="text-lg font-medium text-gray-900 mb-4">Фильтры</h3>
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
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
              Дата от
            </label>
            <input
              v-model="filters.date_from"
              type="date"
              class="input"
            />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
              Дата до
            </label>
            <input
              v-model="filters.date_to"
              type="date"
              class="input"
            />
          </div>
        </div>

        <div class="mt-4 flex justify-end space-x-3">
          <button
            @click="clearFilters"
            class="btn btn-secondary btn-md"
          >
            Очистить
          </button>
          <button
            @click="applyFilters"
            class="btn btn-primary btn-md"
          >
            Применить
          </button>
        </div>
      </div>

      <!-- Search -->
      <div class="bg-white shadow rounded-lg p-6">
        <div class="flex space-x-4">
          <div class="flex-1">
            <input
              v-model="searchQuery"
              type="text"
              placeholder="Поиск по содержанию обращений..."
              class="input"
              @keyup.enter="handleSearch"
            />
          </div>
          <button
            @click="handleSearch"
            class="btn btn-primary btn-md"
          >
            <MagnifyingGlassIcon class="h-5 w-5 mr-2" />
            Поиск
          </button>
        </div>
      </div>

      <!-- Table -->
      <DataTable
        :data="proposalsStore.filteredProposals"
        :columns="columns"
        :actions="actions"
        :loading="proposalsStore.loading"
        :current-page="proposalsStore.pagination.page"
        :per-page="proposalsStore.pagination.perPage"
        :total="proposalsStore.pagination.total"
        title="Список обращений"
        @update:current-page="handlePageChange"
      >
        <template #cell-content="{ item }">
          <div class="max-w-lg">
            <div class="text-sm text-gray-900 line-clamp-2 mb-2" :title="item.content">
              {{ item.content.length > 120 ? `${item.content.substring(0, 120)}...` : item.content }}
            </div>

            <!-- Спойлер с дополнительной информацией -->
            <details class="group">
              <summary class="inline-flex items-center px-2 py-1 text-xs font-medium text-blue-600 bg-blue-50 rounded-md cursor-pointer hover:bg-blue-100 hover:text-blue-700 transition-colors">
                <span class="mr-1">Подробнее</span>
                <ChevronDownIcon class="h-3 w-3 transform group-open:rotate-180 transition-transform" />
              </summary>
              <div class="mt-2 space-y-2 text-xs text-gray-600">
                <div class="flex items-center space-x-2">
                  <span class="font-medium">Категория:</span>
                  <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">
                    {{ item.category.name }}
                  </span>
                </div>
                <div v-if="item.category.parent" class="flex items-center space-x-2">
                    <span class="font-medium">Родительская категория:</span>
                  <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800">
                    {{ item.category.parent.name || item.category.parent }}
                  </span>
                </div>
                <div class="space-y-1">
                  <div class="font-medium text-gray-700">Полный текст:</div>
                  <div class="text-gray-700 bg-gray-50 p-2 rounded text-xs leading-relaxed">
                    {{ item.content }}
                  </div>
                </div>
              </div>
            </details>
          </div>
        </template>

        <template #cell-city="{ item }">
          <span class="text-sm font-medium text-gray-900">
            {{ item.city.name }}
          </span>
        </template>


        <template #cell-created_at="{ item }">
          <div class="text-sm">
            <div class="font-medium text-gray-900">
              {{ dayjs.utc(item.created_at).format('DD.MM.YYYY') }}
            </div>
            <div class="text-xs text-gray-500">
              {{ dayjs.utc(item.created_at).format('HH:mm') }}
            </div>
          </div>
        </template>
      </DataTable>

      <!-- Error state -->
      <div v-if="proposalsStore.error" class="bg-red-50 border border-red-200 rounded-md p-4">
        <div class="flex">
          <ExclamationTriangleIcon class="h-5 w-5 text-red-400" />
          <div class="ml-3">
            <h3 class="text-sm font-medium text-red-800">
              Ошибка загрузки
            </h3>
            <div class="mt-2 text-sm text-red-700">
              {{ proposalsStore.error }}
            </div>
            <div class="mt-4">
              <button
                @click="proposalsStore.fetchProposals()"
                class="btn btn-primary btn-sm"
              >
                Попробовать снова
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Edit Modal -->
      <EditProposalModal
        :is-open="showEditModal"
        :proposal="selectedProposal"
        @close="closeEditModal"
        @updated="handleProposalUpdated"
      />
    </div>
  </AppLayout>
</template>

<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { useRouter } from 'vue-router'
import { useProposalsStore } from '@/stores/proposals'
import { useDictionariesStore } from '@/stores/dictionaries'
import { useUIStore } from '@/stores/ui'
import AppLayout from '@/components/common/AppLayout.vue'
import DataTable from '@/components/common/DataTable.vue'
import EditProposalModal from '@/components/common/EditProposalModal.vue'
import {
  PlusIcon,
  FunnelIcon,
  MagnifyingGlassIcon,
  ExclamationTriangleIcon,
  EyeIcon,
  PencilIcon,
  TrashIcon,
  ChevronDownIcon
} from '@heroicons/vue/24/outline'
import type { TableColumn, TableAction, Proposal } from '@/types'
import dayjs from 'dayjs'
import utc from 'dayjs/plugin/utc'
dayjs.extend(utc)

const router = useRouter()
const proposalsStore = useProposalsStore()
const dictionariesStore = useDictionariesStore()
const uiStore = useUIStore()

const showFilters = ref(false)
const searchQuery = ref('')
const showEditModal = ref(false)
const selectedProposal = ref<Proposal | null>(null)
const filters = ref({
  city_id: '',
  category_id: '',
  date_from: '',
  date_to: ''
})

const columns: TableColumn[] = [
  { key: 'id', label: '№', width: '12', sortable: true },
  { key: 'city.name', label: 'Город', width: '18', sortable: true },
  { key: 'content', label: 'Обращение', sortable: true },
  { key: 'created_at', label: 'Дата', width: '18', sortable: true }
]

const actions: TableAction[] = [
  {
    label: 'Просмотр',
    icon: EyeIcon,
    action: (item) => {
      // Открываем в новой вкладке
      const url = router.resolve(`/proposals/${item.id}`)
      window.open(url.href, '_blank')
    }
  },
  {
    label: 'Редактировать',
    icon: PencilIcon,
    action: (item) => {
      selectedProposal.value = item
      showEditModal.value = true
    }
  },
  {
    label: 'Удалить',
    icon: TrashIcon,
    variant: 'danger',
    action: async (item) => {
      if (confirm('Вы уверены, что хотите удалить это обращение?')) {
        try {
          await proposalsStore.deleteProposal(item.id)
          uiStore.showSuccess('Обращение удалено')
        } catch (error) {
          uiStore.showError('Ошибка удаления', 'Не удалось удалить обращение')
        }
      }
    }
  }
]


function handlePageChange(page: number) {
  proposalsStore.setPagination(page)
  proposalsStore.fetchProposals(page)
}

function handleSearch() {
  if (searchQuery.value.trim()) {
    proposalsStore.searchProposals(searchQuery.value.trim())
  } else {
    proposalsStore.clearSearch()
  }
}

function applyFilters() {
  proposalsStore.setFilters(filters.value)
  proposalsStore.fetchProposals()
  showFilters.value = false
}

function clearFilters() {
  filters.value = {
    city_id: '',
    category_id: '',
    date_from: '',
    date_to: ''
  }
  searchQuery.value = ''
  proposalsStore.clearFilters()
  proposalsStore.clearSearch()
}

function closeEditModal() {
  showEditModal.value = false
  selectedProposal.value = null
}

function handleProposalUpdated(updatedProposal: Proposal) {
  // Обновляем данные в таблице
  const index = proposalsStore.proposals.findIndex(p => p.id === updatedProposal.id)
  if (index !== -1) {
    proposalsStore.proposals[index] = updatedProposal
  }
}

onMounted(async () => {
  await dictionariesStore.loadDictionaries()
  await proposalsStore.fetchProposals()
})
</script>
