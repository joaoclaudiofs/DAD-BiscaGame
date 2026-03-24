<template>
  <div class="min-h-screen bg-gradient-to-br from-slate-50 to-blue-50 dark:from-slate-900 dark:to-slate-800">
    <div class="max-w-7xl mx-auto px-6 py-8">
      <div class="mb-8 flex items-center gap-4">
        <button @click="$router.back()"
          class="p-2 hover:bg-slate-200 dark:hover:bg-slate-700 rounded-lg transition-colors">
          <ArrowLeftIcon class="h-6 w-6 text-slate-700 dark:text-slate-300" />
        </button>
        <div>
          <h1 class="text-3xl font-bold text-slate-900 dark:text-slate-50">Histórico de Partidas</h1>
        </div>
      </div>

      <div class="space-y-6">
        <Card class="p-4 space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              Resultado:
            </label>
            <div class="flex gap-2 flex-wrap">
              <button v-for="res in ['all', 'win', 'loss', 'draw', 'interrupted']" :key="res"
                @click="filters.result = res; loadPage(1)" :class="[
                  'px-3 py-1.5 rounded-md text-sm font-medium transition-colors flex items-center gap-1',
                  filters.result === res
                    ? (res === 'all' ? 'bg-gray-500 text-white dark:bg-gray-600' :
                      res === 'win' ? 'bg-emerald-500 text-white dark:bg-emerald-600' :
                        res === 'loss' ? 'bg-rose-500 text-white dark:bg-rose-600' :
                          res === 'draw' ? 'bg-yellow-500 text-white dark:bg-yellow-600' :
                            'bg-slate-500 text-white dark:bg-slate-600')
                    : 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600'
                ]">
                <component v-if="res !== 'all'"
                  :is="res === 'win' ? Trophy : (res === 'loss' ? Ban : (res === 'draw' ? Gamepad2 : Flag))"
                  class="h-4 w-4" />
                {{ res === 'all' ? 'Todas' : (res === 'win' ? 'Vitórias' : (res === 'loss' ? 'Derrotas' : (res ===
                  'draw'
                  ? 'Empates' : 'Interrompidas'))) }}
              </button>
            </div>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              Procurar adversário:
            </label>
            <div class="relative">
              <input v-model="filters.opponent" type="text" placeholder="Nickname do adversário..." @input="loadPage(1)"
                class="w-full px-3 py-2 border border-slate-300 dark:border-slate-700 rounded-md bg-white dark:bg-slate-800 text-gray-900 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-sm" />
              <button v-if="filters.opponent" @click="filters.opponent = ''; loadPage(1)"
                class="absolute right-2 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                <X class="h-4 w-4" />
              </button>
            </div>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Depois da data:
              </label>
              <input v-model="filters.began_after" type="date" @change="loadPage(1)"
                class="w-full px-3 py-2 border border-slate-300 dark:border-slate-700 rounded-md bg-white dark:bg-slate-800 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-sm" />
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Até à data:
              </label>
              <input v-model="filters.ended_before" type="date" @change="loadPage(1)"
                class="w-full px-3 py-2 border border-slate-300 dark:border-slate-700 rounded-md bg-white dark:bg-slate-800 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-sm" />
            </div>
          </div>

          <div class="flex items-center gap-4">
            <label class="inline-flex items-center gap-2">
              <input type="checkbox" v-model="filters.capote" @change="loadPage(1)"
                class="h-4 w-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500" />
              <span class="text-sm text-gray-700 dark:text-gray-300">Com Capote</span>
            </label>
            <label class="inline-flex items-center gap-2">
              <input type="checkbox" v-model="filters.bandeira" @change="loadPage(1)"
                class="h-4 w-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500" />
              <span class="text-sm text-gray-700 dark:text-gray-300">Com Bandeira</span>
            </label>
            <label class="inline-flex items-center gap-2">
              <span class="text-sm text-gray-700 dark:text-gray-300">Número mínimo de jogos</span>
              <input type="number" v-model.number="filters.total_games" id="numberGames" name="numberGames" min="1"
                size="1" />
            </label>
          </div>
          <button @click="resetFilters" :disabled="!hasActiveFilters" :class="[
            'w-full px-3 py-2 rounded-md text-sm font-medium transition-colors',
            hasActiveFilters
              ? 'bg-gray-200 text-gray-800 dark:bg-gray-700 dark:text-gray-200 hover:bg-gray-300 dark:hover:bg-gray-600'
              : 'bg-gray-100 text-gray-400 dark:bg-gray-800 dark:text-gray-600 cursor-not-allowed'
          ]">
            Limpar filtros
          </button>
        </Card>

        <div v-if="loading" class="flex justify-center py-10">
          <span class="text-sm text-gray-500 dark:text-gray-400">A carregar...</span>
        </div>
        <div v-else>
          <div v-if="matches.length === 0" class="text-center py-10">
            <Card class="p-6">
              <Gamepad2 class="h-12 w-12 mx-auto text-gray-300 dark:text-gray-600 mb-2" />
              <p class="text-sm text-gray-500 dark:text-gray-400">Ainda não há partidas.</p>
            </Card>
          </div>
          <div v-else>
            <div class="text-sm text-gray-600 dark:text-gray-400 mb-3 px-2">
              A mostrar {{ filteredMatches.length }} de {{ matches.length }} partida(s)
            </div>

            <div v-if="filteredMatches.length === 0" class="text-center py-10">
              <Card class="p-6">
                <Gamepad2 class="h-12 w-12 mx-auto text-gray-300 dark:text-gray-600 mb-2" />
                <p class="text-sm text-gray-500 dark:text-gray-400">Nenhuma partida corresponde aos filtros.</p>
              </Card>
            </div>

            <div class="space-y-3">
              <Card v-for="m in filteredMatches" :key="m.id"
                class="p-4 flex items-start justify-between gap-3 hover:shadow-md transition-shadow">
                <div class="flex items-start gap-3 flex-1">
                  <span :class="[
                    'inline-flex h-10 w-10 items-center justify-center rounded-full text-white flex-shrink-0',
                    m.result === 'win' ? 'bg-emerald-500' :
                      m.result === 'loss' ? 'bg-rose-500' :
                        m.result === 'draw' ? 'bg-yellow-500' :
                          m.result === 'interrupted' ? 'bg-slate-500' : 'bg-gray-400'
                  ]">
                    <component :is="m.result === 'win' ? Trophy :
                      m.result === 'loss' ? Ban :
                        m.result === 'draw' ? Gamepad2 :
                          m.result === 'interrupted' ? Flag : Gamepad2" class="h-5 w-5" />
                  </span>
                  <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2 mb-1">
                      <span :class="[
                        'inline-flex items-center rounded-full px-2 py-0.5 text-xs font-semibold',
                        m.result === 'win' ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-200' :
                          m.result === 'loss' ? 'bg-rose-100 text-rose-700 dark:bg-rose-900/40 dark:text-rose-200' :
                            m.result === 'draw' ? 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/40 dark:text-yellow-200' :
                              m.result === 'interrupted' ? 'bg-slate-100 text-slate-700 dark:bg-slate-900/40 dark:text-slate-200' :
                                'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300'
                      ]">
                        {{ m.result === 'win' ? 'Vitória' :
                          (m.result === 'loss' ? 'Derrota' :
                            (m.result === 'draw' ? 'Empate' :
                              (m.result === 'interrupted' ? 'Interrompido' : 'Desconhecido'))) }}
                      </span>
                      <span v-if="m.status && !m.ended_at"
                        class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-semibold bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-200">
                        <Clock class="h-3 w-3" />
                        A decorrer
                      </span>
                      <span class="text-xs text-gray-400">{{ formatDate(m.ended_at) }}</span>
                    </div>

                    <div class="text-xs text-gray-600 dark:text-gray-400 mb-2">
                      vs {{ m.opponent_name }}
                    </div>

                    <div class="flex gap-4 text-xs text-gray-500 dark:text-gray-400">
                      <div>
                        <span class="text-gray-400">Pontos:</span>
                        <span class="font-semibold text-gray-700 dark:text-gray-200">{{ m.player1_points ??
                          m.total_player1_points ?? '–' }}</span> - {{ m.player2_points ?? m.total_player2_points ?? '–'
                          }}
                      </div>
                      <div>
                        <span class="text-gray-400">Score:</span>
                        <span class="font-semibold text-gray-700 dark:text-gray-200">{{ m.player1_marks ??
                          m.match_player1_marks ?? '–' }}</span> - {{ m.player2_marks ?? m.match_player2_marks ?? '–' }}
                      </div>
                    </div>
                  </div>
                </div>

                <div class="flex-shrink-0">
                  <Button size="sm" variant="outline" @click="viewMatch(m)">Ver</Button>
                </div>
              </Card>
            </div>

            <div class="flex items-center justify-between mt-6">
              <Button size="sm" variant="outline" @click="prevPage" :disabled="meta.current_page <= 1">Anterior</Button>
              <div class="text-sm text-gray-600 dark:text-gray-400 font-medium">Página {{ meta.current_page }} / {{
                meta.last_page }}</div>
              <Button size="sm" variant="outline" @click="nextPage"
                :disabled="meta.current_page >= meta.last_page">Próxima</Button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed, watch } from 'vue'
