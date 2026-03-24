<template>
  <div class="min-h-screen bg-gradient-to-br from-slate-50 to-blue-50 dark:from-slate-900 dark:to-slate-800">
    <div class="max-w-7xl mx-auto px-6 py-8">
      <div class="mb-8 flex items-center justify-between">
        <div class="flex items-center gap-4">
          <button @click="$router.back()"
            class="p-2 hover:bg-slate-200 dark:hover:bg-slate-700 rounded-lg transition-colors">
            <ArrowLeft class="h-6 w-6 text-slate-700 dark:text-slate-300" />
          </button>
          <div>
            <h1 class="text-3xl font-bold text-slate-900 dark:text-slate-50">
              {{ isAdmin ? 'Transações (Admin)' : 'A Minha Carteira' }}
            </h1>
          </div>
        </div>
        <Badge v-if="!isAdmin" variant="default" class="px-4 py-2 text-lg bg-blue-600 dark:bg-blue-500">
          <CoinsIcon class="h-5 w-5 mr-2 text-yellow-400" />
          {{ wallet.balance }} moedas
        </Badge>
      </div>

      <section class="space-y-6">
        <div v-if="!isAdmin" class="bg-black/5 rounded-md p-4 space-y-3">
          <h2 class="font-medium">Comprar Moedas</h2>

          <form @submit.prevent="submitPurchase" class="grid grid-cols-1 md:grid-cols-3 gap-3 items-end">
            <div>
              <label class="block text-sm mb-1">Método de Pagamento</label>
              <select v-model="form.type" class="w-full border rounded p-2">
                <option disabled value="">Selecionar...</option>
                <option value="MBWAY">MBWAY</option>
                <option value="PAYPAL">PAYPAL</option>
                <option value="IBAN">IBAN</option>
                <option value="MB">MB</option>
                <option value="VISA">VISA</option>
              </select>
            </div>
            <div>
              <label class="block text-sm mb-1">Referência</label>
              <input v-model="form.reference" class="w-full border rounded p-2" placeholder="Referência" />
            </div>
            <div>
              <label class="block text-sm mb-1">Quantidade (€)</label>
              <input v-model.number="form.value" type="number" min="1" max="99" step="1"
                class="w-full border rounded p-2" />
            </div>
            <div class="md:col-span-3">
              <Button
                class="bg-blue-500 hover:bg-blue-400 text-white font-bold py-2 px-4 border-b-4 border-blue-700 hover:border-blue-500 rounded"
                :disabled="wallet.loading">
                Comprar
              </Button>
            </div>
          </form>
        </div>

        <div v-if="isAdmin">
          <AdminWalletTable :transactions="wallet.transactions" :loading="wallet.loading"
            :current-page="wallet.pagination.currentPage" :last-page="wallet.pagination.lastPage"
            :total="wallet.pagination.total" :transaction-types="transactionTypes" @filter="handleAdminFilter"
            @prev-page="handlePrevPage" @next-page="handleNextPage" />
        </div>

        <div v-else class="bg-white/5 rounded-md p-4 space-y-3">
          <div class="flex items-center justify-between mb-3">
            <h1 class="font-bold">As tuas transações</h1>
            <div class="flex items-center gap-2">
              <label class="text-sm text-slate-600 dark:text-slate-400">C/D:</label>
              <select v-model="playerTypeCodeFilter"
                class="px-3 py-1.5 border border-slate-300 dark:border-slate-700 rounded-md bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-sm">
                <option value="">Todos</option>
                <option value="C">C (Crédito)</option>
                <option value="D">D (Débito)</option>
              </select>
              <label class="text-sm text-slate-600 dark:text-slate-400">Tipo:</label>
              <select v-model="playerTypeFilter"
                class="px-3 py-1.5 border border-slate-300 dark:border-slate-700 rounded-md bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-sm">
                <option value="">Todos</option>
                <option v-for="tt in filteredTransactionTypes" :key="tt.id" :value="tt.id">
                  {{ tt.name + ' (' + tt.type + ')' }}
                </option>
              </select>
              <label class="text-sm text-slate-600 dark:text-slate-400">Depois da data:</label>
              <input v-model="playerDateStart" type="date"
                class="px-3 py-1.5 border border-slate-300 dark:border-slate-700 rounded-md bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-sm" />
              <label class="text-sm text-slate-600 dark:text-slate-400">Até à data:</label>
              <input v-model="playerDateEnd" type="date"
                class="px-3 py-1.5 border border-slate-300 dark:border-slate-700 rounded-md bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-sm" />
              <button @click="applyPlayerFilters"
                class="px-3 py-1.5 rounded-md bg-blue-600 text-white hover:bg-blue-700 transition-colors text-sm font-medium">
                Filtrar
              </button>
              <button v-if="hasPlayerActiveFilters" @click="resetPlayerFilters"
                class="px-3 py-1.5 rounded-md bg-slate-500 text-white hover:bg-slate-600 transition-colors text-sm font-medium">
                Limpar
              </button>
            </div>
          </div>

          <div
            class="relative overflow-x-auto bg-white/80 dark:bg-slate-900/80 shadow-sm rounded-2xl border border-slate-200/80 dark:border-slate-800/80">
            <table class="w-full text-sm text-left text-slate-800 dark:text-slate-200">
              <thead class="bg-slate-100 dark:bg-slate-800 sticky top-0">
                <tr class="text-left">
                  <th class="py-2 px-3">Data</th>
                  <th class="py-2 px-3">Tipo</th>
                  <th class="py-2 px-3">Moedas</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="t in filteredPlayerTransactions" :key="t.id"
                  class="odd:bg-white even:bg-slate-50 dark:odd:bg-slate-900 dark:even:bg-slate-800 hover:bg-slate-100 dark:hover:bg-slate-700 border-b border-slate-200/60 dark:border-slate-700/60">
                  <td class="py-2 px-3">{{ formatDate(t.datetime) }}</td>
                  <td class="py-2 px-3">
                    <span :class="[
                      'inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold',
                      getTypeClass(t.type),
                    ]">
                      {{ getTypeLabel(t.type) }}
                    </span>
                  </td>
                  <td class="py-2 px-3" :class="t.coins > 0
                    ? 'text-green-600 dark:text-green-400'
                    : 'text-red-600 dark:text-red-400'
                    ">
                    {{ t.coins > 0 ? '+' + t.coins : t.coins }}
                  </td>
                </tr>
                <tr v-if="filteredPlayerTransactions.length === 0">
                  <td colspan="3" class="py-4 px-3 text-center text-slate-500 dark:text-slate-400">
                    Sem transações.
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <div class="flex items-center justify-between pt-2 text-sm">
            <span>Página {{ wallet.pagination.currentPage }} de {{ wallet.pagination.lastPage }} ({{
              wallet.pagination.total
            }}
              itens)</span>
            <div class="flex gap-2">
              <button class="px-3 py-1 rounded bg-slate-700 text-white disabled:opacity-50"
                :disabled="wallet.pagination.currentPage <= 1" @click="goToPage(wallet.pagination.currentPage - 1)">
                Anterior
              </button>
              <button class="px-3 py-1 rounded bg-slate-700 text-white disabled:opacity-50"
                :disabled="wallet.pagination.currentPage >= wallet.pagination.lastPage"
                @click="goToPage(wallet.pagination.currentPage + 1)">
                Próxima
              </button>
            </div>
          </div>
        </div>
      </section>
    </div>
  </div>
