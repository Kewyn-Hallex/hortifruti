<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { Form } from '@inertiajs/vue3';
import { ArrowLeft, DollarSign, History, Check, X, Trash2 } from 'lucide-vue-next';
import { dashboard } from '@/routes';

const props = defineProps<{
  order: {
    id: number;
    client_name: string;
    date: string;
    total: number;
    total_paid: number;
    remaining_balance: number;
    payment_percentage: number;
    payment_status: 'paid' | 'partial' | 'unpaid';
  };
  payments: Array<{
    id: number;
    amount: number;
    balance_after: number;
    notes: string | null;
    created_at: string;
  }>;
}>();

const page = usePage();
const successMessage = computed(() => (page.props.flash as { success?: string })?.success);

const paymentAmount = ref('');
const paymentNotes = ref('');
const submitting = ref(false);

const statusConfig = computed(() => {
  const status = props.order.payment_status;
  if (status === 'paid') {
    return {
      color: 'green',
      bgColor: 'bg-green-500/10',
      borderColor: 'border-green-500/30',
      textColor: 'text-green-400',
      label: 'Totalmente Pago',
    };
  } else if (status === 'partial') {
    return {
      color: 'yellow',
      bgColor: 'bg-yellow-500/10',
      borderColor: 'border-yellow-500/30',
      textColor: 'text-yellow-400',
      label: 'Pagamento Parcial',
    };
  } else {
    return {
      color: 'red',
      bgColor: 'bg-red-500/10',
      borderColor: 'border-red-500/30',
      textColor: 'text-red-400',
      label: 'Não Pago',
    };
  }
});

const handleSubmit = () => {
  if (!paymentAmount.value || Number(paymentAmount.value) <= 0) {
    return;
  }

  const maxAmount = props.order.remaining_balance;
  const amount = Number(paymentAmount.value);

  if (amount > maxAmount) {
    alert(`O valor não pode exceder o saldo restante de R$ ${maxAmount.toFixed(2)}`);
    return;
  }

  submitting.value = true;
  router.post(`/pedidos/${props.order.id}/payments`, {
    amount: amount,
    notes: paymentNotes.value || null,
  }, {
    onSuccess: () => {
      paymentAmount.value = '';
      paymentNotes.value = '';
      submitting.value = false;
    },
    onError: (errors) => {
      console.error('Erro ao registrar pagamento', errors);
      alert(errors.amount || 'Erro ao registrar pagamento');
      submitting.value = false;
    },
  });
};

const handleDeletePayment = (paymentId: number) => {
  if (!confirm('Tem certeza que deseja remover este pagamento?')) {
    return;
  }

  router.delete(`/pedidos/${props.order.id}/payments/${paymentId}`, {
    onSuccess: () => {
      // Success handled by redirect
    },
    onError: () => {
      alert('Erro ao remover pagamento');
    },
  });
};

const handleBack = () => {
  router.visit(dashboard().url);
};
</script>

