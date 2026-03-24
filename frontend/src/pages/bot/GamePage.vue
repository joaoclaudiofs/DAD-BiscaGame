<template>
    <div class="min-h-screen bg-slate-950 text-slate-50">
        <div class="mx-auto w-full max-w-6xl px-4 py-6 space-y-6">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div class="flex items-center gap-2">
                    <Button variant="ghost" size="icon" class="h-9 w-9" @click="router.push('/dashboard')">
                        <ChevronLeft class="h-5 w-5" />
                    </Button>
                    <div class="flex flex-col">
                        <span class="text-lg font-semibold">Estás a treinar contra o Bot</span>
                    </div>
                </div>
                <div class="flex items-center gap-2 rounded-full bg-slate-900/80 px-3 py-2 border border-slate-800 shadow-inner">
                    <span class="h-2 w-2 rounded-full"
                        :class="gameStore.isPlayersTurn ? 'bg-emerald-400 animate-pulse' : 'bg-slate-500'" />
                    <span class="text-sm font-medium"
                        :class="gameStore.isPlayersTurn ? 'text-emerald-200' : 'text-slate-300'">
                        {{ gameStore.isPlayersTurn === null ? 'A aguardar' : gameStore.isPlayersTurn ? 'É o teu turno' : 'Bot a jogar' }}
                    </span>
                </div>
            </div>

            <div class="grid gap-3 sm:grid-cols-2">
                <div class="rounded-xl border border-slate-800 bg-slate-900/80 p-4 shadow-lg">
                    <div class="flex items-center justify-between">
                        <div class="space-y-1">
                            <div class="flex items-center gap-2">
                                <User class="h-4 w-4 text-emerald-300" />
                                <p class="text-xs uppercase tracking-wide text-emerald-300">Tu</p>
                            </div>
                            <p class="text-2xl font-semibold">{{ gameStore.playerScore }}</p>
                            <p class="text-sm text-slate-300">Pontos da rodada: {{ playerRoundPoints }}</p>
                        </div>
                        <div class="text-right">
                            <p class="text-xs text-slate-400">Cartas na mão</p>
                            <p class="text-lg font-medium">{{ gameStore.playerHand.length }}</p>
                        </div>
                    </div>
                </div>
                <div class="rounded-xl border border-slate-800 bg-slate-900/60 p-4 shadow-lg">
                    <div class="flex items-center justify-between">
                        <div class="space-y-1">
                            <div class="flex items-center gap-2">
                                <Bot class="h-4 w-4 text-rose-300" />
                                <p class="text-xs uppercase tracking-wide text-slate-300">Bot</p>
                            </div>
                            <p class="text-2xl font-semibold">{{ gameStore.botScore }}</p>
                            <p class="text-sm text-slate-300">Pontos da rodada: {{ botRoundPoints }}</p>
                        </div>
                        <div class="text-right">
                            <p class="text-xs text-slate-400">Cartas na mão</p>
                            <p class="text-lg font-medium">{{ gameStore.botHand.length }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="rounded-2xl border border-slate-800 bg-gradient-to-br from-slate-900/90 via-slate-900/60 to-slate-950 shadow-2xl overflow-hidden relative">
                <div class="absolute inset-0 pointer-events-none opacity-60"
                    style="background: radial-gradient(120% 120% at 50% 40%, rgba(14,165,233,0.08), transparent 45%), radial-gradient(90% 90% at 70% 70%, rgba(16,185,129,0.08), transparent 50%);"></div>
                <div class="relative grid gap-6 p-6 lg:grid-cols-[200px_1fr_200px] items-center">
                    <div class="flex flex-col items-center gap-3">
                        <p class="text-xs uppercase tracking-wide text-slate-400">Baralho</p>
                        <div class="relative">
                            <div class="translate-x-3 translate-y-3 opacity-70">
                                <GameCard :deck="deck" :card="gameStore.trump || { suit: 'hearts', face: 'A' }" faceDown />
                            </div>
                            <div class="-translate-x-1 -translate-y-1">
                                <GameCard v-if="gameStore.trump" :deck="deck" :card="gameStore.trump" />
                            </div>
                        </div>
                        <p class="text-xs text-slate-300">{{ gameStore.deck.length }} cartas restantes</p>
                    </div>

                    <div class="rounded-xl border border-slate-800/70 bg-slate-900/60 p-4 shadow-inner">
                        <div class="flex items-center justify-between mb-3">
                            <div>
                                <p class="text-xs uppercase tracking-wide text-slate-400">Mesa</p>
                                <p class="text-lg font-semibold">Rodada atual</p>
                            </div>
                            <div class="text-right text-xs text-slate-400">{{ gameStore.board.length }} cartas na mesa</div>
                        </div>
                        <div class="relative flex items-center justify-center min-h-[140px] gap-3 flex-wrap">
                            <div v-for="card in gameStore.board" :key="card.id" class="shadow-lg">
                                <GameCard :deck="deck" :card="card" />
                            </div>
                            <p v-if="!gameStore.board.length" class="text-sm text-slate-400">A aguardar cartas...</p>
                        </div>
                    </div>

                    <div class="flex flex-col gap-3 text-sm text-slate-300">
                        <div class="rounded-lg border border-slate-800 bg-slate-900/70 p-3">
                            <p class="text-xs uppercase tracking-wide text-slate-400 mb-1">Trunfo</p>
                            <p class="font-semibold">{{ gameStore.trump ? (gameStore.trump.suit + ' ' + gameStore.trump.face) : '—' }}</p>
                        </div>
                        <div class="rounded-lg border border-slate-800 bg-slate-900/70 p-3">
                            <p class="text-xs uppercase tracking-wide text-slate-400 mb-1">Fase</p>
                            <p class="font-semibold">{{ gameStore.isPlayersTurn ? 'É a tua jogada' : 'A aguardar adversário' }}</p>
                        </div>
                        <div class="rounded-lg border border-slate-800 bg-slate-900/70 p-3" v-if="gameStore.capoteCount || gameStore.bandeira">
                            <p class="text-xs uppercase tracking-wide text-slate-400 mb-1">Bónus</p>
                            <p class="font-semibold" v-if="gameStore.capoteCount">Capotes: {{ gameStore.capoteCount }}</p>
                            <p class="font-semibold text-emerald-300" v-if="gameStore.bandeira">Bandeira ativa</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="space-y-4">
                <div class="rounded-xl border border-slate-800 bg-slate-900/70 p-4 shadow">
                    <div class="flex items-center justify-between mb-3">
                        <p class="text-sm font-semibold text-slate-200">Mão do Bot</p>
                        <span class="text-xs text-slate-400">{{ gameStore.botHand.length }} cartas</span>
                    </div>
                    <div class="flex flex-wrap gap-2 justify-center">
                        <GameCard v-for="card in gameStore.botHand" :key="card.id" :deck="deck" :card="card" faceDown />
                    </div>
                </div>

                <div class="rounded-xl border border-emerald-700/50 bg-gradient-to-r from-emerald-900/40 to-slate-900/70 p-4 shadow-lg">
                    <div class="flex items-center justify-between mb-3">
                        <p class="text-sm font-semibold text-emerald-200">A tua mão</p>
                        <span class="text-xs text-emerald-200">Clica para jogar</span>
                    </div>
                    <div class="flex flex-wrap gap-2 justify-center">
                        <GameCard v-for="card in gameStore.playerHand" :key="card.id" :deck="deck" :card="card"
                            :class="gameStore.isPlayersTurn ? 'cursor-pointer hover:-translate-y-1 transition' : 'opacity-60'"
                            @playCard="gameStore.isPlayersTurn && gameStore.playCard(card)" />
                    </div>
                </div>
            </div>
        </div>

        <Dialog :open="gameStore.showScoreboard">
            <DialogContent class="sm:max-w-md">
                <DialogHeader class="text-center">
                    <div class="flex flex-col items-center gap-3 py-2">
                        <div class="w-16 h-16 rounded-full flex items-center justify-center"
                            :class="gameStore.didPlayerWinGame ? 'bg-emerald-500/20' : 'bg-rose-500/20'">
                            <Trophy v-if="gameStore.didPlayerWinGame" class="h-8 w-8 text-emerald-500" />
                            <Frown v-else class="h-8 w-8 text-rose-500" />
                        </div>
                        <DialogTitle class="text-2xl">
                            <span :class="gameStore.didPlayerWinGame ? 'text-emerald-500' : 'text-rose-500'">
                                {{ gameStore.didPlayerWinGame ? 'Vitória' : 'Derrota' }}
                            </span>
                        </DialogTitle>
                        <DialogDescription class="sr-only">
                            Resultados do jogo
                        </DialogDescription>
                    </div>
                </DialogHeader>

                <div class="space-y-4 py-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div class="rounded-lg border border-slate-200/30 bg-slate-100 text-slate-900 p-4 text-center">
                            <User class="h-5 w-5 mx-auto text-emerald-600" />
                            <p class="text-xs text-slate-500">Tu</p>
                            <p class="text-3xl font-bold">{{ gameStore.playerPoints }}</p>
                        </div>
                        <div class="rounded-lg border border-slate-200/30 bg-slate-100 text-slate-900 p-4 text-center">
                            <Bot class="h-5 w-5 mx-auto text-rose-600" />
                            <p class="text-xs text-slate-500">Bot</p>
                            <p class="text-3xl font-bold">{{ gameStore.botPoints }}</p>
                        </div>
                    </div>

                    <div v-if="gameStore.capoteCount > 0" class="text-center">
                        <p class="text-xs text-slate-500">Capotes</p>
                        <p class="text-2xl font-bold text-amber-500">{{ gameStore.capoteCount }}</p>
                    </div>
                    <div v-if="gameStore.bandeira" class="text-center">
                        <p class="text-2xl text-emerald-500">Bandeira</p>
                    </div>
                </div>

                <DialogFooter class="flex-col gap-2 sm:flex-col">
                    <Button class="w-full"
                        @click="() => { gameStore.resetGame(); router.push('/dashboard'); }">
                        <Home class="h-4 w-4 mr-2" />
                        Voltar para a dashboard
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </div>
</template>

<script setup>
import { onMounted, ref, computed } from 'vue';
import { useGameStore } from '@/stores/game';
import GameCard from '@/components/game/Card.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogFooter,
    DialogDescription
} from '@/components/ui/dialog';
import {
    ChevronLeft,
    ChevronDown,
    ChevronUp,
    User,
    Bot,
    Layers,
    Trophy,
    Frown,
    Home
} from 'lucide-vue-next';

import { useRouter } from 'vue-router';

const router = useRouter();

const deck = ref('Classic');

const gameStore = useGameStore();
const playerRoundPoints = computed(() => gameStore.calculatePoints(gameStore.playerWins));
const botRoundPoints = computed(() => gameStore.calculatePoints(gameStore.botWins));
const showBotHand = ref(false);

onMounted(() => {
    gameStore.threebisca = router.currentRoute.value.path === '/game/3'
    gameStore.setBoard();
});
</script>
