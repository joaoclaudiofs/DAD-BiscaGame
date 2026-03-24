<template>
    <div class="min-h-screen bg-slate-950 text-slate-50">
        <div class="mx-auto w-full max-w-6xl px-4 py-8 space-y-6">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <p class="text-[10px] uppercase tracking-[0.3em] text-slate-400">Partida</p>
                    <h1 class="text-2xl font-semibold leading-tight">Mesa de jogo</h1>
                </div>
                <div class="flex items-center gap-2">
                    <button
                        v-if="match"
                        type="button"
                        class="inline-flex items-center gap-2 rounded-md bg-rose-600 px-3 py-2 text-sm font-semibold text-slate-50 shadow hover:bg-rose-500 focus:outline-none focus:ring-2 focus:ring-rose-400 focus:ring-offset-2 focus:ring-offset-slate-900 transition"
                        @click="handleForfeit"
                    >
                        Desistir (forfeit)
                    </button>
                    <div v-if="match" class="flex items-center gap-2 rounded-full bg-slate-900/80 px-3 py-2 border border-slate-800 shadow-inner">
                        <span class="h-2 w-2 rounded-full" :class="isMyTurn ? 'bg-emerald-400 animate-pulse' : 'bg-slate-500'"></span>
                        <span class="text-sm font-medium" :class="isMyTurn ? 'text-emerald-200' : 'text-slate-300'">
                            {{ isMyTurn ? 'É o teu turno' : 'É o turno do adversário' }}
                        </span>
                    </div>
                </div>
            </div>

            <div v-if="match" class="grid gap-3 sm:grid-cols-2">
                <div class="rounded-xl border border-slate-800 bg-slate-900/80 p-4 shadow-lg">
                    <div class="flex items-center justify-between">
                        <div class="space-y-1">
                            <div class="flex items-center gap-2">
                                <p class="text-xs uppercase tracking-wide text-emerald-300">Tu</p>
                                <span v-if="myPointsGain" class="points-gain bg-emerald-500/20 text-emerald-200 text-xs px-2 py-0.5 rounded-full font-semibold">+{{ myPointsGain }}</span>
                            </div>
                            <p class="text-2xl font-semibold">{{ myMatchMarks }}</p>
                            <p class="text-sm text-slate-300">Pontos da rodada: {{ myGamePoints }}</p>
                        </div>
                        <div class="text-right">
                            <p class="text-xs text-slate-400">Cartas na mão</p>
                            <p class="text-lg font-medium">{{ playerHand.length }}</p>
                        </div>
                    </div>
                </div>
                <div class="rounded-xl border border-slate-800 bg-slate-900/60 p-4 shadow-lg">
                    <div class="flex items-center justify-between">
                        <div class="space-y-1">
                            <div class="flex items-center gap-2">
                                <p class="text-xs uppercase tracking-wide text-slate-300">{{ opponentName }}</p>
                                <span v-if="oppPointsGain" class="points-gain bg-slate-500/30 text-slate-200 text-xs px-2 py-0.5 rounded-full font-semibold">+{{ oppPointsGain }}</span>
                            </div>
                            <p class="text-2xl font-semibold">{{ oppMatchMarks }}</p>
                            <p class="text-sm text-slate-300">Pontos da rodada: {{ oppGamePoints }}</p>
                        </div>
                        <div class="text-right">
                            <p class="text-xs text-slate-400">Cartas na mão</p>
                            <p class="text-lg font-medium">{{ opponentHand.length }}</p>
                        </div>
                    </div>
                </div>
            </div>
            <div v-if="match" class="rounded-2xl border border-slate-800 bg-gradient-to-br from-slate-900/90 via-slate-900/60 to-slate-950 shadow-2xl overflow-hidden relative">
                <div class="absolute inset-0 pointer-events-none opacity-60" style="background: radial-gradient(120% 120% at 50% 40%, rgba(14,165,233,0.08), transparent 45%), radial-gradient(90% 90% at 70% 70%, rgba(16,185,129,0.08), transparent 50%);"></div>
                <div class="relative grid gap-6 p-6 lg:grid-cols-[200px_1fr_200px] items-center">
                    <div class="flex flex-col items-center gap-3">
                        <p class="text-xs uppercase tracking-wide text-slate-400">Baralho</p>
                        <div class="relative">
                            <div class="translate-x-3 translate-y-3 opacity-70">
                                <MatchCard deck="Classic" :card="trumpCard || { suit: 'hearts', face: 'A' }" :faceDown="true" />
                            </div>
                            <div class="-translate-x-1 -translate-y-1">
                                <MatchCard v-if="trumpCard" deck="Classic" :card="trumpCard" :faceDown="false" />
                            </div>
                        </div>
                        <p class="text-xs text-slate-300">{{ deckCount }} cartas restantes</p>
                    </div>

                    <div class="rounded-xl border border-slate-800/70 bg-slate-900/60 p-4 shadow-inner">
                        <div class="flex items-center justify-between mb-3">
                            <div>
                                <p class="text-xs uppercase tracking-wide text-slate-400">Mesa</p>
                                <p class="text-lg font-semibold">Rodada atual</p>
                            </div>
                            <div class="text-right text-xs text-slate-400">{{ boardView.length }} cartas na mesa</div>
                        </div>
                        <TransitionGroup name="card-flight" tag="div"
                            class="relative flex items-center justify-center min-h-[140px]">
                            <div v-for="(boardEntry, index) in boardView"
                                :key="boardEntry.id || boardEntry.card.suit + boardEntry.card.value + '-' + index"
                                class="board-card"
                                :style="{ zIndex: index }">
                                <MatchCard deck="Classic"
                                    :card="{ suit: boardEntry.card.suit, face: boardEntry.card.value }"
                                    :faceDown="false" />
                            </div>
                        </TransitionGroup>
                        <p v-if="!boardView.length" class="text-sm text-slate-400">A aguardar cartas...</p>
                    </div>
                    <div class="flex flex-col gap-3 text-sm text-slate-300">
                        <div class="rounded-lg border border-slate-800 bg-slate-900/70 p-3">
                            <p class="text-xs uppercase tracking-wide text-slate-400 mb-1">Trunfo</p>
                            <p class="font-semibold">{{ trumpCard ? (trumpCard.suit + ' ' + trumpCard.face) : '—' }}</p>
                        </div>
                        <div class="rounded-lg border border-slate-800 bg-slate-900/70 p-3">
                            <p class="text-xs uppercase tracking-wide text-slate-400 mb-1">Fase</p>
                            <p class="font-semibold">{{ isMyTurn ? 'É a tua jogada' : 'A aguardar adversário' }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <div v-if="match" class="space-y-4">
                <div class="rounded-xl border border-slate-800 bg-slate-900/70 p-4 shadow">
                    <div class="flex items-center justify-between mb-3">
                        <p class="text-sm font-semibold text-slate-200">Mão do {{ opponentName }}</p>
                        <span class="text-xs text-slate-400">{{ opponentHand.length }} cartas</span>
                    </div>
                    <TransitionGroup name="card-motion" tag="div" class="flex flex-wrap gap-2 justify-center">
                        <MatchCard v-for="(card, index) in opponentHand"
                            :key="card.id || card.suit + card.value + '-' + index"
                            deck="Classic"
                            :card="{ suit: card.suit, face: card.value }" :faceDown="true" />
                    </TransitionGroup>
                </div>

                <div class="rounded-xl border border-emerald-700/50 bg-gradient-to-r from-emerald-900/40 to-slate-900/70 p-4 shadow-lg">
                    <div class="flex items-center justify-between mb-3">
                        <p class="text-sm font-semibold text-emerald-200">A tua mão</p>
                        <div class="flex items-center gap-2">
                            <span class="text-xs text-emerald-200">Clica para jogar</span>
                            <div class="flex gap-1">
                                <button @click="sendEmote('Hello')" class="px-2 py-1 text-xs rounded bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-emerald-300 transition">
                                    Hello
                                </button>
                                <button @click="sendEmote('Nice Play')" class="px-2 py-1 text-xs rounded bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-emerald-300 transition">
                                    Nice Play
                                </button>
                                <button @click="sendEmote('Noob')" class="px-2 py-1 text-xs rounded bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-red-300 transition">
                                    Noob
                                </button>
                            </div>
                        </div>
                    </div>
                    <TransitionGroup name="card-motion" tag="div" class="flex flex-wrap gap-2 justify-center">
                        <MatchCard v-for="(card, index) in playerHand"
                            :key="card.id || card.suit + card.value + '-' + index"
                            deck="Classic"
                            :card="{ suit: card.suit, face: card.value }" :faceDown="false"
                            @playCard="handlePlayCard" />
                    </TransitionGroup>
                </div>
            </div>
        </div>

        <Transition name="emote-popup">
            <div v-if="receivedEmote" class="fixed bottom-8 left-8 z-50">
                <div class="emote-bubble bg-slate-800 border border-slate-700 rounded-xl px-6 py-3 shadow-2xl">
                    <p class="text-sm text-slate-400 mb-1">{{ opponentName }}</p>
                    <p class="text-2xl font-bold text-slate-100">{{ receivedEmote }}</p>
                </div>
            </div>
        </Transition>

        <Transition name="points-popup">
            <div v-if="showPointsPopup" class="fixed inset-0 z-50 flex items-center justify-center pointer-events-none">
                <div class="points-popup-content text-emerald-400">
                    <div class="text-8xl font-black tracking-tight">+{{ pointsValue }}</div>
                    <div class="text-2xl font-semibold mt-2 uppercase tracking-widest opacity-80">Pontos!</div>
                </div>
            </div>
        </Transition>
        <Transition name="turn-popup">
            <div v-if="showTurnPopup" class="fixed inset-0 z-40 flex items-center justify-center pointer-events-none">
                <div class="turn-popup-content text-emerald-400">
                    <div class="text-6xl font-black tracking-tight">É O TEU TURNO!</div>
                </div>
            </div>
        </Transition>

        <div v-if="match && match.status === 'Ended'" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
            <div class="bg-slate-950 border border-slate-800 rounded-xl shadow-2xl p-6 w-full max-w-md space-y-4 text-center">
                <h2 class="text-2xl font-semibold">
                    <span v-if="match.is_draw">Empate</span>
                    <span v-else-if="didIWin">Tu venceste</span>
                    <span v-else>Tu perdeste</span>
                </h2>

                <button type="button"
                    class="mt-4 inline-flex items-center justify-center rounded-md bg-emerald-500 px-4 py-2 text-sm font-semibold text-slate-950 hover:bg-emerald-400 transition"
                    @click="goHome">
                    Voltar para o lobby
                </button>
            </div>
        </div>

        <div v-if="match && match.status === 'Interrupted'" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
            <div class="bg-slate-950 border border-slate-800 rounded-xl shadow-2xl p-6 w-full max-w-md space-y-4 text-center">
                <h2 class="text-2xl font-semibold">
					<span>O adversário desistiu da partida</span>
                </h2>

                <button type="button"
                    class="mt-4 inline-flex items-center justify-center rounded-md bg-emerald-500 px-4 py-2 text-sm font-semibold text-slate-950 hover:bg-emerald-400 transition"
                    @click="goHome">
                    Voltar para o lobby
                </button>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed, ref, watch, inject, onMounted, onUnmounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useMatchStore } from '@/stores/match'
