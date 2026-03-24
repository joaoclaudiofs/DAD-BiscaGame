<template>
  <div
    class="min-h-screen bg-gradient-to-br from-slate-50 to-blue-50 dark:from-slate-900 dark:to-slate-800 flex items-center justify-center p-6">
    <Card
      class="w-full max-w-xl bg-white/80 dark:bg-slate-900/80 backdrop-blur-sm border-slate-200 dark:border-slate-700 shadow-2xl">
      <div class="p-10 space-y-8">
        <div class="text-center space-y-4">
          <div
            class="mx-auto w-20 h-20 bg-gradient-to-br from-blue-500 to-indigo-600 dark:from-blue-600 dark:to-indigo-700 rounded-2xl flex items-center justify-center mb-4 shadow-lg">
            <img :src="logo" alt="Logo" class="h-20 w-20" />
          </div>
          <h1 class="text-4xl font-bold text-slate-900 dark:text-slate-50">Jogo da Bisca</h1>
        </div>

        <div class="space-y-4">
          <Button @click="goToLogin"
            class="w-full h-14 text-lg font-semibold bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 dark:from-blue-500 dark:to-indigo-500 dark:hover:from-blue-600 dark:hover:to-indigo-600"
            size="lg">
            <UserIcon class="h-5 w-5 mr-2" />
            Login
          </Button>

          <Button @click="playAsGuest" variant="outline"
            class="w-full h-14 text-lg font-semibold border-2 border-slate-300 dark:border-slate-600 hover:bg-slate-50 dark:hover:bg-slate-800"
            size="lg">
            <PlayIcon class="h-5 w-5 mr-2" />
            Continuar como Convidado
          </Button>
        </div>

        <div class="space-y-3 pt-4 border-t border-slate-200 dark:border-slate-700">
          <div class="text-center text-sm text-slate-600 dark:text-slate-400">
            <Badge variant="outline" class="px-2 py-1 border-slate-300 dark:border-slate-600">Novidade</Badge>
            <span class="ml-2">Joga como convidado sem criares conta</span>
          </div>
          <div class="text-center text-sm text-slate-600 dark:text-slate-400">
            <Badge variant="default" class="px-2 py-1 bg-blue-600 dark:bg-blue-500">Novo update</Badge>
            <span class="ml-2">Multiplayer - Desafia outros jogadores</span>
          </div>
        </div>
      </div>
    </Card>
  </div>
</template>

<script setup>
import logo from '@/assets/playing-cards.png'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import {
  User as UserIcon,
  Play as PlayIcon,
} from 'lucide-vue-next'

import Card from '@/components/ui/card/Card.vue'
import Button from '@/components/ui/button/Button.vue'
import Badge from '@/components/ui/badge/Badge.vue'

const router = useRouter()
const authStore = useAuthStore()

const goToLogin = () => {
  router.push('/login')
}

const playAsGuest = () => {
  //coloca o utilizador como convidado
  authStore.loginAsGuest()
  router.push('/dashboard')
}
</script>

<style scoped></style>
