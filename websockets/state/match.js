const matches = new Map();
let matchId = 0;

//naipes e valores
const suits = ['hearts', 'diamonds', 'clubs', 'spades'];
const values = ['2', '3', '4', '5', '6', 'Q', 'J', 'K', '7', 'A'];

export const getMatches = () => {
    //return matches.values().toArray();
    return Array.from(matches.values());
}

/*export const interruptMatchesForUser = (userId) => {
    const interrupted = [];

    for (const match of matches.values()) {
        if (match.status !== 'Ended' && match.status !== 'Interrupted' && (match.player1_user_id === userId || match.player2_user_id === userId)) {
            match.status = 'Interrupted';
            match.ended_at = new Date();
            match.total_time = Math.round((match.ended_at - match.began_at) / 1000);
            interrupted.push(match);
        }
    }

    return interrupted;
}*/

export const interruptMatch = (userId) => {
    for (const match of matches.values()) {
        if (match.status !== 'Ended' && match.status !== 'Interrupted' && (match.player1_user_id === userId || match.player2_user_id === userId)) {
            match.status = 'Interrupted';
            match.ended_at = new Date();
            match.total_time = Math.round((match.ended_at - match.began_at) / 1000);
            return match;
        }
    }

    return null;
}

export const playCard = (matchId, userId, card, io) => {
    const match = matches.get(matchId);
    if (!match) return;

    if (match.turn !== userId) return;
    
    //bloquear jogadas durante o processamento da rodada
    if (match.processingRound) return;

    //obrigatorio assistir quando o baralho esta vazio
    if (match.deck.length === 0 && match.board.length === 1) {
        const firstCard = match.board[0];
        if (firstCard.card.suit !== card.suit) {
            //ver se tem alguma carta do mesmo naipe
            let hasSuit = false;

            if (match.player1_user_id === userId) {
                for (const c of match.player1_hand) {
                    if (c.suit === firstCard.card.suit) {
                        hasSuit = true;
                        break;
                    }
                }
            } else if (match.player2_user_id === userId) {
                for (const c of match.player2_hand) {
                    if (c.suit === firstCard.card.suit) {
                        hasSuit = true;
                        break;
                    }
                }
            }

            if (hasSuit) {
                return;
            }
        }
    }

    //remover a carta de quem a jogou
    let playedCard = null;
    if (match.player1_user_id === userId) {
        const index = match.player1_hand.findIndex((c) => c.suit === card.suit && c.value === card.value);
        playedCard = match.player1_hand.splice(index, 1)[0];

        match.turn = match.player2_user_id;
    } else if (match.player2_user_id === userId) {
        const index = match.player2_hand.findIndex((c) => c.suit === card.suit && c.value === card.value);
        playedCard = match.player2_hand.splice(index, 1)[0];

        match.turn = match.player1_user_id;
    } else {
        return;
    }

    match.board.push({ card: playedCard, player_id: userId });

    if (match.board.length != 2) {
        return;
    }

    //bloquear novas jogadas durante o processamento
    match.processingRound = true;

    //ver se quem venceu
    let firstCard = match.board[0];
    let lastCard = match.board[1];
    let winnerId = firstCard.player_id;
    //se for do mesmo naipe
    if (firstCard.card.suit === lastCard.card.suit) {
        //a maior carta ganha
        if (values.indexOf(lastCard.card.value) > values.indexOf(firstCard.card.value)) {
            //ultima carta ganhou
            winnerId = lastCard.player_id;
        }
    }
    //se o naipe for diferente
    else {
        //se a ultima for trunfo
        if (lastCard.card.suit === match.trump.suit) {
            //ultima carta ganhou
            winnerId = lastCard.player_id;
        }
    }

    //quem ganhou tira cartas primeiro e ganha pontos
    const gameIndex = match.games.length - 1;
    const points = calculatePoints([firstCard.card, lastCard.card]);
    if (match.player1_user_id === winnerId) {
        if (match.deck.length > 0) {
            match.player1_hand.push(match.deck.shift());
            match.player2_hand.push(match.deck.shift());
        }

        match.games[gameIndex].player1_points += points;
    } else if (match.player2_user_id === winnerId) {
        if (match.deck.length > 0) {
            match.player2_hand.push(match.deck.shift());
            match.player1_hand.push(match.deck.shift());
        }

        match.games[gameIndex].player2_points += points;
    }

    match.turn = winnerId;
    
    //delay de 1s para mostrar as cartas antes de limpar a mesa
    setTimeout(() => {
        match.board = [];
        match.processingRound = false;
        
        //emitir o estado atualizado para os clientes
        if (io) {
            io.to(`match-${matchId}`).emit("match-change", match);
        }
    }, 1000);

    //game acabou
    if (match.player1_hand.length === 0 && match.player2_hand.length === 0) {
        match.games[gameIndex].status = 'Ended';
        match.games[gameIndex].ended_at = new Date();
        match.games[gameIndex].total_time = Math.round((match.games[gameIndex].ended_at - match.games[gameIndex].began_at) / 1000);
        
        match.player1_points += match.games[gameIndex].player1_points;
        match.player2_points += match.games[gameIndex].player2_points;

        //empate 0 pontos
        if (match.games[gameIndex].player1_points === match.games[gameIndex].player2_points) {
            match.games[gameIndex].is_draw = true;
        }
        //capote 2 pontos
        else if (match.games[gameIndex].player1_points >= 91 && match.games[gameIndex].player1_points <= 119) {
            match.games[gameIndex].winner_user_id = match.player2_user_id;
            match.games[gameIndex].loser_user_id = match.player1_user_id;

            match.player1_marks += 2;
        }
        else if (match.games[gameIndex].player2_points >= 91 && match.games[gameIndex].player2_points <= 119) {
            match.games[gameIndex].winner_user_id = match.player1_user_id;
            match.games[gameIndex].loser_user_id = match.player2_user_id;

            match.player1_marks += 2;
        }
        //bandeira 4 pontos
        else if (match.games[gameIndex].player1_points === 120) {
            match.games[gameIndex].winner_user_id = match.player1_user_id;
            match.games[gameIndex].loser_user_id = match.player2_user_id;

            match.player1_marks += 4;
        }
        else if (match.games[gameIndex].player2_points === 120) {
            match.games[gameIndex].winner_user_id = match.player2_user_id;
            match.games[gameIndex].loser_user_id = match.player1_user_id;

            match.player2_marks += 4;
        }
        //vitoria normal 1 ponto
        else if (match.games[gameIndex].player1_points > match.games[gameIndex].player2_points) {
            match.games[gameIndex].winner_user_id = match.player1_user_id;
            match.games[gameIndex].loser_user_id = match.player2_user_id;

            match.player1_marks += 1;
        } else {
            match.games[gameIndex].winner_user_id = match.player2_user_id;
            match.games[gameIndex].loser_user_id = match.player1_user_id;

            match.player2_marks += 1;
        }

        //match acabou
        if (match.player1_marks >= 4 || match.player2_marks >= 4) {
            match.status = 'Ended';
            match.ended_at = new Date();
            match.total_time = Math.round((match.ended_at - match.began_at) / 1000);
            match.winner_user_id = match.player1_marks > match.player2_marks ? match.player1_user_id : match.player2_user_id;
            match.loser_user_id = match.player1_marks < match.player2_marks ? match.player1_user_id : match.player2_user_id;
        } else {
            startGame(matchId);
        }
    }
}

