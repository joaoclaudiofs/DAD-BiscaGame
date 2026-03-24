<template>
    <div class="min-h-screen bg-gradient-to-b from-slate-50 to-blue-50 dark:from-slate-900 dark:to-slate-800">
        <div class="max-w-7xl mx-auto px-6 py-8">
            <div class="mb-8 flex items-center gap-4">
                <button @click="$router.back()"
                    class="p-2 hover:bg-slate-200 dark:hover:bg-slate-700 rounded-lg transition-colors">
                    <ArrowLeft class="h-6 w-6 text-slate-700 dark:text-slate-300" />
                </button>
                <div>
                    <h1 class="text-3xl font-bold text-slate-900 dark:text-slate-50">Tabela de Classificação</h1>
                </div>
            </div>


            <div v-if="error">
                <Card class="bg-red-50 dark:bg-red-900/20 border-red-200">
                    <CardContent class="p-4 text-center">
                        <p class="text-red-600 dark:text-red-400">{{ error }}</p>
                        <Button variant="outline" size="sm" class="mt-2" @click="fetchScoreboard">
                            Try Again
                        </Button>
                    </CardContent>
                </Card>
            </div>

            <div v-else class="p-4 space-y-6 pb-8 max-w-3xl mx-auto">
                <div class="flex gap-2 justify-center flex-wrap items-center">
                    <Button size="sm" :variant="activeTab === 'wins' && orderBy === 'wins' ? 'default' : 'outline'"
                        class="shrink-0" @click="handleQuickFilter('wins')">
                        <Trophy class="h-4 w-4 mr-2" />
                        Vitórias
                    </Button>
                    <Button size="sm" :variant="activeTab === 'bandeiras' && orderBy === 'bandeiras' ? 'default' : 'outline'"
                        class="shrink-0" @click="handleQuickFilter('bandeiras')">
                        <FlagTriangleRight class="h-4 w-4 mr-2" />
                        Bandeiras
                    </Button>
                    <Button size="sm" :variant="activeTab === 'capotes' && orderBy === 'capotes' ? 'default' : 'outline'"
                        class="shrink-0" @click="handleQuickFilter('capotes')">
                        <Club class="h-4 w-4 mr-2" />
                        Capotes
                    </Button>
                    <Button size="sm" :variant="activeTab === 'game_wins' ? 'default' : 'outline'" class="shrink-0"
                        @click="handleQuickFilter('game_wins')">
                        <Award class="h-4 w-4 mr-2" />
                        Jogos Ganhos
                    </Button>
                    <Button size="sm" :variant="activeTab === 'coins' ? 'default' : 'outline'" class="shrink-0"
                        @click="handleQuickFilter('coins')">
                        <Coins class="h-4 w-4 mr-2" />
                        Moedas
                    </Button>
                </div>

                <div v-if="['wins', 'bandeiras', 'capotes'].includes(activeTab)">
                    <RankingList 
                        :items="scoreboard.most_wins?.items ?? []" 
                        :title="activeTab === 'wins' ? 'Mais vitórias em partidas' : activeTab === 'bandeiras' ? 'Mais bandeiras' : 'Mais capotes'"
                        :icon="activeTab === 'wins' ? 'trophy' : activeTab === 'bandeiras' ? 'flagtriangleright' : 'club'"
                        suffix="vitórias" 
                        :currentUserId="authStore.currentUser?.id" 
                        :loading="loading || loadingMore"
                        :userPosition="scoreboard.most_wins?.my_position" 
                    />

                    <div v-if="scoreboard.most_wins?.pagination && scoreboard.most_wins.pagination.last_page > 1" class="mt-4">
                        <Pagination v-slot="{ page }" :items-per-page="Number(scoreboard.most_wins.pagination.per_page)"
                            :total="scoreboard.most_wins.pagination.total" :sibling-count="1" :show-edges="true"
                            :default-page="currentPage.wins" @update:page="(p) => changePage('wins', p)">
                            <PaginationContent v-slot="{ items }">
                                <PaginationPrevious />

                                <template v-for="(item, index) in items">
                                    <PaginationItem v-if="item.type === 'page'" :key="`page-${item.value}`"
                                        :value="item.value" :is-active="item.value === page">
                                        {{ item.value }}
                                    </PaginationItem>
                                    <PaginationEllipsis v-else :key="`ellipsis-${index}`" :index="index" />
                                </template>

                                <PaginationNext />
                            </PaginationContent>
                        </Pagination>
                    </div>
                </div>

                <div v-if="activeTab === 'game_wins'">
                    <RankingList :items="scoreboard.most_game_wins?.items ?? []" title="Mais vitórias em jogos"
                        icon="award" suffix="jogos ganhos" :currentUserId="authStore.currentUser?.id"
                        :loading="loading || loadingMore" :userPosition="scoreboard.most_game_wins?.my_position" />

                    <div v-if="scoreboard.most_game_wins?.pagination && scoreboard.most_game_wins.pagination.last_page > 1"
                        class="mt-4">
                        <Pagination v-slot="{ page }"
                            :items-per-page="Number(scoreboard.most_game_wins.pagination.per_page)"
                            :total="scoreboard.most_game_wins.pagination.total" :sibling-count="1" :show-edges="true"
                            :default-page="currentPage.game_wins" @update:page="(p) => changePage('game_wins', p)">
                            <PaginationContent v-slot="{ items }">
                                <PaginationPrevious />

                                <template v-for="(item, index) in items">
                                    <PaginationItem v-if="item.type === 'page'" :key="`page-${item.value}`"
                                        :value="item.value" :is-active="item.value === page">
                                        {{ item.value }}
                                    </PaginationItem>
                                    <PaginationEllipsis v-else :key="`ellipsis-${index}`" :index="index" />
                                </template>

                                <PaginationNext />
                            </PaginationContent>
                        </Pagination>
                    </div>
                </div>

                <div v-if="activeTab === 'coins'">
                    <RankingList :items="scoreboard.most_coins?.items ?? []" title="Jogadores com mais moedas"
                        icon="coins" suffix="moedas" :currentUserId="authStore.currentUser?.id"
                        :loading="loading || loadingMore" :userPosition="scoreboard.most_coins?.my_position" />

                    <div v-if="scoreboard.most_coins?.pagination && scoreboard.most_coins.pagination.last_page > 1"
                        class="mt-4">
                        <Pagination v-slot="{ page }"
                            :items-per-page="Number(scoreboard.most_coins.pagination.per_page)"
                            :total="scoreboard.most_coins.pagination.total" :sibling-count="1" :show-edges="true"
                            :default-page="currentPage.coins" @update:page="(p) => changePage('coins', p)">
                            <PaginationContent v-slot="{ items }">
                                <PaginationPrevious />

                                <template v-for="(item, index) in items">
                                    <PaginationItem v-if="item.type === 'page'" :key="`page-${item.value}`"
                                        :value="item.value" :is-active="item.value === page">
                                        {{ item.value }}
                                    </PaginationItem>
                                    <PaginationEllipsis v-else :key="`ellipsis-${index}`" :index="index" />
                                </template>

                                <PaginationNext />
                            </PaginationContent>
                        </Pagination>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, inject } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useAPIStore } from '@/stores/api'
