import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { useAPIStore } from './api'
import { useSocketStore } from './socket'
import { useWalletStore } from './wallet'

export const useAuthStore = defineStore('auth', () => {
  const apiStore = useAPIStore()
  const socketStore = useSocketStore()

  // initialize currentUser from localStorage
  const storedUser = localStorage.getItem('current_user')
  const currentUser = ref(storedUser ? JSON.parse(storedUser) : undefined)
  const isAnonymousMode = ref(false)

  const isLoggedIn = computed(() => {
    return currentUser.value !== undefined && !isAnonymousMode.value
  })

  const isAnonymous = computed(() => {
    return isAnonymousMode.value
  })

  const isAdmin = computed(() => (currentUser.value?.type ?? 'P') === 'A')
  const isPlayer = computed(() => (currentUser.value?.type ?? 'P') === 'P')

  const currentUserID = computed(() => {
    return currentUser.value?.id
  })

  // Access control computeds
  const canAccessStore = computed(() => {
    return isLoggedIn.value
  })

  const canAccessLeaderboard = computed(() => {
    return true // Leaderboard is visible to everyone (including anonymous)
  })

  const canAccessHistory = computed(() => {
    return isLoggedIn.value
  })

  const canOnlyPractice = computed(() => {
    return isAnonymousMode.value
  })

  const login = async (credentials) => {
    await apiStore.postLogin(credentials)
    const response = await apiStore.getAuthUser()
    currentUser.value = response.data
    localStorage.setItem('current_user', JSON.stringify(response.data))
    isAnonymousMode.value = false
    socketStore.emitJoin(currentUser.value)
    return response.data
  }

  const register = async (userData) => {
    await apiStore.postRegister(userData)
    const response = await apiStore.getAuthUser()
    currentUser.value = response.data
    localStorage.setItem('current_user', JSON.stringify(response.data))
    isAnonymousMode.value = false

    const walletStore = useWalletStore();
    walletStore.addCoins(10, 'Bonus')

    return response.data
  }

  const loginAsGuest = () => {
    currentUser.value = {
      name: 'Guest',
      type: 'G',
      coins_balance: 0,
    }
    isAnonymousMode.value = true
  }

  const logout = async () => {
    if (!isAnonymousMode.value) {
      await apiStore.postLogout()
    }
    currentUser.value = undefined
    isAnonymousMode.value = false
    socketStore.emitLeave()
  }

  const addCoins = async (amount) => {
    if (!isLoggedIn.value) return
    await apiStore.postAddCoins(amount)
    const response = await apiStore.getAuthUser()
    currentUser.value = response.data
  }

  const removeCoins = async (amount) => {
    if (!isLoggedIn.value) return
    await apiStore.postRemoveCoins(amount)
    const response = await apiStore.getAuthUser()
    currentUser.value = response.data
  }

  const restoreSession = async () => {
    if (currentUser.value && localStorage.getItem('auth_token')) {
      try {
        // verify token is still valid by fetching user data
        const response = await apiStore.getAuthUser()
        currentUser.value = response.data
        localStorage.setItem('current_user', JSON.stringify(response.data))
        socketStore.emitJoin(currentUser.value)
      } catch {
        // token is invalid, clear session
        currentUser.value = undefined
        localStorage.removeItem('current_user')
        localStorage.removeItem('auth_token')
        socketStore.emitLeave()
      }
    }
  }

  const updateProfile = async (userData) => {
    const response = await apiStore.updateUser(userData)
    currentUser.value = response.data
    localStorage.setItem('current_user', JSON.stringify(response.data))
    return response.data
  }

  const updatePassword = async (passwordData) => {
    return await apiStore.updatePassword(passwordData)
  }

  const deleteAccount = async (confirmationData) => {
    await apiStore.deleteOwnAccount(confirmationData)
    currentUser.value = undefined
    socketStore.emitLeave()
    localStorage.removeItem('current_user')
    localStorage.removeItem('auth_token')
  }

  return {
    currentUser,
    isLoggedIn,
    isAnonymous,
    currentUserID,
    isAdmin,
    isPlayer,
    canAccessStore,
    canAccessLeaderboard,
    canAccessHistory,
    canOnlyPractice,
    login,
    loginAsGuest,
    logout,
    addCoins,
    removeCoins,
    register,
    restoreSession,
    updateProfile,
    updatePassword,
    deleteAccount,
  }
})
