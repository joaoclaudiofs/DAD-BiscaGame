<template>
  <div :class="{ dark: isDarkMode }">
    <Toaster richColors />
    <nav
      class="bg-white dark:bg-slate-900 border-b border-slate-200 dark:border-slate-700 sticky top-0 z-50 backdrop-blur-sm bg-white/80 dark:bg-slate-900/80">
      <div class="max-w-7xl mx-auto px-6 py-4 flex flex-row justify-between items-center">
        <div class="flex items-center gap-2">
          <RouterLink to="/dashboard"
            class="text-2xl font-bold text-slate-900 dark:text-slate-50 hover:text-blue-600 dark:hover:text-blue-400 transition-colors">
            {{ pageTitle }}
          </RouterLink>
          <span v-if="authStore.currentUser" class="text-sm text-slate-600 dark:text-slate-400 ml-2">
            — {{ authStore.currentUser?.name }}
          </span>
        </div>
        <div class="flex items-center gap-4">
          <button @click="toggleDarkMode"
            class="p-2 rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700 transition-colors"
            :title="isDarkMode ? 'Modo claro' : 'Modo escuro'">
            <Sun v-if="isDarkMode" class="h-5 w-5" />
            <Moon v-else class="h-5 w-5" />
          </button>
          <NavBar @logout="logout" :userLoggedIn="authStore.isLoggedIn" />
        </div>
      </div>
    </nav>
    <div>
      <main>
        <RouterView />
      </main>
    </div>
  </div>
</template>

<script setup>
import { RouterLink, RouterView } from 'vue-router'
import { toast } from 'vue-sonner'
import 'vue-sonner/style.css'
import { ref, onMounted } from 'vue'
import { Sun, Moon } from 'lucide-vue-next'
import { Toaster } from '@/components/ui/sonner'
import NavBar from './components/layout/NavBar.vue'
import { useAuthStore } from './stores/auth'
import { useSocketStore } from './stores/socket'
import router from './router'

const authStore = useAuthStore()
const socketStore = useSocketStore()

const year = new Date().getFullYear()
const pageTitle = ref(`DAD ${year}/${String(year + 1).slice(-2)}`)

const isDarkMode = ref(true)

const toggleDarkMode = () => {
  isDarkMode.value = !isDarkMode.value
  localStorage.setItem('darkMode', isDarkMode.value ? 'true' : 'false')

  if (isDarkMode.value) {
    document.documentElement.classList.add('dark')
  } else {
    document.documentElement.classList.remove('dark')
  }
}

const logout = () => {
  toast.promise(authStore.logout(), {
    loading: 'A fazer chamada à API',
    success: () => {
      return 'Logout feito com sucesso'
    },
    error: (data) => `[API] Erro ao fazer logout - ${data?.response?.data?.message}`,
  })
  router.push('/')
}

onMounted(() => {
  socketStore.handleConnection();
  socketStore.handleMatchEvents();

  const savedDarkMode = localStorage.getItem('darkMode')
  isDarkMode.value = savedDarkMode !== null ? savedDarkMode === 'true' : true

  if (isDarkMode.value) {
    document.documentElement.classList.add('dark')
    localStorage.setItem('darkMode', 'true')
  } else {
    document.documentElement.classList.remove('dark')
    localStorage.setItem('darkMode', 'false')
  }
})
</script>

<style></style>
