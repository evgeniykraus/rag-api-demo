export * from './api'
export * from './ui'

// UI types
export interface BreadcrumbItem {
  label: string
  to?: string
}

export interface TableColumn {
  key: string
  label: string
  sortable?: boolean
  width?: string
  align?: 'left' | 'center' | 'right'
}

export interface TableAction {
  label: string
  icon?: string
  action: () => void
  variant?: 'primary' | 'secondary' | 'danger'
  disabled?: boolean
}

export interface SelectOption {
  value: any
  label: string
  disabled?: boolean
}

export interface ChartData {
  labels: string[]
  datasets: Array<{
    label: string
    data: number[]
    backgroundColor?: string | string[]
    borderColor?: string | string[]
    borderWidth?: number
  }>
}

export interface Notification {
  id: string
  type: 'success' | 'error' | 'warning' | 'info'
  title: string
  message?: string
  duration?: number
}

export interface LoadingState {
  isLoading: boolean
  message?: string
}

export interface PaginationState {
  page: number
  perPage: number
  total: number
  totalPages: number
}

