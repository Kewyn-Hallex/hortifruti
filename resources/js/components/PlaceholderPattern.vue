<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from "vue"
import { Plus, Search, Trash2, Pencil, DollarSign } from 'lucide-vue-next';
import { router } from '@inertiajs/vue3';

const stats = ref({
  pedidos: 0,
  faturamento: 0,
  clientes: 0
})

const pedidos = ref<Array<{ 
  id: number; 
  cliente: string; 
  total: number; 
  data: string; 
  itens: number;
  total_paid?: number;
  remaining?: number;
  payment_percentage?: number;
  payment_status?: 'paid' | 'partial' | 'unpaid';
}>>([])
const loading = ref(false)
const loadingStats = ref(false)
const successMessage = ref('')
const deleteModal = ref({ show: false, orderId: 0, orderName: '' })
const deleting = ref(false)
const searchTerm = ref('')
let statsInterval: ReturnType<typeof setInterval> | null = null

async function fetchStats() {
  loadingStats.value = true
  try {
    const res = await fetch('/api/stats', { credentials: 'same-origin' })
    if (!res.ok) throw new Error('Erro ao carregar estatísticas')
    const data = await res.json()
    stats.value = {
      pedidos: data.pedidos || 0,
      faturamento: Number(data.faturamento || 0),
      clientes: data.clientes || 0,
    }
  } catch (e) {
    console.error('Erro ao buscar estatísticas:', e)
  } finally {
    loadingStats.value = false
  }
}

async function fetchOrders() {
  loading.value = true
  try {
    const res = await fetch('/api/orders', { credentials: 'same-origin' })
    if (!res.ok) throw new Error('Erro ao carregar pedidos')
    const data = await res.json()
    pedidos.value = (data || []).map((p: any) => ({
      id: p.id,
      cliente: p.cliente,
      total: Number(p.total || 0),
      data: p.data,
      itens: Number(p.itens || 0),
      total_paid: Number(p.total_paid || 0),
      remaining: Number(p.remaining || p.total || 0),
      payment_percentage: Number(p.payment_percentage || 0),
      payment_status: p.payment_status || 'unpaid',
    }))
    // Atualizar estatísticas após carregar pedidos
    await fetchStats()
  } catch (e) {
    console.error(e)
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  fetchOrders()
  fetchStats()
  
  // Configurar polling para atualizar estatísticas a cada 30 segundos
  statsInterval = setInterval(() => {
    fetchStats()
  }, 30000)
  
  try {
    const v = sessionStorage.getItem('orderSuccess')
    if (v === '1') {
      successMessage.value = 'Pedido finalizado com sucesso.'
      sessionStorage.removeItem('orderSuccess')
      setTimeout(() => (successMessage.value = ''), 3000)
      // Atualizar estatísticas quando um novo pedido é criado
      fetchStats()
    }
  } catch (e) {}
})

onUnmounted(() => {
  if (statsInterval) {
    clearInterval(statsInterval)
  }
})

const filteredPedidos = computed(() => {
  const term = searchTerm.value.toLowerCase().trim()
  if (!term) return pedidos.value
  return pedidos.value.filter((p) =>
    String(p.id).includes(term) ||
    p.cliente.toLowerCase().includes(term)
  )
})

const handleAdd = () => {
  router.visit('/pedidos/create');
}

const handleEdit = (orderId: number) => {
  router.visit(`/pedidos/${orderId}/edit`);
}

const handlePayments = (orderId: number) => {
  router.visit(`/pedidos/${orderId}/payments`);
}

const getPaymentStatusColor = (status: string) => {
  if (status === 'paid') return 'bg-green-500/20 border-green-500/50 text-green-400';
  if (status === 'partial') return 'bg-yellow-500/20 border-yellow-500/50 text-yellow-400';
  return 'bg-red-500/20 border-red-500/50 text-red-400';
}

const getPaymentStatusLabel = (status: string) => {
  if (status === 'paid') return 'Pago';
  if (status === 'partial') return 'Parcial';
  return 'Não Pago';
}

function openDeleteModal(orderId: number, orderName: string) {
  deleteModal.value = { show: true, orderId, orderName };
}

