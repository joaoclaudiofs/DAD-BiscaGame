import { defineStore } from 'pinia'
import { inject } from 'vue'
import { ref } from 'vue'
import { useAuthStore } from './auth'
import { useWalletStore } from './wallet'
import { computed } from 'vue'
import { toast } from 'vue-sonner'

export const useMatchStore = defineStore('match', () => {
    const authStore = useAuthStore();
    const walletStore = useWalletStore();

    const socket = inject('socket')
    const matches = ref([]);

    const dbIds = ref({});
    const gameDbIds = ref({});

    const setDBId = (id, dbId) => {
        dbIds.value[id] = dbId;
    }

    const getDBId = (id) => {
        return dbIds.value[id];
    }

    const setGameDBId = (matchId, gameIndex, gameDbId) => {
        if (!gameDbIds.value[matchId]) {
            gameDbIds.value[matchId] = {};
        }

        gameDbIds.value[matchId][gameIndex] = gameDbId;
    }

    const getGameDBId = (matchId, gameIndex) => {
        if (!gameDbIds.value[matchId]) {
            return undefined;
        }
        
        return gameDbIds.value[matchId][gameIndex];
    }

    const setMatches = (newMatches) => {
        matches.value = newMatches;
    }

    const getMatchById = (id) => {
        return matches.value.find((m) => m.id === id);
    }

    const createMatch = async (type, stake) => {
        if (!authStore.currentUser) {
            toast.error('You must be logged in to create a match');
            return;
        }
        
        if (!socket || !socket.connected) {
            toast.error('Not connected to server. Please refresh the page.');
            return;
        }

        for (const m of matches.value) {
            if (m.player1_user_id === authStore.currentUser.id && m.status === 'Waiting') {
                toast.error('You already have a pending match.');
                return;
            }
        }

        await walletStore.fetchBalance();
        if (walletStore.balance < stake) {
            toast.error('You do not have enough coins to create this match.');
            return;
        }

        socket.emit('create-match', { type, stake });
    }

    const cancelMatch = (id) => {
        if (!authStore.currentUser) {
            toast.error('You must be logged in to cancel a match');
            return;
        }

        if (!socket || !socket.connected) {
            toast.error('Not connected to server. Please refresh the page.');
            return;
        }

        socket.emit('cancel-match', id);
    }

    const joinMatch = async (id) => {
        if (!authStore.currentUser) {
            toast.error('You must be logged in to create a match');
            return;
        }

        if (!socket || !socket.connected) {
            toast.error('Not connected to server. Please refresh the page.');
            return;
        }

        const match = getMatchById(id);
        await walletStore.fetchBalance();
        if (walletStore.balance < match.stake) {
            toast.error('You do not have enough coins to join this match.');
            return;
        }

        socket.emit('join-match', id);
    }

    const proposeStake = async (matchId, proposedStake) => {
        if (!authStore.currentUser) {
            toast.error('You must be logged in to propose a stake');
            return;
        }

        if (!socket || !socket.connected) {
            toast.error('Not connected to server. Please refresh the page.');
            return;
        }

        await walletStore.fetchBalance();
        if (walletStore.balance < proposedStake) {
            toast.error('You do not have enough coins for this stake.');
            return;
        }

        socket.emit('propose-stake', { matchId, proposedStake });
        toast.success('Stake proposal sent!');
    }

    const respondStakeProposal = async (matchId, accept) => {
        if (!authStore.currentUser) {
            toast.error('You must be logged in to respond to proposals');
            return;
        }

        if (!socket || !socket.connected) {
            toast.error('Not connected to server. Please refresh the page.');
            return;
        }

        await walletStore.fetchBalance();
        if (getMatchById(matchId).stakeProposal.amount > walletStore.balance && accept) { 
            toast.error('You do not have enough coins to accept this stake proposal.');
            return;
        }

        socket.emit('respond-stake-proposal', { matchId, accept });
        
        if (accept) {
            toast.success('Stake proposal accepted!');
        } else {
            toast.info('Stake proposal rejected.');
        }
    }

    const myMatches = computed(() => {
        return matches.value.filter((match) => match.player1_user_id == authStore.currentUser.id && match.status === 'Waiting');
    });

    const availableMatches = computed(() => {
        return matches.value.filter((match) => match.status === 'Waiting' && match.player1_user_id != authStore.currentUser.id);
    });

    const updateMatch = (updated) => {
        matches.value = matches.value.map(m => m.id === updated.id ? updated : m);
    };

    return {
        createMatch,
        availableMatches,
        matches,
        setMatches,
        myMatches,
        cancelMatch,
        joinMatch,
        proposeStake,
        respondStakeProposal,
        updateMatch,
        getMatchById,
        setDBId,
        getDBId,
        setGameDBId,
        getGameDBId
    }
});