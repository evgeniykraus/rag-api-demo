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
                Анализ ответа (AI)
              </h3>
            </div>
            <div class="px-6 py-4 space-y-6">
              <div v-if="!proposal.metadata" class="text-sm text-gray-500">
                Метаданные анализа пока отсутствуют.
              </div>

              <template v-else>
                <!-- Scores -->
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                  <div>
                    <dt class="text-sm font-medium text-gray-500">Соответствие вопросу</dt>
                    <dd class="mt-1">
                      <div class="h-2 w-full bg-gray-100 rounded">
                        <div
                          class="h-2 rounded bg-green-500"
                          :style="{ width: formatPercentWidth(proposal.metadata.correctness_score) }"
                          aria-label="Оценка соответствия"
                          role="progressbar"
                          :aria-valuenow="Math.round((proposal.metadata.correctness_score||0)*100)"
                          aria-valuemin="0"
                          aria-valuemax="100"
                        />
                      </div>
                      <div class="mt-1 text-xs text-gray-600">{{ formatPercent(proposal.metadata.correctness_score) }}</div>
                    </dd>
                  </div>
                  <div>
                    <dt class="text-sm font-medium text-gray-500">Полнота ответа</dt>
                    <dd class="mt-1">
                      <div class="h-2 w-full bg-gray-100 rounded">
                        <div
                          class="h-2 rounded bg-indigo-500"
                          :style="{ width: formatPercentWidth(proposal.metadata.completeness_score) }"
                          aria-label="Оценка полноты"
                          role="progressbar"
                          :aria-valuenow="Math.round((proposal.metadata.completeness_score||0)*100)"
                          aria-valuemin="0"
                          aria-valuemax="100"
                        />
                      </div>
                      <div class="mt-1 text-xs text-gray-600">{{ formatPercent(proposal.metadata.completeness_score) }}</div>
                    </dd>
                  </div>
                  <div>
                    <dt class="text-sm font-medium text-gray-500">Конкретность действий</dt>
                    <dd class="mt-1">
                      <div class="h-2 w-full bg-gray-100 rounded">
                        <div
                          class="h-2 rounded bg-amber-500"
                          :style="{ width: formatPercentWidth(proposal.metadata.actionable_score) }"
                          aria-label="Оценка конкретности"
                          role="progressbar"
                          :aria-valuenow="Math.round((proposal.metadata.actionable_score||0)*100)"
                          aria-valuemin="0"
                          aria-valuemax="100"
                        />
                      </div>
                      <div class="mt-1 text-xs text-gray-600">{{ formatPercent(proposal.metadata.actionable_score) }}</div>
                    </dd>
                  </div>
                </div>

                <!-- Tone & clarity -->
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                  <div>
                    <dt class="text-sm font-medium text-gray-500">Вежливость тона</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ formatPercent(proposal.metadata.tone_politeness_score) }}</dd>
                  </div>
                  <div>
                    <dt class="text-sm font-medium text-gray-500">Ясность формулировок</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ formatPercent(proposal.metadata.clarity_score) }}</dd>
                  </div>
                  <div>
                    <dt class="text-sm font-medium text-gray-500">Канцелярит/жаргон</dt>
                    <dd class="mt-1">
                      <span
                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                        :class="proposal.metadata.jargon_flag ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800'"
                      >
                        {{ proposal.metadata.jargon_flag ? 'Есть' : 'Нет' }}
                      </span>
                    </dd>
                  </div>
                </div>

                <!-- Missing points -->
                <div v-if="proposal.metadata.missing_points?.length">
                  <dt class="text-sm font-medium text-gray-500">Что можно улучшить</dt>
                  <dd class="mt-2">
                    <ul class="list-disc space-y-1 pl-5 text-sm text-gray-800">
                      <li v-for="(mp, i) in proposal.metadata.missing_points" :key="i">{{ mp }}</li>
                    </ul>
                  </dd>
                </div>

                <!-- Compliance risks -->
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                  <div>
                    <dt class="text-sm font-medium text-gray-500">Соответствие правилам</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ formatPercent(proposal.metadata.policy_compliance_score) }}</dd>
                  </div>
                  <div class="sm:col-span-2">
                    <dt class="text-sm font-medium text-gray-500">Риски</dt>
                    <dd class="mt-1">
                      <div class="flex flex-wrap gap-2">
                        <span
                          v-for="(risk, idx) in (proposal.metadata.risk_flags || [])"
                          :key="risk + idx"
                          class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800"
                        >
                          {{ translateRisk(risk) }}
                        </span>
                        <span v-if="!proposal.metadata.risk_flags || proposal.metadata.risk_flags.length === 0" class="text-sm text-gray-500">Не выявлено</span>
                      </div>
                    </dd>
                  </div>
                </div>

                <!-- Tags & entities -->
                <div class="grid grid-cols-1 gap-6 sm:grid-cols-3">
                  <div class="sm:col-span-1">
                    <dt class="text-sm font-medium text-gray-500">Теги тематики</dt>
                    <dd class="mt-2">
                      <div class="flex flex-wrap gap-2">
                        <span
                          v-for="(tag, i) in (proposal.metadata.intent_tags || [])"
                          :key="tag + i"
                          class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800"
                          :title="tag"
                        >
                          {{ translateIntentTag(tag) }}
                        </span>
                        <span v-if="!proposal.metadata.intent_tags || proposal.metadata.intent_tags.length === 0" class="text-sm text-gray-500">—</span>
                      </div>
                    </dd>
                  </div>
                  <div class="sm:col-span-1">
                    <dt class="text-sm font-medium text-gray-500">Локации</dt>
                    <dd class="mt-2 text-sm text-gray-900">
                      <ul class="list-disc space-y-1 pl-5">
                        <li v-for="(loc, i) in (proposal.metadata.entities?.locations || [])" :key="loc + i">{{ loc }}</li>
                        <li v-if="!proposal.metadata.entities?.locations || proposal.metadata.entities.locations.length === 0" class="text-gray-500">—</li>
                      </ul>
                    </dd>
                  </div>
                  <div class="sm:col-span-1">
                    <dt class="text-sm font-medium text-gray-500">Объекты</dt>
                    <dd class="mt-2 text-sm text-gray-900">
                      <ul class="list-disc space-y-1 pl-5">
                        <li v-for="(obj, i) in (proposal.metadata.entities?.objects || [])" :key="obj + i">{{ obj }}</li>
                        <li v-if="!proposal.metadata.entities?.objects || proposal.metadata.entities.objects.length === 0" class="text-gray-500">—</li>
                      </ul>
                    </dd>
                  </div>
                </div>

                <!-- Resolution -->
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                  <div>
                    <dt class="text-sm font-medium text-gray-500">Вероятность решения</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ formatPercent(proposal.metadata.resolution_likelihood) }}</dd>
                  </div>
                  <div>
                    <dt class="text-sm font-medium text-gray-500">Нужен фоллоу-ап</dt>
                    <dd class="mt-1">
                      <span
                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                        :class="proposal.metadata.followup_needed ? 'bg-yellow-100 text-yellow-800' : 'bg-green-100 text-green-800'"
                      >
                        {{ proposal.metadata.followup_needed ? 'Да' : 'Нет' }}
                      </span>
                    </dd>
                  </div>
                  <div class="sm:col-span-1">
                    <dt class="text-sm font-medium text-gray-500">Следующие шаги</dt>
                    <dd class="mt-2">
                      <ul class="list-disc space-y-1 pl-5 text-sm text-gray-900">
                        <li v-for="(step, i) in (proposal.metadata.next_steps || [])" :key="step + i">{{ step }}</li>
                        <li v-if="!proposal.metadata.next_steps || proposal.metadata.next_steps.length === 0" class="text-gray-500">—</li>
                      </ul>
                    </dd>
                  </div>
                </div>

                <div class="text-xs text-gray-500" v-if="proposal.metadata.processed_at">
                  Обработано: {{ formatDate(proposal.metadata.processed_at) }}
                </div>
              </template>
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

