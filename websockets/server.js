import { Server } from "socket.io";
import { handleConnectionEvents } from "./events/connection.js";
import { handleMatchEvents } from "./events/match.js";

export const server = {
  io: null,
};

export const serverStart = (port) => {
  server.io = new Server(port, {
    cors: {
      origin: "*",
    },
  });
  server.io.on("connection", (socket) => {
    console.log("New connection:", socket.id);

    /*socket.onAny((event, ...args) => {
		  console.log(`[Debug] Event '${event}' from ${socket.id}`, args);
	  });*/

    handleConnectionEvents(server.io, socket);
    handleMatchEvents(server.io, socket);
  });
};
