<template>
  <AppLayout>
    <div class="min-h-screen flex items-center justify-center">
      <div class="text-center">
        <div class="mx-auto h-24 w-24 text-gray-400">
          <ExclamationTriangleIcon class="h-24 w-24" />
        </div>
        <h1 class="mt-4 text-3xl font-bold text-gray-900">404</h1>
        <h2 class="mt-2 text-xl text-gray-600">Страница не найдена</h2>
        <p class="mt-2 text-gray-500">
          Извините, мы не смогли найти страницу, которую вы ищете.
        </p>
        <div class="mt-6 flex justify-center space-x-4">
          <RouterLink
            to="/"
            class="btn btn-primary btn-md"
          >
            <HomeIcon class="h-5 w-5 mr-2" />
            На главную
          </RouterLink>
          <button
            @click="$router.go(-1)"
            class="btn btn-secondary btn-md"
          >
            <ArrowLeftIcon class="h-5 w-5 mr-2" />
            Назад
          </button>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup lang="ts">
import AppLayout from '@/components/common/AppLayout.vue'
import {
  ExclamationTriangleIcon,
  HomeIcon,
  ArrowLeftIcon
} from '@heroicons/vue/24/outline'
</script>

