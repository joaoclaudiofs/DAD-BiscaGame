<template>
  <div class="min-h-screen bg-gradient-to-br from-slate-50 to-blue-50 dark:from-slate-900 dark:to-slate-800">
    <!-- Header -->
    <div class="sticky top-0 z-10 bg-white/80 dark:bg-slate-900/80 backdrop-blur-sm border-b border-slate-200/70 dark:border-slate-700/70">
      <div class="flex items-center justify-between p-4">
        <button @click="goBack"
          class="inline-flex items-center justify-center h-10 w-10 rounded-lg hover:bg-slate-200 dark:hover:bg-slate-700 transition-colors text-slate-700 dark:text-slate-300">
          <ArrowLeft class="h-5 w-5" />
        </button>

        <h1 class="text-lg font-semibold">Adminstração de Utilizadores</h1>
        <div class="w-10" />

      </div>
    </div>

    <section class="p-4 space-y-6 pb-8 max-w-6xl mx-auto">
      <div v-if="!isAdmin" class="p-4 rounded-lg bg-red-500/10 border border-red-500/30 text-red-400">
        Só os administradores podem entrar nesta página.
      </div>

      <div v-else class="space-y-6">
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg p-4 shadow-sm">
          <div class="flex items-center justify-between">
            <h2 class="text-lg font-semibold text-slate-800 dark:text-slate-100">Utilizadores</h2>
            <button
              class="px-3 py-2 rounded-md bg-blue-600 text-white hover:bg-blue-700 transition-colors disabled:opacity-50 text-sm"
              @click="showCreate = !showCreate" :disabled="loading">
              {{ showCreate ? 'Fechar' : 'Criar Admin' }}
            </button>
          </div>

          <div v-if="showCreate" class="mt-4 rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 p-4 space-y-3">
            <h3 class="font-medium text-slate-900 dark:text-slate-100">Criar conta de administrador</h3>
            <form @submit.prevent="submitCreateAdmin" class="grid grid-cols-1 md:grid-cols-4 gap-3 items-end">
              <div>
                <label class="block text-sm text-slate-600 dark:text-slate-400 mb-1">Nome</label>
                <input v-model="createForm.name"
                  class="w-full border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                  placeholder="Nome completo" />
              </div>
              <div>
                <label class="block text-sm text-slate-600 dark:text-slate-400 mb-1">Email</label>
                <input v-model="createForm.email" type="email"
                  class="w-full border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                  placeholder="email@example.com" />
              </div>
              <div>
                <label class="block text-sm text-slate-600 dark:text-slate-400 mb-1">Password</label>
                <input v-model="createForm.password" type="password"
                  class="w-full border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                  placeholder="Password" />
              </div>
              <div class="md:col-span-4">
                <button
                  class="px-4 py-2 rounded-md bg-blue-600 text-white hover:bg-blue-700 transition-colors disabled:opacity-50"
                  :disabled="loading">Criar</button>
              </div>
            </form>
          </div>
        </div>

        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg p-4 shadow-sm">
          <AdminUserTable :items="pagedUsers" :loading="loading" :error="error" :current-user-id="currentUser?.id ?? null"
            :page="pagination.currentPage" :per-page="pagination.perPage" :total="filtered.length" :last-page="lastPage"
            :filters="filters" :sort-by="sort.sortBy" :sort-dir="sort.sortDir" @apply-filters="onApplyFilters"
            @reset-filters="onResetFilters" @page-change="onPageChange" @sort-change="onSort" @toggle-block="toggleBlock"
            @delete-user="removeUser" />
        </div>
      </div>
    </section>
  </div>
</template>

<script setup>
import { onMounted, reactive, computed, ref, inject } from 'vue'
import { useRouter } from 'vue-router'
import AdminUserTable from '@/components/admin/AdminUserTable.vue'
import axios from 'axios'
import { toast } from 'vue-sonner'
import { useAPIStore } from '@/stores/api'
import { useAuthStore } from '@/stores/auth'
import { ArrowLeft } from 'lucide-vue-next'

const router = useRouter()
const api = useAPIStore()
const auth = useAuthStore()
const { currentUser } = auth
const API_BASE_URL = inject('apiBaseURL')

const loading = ref(false)
const error = ref(null)

const users = ref([])
const filters = reactive({ search: '', role: '', blocked: '' })
const sort = reactive({ sortBy: '', sortDir: 'asc' })
const pagination = reactive({ currentPage: 1, perPage: 10 })

const showCreate = ref(false)
const createForm = reactive({ name: '', email: '', password: '' })

const goBack = () => router.back()

const isAdmin = computed(() => (currentUser?.type ?? 'P') === 'A')

