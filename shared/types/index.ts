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
