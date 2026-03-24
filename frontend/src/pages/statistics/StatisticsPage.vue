<template>
  <div class="min-h-screen bg-gradient-to-br from-slate-50 to-blue-50 dark:from-slate-900 dark:to-slate-800">
    <div class="max-w-7xl mx-auto px-6 py-8">
      <div class="mb-8 flex items-center gap-4">
        <button @click="$router.back()"
          class="p-2 hover:bg-slate-200 dark:hover:bg-slate-700 rounded-lg transition-colors">
          <ArrowLeft class="h-6 w-6 text-slate-700 dark:text-slate-300" />
        </button>
        <div>
          <h1 class="text-3xl font-bold text-slate-900 dark:text-slate-50">Estatísticas Globais</h1>
        </div>
      </div>

      <main class="space-y-6">

        <div v-if="loading" class="flex items-center justify-center py-20">
          <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-slate-900 dark:border-slate-50"></div>
        </div>

        <div v-else-if="error"
          class="rounded-lg bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 p-4">
          <p class="text-red-800 dark:text-red-200">{{ error }}</p>
        </div>

        <div v-else class="space-y-6">
          <section>
            <h2 class="text-xl font-semibold text-slate-900 dark:text-slate-50 mb-4">
              Estatísticas públicas
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
              <Card class="p-5 bg-white/80 dark:bg-slate-900/80 border-slate-200/80 dark:border-slate-800/80">
                <div class="flex items-center justify-between">
                  <div>
                    <p class="text-sm text-slate-600 dark:text-slate-400">Jogadores Registados</p>
                    <p class="text-2xl font-bold text-slate-900 dark:text-slate-50 mt-1">
                      {{ formatNumber(stats?.total_players || 0) }}
                    </p>
                  </div>
                  <div class="h-12 w-12 rounded-full bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center">
                    <Users class="h-6 w-6 text-blue-600 dark:text-blue-400" />
                  </div>
                </div>
              </Card>

              <Card class="p-5 bg-white/80 dark:bg-slate-900/80 border-slate-200/80 dark:border-slate-800/80">
                <div class="flex items-center justify-between">
                  <div>
                    <p class="text-sm text-slate-600 dark:text-slate-400">Totais de jogos</p>
                    <p class="text-2xl font-bold text-slate-900 dark:text-slate-50 mt-1">
                      {{ formatNumber(stats?.total_games || 0) }}
                    </p>
                  </div>
                  <div
                    class="h-12 w-12 rounded-full bg-green-100 dark:bg-green-900/30 flex items-center justify-center">
                    <Gamepad2 class="h-6 w-6 text-green-600 dark:text-green-400" />
                  </div>
                </div>
              </Card>

              <Card class="p-5 bg-white/80 dark:bg-slate-900/80 border-slate-200/80 dark:border-slate-800/80">
                <div class="flex items-center justify-between">
                  <div>
                    <p class="text-sm text-slate-600 dark:text-slate-400">Totais de partidas</p>
                    <p class="text-2xl font-bold text-slate-900 dark:text-slate-50 mt-1">
                      {{ formatNumber(stats?.total_matches || 0) }}
                    </p>
                  </div>
                  <div
                    class="h-12 w-12 rounded-full bg-purple-100 dark:bg-purple-900/30 flex items-center justify-center">
                    <Trophy class="h-6 w-6 text-purple-600 dark:text-purple-400" />
                  </div>
                </div>
              </Card>

              <Card class="p-5 bg-white/80 dark:bg-slate-900/80 border-slate-200/80 dark:border-slate-800/80">
                <div class="flex items-center justify-between">
                  <div>
                    <p class="text-sm text-slate-600 dark:text-slate-400">Jogos (últimos 7 dias)</p>
                    <p class="text-2xl font-bold text-slate-900 dark:text-slate-50 mt-1">
                      {{ formatNumber(stats?.recent_activity?.games_last_7_days || 0) }}
                    </p>
                  </div>
                  <div
                    class="h-12 w-12 rounded-full bg-orange-100 dark:bg-orange-900/30 flex items-center justify-center">
                    <TrendingUp class="h-6 w-6 text-orange-600 dark:text-orange-400" />
                  </div>
                </div>
              </Card>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
              <Card class="p-6 bg-white/80 dark:bg-slate-900/80 border-slate-200/80 dark:border-slate-800/80">
                <h3 class="text-lg font-semibold text-slate-900 dark:text-slate-50 mb-4">
                  Jogos por Variante
                </h3>
                <div class="h-64">
                  <Doughnut :data="gamesVariantChartData" :options="doughnutOptions" />
                </div>
              </Card>

              <Card class="p-6 bg-white/80 dark:bg-slate-900/80 border-slate-200/80 dark:border-slate-800/80">
                <h3 class="text-lg font-semibold text-slate-900 dark:text-slate-50 mb-4">
                  Atividade Diária (últimos 30 dias)
                </h3>
                <div class="h-64">
                  <Line :data="gamesPerDayChartData" :options="lineOptions" />
                </div>
              </Card>
            </div>

            <Card class="p-6 bg-white/80 dark:bg-slate-900/80 border-slate-200/80 dark:border-slate-800/80">
              <h3 class="text-lg font-semibold text-slate-900 dark:text-slate-50 mb-4">
                Durações Médias
              </h3>
              <div class="overflow-x-auto">
                <table class="w-full">
                  <thead>
                    <tr class="border-b border-slate-200 dark:border-slate-700">
                      <th class="text-left py-3 px-4 text-sm font-semibold text-slate-700 dark:text-slate-300">
                        Métrica
                      </th>
                      <th class="text-right py-3 px-4 text-sm font-semibold text-slate-700 dark:text-slate-300">
                        Valor
                      </th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr class="border-b border-slate-100 dark:border-slate-800">
                      <td class="py-3 px-4 text-sm text-slate-600 dark:text-slate-400">
                        Duração Média do Jogo
                      </td>
                      <td class="py-3 px-4 text-sm text-slate-900 dark:text-slate-50 text-right font-medium">
                        {{ formatDuration(stats?.averages?.game_duration) }}
                      </td>
                    </tr>
                    <tr>
                      <td class="py-3 px-4 text-sm text-slate-600 dark:text-slate-400">
                        Duração Média da Partida
                      </td>
                      <td class="py-3 px-4 text-sm text-slate-900 dark:text-slate-50 text-right font-medium">
                        {{ formatDuration(stats?.averages?.match_duration) }}
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </Card>
          </section>

          <section v-if="isAdmin && adminStats">
            <h2 class="text-xl font-semibold text-slate-900 dark:text-slate-50 mb-4 mt-8">
              Estatísticas para administradores
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mb-6">
              <Card class="p-5 bg-white/80 dark:bg-slate-900/80 border-slate-200/80 dark:border-slate-800/80">
                <div class="flex items-center justify-between">
                  <div>
                    <p class="text-sm text-slate-600 dark:text-slate-400">Total de utilizadores</p>
                    <p class="text-2xl font-bold text-slate-900 dark:text-slate-50 mt-1">
                      {{ formatNumber(adminStats?.users?.total || 0) }}
                    </p>
                  </div>
                </div>
              </Card>

              <Card class="p-5 bg-white/80 dark:bg-slate-900/80 border-slate-200/80 dark:border-slate-800/80">
                <div class="flex items-center justify-between">
                  <div>
                    <p class="text-sm text-slate-600 dark:text-slate-400">Jogadores Ativos (últimos 7 dias)</p>
                    <p class="text-2xl font-bold text-slate-900 dark:text-slate-50 mt-1">
                      {{ formatNumber(adminStats?.users?.active_last_7_days || 0) }}
                    </p>
                  </div>
                </div>
              </Card>

              <Card class="p-5 bg-white/80 dark:bg-slate-900/80 border-slate-200/80 dark:border-slate-800/80">
                <div class="flex items-center justify-between">
                  <div>
                    <p class="text-sm text-slate-600 dark:text-slate-400">Utilizadores Bloqueados</p>
                    <p class="text-2xl font-bold text-slate-900 dark:text-slate-50 mt-1">
                      {{ formatNumber(adminStats?.users?.blocked || 0) }}
                    </p>
                  </div>
                </div>
              </Card>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
              <Card class="p-6 bg-white/80 dark:bg-slate-900/80 border-slate-200/80 dark:border-slate-800/80">
                <h3 class="text-lg font-semibold text-slate-900 dark:text-slate-50 mb-4">
                  Registos (últimos 30 dias)
                </h3>
                <div class="h-64">
                  <Bar :data="userRegistrationsChartData" :options="barOptions" />
                </div>
              </Card>

              <Card class="p-6 bg-white/80 dark:bg-slate-900/80 border-slate-200/80 dark:border-slate-800/80">
                <div class="flex flex-col gap-3 mb-4">
                  <h3 class="text-lg font-semibold text-slate-900 dark:text-slate-50">
                    Compras de Moedas
                  </h3>
                  <div class="grid grid-cols-1 md:grid-cols-3 gap-3 items-end">
                    <div>
                      <label class="block text-sm font-medium text-slate-600 dark:text-slate-300 mb-1">De</label>
                      <input type="date" v-model="purchasesFrom"
                        class="w-full rounded-md border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-2 text-sm text-slate-900 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-indigo-500" />
                    </div>
                    <div>
                      <label class="block text-sm font-medium text-slate-600 dark:text-slate-300 mb-1">Até</label>
                      <input type="date" v-model="purchasesTo"
                        class="w-full rounded-md border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-2 text-sm text-slate-900 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-indigo-500" />
                    </div>
                    <div class="flex gap-2">
                      <button @click="loadAdminStats"
                        class="flex-1 inline-flex items-center justify-center rounded-md bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium px-3 py-2 transition-colors">
                        Aplicar
                      </button>
                      <button @click="resetPurchaseDateRange"
                        class="inline-flex items-center justify-center rounded-md border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-200 text-sm font-medium px-3 py-2 transition-colors">
                        Limpar
                      </button>
                    </div>
                  </div>
                </div>
                <div class="h-64">
                  <Line :data="purchasesPerDayChartData" :options="lineOptions" />
                </div>
              </Card>
            </div>
            <Card class="p-6 bg-white/80 dark:bg-slate-900/80 border-slate-200/80 dark:border-slate-800/80 mb-6">
              <h3 class="text-lg font-semibold text-slate-900 dark:text-slate-50 mb-4">
                Estatísticas de Moedas
              </h3>
              <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="p-4 rounded-lg bg-slate-50 dark:bg-slate-800/50">
                  <p class="text-sm text-slate-600 dark:text-slate-400">Total de Moedas Compradas</p>
                  <p class="text-xl font-bold text-slate-900 dark:text-slate-50 mt-1">
                    {{ formatNumber(adminStats?.coins?.total_purchased || 0) }}
                  </p>
                </div>
                <div class="p-4 rounded-lg bg-slate-50 dark:bg-slate-800/50">
                  <p class="text-sm text-slate-600 dark:text-slate-400">Receita Total</p>
                  <p class="text-xl font-bold text-slate-900 dark:text-slate-50 mt-1">
                    €{{ formatNumber(adminStats?.coins?.total_revenue || 0) }}
                  </p>
                </div>
                <div class="p-4 rounded-lg bg-slate-50 dark:bg-slate-800/50">
                  <p class="text-sm text-slate-600 dark:text-slate-400">Total de Compras</p>
                  <p class="text-xl font-bold text-slate-900 dark:text-slate-50 mt-1">
                    {{ formatNumber(adminStats?.coins?.total_purchases || 0) }}
                  </p>
                </div>
              </div>
            </Card>

            <Card class="p-6 bg-white/80 dark:bg-slate-900/80 border-slate-200/80 dark:border-slate-800/80 mb-6">
              <div class="flex flex-col gap-3 mb-4">
                <h3 class="text-lg font-semibold text-slate-900 dark:text-slate-50">
                  Partidas por Dia
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3 items-end">
                  <div>
                    <label class="block text-sm font-medium text-slate-600 dark:text-slate-300 mb-1">De</label>
                    <input type="date" v-model="matchesFrom"
                      class="w-full rounded-md border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-2 text-sm text-slate-900 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-indigo-500" />
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-slate-600 dark:text-slate-300 mb-1">Até</label>
                    <input type="date" v-model="matchesTo"
                      class="w-full rounded-md border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-2 text-sm text-slate-900 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-indigo-500" />
                  </div>
                  <div class="flex gap-2">
                    <button @click="loadAdminStats"
                      class="flex-1 inline-flex items-center justify-center rounded-md bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium px-3 py-2 transition-colors">
                      Aplicar
                    </button>
                    <button @click="resetMatchesDateRange"
                      class="inline-flex items-center justify-center rounded-md border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-200 text-sm font-medium px-3 py-2 transition-colors">
                      Limpar
                    </button>
                  </div>
                </div>
              </div>
              <div class="h-64">
                <div v-if="!matchesHasData"
                  class="h-full flex items-center justify-center text-sm text-slate-500 dark:text-slate-400">
                  Ups, não há dados neste período
                </div>
                <Line v-else :data="matchesPerDayAdminChartData" :options="lineOptions" />
              </div>
            </Card>

            <Card class="p-6 bg-white/80 dark:bg-slate-900/80 border-slate-200/80 dark:border-slate-800/80 mb-6">
              <h3 class="text-lg font-semibold text-slate-900 dark:text-slate-50 mb-4">
                Jogos por Estado
              </h3>
              <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-3">
                <div class="p-4 rounded-lg bg-slate-50 dark:bg-slate-800/50">
                  <p class="text-sm text-slate-600 dark:text-slate-400">Terminados</p>
                  <p class="text-xl font-bold text-slate-900 dark:text-slate-50 mt-1">{{
                    formatNumber(gameStatusTotals.Ended) }}</p>
                </div>
                <div class="p-4 rounded-lg bg-slate-50 dark:bg-slate-800/50">
                  <p class="text-sm text-slate-600 dark:text-slate-400">Pendente</p>
                  <p class="text-xl font-bold text-slate-900 dark:text-slate-50 mt-1">{{
                    formatNumber(gameStatusTotals.Pending) }}</p>
                </div>
                <div class="p-4 rounded-lg bg-slate-50 dark:bg-slate-800/50">
                  <p class="text-sm text-slate-600 dark:text-slate-400">Interrompidos</p>
                  <p class="text-xl font-bold text-slate-900 dark:text-slate-50 mt-1">{{
                    formatNumber(gameStatusTotals.Interrupted) }}</p>
                </div>
                <div class="p-4 rounded-lg bg-slate-50 dark:bg-slate-800/50">
                  <p class="text-sm text-slate-600 dark:text-slate-400">Em Jogo</p>
                  <p class="text-xl font-bold text-slate-900 dark:text-slate-50 mt-1">{{
                    formatNumber(gameStatusTotals.Playing) }}</p>
                </div>
              </div>
            </Card>


            <Card class="p-6 bg-white/80 dark:bg-slate-900/80 border-slate-200/80 dark:border-slate-800/80">
              <h3 class="text-lg font-semibold text-slate-900 dark:text-slate-50 mb-4">
                Top 10 Compradores de Moedas
              </h3>
              <div class="overflow-x-auto">
                <table class="w-full">
                  <thead>
                    <tr class="border-b border-slate-200 dark:border-slate-700">
                      <th class="text-left py-3 px-4 text-sm font-semibold text-slate-700 dark:text-slate-300">
                        Nome
                      </th>
                      <th class="text-right py-3 px-4 text-sm font-semibold text-slate-700 dark:text-slate-300">
                        Compras
                      </th>
                      <th class="text-right py-3 px-4 text-sm font-semibold text-slate-700 dark:text-slate-300">
                        Moedas Compradas
                      </th>
                      <th class="text-right py-3 px-4 text-sm font-semibold text-slate-700 dark:text-slate-300">
                        Total Gasto
                      </th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="buyer in adminStats?.coins?.purchases_by_player" :key="buyer.id"
                      class="border-b border-slate-100 dark:border-slate-800">
                      <td class="py-3 px-4 text-sm text-slate-600 dark:text-slate-400">
                        {{ buyer.name }}
                      </td>
                      <td class="py-3 px-4 text-sm text-slate-900 dark:text-slate-50 text-right">
                        {{ buyer.purchase_count }}
                      </td>
                      <td class="py-3 px-4 text-sm text-slate-900 dark:text-slate-50 text-right">
                        {{ formatNumber(buyer.total_coins) }}
                      </td>
                      <td class="py-3 px-4 text-sm text-slate-900 dark:text-slate-50 text-right font-semibold">
                        €{{ formatNumber(buyer.total_spent) }}
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </Card>
          </section>
        </div>
      </main>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { storeToRefs } from 'pinia'
