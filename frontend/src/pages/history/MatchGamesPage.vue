<template>
    <div class="min-h-screen bg-gradient-to-br from-slate-50 to-blue-50 dark:from-slate-900 dark:to-slate-800">
        <div class="max-w-7xl mx-auto px-6 py-8">
            <div class="mb-8 flex items-center gap-4">
                <button @click="$router.back()"
                    class="p-2 hover:bg-slate-200 dark:hover:bg-slate-700 rounded-lg transition-colors">
                    <ArrowLeftIcon class="h-6 w-6 text-slate-700 dark:text-slate-300" />
                </button>
                <div>
                    <h1 class="text-3xl font-bold text-slate-900 dark:text-slate-50">
                        Jogos da Partida
                    </h1>
                </div>
            </div>

            <div class="space-y-6">
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
                    <div class="space-y-3">
                        <Card v-for="(g, index) in games" :key="g.id"
                            class="p-4 flex items-start gap-3 hover:shadow-md transition-shadow">

                            <span :class="[
                                'inline-flex h-10 w-10 items-center justify-center rounded-full text-white flex-shrink-0',
                                getResult(g).bgClass
                            ]">
                                <component :is="getResult(g).icon" class="h-5 w-5" />
                            </span>

                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2 mb-2 flex-wrap">
                                    <span class="text-sm font-semibold text-gray-900 dark:text-gray-100">
                                        Jogo {{ index + 1 }}
                                    </span>
                                    <span :class="[
                                        'inline-flex items-center rounded-full px-2 py-0.5 text-xs font-semibold',
                                        getResult(g).badgeClass
                                    ]">
                                        {{ getResult(g).label }}
                                    </span>
                                    <span v-if="getAchievement(g)" :class="[
                                        'inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-semibold',
                                        getAchievement(g).badgeClass
                                    ]">
                                        {{ getAchievement(g).label }}
                                    </span>
                                </div>

                                <div class="flex gap-4 text-xs text-gray-600 dark:text-gray-400">
                                    <div>
                                        <span class="text-gray-400">Pontos:</span>
                                        <span class="font-semibold text-gray-700 dark:text-gray-200">{{ g.player1_points
                                        }}</span> - {{ g.player2_points }}
                                    </div>
                                    <div class="flex items-center gap-1">
                                        <span class="text-gray-400">Tempo:</span>
                                        <span>{{ formatTime(g.total_time) }}</span>
                                    </div>
                                </div>
                            </div>
                        </Card>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAPIStore } from '@/stores/api'
import { useAuthStore } from '@/stores/auth'
import Button from '@/components/ui/button/Button.vue'
import Card from '@/components/ui/card/Card.vue'
import { ArrowLeft as ArrowLeftIcon, Trophy, Ban, Activity, Gamepad2, Zap } from 'lucide-vue-next'

const route = useRoute()
const router = useRouter()
const apiStore = useAPIStore()
const authStore = useAuthStore()

const currentUserId = computed(() => authStore.currentUser?.id)

const matchId = route.params.matchId
const games = ref([])
const loading = ref(false)

function goBack() {
    router.back()
}

function getResult(game) {
    if (game.is_draw == 1) {
        return {
            label: 'Empate',
            badgeClass: 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-200',
            bgClass: 'bg-amber-500',
            icon: Activity
        }
    }

    if (game.winner_user_id == currentUserId.value) {
        return {
            label: 'Vitória',
            badgeClass: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-200',
            bgClass: 'bg-emerald-500',
            icon: Trophy
        }
    } else {
        return {
            label: 'Derrota',
            badgeClass: 'bg-rose-100 text-rose-700 dark:bg-rose-900/40 dark:text-rose-200',
            bgClass: 'bg-rose-500',
            icon: Ban
        }
    }
}

function getAchievement(game) {
    //bandeira
    if (game.player1_points === 120 || game.player2_points === 120) {
        return {
            label: 'Bandeira',
            badgeClass: 'bg-purple-100 text-purple-700 dark:bg-purple-900/40 dark:text-purple-200',
        }
    }

    //capote
    if (game.player1_points >= 91 || game.player2_points >= 91) {
        return {
            label: 'Capote',
            badgeClass: 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-200',
        }
    }

    return null
}

function formatTime(seconds) {
    const mins = Math.floor(seconds / 60)
    const secs = seconds % 60
    return `${mins}m ${secs}s`
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