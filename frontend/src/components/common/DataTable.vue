<template>
  <div class="bg-white shadow-sm rounded-lg overflow-hidden dark:bg-gray-800">
    <!-- Table Header -->
    <div v-if="title || $slots.header" class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
      <slot name="header">
        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">{{ title }}</h3>
      </slot>
    </div>

    <!-- Table -->
    <div ref="tableContainer" class="overflow-x-auto relative">
      <!-- Scroll indicator -->
      <div class="absolute top-0 right-0 bg-gradient-to-l from-white to-transparent dark:from-gray-800 w-8 h-full pointer-events-none z-10" v-if="hasHorizontalScroll"></div>
      <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700" style="min-width: 1200px;">
        <thead class="bg-gray-50 dark:bg-gray-700">
          <tr>
            <th
              v-for="column in columns"
              :key="column.key"
              :class="[
                'px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider',
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
                  class="p-1 rounded hover:bg-gray-200 dark:hover:bg-gray-600"
                >
                  <ChevronUpDownIcon class="h-4 w-4" />
                </button>
              </div>
            </th>
            <th v-if="actions && actions.length > 0" class="px-3 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider w-20">
              Действия
            </th>
          </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200 dark:bg-gray-800 dark:divide-gray-700">
          <tr
            v-for="(item, index) in paginatedData"
            :key="getItemKey(item, index)"
            class="hover:bg-gray-50 dark:hover:bg-gray-700"
          >
            <td
              v-for="column in columns"
              :key="column.key"
              :class="[
                'px-3 py-3 text-sm text-gray-900 dark:text-gray-100',
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
              <div class="relative dropdown-container">
                <button
                  @click="toggleDropdown(index)"
                  class="inline-flex items-center p-2 border border-transparent text-sm font-medium rounded-md text-gray-500 hover:text-gray-700 hover:bg-gray-100 dark:text-gray-400 dark:hover:text-gray-200 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500"
                  :title="'Действия'"
                >
                  <EllipsisVerticalIcon class="h-5 w-5" />
                </button>
                
                <!-- Dropdown menu -->
                <div
                  v-if="isDropdownOpen(index)"
                  class="absolute right-0 z-10 mt-2 w-48 origin-top-right rounded-md bg-white dark:bg-gray-800 shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none"
                  role="menu"
                  aria-orientation="vertical"
                >
                  <div class="py-1">
                    <button
                      v-for="action in actions"
                      :key="action.label"
                      @click="action.action(item, index); closeDropdown(index)"
                      :disabled="action.disabled?.(item, index)"
                      :class="[
                        'group flex w-full items-center px-4 py-2 text-sm',
                        action.variant === 'danger' ? 'text-red-700 hover:bg-red-50 dark:text-red-300 dark:hover:bg-red-900/20' : 'text-gray-700 hover:bg-gray-50 dark:text-gray-300 dark:hover:bg-gray-700',
                        action.disabled?.(item, index) ? 'opacity-50 cursor-not-allowed' : 'cursor-pointer'
                      ]"
                      role="menuitem"
                    >
                      <component
                        v-if="action.icon"
                        :is="action.icon"
                        class="mr-3 h-4 w-4"
                      />
                      {{ action.label }}
                    </button>
                  </div>
                </div>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Empty state -->
    <div v-if="data.length === 0" class="text-center py-12">
      <div class="mx-auto h-12 w-12 text-gray-400 dark:text-gray-500">
        <DocumentTextIcon class="h-12 w-12" />
      </div>
      <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-gray-100">Нет данных</h3>
      <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Начните с добавления нового элемента.</p>
    </div>

    <!-- Pagination -->
    <div v-if="showPagination && totalPages > 1" class="bg-white px-4 py-3 flex items-center justify-between border-t border-gray-200 dark:bg-gray-800 dark:border-gray-700 sm:px-6">
      <div class="flex-1 flex justify-between sm:hidden">
        <button
          @click="goToPage(currentPage - 1)"
          :disabled="currentPage <= 1"
          class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed dark:border-gray-600 dark:text-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600"
        >
          Предыдущая
        </button>
        <button
          @click="goToPage(currentPage + 1)"
          :disabled="currentPage >= totalPages"
          class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed dark:border-gray-600 dark:text-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600"
        >
          Следующая
        </button>
      </div>
      <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
        <div>
          <p class="text-sm text-gray-700 dark:text-gray-300">
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
              class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed dark:border-gray-600 dark:bg-gray-700 dark:text-gray-400 dark:hover:bg-gray-600"
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
                    ? 'z-10 bg-primary-50 border-primary-500 text-primary-600 dark:bg-primary-900 dark:border-primary-400 dark:text-primary-300'
                    : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-400 dark:hover:bg-gray-600'
                ]"
              >
                {{ page }}
              </button>
              <span
                v-else
                class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300"
              >
                ...
              </span>
            </template>
            
            <button
              @click="goToPage(currentPage + 1)"
              :disabled="currentPage >= totalPages"
              class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed dark:border-gray-600 dark:bg-gray-700 dark:text-gray-400 dark:hover:bg-gray-600"
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
import { computed, ref, onMounted, onUnmounted } from 'vue'
import { ChevronUpDownIcon, ChevronLeftIcon, ChevronRightIcon, DocumentTextIcon, EllipsisVerticalIcon } from '@heroicons/vue/24/outline'
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

// Dropdown state
const openDropdowns = ref<Set<number>>(new Set())

// Horizontal scroll detection
const hasHorizontalScroll = ref(false)
const tableContainer = ref<HTMLElement | null>(null)

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

function toggleDropdown(index: number) {
  if (openDropdowns.value.has(index)) {
    openDropdowns.value.delete(index)
  } else {
    openDropdowns.value.clear()
    openDropdowns.value.add(index)
  }
}

function closeDropdown(index: number) {
  openDropdowns.value.delete(index)
}

function isDropdownOpen(index: number) {
  return openDropdowns.value.has(index)
}

// Close dropdown when clicking outside
function handleClickOutside(event: Event) {
  const target = event.target as HTMLElement
  if (!target.closest('.dropdown-container')) {
    openDropdowns.value.clear()
  }
}

function checkHorizontalScroll() {
  if (tableContainer.value) {
    hasHorizontalScroll.value = tableContainer.value.scrollWidth > tableContainer.value.clientWidth
  }
}

onMounted(() => {
  document.addEventListener('click', handleClickOutside)
  // Check scroll after component is mounted
  setTimeout(checkHorizontalScroll, 100)
  // Check on window resize
  window.addEventListener('resize', checkHorizontalScroll)
})

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside)
  window.removeEventListener('resize', checkHorizontalScroll)
})
</script>
