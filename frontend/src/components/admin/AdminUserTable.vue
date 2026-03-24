<template>
  <div class="space-y-6">
    <div class="rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-4 space-y-4">
      <h3 class="text-base font-semibold text-slate-800 dark:text-slate-100">Filtros</h3>
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
        <div>
          <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Pesquisar (nome, nickname, email)</label>
          <input v-model="localFilters.search"
            class="block w-full rounded-md border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 px-3 py-2 shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
            placeholder="Pesquisar..." />
        </div>
        <div>
          <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Papel</label>
          <select v-model="localFilters.role"
            class="block w-full rounded-md border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 px-3 py-2 shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            <option value="">Todos</option>
            <option value="P">Jogadores</option>
            <option value="A">Administradores</option>
          </select>
        </div>
        <div>
          <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Estado</label>
          <select v-model="localFilters.blocked"
            class="block w-full rounded-md border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 px-3 py-2 shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            <option value="">Todos</option>
            <option value="0">Ativos</option>
            <option value="1">Bloqueados</option>
          </select>
        </div>
        <div class="flex gap-2">
          <button
            class="inline-flex items-center justify-center rounded-md bg-blue-600 dark:bg-blue-600 text-white px-3 py-2 text-sm font-medium hover:bg-blue-700 dark:hover:bg-blue-700 transition-colors"
            @click="emitApplyFilters">Aplicar</button>
          <button
            class="inline-flex items-center justify-center rounded-md bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-300 px-3 py-2 text-sm font-medium hover:bg-slate-300 dark:hover:bg-slate-600 transition-colors"
            @click="emitResetFilters">Limpar</button>
        </div>
      </div>
    </div>
    <div class="rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 shadow-lg">
      <div class="flex items-center justify-between p-4 border-b border-slate-200 dark:border-slate-700">
        <h3 class="text-base font-semibold text-slate-800 dark:text-slate-100">Utilizadores</h3>
        <span v-if="loading" class="text-xs text-slate-500 dark:text-slate-400">A carregar...</span>
      </div>
      <div v-if="error" class="px-4 pb-2 text-sm text-red-500 dark:text-red-400">{{ error }}</div>

      <div class="relative overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
          <thead class="bg-slate-50 dark:bg-slate-900">
            <tr>
              <th scope="col"
                class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-slate-700 dark:text-slate-300 cursor-pointer hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors"
                @click="onSort('name')">
                <div class="flex items-center gap-2">Nome
                  <component :is="sortIcon('name')" class="h-4 w-4" />
                </div>
              </th>
              <th scope="col"
                class="px-3 py-3.5 text-left text-sm font-semibold text-slate-700 dark:text-slate-300 cursor-pointer hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors"
                @click="onSort('type')">
                <div class="flex items-center gap-2">Papel
                  <component :is="sortIcon('type')" class="h-4 w-4" />
                </div>
              </th>
              <th scope="col"
                class="px-3 py-3.5 text-left text-sm font-semibold text-slate-700 dark:text-slate-300 cursor-pointer hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors"
                @click="onSort('coins_balance')">
                <div class="flex items-center gap-2">Moedas
                  <component :is="sortIcon('coins_balance')" class="h-4 w-4" />
                </div>
              </th>
              <th scope="col"
                class="px-3 py-3.5 text-left text-sm font-semibold text-slate-700 dark:text-slate-300 cursor-pointer hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors"
                @click="onSort('blocked')">
                <div class="flex items-center gap-2">Estado
                  <component :is="sortIcon('blocked')" class="h-4 w-4" />
                </div>
              </th>
              <th scope="col" class="relative py-3.5 pl-3 pr-4 sm:pr-6">
                <span class="sr-only">Ações</span>
              </th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-200 dark:divide-slate-700 bg-white dark:bg-slate-900">
            <tr v-for="u in items" :key="u.id" class="hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors">
              <td class="whitespace-nowrap py-4 pl-4 pr-3">
                <div class="flex items-center gap-3">
                  <div class="h-10 w-10 flex-shrink-0">
                    <img v-if="u.photo_url" class="h-10 w-10 rounded-full object-cover" :src="u.photo_url"
                      :alt="u.name" />
                    <div v-else
                      class="h-10 w-10 rounded-full bg-slate-200 dark:bg-slate-700 border border-slate-300 dark:border-slate-600 flex items-center justify-center text-sm font-medium text-slate-700 dark:text-slate-300">
                      {{ userInitials(u) }}
                    </div>
                  </div>
                  <div>
                    <div class="font-medium text-slate-900 dark:text-slate-100 cursor-help" :title="`${u.name}`">{{ u.nickname || u.name }}</div>
                    <div class="text-sm text-slate-600 dark:text-slate-400">{{ u.email }}</div>
                  </div>
                </div>
              </td>
              <td class="whitespace-nowrap px-3 py-4 text-sm">
                <span class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium border"
                  :class="u.type === 'A' ? 'border-purple-500/40 bg-purple-500/20 text-purple-700 dark:text-purple-300' : 'border-slate-300 dark:border-slate-600 bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300'">
                  {{ u.type === 'A' ? 'Admin' : 'Jogador' }}
                </span>
              </td>
              <td class="whitespace-nowrap px-3 py-4 text-sm">
                <div class="flex items-center gap-1">
                  <span class="font-medium text-slate-900 dark:text-slate-100">{{ u.coins_balance ?? 0 }}</span>
                  <span class="text-slate-600 dark:text-slate-400">moedas</span>
                </div>
              </td>
              <td class="whitespace-nowrap px-3 py-4 text-sm">
                <span class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium border"
                  :class="Number(u.blocked ?? 0) === 1 ? 'border-red-500/40 bg-red-500/20 text-red-700 dark:text-red-300' : 'border-emerald-500/40 bg-emerald-500/20 text-emerald-700 dark:text-emerald-300'">
                  {{ Number(u.blocked ?? 0) === 1 ? 'Bloqueado' : 'Ativo' }}
                </span>
              </td>
              <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-6">
                <div class="flex justify-end gap-2">
                  <button
                    class="inline-flex items-center rounded-md px-2.5 py-1.5 text-xs font-medium border border-slate-300 dark:border-slate-600 bg-white dark:bg-transparent text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700 disabled:opacity-50 transition-colors"
                    @click="onToggleBlock(u)" :disabled="loading || u.type === 'A'">{{ Number(u.blocked ?? 0) === 1 ?
                      'Desbloquear' : 'Bloquear' }}</button>
                  <button
                    class="inline-flex items-center rounded-md px-2.5 py-1.5 text-xs font-medium border border-slate-300 dark:border-slate-600 bg-white dark:bg-transparent text-slate-600 dark:text-slate-400 hover:bg-red-50 dark:hover:bg-red-500/20 hover:text-red-600 dark:hover:text-red-400 hover:border-red-300 dark:hover:border-red-500/40 disabled:opacity-50 transition-colors"
                    @click="onDelete(u)" :disabled="loading || u.id === currentUserId">Remover</button>
                </div>
              </td>
            </tr>
            <tr v-if="!items || items.length === 0">
              <td colspan="5" class="px-4 py-8 text-center text-sm text-slate-500 dark:text-slate-400">Sem utilizadores.
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div
        class="flex items-center justify-between p-4 text-sm text-slate-600 dark:text-slate-400 border-t border-slate-200 dark:border-slate-700">
        <span>Página {{ page }} de {{ lastPage }} ({{ total }} registos)</span>
        <div class="flex gap-2">
          <button
            class="inline-flex items-center rounded-md bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-300 px-3 py-1 font-medium hover:bg-slate-300 dark:hover:bg-slate-700 disabled:opacity-50 transition-colors"
            :disabled="page <= 1" @click="emitPageChange(page - 1)">Anterior</button>
          <button
            class="inline-flex items-center rounded-md bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-300 px-3 py-1 font-medium hover:bg-slate-300 dark:hover:bg-slate-700 disabled:opacity-50 transition-colors"
            :disabled="page >= lastPage" @click="emitPageChange(page + 1)">Próxima</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { reactive, watch, computed } from 'vue'
