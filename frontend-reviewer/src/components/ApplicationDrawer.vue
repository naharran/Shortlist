<script setup lang="ts">
import type { Application, Skill } from '@shared/types'

const show = defineModel<boolean>('show', { required: true })
const reviewNote = defineModel<string>('reviewNote', { required: true })

defineProps<{
  detailLoading: boolean
  selectedApplication: Application | null
  skills: Skill[]
  reviewError: string | null
  reviewing: boolean
}>()

const emit = defineEmits<{
  submitReview: [status: 'shortlisted' | 'rejected']
}>()

function skillNames(skills: Skill[], ids: number[]): string {
  if (ids.length === 0) return '—'
  return ids
    .map((id) => skills.find((s) => s.id === id)?.name ?? `Skill #${id}`)
    .join(', ')
}
</script>

<template>
  <n-drawer v-model:show="show" :width="560" placement="right">
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
              {{ skillNames(skills, selectedApplication.top_skills) }}
            </n-descriptions-item>
            <n-descriptions-item label="Moderate Skills">
              {{ skillNames(skills, selectedApplication.moderate_skills) }}
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
              <n-button type="success" :loading="reviewing" @click="emit('submitReview', 'shortlisted')">
                Shortlist
              </n-button>
              <n-button type="error" :loading="reviewing" @click="emit('submitReview', 'rejected')">
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
</template>

<style scoped>
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