import { useRouter } from 'vue-router'
import { useAPIStore } from '@/stores/api'
import Button from '@/components/ui/button/Button.vue'
import Card from '@/components/ui/card/Card.vue'
import { ArrowLeft as ArrowLeftIcon, Trophy, Ban, Gamepad2, UserCheck, Clock, X, Flag } from 'lucide-vue-next'

const router = useRouter()
const goBack = () => router.back()

const apiStore = useAPIStore()

const matches = ref([])
const meta = ref({ current_page: 1, last_page: 1, per_page: 15, total: 0 })
const loading = ref(false)

const filters = ref({
  result: 'all',
  opponent: '',
  capote: false,
  bandeira: false,
  total_games: 1,
  began_after: '',
  ended_before: ''
})

//usar um debounce para o filtro de número mínimo de jogos
let debounceTimer = null
watch(() => filters.value.total_games, () => {
  clearTimeout(debounceTimer)
  debounceTimer = setTimeout(() => {
    loadPage(1)
  }, 1000) //esperar 1000 ms para fzr a chamada à API
})

function formatDate(dt) {
  if (!dt) return '–'
  return new Date(dt).toLocaleString()
}

const filteredMatches = computed(() => {
  let result = matches.value

  if (filters.value.result !== 'all') {
    result = result.filter(m => m.result === filters.value.result)
  }

  return result
})