import { Button } from '@/components/ui/button'
import { Card, CardContent } from '@/components/ui/card'
import {
    Pagination,
    PaginationContent,
    PaginationEllipsis,
    PaginationItem,
    PaginationNext,
    PaginationPrevious,
} from '@/components/ui/pagination'
import RankingList from '@/components/scoreboard/RankingList.vue'
import {
    ArrowLeft,
    Trophy,
    Coins,
    Award,
    FlagTriangleRight, 
    Club
} from 'lucide-vue-next'

const router = useRouter()
const authStore = useAuthStore()
const apiStore = useAPIStore()

const loading = ref(true)
const loadingMore = ref(false)
const error = ref(null)
const activeTab = ref('wins')
const orderBy = ref('wins')

//rastreia a última solicitação para cada categoria
const latestRequestId = {
    wins: 0,
    game_wins: 0,
    coins: 0
}

const abortControllers = {
    wins: null,
    game_wins: null,
    coins: null
}

const currentPage = ref({
    wins: 1,
    game_wins: 1,
    coins: 1,
})

const scoreboard = ref({
    most_wins: [],
    most_game_wins: [],
    most_coins: [],
})

//mapeia API avatar_url para avatarUrl da UI
function attachAvatarUrls(list) {
    if (!Array.isArray(list)) return []
    return list.map(item => ({
        ...item,
        avatarUrl: item.avatar_url ?? null,
    }))
}

