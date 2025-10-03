// API Response types
export interface ApiResponse<T> {
  data: T
  links?: PaginationLinks
  meta?: PaginationMeta
}

export interface PaginationLinks {
  first: string | null
  last: string | null
  prev: string | null
  next: string | null
}

export interface PaginationMeta {
  current_page: number
  from: number
  last_page: number
  links: PaginationLink[]
  path: string
  per_page: number
  to: number
  total: number
}

export interface PaginationLink {
  url: string | null
  label: string
  page: number | null
  active: boolean
}

// Proposal types
export interface Proposal {
  id: number
  content: string
  created_at: string
  updated_at: string
  city: City
  category: Category
  similarity?: number
  response?: string | {
    id: number
    content: string
    created_at: string
    updated_at: string
  } | null
  metadata?: ProposalMetadata | null
}

export interface ProposalWithParent extends Proposal {
  category: CategoryWithParent
}

export interface CategoryWithParent extends Category {
  parent: Category
}

// City types
export interface City {
  id: number
  name: string
}

// Category types
export interface Category {
  id: number
  name: string
  children?: Category[]
}

export interface CategoryTree extends Category {
  children: Category[]
}

// Proposal metadata (AI analysis)
export interface ProposalMetadata {
  correctness_score?: number | string | null
  completeness_score?: number | string | null
  actionable_score?: number | string | null
  missing_points?: string[] | null
  tone_politeness_score?: number | string | null
  clarity_score?: number | string | null
  jargon_flag?: boolean | null
  policy_compliance_score?: number | string | null
  risk_flags?: string[] | null
  intent_tags?: string[] | null
  entities?: {
    locations?: string[] | null
    objects?: string[] | null
  } | null
  resolution_likelihood?: number | string | null
  followup_needed?: boolean | null
  next_steps?: string[] | null
  processed_at?: string | null
}

// Request types
export interface CreateProposalRequest {
  city_id: number
  content: string
}

export interface UpdateProposalRequest {
  city_id?: number
  content?: string
}

export interface SearchProposalsRequest {
  query: string
}

// AI Classification types
export interface SentimentAnalysis {
  sentiment: 'positive' | 'negative' | 'neutral' | 'meaningless'
  confidence: number
}

export interface CategoryClassification {
  id: number
  confidence?: number
}

// Error types
export interface ApiError {
  error: string
  message: string
  details?: Record<string, any>
}

// Filter types
export interface ProposalFilters {
  city_id?: number
  category_id?: number
  date_from?: string
  date_to?: string
  search?: string
}

// Analytics types
export interface AnalyticsData {
  total_proposals: number
  proposals_by_city: Array<{
    city: string
    count: number
  }>
  proposals_by_category: Array<{
    category: string
    count: number
  }>
  proposals_by_month: Array<{
    month: string
    count: number
  }>
}

