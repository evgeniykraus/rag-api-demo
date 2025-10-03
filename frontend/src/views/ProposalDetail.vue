<template>
  <AppLayout>
    <div class="max-w-4xl mx-auto">
      <div class="space-y-6">
        <!-- Header -->
        <div class="md:flex md:items-center md:justify-between">
          <div class="flex-1 min-w-0">
            <nav class="flex" aria-label="Breadcrumb">
              <ol class="flex items-center space-x-4">
                <li>
                  <RouterLink to="/proposals" class="text-gray-400 hover:text-gray-500">
                    Обращения
                  </RouterLink>
                </li>
                <li>
                  <ChevronRightIcon class="h-5 w-5 text-gray-400" />
                </li>
                <li>
                  <span class="text-gray-500">Обращение #{{ proposal?.id }}</span>
                </li>
              </ol>
            </nav>
            <h2 class="mt-2 text-2xl font-bold leading-7 text-gray-900 sm:text-3xl sm:truncate">
              Обращение #{{ proposal?.id }}
            </h2>
          </div>
          <div class="mt-4 flex space-x-3 md:mt-0 md:ml-4">
            <button
              @click="openEditModal"
              class="btn btn-secondary btn-md"
            >
              <PencilIcon class="h-5 w-5 mr-2" />
              Редактировать
            </button>
            <button
              @click="openDeleteModal"
              class="btn btn-danger btn-md"
            >
              <TrashIcon class="h-5 w-5 mr-2" />
              Удалить
            </button>
          </div>
        </div>

        <!-- Loading State -->
        <div v-if="loading" class="flex justify-center py-12">
          <LoadingSpinner message="Загрузка обращения..." />
        </div>

        <!-- Error State -->
        <div v-else-if="error" class="bg-red-50 border border-red-200 rounded-md p-4">
          <div class="flex">
            <ExclamationTriangleIcon class="h-5 w-5 text-red-400" />
            <div class="ml-3">
              <h3 class="text-sm font-medium text-red-800">
                Ошибка загрузки
              </h3>
              <div class="mt-2 text-sm text-red-700">
                {{ error }}
              </div>
              <div class="mt-4">
                <button
                  @click="loadProposal"
                  class="btn btn-primary btn-sm"
                >
                  Попробовать снова
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Content -->
        <div v-else-if="proposal" class="space-y-6">
          <!-- Main Info -->
          <div class="bg-white shadow rounded-lg">
            <div class="px-6 py-4 border-b border-gray-200">
              <h3 class="text-lg font-medium text-gray-900">Основная информация</h3>
            </div>
            <div class="px-6 py-4 space-y-4">
              <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                  <dt class="text-sm font-medium text-gray-500">ID</dt>
                  <dd class="mt-1 text-sm text-gray-900">#{{ proposal.id }}</dd>
                </div>
                <div>
                  <dt class="text-sm font-medium text-gray-500">Дата создания</dt>
                  <dd class="mt-1 text-sm text-gray-900">{{ formatDate(proposal.created_at) }}</dd>
                </div>
                <div>
                  <dt class="text-sm font-medium text-gray-500">Город</dt>
                  <dd class="mt-1">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                      {{ proposal.city.name }}
                    </span>
                  </dd>
                </div>
                <div>
                  <dt class="text-sm font-medium text-gray-500">Категория</dt>
                  <dd class="mt-1">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                      {{ proposal.category.name }}
                    </span>
                  </dd>
                </div>
              </div>
            </div>
          </div>

          <!-- Content -->
          <div class="bg-white shadow rounded-lg">
            <div class="px-6 py-4 border-b border-gray-200">
              <h3 class="text-lg font-medium text-gray-900">Содержание обращения</h3>
            </div>
            <div class="px-6 py-4">
              <div class="prose max-w-none">
                <p class="text-gray-900 whitespace-pre-wrap">{{ proposal.content }}</p>
              </div>
            </div>
          </div>

          <!-- Response (view/edit/create) -->
          <div class="bg-white shadow rounded-lg">
            <div class="px-6 py-4 border-b border-gray-200">
              <h3 class="text-lg font-medium text-gray-900">Ответ на обращение</h3>
            </div>
            <div class="px-6 py-4 space-y-3">
              <!-- View mode -->
              <template v-if="!editingResponse">
                <div v-if="proposal.response">
                  <template v-if="typeof proposal.response !== 'string'">
                    <div class="text-sm text-gray-500" v-if="proposal.response?.created_at">
                      Дата ответа: {{ formatDate(proposal.response.created_at) }}
                    </div>
                    <div class="prose max-w-none" v-if="proposal.response?.content">
                      <p class="text-gray-900 whitespace-pre-wrap">{{ proposal.response.content }}</p>
                    </div>
                  </template>
                  <template v-else>
                    <div class="prose max-w-none">
                      <p class="text-gray-900 whitespace-pre-wrap">{{ proposal.response }}</p>
                    </div>
                  </template>
                </div>
                <div v-else class="text-sm text-gray-500">Ответ отсутствует</div>
                <div class="pt-2 flex items-center space-x-3">
                  <button @click="startEditResponse" class="btn btn-secondary btn-sm">
                    {{ proposal.response ? 'Редактировать ответ' : 'Добавить ответ' }}
                  </button>
                  <button @click="generateResponse" :disabled="generatingResponse" class="btn btn-primary btn-sm">
                    <span v-if="generatingResponse" class="animate-spin mr-2">⟳</span>
                    Сгенерировать ответ (AI)
                  </button>
                </div>
              </template>

              <!-- Edit mode -->
              <template v-else>
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">Текст ответа</label>
                  <textarea v-model="responseDraft" rows="6" class="input" placeholder="Введите ответ..." />
                </div>
                <div class="flex items-center space-x-3">
                  <button @click="saveResponse" :disabled="savingResponse || !responseDraft.trim()" class="btn btn-primary btn-sm">
                    <span v-if="savingResponse" class="animate-spin mr-2">⟳</span>
                    Сохранить
                  </button>
                  <button @click="cancelEditResponse" :disabled="savingResponse" class="btn btn-secondary btn-sm">Отмена</button>
                </div>
                <div v-if="saveError" class="text-sm text-red-600">{{ saveError }}</div>
              </template>

              <!-- AI Suggestion Panel -->
              <div v-if="aiSuggestion" class="mt-4 border border-blue-200 rounded-md bg-blue-50">
                <div class="px-4 py-2 border-b border-blue-200 flex items-center justify-between">
                  <div class="text-sm font-medium text-blue-800">Предложенный ответ (AI)</div>
                  <div class="flex items-center space-x-2">
                    <button @click="copyAISuggestion" class="btn btn-secondary btn-sm">Копировать</button>
                  </div>
                </div>
                <div class="p-4">
                  <pre class="whitespace-pre-wrap text-sm text-blue-900">{{ aiSuggestion }}</pre>
                </div>
              </div>
            </div>
          </div>

          <!-- AI Analysis -->
          <div class="bg-white shadow rounded-lg">
            <div class="px-6 py-4 border-b border-gray-200">
              <h3 class="text-lg font-medium text-gray-900 flex items-center">
                <CpuChipIcon class="h-5 w-5 mr-2 text-blue-500" />
                AI-анализ
              </h3>
            </div>
            <div class="px-6 py-4">
              <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                  <dt class="text-sm font-medium text-gray-500">Тональность</dt>
                  <dd class="mt-1">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                      Отрицательная
                    </span>
                  </dd>
                </div>
                <div>
                  <dt class="text-sm font-medium text-gray-500">Уверенность классификации</dt>
                  <dd class="mt-1 text-sm text-gray-900">85%</dd>
                </div>
              </div>
            </div>
          </div>

          <!-- Similar Proposals -->
          <div class="bg-white shadow rounded-lg">
            <div class="px-6 py-4 border-b border-gray-200">
              <h3 class="text-lg font-medium text-gray-900">Похожие обращения</h3>
            </div>
            <div class="px-6 py-4">
              <div class="space-y-4">
                <div
                  v-for="(similar, index) in similarProposals"
                  :key="similar.id"
                  class="border border-gray-200 rounded-lg p-4 hover:bg-gray-50"
                >
                  <div class="flex items-start justify-between">
                    <div class="flex-1 min-w-0">
                      <div class="text-sm text-gray-900">
                        <p v-if="!expandedSimilar[index] && similar.content.length > 150" class="line-clamp-2">
                          {{ similar.content.substring(0, 150) }}...
                        </p>
                        <p v-else class="whitespace-pre-wrap">
                          {{ similar.content }}
                        </p>
                        <button
                          v-if="similar.content.length > 150"
                          @click="toggleSimilarExpanded(index)"
                          class="mt-1 text-xs text-primary-600 hover:text-primary-800 font-medium"
                        >
                          {{ expandedSimilar[index] ? 'Свернуть' : 'Развернуть' }}
                        </button>
                      </div>
                      <div class="mt-2 flex items-center space-x-4 text-xs text-gray-500">
                        <span class="flex items-center">
                          <BuildingOfficeIcon class="h-4 w-4 mr-1" />
                          {{ similar.city.name }}
                        </span>
                        <span class="flex items-center">
                          <TagIcon class="h-4 w-4 mr-1" />
                          {{ similar.category.name }}
                        </span>
                        <span class="flex items-center">
                          <ClockIcon class="h-4 w-4 mr-1" />
                          {{ formatDate(similar.created_at) }}
                        </span>
                      </div>
                    </div>
                    <div class="ml-4 flex-shrink-0">
                      <button
                        @click="openProposalInNewTab(similar.id)"
                        class="inline-flex items-center p-1.5 border border-transparent text-xs font-medium rounded focus:outline-none focus:ring-1 focus:ring-offset-1 text-primary-700 bg-primary-100 hover:bg-primary-200 focus:ring-primary-500"
                        title="Просмотр"
                      >
                        <EyeIcon class="h-4 w-4" />
                      </button>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Edit Modal -->
        <EditProposalModal
          :is-open="showEditModal"
          :proposal="proposal"
          @close="closeEditModal"
          @updated="handleProposalUpdated"
        />

        <ConfirmModal
          :is-open="showDeleteModal"
          title="Подтверждение удаления"
          message="Вы уверены, что хотите удалить это обращение? Действие необратимо."
          confirm-text="Удалить"
          cancel-text="Отмена"
          :loading="deleting"
          @confirm="confirmDelete"
          @cancel="closeDeleteModal"
          @close="closeDeleteModal"
        />
      </div>
    </div>
  </AppLayout>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useProposalsStore } from '@/stores/proposals'