async function fetchScoreboard() {
    error.value = null

    try {
        const response = await apiStore.getScoreboard(1, 10, orderBy.value)

        //mapeia os itens para anexar URLs de avatar
        const winsWithAvatars = attachAvatarUrls(response.data.most_wins?.items ?? [])
        const gameWinsWithAvatars = attachAvatarUrls(response.data.most_game_wins?.items ?? [])
        const coinsWithAvatars = attachAvatarUrls(response.data.most_coins?.items ?? [])

        //mapeia a posição do user atual para anexar URL do avatar
        const winsMyPos = response.data.most_wins?.my_position
            ? { ...response.data.most_wins.my_position, avatarUrl: response.data.most_wins.my_position.avatar_url ?? null }
            : null
        const gameWinsMyPos = response.data.most_game_wins?.my_position
            ? { ...response.data.most_game_wins.my_position, avatarUrl: response.data.most_game_wins.my_position.avatar_url ?? null }
            : null
        const coinsMyPos = response.data.most_coins?.my_position
            ? { ...response.data.most_coins.my_position, avatarUrl: response.data.most_coins.my_position.avatar_url ?? null }
            : null

        scoreboard.value = {
            most_wins: {
                ...response.data.most_wins,
                items: winsWithAvatars,
                my_position: winsMyPos
            },
            most_game_wins: {
                ...response.data.most_game_wins,
                items: gameWinsWithAvatars,
                my_position: gameWinsMyPos
            },
            most_coins: {
                ...response.data.most_coins,
                items: coinsWithAvatars,
                my_position: coinsMyPos
            }
        }

        currentPage.value = { wins: 1, game_wins: 1, coins: 1 }
    } catch (err) {
        console.error('Falha ao carregar a leaderboard:', err)
        error.value = 'Falha ao carregar a leaderboard'
    } finally {
        loading.value = false
    }
}

async function changePage(category, page) {
    //cancela a solicitação anterior para esta categoria, se ainda estiver pendente
    if (abortControllers[category]) {
        abortControllers[category].abort()
    }

    //incrementa o ID da solicitação para esta categoria
    latestRequestId[category]++
    const currentRequestId = latestRequestId[category]

    //cria um novo AbortController para esta categoria
    abortControllers[category] = new AbortController()
    loadingMore.value = true

    try {
        const response = await apiStore.getScoreboard(page, 10, orderBy.value, abortControllers[category].signal)

        //ignora a resposta se não for a mais recente
        if (currentRequestId !== latestRequestId[category]) {
            return
        }

        const categoryKey = category === 'wins' || category === 'bandeiras' || category === 'capotes'
            ? 'most_wins'
            : category === 'game_wins'
                ? 'most_game_wins'
                : 'most_coins'
        const rawCategory = response.data[categoryKey]

        //anexa URLs de avatar aos itens
        const itemsWithAvatars = attachAvatarUrls(rawCategory?.items ?? [])
        const myPosWithAvatar = rawCategory?.my_position
            ? { ...rawCategory.my_position, avatarUrl: rawCategory.my_position.avatar_url ?? null }
            : null

        //atualiza o scoreboard com os novos dados da página
        scoreboard.value[categoryKey] = {
            ...rawCategory,
            items: itemsWithAvatars,
            my_position: myPosWithAvatar
        }

        //atualiza a página atual
        currentPage.value[category] = page
    } catch (err) {
        if (err.name === 'AbortError' || err.name === 'CanceledError') {
            console.log(`Pedido cancelado para ${category}`)
            return
        }
        console.error('Falha ao alterar a página:', err)
    } finally {
        //apenas atualiza o estado de carregamento se esta for a resposta mais recente
        if (currentRequestId === latestRequestId[category]) {
            loadingMore.value = false
            abortControllers[category] = null
        }
    }
}

onMounted(async () => {
    loading.value = true
    await fetchScoreboard()
})

async function handleQuickFilter(type) {
    if (type === 'wins') {
        activeTab.value = 'wins'
        orderBy.value = 'wins'
    } else if (type === 'bandeiras') {
        activeTab.value = 'bandeiras'
        orderBy.value = 'bandeiras'
    } else if (type === 'capotes') {
        activeTab.value = 'capotes'
        orderBy.value = 'capotes'
    } else if (type === 'game_wins') {
        activeTab.value = 'game_wins'
    } else if (type === 'coins') {
        activeTab.value = 'coins'
    }

    currentPage.value = { wins: 1, game_wins: 1, coins: 1 }
    loading.value = true
    await fetchScoreboard()
}
</script>
