<template>
  <AppLayout>
    <div class="max-w-2xl mx-auto">
      <div class="space-y-6">
        <!-- Page-level error -->
        <div v-if="proposalsStore.error" class="bg-red-50 border border-red-200 rounded-md p-4">
          <div class="flex">
            <ExclamationTriangleIcon class="h-5 w-5 text-red-400" />
            <div class="ml-3">
              <h3 class="text-sm font-medium text-red-800">
                Ошибка
              </h3>
              <div class="mt-2 text-sm text-red-700">
                {{ proposalsStore.error }}
              </div>
            </div>
          </div>
        </div>
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
                  <span class="text-gray-500">Создать обращение</span>
                </li>
              </ol>
            </nav>
            <h2 class="mt-2 text-2xl font-bold leading-7 text-gray-900 sm:text-3xl sm:truncate">
              Создать обращение
            </h2>
          </div>
        </div>

        <!-- Form -->
        <div class="bg-white shadow rounded-lg">
          <!-- Form-level error -->
          <div v-if="proposalsStore.error" class="px-6 pt-4">
            <div class="bg-red-50 border border-red-200 rounded-md p-3">
              <div class="flex">
                <ExclamationTriangleIcon class="h-5 w-5 text-red-400" />
                <div class="ml-3">
                  <div class="text-sm text-red-700">
                    {{ proposalsStore.error }}
                  </div>
                </div>
              </div>
            </div>
          </div>
          <form @submit.prevent="handleSubmit">
            <div class="px-6 py-4 border-b border-gray-200">
              <h3 class="text-lg font-medium text-gray-900">Основная информация</h3>
            </div>
            
            <div class="px-6 py-4 space-y-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                  Содержание обращения *
                </label>
                <textarea
                  v-model="formData.content"
                  rows="12"
                  class="input"
                  placeholder="Опишите проблему или вопрос..."
                  required
                ></textarea>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                  Город *
                </label>
                <select
                  v-model="formData.city_id"
                  class="input"
                  required
                >
                  <option value="">Выберите город</option>
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
                  Изображения (jpg, bmp, png) — до 5 файлов, 10MB каждый
                </label>
                <input
                  type="file"
                  accept="image/jpeg,image/png,image/bmp"
                  multiple
                  @change="onFilesChange"
                  class="block w-full text-sm text-gray-900 file:mr-4 file:py-2 file:px-4 file:rounded file:border-0 file:text-sm file:font-semibold file:bg-gray-50 file:text-gray-700 hover:file:bg-gray-100"
                />
                <div v-if="previews.length" class="mt-3 grid grid-cols-3 gap-3">
                  <div v-for="(src, i) in previews" :key="i" class="relative group">
                    <img :src="src" class="h-24 w-full object-cover rounded border" />
                    <button
                      type="button"
                      @click="removeFile(i)"
                      class="absolute -top-2 -right-2 bg-black/70 text-white rounded-full w-6 h-6 flex items-center justify-center text-xs opacity-90 group-hover:opacity-100"
                      aria-label="Удалить изображение"
                      title="Удалить"
                    >
                      ×
                    </button>
                  </div>
                </div>
              </div>
            </div>

            <div class="bg-gray-50 px-6 py-3 flex justify-end space-x-3">
              <RouterLink
                to="/proposals"
                class="btn btn-secondary btn-md"
              >
                Отмена
              </RouterLink>
              <button
                type="submit"
                :disabled="loading"
                class="btn btn-primary btn-md"
              >
                <span v-if="loading" class="animate-spin mr-2">⟳</span>
                {{ loading ? 'Создание...' : 'Создать обращение' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useProposalsStore } from '@/stores/proposals'
import { useDictionariesStore } from '@/stores/dictionaries'
import { useUIStore } from '@/stores/ui'
import AppLayout from '@/components/common/AppLayout.vue'
import { ChevronRightIcon, ExclamationTriangleIcon } from '@heroicons/vue/24/outline'
import type { CreateProposalRequest } from '@/types'

const router = useRouter()
const proposalsStore = useProposalsStore()
const dictionariesStore = useDictionariesStore()
const uiStore = useUIStore()

const loading = ref(false)
const formData = ref<CreateProposalRequest>({
  content: '',
  city_id: 0
})
const files = ref<File[]>([])
const previews = ref<string[]>([])

function onFilesChange(e: Event) {
  const input = e.target as HTMLInputElement
  const selected = Array.from(input.files || [])
  const allowed = ['image/jpeg', 'image/png', 'image/bmp']
  const maxSize = 10 * 1024 * 1024

  // Текущие файлы в виде карты для дедупликации (name + lastModified)
  const keyOf = (f: File) => `${f.name}__${f.lastModified}`
  const existingMap = new Map(files.value.map(f => [keyOf(f), f]))

  for (const f of selected) {
    if (!allowed.includes(f.type) || f.size > maxSize) continue
    const key = keyOf(f)
    if (!existingMap.has(key)) existingMap.set(key, f)
    if (existingMap.size >= 5) break
  }

  files.value = Array.from(existingMap.values())

  // Пересобираем превью, освобождая старые URL
  for (const url of previews.value) URL.revokeObjectURL(url)
  previews.value = files.value.map(file => URL.createObjectURL(file))

  // Очистить input, чтобы можно было выбрать те же файлы снова
  input.value = ''
}

function removeFile(index: number) {
  const toRemove = previews.value[index]
  if (toRemove) URL.revokeObjectURL(toRemove)
  files.value.splice(index, 1)
  previews.value.splice(index, 1)
}

async function handleSubmit() {
  try {
    loading.value = true
    // send multipart only if files provided
    let payload: CreateProposalRequest | FormData = formData.value
    if (files.value.length) {
      const fd = new FormData()
      fd.append('content', formData.value.content)
      fd.append('city_id', String(formData.value.city_id))
      files.value.forEach((f) => fd.append('attachments[]', f))
      payload = fd
    }
    const newProposal = await proposalsStore.createProposal(payload)
    uiStore.showSuccess('Обращение создано')
    router.push(`/proposals/${newProposal.id}`)
  } catch (error) {
    uiStore.showError('Ошибка создания', 'Не удалось создать обращение')
  } finally {
    loading.value = false
  }
}

onMounted(async () => {
  await dictionariesStore.loadDictionaries()
})
</script>