const filtered = computed(() => {
  let list = users.value.slice()
  if (filters.search) {
    const q = filters.search.toLowerCase()
    list = list.filter(u => (u.name || '').toLowerCase().includes(q)
      || (u.nickname || '').toLowerCase().includes(q)
      || (u.email || '').toLowerCase().includes(q))
  }
  if (filters.role) {
    list = list.filter(u => (u.type ?? 'P') === filters.role)
  }
  if (filters.blocked !== '') {
    const b = Number(filters.blocked)
    list = list.filter(u => Number(u.blocked ?? 0) === b)
  }

  // Apply sorting
  if (sort.sortBy) {
    list.sort((a, b) => {
      let aVal = a[sort.sortBy]
      let bVal = b[sort.sortBy]

      // Handle null/undefined
      if (aVal == null) aVal = ''
      if (bVal == null) bVal = ''

      // Numeric comparison
      if (typeof aVal === 'number' && typeof bVal === 'number') {
        return sort.sortDir === 'asc' ? aVal - bVal : bVal - aVal
      }

      // String comparison
      aVal = String(aVal).toLowerCase()
      bVal = String(bVal).toLowerCase()
      return sort.sortDir === 'asc' ? aVal.localeCompare(bVal) : bVal.localeCompare(aVal)
    })
  }

  return list
})

const lastPage = computed(() => Math.max(1, Math.ceil(filtered.value.length / pagination.perPage)))
const pagedUsers = computed(() => {
  const start = (pagination.currentPage - 1) * pagination.perPage
  return filtered.value.slice(start, start + pagination.perPage)
})

function goToPage(page) {
  pagination.currentPage = Math.max(1, Math.min(page, lastPage.value))
}
function onApplyFilters(payload) {
  filters.search = payload?.search ?? ''
  filters.role = payload?.role ?? ''
  filters.blocked = payload?.blocked ?? ''
  pagination.currentPage = 1
}
function onResetFilters() {
  filters.search = ''
  filters.role = ''
  filters.blocked = ''
  pagination.currentPage = 1
}
function onPageChange(nextPage) {
  goToPage(nextPage)
}
function onSort(payload) {
  sort.sortBy = payload?.sortBy ?? ''
  sort.sortDir = payload?.sortDir ?? 'asc'
}

async function reloadUsers() {
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

async function submitCreateAdmin() {
  loading.value = true
  try {
    const payload = { ...createForm }
    const res = await axios.post(`${API_BASE_URL}/admin/users`, payload)
    if (res.status === 201) {
      toast.success('Administrador criado com sucesso!')
      showCreate.value = false
      createForm.name = ''
      createForm.email = ''
      createForm.password = ''
      await reloadUsers()
    } else {
      toast.error('Não foi possível criar o administrador.')
    }
  } catch (e) {
    const errorMsg = e?.response?.data?.message || e?.message || 'Não foi possível criar o administrador.'
    toast.error(errorMsg)
  } finally {
    loading.value = false
  }
}

async function toggleBlock(u) {
  loading.value = true
  error.value = null
  try {
    const isBlocked = Number(u.blocked ?? 0) === 1
    const endpoint = isBlocked ? 'unblock' : 'block'
    const res = await axios.post(`${API_BASE_URL}/admin/users/${u.id}/${endpoint}`)
    if (res.status >= 200 && res.status < 300) {
      u.blocked = isBlocked ? 0 : 1
      toast.success(isBlocked ? 'O user foi desbloqueado com sucesso.' : 'O user foi bloqueado com sucesso.')
    } else {
      throw new Error('Falha ao atualizar o estado de bloqueado/desbloqueado.')
    }
  } catch (e) {
    const errorMsg = e?.response?.data?.message || e?.message || 'Falha ao atualizar o estado para bloqueado/desbloqueado.'
    toast.error(errorMsg)
  } finally {
    loading.value = false
  }
}

async function removeUser(u) {
  if (u.id === currentUser?.id) {
    toast.error('Os administradores não podem remover a sua própria conta!')
    return
  }
  loading.value = true
  error.value = null
  try {
    const res = await axios.delete(`${API_BASE_URL}/admin/users/${u.id}`)
    if (res.status >= 200 && res.status < 300) {
      users.value = users.value.filter(x => x.id !== u.id)
      pagination.currentPage = 1
      toast.success('Utilizador removido com sucesso.')
    } else {
      throw new Error('Erro ao remover o utilizador.')
    }
  } catch (e) {
    const errorMsg = e?.response?.data?.message || e?.message || 'Erro ao remover o utilizador.'
    toast.error(errorMsg)
  } finally {
    loading.value = false
  }
}

onMounted(async () => {
  await reloadUsers()
})
</script>