</template>

<script setup>
import { onMounted, reactive, ref, computed, watch } from 'vue'
import { toast } from 'vue-sonner'
import { storeToRefs } from 'pinia'
import { useWalletStore } from '@/stores/wallet'
import { useAuthStore } from '@/stores/auth'
import { useAPIStore } from '@/stores/api'

import Button from '@/components/ui/button/Button.vue'
import Badge from '@/components/ui/badge/Badge.vue'
import AdminWalletTable from '@/components/admin/AdminWalletTable.vue'
import { ArrowLeft, CoinsIcon } from 'lucide-vue-next'

const wallet = useWalletStore()
const auth = useAuthStore()
const apiStore = useAPIStore()
const { isAdmin } = storeToRefs(auth)

const form = reactive({
  type: '',
  reference: '',
  value: 1,
})

const playerTypeFilter = ref('')
const playerTypeCodeFilter = ref('')
const playerDateStart = ref('')
const playerDateEnd = ref('')
const transactionTypes = ref([])

const filteredTransactionTypes = computed(() => {
  if (!playerTypeCodeFilter.value) {
    return transactionTypes.value
  }
  return transactionTypes.value.filter((tt) => tt.type === playerTypeCodeFilter.value)
})

watch(playerTypeCodeFilter, () => {
  playerTypeFilter.value = ''
})
const loadPlayerTransactions = async (page = 1) => {
  const typeId = playerTypeFilter.value ? Number(playerTypeFilter.value) : null
  const typeCode = playerTypeCodeFilter.value || null
  await wallet.fetchTransactions(
    page,
    playerDateStart.value || null,
    playerDateEnd.value || null,
    typeId,
    typeCode,
  )
}

