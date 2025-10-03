<template>
  <div class="bg-white shadow-sm rounded-lg overflow-hidden">
    <!-- Table Header -->
    <div v-if="title || $slots.header" class="px-6 py-4 border-b border-gray-200">
      <slot name="header">
        <h3 class="text-lg font-medium text-gray-900">{{ title }}</h3>
      </slot>
    </div>

    <!-- Table -->
    <div class="overflow-x-auto">
      <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
          <tr>
            <th
              v-for="column in columns"
              :key="column.key"
              :class="[
                'px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider',
                column.align === 'center' ? 'text-center' : '',
                column.align === 'right' ? 'text-right' : '',
                column.width ? `w-${column.width}` : ''
              ]"
            >
              <div class="flex items-center space-x-1">
                <span>{{ column.label }}</span>
                <button
                  v-if="column.sortable"
                  @click="handleSort(column.key)"
                  class="p-1 rounded hover:bg-gray-200"
                >
                  <ChevronUpDownIcon class="h-4 w-4" />
                </button>
              </div>
            </th>
            <th v-if="actions && actions.length > 0" class="px-3 py-2 text-right text-xs font-medium text-gray-500 uppercase tracking-wider w-20">
              Действия
            </th>
          </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
          <tr
            v-for="(item, index) in paginatedData"
            :key="getItemKey(item, index)"
            class="hover:bg-gray-50"
          >
            <td
              v-for="column in columns"
              :key="column.key"
              :class="[
                'px-3 py-3 text-sm text-gray-900',
                column.align === 'center' ? 'text-center' : '',
                column.align === 'right' ? 'text-right' : '',
                column.key === 'content' ? '' : 'whitespace-nowrap'
              ]"
            >
              <slot
                :name="`cell-${column.key}`"
                :item="item"
                :value="getNestedValue(item, column.key)"
                :index="index"
              >
                {{ getNestedValue(item, column.key) }}
              </slot>
            </td>
            <td v-if="actions && actions.length > 0" class="px-3 py-3 whitespace-nowrap text-right text-sm font-medium">
              <div class="flex items-center justify-end space-x-1">
                <button
                  v-for="action in actions"
                  :key="action.label"
                  @click="action.action(item, index)"
                  :disabled="action.disabled?.(item, index)"
                  :title="action.label"
                  :class="[
                    'inline-flex items-center p-1.5 border border-transparent text-xs font-medium rounded focus:outline-none focus:ring-1 focus:ring-offset-1',
                    action.variant === 'primary' ? 'text-primary-700 bg-primary-100 hover:bg-primary-200 focus:ring-primary-500' : '',
                    action.variant === 'danger' ? 'text-red-700 bg-red-100 hover:bg-red-200 focus:ring-red-500' : '',
                    action.variant === 'secondary' || !action.variant ? 'text-gray-700 bg-gray-100 hover:bg-gray-200 focus:ring-gray-500' : '',
                    action.disabled?.(item, index) ? 'opacity-50 cursor-not-allowed' : ''
                  ]"
                >
                  <component
                    v-if="action.icon"
                    :is="action.icon"
                    class="h-4 w-4"
                  />
                  <span v-else class="text-xs">{{ action.label }}</span>
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Empty state -->
    <div v-if="data.length === 0" class="text-center py-12">
      <div class="mx-auto h-12 w-12 text-gray-400">
        <DocumentTextIcon class="h-12 w-12" />
      </div>
      <h3 class="mt-2 text-sm font-medium text-gray-900">Нет данных</h3>
      <p class="mt-1 text-sm text-gray-500">Начните с добавления нового элемента.</p>
    </div>

    <!-- Pagination -->
    <div v-if="showPagination && totalPages > 1" class="bg-white px-4 py-3 flex items-center justify-between border-t border-gray-200 sm:px-6">
      <div class="flex-1 flex justify-between sm:hidden">
        <button
          @click="goToPage(currentPage - 1)"
          :disabled="currentPage <= 1"
          class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
        >
          Предыдущая
        </button>
        <button
          @click="goToPage(currentPage + 1)"
          :disabled="currentPage >= totalPages"
          class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
        >
          Следующая
        </button>
      </div>
      <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
        <div>
          <p class="text-sm text-gray-700">
            Показано
            <span class="font-medium">{{ (currentPage - 1) * perPage + 1 }}</span>
            -
            <span class="font-medium">{{ Math.min(currentPage * perPage, total) }}</span>
            из
            <span class="font-medium">{{ total }}</span>
            результатов
          </p>
        </div>
        <div>
          <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px">
            <button
              @click="goToPage(currentPage - 1)"
              :disabled="currentPage <= 1"
              class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
            >
              <ChevronLeftIcon class="h-5 w-5" />
            </button>
            
            <template v-for="page in visiblePages" :key="page">
              <button
                v-if="page !== '...'"
                @click="goToPage(page as number)"
                :class="[
                  'relative inline-flex items-center px-4 py-2 border text-sm font-medium',
                  page === currentPage
                    ? 'z-10 bg-primary-50 border-primary-500 text-primary-600'
                    : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50'
                ]"
              >
                {{ page }}
              </button>
              <span
                v-else
                class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700"
              >
                ...
              </span>
            </template>
            
            <button
              @click="goToPage(currentPage + 1)"
              :disabled="currentPage >= totalPages"
              class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
            >
              <ChevronRightIcon class="h-5 w-5" />
            </button>
          </nav>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { ChevronUpDownIcon, ChevronLeftIcon, ChevronRightIcon, DocumentTextIcon } from '@heroicons/vue/24/outline'