import { useAuthStore } from '@/stores/auth'
import { useAPIStore } from '@/stores/api'
import {
  ChevronLeft,
  Users,
  Gamepad2,
  Trophy,
  TrendingUp,
  UserCheck,
  Activity,
  Ban,
  ArrowLeft
} from 'lucide-vue-next'
import Card from '@/components/ui/card/Card.vue'
import {
  Chart as ChartJS,
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  BarElement,
  Title,
  Tooltip,
  Legend,
  ArcElement
} from 'chart.js'
import { Line, Bar, Doughnut } from 'vue-chartjs'


ChartJS.register(
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  BarElement,
  Title,
  Tooltip,
  Legend,
  ArcElement
)

const router = useRouter()
const authStore = useAuthStore()
const apiStore = useAPIStore()
const { isAdmin, currentUser } = storeToRefs(authStore)

const stats = ref(null)
const adminStats = ref(null)
const loading = ref(true)
const error = ref(null)

const purchasesFrom = ref('')
const purchasesTo = ref('')
const matchesFrom = ref('')
const matchesTo = ref('')


const loadAdminStats = async () => {
  if (!isAdmin.value) return
  const options = {
    purchasesFrom: purchasesFrom.value || null,
    purchasesTo: purchasesTo.value || null,
    matchesFrom: matchesFrom.value || null,
    matchesTo: matchesTo.value || null,
  }
  const adminResponse = await apiStore.getAdminStatistics(options)
  adminStats.value = adminResponse.data
}

