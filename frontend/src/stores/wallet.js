import { defineStore } from 'pinia'
import { ref } from 'vue'
import { useAPIStore } from './api'

export const useWalletStore = defineStore('wallet', () => {
  const api = useAPIStore()
  const balance = ref(0)
  const transactions = ref([])
  const playerFilterBeganAfter = ref(null)
  const playerFilterEndedBefore = ref(null)
  const playerFilterType = ref(null)
  const playerFilterTypeCode = ref(null)
  const adminFilterUserId = ref(null)
  const adminFilterUserName = ref(null)
  const adminFilterType = ref(null)
  const adminFilterTypeCode = ref(null)
  const adminFilterBeganAfter = ref(null)
  const adminFilterEndedBefore = ref(null)
  const pagination = ref({ currentPage: 1, perPage: 20, total: 0, lastPage: 1 })
  const loading = ref(false)
  const error = ref(null)
  const lastPurchase = ref(null)

  const fetchBalance = async () => {
    error.value = null
    try {
      const { data } = await api.getCoinsBalance()
      balance.value = data?.coins_balance ?? 0
    } catch (e) {
      error.value = parseError(e)
    }
  }

  const fetchTransactions = async (page = 1, beganAfter = null, endedBefore = null, type = null, typeCode = null) => {
    error.value = null
    playerFilterBeganAfter.value = beganAfter
    playerFilterEndedBefore.value = endedBefore
    playerFilterType.value = type
    playerFilterTypeCode.value = typeCode
    try {
      const { data } = await api.getMyCoinTransactions(page, pagination.value.perPage, beganAfter, endedBefore, type, typeCode)
      transactions.value = Array.isArray(data?.items) ? data.items : []
      pagination.value = {
        currentPage: data?.pagination?.current_page ?? page,
        perPage: data?.pagination?.per_page ?? pagination.value.perPage,
        total: data?.pagination?.total ?? transactions.value.length,
        lastPage: data?.pagination?.last_page ?? 1,
      }
    } catch (e) {
      error.value = parseError(e)
    }
  }

  const fetchAdminTransactions = async (userId = null, page = 1, userName = null, type = null, typeCode = null, beganAfter = null, endedBefore = null) => {
    error.value = null
    adminFilterUserId.value = userId
    adminFilterUserName.value = userName
    adminFilterType.value = type
    adminFilterTypeCode.value = typeCode
    adminFilterBeganAfter.value = beganAfter
    adminFilterEndedBefore.value = endedBefore
    try {
      const { data } = await api.getAllCoinTransactions({
        userId,
        userName,
        type,
        typeCode,
        beganAfter,
        endedBefore,
        page,
        perPage: pagination.value.perPage,
      })
      transactions.value = Array.isArray(data?.items) ? data.items : []
      pagination.value = {
        currentPage: data?.pagination?.current_page ?? page,
        perPage: data?.pagination?.per_page ?? pagination.value.perPage,
        total: data?.pagination?.total ?? transactions.value.length,
        lastPage: data?.pagination?.last_page ?? 1,
      }
    } catch (e) {
      error.value = parseError(e)
    }
  }

  const purchaseCoins = async ({ type, reference, value }) => {
    loading.value = true
    error.value = null
    lastPurchase.value = null
    try {
      const { data, status } = await api.postCoinPurchase({ type, reference, value })
      if (status === 201) {
        lastPurchase.value = {
          coinsCredited: data?.coins_credited ?? value * 10,
          newBalance: data?.coins_balance,
        }
        balance.value = data?.coins_balance ?? balance.value
        await fetchTransactions()
        return { ok: true, data }
      }
      return { ok: false, data }
    } catch (e) {
      error.value = parseError(e)
      return { ok: false, error: error.value }
    } finally {
      loading.value = false
    }
  }

  const addCoins = async (amount, type) => {
    loading.value = true
    error.value = null
    try {
      const { data } = await api.postAddCoins(amount, type)
      balance.value = data?.coins_balance ?? balance.value
      await fetchTransactions()
      return { ok: true, data }
    } catch (e) {
      error.value = parseError(e)
      return { ok: false, error: error.value }
    } finally {
      loading.value = false
    }
  }

  const removeCoins = async (amount, type) => {
    loading.value = true
    error.value = null
    try {
      const { data } = await api.postRemoveCoins(amount, type)
      balance.value = data?.coins_balance ?? balance.value
      await fetchTransactions()
      return { ok: true, data }
    } catch (e) {
      error.value = parseError(e)
      return { ok: false, error: error.value }
    } finally {
      loading.value = false
    }
  }

  const parseError = (e) => {
    const message = e?.response?.data?.message || e?.message || 'Unexpected error'
    const details = e?.response?.data?.details
    return { message, details }
  }

  return {
    balance,
    transactions,
    playerFilterBeganAfter,
    playerFilterEndedBefore,
    playerFilterType,
    playerFilterTypeCode,
    adminFilterUserId,
    adminFilterUserName,
    adminFilterType,
    adminFilterTypeCode,
    adminFilterBeganAfter,
    adminFilterEndedBefore,
    pagination,
    loading,
    error,
    lastPurchase,
    fetchBalance,
    fetchTransactions,
    fetchAdminTransactions,
    purchaseCoins,
    addCoins,
    removeCoins,
  }
})
