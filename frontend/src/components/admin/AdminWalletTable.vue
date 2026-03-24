<template>
  <div class="space-y-4">
    <div class="bg-white/5 dark:bg-slate-900/40 rounded-lg p-4 space-y-4 border border-slate-200/50 dark:border-slate-800/50">
      <div class="flex items-center justify-between flex-wrap gap-3">
        <h2 class="text-lg font-semibold text-slate-800 dark:text-slate-100">Todas as Transações</h2>
        <div class="flex items-center gap-3 flex-wrap">
          <div class="flex items-center gap-2">
            <label class="text-sm text-slate-600 dark:text-slate-400">Jogador:</label>
            <input 
              v-model="filters.userName" 
              type="text"
              placeholder="Nickname, Nome ou Email..."
              class="w-52 px-3 py-1.5 border border-slate-300 dark:border-slate-700 rounded-md bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 placeholder-slate-400 dark:placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-sm"
            />
          </div>
          <div class="flex items-center gap-2">
            <label class="text-sm text-slate-600 dark:text-slate-400">C/D:</label>
            <select 
              v-model="filters.typeCode"
              class="px-3 py-1.5 border border-slate-300 dark:border-slate-700 rounded-md bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-sm"
            >
              <option value="">Todos</option>
              <option value="C">C (Crédito)</option>
              <option value="D">D (Débito)</option>
            </select>
          </div>
          <div class="flex items-center gap-2">
            <label class="text-sm text-slate-600 dark:text-slate-400">Tipo:</label>
            <select 
              v-model="filters.type"
              class="px-3 py-1.5 border border-slate-300 dark:border-slate-700 rounded-md bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-sm"
            >
              <option value="">Todos</option>
              <option v-for="tt in filteredTransactionTypes" :key="tt.id" :value="tt.id">
                {{ tt.name + ' (' + tt.type + ')' }}
              </option>
            </select>
          </div>
          <div class="flex items-center gap-2">
            <label class="text-sm text-slate-600 dark:text-slate-400">Depois da data:</label>
            <input
              v-model="filters.began_after"
              type="date"
              class="px-3 py-1.5 border border-slate-300 dark:border-slate-700 rounded-md bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-sm"
            />
          </div>
          <div class="flex items-center gap-2">
            <label class="text-sm text-slate-600 dark:text-slate-400">Até à data:</label>
            <input
              v-model="filters.ended_before"
              type="date"
              class="px-3 py-1.5 border border-slate-300 dark:border-slate-700 rounded-md bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-sm"
            />
          </div>
          <button 
            @click="applyFilters"
            class="px-4 py-1.5 rounded-md bg-blue-600 text-white hover:bg-blue-700 transition-colors text-sm font-medium"
          >
            Filtrar
          </button>
          <button 
            v-if="hasActiveFilters"
            @click="resetFilters"
            class="px-4 py-1.5 rounded-md bg-slate-500 text-white hover:bg-slate-600 transition-colors text-sm font-medium"
          >
            Limpar
          </button>
        </div>
      </div>
    </div>

    <div v-if="loading" class="flex justify-center py-10">
      <span class="text-sm text-slate-500 dark:text-slate-400">A carregar...</span>
    </div>

    <div v-else class="relative overflow-x-auto bg-white dark:bg-slate-900 shadow-sm rounded-lg border border-slate-200 dark:border-slate-800">
      <table class="w-full text-sm text-left text-slate-800 dark:text-slate-200">
        <thead class="bg-slate-100 dark:bg-slate-800 sticky top-0">
          <tr class="text-left">
            <th class="py-3 px-4 font-semibold">Data</th>
            <th class="py-3 px-4 font-semibold">Jogador</th>
            <th class="py-3 px-4 font-semibold">Tipo</th>
            <th class="py-3 px-4 font-semibold">Moedas</th>
          </tr>
        </thead>
        <tbody>
          <tr 
            v-for="t in transactions" 
            :key="t.id"
            class="odd:bg-white even:bg-slate-50 dark:odd:bg-slate-900 dark:even:bg-slate-800 hover:bg-slate-100 dark:hover:bg-slate-700 border-b border-slate-200/60 dark:border-slate-700/60 transition-colors"
          >
            <td class="py-3 px-4 text-slate-700 dark:text-slate-300">{{ formatDate(t.datetime) }}</td>
            <td class="py-3 px-4">
              <span class="font-medium text-slate-800 dark:text-slate-100 cursor-help" :title="`${t.user?.name ?? '-'} | ${t.user?.email ?? '-'}`">{{ t.user?.nickname ?? '' }}</span>
            </td>
            <td class="py-3 px-4">
              <span 
                :class="[
                  'inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold',
                  getTypeClass(t.type)
                ]"
              >
                {{ getTypeLabel(t.type) }}
              </span>
            </td>
            <td 
              class="py-3 px-4 font-semibold"
              :class="t.coins > 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'"
            >
              {{ t.coins > 0 ? '+' + t.coins : t.coins }}
            </td>
          </tr>
          <tr v-if="transactions.length === 0">
            <td colspan="4" class="py-8 px-4 text-center text-slate-500 dark:text-slate-400">
              Sem transações.
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <div class="flex items-center justify-between pt-2 text-sm text-slate-700 dark:text-slate-300">
      <span>Página {{ currentPage }} de {{ lastPage }} ({{ total }} transações)</span>
      <div class="flex gap-2">
        <button 
          class="px-4 py-2 rounded-md bg-slate-600 text-white hover:bg-slate-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors font-medium"
          :disabled="currentPage <= 1"
          @click="prevPage"
        >
          Anterior
        </button>
        <button 
          class="px-4 py-2 rounded-md bg-slate-600 text-white hover:bg-slate-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors font-medium"
          :disabled="currentPage >= lastPage"
          @click="nextPage"
        >
          Próxima
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'