const resetPurchaseDateRange = async () => {
  purchasesFrom.value = ''
  purchasesTo.value = ''
  await loadAdminStats()
}

const resetMatchesDateRange = async () => {
  matchesFrom.value = ''
  matchesTo.value = ''
  await loadAdminStats()
}

onMounted(async () => {
  try {
    loading.value = true
    error.value = null

    const publicResponse = await apiStore.getPublicStatistics()
    stats.value = publicResponse.data

    if (isAdmin.value) {
      try {
        await loadAdminStats()
      } catch (adminError) {
        console.error('Failed to fetch admin statistics:', adminError)
        error.value = 'Failed to load admin statistics.'
      }
    }
  } catch (err) {
    console.error('Failed to fetch statistics:', err)
    error.value = 'Failed to load statistics. Please try again.'
  } finally {
    loading.value = false
  }
})


//formatação de números com separadores de milhar 
const formatNumber = (num) => {
  if (!num && num !== 0) return '0'
  return num.toLocaleString('pt-PT')
}

//formatação de duração em segundos para minutos e segundos
const formatDuration = (seconds) => {
  if (!seconds) return '0s'
  const minutes = Math.floor(seconds / 60)
  const secs = Math.floor(seconds % 60)
  if (minutes > 0) {
    return `${minutes}m ${secs}s`
  }
  return `${secs}s`
}

