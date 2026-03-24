<template>
	<div class="min-h-screen bg-gradient-to-br from-slate-50 to-blue-50 dark:from-slate-900 dark:to-slate-800 py-8">
		<div class="mx-auto max-w-5xl px-6">
			<div class="mb-8 flex items-center gap-4">
				<button @click="$router.back()"
					class="p-2 hover:bg-slate-200 dark:hover:bg-slate-700 rounded-lg transition-colors">
					<ArrowLeft class="h-6 w-6 text-slate-700 dark:text-slate-300" />
				</button>
				<div>
					<h1 class="text-3xl font-bold text-slate-900 dark:text-slate-50">Match Lobby</h1>
				</div>
			</div>

			<Card class="mb-8 bg-white dark:bg-slate-800 border-slate-200 dark:border-slate-700">
				<CardHeader>
					<CardTitle class="text-slate-900 dark:text-slate-50">Create New Match</CardTitle>
					<CardDescription class="text-slate-600 dark:text-slate-400">Choose match type and start a new game
					</CardDescription>
				</CardHeader>
				<CardContent class="flex items-center gap-6">
					<div class="flex flex-col gap-4 md:flex-row md:items-end md:gap-6">
						<div class="flex flex-col gap-2">
							<span class="text-sm font-medium text-slate-700 dark:text-slate-300">
								Match type
							</span>

							<RadioGroup v-model="type" class="flex items-center gap-4">
								<label
									class="flex items-center gap-2 text-sm cursor-pointer text-slate-700 dark:text-slate-300">
									<RadioGroupItem value="3" />
									<span>Bisca 3</span>
								</label>

								<label
									class="flex items-center gap-2 text-sm cursor-pointer text-slate-700 dark:text-slate-300">
									<RadioGroupItem value="9" />
									<span>Bisca 9</span>
								</label>
							</RadioGroup>
						</div>

						<div class="flex flex-col gap-2">
							<span class="text-sm font-medium text-slate-700 dark:text-slate-300">
								Stake
							</span>
							<input
								v-model.number="stake"
								type="number"
								min="0"
								class="w-32 rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-50"
							/>
						</div>
					</div>

					<Button @click="createMatch"
						class="bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 dark:from-blue-500 dark:to-indigo-500 dark:hover:from-blue-600 dark:hover:to-indigo-600">
						<Plus class="h-4 w-4 mr-2" />
						Create Match
					</Button>
				</CardContent>
			</Card>

			<div v-if="matchStore.myMatches.length > 0" class="mb-8">
				<h2 class="text-xl font-semibold text-slate-900 dark:text-slate-50 mb-4">My Matches</h2>
				<div class="grid gap-4 md:grid-cols-2">
					<Card v-for="match in matchStore.myMatches" :key="match.id"
						class="bg-white dark:bg-slate-800 border-slate-200 dark:border-slate-700">
						<CardHeader>
							<div class="flex items-start justify-between">
								<div class="space-y-2">
									<div class="flex items-center gap-2">
										<CardTitle class="text-slate-900 dark:text-slate-50">Match {{ match.id }}
										</CardTitle>
										<Badge :class="String(match.type) === '3'
											? 'bg-yellow-500 text-yellow-950'
											: 'bg-blue-500 text-blue-50'">
											Bisca {{ match.type }}
										</Badge>
									</div>
									<CardDescription class="text-slate-600 dark:text-slate-400">
										<User class="h-3 w-3 inline mr-1" />
										Player {{ match.player1_user_id }}
									</CardDescription>
									<CardDescription class="text-slate-600 dark:text-slate-400">
										Stake: {{ match.stake }} coins
									</CardDescription>
									
									<div v-if="match.stakeProposal" class="mt-3 p-3 bg-amber-50 dark:bg-amber-900/20 rounded-lg border border-amber-200 dark:border-amber-800">
										<p class="text-sm font-medium text-amber-900 dark:text-amber-200 mb-2">
											<DollarSign class="h-4 w-4 inline mr-1" />
											Stake proposal: {{ match.stakeProposal.amount }} coins
										</p>
										<div class="flex gap-2">
											<Button size="sm" @click="acceptProposal(match.id)" class="bg-emerald-600 hover:bg-emerald-700">
												<Check class="h-4 w-4 mr-1" />
												Accept
											</Button>
											<Button size="sm" variant="outline" @click="rejectProposal(match.id)">
												<X class="h-4 w-4 mr-1" />
												Reject
											</Button>
										</div>
									</div>
								</div>

								<Button variant="destructive" size="sm" @click="cancelMatch(match)">
									<X class="h-4 w-4 mr-1" />
									Cancel
								</Button>
							</div>
						</CardHeader>
					</Card>
				</div>
			</div>
			<div>
				<h2 class="text-xl font-semibold text-slate-900 dark:text-slate-50 mb-4">Available Matches</h2>
				<div v-if="matchStore.availableMatches.length === 0" class="text-center py-12">
					<div
						class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-slate-100 dark:bg-slate-800 mb-4">
						<Users class="h-8 w-8 text-slate-400" />
					</div>
					<p class="text-slate-600 dark:text-slate-400">No matches available right now</p>
					<p class="text-sm text-slate-500 dark:text-slate-500 mt-1">Create a match to get started!</p>
				</div>
				<div v-else class="grid gap-4 md:grid-cols-2">
					<Card v-for="match in matchStore.availableMatches" :key="match.id"
						class="bg-white dark:bg-slate-800 border-slate-200 dark:border-slate-700 hover:border-blue-400 dark:hover:border-blue-600 transition-colors">
						<CardHeader>
							<div class="flex items-start justify-between">
								<div class="space-y-2 flex-1">
									<div class="flex items-center gap-2">
										<CardTitle class="text-slate-900 dark:text-slate-50">Match {{ match.id }}
										</CardTitle>
										<Badge :class="String(match.type) === '3'
											? 'bg-yellow-500 text-yellow-950'
											: 'bg-blue-500 text-blue-50'">
											Bisca {{ match.type }}
										</Badge>
									</div>
									<CardDescription class="text-slate-600 dark:text-slate-400">
										<User class="h-3 w-3 inline mr-1" />
										Player {{ match.player1_user_id }}
									</CardDescription>
									<CardDescription class="text-slate-600 dark:text-slate-400">
										Stake: {{ match.stake }} coins
									</CardDescription>
									
									<div v-if="negotiatingStake[match.id]" class="mt-3 flex items-end gap-2">
										<div class="flex-1">
											<label class="text-xs text-slate-600 dark:text-slate-400 mb-1 block">Propose new stake</label>
											<input
												v-model.number="proposedStakes[match.id]"
												type="number"
												min="0"
												class="w-full rounded-md border border-slate-300 bg-white px-3 py-1.5 text-sm text-slate-900 shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-50"
											/>
										</div>
										<Button size="sm" @click="proposeStake(match.id)" class="bg-blue-600 hover:bg-blue-700">
											<DollarSign class="h-4 w-4 mr-1" />
											Send
										</Button>
										<Button size="sm" variant="outline" @click="toggleNegotiate(match.id)">
											<X class="h-4 w-4" />
										</Button>
									</div>
								</div>

								<div class="flex flex-col gap-2">
									<Button @click="joinMatch(match)"
										class="bg-emerald-600 hover:bg-emerald-700 dark:bg-emerald-500 dark:hover:bg-emerald-600">
										<LogIn class="h-4 w-4 mr-1" />
										Join
									</Button>
									<Button v-if="!negotiatingStake[match.id]" size="sm" variant="outline" @click="toggleNegotiate(match.id)">
										<DollarSign class="h-4 w-4 mr-1" />
										Negotiate
									</Button>
								</div>
							</div>
						</CardHeader>
					</Card>
				</div>
			</div>
		</div>
	</div>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import { useMatchStore } from '@/stores/match'
