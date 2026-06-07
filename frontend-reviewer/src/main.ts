import { createApp } from 'vue'
import { createAuth0 } from '@auth0/auth0-vue'
import naive from 'naive-ui'
import router from './router'
import './style.css'
import App from './App.vue'

createApp(App)
  .use(router)
  .use(createAuth0({
    domain: import.meta.env.VITE_AUTH0_DOMAIN,
    clientId: import.meta.env.VITE_AUTH0_CLIENT_ID,
    authorizationParams: {
      redirect_uri: import.meta.env.VITE_AUTH0_REDIRECT_URI,
      audience: import.meta.env.VITE_AUTH0_AUDIENCE,
    },
    useRefreshTokens: true,
    cacheLocation: 'localstorage',
    useCookiesForTransactions: true,
  }))
  .use(naive)
  .mount('#app')