import { useSocketStore } from '@/stores/socket'
import { useAuthStore } from '@/stores/auth'
import MatchCard from '@/components/match/Card.vue'

const route = useRoute();
const router = useRouter();
const matchStore = useMatchStore();
const authStore = useAuthStore();
const socketStore = useSocketStore();
const socket = inject('socket');

const receivedEmote = ref(null);
let emoteTimeout = null;

const match = computed(() => {
    const id = Number(route.params.id);
    return matchStore.matches.find((m) => m.id === id);
});

const currentGame = computed(() => match.value?.games?.[match.value.games.length - 1] ?? null);

const asPlayerOne = computed(() => {
    if (!match.value || !authStore.currentUser) return false;
    return match.value.player1_user_id === authStore.currentUser.id;
});

const myMatchMarks = computed(() => {
    if (!match.value) return 0;
    return asPlayerOne.value ? match.value.player1_marks : match.value.player2_marks;
});

const oppMatchMarks = computed(() => {
    if (!match.value) return 0;
    return asPlayerOne.value ? match.value.player2_marks : match.value.player1_marks;
});

const opponentName = computed(() => {
    if (!match.value) return 'Adversário';
    const opponent = asPlayerOne.value ? match.value.player2 : match.value.player1;
    return opponent?.nickname || opponent?.name || 'Adversário';
});

