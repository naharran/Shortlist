<script setup lang="ts">
import { computed, h, ref, watch } from 'vue'
import { NTag } from 'naive-ui'
import { useAuth0 } from '@auth0/auth0-vue'
import type { Application, ApplicationSummary, Skill } from '@shared/types'
import ApplicationDrawer from './ApplicationDrawer.vue'

const apiUrl = import.meta.env.VITE_API_URL ?? 'http://localhost:8000'
const { getAccessTokenSilently, isAuthenticated, isLoading: authLoading } = useAuth0()

async function authFetch(url: string, options: RequestInit = {}): Promise<Response> {
  const token = await getAccessTokenSilently()

  if (!token) {
    throw new Error('No access token available. Log out and log back in.')
  }

  return fetch(url, {
    ...options,
    headers: {
      'Content-Type': 'application/json',
      Accept: 'application/json',
      Authorization: `Bearer ${token}`,
      ...(options.headers ?? {}),
    },
  })
}

type TabStatus = 'pending' | 'shortlisted' | 'rejected'

const activeTab = ref<TabStatus>('pending')
const applications = ref<ApplicationSummary[]>([])
const skills = ref<Skill[]>([])
const loading = ref(true)
const errorMessage = ref<string | null>(null)

const drawerOpen = ref(false)
const detailLoading = ref(false)
const reviewing = ref(false)
const reviewNote = ref('')
const reviewError = ref<string | null>(null)
const selectedApplication = ref<Application | null>(null)

const search = ref('')
const minExperience = ref<number | null>(null)
const maxExperience = ref<number | null>(null)
const minRisk = ref<number | null>(null)
const maxRisk = ref<number | null>(null)
const filterSkillIds = ref<number[]>([])
const filtersExpanded = ref(false)

const skillOptions = computed(() =>
  skills.value.map((s) => ({ label: s.name, value: s.id })),
)

const hasActiveFilters = computed(
  () =>
    search.value.trim() !== ''
    || minExperience.value !== null
    || maxExperience.value !== null
    || minRisk.value !== null
    || maxRisk.value !== null
    || filterSkillIds.value.length > 0,
)

const columns = [
  { title: 'Name', key: 'name' },
  { title: 'Email', key: 'email' },
  { title: 'Experience', key: 'overall_experience', width: 110 },
  {
    title: 'Risk Score',
    key: 'risk_score',
    width: 110,
    render: (row: ApplicationSummary) =>
      h(
        NTag,
        { type: row.risk_score >= 50 ? 'error' : row.risk_score >= 25 ? 'warning' : 'success' },
        { default: () => row.risk_score },
      ),
  },
  {
    title: 'Flags',
    key: 'heuristic_flags',
    width: 80,
    render: (row: ApplicationSummary) => row.heuristic_flags.length,
  },
  {
    title: 'Submitted',
    key: 'created_at',
    width: 180,
    render: (row: ApplicationSummary) => new Date(row.created_at).toLocaleString(),
  },
]

async function openDetail(id: number) {
  drawerOpen.value = true
  detailLoading.value = true
  reviewNote.value = ''
  reviewError.value = null
  selectedApplication.value = null

  try {
    const response = await authFetch(`${apiUrl}/api/applications/${id}`)
    if (!response.ok) throw new Error('Failed to load application')
    selectedApplication.value = await response.json()
  } catch (err) {
    errorMessage.value = err instanceof Error ? err.message : 'Could not load application details.'
    drawerOpen.value = false
  } finally {
    detailLoading.value = false
  }
}

async function submitReview(status: 'shortlisted' | 'rejected') {
  if (!selectedApplication.value) return

  reviewing.value = true
  reviewError.value = null

  try {
    const response = await authFetch(
      `${apiUrl}/api/applications/${selectedApplication.value.id}/review`,
      {
        method: 'PATCH',
        body: JSON.stringify({
          status,
          review_note: reviewNote.value || null,
        }),
      },
    )

    if (!response.ok) {
      const data = await response.json()
      reviewError.value = data.message ?? 'Review failed. Please try again.'
      return
    }

    applications.value = applications.value.filter(
      (app) => app.id !== selectedApplication.value!.id,
    )
    drawerOpen.value = false
    selectedApplication.value = null
    reviewNote.value = ''
  } catch {
    reviewError.value = 'Network error. Please try again.'
  } finally {
    reviewing.value = false
  }
}

const emptyMessages: Record<TabStatus, string> = {
  pending: 'No pending applications.',
  shortlisted: 'No shortlisted applications.',
  rejected: 'No rejected applications.',
}

function buildApplicationsUrl(status: TabStatus): string {
  const params = new URLSearchParams({ status })

  const term = search.value.trim()
  if (term) params.set('search', term)
  if (minExperience.value !== null) params.set('min_experience', String(minExperience.value))
  if (maxExperience.value !== null) params.set('max_experience', String(maxExperience.value))
  if (minRisk.value !== null) params.set('min_risk', String(minRisk.value))
  if (maxRisk.value !== null) params.set('max_risk', String(maxRisk.value))
  filterSkillIds.value.forEach((id) => params.append('skill_ids[]', String(id)))

  return `${apiUrl}/api/applications?${params}`
}

