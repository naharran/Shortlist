<script setup lang="ts">
import { h, ref, watch } from 'vue'
import { NTag } from 'naive-ui'
import { useAuth0 } from '@auth0/auth0-vue'
import type { Application, ApplicationSummary, Skill } from '@shared/types'

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

function skillNames(ids: number[]): string {
  if (ids.length === 0) return '—'
  return ids
    .map((id) => skills.value.find((s) => s.id === id)?.name ?? `Skill #${id}`)
    .join(', ')
}

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

async function loadApplications(status: TabStatus) {
  loading.value = true
  errorMessage.value = null

  try {
    const response = await authFetch(`${apiUrl}/api/applications?status=${status}`)
    if (!response.ok) throw new Error('Failed to load applications')
    applications.value = await response.json()
  } catch (err) {
    errorMessage.value = err instanceof Error ? err.message : `Could not load ${status} applications.`
  } finally {
    loading.value = false
  }
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
          {{ emptyMessages[activeTab] }}
        </p>
      </n-spin>
    </n-card>

    <n-drawer v-model:show="drawerOpen" :width="560" placement="right">
      <n-drawer-content :title="selectedApplication?.name ?? 'Application Details'">
        <n-spin :show="detailLoading">
          <template v-if="selectedApplication">
            <n-descriptions :column="1" label-placement="left" bordered>
              <n-descriptions-item label="Email">{{ selectedApplication.email }}</n-descriptions-item>
              <n-descriptions-item label="Phone">{{ selectedApplication.phone_number }}</n-descriptions-item>
              <n-descriptions-item label="Position">{{ selectedApplication.position }}</n-descriptions-item>
              <n-descriptions-item label="Experience">
                {{ selectedApplication.overall_experience }} years
              </n-descriptions-item>
              <n-descriptions-item label="Risk Score">
                <n-tag
                  :type="selectedApplication.risk_score >= 50 ? 'error' : selectedApplication.risk_score >= 25 ? 'warning' : 'success'"
                >
                  {{ selectedApplication.risk_score }}
                </n-tag>
              </n-descriptions-item>
              <n-descriptions-item label="Top Skills">
                {{ skillNames(selectedApplication.top_skills) }}
              </n-descriptions-item>
              <n-descriptions-item label="Moderate Skills">
                {{ skillNames(selectedApplication.moderate_skills) }}
              </n-descriptions-item>
            </n-descriptions>

            <h3 class="section-title">Flags</h3>
            <n-space v-if="selectedApplication.heuristic_flags.length > 0">
              <n-tag v-for="(flag, i) in selectedApplication.heuristic_flags" :key="i" type="warning">
                {{ flag.key }}
              </n-tag>
            </n-space>
            <p v-else class="muted">No flags.</p>

            <h3 class="section-title">Cover Letter</h3>
            <p class="cover-letter">{{ selectedApplication.cover_letter }}</p>

            <template v-if="selectedApplication.status === 'pending'">
              <h3 class="section-title">Review Note (optional)</h3>
              <n-input
                v-model:value="reviewNote"
                type="textarea"
                placeholder="Add a note about your decision..."
                :rows="3"
              />

              <n-alert v-if="reviewError" type="error" :title="reviewError" style="margin-top: 16px" />

              <n-space style="margin-top: 16px">
                <n-button type="success" :loading="reviewing" @click="submitReview('shortlisted')">
                  Shortlist
                </n-button>
                <n-button type="error" :loading="reviewing" @click="submitReview('rejected')">
                  Reject
                </n-button>
              </n-space>
            </template>

            <template v-else-if="selectedApplication.review_note">
              <h3 class="section-title">Review Note</h3>
              <p class="cover-letter">{{ selectedApplication.review_note }}</p>
            </template>
          </template>
        </n-spin>
      </n-drawer-content>
    </n-drawer>
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

.section-title {
  margin: 24px 0 8px;
  font-size: 14px;
  font-weight: 600;
}

.cover-letter {
  white-space: pre-wrap;
  line-height: 1.5;
  margin: 0;
}

.muted {
  color: #666;
  margin: 0;
}
</style>