import { useDictionariesStore } from '@/stores/dictionaries'
import { useUIStore } from '@/stores/ui'
import { apiClient } from '@/services/api'
import type { Proposal } from '@/types'
import AppLayout from '@/components/common/AppLayout.vue'
import LoadingSpinner from '@/components/common/LoadingSpinner.vue'
import EditProposalModal from '@/components/common/EditProposalModal.vue'
import ConfirmModal from '@/components/common/ConfirmModal.vue'
import {
  ChevronRightIcon,
  PencilIcon,
  TrashIcon,
  ExclamationTriangleIcon,
  CpuChipIcon,
  BuildingOfficeIcon,
  TagIcon,
  ClockIcon,
  EyeIcon
} from '@heroicons/vue/24/outline'
import dayjs from 'dayjs'
import utc from 'dayjs/plugin/utc'
dayjs.extend(utc)

const router = useRouter()
const route = useRoute()
const proposalsStore = useProposalsStore()
const dictionariesStore = useDictionariesStore()
const uiStore = useUIStore()

const loading = ref(false)
const error = ref<string | null>(null)
const similarProposals = ref<Proposal[]>([])
const showEditModal = ref(false)
const expandedSimilar = ref<boolean[]>([])
const showDeleteModal = ref(false)
const deleting = ref(false)