import { useSocketStore } from '@/stores/socket'
import { useAuthStore } from '@/stores/auth'
import { Card, CardHeader, CardTitle, CardDescription, CardContent } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { RadioGroup, RadioGroupItem } from '@/components/ui/radio-group'
import { Button } from '@/components/ui/button'
import { ArrowLeft, Plus, X, User, Users, LogIn, DollarSign, Check } from 'lucide-vue-next'

const matchStore = useMatchStore()
const socketStore = useSocketStore()
const authStore = useAuthStore()
const type = ref('9')
const stake = ref(50);
const negotiatingStake = ref({});
const proposedStakes = ref({});

const createMatch = () => {
	matchStore.createMatch(type.value, stake.value);
};

const cancelMatch = (match) => {
	matchStore.cancelMatch(match.id);
};

const joinMatch = (match) => {
	matchStore.joinMatch(match.id);
};

const toggleNegotiate = (matchId) => {
	if (negotiatingStake.value[matchId]) {
		delete negotiatingStake.value[matchId];
		delete proposedStakes.value[matchId];
	} else {
		const match = matchStore.getMatchById(matchId);
		negotiatingStake.value[matchId] = true;
		proposedStakes.value[matchId] = match.stake;
	}
};

const proposeStake = (matchId) => {
	matchStore.proposeStake(matchId, proposedStakes.value[matchId]);
	delete negotiatingStake.value[matchId];
};

const acceptProposal = (matchId) => {
	matchStore.respondStakeProposal(matchId, true);
};

const rejectProposal = (matchId) => {
	matchStore.respondStakeProposal(matchId, false);
};

onMounted(() => {
	socketStore.emitGetMatches();
});
</script>
