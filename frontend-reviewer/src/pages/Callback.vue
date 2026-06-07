<script setup lang="ts">
import { useAuth0 } from '@auth0/auth0-vue'

const { isLoading, error } = useAuth0()
</script>

<template>
  <div class="callback-page">
    <n-spin v-if="isLoading" size="large" />
    <p v-if="isLoading">Signing you in…</p>
    <p v-else-if="error" class="error">{{ error.message }}</p>
  </div>
</template>

<style scoped>
.callback-page {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  height: 100vh;
  gap: 16px;
  color: #666;
}

.error {
  color: #d03050;
}
</style>