//gráfico jogos por variante
const gamesVariantChartData = computed(() => {
  const variants = stats.value?.games_by_variant || {}
  return {
    labels: Object.keys(variants).map(k => `Variante ${k}`),
    datasets: [
      {
        data: Object.values(variants),
        backgroundColor: [
          'rgba(59, 130, 246, 0.8)',
          'rgba(139, 92, 246, 0.8)',
          'rgba(236, 72, 153, 0.8)'
        ],
        borderColor: [
          'rgba(59, 130, 246, 1)',
          'rgba(139, 92, 246, 1)',
          'rgba(236, 72, 153, 1)'
        ],
        borderWidth: 2
      }
    ]
  }
})

//gráfico jogos por dia
const gamesPerDayChartData = computed(() => {
  const gamesPerDay = stats.value?.games_per_day || []
  return {
    labels: gamesPerDay.map(item => new Date(item.date).toLocaleDateString('pt-PT')),
    datasets: [
      {
        label: 'Jogos',
        data: gamesPerDay.map(item => item.count),
        borderColor: 'rgba(34, 197, 94, 1)',
        backgroundColor: 'rgba(34, 197, 94, 0.1)',
        tension: 0.4,
        fill: true
      }
    ]
  }
})

//gráfico registos de utilizadores por dia
const userRegistrationsChartData = computed(() => {
  const registrations = adminStats.value?.users?.registrations_per_day || []
  return {
    labels: registrations.map(item => new Date(item.date).toLocaleDateString('pt-PT')),
    datasets: [
      {
        label: 'Novos Registos',
        data: registrations.map(item => item.count),
        backgroundColor: 'rgba(59, 130, 246, 0.8)',
        borderColor: 'rgba(59, 130, 246, 1)',
        borderWidth: 1
      }
    ]
  }
})

