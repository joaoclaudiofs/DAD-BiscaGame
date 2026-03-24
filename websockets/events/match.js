import { getUser } from "../state/connection.js";
import { createMatch, getMatches, cancelMatch, joinMatch, playCard, proposeStake, respondStakeProposal, forfeitMatch } from "../state/match.js";

export const handleMatchEvents = (io, socket) => {
    socket.on("create-match", ({ type, stake }) => {
        const user = getUser(socket.id);
        if (!user) {
            console.log("[Match] Can't create match with no user");
            return;
        }

        const match = createMatch(user, type, stake);
        socket.join(`match-${match.id}`);   
        console.log(`[Match] ${user.name} created a new match - ID: ${match.id}`);

        io.emit("matches", getMatches());
    });

    socket.on("get-matches", () => {
        io.emit("matches", getMatches());
    });

    socket.on("cancel-match", (id) => {
        cancelMatch(id);
        io.emit("matches", getMatches());
    });

    socket.on("join-match", (id) => {
        const user = getUser(socket.id);
        joinMatch(id, user);
        console.log(`[Match] ${user.name} joined match - ID: ${id}`);

        socket.join(`match-${id}`);
        io.emit("matches", getMatches());

        io.to(`match-${id}`).emit("match-ready", id);
    });

    socket.on("play-card", ({ id, card }) => {
        const user = getUser(socket.id);
        playCard(id, user.id, card, io);
        console.log(`[Match] ${id} - ${user.name} played ${card.value} of ${card.suit}`);

        const match = getMatches().find((m) => m.id === id);
        io.to(`match-${id}`).emit("match-change", match);
    });

    socket.on("propose-stake", ({ matchId, proposedStake }) => {
        const user = getUser(socket.id);
        const match = proposeStake(matchId, user.id, proposedStake);
        
        if (match) {
            console.log(`[Match] ${user.name} proposed stake ${proposedStake} for match ${matchId}`);
            io.emit("matches", getMatches());
            io.to(`match-${matchId}`).emit("stake-proposal", { matchId, proposedBy: user.id, amount: proposedStake });
        }
    });

    socket.on("respond-stake-proposal", ({ matchId, accept }) => {
        const match = respondStakeProposal(matchId, accept);
        
        if (match) {
            console.log(`[Match] Stake proposal for match ${matchId} ${accept ? 'accepted' : 'rejected'}`);
            io.emit("matches", getMatches());
            io.to(`match-${matchId}`).emit("stake-proposal-response", { matchId, accept, newStake: match.stake });
        }
    });

    socket.on("forfeit", ({ matchId }) => {
        const user = getUser(socket.id);
        if (!user) return;

        const match = forfeitMatch(matchId, user.id);
        if (!match) return;

        console.log(`[Match] ${user.name} forfeited match ${matchId}`);
        io.to(`match-${matchId}`).emit("match-change", match);
        io.emit("matches", getMatches());
    });

    socket.on("send-emote", ({ matchId, emote }) => {
        const user = getUser(socket.id);
        
        if (user) {
            console.log(`[Match] ${user.name} sent emote "${emote}" in match ${matchId}`);
            socket.to(`match-${matchId}`).emit("receive-emote", { emote, from: user.id, name: user.name });
        }
    });
}   