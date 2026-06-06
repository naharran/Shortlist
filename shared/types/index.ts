export interface Skill {
  id: number
  name: string
  type: 'broad' | 'specific'
}

export interface ApplicationPayload {
  name: string
  email: string
  phone_number: string
  position: string
  overall_experience: number
  top_skills: number[]
  moderate_skills: number[]
  cover_letter: string
}

export interface ApplicationSummary {
  id: number
  name: string
  email: string
  position: string
  overall_experience: number
  status: 'pending' | 'shortlisted' | 'rejected'
  risk_score: number
  heuristic_flags: Array<{ key: string; [field: string]: unknown }>
  created_at: string
}

export interface Application extends ApplicationPayload {
  id: number
  status: 'pending' | 'shortlisted' | 'rejected'
  risk_score: number
  heuristic_flags: Array<{ key: string; [field: string]: unknown }>
  review_note: string | null
  reviewed_at: string | null
  created_at: string
  updated_at: string
}
