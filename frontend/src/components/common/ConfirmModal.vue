<template>
  <div v-if="isOpen" class="fixed inset-0 z-50 overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
      <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="emit('close')"></div>

      <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-md sm:w-full">
        <div class="bg-white px-6 py-4 border-b border-gray-200">
          <h3 class="text-lg font-medium text-gray-900">{{ title }}</h3>
        </div>
        <div class="bg-white px-6 py-4">
          <p class="text-sm text-gray-700">{{ message }}</p>
        </div>
        <div class="bg-gray-50 px-6 py-3 flex justify-end space-x-3">
          <button type="button" @click="emit('cancel')" :disabled="loading" class="btn btn-secondary btn-md">{{ cancelText }}</button>
          <button type="button" @click="emit('confirm')" :disabled="loading" class="btn btn-danger btn-md">
            <span v-if="loading" class="animate-spin mr-2">⟳</span>
            {{ confirmText }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
interface Props {
  isOpen: boolean
  title?: string
  message?: string
  confirmText?: string
  cancelText?: string
  loading?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  title: 'Подтверждение действия',
  message: 'Вы уверены, что хотите продолжить?',
  confirmText: 'Подтвердить',
  cancelText: 'Отмена',
  loading: false
})

const emit = defineEmits<{
  (e: 'confirm'): void
  (e: 'cancel'): void
  (e: 'close'): void
}>()
</script>