export const calculatePoints = (cards) => {
    const points = [0, 0, 0, 0, 0, 2, 3, 4, 10, 11];

    let total = 0;
    for (const card of cards) {
        total += points[values.indexOf(card.value)];
    }
    return total;
}

export const joinMatch = (id, user) => {
    const match = matches.get(id);
    if (!match) return;

    match.player2_user_id = user.id;
    match.began_at = new Date();
    match.status = 'Playing';

    match.games = [];
    startGame(id);
}

export const startGame = (id) => {
    const match = matches.get(id);
    if (!match) return;

    match.games.push({
        player1_user_id: match.player1_user_id,
        player2_user_id: match.player2_user_id,
        is_draw: false,
        winner_user_id: null,
        loser_user_id: null,
        match_id: match.id,
        status: 'Playing',
        began_at: new Date(),
        ended_at: null,
        total_time: null,
        player1_points: 0,
        player2_points: 0
    });

    match.deck = generateDeck();
    match.board = [];

    match.player1_hand = match.deck.splice(0, match.type);
    match.player2_hand = match.deck.splice(0, match.type);
    match.trump = { ...match.deck[match.deck.length - 1] }

    //50/50 de quem começa
    match.turn = Math.random() < 0.5 ? match.player1_user_id : match.player2_user_id;
}

export const cancelMatch = (id) => {
    matches.delete(id);
}

export const forfeitMatch = (matchId, userId) => {
    const match = matches.get(matchId);
    if (!match) return null;

    if (match.status === 'Ended' || match.status === 'Interrupted') {
        return null;
    }

    const opponentId = match.player1_user_id === userId ? match.player2_user_id : match.player1_user_id;
    if (!opponentId) {
        return null;
    }

    match.status = 'Ended';
    match.winner_user_id = opponentId;
    match.loser_user_id = userId;
    match.ended_at = new Date();
    match.total_time = Math.round((match.ended_at - match.began_at) / 1000);

    return match;
}

export const proposeStake = (matchId, userId, proposedStake) => {
    const match = matches.get(matchId);
    if (!match || match.status !== 'Waiting') return null;
    
    match.stakeProposal = {
        proposedBy: userId,
        amount: proposedStake,
        timestamp: new Date()
    };
    
    return match;
}

export const respondStakeProposal = (matchId, accept) => {
    const match = matches.get(matchId);
    if (!match || !match.stakeProposal) return null;
    
    if (accept) {
        match.stake = match.stakeProposal.amount;
    }
    
    match.stakeProposal = null;
    return match;
}

export const createMatch = (host, type, stake) => {
    matchId++;

    const match = {
        id: matchId,
        type,
        player1_user_id: host.id,
        player2_user_id: null,
        winner_user_id: null,
        loser_user_id: null,
        status: 'Waiting',
        stake,
        stakeProposal: null,
        began_at: null,
        ended_at: null,
        total_time: null,
        player1_marks: 0,
        player2_marks: 0,
        player1_points: 0,
        player2_points: 0,
        custom: null,
        processingRound: false
    }

    matches.set(matchId, match);
    return match;
}

const generateDeck = () => {
    //criar o deck
    const deck = [];
    for (const suit of suits) {
        for (const value of values) {
            deck.push({ suit, value });
        }
    }

    //baralhar o deck
    for (let i = 0; i < deck.length; i++) {
        const j = Math.floor(Math.random() * deck.length);

        const temp = deck[i];
        deck[i] = deck[j];
        deck[j] = temp;
    }

    return deck;
}