//gráfico compras por dia
const purchasesPerDayChartData = computed(() => {
  const purchases = adminStats.value?.coins?.purchases_per_day || []
  return {
    labels: purchases.map(item => new Date(item.date).toLocaleDateString('pt-PT')),
    datasets: [
      {
        label: 'Receita (€)',
        data: purchases.map(item => item.revenue),
        borderColor: 'rgba(234, 88, 12, 1)',
        backgroundColor: 'rgba(234, 88, 12, 0.1)',
        tension: 0.4,
        fill: true,
        yAxisID: 'y'
      },
      {
        label: 'Compras',
        data: purchases.map(item => item.count),
        borderColor: 'rgba(168, 85, 247, 1)',
        backgroundColor: 'rgba(168, 85, 247, 0.1)',
        tension: 0.4,
        fill: true,
        yAxisID: 'y1'
      }
    ]
  }
})

// gráfico partidas por dia (admin, intervalo)
const matchesPerDayAdminChartData = computed(() => {
  const matches = adminStats.value?.matches_per_day || []
  return {
    labels: matches.map(item => new Date(item.date).toLocaleDateString('pt-PT')),
    datasets: [
      {
        label: 'Partidas',
        data: matches.map(item => item.count),
        borderColor: 'rgba(59, 130, 246, 1)',
        backgroundColor: 'rgba(59, 130, 246, 0.12)',
        tension: 0.4,
        fill: true
      }
    ]
  }
})

