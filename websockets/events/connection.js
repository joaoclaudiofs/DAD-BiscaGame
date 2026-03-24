import { addUser, removeUser, getUserCount } from "../state/connection.js";
import { interruptMatch, getMatches } from "../state/match.js";

export const handleConnectionEvents = (io, socket) => {
	socket.on('join', (user) => {
		addUser(socket, user);

		console.log(`[Connection] User ${user.name} has joined the server`);
        console.log(`[Connection] ${getUserCount()} users online`);

		io.emit("user-joined", {
			socketId: socket.id,
			userId: user.id,
			userName: user.name
		});
	});

	socket.on('leave', () => {
		const user = removeUser(socket.id);
		if (user && user.id) {
			const match = interruptMatch(user.id);
			if (match) {
				io.to(`match-${match.id}`).emit("match-change", match);
			}
			io.emit("matches", getMatches());
		}

		console.log(`[Connection] User ${user.name} has left the server`);
		console.log(`[Connection] ${getUserCount()} users online`);
	});

	socket.on('disconnect', () => {
		const user = removeUser(socket.id);
		if (user && user.id) {
			const match = interruptMatch(user.id);
			if (match) {
				io.to(`match-${match.id}`).emit("match-change", match);
			}
			io.emit("matches", getMatches());
		}
		
        console.log(`Lost connection: ${socket.id}`);
    })
};