import { ArrowUpDown, ArrowUp, ArrowDown } from 'lucide-vue-next'


const props = defineProps({
  items: { type: Array, default: () => [] },
  loading: { type: Boolean, default: false },
  error: { type: String, default: null },
  currentUserId: { type: Number, default: null },
  page: { type: Number, default: 1 },
  perPage: { type: Number, default: 10 },
  total: { type: Number, default: 0 },
  lastPage: { type: Number, default: 1 },
  filters: {
    type: Object,
    default: () => ({ search: '', role: '', blocked: '' })
  },
  sortBy: { type: String, default: '' },
  sortDir: { type: String, default: 'asc' }
})



const emit = defineEmits([
  'apply-filters',
  'reset-filters',
  'page-change',
  'toggle-block',
  'delete-user',
  'sort-change',
])

const localFilters = reactive({ ...props.filters })


function userInitials(user) {
  const name = user.nickname || user.name || 'U'
  return name.split(' ').map(n => n[0]).join('').toUpperCase().substring(0, 2)
}

watch(() => props.filters, (f) => {
  Object.assign(localFilters, f || { search: '', role: '', blocked: '' })
}, { deep: true })

function emitApplyFilters() {
  emit('apply-filters', { ...localFilters })
}
function emitResetFilters() {
  emit('reset-filters')
}
function emitPageChange(nextPage) {
  emit('page-change', nextPage)
}
function onToggleBlock(u) {
  emit('toggle-block', u)
}
function onDelete(u) {
  emit('delete-user', u)
}

function sortIcon(field) {
  if (props.sortBy !== field) return ArrowUpDown
  return props.sortDir === 'asc' ? ArrowUp : ArrowDown
}

function onSort(field) {
  let newDir = 'asc'
  if (props.sortBy === field && props.sortDir === 'asc') {
    newDir = 'desc'
  }
  emit('sort-change', { sortBy: field, sortDir: newDir })
}
</script>
