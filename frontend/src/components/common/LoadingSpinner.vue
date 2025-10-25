<template>
  <div class="flex items-center justify-center" :class="containerClass">
    <div class="flex items-center space-x-2">
      <div
        class="animate-spin rounded-full border-2 border-gray-300 border-t-primary-600 dark:border-gray-600"
        :class="spinnerClass"
      ></div>
      <span v-if="message" class="text-sm text-gray-600 dark:text-gray-400">{{ message }}</span>
    </div>
  </div>
</template>

<script setup lang="ts">
interface Props {
  size?: 'sm' | 'md' | 'lg'
  message?: string
  containerClass?: string
}

const props = withDefaults(defineProps<Props>(), {
  size: 'md',
  containerClass: 'py-8'
})

const spinnerClass = computed(() => {
  const sizes = {
    sm: 'h-4 w-4',
    md: 'h-6 w-6',
    lg: 'h-8 w-8'
  }
  return sizes[props.size]
})
</script>

