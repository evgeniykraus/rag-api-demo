import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import apiClient from '@/services/api'
import type { Proposal, CreateProposalRequest, UpdateProposalRequest, ProposalFilters, ProposalMetadata } from '@/types'

export const useProposalsStore = defineStore('proposals', () => {
  // State
  const proposals = ref<Proposal[]>([])
  const currentProposal = ref<Proposal | null>(null)
  const loading = ref(false)
  const error = ref<string | null>(null)
  const pagination = ref({
    page: 1,
    perPage: 15,
    total: 0,
    totalPages: 0
  })
  const filters = ref<ProposalFilters>({})

  // Getters
  const filteredProposals = computed(() => {
    // Возвращаем данные как есть, так как фильтрация происходит на сервере
    return proposals.value
  })

  // Actions
  async function fetchProposals(page = 1) {
    try {
      loading.value = true
      error.value = null

      // Подготавливаем параметры запроса
      const params: any = {
        page,
        per_page: pagination.value.perPage
      }

      // Добавляем фильтры если они есть
      if (filters.value.city_id) {
        params.city_id = filters.value.city_id
      }
      if (filters.value.category_id) {
        params.category_id = filters.value.category_id
      }
      if (filters.value.date_from) {
        params.date_from = filters.value.date_from
      }
      if (filters.value.date_to) {
        params.date_to = filters.value.date_to
      }

      const response = await apiClient.getProposals(page, pagination.value.perPage, params)
      proposals.value = response.data

      if (response.meta) {
        pagination.value = {
          page: response.meta.current_page,
          perPage: response.meta.per_page,
          total: response.meta.total,
          totalPages: response.meta.last_page
        }
      }
    } catch (err: any) {
      error.value = err.message || 'Ошибка загрузки обращений'
      console.error('Error fetching proposals:', err)
    } finally {
      loading.value = false
    }
  }

  async function fetchProposal(id: number) {
    try {
      loading.value = true
      error.value = null

      currentProposal.value = await apiClient.getProposal(id)
    } catch (err: any) {
      error.value = err.message || 'Ошибка загрузки обращения'
      console.error('Error fetching proposal:', err)
    } finally {
      loading.value = false
    }
  }

  async function createProposal(data: CreateProposalRequest | FormData) {
    try {
      loading.value = true
      error.value = null

      const newProposal = await apiClient.createProposal(data)
      proposals.value.unshift(newProposal)
      return newProposal
    } catch (err: any) {
      error.value = err.message || 'Ошибка создания обращения'
      console.error('Error creating proposal:', err)
      throw err
    } finally {
      loading.value = false
    }
  }

  async function updateProposal(id: number, data: UpdateProposalRequest) {
    try {
      loading.value = true
      error.value = null

      const updatedProposal = await apiClient.updateProposal(id, data)

      const index = proposals.value.findIndex((p: Proposal) => p.id === id)
      if (index !== -1) {
        proposals.value[index] = updatedProposal
      }

      if (currentProposal.value?.id === id) {
        currentProposal.value = updatedProposal
      }

      return updatedProposal
    } catch (err: any) {
      error.value = err.message || 'Ошибка обновления обращения'
      console.error('Error updating proposal:', err)
      throw err
    } finally {
      loading.value = false
    }
  }

  async function deleteProposal(id: number) {
    try {
      loading.value = true
      error.value = null

      await apiClient.deleteProposal(id)
      proposals.value = proposals.value.filter((p: Proposal) => p.id !== id)

      if (currentProposal.value?.id === id) {
        currentProposal.value = null
      }
    } catch (err: any) {
      error.value = err.message || 'Ошибка удаления обращения'
      console.error('Error deleting proposal:', err)
      throw err
    } finally {
      loading.value = false
    }
  }

  async function getSimilarProposals(id: number) {
    try {
      loading.value = true
      error.value = null

      return await apiClient.getSimilarProposals(id)
    } catch (err: any) {
      error.value = err.message || 'Ошибка загрузки похожих обращений'
      console.error('Error fetching similar proposals:', err)
      throw err
    } finally {
      loading.value = false
    }
  }

  async function saveProposalResponse(id: number, content: string) {
    try {
      loading.value = true
      error.value = null
      const updated = await apiClient.postProposalResponse(id, content)
      // Обновляем в списке
      const index = proposals.value.findIndex((p: Proposal) => p.id === id)
      if (index !== -1) {
        proposals.value[index] = updated
      }
      // Обновляем детальное состояние
      if (currentProposal.value?.id === id) {
        currentProposal.value = updated
      }
      return updated
    } catch (err: any) {
      error.value = err.message || 'Ошибка сохранения ответа'
      console.error('Error saving response:', err)
      throw err
    } finally {
      loading.value = false
    }
  }

  const aiSuggestion = ref<string | null>(null)

  async function aiGenerateProposalResponse(id: number) {
    try {
      loading.value = true
      error.value = null
      const suggestion = await apiClient.aiGenerateProposalResponse(id)
      aiSuggestion.value = suggestion
      return suggestion
    } catch (err: any) {
      error.value = err.message || 'Ошибка генерации ответа'
      console.error('Error AI-generating response:', err)
      throw err
    } finally {
      loading.value = false
    }
  }

  async function analyzeProposal(id: number) {
    try {
      loading.value = true
      error.value = null
      await apiClient.analyzeProposal(id)
      // Обновляем флаг is_analyzing в текущем обращении
      if (currentProposal.value?.id === id) {
        currentProposal.value.is_analyzing = true
      }
      // Обновляем флаг в списке обращений
      const index = proposals.value.findIndex((p: Proposal) => p.id === id)
      if (index !== -1) {
        proposals.value[index].is_analyzing = true
      }
    } catch (err: any) {
      error.value = err.message || 'Ошибка анализа обращения'
      console.error('Error analyzing proposal:', err)
      throw err
    } finally {
      loading.value = false
    }
  }

  async function fetchProposalMetadata(id: number): Promise<ProposalMetadata> {
    try {
      loading.value = true
      error.value = null
      const metadata = await apiClient.getProposalMetadata(id)

      // Обновляем метаданные в текущем обращении
      if (currentProposal.value?.id === id) {
        currentProposal.value.metadata = metadata
        currentProposal.value.is_analyzing = false
      }

      // Обновляем метаданные в списке обращений
      const index = proposals.value.findIndex((p: Proposal) => p.id === id)
      if (index !== -1) {
        proposals.value[index].metadata = metadata
        proposals.value[index].is_analyzing = false
      }

      return metadata
    } catch (err: any) {
      error.value = err.message || 'Ошибка загрузки метаданных'
      console.error('Error fetching proposal metadata:', err)
      throw err
    } finally {
      loading.value = false
    }
  }

  async function searchProposals(query: string) {
    try {
      loading.value = true
      error.value = null

      // Устанавливаем поисковый запрос в фильтры
      filters.value.search = query

      // Выполняем поиск через API
      const results = await apiClient.searchProposals(query)
      proposals.value = results

      // Сбрасываем пагинацию для результатов поиска
      pagination.value = {
        page: 1,
        perPage: pagination.value.perPage,
        total: results.length,
        totalPages: 1
      }
    } catch (err: any) {
      error.value = err.message || 'Ошибка поиска обращений'
      console.error('Error searching proposals:', err)
    } finally {
      loading.value = false
    }
  }

  function setFilters(newFilters: ProposalFilters) {
    filters.value = { ...filters.value, ...newFilters }
  }

  function clearFilters() {
    filters.value = {}
  }

  function clearSearch() {
    filters.value.search = undefined
    fetchProposals(1)
  }

  function setPagination(page: number, perPage?: number) {
    pagination.value.page = page
    if (perPage) {
      pagination.value.perPage = perPage
    }
  }

  function clearError() {
    error.value = null
  }

  return {
    // State
    proposals,
    currentProposal,
    loading,
    error,
    pagination,
    filters,
    aiSuggestion,

    // Getters
    filteredProposals,

    // Actions
    fetchProposals,
    fetchProposal,
    createProposal,
    updateProposal,
    deleteProposal,
    getSimilarProposals,
    saveProposalResponse,
    aiGenerateProposalResponse,
    analyzeProposal,
    fetchProposalMetadata,
    searchProposals,
    setFilters,
    clearFilters,
    clearSearch,
    setPagination,
    clearError
  }
})