function closeDeleteModal() {
  deleteModal.value = { show: false, orderId: 0, orderName: '' };
}

async function confirmDelete() {
  const orderId = deleteModal.value.orderId;
  deleting.value = true;

  try {
    router.delete(`/pedidos/${orderId}`, {
      onSuccess: () => {
        pedidos.value = pedidos.value.filter(p => p.id !== orderId);
        successMessage.value = 'Pedido deletado com sucesso.';
        setTimeout(() => (successMessage.value = ''), 2500);
        closeDeleteModal();
        deleting.value = false;
        // Atualizar estatísticas após deletar pedido
        fetchStats();
      },
      onError: (errors) => {
        console.error('Erro ao deletar', errors);
        alert('Erro ao deletar pedido: ' + JSON.stringify(errors));
        deleting.value = false;
      }
    });
  } catch (e) {
    console.error('Erro ao deletar', e);
    alert('Erro ao deletar pedido');
    deleting.value = false;
  }
}
</script>

<template>
  <!-- Layout base -->
  <div class="relative min-h-screen text-gray-100">
    <!-- Container central -->
    <div class="mx-auto max-w-6xl px-4 py-8 lg:px-6 lg:py-10 flex flex-col gap-8">

      <!-- Toast de sucesso (flutuante e discreto) -->
      <transition name="fade">
        <div
          v-if="successMessage"
          class="fixed right-4 top-4 z-40 flex items-center gap-3 rounded-lg border border-emerald-500/40 bg-zinc-900/95 px-4 py-3 text-sm shadow-xl backdrop-blur-md"
        >
          <svg class="h-4 w-4 text-emerald-400" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd"
              d="M16.707 5.293a1 1 0 00-1.414-1.414L8 11.172 4.707 7.879A1 1 0 103.293 9.293l4 4a1 1 0 001.414 0l8-8z"
              clip-rule="evenodd" />
          </svg>
          <span class="text-emerald-100">{{ successMessage }}</span>
        </div>
      </transition>

      <!-- Header -->
      <header class="flex flex-col gap-4 border-b border-white/5 pb-4 md:flex-row md:items-end md:justify-between">
        <div class="space-y-1">
          <h1 class="text-2xl font-semibold tracking-tight sm:text-3xl">
            Dashboard de Pedidos
          </h1>
          <p class="text-sm text-gray-400">
            Acompanhe os pedidos em tempo real, clientes e faturamento.
          </p>
        </div>

        <!-- Ações principais no header (mobile-friendly) -->
        <div class="flex flex-col-reverse gap-3 sm:flex-row sm:items-center">
          <!-- Campo busca -->
          <div class="w-full sm:w-64">
            <label for="order-search" class="mb-1 block text-xs font-medium text-gray-400">
              Buscar pedidos
            </label>
            <div class="relative">
              <span class="pointer-events-none absolute inset-y-0 left-3 flex items-center">
                <Search class="h-4 w-4 text-gray-500" />
              </span>
              <input
                id="order-search"
                v-model="searchTerm"
                type="text"
                placeholder="Filtrar por ID ou cliente..."
                class="w-full rounded-lg border border-white/10 bg-black/40 px-9 py-2 text-xs sm:text-sm 
                       text-gray-100 shadow-inner outline-none transition
                       placeholder:text-gray-500
                       focus:border-[#D9A84E] focus:ring-2 focus:ring-[#D9A84E]/40"
              />
            </div>
          </div>
        </div>
      </header>

      <!-- Stats -->
      <section class="grid gap-4 md:grid-cols-3">
        <article
          class="relative overflow-hidden rounded-md border border-white/5 bg-zinc-950/80 p-4 shadow-sm
                 backdrop-blur-sm"
        >
          <p class="text-xs font-medium uppercase tracking-wide text-gray-400">
            Pedidos hoje
          </p>
          <p class="mt-2 text-3xl font-semibold tracking-tight">
            <span
              class="bg-gradient-to-r from-[#D9A84E] to-[#B58737] bg-clip-text text-transparent"
            >
              {{ stats.pedidos }}
            </span>
          </p>
          <p class="mt-1 text-xs text-gray-500">
            Número total de pedidos realizados nas últimas 24h.
          </p>
        </article>

        <article
          class="relative overflow-hidden rounded-md border border-white/5 bg-zinc-950/80 p-4 shadow-sm
                 backdrop-blur-sm"
        >
          <p class="text-xs font-medium uppercase tracking-wide text-gray-400">
            Faturamento estimado
          </p>
          <p class="mt-2 text-3xl font-semibold tracking-tight">
            <span
              class="bg-gradient-to-r from-[#D9A84E] to-[#B58737] bg-clip-text text-transparent"
            >
              R$ {{ stats.faturamento.toFixed(2) }}
            </span>
          </p>
          <p class="mt-1 text-xs text-gray-500">
            Valor aproximado considerando pedidos em aberto.
          </p>
        </article>

        <article
          class="relative overflow-hidden rounded-md border border-white/5 bg-zinc-950/80 p-4 shadow-sm
                 backdrop-blur-sm"
        >
          <p class="text-xs font-medium uppercase tracking-wide text-gray-400">
            Clientes ativos
          </p>
          <p class="mt-2 text-3xl font-semibold tracking-tight">
            <span
              class="bg-gradient-to-r from-[#D9A84E] to-[#B58737] bg-clip-text text-transparent"
            >
              {{ stats.clientes }}
            </span>
          </p>
          <p class="mt-1 text-xs text-gray-500">
            Clientes que fizeram pelo menos um pedido este mês.
          </p>
        </article>
      </section>

      <!-- Tabela de pedidos -->
<section
  class="overflow-hidden rounded-md border border-white/5 bg-zinc-900 shadow-sm backdrop-blur-sm"
>
  <!-- Header da seção de tabela -->
  <div
    class="flex flex-col gap-2 border-b border-white/5 px-4 py-3 text-xs text-gray-400 sm:flex-row sm:items-center sm:justify-between"
  >
    <div>
      <div class="font-medium tracking-wide uppercase">
        Últimos pedidos
      </div>
      <div class="text-[11px] text-gray-500">
        {{ filteredPedidos.length }} resultado(s)
      </div>
    </div>

    <!-- Botão Novo Pedido aqui (posição escolhida) -->
    <button
      @click="handleAdd"
      class="inline-flex items-center justify-center gap-2 rounded-md border border-[#D9A84E]/70 
             bg-gradient-to-r from-[#D9A84E] to-[#B58737]
             px-3 py-1.5 text-xs font-semibold text-black shadow-sm
             transition hover:brightness-110 hover:shadow-md
             focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#D9A84E]/60
             dark:text-white"
    >
      <Plus class="h-3 w-3" />
      <span>Novo pedido</span>
    </button>
  </div>

  <!-- Loading -->
  <div
    v-if="loading"
    class="flex flex-col items-center justify-center gap-2 py-12 text-sm"
  >
    <svg
      class="h-7 w-7 animate-spin text-[#D9A84E]"
      xmlns="http://www.w3.org/2000/svg"
      fill="none"
      viewBox="0 0 24 24"
    >
      <circle
        class="opacity-20"
        cx="12"
        cy="12"
        r="10"
        stroke="currentColor"
        stroke-width="4"
      ></circle>
      <path
        class="opacity-80"
        fill="currentColor"
        d="M4 12a8 8 0 018-8v3.5A4.5 4.5 0 007.5 12H4z"
      ></path>
    </svg>
    <p class="text-gray-400">Carregando pedidos...</p>
  </div>

  <!-- Listagem -->
  <div v-else>
    <!-- Versão Desktop/Tablet (tabela) -->
    <div class="hidden md:block overflow-x-auto">
      <table class="min-w-full text-left text-sm">
        <thead class="text-xs uppercase tracking-wide text-gray-400">
          <tr class="border-b border-white/5 bg-black/40">
            <th class="px-4 py-3 font-medium">ID</th>
            <th class="px-4 py-3 font-medium">Cliente</th>
            <th class="px-4 py-3 font-medium">Itens</th>
            <th class="px-4 py-3 text-right font-medium">Data</th>
            <th class="px-4 py-3 text-right font-medium">Total</th>
            <th class="px-4 py-3 text-center font-medium">Pagamento</th>
            <th class="px-4 py-3 text-right font-medium">Ações</th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="(pedido, index) in filteredPedidos"
            :key="pedido.id"
            class="border-b border-white/[0.03] text-sm last:border-0
                   hover:bg-white/5 transition-colors"
            :class="index % 2 === 0 ? 'bg-white/[0.01]' : ''"
          >
            <td class="px-4 py-3 align-middle font-mono text-xs text-gray-400">
              #{{ pedido.id }}
            </td>
            <td class="px-4 py-3 align-middle text-sm font-medium text-gray-100">
              {{ pedido.cliente }}
            </td>
            <td class="px-4 py-3 align-middle text-xs text-gray-300">
              {{ pedido.itens }} item{{ pedido.itens !== 1 ? 's' : '' }}
            </td>
            <td class="px-4 py-3 align-middle text-right text-xs text-gray-400">
              {{ pedido.data }}
            </td>
            <td
              class="px-4 py-3 align-middle text-right text-sm font-semibold 
                     bg-gradient-to-r from-[#D9A84E] to-[#B58737] bg-clip-text text-transparent"
            >
              R$ {{ pedido.total.toFixed(2) }}
            </td>
            <td class="px-4 py-3 align-middle text-center">
              <div class="flex flex-col items-center gap-2">
                <!-- Status Badge -->
                <span
                  class="inline-flex items-center gap-1.5 px-2 py-1 rounded-full text-xs font-medium border"
                  :class="getPaymentStatusColor(pedido.payment_status || 'unpaid')"
                >
                  <span
                    class="h-2 w-2 rounded-full"
                    :class="{
                      'bg-green-400': pedido.payment_status === 'paid',
                      'bg-yellow-400': pedido.payment_status === 'partial',
                      'bg-red-400': pedido.payment_status === 'unpaid',
                    }"
                  ></span>
                  {{ getPaymentStatusLabel(pedido.payment_status || 'unpaid') }}
                </span>
                <!-- Barra de Progresso -->
                <div class="w-20 h-1.5 bg-zinc-800 rounded-full overflow-hidden">
                  <div
                    class="h-full transition-all duration-500 rounded-full"
                    :class="{
                      'bg-green-500': pedido.payment_status === 'paid',
                      'bg-yellow-500': pedido.payment_status === 'partial',
                      'bg-red-500': pedido.payment_status === 'unpaid',
                    }"
                    :style="{ width: `${pedido.payment_percentage || 0}%` }"
                  ></div>
                </div>
                <!-- Valores -->
                <div class="text-[10px] text-gray-500 text-center">
                  <div>Pago: R$ {{ (pedido.total_paid || 0).toFixed(2) }}</div>
                  <div>Restante: R$ {{ (pedido.remaining || pedido.total).toFixed(2) }}</div>
                </div>
              </div>
            </td>
            <td class="px-4 py-3 align-middle text-right">
              <div class="flex items-center justify-end gap-2">
                <button
                  @click="handlePayments(pedido.id)"
                  class="inline-flex items-center justify-center rounded-full border border-blue-500/30 
                         bg-blue-500/5 p-1.5 text-blue-400 transition
                         hover:bg-blue-500/15 hover:text-blue-300
                         focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500/40"
                  title="Gerenciar pagamentos"
                >
                  <DollarSign class="h-4 w-4" />
                </button>
                <button
                  @click="handleEdit(pedido.id)"
                  class="inline-flex items-center justify-center rounded-full border border-[#D9A84E]/30 
                         bg-[#D9A84E]/5 p-1.5 text-[#D9A84E] transition
                         hover:bg-[#D9A84E]/15 hover:text-[#D9A84E]
                         focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#D9A84E]/40"
                  title="Editar pedido"
                >
                  <Pencil class="h-4 w-4" />
                </button>
                <button
                  @click="openDeleteModal(pedido.id, pedido.cliente)"
                  class="inline-flex items-center justify-center rounded-full border border-red-500/30 
                         bg-red-500/5 p-1.5 text-red-400 transition
                         hover:bg-red-500/15 hover:text-red-300
                         focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-red-500/40"
                  title="Deletar pedido"
                >
                  <Trash2 class="h-4 w-4" />
                </button>
              </div>
            </td>
          </tr>

          <!-- Estado vazio -->
          <tr v-if="!filteredPedidos.length">
            <td colspan="7" class="py-10 text-center text-sm text-gray-500">
              <div class="mx-auto max-w-xs space-y-2">
                <p class="font-medium text-gray-300">
                  Nenhum pedido encontrado.
                </p>
                <p class="text-xs text-gray-500">
                  Ajuste o filtro de busca ou crie um novo pedido para começar.
                </p>
                <button
                  @click="handleAdd"
                  class="mt-2 inline-flex items-center justify-center gap-2 rounded-md border border-[#D9A84E] 
                         bg-white/5 px-3 py-1.5 text-xs font-medium text-gray-100
                         hover:bg-white/10 transition"
                >
                  <Plus class="h-3 w-3" />
                  Novo pedido
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Versão Mobile (cards) -->
    <div class="block md:hidden">
      <div
        v-for="(pedido, index) in filteredPedidos"
        :key="pedido.id"
        class="border-b border-white/[0.04] last:border-0
               px-4 py-3 text-xs"
        :class="index % 2 === 0 ? 'bg-white/[0.01]' : ''"
      >
        <div class="flex items-center justify-between gap-2 mb-1.5">
          <div class="flex items-center gap-2">
            <span class="font-mono text-[11px] text-gray-400">#{{ pedido.id }}</span>
            <span class="text-[13px] font-semibold text-gray-100">
              {{ pedido.cliente }}
            </span>
          </div>
          <div
            class="text-[12px] font-semibold bg-gradient-to-r from-[#D9A84E] to-[#B58737] 
                   bg-clip-text text-transparent"
          >
            R$ {{ pedido.total.toFixed(2) }}
          </div>
        </div>

        <div class="flex items-center justify-between gap-2 mb-2">
          <div class="text-[11px] text-gray-400">
            {{ pedido.itens }} item{{ pedido.itens !== 1 ? 's' : '' }}
          </div>
          <div class="text-[11px] text-gray-500">
            {{ pedido.data }}
          </div>
        </div>

        <div class="flex items-start justify-between gap-3">
          <!-- Pagamento -->
          <div class="flex-1">
            <div class="flex items-center gap-2 mb-1">
              <span
                class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-[10px] font-medium border"
                :class="getPaymentStatusColor(pedido.payment_status || 'unpaid')"
              >
                <span
                  class="h-1.5 w-1.5 rounded-full"
                  :class="{
                    'bg-green-400': pedido.payment_status === 'paid',
                    'bg-yellow-400': pedido.payment_status === 'partial',
                    'bg-red-400': pedido.payment_status === 'unpaid',
                  }"
                ></span>
                {{ getPaymentStatusLabel(pedido.payment_status || 'unpaid') }}
              </span>
            </div>
            <div class="w-full h-1.5 bg-zinc-800 rounded-full overflow-hidden mb-1">
              <div
                class="h-full transition-all duration-500 rounded-full"
                :class="{
                  'bg-green-500': pedido.payment_status === 'paid',
                  'bg-yellow-500': pedido.payment_status === 'partial',
                  'bg-red-500': pedido.payment_status === 'unpaid',
                }"
                :style="{ width: `${pedido.payment_percentage || 0}%` }"
              ></div>
            </div>
            <div class="text-[10px] text-gray-500">
              <div>Pago: R$ {{ (pedido.total_paid || 0).toFixed(2) }}</div>
              <div>Restante: R$ {{ (pedido.remaining || pedido.total).toFixed(2) }}</div>
            </div>
          </div>

          <!-- Ações -->
          <div class="flex flex-col items-end gap-1.5">
            <button
              @click="handlePayments(pedido.id)"
              class="inline-flex items-center justify-center rounded-full border border-blue-500/30 
                     bg-blue-500/5 p-1.5 text-blue-400 transition
                     hover:bg-blue-500/15 hover:text-blue-300
                     focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500/40"
              title="Gerenciar pagamentos"
            >
              <DollarSign class="h-3.5 w-3.5" />
            </button>
            <button
              @click="handleEdit(pedido.id)"
              class="inline-flex items-center justify-center rounded-full border border-[#D9A84E]/30 
                     bg-[#D9A84E]/5 p-1.5 text-[#D9A84E] transition
                     hover:bg-[#D9A84E]/15 hover:text-[#D9A84E]
                     focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#D9A84E]/40"
              title="Editar pedido"
            >
              <Pencil class="h-3.5 w-3.5" />
            </button>
            <button
              @click="openDeleteModal(pedido.id, pedido.cliente)"
              class="inline-flex items-center justify-center rounded-full border border-red-500/30 
                     bg-red-500/5 p-1.5 text-red-400 transition
                     hover:bg-red-500/15 hover:text-red-300
                     focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-red-500/40"
              title="Deletar pedido"
            >
              <Trash2 class="h-3.5 w-3.5" />
            </button>
          </div>
        </div>
      </div>

      <!-- Estado vazio (mobile) -->
      <div v-if="!filteredPedidos.length" class="py-8 px-4 text-center text-sm text-gray-500">
        <div class="mx-auto max-w-xs space-y-2">
          <p class="font-medium text-gray-300">
            Nenhum pedido encontrado.
          </p>
          <p class="text-xs text-gray-500">
            Ajuste o filtro de busca ou crie um novo pedido para começar.
          </p>
          <button
            @click="handleAdd"
            class="mt-2 inline-flex items-center justify-center gap-2 rounded-md border border-[#D9A84E] 
                   bg-white/5 px-3 py-1.5 text-xs font-medium text-gray-100
                   hover:bg-white/10 transition"
          >
            <Plus class="h-3 w-3" />
            Novo pedido
          </button>
        </div>
      </div>
    </div>
  </div>
</section>
    </div>

    <!-- Modal de delete -->
    <transition name="fade">
      <div
        v-if="deleteModal.show"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 px-4 backdrop-blur-sm"
      >
        <div
          class="w-full max-w-sm rounded-md border border-white/10 bg-zinc-950/95 p-5 shadow-2xl"
        >
          <h3 class="text-base font-semibold text-gray-100">
            Deletar pedido
          </h3>
          <p class="mt-2 text-sm text-gray-400">
            Tem certeza de que deseja deletar o pedido do cliente
            <span class="font-semibold text-gray-100">{{ deleteModal.orderName }}</span>?
            Esta ação é permanente e não poderá ser desfeita.
          </p>

          <div class="mt-5 flex justify-end gap-2">
            <button
              @click="closeDeleteModal"
              class="rounded-lg border border-white/10 bg-white/5 px-3 py-1.5 text-xs font-medium
                     text-gray-200 transition hover:bg-white/10
                     focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white/30"
            >
              Cancelar
            </button>
            <button
              @click="confirmDelete"
              :disabled="deleting"
              class="inline-flex items-center justify-center gap-2 rounded-lg bg-red-600 px-3 py-1.5 
                     text-xs font-semibold text-white shadow-sm transition
                     hover:bg-red-500 disabled:cursor-not-allowed disabled:opacity-60
                     focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-red-500/60"
            >
              <template v-if="!deleting">
                Deletar
              </template>
              <template v-else>
                <svg
                  class="h-3 w-3 animate-spin"
                  xmlns="http://www.w3.org/2000/svg"
                  fill="none"
                  viewBox="0 0 24 24"
                >
                  <circle
                    class="opacity-25"
                    cx="12"
                    cy="12"
                    r="10"
                    stroke="currentColor"
                    stroke-width="4"
                  ></circle>
                  <path
                    class="opacity-75"
                    fill="currentColor"
                    d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"
                  ></path>
                </svg>
                <span>Deletando...</span>
              </template>
            </button>
          </div>
        </div>
      </div>
    </transition>
  </div>
</template>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.15s ease-out;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>