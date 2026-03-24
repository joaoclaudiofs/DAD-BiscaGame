import { defineStore } from 'pinia'
import axios from 'axios'
import { inject, ref } from 'vue'
export const useAPIStore = defineStore('api', () => {
  const API_BASE_URL = inject('apiBaseURL')

  // initialize token from localStorage
  const token = ref(localStorage.getItem('auth_token') || undefined)

  if (token.value) {
    axios.defaults.headers.common['Authorization'] = `Bearer ${token.value}`
  }

  const postGame = (game) => {
    return axios.post(`${API_BASE_URL}/games`, game)
  }
  const updateGame = (id, game) => {
    return axios.put(`${API_BASE_URL}/games/${id}`, game)
  }
  const getGames = () => {
    return axios.get(`${API_BASE_URL}/games`)
  }
  const getMatchGames = (matchId) => {
    return axios.get(`${API_BASE_URL}/matches/${matchId}/games`)
  }

  const postMatch = (match) => {
    return axios.post(`${API_BASE_URL}/matches`, match)
  }
  const updateMatch = (id, match) => {
    return axios.put(`${API_BASE_URL}/matches/${id}`, match)
  }

  const getCustomizations = (type) => {
    return axios.get(`${API_BASE_URL}/customizations/${type}`)
  }
  const getOwnedCustomizations = () => {
    return axios.get(`${API_BASE_URL}/customizations/owned`)
  }
  const getCustomizationOwned = (customizationId) => {
    return axios.get(`${API_BASE_URL}/customizations/${customizationId}/owned`)
  }
  const equipCustomization = (customizationId) => {
    return axios.post(`${API_BASE_URL}/customizations/${customizationId}/equip`)
  }

  const purchaseCustomization = (customizationId) => {
    return axios.post(`${API_BASE_URL}/customizations/${customizationId}/purchase`)
  }
  const getEquippedDeck = () => {
    return axios.get(`${API_BASE_URL}/users/me/deck`)
  }

  // AUTH
  const postLogin = async (credentials) => {
    const response = await axios.post(`${API_BASE_URL}/login`, credentials)
    token.value = response.data.token
    localStorage.setItem('auth_token', token.value)
    axios.defaults.headers.common['Authorization'] = `Bearer ${token.value}`
  }

  const postRegister = async (userData) => {
    const formData = new FormData()
    for (const key in userData) {
      // Skip null/undefined values, especially for optional file uploads
      if (userData[key] === null || userData[key] === undefined) {
        continue
      }
      formData.append(key, userData[key])
      console.log(`Appending ${key}:`, userData[key])
    }
    const response = await axios.post(`${API_BASE_URL}/register`, formData, {
      headers: {
        'Content-Type': 'multipart/form-data',
      },
    })
    token.value = response.data.token
    localStorage.setItem('auth_token', token.value)
    axios.defaults.headers.common['Authorization'] = `Bearer ${token.value}`
  }

  const postLogout = async () => {
    await axios.post(`${API_BASE_URL}/logout`)
    token.value = undefined
    localStorage.removeItem('auth_token')
    localStorage.removeItem('current_user')
    delete axios.defaults.headers.common['Authorization']
  }
  // Users
  const getAuthUser = () => {
    return axios.get(`${API_BASE_URL}/users/me`)
  }

  const updateUser = async (userData) => {
    const formData = new FormData()
    for (const key in userData) {
      if (userData[key] === null || userData[key] === undefined) {
        continue
      }
      formData.append(key, userData[key])
    }
    return axios.post(`${API_BASE_URL}/users/me`, formData, {
      headers: {
        'Content-Type': 'multipart/form-data',
      },
    })
  }

  const updatePassword = (passwordData) => {
    return axios.put(`${API_BASE_URL}/users/me/password`, passwordData)
  }

  const deleteOwnAccount = (confirmationData) => {
    return axios.delete(`${API_BASE_URL}/users/me`, {
      data: confirmationData,
    })
  }

  // Coins
  const postAddCoins = (amount, type) => {
    return axios.post(`${API_BASE_URL}/users/coins/add`, { amount, type })
  }
  const postRemoveCoins = (amount, type) => {
    return axios.post(`${API_BASE_URL}/users/coins/remove`, { amount, type })
  }

  // Coin Purchases & History
  const postCoinPurchase = (payload) => {
    return axios.post(`${API_BASE_URL}/users/coins/purchase`, payload)
  }
  const getCoinsBalance = () => {
    return axios.get(`${API_BASE_URL}/users/me/coins`)
  }
  const getMyCoinTransactions = (
    page = 1,
    perPage = 20,
    beganAfter = null,
    endedBefore = null,
    type = null,
    typeCode = null,
  ) => {
    const params = { page, per_page: perPage }
    if (beganAfter) params.began_after = beganAfter
    if (endedBefore) params.ended_before = endedBefore
    if (type) params.type = type
    if (typeCode) params.type_code = typeCode
    return axios.get(`${API_BASE_URL}/users/me/coins/transactions`, { params })
  }
  const getAllCoinTransactions = ({
    userId = null,
    userName = null,
    type = null,
    typeCode = null,
    beganAfter = null,
    endedBefore = null,
    page = 1,
    perPage = 20,
  } = {}) => {
    const params = { page, per_page: perPage }
    if (userId) params.user_id = userId
    if (userName) params.user_name = userName
    if (type) params.type = type
    if (typeCode) params.type_code = typeCode
    if (beganAfter) params.began_after = beganAfter
    if (endedBefore) params.ended_before = endedBefore
    return axios.get(`${API_BASE_URL}/admin/coins/transactions`, { params })
  }

  // Stats
  const getUserStats = () => {
    return axios.get(`${API_BASE_URL}/users/me/stats`)
  }

  // Statistics
  const getPublicStatistics = () => {
    return axios.get(`${API_BASE_URL}/statistics/public`)
  }

  const getAdminStatistics = (options = {}) => {
    const {
      purchasesFrom = null,
      purchasesTo = null,
      matchesFrom = null,
      matchesTo = null,
    } = options
    const params = {}
    if (purchasesFrom) params.purchases_from = purchasesFrom
    if (purchasesTo) params.purchases_to = purchasesTo
    if (matchesFrom) params.matches_from = matchesFrom
    if (matchesTo) params.matches_to = matchesTo
    return axios.get(`${API_BASE_URL}/statistics/admin`, { params })
  }

  // Scoreboard
  const getScoreboard = (page = 1, perPage = 10, orderBy = 'wins', signal = null) => {
    return axios.get(`${API_BASE_URL}/scoreboard`, {
      params: { page, per_page: perPage, order_by: orderBy },
      signal,
    })
  }

  const getUserMatches = (
    page = 1,
    perPage = 15,
    playerName = null,
    capote = null,
    bandeira = null,
    minGames = null,
    beganAfter = null,
    endedBefore = null,
  ) => {
    const params = { page, per_page: perPage }
    if (playerName) params.player_name = playerName
    if (capote) params.capote = capote
    if (bandeira) params.bandeira = bandeira
    if (minGames) params.min_games = minGames
    if (beganAfter) params.began_after = beganAfter
    if (endedBefore) params.ended_before = endedBefore
    return axios.get(`${API_BASE_URL}/users/me/matches`, { params })
  }

  const getMatches = (
    page = 1,
    perPage = 15,
    userId = null,
    playerName = null,
    capote = null,
    bandeira = null,
    minGames = null,
    status = null,
    beganAfter = null,
    endedBefore = null,
  ) => {
    const params = { page, per_page: perPage }
    if (userId) params.user_id = userId
    if (playerName) params.player_name = playerName
    if (capote) params.capote = capote
    if (bandeira) params.bandeira = bandeira
    if (minGames) params.min_games = minGames
    if (status) params.status = status
    if (beganAfter) params.began_after = beganAfter
    if (endedBefore) params.ended_before = endedBefore
    return axios.get(`${API_BASE_URL}/matches`, { params })
  }

  const getAllUsers = () => {
    return axios.get(`${API_BASE_URL}/users`)
  }

  //admin - user Management
  const createAdminUser = (payload) => {
    return axios.post(`${API_BASE_URL}/users`, {
      ...payload,
      type: 'A',
    })
  }

  const updateUserBlock = (userId, blocked) => {
    return axios.put(`${API_BASE_URL}/users/${userId}`, { blocked })
  }

  const deleteUserByAdmin = (userId) => {
    return axios.delete(`${API_BASE_URL}/users/${userId}`)
  }

  // // Notifications
  // const getNotifications = () => {
  //   return axios.get(`${API_BASE_URL}/notifications`)
  // }

  // const getUnreadCount = () => {
  //   return axios.get(`${API_BASE_URL}/notifications/unread-count`)
  // }

  // const createNotification = (notification) => {
  //   return axios.post(`${API_BASE_URL}/notifications`, notification)
  // }

  // const markNotificationAsRead = (id) => {
  //   return axios.post(`${API_BASE_URL}/notifications/${id}/read`)
  // }

  // const markAllNotificationsAsRead = () => {
  //   return axios.post(`${API_BASE_URL}/notifications/read-all`)
  // }

  // const dismissNotification = (id) => {
  //   return axios.post(`${API_BASE_URL}/notifications/${id}/dismiss`)
  // }

  // const clearAllNotifications = () => {
  //   return axios.delete(`${API_BASE_URL}/notifications/clear`)
  // }

  const getTransactionTypes = () => {
    return axios.get(`${API_BASE_URL}/coins/transaction-types`)
  }

  return {
    postGame,
    updateGame,
    getGames,
    postLogin,
    postRegister,
    postLogout,
    getAuthUser,
    updateUser,
    updatePassword,
    deleteOwnAccount,
    postMatch,
    updateMatch,
    postAddCoins,
    postRemoveCoins,
    postCoinPurchase,
    getCoinsBalance,
    getMyCoinTransactions,
    getAllCoinTransactions,
    getCustomizations,
    getCustomizationOwned,
    equipCustomization,
    getUserStats,
    getPublicStatistics,
    getAdminStatistics,
    purchaseCustomization,
    getEquippedDeck,
    getScoreboard,
    getUserMatches,
    getMatchGames,
    getOwnedCustomizations,
    getMatches,
    getAllUsers,
    createAdminUser,
    updateUserBlock,
    deleteUserByAdmin,
    getTransactionTypes,
  }
})
