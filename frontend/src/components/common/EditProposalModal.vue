<template>
  <div v-if="isOpen" class="fixed inset-0 z-50 overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
      <!-- Backdrop -->
      <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="closeModal"></div>

      <!-- Modal panel -->
      <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full">
        <form @submit.prevent="handleSubmit">
          <!-- Header -->
          <div class="bg-white px-6 py-4 border-b border-gray-200">
            <div class="flex items-center justify-between">
              <h3 class="text-lg font-medium text-gray-900">
                Редактирование обращения #{{ proposal?.id }}
              </h3>
              <button
                type="button"
                @click="closeModal"
                class="text-gray-400 hover:text-gray-600"
              >
                <XMarkIcon class="h-6 w-6" />
              </button>
            </div>
          </div>

          <!-- Body -->
          <div class="bg-white px-6 py-4 space-y-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">
                Содержание обращения
              </label>
              <textarea
                v-model="formData.content"
                rows="12"
                class="input"
                placeholder="Введите содержание обращения..."
                required
              ></textarea>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">
                Город
              </label>
              <select
                v-model="formData.city_id"
                class="input"
                required
              >
                <option value="">Выберите город</option>
                <option
                  v-for="city in citiesOptions"
                  :key="city.value"
                  :value="city.value"
                >
                  {{ city.label }}
                </option>
              </select>
            </div>
          </div>

          <!-- Footer -->
          <div class="bg-gray-50 px-6 py-3 flex justify-end space-x-3">
            <button
              type="button"
              @click="closeModal"
              class="btn btn-secondary btn-md"
            >
              Отмена
            </button>
            <button
              type="submit"
              :disabled="loading"
              class="btn btn-primary btn-md"
            >
              <span v-if="loading" class="animate-spin mr-2">⟳</span>
              {{ loading ? 'Сохранение...' : 'Сохранить' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { XMarkIcon } from '@heroicons/vue/24/outline'
import { useProposalsStore } from '@/stores/proposals'
import { useDictionariesStore } from '@/stores/dictionaries'
import { useUIStore } from '@/stores/ui'
import type { Proposal, UpdateProposalRequest } from '@/types'

interface Props {
  isOpen: boolean
  proposal: Proposal | null
}

interface Emits {
  (e: 'close'): void
  (e: 'updated', proposal: Proposal): void
}

const props = defineProps<Props>()
const emit = defineEmits<Emits>()

const proposalsStore = useProposalsStore()
const dictionariesStore = useDictionariesStore()
const uiStore = useUIStore()

const loading = ref(false)
const formData = ref<UpdateProposalRequest>({
  content: '',
  city_id: 0
})

const citiesOptions = computed(() => dictionariesStore.citiesOptions)

watch(() => props.proposal, (newProposal) => {
  if (newProposal) {
    formData.value = {
      content: newProposal.content,
      city_id: newProposal.city.id
    }
  }
}, { immediate: true })

async function handleSubmit() {
  if (!props.proposal) return

  try {
    loading.value = true
    const updatedProposal = await proposalsStore.updateProposal(
      props.proposal.id,
      formData.value
    )
    
    uiStore.showSuccess('Обращение обновлено')
    emit('updated', updatedProposal)
    closeModal()
  } catch (error) {
    uiStore.showError('Ошибка обновления', 'Не удалось обновить обращение')
  } finally {
    loading.value = false
  }
}

function closeModal() {
  emit('close')
}
</script>