<template>
  <Head :title="`Pagamentos - Pedido #${order.id}`" />

  <AppLayout>
    <div class="p-4 md:p-6 max-w-5xl mx-auto w-full">
      <!-- Header -->
      <div class="mb-6">
        <button
          @click="handleBack"
          class="mb-4 inline-flex items-center gap-2 text-sm text-gray-400 hover:text-gray-200 transition"
        >
          <ArrowLeft class="w-4 h-4" />
          Voltar ao Dashboard
        </button>
        <h1 class="text-2xl md:text-3xl font-semibold tracking-tight text-white">
          Controle de Pagamentos
        </h1>
        <p class="text-gray-400 mt-1 text-sm md:text-base">
          Pedido #{{ order.id }} - {{ order.client_name }}
        </p>
      </div>

      <!-- Success Message -->
      <Transition name="slide-down">
        <div
          v-if="successMessage"
          class="mb-6 flex items-center gap-2 rounded-lg bg-green-50 dark:bg-green-900/20 p-4 text-sm font-medium text-green-700 dark:text-green-400 border border-green-200 dark:border-green-800"
          role="status"
          aria-live="polite"
        >
          <Check class="h-4 w-4" aria-hidden="true" />
          <span>{{ successMessage }}</span>
        </div>
      </Transition>

      <!-- Resumo do Pedido -->
      <div class="rounded-xl border border-neutral-800 bg-zinc-900/80 p-4 md:p-6 mb-6 shadow-sm">
        <h2 class="font-semibold text-gray-100 text-base md:text-lg mb-4">
          Resumo do Pedido
        </h2>

        <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
          <div class="flex flex-col">
            <span class="text-xs text-gray-400 mb-1">Total do Pedido</span>
            <span class="text-xl font-bold text-white">R$ {{ order.total.toFixed(2) }}</span>
          </div>
          <div class="flex flex-col">
            <span class="text-xs text-gray-400 mb-1">Total Pago</span>
            <span class="text-xl font-bold text-green-400">R$ {{ order.total_paid.toFixed(2) }}</span>
          </div>
          <div class="flex flex-col">
            <span class="text-xs text-gray-400 mb-1">Saldo Restante</span>
            <span class="text-xl font-bold" :class="order.remaining_balance > 0 ? 'text-red-400' : 'text-green-400'">
              R$ {{ order.remaining_balance.toFixed(2) }}
            </span>
          </div>
          <div class="flex flex-col">
            <span class="text-xs text-gray-400 mb-1">Status</span>
            <span
              class="inline-flex items-center gap-1.5 px-2 py-1 rounded-full text-xs font-medium"
              :class="[
                statusConfig.bgColor,
                statusConfig.borderColor,
                statusConfig.textColor,
                'border'
              ]"
            >
              <span
                class="h-2 w-2 rounded-full"
                :class="{
                  'bg-green-400': order.payment_status === 'paid',
                  'bg-yellow-400': order.payment_status === 'partial',
                  'bg-red-400': order.payment_status === 'unpaid',
                }"
              ></span>
              {{ statusConfig.label }}
            </span>
          </div>
        </div>

        <!-- Barra de Progresso -->
        <div class="mt-6">
          <div class="flex items-center justify-between mb-2">
            <span class="text-xs text-gray-400">Progresso do Pagamento</span>
            <span class="text-xs font-medium" :class="statusConfig.textColor">
              {{ order.payment_percentage.toFixed(1) }}%
            </span>
          </div>
          <div class="w-full h-3 bg-zinc-800 rounded-full overflow-hidden">
            <div
              class="h-full transition-all duration-500 rounded-full"
              :class="{
                'bg-green-500': order.payment_status === 'paid',
                'bg-yellow-500': order.payment_status === 'partial',
                'bg-red-500': order.payment_status === 'unpaid',
              }"
              :style="{ width: `${order.payment_percentage}%` }"
            ></div>
          </div>
        </div>
      </div>

      <!-- Registrar Novo Pagamento -->
      <div class="rounded-xl border border-neutral-800 bg-zinc-900/80 p-4 md:p-6 mb-6 shadow-sm">
        <h2 class="font-semibold text-gray-100 text-base md:text-lg mb-4 flex items-center gap-2">
          <DollarSign class="w-5 h-5 text-[#D9A84E]" />
          Registrar Novo Pagamento
        </h2>

        <div class="space-y-4">
          <div>
            <label class="block text-xs font-medium text-gray-400 mb-1">
              Valor do Pagamento *
            </label>
            <div class="relative">
              <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-sm text-gray-400">
                R$
              </span>
              <input
                v-model="paymentAmount"
                type="number"
                step="0.01"
                min="0.01"
                :max="order.remaining_balance"
                placeholder="0,00"
                class="w-full rounded-lg border border-neutral-700 bg-zinc-950/70 px-3 pl-10 py-2.5 text-sm text-white outline-none focus:ring-2 focus:ring-[#D9A84E] focus:border-[#D9A84E]"
              />
            </div>
            <p class="mt-1 text-xs text-gray-500">
              Saldo restante: R$ {{ order.remaining_balance.toFixed(2) }}
            </p>
          </div>

          <div>
            <label class="block text-xs font-medium text-gray-400 mb-1">
              Observações (opcional)
            </label>
            <textarea
              v-model="paymentNotes"
              rows="2"
              placeholder="Ex: Pagamento via Pix, parcela 1/3..."
              class="w-full rounded-lg border border-neutral-700 bg-zinc-950/70 px-3 py-2.5 text-sm text-white placeholder-gray-500 outline-none focus:ring-2 focus:ring-[#D9A84E] focus:border-[#D9A84E]"
            ></textarea>
          </div>

          <button
            @click="handleSubmit"
            :disabled="submitting || !paymentAmount || Number(paymentAmount) <= 0 || Number(paymentAmount) > order.remaining_balance"
            class="w-full sm:w-auto px-6 py-2.5 rounded-lg text-sm font-semibold text-white
                   bg-gradient-to-r from-[#D9A84E] to-[#B58737]
                   hover:shadow-lg transition-all duration-300
                   focus:ring-4 focus:ring-[#D9A84E]/40
                   disabled:opacity-50 disabled:cursor-not-allowed
                   flex items-center justify-center gap-2"
          >
            <template v-if="!submitting">
              <Check class="w-4 h-4" />
              Registrar Pagamento
            </template>
            <template v-else>
              <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z" />
              </svg>
              Registrando...
            </template>
          </button>
        </div>
      </div>

      <!-- Histórico de Pagamentos -->
      <div class="rounded-xl border border-neutral-800 bg-zinc-900/80 p-4 md:p-6 shadow-sm">
        <h2 class="font-semibold text-gray-100 text-base md:text-lg mb-4 flex items-center gap-2">
          <History class="w-5 h-5 text-[#D9A84E]" />
          Histórico de Pagamentos
        </h2>

        <div v-if="payments.length === 0" class="text-center py-8 text-gray-400">
          <p class="text-sm">Nenhum pagamento registrado ainda.</p>
        </div>

        <div v-else class="space-y-3">
          <div
            v-for="payment in payments"
            :key="payment.id"
            class="flex items-center justify-between p-4 rounded-lg border border-neutral-700 bg-zinc-950/50 hover:bg-zinc-950/70 transition"
          >
            <div class="flex-1">
              <div class="flex items-center gap-3 mb-1">
                <span class="text-sm font-semibold text-white">
                  R$ {{ payment.amount.toFixed(2) }}
                </span>
                <span class="text-xs text-gray-400">
                  {{ payment.created_at }}
                </span>
              </div>
              <div class="text-xs text-gray-500">
                Saldo após: R$ {{ payment.balance_after.toFixed(2) }}
              </div>
              <div v-if="payment.notes" class="text-xs text-gray-400 mt-1 italic">
                {{ payment.notes }}
              </div>
            </div>
            <button
              @click="handleDeletePayment(payment.id)"
              class="p-2 text-red-500 hover:text-red-400 hover:bg-red-500/10 rounded-lg transition"
              title="Remover pagamento"
            >
              <Trash2 class="w-4 h-4" />
            </button>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<style scoped>
.slide-down-enter-active,
.slide-down-leave-active {
  transition: all 0.3s ease;
}

.slide-down-enter-from {
  transform: translateY(-10px);
  opacity: 0;
}

.slide-down-leave-to {
  transform: translateY(-10px);
  opacity: 0;
}
</style>