const matchesHasData = computed(() => {
  const matches = adminStats.value?.matches_per_day || []
  return matches.length > 0
})

const gameStatusTotals = computed(() => {
  const statuses = adminStats.value?.games_by_status || {}
  return {
    Ended: statuses.Ended || 0,
    Pending: statuses.Pending || 0,
    Interrupted: statuses.Interrupted || 0,
    Playing: statuses.Playing || 0,
  }
})

//customização dos gráficos
const doughnutOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: {
      position: 'bottom',
      labels: {
        color: '#64748b',
        padding: 15,
        font: {
          size: 12
        }
      }
    }
  }
}

const lineOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: {
      position: 'top',
      labels: {
        color: '#64748b',
        padding: 10,
        font: {
          size: 11
        }
      }
    }
  },
  scales: {
    y: {
      beginAtZero: true,
      ticks: {
        color: '#94a3b8'
      },
      grid: {
        color: 'rgba(148, 163, 184, 0.1)'
      }
    },
    y1: {
      beginAtZero: true,
      position: 'right',
      ticks: {
        color: '#94a3b8'
      },
      grid: {
        display: false
      }
    },
    x: {
      ticks: {
        color: '#94a3b8',
        maxRotation: 45,
        minRotation: 0
      },
      grid: {
        color: 'rgba(148, 163, 184, 0.1)'
      }
    }
  }
}

const barOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: {
      display: false
    }
  },
  scales: {
    y: {
      beginAtZero: true,
      ticks: {
        color: '#94a3b8',
        precision: 0
      },
      grid: {
        color: 'rgba(148, 163, 184, 0.1)'
      }
    },
    x: {
      ticks: {
        color: '#94a3b8',
        maxRotation: 45,
        minRotation: 0
      },
      grid: {
        display: false
      }
    }
  }
}
</script>