const props = defineProps({
  transactions: {
    type: Array,
    default: () => []
  },
  loading: {
    type: Boolean,
    default: false
  },
  currentPage: {
    type: Number,
    default: 1
  },
  lastPage: {
    type: Number,
    default: 1
  },
  total: {
    type: Number,
    default: 0
  },
  transactionTypes: {
    type: Array,
    default: () => []
  }
})

const emit = defineEmits(['filter', 'prev-page', 'next-page'])

const filters = ref({
  userName: '',
  type: '',
  typeCode: '',
  began_after: '',
  ended_before: ''
})

const hasActiveFilters = computed(() => {
  return filters.value.userName.trim() !== '' || filters.value.type !== '' || filters.value.typeCode !== '' ||
    filters.value.began_after !== '' || filters.value.ended_before !== ''
})

const filteredTransactionTypes = computed(() => {
  if (!filters.value.typeCode) {
    return props.transactionTypes
  }
  return props.transactionTypes.filter(tt => tt.type === filters.value.typeCode)
})

watch(() => filters.value.typeCode, () => {
  filters.value.type = ''
})

function formatDate(d) {
  try {
    return new Date(d).toLocaleString()
  } catch {
    return d
  }
}

function applyFilters() {
  emit('filter', {
    userName: filters.value.userName.trim() || null,
    type: filters.value.type || null,
    typeCode: filters.value.typeCode || null,
    beganAfter: filters.value.began_after || null,
    endedBefore: filters.value.ended_before || null
  })
}

function resetFilters() {
  filters.value = {
    userName: '',
    type: '',
    typeCode: '',
    began_after: '',
    ended_before: ''
  }
  emit('filter', {
    userName: null,
    type: null,
    typeCode: null,
    beganAfter: null,
    endedBefore: null
  })
}

function prevPage() {
  emit('prev-page')
}

function nextPage() {
  emit('next-page')
}

function getTypeLabel(type) {
  const labels = {
    'P': 'Coin purchase',
    'B': 'Bonus',
    'MP': 'Match payout',
    'MS': 'Match stake',
    'GP': 'Game payout',
    'GF': 'Game fee'
  }
  return labels[type] || type
}

function getTypeClass(type) {
  const classes = {
    'P': 'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-200',
    'B': 'bg-purple-100 text-purple-700 dark:bg-purple-900/40 dark:text-purple-200',
    'MP': 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-200',
    'MS': 'bg-orange-100 text-orange-700 dark:bg-orange-900/40 dark:text-orange-200',
    'GP': 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-200',
    'GF': 'bg-rose-100 text-rose-700 dark:bg-rose-900/40 dark:text-rose-200'
  }
  return classes[type] || 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300'
}
</script>

<style scoped></style>