const hasActiveFilters = computed(() => {
  return filters.value.result !== 'all' || filters.value.opponent.trim() !== '' || filters.value.capote || filters.value.bandeira ||
    filters.value.total_games > 1 ||
    filters.value.began_after !== '' ||
    filters.value.ended_before !== ''
})

function resetFilters() {
  filters.value = {
    result: 'all',
    opponent: '',
    capote: false,
    bandeira: false,
    total_games: 1,
    began_after: '',
    ended_before: ''
  }
  loadPage(1)
}

async function loadPage(p = 1) {
  loading.value = true
  try {
    const res = await apiStore.getUserMatches(
      p,
      meta.value.per_page || 15,
      filters.value.opponent || null,
      filters.value.capote ? true : null,
      filters.value.bandeira ? true : null,
      filters.value.total_games > 1 ? filters.value.total_games : null,
      filters.value.began_after || null,
      filters.value.ended_before || null
    )
    const payload = res.data

    if (Array.isArray(payload)) {
      matches.value = payload
      meta.value = {
        current_page: 1,
        last_page: 1,
        per_page: payload.length,
        total: payload.length
      }
    } else {
      matches.value = payload.data || []
      meta.value = payload.meta || meta.value
    }
  } catch (err) {
    console.error('Failed to load matches', err)
  } finally {
    loading.value = false
  }
}

function prevPage() {
  if (meta.value.current_page > 1) {
    loadPage(meta.value.current_page - 1)
  }
}

function nextPage() {
  if (meta.value.current_page < meta.value.last_page) {
    loadPage(meta.value.current_page + 1)
  }
}

function viewMatch(match) {
  router.push({ name: 'match-games', params: { matchId: match.id } })
}

onMounted(() => {
  loadPage(1)
})
</script>

<style scoped></style>