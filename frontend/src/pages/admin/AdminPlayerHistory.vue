<template>
	<div class="min-h-screen bg-gradient-to-br from-slate-50 to-blue-50 dark:from-slate-900 dark:to-slate-800">
		<div
			class="sticky top-0 z-10 bg-white/80 dark:bg-slate-900/80 backdrop-blur-sm border-b border-slate-200/70 dark:border-slate-700/70">
			<div class="flex items-center justify-between p-4">
				<Button variant="ghost" size="icon" @click="goBack">
					<ArrowLeft class="h-5 w-5" />
				</Button>
				<h1 class="text-lg font-semibold">Histórico de Jogadores (Admin)</h1>
				<div class="w-10" />
			</div>
		</div>

		<div class="p-4 space-y-6 pb-8 max-w-3xl mx-auto">
			<Card class="p-4">
				<div class="space-y-3">
					<label class="text-sm font-medium text-slate-700 dark:text-slate-300">Pesquisar Jogador:</label>
					<div class="relative">
						<input v-model="searchQuery" type="text" placeholder="Nome, Nickname ou Email..."
							class="w-full px-3 py-2 border border-slate-300 dark:border-slate-700 rounded-md bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-indigo-500" />
						<button v-if="searchQuery" @click="searchQuery = ''; selectedUserId = ''"
							class="absolute right-2 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-300">
							<X class="h-4 w-4" />
						</button>
					</div>

					<div v-if="searchQuery && filteredPlayers.length > 0"
						class="max-h-60 overflow-y-auto border border-slate-200 dark:border-slate-700 rounded-md">
						<button v-for="user in filteredPlayers" :key="user.id" @click="selectPlayer(user.id)"
							class="w-full text-left px-3 py-2 hover:bg-slate-100 dark:hover:bg-slate-800 border-b border-slate-100 dark:border-slate-800 last:border-b-0"
							:class="selectedUserId === user.id ? 'bg-blue-50 dark:bg-blue-900/20' : ''">
							<div class="text-sm font-medium text-slate-900 dark:text-slate-100">
								{{ user.nickname || user.name }}
							</div>
							<div class="text-xs text-slate-500 dark:text-slate-400">
								{{ user.email }}
							</div>
						</button>
					</div>

					<div v-if="searchQuery && filteredPlayers.length === 0"
						class="text-center py-3 text-sm text-slate-500 dark:text-slate-400">
						Nenhum jogador encontrado
					</div>

					<div v-if="selectedUserId && selectedPlayer"
						class="flex items-center justify-between p-3 bg-blue-50 dark:bg-blue-900/20 rounded-md">
						<div>
							<div class="text-sm font-medium text-slate-900 dark:text-slate-100">
								{{ selectedPlayer.nickname || selectedPlayer.name }}
							</div>
							<div class="text-xs text-slate-500 dark:text-slate-400">
								{{ selectedPlayer.email }}
							</div>
						</div>
						<button @click="selectedUserId = ''; rawStats = null"
							class="text-xs text-red-600 dark:text-red-400 hover:underline">
							Limpar
						</button>
					</div>
				</div>
			</Card>

			<div v-if="!selectedUserId" class="text-center py-10">
				<Card class="p-6">
					<p class="text-sm text-slate-500 dark:text-slate-400">Seleciona um jogador para veres o histórico
						dele</p>
				</Card>
			</div>

			<div v-else-if="error" class="p-2">
				<Card class="bg-red-50 dark:bg-red-900/20 border-red-200">
					<CardContent class="p-4 text-center">
						<p class="text-red-600 dark:text-red-400">{{ error }}</p>
						<Button variant="outline" size="sm" class="mt-2" @click="loadStats">Tentar novamente</Button>
					</CardContent>
				</Card>
			</div>

			<div v-else-if="loading" class="flex justify-center py-10">
				<span class="text-sm text-gray-500 dark:text-gray-400">A carregar o histórico...</span>
			</div>

			<div v-else class="space-y-6">
				<div class="flex flex-wrap gap-2">
					<span
						class="inline-flex items-center gap-1 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200 px-3 py-1 text-xs dark:bg-emerald-900/30 dark:text-emerald-200 dark:border-emerald-800">
						<span class="h-2.5 w-2.5 rounded-full bg-emerald-500"></span>
						Vitórias: {{ matchStats.wins }}
					</span>
					<span
						class="inline-flex items-center gap-1 rounded-full bg-rose-50 text-rose-700 border border-rose-200 px-3 py-1 text-xs dark:bg-rose-900/30 dark:text-rose-200 dark:border-rose-800">
						<span class="h-2.5 w-2.5 rounded-full bg-rose-500"></span>
						Derrotas: {{ matchStats.losses ?? 0 }}
					</span>
					<span
						class="inline-flex items-center gap-1 rounded-full bg-amber-50 text-amber-700 border border-amber-200 px-3 py-1 text-xs dark:bg-amber-900/30 dark:text-amber-200 dark:border-amber-800">
						<span class="h-2.5 w-2.5 rounded-full bg-amber-500"></span>
						Capotes: {{ gameStats.capotes ?? 0 }}
					</span>
					<span
						class="inline-flex items-center gap-1 rounded-full bg-blue-50 text-blue-700 border border-blue-200 px-3 py-1 text-xs dark:bg-blue-900/30 dark:text-blue-200 dark:border-blue-800">
						<span class="h-2.5 w-2.5 rounded-full bg-blue-500"></span>
						Bandeiras: {{ gameStats.bandeiras ?? 0 }}
					</span>
					<span
						class="inline-flex items-center gap-1 rounded-full bg-yellow-50 text-yellow-700 border border-yellow-200 px-3 py-1 text-xs dark:bg-yellow-900/30 dark:text-yellow-200 dark:border-yellow-800">
						<span class="h-2.5 w-2.5 rounded-full bg-yellow-500"></span>
						Empates: {{ matchStats.draws ?? 0 }}
					</span>
					<span
						class="inline-flex items-center gap-1 rounded-full bg-gray-50 text-gray-700 border border-gray-200 px-3 py-1 text-xs dark:bg-gray-900/30 dark:text-gray-200 dark:border-gray-800">
						<span class="h-2.5 w-2.5 rounded-full bg-gray-500"></span>
						Interrompidas: {{ matchStats.interrupted ?? 0 }}
					</span>
				</div>

				<div class="grid grid-cols-2 gap-4">
					<Card class="p-4 space-y-2">
						<div class="flex items-center gap-2 text-xs text-gray-500">
							<span
								class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-200">
								<Trophy class="h-4 w-4" />
							</span>
							<span>Partidas ganhas</span>
						</div>
						<p class="text-2xl font-bold">{{ matchStats.wins }}</p>
						<p class="text-[11px] text-gray-400">
							{{ matchStats.total }} partidas - {{ matchStats.win_rate }}% taxa de vitórias
						</p>
					</Card>
					<Card class="p-4 space-y-2">
						<div class="flex items-center gap-2 text-xs text-gray-500">
							<span
								class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-200">
								<Gamepad2 class="h-4 w-4" />
							</span>
							<span>Jogos ganhos</span>
						</div>
						<p class="text-2xl font-bold">{{ gameStats.wins }}</p>
						<p class="text-[11px] text-gray-400">
							Capotes: {{ gameStats.capotes }} - Bandeiras: {{ gameStats.bandeiras }}
						</p>
					</Card>

					<Card class="p-4 space-y-2 col-span-2">
						<div class="flex items-center gap-2 text-xs text-gray-500">
							<span
								class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-indigo-100 text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-200">
								<TrendingUp class="h-4 w-4" />
							</span>
							<span>Pontos médios por partida</span>
						</div>
						<p class="text-2xl font-bold">{{ matchStats.points.average }}</p>
						<p class="text-[11px] text-gray-400">
							Pontos totais: {{ matchStats.points.total }}
						</p>
					</Card>
				</div>

				<Card class="p-4">
					<p class="text-xs font-semibold text-gray-500 mb-2">Por Variante</p>
					<div class="grid grid-cols-2 gap-4 text-sm">
						<div v-for="variant in variants" :key="variant.id">
							<p class="font-semibold mb-1">Bisca de {{ variant.label }}</p>
							<p class="text-xs text-gray-500">
								Partidas ganhas: {{ variantMatchWins(variant.id) ?? 0 }}
							</p>
							<p class="text-xs text-gray-500">
								Jogos ganhos: {{ variantGameWins(variant.id) ?? 0 }}
							</p>
							<p class="text-[11px] text-gray-400">
								Capotes: {{ variantCapotes(variant.id) ?? 0 }} - Bandeiras: {{
									variantBandeiras(variant.id) ?? 0 }}
							</p>
						</div>
					</div>
				</Card>

				<div>
					<h2 class="text-sm font-semibold mb-2">Partidas Multiplayer Recentes</h2>
					<div v-if="recentMatches.length === 0" class="text-xs text-gray-500">
						Ainda não há partidas.
					</div>
					<div v-else class="space-y-2">
						<Card v-for="m in recentMatches" :key="m.id" class="p-3 flex items-start justify-between gap-3">
							<div class="flex items-start gap-3">
								<span :class="[
									'inline-flex h-9 w-9 items-center justify-center rounded-full text-white',
									m.result === 'win' ? 'bg-emerald-500' : m.result === 'loss' ? 'bg-rose-500' : 'bg-gray-400'
								]">
									<component :is="m.result === 'win' ? Trophy : m.result === 'loss' ? Ban : Gamepad2"
										class="h-4 w-4" />
								</span>
								<div>
									<p class="text-sm font-semibold flex items-center gap-2">
										<span>{{ m.result === 'win' ? 'Vitória' : (m.result === 'loss' ? 'Derrota' :
											'Jogado') }}</span>
										<span class="text-[11px] text-gray-400">{{ formatDate(m.played_at) }}</span>
									</p>
									<p class="text-xs text-gray-500">vs {{ m.opponent_name }}</p>
									<p class="text-xs text-gray-500 mt-1">
										Pontos: <span class="font-semibold text-gray-700 dark:text-gray-200">{{
											m.user_points }}</span> - {{ m.opponent_points }} - Duração: {{
												formatDuration(m.duration) }}
									</p>
								</div>
							</div>
							<div class="flex items-center gap-2 text-xs">
								<span
									class="rounded-full border px-2 py-1 text-gray-600 dark:text-gray-300 dark:border-gray-700">{{
										m.type ?? 'Multiplayer' }}</span>
							</div>
						</Card>
					</div>
				</div>
			</div>
		</div>
	</div>