import type { TableColumn, TableAction } from '@/types'

interface Props {
  data: any[]
  columns: TableColumn[]
  actions?: TableAction[]
  title?: string
  showPagination?: boolean
  currentPage?: number
  perPage?: number
  total?: number
  sortBy?: string
  sortOrder?: 'asc' | 'desc'
  getItemKey?: (item: any, index: number) => string | number
}

const props = withDefaults(defineProps<Props>(), {
  showPagination: true,
  currentPage: 1,
  perPage: 10,
  total: 0,
  sortBy: '',
  sortOrder: 'asc',
  getItemKey: (item: any, index: number) => item.id || index
})

const emit = defineEmits<{
  'update:currentPage': [page: number]
  'update:sortBy': [sortBy: string]
  'update:sortOrder': [order: 'asc' | 'desc']
}>()

const totalPages = computed(() => Math.ceil(props.total / props.perPage))

const paginatedData = computed(() => {
  // Данные уже приходят с сервера с пагинацией, поэтому возвращаем их как есть
  return props.data
})

const visiblePages = computed(() => {
  const pages: (number | string)[] = []
  const total = totalPages.value
  const current = props.currentPage
  
  if (total <= 7) {
    for (let i = 1; i <= total; i++) {
      pages.push(i)
    }
  } else {
    pages.push(1)
    
    if (current > 4) {
      pages.push('...')
    }
    
    const start = Math.max(2, current - 1)
    const end = Math.min(total - 1, current + 1)
    
    for (let i = start; i <= end; i++) {
      pages.push(i)
    }
    
    if (current < total - 3) {
      pages.push('...')
    }
    
    if (total > 1) {
      pages.push(total)
    }
  }
  
  return pages
})

function getNestedValue(obj: any, path: string) {
  return path.split('.').reduce((current, key) => current?.[key], obj)
}

function handleSort(key: string) {
  if (props.sortBy === key) {
    emit('update:sortOrder', props.sortOrder === 'asc' ? 'desc' : 'asc')
  } else {
    emit('update:sortBy', key)
    emit('update:sortOrder', 'asc')
  }
}

function goToPage(page: number) {
  if (page >= 1 && page <= totalPages.value) {
    emit('update:currentPage', page)
  }
}
</script>
