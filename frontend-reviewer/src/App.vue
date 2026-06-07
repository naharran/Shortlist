<script setup lang="ts">
import { useAuth0 } from '@auth0/auth0-vue'
import { RouterView } from 'vue-router'

const { loginWithRedirect, logout, isAuthenticated, isLoading, user } = useAuth0()
</script>

<template>
  <div v-if="isLoading" class="loading">
    <n-spin size="large" />
  </div>

  <template v-else>
    <header class="header">
      <span class="brand">Shortlist — Reviewer</span>
      <div class="user-area">
        <template v-if="isAuthenticated">
          <span class="user-name">{{ user?.name ?? user?.email }}</span>
          <n-button size="small" @click="logout({ logoutParams: { returnTo: 'http://localhost:5174' } })">
            Log out
          </n-button>
        </template>
        <template v-else>
          <n-button type="primary" @click="loginWithRedirect()">Log in</n-button>
        </template>
      </div>
    </header>

    <RouterView />
  </template>
</template>

<style scoped>
.loading {
  display: flex;
  align-items: center;
  justify-content: center;
  height: 100vh;
}

.header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 12px 24px;
  background: #fff;
  border-bottom: 1px solid #e5e5e5;
}

.brand {
  font-weight: 600;
  font-size: 15px;
}

.user-area {
  display: flex;
  align-items: center;
  gap: 12px;
}

.user-name {
  font-size: 14px;
  color: #555;
}
</style>