const applyPlayerFilters = async () => {
  await loadPlayerTransactions(1)
}

const resetPlayerFilters = async () => {
  playerTypeFilter.value = ''
  playerTypeCodeFilter.value = ''
  playerDateStart.value = ''
  playerDateEnd.value = ''
  await loadPlayerTransactions(1)
}

const hasPlayerActiveFilters = computed(() => {
  return !!(
    playerTypeFilter.value ||
    playerTypeCodeFilter.value ||
    playerDateStart.value ||
    playerDateEnd.value
  )
})

const submitPurchase = async () => {
  try {
    const result = await wallet.purchaseCoins({ ...form })

    if (result.ok && wallet.lastPurchase) {
      const lp = wallet.lastPurchase
      toast.success(`Comprado! +${lp.coinsCredited} moedas`, {
        description: `Novo saldo: ${lp.newBalance}`,
      })
      form.type = ''
      form.reference = ''
      form.value = 1
      await wallet.fetchBalance()
    } else if (wallet.error) {
      const msg = wallet.error?.message ?? 'Ups, erro na compra'
      const details = wallet.error?.details?.message || wallet.error?.details
      const fullMsg = details ? `${msg}: ${details}` : msg
      toast.error('Ups, erro na compra', { description: fullMsg })
    }
  } catch (e) {
    const errorMsg = e?.response?.data?.message || e?.message || 'Ups, erro na compra'
    const errorDetails = e?.response?.data?.details?.message || e?.response?.data?.details
    const fullMsg = errorDetails ? `${errorMsg}: ${errorDetails}` : errorMsg
    toast.error('Ups, erro na compra', { description: fullMsg })
  }
}

const handleAdminFilter = async (filters) => {
  console.log('Filter applied:', filters)
  await wallet.fetchAdminTransactions(
    null,
    1,
    filters.userName,
    filters.type,
    filters.typeCode,
    filters.beganAfter,
    filters.endedBefore,
  )
}

const handlePrevPage = async () => {
  const page = wallet.pagination.currentPage - 1
  if (page >= 1) {
    await wallet.fetchAdminTransactions(
      null,
      page,
      wallet.adminFilterUserName,
      wallet.adminFilterType,
      wallet.adminFilterTypeCode,
      wallet.adminFilterBeganAfter,
      wallet.adminFilterEndedBefore,
    )
  }
}

const handleNextPage = async () => {
  const page = wallet.pagination.currentPage + 1
  if (page <= wallet.pagination.lastPage) {
    await wallet.fetchAdminTransactions(
      null,
      page,
      wallet.adminFilterUserName,
      wallet.adminFilterType,
      wallet.adminFilterTypeCode,
      wallet.adminFilterBeganAfter,
      wallet.adminFilterEndedBefore,
    )
  }
}

const goToPage = async (page) => {
  const target = Math.max(1, Math.min(page, wallet.pagination.lastPage))
  await loadPlayerTransactions(target)
}

const formatDate = (d) => {
  try {
    return new Date(d).toLocaleString()
  } catch {
    return d
  }
}

function getTypeLabel(type) {
  const labels = {
    P: 'Coin purchase',
    B: 'Bonus',
    MP: 'Match payout',
    MS: 'Match stake',
    GP: 'Game payout',
    GF: 'Game fee',
  }
  return labels[type] || type
}

function getTypeClass(type) {
  const classes = {
    P: 'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-200',
    B: 'bg-purple-100 text-purple-700 dark:bg-purple-900/40 dark:text-purple-200',
    MP: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-200',
    MS: 'bg-orange-100 text-orange-700 dark:bg-orange-900/40 dark:text-orange-200',
    GP: 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-200',
    GF: 'bg-rose-100 text-rose-700 dark:bg-rose-900/40 dark:text-rose-200',
  }
  return classes[type] || 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300'
}

const filteredPlayerTransactions = computed(() => {
  return wallet.transactions
})

onMounted(async () => {
  try {
    const res = await apiStore.getTransactionTypes()
    transactionTypes.value = Array.isArray(res.data) ? res.data : []
  } catch (e) {
    console.error('Failed to load transaction types:', e)
  }

  if (!isAdmin.value) {
    await wallet.fetchBalance()
    await loadPlayerTransactions(1)
  } else {
    await wallet.fetchAdminTransactions()
  }
})
</script>