</template>

<script setup>
import { onMounted, ref, computed } from 'vue'
import { useRouter } from 'vue-router'
import { useAPIStore } from '@/stores/api'
import { Button } from '@/components/ui/button'
import { Card, CardContent } from '@/components/ui/card'
import { ArrowLeft, Trophy, Gamepad2, TrendingUp, Ban, X, Car } from 'lucide-vue-next'
import { inject } from 'vue'
import axios from 'axios'

const router = useRouter()
const apiStore = useAPIStore()
const API_BASE_URL = inject('apiBaseURL')

const loading = ref(false)
const error = ref(null)
const players = ref([])
const selectedUserId = ref('')
const searchQuery = ref('')
const rawStats = ref(null)

const variants = [
	{ id: '3', label: '3' },
	{ id: '9', label: '9' },
]

const filteredPlayers = computed(() => {
	if (!searchQuery.value) return []
	const query = searchQuery.value.toLowerCase()
	return players.value.filter(u =>
		(u.name && u.name.toLowerCase().includes(query)) ||
		(u.nickname && u.nickname.toLowerCase().includes(query)) ||
		(u.email && u.email.toLowerCase().includes(query))
	).slice(0, 200)
})

const selectedPlayer = computed(() => {
	if (!selectedUserId.value) return null
	return players.value.find(u => u.id === Number(selectedUserId.value))
})

