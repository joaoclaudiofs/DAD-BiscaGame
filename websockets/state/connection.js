const users = new Map();

export const addUser = (socket, user) => {
    users.set(socket.id, user);
}

export const removeUser = (socketId) => {
    const userToRemove = { ...users.get(socketId) };
    users.delete(socketId);
    return userToRemove;
}

export const getUser = (socketId) => {
    return users.get(socketId);
}

export const getUserById = (id) => {
    for (const [socket, user] of users.entries()) {
        if (user.id === id) {
            return {
                user,
                socket
            }
        }
    }

    return null;
}

export const getUserCount = () => {
    return users.size;
}