<template>
  <div
    class="min-h-screen flex flex-col bg-gradient-to-br from-slate-50 to-blue-50 dark:from-slate-900 dark:to-slate-800">
    <main class="flex-1">
      <div class="px-6 py-8 max-w-7xl mx-auto w-full">
        <div v-if="isAnonymous"
          class="mb-6 rounded-2xl border border-slate-200/70 dark:border-slate-700/70 bg-white/70 dark:bg-slate-800/60 px-4 py-3 flex items-center justify-between gap-3">
          <div class="flex items-center gap-2">
            <div
              class="h-7 w-7 rounded-full bg-blue-600 text-white dark:bg-blue-500 flex items-center justify-center text-xs">
              <UserIcon class="h-3 w-3" />
            </div>
            <p class="text-xs sm:text-sm text-slate-700 dark:text-slate-300">
              Estás a jogar como convidado. Progresso, moedas e histórico não serão guardados.
            </p>
          </div>
          <Button size="sm" variant="outline" class="text-xs px-3 border-slate-300 dark:border-slate-600"
            @click="goToLogin">
            Login
          </Button>
        </div>

        <header class="flex items-center justify-between gap-4 mb-10">
          <div class="flex items-center gap-4">
            <Avatar
              class="h-16 w-16 rounded-full ring-1 ring-slate-200 dark:ring-slate-700 overflow-hidden bg-slate-200/60 dark:bg-slate-800/80">
              <AvatarImage :src="avatarUrl" class="h-full w-full object-cover" />
            </Avatar>
            <div>
              <h1 class="text-xl font-semibold text-slate-900 dark:text-slate-50">
                {{ isLoggedIn ? userName : 'Bem-vindo!' }}
              </h1>
              <p class="text-sm text-slate-500 dark:text-slate-400">
                {{
                  isLoggedIn
                    ? 'Escolhe como queres jogar hoje!'
                    : 'Treina contra o bot ou faz o login.'
                }}
              </p>
            </div>
          </div>

          <div class="flex items-center gap-2">
            <Badge v-if="isLoggedIn" variant="secondary"
              class="px-2.5 py-1.5 text-xs bg-blue-600 text-white dark:bg-blue-500">
              <CoinIcon class="h-3.5 w-3.5 mr-1 text-yellow-400" />
              {{ coinBalance }}
            </Badge>

            <Button v-if="isLoggedIn" variant="outline" size="sm"
              class="hidden sm:inline-flex text-xs px-3 py-1.5 border-slate-300 dark:border-slate-700 text-slate-800 dark:text-slate-200"
              @click="goToWallet">
              <CoinIcon class="h-3.5 w-3.5 mr-1 text-yellow-400" />
              {{ isAdmin ? 'Todas as Transações' : 'A Minha Carteira' }}
            </Button>

            <Button v-if="isLoggedIn" variant="outline" size="sm"
              class="hidden sm:inline-flex text-xs px-3 py-1.5 border-slate-300 dark:border-slate-700 text-slate-800 dark:text-slate-200"
              @click="goToMyProfile">
              <CircleUser class="h-3.5 w-3.5 mr-1 text-yellow-400" />
              O Meu Perfil
            </Button>

            <Button v-if="isLoggedIn" variant="ghost" size="icon"
              class="text-slate-500 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800"
              @click="handleLogout">
              <LogOutIcon class="h-4 w-4" />
            </Button>

            <Button v-if="isAnonymous" variant="ghost" size="icon"
              class="text-slate-500 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800" @click="goToHome">
              <HomeIcon class="h-4 w-4" />
            </Button>
          </div>
        </header>

        <section :class="sectionClasses">
          <div class="space-y-4">
            <Card v-if="isLoggedIn && isPlayer"
              class="p-6 rounded-2xl border border-slate-200/80 dark:border-slate-700/80 bg-white/80 dark:bg-slate-800/80 shadow-sm">
              <div class="flex items-start justify-between gap-4">
                <div class="space-y-2">
                  <h2 class="text-base font-semibold text-slate-900 dark:text-slate-50">
                    Partida Ranked
                  </h2>
                  <p class="text-xs text-slate-500 dark:text-slate-400 max-w-md">
                    Entra no lobby para seres emparelhado com outro jogador e competir na leaderboard!
                  </p>
                </div>
                <PlayIcon class="h-7 w-7 text-slate-400 dark:text-slate-500" />
              </div>

              <Button
                class="w-full mt-4 text-sm bg-blue-600 text-white hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-600"
                variant="secondary" @click="startRankedGame">
                <GamepadIcon class="h-4 w-4 mr-2" />
                Ir para o lobby de partidas
              </Button>
            </Card>

            <Card v-if="isAnonymous || isPlayer"
              class="p-6 rounded-2xl border border-slate-200/80 dark:border-slate-800/80 bg-white/80 dark:bg-slate-900/80 shadow-sm">
              <div class="flex items-start justify-between gap-4">
                <div class="space-y-2">
                  <h2 class="text-base font-semibold text-slate-900 dark:text-slate-50">
                    Treino contra o Bot
                  </h2>
                  <p class="text-xs text-slate-500 dark:text-slate-400">
                    Joga em modo de treino para testares estratégias e aprenderes a jogar.
                    <span v-if="isAnonymous"> Como és convidado, nada é guardado. </span>
                  </p>
                </div>
                <GamepadIcon class="h-6 w-6 text-slate-400 dark:text-slate-500" />
              </div>
              <Button
                class="w-full mt-4 text-sm border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-100 hover:bg-slate-100 dark:hover:bg-slate-800/70"
                variant="outline" @click="startPracticeGame3">
                <GamepadIcon class="h-4 w-4 mr-2" />
                Começar treino bisca 3
              </Button>
              <Button
                class="w-full mt-4 text-sm border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-100 hover:bg-slate-100 dark:hover:bg-slate-800/70"
                variant="outline" @click="startPracticeGame">
                <GamepadIcon class="h-4 w-4 mr-2" />
                Começar treino bisca 9
              </Button>
            </Card>
          </div>
          <div class="space-y-3">
            <Card
              class="p-4 rounded-2xl border border-slate-200/80 dark:border-slate-800/80 bg-white/80 dark:bg-slate-900/80 shadow-sm cursor-pointer hover:shadow-md hover:-translate-y-0.5 transition"
              :class="!canAccessHistory ? 'opacity-60 cursor-not-allowed' : ''"
              @click="canAccessHistory ? viewMatchHistory() : null">
              <div class="flex items-center justify-between gap-3">
                <div class="flex items-center gap-3">
                  <div
                    class="h-9 w-9 rounded-full bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-slate-500">
                    <TrophyIcon class="h-4.5 w-4.5" />
                  </div>
                  <div>
                    <h3 class="text-sm font-medium text-slate-900 dark:text-slate-50">
                      Histórico de Partidas
                    </h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400">
                      Aqui podes rever jogos e resultados
                    </p>
                  </div>
                </div>
              </div>
            </Card>

            <Card
              class="p-4 rounded-2xl border border-slate-200/80 dark:border-slate-800/80 bg-white/80 dark:bg-slate-900/80 shadow-sm cursor-pointer hover:shadow-md hover:-translate-y-0.5 transition"
              :class="!isLoggedIn ? 'opacity-60 cursor-not-allowed' : ''"
              @click="isLoggedIn ? viewPersonalStats() : null">
              <div class="flex items-center justify-between gap-3">
                <div class="flex items-center gap-3">
                  <div
                    class="h-9 w-9 rounded-full bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-slate-500">
                    <UserIcon class="h-4.5 w-4.5" />
                  </div>
                  <div>
                    <h3 class="text-sm font-medium text-slate-900 dark:text-slate-50">
                      {{ isAdmin ? 'Histórico de Jogadores' : 'O Meu Histórico' }}
                    </h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400">
                      Aqui podes rever estatísticas pessoais
                    </p>
                  </div>
                </div>
              </div>
            </Card>

            <Card
              class="p-4 rounded-2xl border border-slate-200/80 dark:border-slate-800/80 bg-white/80 dark:bg-slate-900/80 shadow-sm cursor-pointer hover:shadow-md hover:-translate-y-0.5 transition"
              :class="!canAccessLeaderboard ? 'opacity-60 cursor-not-allowed' : ''"
              @click="canAccessLeaderboard ? viewGlobalScoreboard() : null">
              <div class="flex items-center justify-between gap-3">
                <div class="flex items-center gap-3">
                  <div
                    class="h-9 w-9 rounded-full bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-slate-500">
                    <Crown class="h-4.5 w-4.5" />
                  </div>
                  <div>
                    <h3 class="text-sm font-medium text-slate-900 dark:text-slate-50">
                      Tabela de Classificação
                    </h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400">
                      Aqui podes ver a classificação global de jogadores
                    </p>
                  </div>
                </div>
              </div>
            </Card>

            <Card
              class="p-4 rounded-2xl border border-slate-200/80 dark:border-slate-800/80 bg-white/80 dark:bg-slate-900/80 shadow-sm cursor-pointer hover:shadow-md hover:-translate-y-0.5 transition"
              @click="viewPlatformStatistics()">
              <div class="flex items-center justify-between gap-3">
                <div class="flex items-center gap-3">
                  <div
                    class="h-9 w-9 rounded-full bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-slate-500">
                    <TrendingUpIcon class="h-4.5 w-4.5" />
                  </div>
                  <div>
                    <h3 class="text-sm font-medium text-slate-900 dark:text-slate-50">
                      Estatísticas globais
                    </h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400">
                      Aqui podes ver dados e gráficos sobre o jogo
                    </p>
                  </div>
                </div>
              </div>
            </Card>

            <Card v-if="isLoggedIn && isAdmin"
              class="p-4 rounded-2xl border border-slate-200/80 dark:border-slate-800/80 bg-white/80 dark:bg-slate-900/80 shadow-sm cursor-pointer hover:shadow-md hover:-translate-y-0.5 transition"
              @click="isAdmin ? goToAdminPage() : null">
              <div class="flex items-center justify-between gap-3">
                <div class="flex items-center gap-3">
                  <div
                    class="h-9 w-9 rounded-full bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-slate-500">
                    <UserPenIcon class="h-4.5 w-4.5" />
                  </div>
                  <div>
                    <h3 class="text-sm font-medium text-slate-900 dark:text-slate-50">
                      Administração
                    </h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400">
                      Aqui podes gerir os utilizadores
                    </p>
                  </div>
                </div>
              </div>
            </Card>
          </div>
        </section>
      </div>
    </main>

    <div v-if="isLoggedIn" class="h-16"></div>
  </div>