async function loadApplications(status: TabStatus) {
  loading.value = true
  errorMessage.value = null

  try {
    const response = await authFetch(buildApplicationsUrl(status))
    if (!response.ok) throw new Error('Failed to load applications')
    applications.value = await response.json()
  } catch (err) {
    errorMessage.value = err instanceof Error ? err.message : `Could not load ${status} applications.`
  } finally {
    loading.value = false
  }
}

function clearFilters() {
  search.value = ''
  minExperience.value = null
  maxExperience.value = null
  minRisk.value = null
  maxRisk.value = null
  filterSkillIds.value = []
  loadApplications(activeTab.value)
}

async function switchTab(tab: TabStatus) {
  activeTab.value = tab
  await loadApplications(tab)
}

let initialized = false

async function loadInitialData() {
  try {
    const skillsRes = await fetch(`${apiUrl}/api/skills`)
    if (!skillsRes.ok) throw new Error('Failed to load skills')
    skills.value = await skillsRes.json()
    await loadApplications('pending')
  } catch (err) {
    errorMessage.value = err instanceof Error ? err.message : 'Could not load applications.'
    loading.value = false
  }
}

watch(search, (value, previous) => {
  if (previous !== undefined && value === '' && previous !== '') {
    loadApplications(activeTab.value)
  }
})

watch(
  () => authLoading.value || !isAuthenticated.value,
  (blocked) => {
    if (blocked || initialized) return
    initialized = true
    loadInitialData()
  },
  { immediate: true },
)
</script>

<template>
  <div class="page">
    <n-card title="Applications">
      <n-tabs :value="activeTab" @update:value="(tab: TabStatus) => switchTab(tab)">
        <n-tab-pane name="pending" tab="Pending" />
        <n-tab-pane name="shortlisted" tab="Shortlisted" />
        <n-tab-pane name="rejected" tab="Rejected" />
      </n-tabs>

      <div class="filters">
        <n-input
          v-model:value="search"
          class="search-input"
          placeholder="Search name, email, position, cover letter…"
          clearable
          @keyup.enter="loadApplications(activeTab)"
        />

        <n-button type="primary" @click="loadApplications(activeTab)">Search</n-button>

        <n-button text @click="filtersExpanded = !filtersExpanded">
          {{ filtersExpanded ? 'Hide filters' : 'Show filters' }}
          <span v-if="hasActiveFilters && !filtersExpanded" class="filter-dot">●</span>
        </n-button>
      </div>

      <div v-if="filtersExpanded" class="filter-panel">
        <div class="filter-row">
          <label>Experience (years)</label>
          <n-input-number v-model:value="minExperience" :min="0" placeholder="Min" size="small" />
          <span>–</span>
          <n-input-number v-model:value="maxExperience" :min="0" placeholder="Max" size="small" />
        </div>

        <div class="filter-row">
          <label>Risk score</label>
          <n-input-number v-model:value="minRisk" :min="0" :max="100" placeholder="Min" size="small" />
          <span>–</span>
          <n-input-number v-model:value="maxRisk" :min="0" :max="100" placeholder="Max" size="small" />
        </div>

        <div class="filter-row">
          <label>Skills</label>
          <n-select
            v-model:value="filterSkillIds"
            :options="skillOptions"
            multiple
            filterable
            placeholder="Any skill"
            size="small"
            style="flex: 1"
          />
        </div>

        <n-space>
          <n-button type="primary" size="small" @click="loadApplications(activeTab)">
            Apply filters
          </n-button>
          <n-button size="small" :disabled="!hasActiveFilters" @click="clearFilters">
            Clear
          </n-button>
        </n-space>
      </div>

      <n-alert v-if="errorMessage" type="error" :title="errorMessage" style="margin: 16px 0" />

      <n-spin :show="loading">
        <n-data-table
          :columns="columns"
          :data="applications"
          :bordered="false"
          :pagination="{ pageSize: 10 }"
          :row-props="(row: ApplicationSummary) => ({
            style: 'cursor: pointer',
            onClick: () => openDetail(row.id),
          })"
        />
        <p v-if="!loading && !errorMessage && applications.length === 0" class="empty">
          {{ hasActiveFilters ? 'No applications match your search or filters.' : emptyMessages[activeTab] }}
        </p>
      </n-spin>
    </n-card>

    <ApplicationDrawer
      v-model:show="drawerOpen"
      v-model:review-note="reviewNote"
      :detail-loading="detailLoading"
      :selected-application="selectedApplication"
      :skills="skills"
      :review-error="reviewError"
      :reviewing="reviewing"
      @submit-review="submitReview"
    />
  </div>
</template>

<style scoped>
.page {
  max-width: 960px;
  margin: 0 auto;
  padding: 32px 16px;
}

.empty {
  text-align: center;
  color: #666;
  margin-top: 24px;
}

.filters {
  display: flex;
  align-items: center;
  gap: 12px;
  margin: 16px 0 8px;
}

.search-input {
  flex: 1;
}

.filter-dot {
  color: #2080f0;
  margin-left: 4px;
}

.filter-panel {
  display: flex;
  flex-direction: column;
  gap: 12px;
  padding: 12px;
  margin-bottom: 8px;
  background: #fafafa;
  border-radius: 6px;
}

.filter-row {
  display: flex;
  align-items: center;
  gap: 8px;
}

.filter-row label {
  width: 130px;
  font-size: 13px;
  color: #555;
  flex-shrink: 0;
}

.filter-row .n-input-number {
  width: 90px;
}
</style>
