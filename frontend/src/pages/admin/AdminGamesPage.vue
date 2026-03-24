<template>
  <div class="min-h-screen bg-gradient-to-br from-slate-50 to-blue-50 dark:from-slate-900 dark:to-slate-800">
    <div
      class="sticky top-0 z-10 bg-white/80 dark:bg-slate-900/80 backdrop-blur-sm border-b border-slate-200/70 dark:border-slate-700/70">
      <div class="flex items-center justify-between p-4">
        <Button variant="ghost" size="icon" @click="goBack">
          <ArrowLeftIcon class="h-5 w-5" />
        </Button>
        <h1 class="text-lg font-semibold">Jogos da Partida (Admin)</h1>
        <div class="w-10" />
      </div>
    </div>

    <div class="p-4 space-y-6 pb-8 max-w-3xl mx-auto">
      <div v-if="loading" class="flex justify-center py-10">
        <span class="text-sm text-gray-500 dark:text-gray-400">A carregar jogos...</span>
      </div>

      <div v-else>
        <div v-if="games.length === 0" class="text-center py-10">
          <Card class="p-6">
            <Gamepad2 class="h-12 w-12 mx-auto text-gray-300 dark:text-gray-600 mb-2" />
            <p class="text-sm text-gray-500 dark:text-gray-400">Nenhum jogo encontrado.</p>
          </Card>
        </div>

        <div v-else class="space-y-3">
          <Card v-for="(g, index) in games" :key="g.id" class="p-4 hover:shadow-md transition-shadow space-y-3">
            <div class="flex items-center justify-between">
              <div>
                <span class="text-sm font-semibold text-gray-900 dark:text-gray-100">
                  Jogo {{ index + 1 }}
                </span>
              </div>
              <div class="text-xs text-gray-400">
                {{ formatGameDuration(g) }}
              </div>
            </div>

            <div class="bg-gray-50 dark:bg-gray-800 rounded-md p-3">
              <div class="flex items-center justify-between gap-2">
                <div class="flex-1 text-center">
                  <div class="text-sm font-semibold text-gray-900 dark:text-gray-100">
                    {{ g.player1_name || 'Jogador 1' }}
                  </div>
                  <div class="text-2xl font-bold mt-1" :class="[
                    getWinnerClass(g, 1)
                  ]">
                    {{ g.player1_points ?? 0 }}
                  </div>
                </div>

                <div class="flex-shrink-0">
                  <span class="text-xs font-semibold text-gray-500 dark:text-gray-400">vs</span>
                </div>

                <div class="flex-1 text-center">
                  <div class="text-sm font-semibold text-gray-900 dark:text-gray-100">
                    {{ g.player2_name || 'Jogador 2' }}
                  </div>
                  <div class="text-2xl font-bold mt-1" :class="[
                    getWinnerClass(g, 2)
                  ]">
                    {{ g.player2_points ?? 0 }}
                  </div>
                </div>
              </div>

              <div v-if="getWinner(g)" class="mt-3 pt-3 border-t border-gray-200 dark:border-gray-700 text-center">
                <span :class="[
                  'inline-flex items-center gap-1 rounded-full px-2 py-1 text-xs font-semibold',
                  getWinner(g).bgClass
                ]">
                  O {{ getWinner(g).label }} venceu
                </span>
              </div>

              <div v-else class="mt-3 pt-3 border-t border-gray-200 dark:border-gray-700 text-center">
                <span
                  class="inline-flex items-center gap-1 rounded-full px-2 py-1 text-xs font-semibold bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-200">
                  Empate
                </span>
              </div>
            </div>

            <div v-if="getAchievements(g).length > 0" class="flex gap-2 flex-wrap">
              <span v-for="ach in getAchievements(g)" :key="ach.label" :class="[
                'inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-semibold',
                ach.bgClass
              ]">
                {{ ach.label }}
              </span>
            </div>
          </Card>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useAPIStore } from '@/stores/api'
import Button from '@/components/ui/button/Button.vue'
import Card from '@/components/ui/card/Card.vue'
import { ArrowLeft as ArrowLeftIcon } from 'lucide-vue-next'

const router = useRouter()
const route = useRoute()
const goBack = () => router.back()

const apiStore = useAPIStore()

const matchId = parseInt(route.params.matchId)
const games = ref([])
const loading = ref(false)

function getWinner(game) {
  if (game.is_draw == 1) return null

  const winnerId = game.winner_user_id
  if (winnerId === game.player1_user_id) {
    return {
      label: game.player1_name || 'Jogador 1',
      bgClass: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-200'
    }
  } else if (winnerId === game.player2_user_id) {
    return {
      label: game.player2_name || 'Jogador 2',
      bgClass: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-200'
    }
  }
  return null
}

function getWinnerClass(game, playerNum) {
  const winnerId = game.winner_user_id
  const playerId = playerNum === 1 ? game.player1_user_id : game.player2_user_id

  if (winnerId === playerId) {
    return 'text-emerald-600 dark:text-emerald-400'
  } else if (winnerId && winnerId !== playerId) {
    return 'text-rose-600 dark:text-rose-400'
  }
  return 'text-gray-600 dark:text-gray-400'
}

function getAchievements(game) {
  const achievements = []

  //bandeira (120 points)
  if ((game.player1_points === 120) || (game.player2_points === 120)) {
    achievements.push({
      label: 'Bandeira',
      bgClass: 'bg-purple-100 text-purple-700 dark:bg-purple-900/40 dark:text-purple-200',
    })
  }

  //capote (91+ points)
  if ((game.player1_points >= 91) || (game.player2_points >= 91)) {
    achievements.push({
      label: 'Capote',
      bgClass: 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-200',
    })
  }

  return achievements
}

function formatTime(seconds) {
  if (!seconds) return '0m 0s'
  const mins = Math.floor(seconds / 60)
  const secs = seconds % 60
  return `${mins}m ${secs}s`
}

function formatGameDuration(game) {
  const total = Number.isFinite(game.total_time) ? game.total_time : game.duration
  if (Number.isFinite(total) && total > 0) {
    return formatTime(Math.round(total))
  }

  if (game.began_at && game.ended_at) {
    const start = new Date(game.began_at).getTime()
    const end = new Date(game.ended_at).getTime()
    if (!Number.isNaN(start) && !Number.isNaN(end) && end > start) {
      const seconds = Math.round((end - start) / 1000)
      return formatTime(seconds)
    }
  }

  return formatTime(0)
}

onMounted(async () => {
  loading.value = true
  try {
    const res = await apiStore.getMatchGames(matchId)
    games.value = Array.isArray(res.data) ? res.data : []
  } catch (err) {
    console.error('Failed to load match games', err)
    games.value = []
  } finally {
    loading.value = false
  }
})
</script>

<style scoped></style>