const myGamePoints = computed(() => {
    if (!currentGame.value) return 0;
    return asPlayerOne.value ? currentGame.value.player1_points : currentGame.value.player2_points;
});

const oppGamePoints = computed(() => {
    if (!currentGame.value) return 0;
    return asPlayerOne.value ? currentGame.value.player2_points : currentGame.value.player1_points;
});

const myPointsGain = ref(0);
const oppPointsGain = ref(0);
const showPointsPopup = ref(false);
const pointsValue = ref(0);
const showTurnPopup = ref(false);

let pointsTimeout = null;
let turnTimeout = null;
let autoForfeitTimeout = null;
let lastMyGamePoints = 0;
let lastIsMyTurn = false;
const INACTIVITY_TIMEOUT = 20000; 

watch(() => match.value?.games?.[match.value.games.length - 1]?.player1_points, (newPoints) => {
    if (!match.value || !authStore.currentUser) return;
    
    const isPlayer1 = match.value.player1_user_id === authStore.currentUser.id;
    if (!isPlayer1) return;
    
    const delta = (newPoints || 0) - lastMyGamePoints;
    lastMyGamePoints = newPoints || 0;
    
    if (delta > 0 && !showPointsPopup.value) {
        myPointsGain.value = delta;
        pointsValue.value = delta;
        showPointsPopup.value = true;
        
        if (pointsTimeout) clearTimeout(pointsTimeout);
        pointsTimeout = setTimeout(() => { 
            showPointsPopup.value = false;
            myPointsGain.value = 0;
            pointsTimeout = null;
        }, 500);
    }
});

