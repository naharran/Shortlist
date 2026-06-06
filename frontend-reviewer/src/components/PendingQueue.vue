<script setup lang="ts">
import { h, onMounted, ref } from 'vue'
import { NTag } from 'naive-ui'
import type { Application, ApplicationSummary, Skill } from '@shared/types'

const apiUrl = import.meta.env.VITE_API_URL ?? 'http://localhost:8000'

const applications = ref<ApplicationSummary[]>([])
const skills = ref<Skill[]>([])
const loading = ref(true)
const errorMessage = ref<string | null>(null)

const drawerOpen = ref(false)
const detailLoading = ref(false)
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
  selectedApplication.value = null

  try {
    const response = await fetch(`${apiUrl}/api/applications/${id}`)
    if (!response.ok) throw new Error('Failed to load application')
    selectedApplication.value = await response.json()
  } catch {
    errorMessage.value = 'Could not load application details.'
    drawerOpen.value = false
  } finally {
    detailLoading.value = false
  }
}

onMounted(async () => {
  try {
    const [appsRes, skillsRes] = await Promise.all([
      fetch(`${apiUrl}/api/applications?status=pending`),
      fetch(`${apiUrl}/api/skills`),
    ])
    if (!appsRes.ok || !skillsRes.ok) throw new Error('Failed to load data')
    applications.value = await appsRes.json()
    skills.value = await skillsRes.json()
  } catch {
    errorMessage.value = 'Could not load pending applications.'
  } finally {
    loading.value = false
  }
})
</script>

<template>
  <div class="page">
    <n-card title="Pending Applications">
      <n-alert v-if="errorMessage" type="error" :title="errorMessage" style="margin-bottom: 16px" />

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
          No pending applications.
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