const matchStats = computed(() => {
	if (!rawStats.value) {
		return {
			wins: 0,
			losses: 0,
			total: 0,
			win_rate: 0,
			points: { total: 0, average: 0 },
			wins_by_variant: {},
			losses_by_variant: {},
		}
	}

	return rawStats.value.match_stats ?? {
		wins: rawStats.value.wins ?? 0,
		losses: rawStats.value.losses ?? 0,
		total: rawStats.value.total_matches ?? 0,
		win_rate: rawStats.value.win_rate ?? 0,
		points: {
			total: rawStats.value.total_points ?? 0,
			average: rawStats.value.avg_points ?? 0,
		},
		wins_by_variant: {},
		losses_by_variant: {},
	}
})

const gameStats = computed(() => {
	if (!rawStats.value) {
		return {
			wins: 0,
			wins_by_variant: {},
			capotes: 0,
			capotes_by_variant: {},
			bandeiras: 0,
			bandeiras_by_variant: {},
		}
	}

	return rawStats.value.game_stats ?? {
		wins: 0,
		wins_by_variant: {},
		capotes: 0,
		capotes_by_variant: {},
		bandeiras: 0,
		bandeiras_by_variant: {},
	}
})

const recentMatches = computed(() => rawStats.value?.recent_matches ?? [])

function variantMatchWins(id) {
	return matchStats.value.wins_by_variant?.[id]
}

function variantGameWins(id) {
	return gameStats.value.wins_by_variant?.[id]
}

function variantCapotes(id) {
	return gameStats.value.capotes_by_variant?.[id]
}

function variantBandeiras(id) {
	return gameStats.value.bandeiras_by_variant?.[id]
}

function formatDate(dt) {
	if (!dt) return '–'
	return new Date(dt).toLocaleString()
}

function formatDuration(seconds) {
	if (!seconds && seconds !== 0) return '–'
	const total = Math.floor(Number(seconds))
	const mins = Math.floor(total / 60)
	const secs = total % 60
	return `${mins}m ${secs}s`
}

function goBack() {
	router.back()
}

async function loadPlayers() {
	try {
		const res = await apiStore.getAllUsers()
		players.value = (res.data || []).filter(u => u.type === 'P')
	} catch (e) {
		console.error('Failed to load users', e)
	}
}

function selectPlayer(userId) {
	selectedUserId.value = userId
	searchQuery.value = ''
	loadStats()
}

async function loadStats() {
	if (!selectedUserId.value) {
		rawStats.value = null
		return
	}

	loading.value = true
	error.value = null
	try {
		const res = await axios.get(`${API_BASE_URL}/admin/users/${selectedUserId.value}/stats`)
		rawStats.value = res.data
	} catch (e) {
		console.error('Failed to load player stats', e)
		error.value = 'Não foi possível carregar as estatísticas deste jogador.'
		rawStats.value = null
	} finally {
		loading.value = false
	}
}

onMounted(loadPlayers)
</script>

<style scoped></style>
