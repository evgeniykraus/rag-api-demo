import axios, { type AxiosInstance, type AxiosResponse } from 'axios'
import type {
  ApiResponse,
  Proposal,
  CreateProposalRequest,
  UpdateProposalRequest,
  SearchProposalsRequest,
  City,
  CategoryTree,
  ApiError
} from '@/types'

class ApiClient {
  private client: AxiosInstance

  constructor() {
    this.client = axios.create({
      baseURL: import.meta.env.VITE_API_BASE_URL || 'http://localhost:8088',
      timeout: 30000,
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json'
      }
    })

    // Request interceptor
    this.client.interceptors.request.use(
      (config) => {
        const apiKey = import.meta.env.VITE_API_KEY
        if (apiKey) {
          config.headers['x-api-key'] = apiKey
        }
        return config
      },
      (error) => {
        return Promise.reject(error)
      }
    )

    // Response interceptor
    this.client.interceptors.response.use(
      (response: AxiosResponse) => {
        return response
      },
      (error) => {
        const apiError: ApiError = {
          error: error.response?.data?.error || 'Unknown error',
          message: error.response?.data?.message || error.message,
          details: error.response?.data?.details
        }
        return Promise.reject(apiError)
      }
    )
  }

  // Proposals API
  async getProposals(page = 1, perPage = 15, additionalParams: any = {}): Promise<ApiResponse<Proposal[]>> {
    const params = { page, per_page: perPage, ...additionalParams }
    const response = await this.client.get(`/api/v1/proposals`, {
      params
    })
    return response.data
  }

  async getProposal(id: number): Promise<Proposal> {
    const response = await this.client.get(`/api/v1/proposals/${id}`)
    return response.data
  }

  async createProposal(data: CreateProposalRequest): Promise<Proposal> {
    const response = await this.client.post('/api/v1/proposals', data)
    return response.data
  }

  async updateProposal(id: number, data: UpdateProposalRequest): Promise<Proposal> {
    const response = await this.client.put(`/api/v1/proposals/${id}`, data)
    return response.data
  }

  async deleteProposal(id: number): Promise<void> {
    await this.client.delete(`/api/v1/proposals/${id}`)
  }

  async searchProposals(query: string): Promise<Proposal[]> {
    const response = await this.client.get('/api/v1/proposals/search', {
      params: { query }
    })
    // API возвращает данные в формате {data: [...]}
    return response.data.data || response.data
  }

  async getSimilarProposals(id: number): Promise<Proposal[]> {
    const response = await this.client.get(`/api/v1/proposals/${id}/similar`)
    return response.data.data || response.data
  }

  // Analytics API
  async getAnalyticsOverview(params: { from?: string; to?: string } = {}) {
    const response = await this.client.get('/api/v1/analytics/overview', { params })
    return response.data as {
      total_proposals: number
      period_proposals: number
      answered_share: number
      avg_response_time_seconds: number | null
    }
  }

  async getAnalyticsByPeriod(params: { granularity?: 'day' | 'week' | 'month'; from?: string; to?: string } = {}) {
    const response = await this.client.get('/api/v1/analytics/by-period', { params })
    return (response.data?.data || response.data) as Array<{ period: string; count: number }>
  }

  async getAnalyticsByCategory(limit = 10) {
    const response = await this.client.get('/api/v1/analytics/by-category', { params: { limit } })
    return (response.data?.data || response.data) as Array<{ category: string; count: number }>
  }

  async getAnalyticsByCity(limit = 10) {
    const response = await this.client.get('/api/v1/analytics/by-city', { params: { limit } })
    return (response.data?.data || response.data) as Array<{ city: string; count: number }>
  }

  // Proposal response API
  async postProposalResponse(id: number, content: string): Promise<Proposal> {
    const response = await this.client.post(`/api/v1/proposals/${id}/response`, { content })
    return response.data
  }

  async aiGenerateProposalResponse(id: number): Promise<string> {
    const response = await this.client.get(`/api/v1/proposals/${id}/response/ai-generate`)
    // API returns { response: string }
    return response.data?.response ?? response.data
  }
  // Dictionary API
  async getCities(): Promise<City[]> {
    const response = await this.client.get('/api/v1/dictionary/cities')
    return response.data
  }

  async getCategories(): Promise<CategoryTree[]> {
    const response = await this.client.get('/api/v1/dictionary/categories')
    return response.data
  }
}

export const apiClient = new ApiClient()
export default apiClient