watch(() => match.value?.games?.[match.value.games.length - 1]?.player2_points, (newPoints) => {
    if (!match.value || !authStore.currentUser) return;
    
    const isPlayer2 = match.value.player2_user_id === authStore.currentUser.id;
    if (!isPlayer2) return;
    
    const delta = (newPoints || 0) - lastMyGamePoints;
    lastMyGamePoints = newPoints || 0;
    
    if (delta > 0 && !showPointsPopup.value) {
        myPointsGain.value = delta;
        pointsValue.value = delta;
        showPointsPopup.value = true;
        
        if (pointsTimeout) clearTimeout(pointsTimeout);
        pointsTimeout = setTimeout(() => { 
            showPointsPopup.value = false;
            myPointsGain.value = 0;
            pointsTimeout = null;
        }, 500);
    }
});

const deckCount = computed(() => match.value?.deck?.length ?? 0);

const trumpCard = computed(() => {
    if (!match.value?.trump) return null;
    return { suit: match.value.trump.suit, face: match.value.trump.value };
});

const boardCards = computed(() => match.value?.board ?? []);
const boardView = ref([]);

//guardar as cartas visíveis na mesa um pouco após a rodada encerrar
watch(boardCards, (cards) => {
    if (cards.length === 0) {
        //aguardar um segundo antes de limpar a visualização
        setTimeout(() => {
            if ((match.value?.board?.length ?? 0) === 0) {
                boardView.value = [];
            }
        }, 1000);
    } else {
        //mostrar imediatamente quando cartas são jogadas
        boardView.value = cards;
    }
}, { immediate: true, deep: true });