const proposal = computed(() => proposalsStore.currentProposal)
const aiSuggestion = computed(() => proposalsStore.aiSuggestion)

const editingResponse = ref(false)
const responseDraft = ref('')
const savingResponse = ref(false)
const saveError = ref<string | null>(null)
const generatingResponse = ref(false)

function formatDate(date: string) {
  return dayjs.utc(date).format('DD.MM.YYYY HH:mm')
}

function toggleSimilarExpanded(index: number) {
  expandedSimilar.value[index] = !expandedSimilar.value[index]
}

function startEditResponse() {
  saveError.value = null
  editingResponse.value = true
  if (proposal.value?.response) {
    responseDraft.value = typeof proposal.value.response === 'string'
      ? proposal.value.response
      : (proposal.value.response.content || '')
  } else {
    responseDraft.value = ''
  }
}

function cancelEditResponse() {
  editingResponse.value = false
  responseDraft.value = ''
  saveError.value = null
}

async function saveResponse() {
  if (!proposal.value) return
  try {
    savingResponse.value = true
    saveError.value = null
    const updated = await proposalsStore.saveProposalResponse(proposal.value.id, responseDraft.value.trim())
    // store updates currentProposal
    editingResponse.value = false
    responseDraft.value = ''
    uiStore.showSuccess('Ответ сохранён')
  } catch (err: any) {
    saveError.value = err.message || 'Не удалось сохранить ответ'
    uiStore.showError('Ошибка', saveError.value)
  } finally {
    savingResponse.value = false
  }
}

