<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import type { ApplicationPayload, Skill } from '@shared/types'

const apiUrl = import.meta.env.VITE_API_URL ?? 'http://localhost:8000'

const skills = ref<Skill[]>([])
const loadingSkills = ref(true)
const submitting = ref(false)
const submitted = ref(false)
const errorMessage = ref<string | null>(null)

const form = ref<ApplicationPayload>({
  name: '',
  email: '',
  phone_number: '',
  position: 'Full-stack Developer',
  overall_experience: 0,
  top_skills: [],
  moderate_skills: [],
  cover_letter: '',
})

const skillOptions = computed(() =>
  skills.value.map((skill) => ({
    label: `${skill.name} (${skill.type})`,
    value: skill.id,
  })),
)

onMounted(async () => {
  try {
    const response = await fetch(`${apiUrl}/api/skills`)
    if (!response.ok) throw new Error('Failed to load skills')
    skills.value = await response.json()
  } catch {
    errorMessage.value = 'Could not load skills. Please refresh the page.'
  } finally {
    loadingSkills.value = false
  }
})

async function submit() {
  submitting.value = true
  errorMessage.value = null

  try {
    const response = await fetch(`${apiUrl}/api/applications`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        Accept: 'application/json',
      },
      body: JSON.stringify(form.value),
    })

    if (!response.ok) {
      const data = await response.json()
      if (response.status === 422 && data.errors) {
        const messages = Object.values(data.errors).flat().join(' ')
        errorMessage.value = messages
      } else {
        errorMessage.value = data.message ?? 'Submission failed. Please try again.'
      }
      return
    }

    submitted.value = true
  } catch {
    errorMessage.value = 'Network error. Please try again.'
  } finally {
    submitting.value = false
  }
}
</script>

<template>
  <div class="page">
    <n-card v-if="submitted" title="Application Submitted">
      <p>Thank you! Your application has been received and is pending review.</p>
    </n-card>

    <n-card v-else title="Apply — Full-stack Developer">
      <n-alert v-if="errorMessage" type="error" :title="errorMessage" style="margin-bottom: 16px" />

      <n-form label-placement="top">
        <n-form-item label="Full name" required>
          <n-input v-model:value="form.name" placeholder="Jane Doe" />
        </n-form-item>

        <n-form-item label="Email" required>
          <n-input v-model:value="form.email" type="text" placeholder="jane@example.com" />
        </n-form-item>

        <n-form-item label="Phone number" required>
          <n-input v-model:value="form.phone_number" placeholder="0501234567" />
        </n-form-item>

        <n-form-item label="Position">
          <n-input v-model:value="form.position" disabled />
        </n-form-item>

        <n-form-item label="Years of experience" required>
          <n-input-number v-model:value="form.overall_experience" :min="0" :max="50" style="width: 100%" />
        </n-form-item>

        <n-form-item label="Top skills (expert level)" required>
          <n-select
            v-model:value="form.top_skills"
            multiple
            filterable
            :options="skillOptions"
            :loading="loadingSkills"
            placeholder="Search and select skills"
          />
        </n-form-item>

        <n-form-item label="Moderate skills (comfortable, not expert)">
          <n-select
            v-model:value="form.moderate_skills"
            multiple
            filterable
            :options="skillOptions"
            :loading="loadingSkills"
            placeholder="Search and select skills"
          />
        </n-form-item>

        <n-form-item label="Cover letter" required>
          <n-input
            v-model:value="form.cover_letter"
            type="textarea"
            placeholder="Tell us about your experience, projects, and what makes you a good fit..."
            :rows="8"
          />
        </n-form-item>

        <n-button type="primary" :loading="submitting" :disabled="loadingSkills" @click="submit">
          Submit Application
        </n-button>
      </n-form>
    </n-card>
  </div>
</template>

<style scoped>
.page {
  max-width: 640px;
  margin: 0 auto;
  padding: 32px 16px;
}
</style>