const isMyTurn = computed(() => {
    if (!match.value || !authStore.currentUser) return false;
    return match.value.turn === authStore.currentUser.id;
});

//mostrar popup quando passa a ser o turno do jogador
watch(() => match.value?.turn, (newTurn) => {
    if (!match.value || !authStore.currentUser || match.value.status === 'Ended') return;
    
    const isMyTurnNow = newTurn === authStore.currentUser.id;
    
    if (isMyTurnNow && !lastIsMyTurn && !showPointsPopup.value && !showTurnPopup.value) {
        showTurnPopup.value = true;
        
        if (turnTimeout) clearTimeout(turnTimeout);
        turnTimeout = setTimeout(() => {
            showTurnPopup.value = false;
            turnTimeout = null;
        }, 500);
    }
    
    if (autoForfeitTimeout) clearTimeout(autoForfeitTimeout);
    if (isMyTurnNow) {
        autoForfeitTimeout = setTimeout(() => {
            if (isMyTurn.value && match.value && match.value.status !== 'Ended') {
                handleForfeit();
            }
        }, INACTIVITY_TIMEOUT);
    }
    
    lastIsMyTurn = isMyTurnNow;
});

const playerHand = computed(() => {
    if (!match.value || !authStore.currentUser) return [];
    const userId = authStore.currentUser.id;

    if (match.value.player1_user_id === userId) {
        return match.value.player1_hand || [];
    }
    if (match.value.player2_user_id === userId) {
        return match.value.player2_hand || [];
    }

    return [];
});

const opponentHand = computed(() => {
    if (!match.value || !authStore.currentUser) return [];
    const userId = authStore.currentUser.id;

    if (match.value.player1_user_id === userId) {
        return match.value.player2_hand || [];
    }
    if (match.value.player2_user_id === userId) {
        return match.value.player1_hand || [];
    }

    return [];
});

const didIWin = computed(() => {
    if (!authStore.currentUser || !match.value) return false;

    if (match.value.is_draw) return false;
    return match.value.winner_user_id === authStore.currentUser.id;
});

const goHome = () => {
    router.push('/dashboard');
};

const handlePlayCard = (card) => {
    if (!isMyTurn.value || !match.value) return;

    if (autoForfeitTimeout) clearTimeout(autoForfeitTimeout);
    
    const id = match.value.id;
    socketStore.emitPlayCard(id, { suit: card.suit, value: card.face });
};

const sendEmote = (emote) => {
    if (!socket || !match.value) return;
    
    socket.emit('send-emote', { 
        matchId: match.value.id, 
        emote 
    });
};

const handleForfeit = () => {
    if (!match.value) return;
    if (!authStore.currentUser || !socket) return;

    socket.emit('forfeit', { matchId: match.value.id });
};

const handleEmoteReceived = ({ emote }) => {
    receivedEmote.value = emote;
    
    if (emoteTimeout) clearTimeout(emoteTimeout);
    emoteTimeout = setTimeout(() => {
        receivedEmote.value = null;
        emoteTimeout = null;
    }, 2500);
};

onMounted(() => {
    if (socket) {
        socket.on('receive-emote', handleEmoteReceived);
    }
});

onUnmounted(() => {
    if (socket) {
        socket.off('receive-emote', handleEmoteReceived);
    }
    if (emoteTimeout) clearTimeout(emoteTimeout);
    if (autoForfeitTimeout) clearTimeout(autoForfeitTimeout);
});
</script>

