import { createApp } from 'vue'
import { createPinia } from 'pinia'
import { io } from 'socket.io-client'

import App from './App.vue'
import router from './router'
import { useAuthStore } from './stores/auth'

const apiDomain = import.meta.env.VITE_API_DOMAIN
const wsConnection = import.meta.env.VITE_WS_CONNECTION

console.log('[main.js] api domain', apiDomain)
console.log('[main.js] ws connection', wsConnection)

const app = createApp(App)

app.provide('socket', io(wsConnection))
app.provide('serverBaseURL', `http://${apiDomain}`)
app.provide('apiBaseURL', `http://${apiDomain}/api`)

app.use(createPinia())
app.use(router)

// Restore session after Pinia is initialized
const authStore = useAuthStore()
authStore.restoreSession()

app.mount('#app')