async function generateResponse() {
  if (!proposal.value) return
  try {
    generatingResponse.value = true
    await proposalsStore.aiGenerateProposalResponse(proposal.value.id)
    uiStore.showSuccess('Предложение ответа сгенерировано')
  } catch (err: any) {
    uiStore.showError('Ошибка генерации', err.message || 'Не удалось сгенерировать ответ')
  } finally {
    generatingResponse.value = false
  }
}

async function copyAISuggestion() {
  if (!aiSuggestion.value) return
  try {
    await navigator.clipboard.writeText(aiSuggestion.value)
    uiStore.showSuccess('Скопировано')
  } catch (_) {
    uiStore.showError('Ошибка', 'Не удалось скопировать')
  }
}

// removed useAISuggestion per requirements

function openProposalInNewTab(proposalId: number) {
  const url = router.resolve(`/proposals/${proposalId}`)
  window.open(url.href, '_blank')
}

async function loadProposal() {
  const proposalId = Number(route.params.id)
  if (!proposalId) return
  
  loading.value = true
  error.value = null
  
  try {
    await proposalsStore.fetchProposal(proposalId)
    // Load similar proposals from API
    similarProposals.value = await apiClient.getSimilarProposals(proposalId)
    // Initialize expanded state for each similar proposal
    expandedSimilar.value = new Array(similarProposals.value.length).fill(false)
  } catch (err: any) {
    error.value = err.message || 'Ошибка загрузки обращения'
  } finally {
    loading.value = false
  }
}

function openEditModal() {
  showEditModal.value = true
}

function closeEditModal() {
  showEditModal.value = false
}

function handleProposalUpdated(updatedProposal: any) {
  // Обновляем текущее обращение в store
  proposalsStore.currentProposal = updatedProposal
}

async function handleDelete() {
  if (!proposal.value) return
  try {
    deleting.value = true
    await proposalsStore.deleteProposal(proposal.value.id)
    uiStore.showSuccess('Обращение удалено')
    closeDeleteModal()
    router.push('/proposals')
  } catch (err: any) {
    uiStore.showError('Ошибка удаления', 'Не удалось удалить обращение')
  } finally {
    deleting.value = false
  }
}

onMounted(async () => {
  await dictionariesStore.loadDictionaries()
  loadProposal()
})

function openDeleteModal() {
  showDeleteModal.value = true
}

function closeDeleteModal() {
  showDeleteModal.value = false
}

function confirmDelete() {
  handleDelete()
}
</script>

