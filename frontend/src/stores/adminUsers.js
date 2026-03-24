import { defineStore } from 'pinia'
import { ref, computed, reactive } from 'vue'
import { useAPIStore } from './api'

export const useAdminUsersStore = defineStore('adminUsers', () => {
  const api = useAPIStore()

  const users = ref([])
  const loading = ref(false)
  const error = ref(null)

  const filters = reactive({
    search: '',
    role: '',
    blocked: ''
  })

  const pagination = reactive({
    currentPage: 1,
    perPage: 10
  })

  const createForm = reactive({
    name: '',
    email: '',
    password: ''
  })

  const createLoading = ref(false)
  const createError = ref(null)
  const createSuccess = ref(false)

  // Computed: filtered users based on filters
  const filtered = computed(() => {
    let list = users.value.slice()

    if (filters.search) {
      const q = filters.search.toLowerCase()
      list = list.filter(u =>
        (u.name || '').toLowerCase().includes(q) ||
        (u.nickname || '').toLowerCase().includes(q) ||
        (u.email || '').toLowerCase().includes(q)
      )
    }

    if (filters.role) {
      list = list.filter(u => (u.type ?? 'P') === filters.role)
    }

    if (filters.blocked !== '') {
      const b = Number(filters.blocked)
      list = list.filter(u => Number(u.blocked ?? 0) === b)
    }

    return list
  })

  const lastPage = computed(() =>
    Math.max(1, Math.ceil(filtered.value.length / pagination.perPage))
  )

  const pagedUsers = computed(() => {
    const start = (pagination.currentPage - 1) * pagination.perPage
    return filtered.value.slice(start, start + pagination.perPage)
  })

  // Actions
  async function fetchUsers() {
    loading.value = true
    error.value = null
    try {
      const res = await api.getAllUsers()
      users.value = Array.isArray(res.data) ? res.data : []
    } catch (e) {
      error.value = e?.response?.data?.message || e?.message || 'Failed to load users'
    } finally {
      loading.value = false
    }
  }

  function applyFilters() {
    pagination.currentPage = 1
  }

  function resetFilters() {
    filters.search = ''
    filters.role = ''
    filters.blocked = ''
    pagination.currentPage = 1
  }

  function goToPage(page) {
    pagination.currentPage = Math.max(1, Math.min(page, lastPage.value))
  }

  async function createAdmin() {
    createLoading.value = true
    createError.value = null
    createSuccess.value = false

    try {
      const payload = {
        name: createForm.name,
        email: createForm.email,
        password: createForm.password,
        type: 'A',
        blocked: 0
      }

      const res = await api.createAdminUser(payload)

      if (res.status === 201) {
        createSuccess.value = true
        createForm.name = ''
        createForm.email = ''
        createForm.password = ''
        await fetchUsers()
      } else {
        throw new Error('Failed to create admin')
      }
    } catch (e) {
      createError.value = e?.response?.data?.message || e?.message || 'Failed to create admin'
    } finally {
      createLoading.value = false
    }
  }

  async function toggleBlock(user) {
    loading.value = true
    error.value = null

    try {
      const newBlocked = Number(user.blocked ?? 0) === 1 ? 0 : 1
      const res = await api.updateUserBlock(user.id, newBlocked)

      if (res.status >= 200 && res.status < 300) {
        user.blocked = newBlocked
      } else {
        throw new Error('Failed to update blocked state')
      }
    } catch (e) {
      error.value = e?.response?.data?.message || e?.message || 'Failed to update blocked state'
    } finally {
      loading.value = false
    }
  }

  async function removeUser(user) {
    loading.value = true
    error.value = null

    try {
      const res = await api.deleteUser(user.id)

      if (res.status >= 200 && res.status < 300) {
        users.value = users.value.filter(u => u.id !== user.id)
        applyFilters()
      } else {
        throw new Error('Failed to delete user')
      }
    } catch (e) {
      error.value = e?.response?.data?.message || e?.message || 'Failed to delete user'
    } finally {
      loading.value = false
    }
  }

  function resetCreateForm() {
    createForm.name = ''
    createForm.email = ''
    createForm.password = ''
    createError.value = null
    createSuccess.value = false
  }

  return {
    //state
    users,
    loading,
    error,
    filters,
    pagination,
    createForm,
    createLoading,
    createError,
    createSuccess,

    //computed
    filtered,
    lastPage,
    pagedUsers,

    //actions
    fetchUsers,
    applyFilters,
    resetFilters,
    goToPage,
    createAdmin,
    toggleBlock,
    removeUser,
    resetCreateForm
  }
})