</template>

<script setup>
import { computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { storeToRefs } from 'pinia'
import { useAuthStore } from '@/stores/auth'
import { useWalletStore } from '@/stores/wallet'
import {
  Play as PlayIcon,
  Gamepad2 as GamepadIcon,
  Trophy as TrophyIcon,
  User as UserIcon,
  Crown,
  Coins as CoinIcon,
  LogOut as LogOutIcon,
  Home as HomeIcon,
  TrendingUp as TrendingUpIcon,
  UserPenIcon,
  CircleUser,
} from 'lucide-vue-next'

//componentes UI
import Card from '@/components/ui/card/Card.vue'
import Button from '@/components/ui/button/Button.vue'
import Avatar from '@/components/ui/avatar/Avatar.vue'
import AvatarImage from '@/components/ui/avatar/AvatarImage.vue'
import Badge from '@/components/ui/badge/Badge.vue'

const router = useRouter()
const authStore = useAuthStore()
const walletStore = useWalletStore()
const {
  currentUser,
  isLoggedIn,
  isAnonymous,
  isAdmin,
  canAccessLeaderboard,
  canAccessHistory,
} = storeToRefs(authStore)

//se for admin queremos uma coluna só, se for player duas colunas
const sectionClasses = computed(() => {
  const base = 'grid gap-6 items-start'
  if (isAdmin.value) {
    return `${base} lg:grid-cols-1 lg:max-w-3xl mx-auto`
  }
  return `${base} lg:grid-cols-[minmax(0,1.6fr)_minmax(0,1.1fr)]`
})

//nome do utilizador autenticado ou convidado
const userName = computed(() => currentUser.value?.name || 'Convidado')

//estatisticas do utilizador autenticado
const coinBalance = computed(() => walletStore.balance)
const isPlayer = computed(() => (currentUser.value?.type ?? 'P') === 'P')



//métodos de navegação
const startRankedGame = () => {
  router.push('/match/lobby')
}

const startPracticeGame = () => {
  router.push({
    path: '/game',
  })
}

const startPracticeGame3 = () => {
  router.push({
    path: '/game/3',
  })
}

const avatarUrl = computed(() => {
  return authStore.currentUser?.photo_url || 'avatars/default.jpg'
})

const viewMatchHistory = () => {
  if (isAdmin.value) {
    router.push('/admin/matches')
    return
  }
  router.push('/history')
}

const viewPersonalStats = () => {
  if (isAdmin.value) {
    router.push('/admin/player-history')
    return
  }
  router.push('/stats')
}

const viewPlatformStatistics = () => {
  router.push('/statistics')
}

const viewGlobalScoreboard = () => {
  router.push('/leaderboard')
}

const goToLogin = () => {
  router.push('/login')
}

const handleLogout = async () => {
  await authStore.logout()
  router.push('/')
}

const goToHome = () => {
  router.push('/')
}

const goToWallet = () => {
  router.push('/wallet')
}

const goToAdminPage = () => {
  router.push('/admin/users')
}

const goToMyProfile = () => {
  router.push('/profile')
}

onMounted(async () => {
  if (isLoggedIn.value) {
    await walletStore.fetchBalance()
  }
})
</script>
