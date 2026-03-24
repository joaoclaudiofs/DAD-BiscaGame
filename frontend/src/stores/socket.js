import { defineStore } from 'pinia'
import { inject } from 'vue'
import { useAuthStore } from './auth'
import { useMatchStore } from './match'
import { useWalletStore } from './wallet'
import { useAPIStore } from './api'
import { ref } from 'vue'
import router from '@/router'

export const useSocketStore = defineStore('socket', () => {
  const socket = inject('socket')
  const authStore = useAuthStore();
  const matchStore = useMatchStore();
  const walletStore = useWalletStore();
  const apiStore = useAPIStore();

  const joined = ref(false)

  const handleMatchEvents = () => {
    socket.on('matches', (matches) => {
      console.log(`[Socket] server emited matches | match count ${matches.length}`)
      matchStore.setMatches(matches)
    })

    socket.on('stake-proposal', ({ matchId, proposedBy, amount }) => {      
      console.log(`[Socket] Stake proposal received for match ${matchId}: ${amount}`)
      matchStore.updateMatch(matchStore.getMatchById(matchId))
    })

    socket.on('stake-proposal-response', ({ matchId, accept, newStake }) => {
      console.log(`[Socket] Stake proposal ${accept ? 'accepted' : 'rejected'} for match ${matchId}`)
      if (accept) {
        matchStore.updateMatch(matchStore.getMatchById(matchId))
      }
    })

    socket.on('match-ready', async (id) => {
      const match = matchStore.getMatchById(id);
      walletStore.removeCoins(match.stake, 'Match stake');

      if (authStore.currentUser.id === match.player1_user_id) {
        const response = await apiStore.postMatch({
          type: match.type,
          player1_user_id: match.player1_user_id,
          player2_user_id: match.player2_user_id,
          winner_user_id: null,
          loser_user_id: null,
          status: match.status,
          stake: match.stake,
          began_at: match.began_at,
          ended_at: null,
          total_time: null,
          player1_marks: 0,
          player2_marks: 0,
          player1_points: 0,
          player2_points: 0
        });

        matchStore.setDBId(id, response.data.id);
      }

      router.push(`/match/${id}`);
    });

    socket.on('match-change', async (match) => {
      if (authStore.currentUser.id === match.player1_user_id && match.games && match.games.length > 0) {
        const dbMatchId = matchStore.getDBId(match.id);

        if (dbMatchId) {
          for (let index = 0; index < match.games.length; index++) {
            const game = match.games[index];
            if (!game) continue;

            let dbGameId = matchStore.getGameDBId(match.id, index);

            if (!dbGameId) {
              const response = await apiStore.postGame({
                type: match.type,
                player1_user_id: game.player1_user_id,
                player2_user_id: game.player2_user_id,
                is_draw: game.is_draw,
                winner_user_id: game.winner_user_id,
                loser_user_id: game.loser_user_id,
                match_id: dbMatchId,
                status: game.status,
                began_at: game.began_at,
                ended_at: game.ended_at,
                total_time: game.total_time,
                player1_points: game.player1_points,
                player2_points: game.player2_points
              });

              dbGameId = response.data.id;
              matchStore.setGameDBId(match.id, index, dbGameId);
            }

            if (dbGameId && game.status === 'Ended') {
              await apiStore.updateGame(dbGameId, {
                is_draw: game.is_draw,
                winner_user_id: game.winner_user_id,
                loser_user_id: game.loser_user_id,
                status: game.status,
                ended_at: game.ended_at,
                total_time: game.total_time,
                player1_points: game.player1_points,
                player2_points: game.player2_points
              });
            }
          }
        }
      }
      
      if (match.status === 'Ended' || match.status === 'Interrupted') {
        if ((authStore.currentUser.id === match.winner_user_id) ||
            match.status === 'Interrupted') {
          walletStore.addCoins(match.stake * 2, 'Match payout');
        }

        //if (authStore.currentUser.id === match.player1_user_id) {
          const dbId = matchStore.getDBId(match.id);
          console.log(dbId);
          console.log(match);
          apiStore.updateMatch(dbId, {
            winner_user_id: match.winner_user_id,
            loser_user_id: match.loser_user_id,
            status: match.status,
            ended_at: match.ended_at,
            total_time: match.total_time,
            player1_marks: match.player1_marks,
            player2_marks: match.player2_marks,
            player1_points: match.player1_points,
            player2_points: match.player2_points
          });
        //}
      }

      matchStore.updateMatch(match);
    });
  }

  const emitJoin = (user) => {
    if (joined.value) {
      return
    }

    console.log(`[Socket] Joining Server`)
    socket.emit('join', user)
    joined.value = true
  }

  const emitLeave = () => {
    socket.emit('leave')
    console.log(`[Socket] Leaving Server`)
    joined.value = false
  }

  const handleConnection = () => {
    socket.on('connect', () => {
      console.log(`[Socket] Connected -- ${socket.id}`)
      if (authStore.isAuthenticated) {
        emitJoin(authStore.currentUser)
      }
    })

    socket.on('disconnect', () => {
      console.log(`[Socket] Disconnected -- ${socket.id}`)
    })
  }

  const emitGetMatches = () => {
    socket.emit('get-matches')
  }

  const emitPlayCard = (id, card) => {
    socket.emit('play-card', { id, card })
  }

  return {
    handleConnection,
    emitJoin,
    emitLeave,
    emitGetMatches,
    handleMatchEvents,
    emitPlayCard,
  }
})