<style scoped>
.card-flight-enter-active,
.card-flight-leave-active,
.card-flight-move {
  transition: all 420ms ease;
}

.card-flight-enter-from {
  opacity: 0;
  transform: translateY(24px) scale(0.9);
}

.card-flight-leave-to {
  opacity: 0;
  transform: translateY(-18px) scale(0.9);
}

.card-motion-enter-active,
.card-motion-leave-active,
.card-motion-move {
  transition: all 360ms ease;
}

.card-motion-enter-from {
  opacity: 0;
  transform: translateY(18px) scale(0.94);
}

.card-motion-leave-to {
  opacity: 0;
  transform: translateY(-16px) scale(0.94);
}

.board-card {
  position: absolute;
  transition: all 320ms ease;
}

.board-card:nth-child(1) {
  transform: translateX(-35px) translateY(-8px) rotate(-8deg);
  z-index: 1;
}

.board-card:nth-child(2) {
  transform: translateX(35px) translateY(8px) rotate(8deg);
  z-index: 2;
}

.points-gain {
  animation: pop-points 0.75s ease;
}

@keyframes pop-points {
  0% { opacity: 0; transform: translateY(6px) scale(0.9); }
  40% { opacity: 1; transform: translateY(-2px) scale(1.08); }
  100% { opacity: 0; transform: translateY(-10px) scale(0.95); }
}

.points-popup-enter-active {
  animation: points-blast 1.2s cubic-bezier(0.34, 1.56, 0.64, 1);
}

.points-popup-leave-active {
  animation: points-blast 0.3s reverse ease-out;
}

.points-popup-content {
  text-align: center;
  text-shadow: 0 0 20px currentColor, 0 0 40px currentColor;
  filter: drop-shadow(0 10px 30px rgba(0,0,0,0.5));
}

@keyframes points-blast {
  0% { 
    opacity: 0; 
    transform: scale(0.3) translateY(40px) rotate(-8deg);
  }
  50% { 
    opacity: 1; 
    transform: scale(1.15) translateY(-10px) rotate(2deg);
  }
  70% {
    opacity: 1;
    transform: scale(0.95) translateY(0) rotate(0deg);
  }
  100% { 
    opacity: 0; 
    transform: scale(1.1) translateY(-20px) rotate(0deg);
  }
}

.turn-popup-enter-active {
  animation: turn-flash 1.5s cubic-bezier(0.34, 1.56, 0.64, 1);
}

.turn-popup-leave-active {
  animation: turn-flash 0.3s reverse ease-out;
}

.turn-popup-content {
  text-align: center;
  text-shadow: 0 0 30px currentColor, 0 0 60px currentColor, 0 0 90px currentColor;
  filter: drop-shadow(0 10px 40px rgba(16, 185, 129, 0.6));
}

@keyframes turn-flash {
  0% { 
    opacity: 0; 
    transform: scale(0.5) translateY(30px);
  }
  40% { 
    opacity: 1; 
    transform: scale(1.1) translateY(-5px);
  }
  60% {
    opacity: 1;
    transform: scale(1) translateY(0);
  }
  100% { 
    opacity: 0; 
    transform: scale(0.9) translateY(-15px);
  }
}

.emote-popup-enter-active {
  animation: emote-slide 0.4s ease-out;
}

.emote-popup-leave-active {
  animation: emote-slide 0.3s reverse ease-in;
}

.emote-bubble {
  animation: emote-bounce 0.5s ease infinite alternate;
}

@keyframes emote-slide {
  0% {
    opacity: 0;
    transform: translateX(-20px);
  }
  100% {
    opacity: 1;
    transform: translateX(0);
  }
}

@keyframes emote-bounce {
  0% {
    transform: translateY(0);
  }
  100% {
    transform: translateY(-3px);
  }
}
</style>