function formatPercent(value?: number | string | null) {
  const v = typeof value === 'string' ? parseFloat(value) : (typeof value === 'number' ? value : 0)
  return `${Math.round(v * 100)}%`
}

function formatPercentWidth(value?: number | string | null) {
  const v = typeof value === 'string' ? parseFloat(value) : (typeof value === 'number' ? value : 0)
  return `${Math.max(0, Math.min(100, Math.round(v * 100)))}%`
}

function translateRisk(risk: string) {
  switch (risk) {
    case 'personal_data':
      return 'Персональные данные'
    case 'legal_risk':
      return 'Юридический риск'
    case 'incorrect_commitment':
      return 'Некорректное обещание'
    default:
      return risk
  }
}

function translateIntentTag(tag: string) {
  const tagTranslations: Record<string, string> = {
    'report_problem': 'Сообщение о проблеме',
    'request_resolution': 'Запрос на решение',
    'complaint': 'Жалоба',
    'suggestion': 'Предложение',
    'information_request': 'Запрос информации',
    'urgent_request': 'Срочный запрос',
    'maintenance_request': 'Запрос на обслуживание',
    'safety_issue': 'Проблема безопасности',
    'environmental_issue': 'Экологическая проблема',
    'infrastructure_issue': 'Проблема инфраструктуры',
    'housing_issue': 'Жилищная проблема',
    'transport_issue': 'Транспортная проблема',
    'utility_issue': 'Коммунальная проблема',
    'cleanliness_issue': 'Проблема чистоты',
    'noise_complaint': 'Жалоба на шум',
    'parking_issue': 'Проблема с парковкой',
    'lighting_issue': 'Проблема освещения',
    'road_issue': 'Проблема дороги',
    'sidewalk_issue': 'Проблема тротуара',
    'green_space_issue': 'Проблема зеленых зон',
    'public_service_issue': 'Проблема общественных услуг',
    'administrative_request': 'Административный запрос',
    'legal_question': 'Правовой вопрос',
    'thank_you': 'Благодарность',
    'follow_up': 'Дополнительное обращение',
  }
  return tagTranslations[tag] || tag
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

