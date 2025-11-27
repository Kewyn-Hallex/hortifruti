<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { dashboard } from '@/routes';

const props = defineProps<{
  order: {
    id: number;
    client_name: string;
    date: string;
    payment?: string;
    total: number;
  };
  client: {
    id: number;
    name: string;
    phone: string | null;
    address: string | null;
  } | null;
  items: Array<any>;
}>();

const order = ref(props.order);
const client = ref(props.client);
const items = ref(props.items || []);

const handlePrint = () => window.print();

const handleExportPDF = async () => {
  // Get the invoice element
  const invoiceElement = document.querySelector('.invoice-content') as HTMLElement;
  if (!invoiceElement) {
    alert('Erro ao exportar PDF');
    return;
  }

  try {
    // Import html2pdf dynamically
    // @ts-ignore - html2pdf.js doesn't have type definitions
    const html2pdf = await import('html2pdf.js');
    const opt = {
      margin: [10, 10, 10, 10] as [number, number, number, number],
      filename: `pedido-${String(order.value.id).padStart(5, '0')}.pdf`,
      image: { type: 'jpeg' as const, quality: 0.98 },
      html2canvas: { scale: 2, useCORS: true },
      jsPDF: { unit: 'mm' as const, format: 'a4' as const, orientation: 'portrait' as const }
    };

    (html2pdf as any).default().set(opt).from(invoiceElement).save();
  } catch (error) {
    console.error('Erro ao carregar html2pdf:', error);
    // Fallback: try to use window.html2pdf if available
    if ((window as any).html2pdf) {
      const opt = {
        margin: [10, 10, 10, 10] as [number, number, number, number],
        filename: `pedido-${String(order.value.id).padStart(5, '0')}.pdf`,
        image: { type: 'jpeg' as const, quality: 0.98 },
        html2canvas: { scale: 2, useCORS: true },
        jsPDF: { unit: 'mm' as const, format: 'a4' as const, orientation: 'portrait' as const }
      };
      (window as any).html2pdf().set(opt).from(invoiceElement).save();
    } else {
      alert('Biblioteca de PDF não disponível. Por favor, use a opção de imprimir.');
    }
  }
};

const handleBack = () => {
  router.visit(dashboard().url);
};
</script>

<template>
  <Head :title="`Pedido #${order.id} - Nota`" />

  <AppLayout>
    <div
      class="invoice-content p-10 max-w-4xl mx-auto mt-10 bg-[var(--bg)] text-[var(--text)] border border-[var(--border)] shadow-lg rounded-xl print:shadow-none print:border-none print:mt-0 print:p-6"
    >
      <!-- Topo / Identificação -->
      <header class="flex items-start justify-between pb-6 border-b border-[var(--border)] gap-6">
        <!-- Dados da empresa -->
        <div class="space-y-1">
          <h1 class="text-2xl font-extrabold tracking-wide text-[var(--title)] uppercase">
            Comercial Pampulha Ltda
          </h1>
          <p class="text-xs leading-tight text-[var(--muted)]">
            CNPJ: 00.000.000/0001-00
          </p>
          <p class="text-xs leading-tight text-[var(--muted)]">
            Endereço: Rua Exemplo, 123 - Bairro, Cidade/UF - CEP 00000-000
          </p>
          <p class="text-xs leading-tight text-[var(--muted)]">
            Telefone: (91) 99999-0000 &nbsp;|&nbsp; E-mail: contato@comercialpampulha.com.br
          </p>
        </div>

        <!-- Dados do pedido -->
        <div class="text-right space-y-1">
          <p class="text-xs uppercase tracking-[0.18em] text-[var(--muted)]">
            Pedido de Venda
          </p>
          <p class="text-2xl font-semibold text-[var(--title)]">
            Nº {{ String(order.id).padStart(5, '0') }}
          </p>
          <div class="mt-2 space-y-0.5 text-xs">
            <p>
              <span class="font-semibold">Data:</span>
              <span class="ml-1">{{ order.date }}</span>
            </p>
            <p>
              <span class="font-semibold">Forma de pagamento:</span>
              <span class="ml-1">{{ order.payment || 'Não informado' }}</span>
            </p>
          </div>
        </div>
      </header>

      <!-- Dados do cliente -->
      <section class="py-4 border-b border-[var(--border)] mt-2">
        <div class="flex justify-between items-start gap-6">
          <div class="flex-1 space-y-2 text-sm">
            <p class="font-semibold text-[var(--label)] uppercase tracking-wide text-xs">
              Dados do Cliente
            </p>
            <div class="space-y-1">
              <p class="font-medium text-base">
                {{ order.client_name }}
              </p>
              <template v-if="client">
                <p v-if="client.phone" class="text-[var(--text)] text-sm">
                  <span class="font-semibold">Telefone:</span> {{ client.phone }}
                </p>
                <p v-if="client.address" class="text-[var(--text)] text-sm">
                  <span class="font-semibold">Endereço:</span> {{ client.address }}
                </p>
              </template>
            </div>
          </div>

          <div class="w-52 text-xs text-right text-[var(--muted)]">
            <p class="italic">
              Documento gerado eletronicamente.<br />
              Não necessita de assinatura.
            </p>
          </div>
        </div>
      </section>

      <!-- Itens do pedido -->
      <section class="py-5">
        <table class="w-full border border-[var(--border)] text-xs rounded-md overflow-hidden">
          <thead class="bg-[var(--table-head-bg)] text-[var(--table-head-text)]">
            <tr>
              <th class="border border-[var(--border)] px-2 py-2 text-center font-semibold">
                Código
              </th>
              <th class="border border-[var(--border)] px-3 py-2 text-left font-semibold">
                Descrição
              </th>
              <th class="border border-[var(--border)] px-2 py-2 text-center font-semibold">
                Unidade
              </th>
              <th class="border border-[var(--border)] px-2 py-2 text-center font-semibold">
                Quantidade
              </th>
              <th class="border border-[var(--border)] px-2 py-2 text-right font-semibold">
                Vlr. Unitário (R$)
              </th>
              <th class="border border-[var(--border)] px-2 py-2 text-right font-semibold">
                Vlr. Total (R$)
              </th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="(it, index) in items"
              :key="it.id ?? index"
              class="odd:bg-[var(--row-odd-bg)] even:bg-[var(--row-even-bg)]"
            >
              <td class="border border-[var(--border)] text-center px-2 py-2 align-middle">
                {{ it.code || '—' }}
              </td>
              <td class="border border-[var(--border)] px-3 py-2 align-middle">
                <div class="font-medium text-[var(--text)]">
                  {{ it.product_name }}
                </div>
                <!-- Se quiser, pode exibir observações do item aqui -->
              </td>
              <td class="border border-[var(--border)] text-center px-2 py-2 align-middle">
                {{ it.unit === 'box' ? 'CX' : 'KG' }}
              </td>
              <td class="border border-[var(--border)] text-center px-2 py-2 align-middle">
                {{ it.qty }}
              </td>
              <td class="border border-[var(--border)] text-right px-2 py-2 align-middle">
                {{ `R$ ${it.price.toFixed(2)}` }}
              </td>
              <td class="border border-[var(--border)] text-right px-2 py-2 align-middle font-semibold">
                {{ `R$ ${it.total.toFixed(2)}` }}
              </td>
            </tr>

            <tr v-if="!items.length">
              <td colspan="6" class="text-center text-[var(--muted)] py-6">
                Nenhum item adicionado a este pedido.
              </td>
            </tr>
          </tbody>
        </table>
      </section>

      <!-- Resumo financeiro -->
      <section class="mt-2 flex justify-end">
        <div class="w-72 text-sm border border-[var(--border)] rounded-md overflow-hidden">
          <div class="flex justify-between px-3 py-2 bg-[var(--summary-bg)] border-b border-[var(--border)]">
            <span class="font-semibold text-[var(--label)] uppercase text-xs tracking-wide">
              Resumo do Pedido
            </span>
          </div>

          <div class="px-3 py-2 space-y-1">
            <!-- Caso tenha desconto/frete, adicionar aqui -->
            <div class="flex justify-between">
              <span class="text-[var(--muted)]">Subtotal</span>
              <span class="font-medium">
                {{ `R$ ${order.total.toFixed(2)}` }}
              </span>
            </div>
          </div>

          <div class="px-3 py-2 bg-[var(--summary-total-bg)] border-t border-[var(--border)]">
            <div class="flex justify-between items-center">
              <span class="font-bold text-[var(--label)] uppercase text-xs tracking-wide">
                Total do Pedido
              </span>
              <span class="font-extrabold text-lg text-[var(--title)]">
                {{ `R$ ${order.total.toFixed(2)}` }}
              </span>
            </div>
          </div>
        </div>
      </section>

      <!-- Observações e assinatura -->
      <section class="mt-8 text-xs text-[var(--muted)]">
        <p class="mb-4">
          <span class="font-semibold">Observações:</span><br />
          Os preços e condições de pagamento aqui descritos são válidos somente para este pedido.
        </p>

        <div class="flex justify-between mt-10 print:mt-6 gap-10">
          <div class="flex-1 text-center">
            <div class="border-t border-[var(--border)] pt-1"></div>
            <p class="mt-1 text-[var(--muted)] text-[0.65rem] uppercase tracking-wide">
              Assinatura do Cliente
            </p>
          </div>
          <div class="flex-1 text-center">
            <div class="border-t border-[var(--border)] pt-1"></div>
            <p class="mt-1 text-[var(--muted)] text-[0.65rem] uppercase tracking-wide">
              Assinatura do Representante
            </p>
          </div>
        </div>
      </section>

      <!-- Botões (não imprime) -->
      <div class="flex gap-3 mt-8 print:hidden justify-end">
        <button
          @click="handleBack"
          class="px-4 py-2 border border-[var(--border)] rounded-md text-[var(--text)] text-sm hover:bg-[var(--border)] hover:bg-opacity-15 transition-colors"
        >
          ← Voltar ao Dashboard
        </button>

        <button
          @click="handlePrint"
          class="px-6 py-2 rounded-md text-sm font-semibold text-black bg-[#D9A84E] hover:brightness-105 active:brightness-95 transition-all shadow-sm"
        >
          Imprimir
        </button>

        <button
          @click="handleExportPDF"
          class="px-6 py-2 rounded-md text-sm font-semibold text-white bg-gray-700 hover:bg-gray-600 active:bg-gray-800 transition-all shadow-sm"
        >
          Exportar PDF
        </button>
      </div>
    </div>
  </AppLayout>
</template>

<style scoped>
/* Tipografia empresarial ( Arial Narrow se houver, senão Arial ) */
.invoice-content {
  font-family: "Arial Narrow", Arial, Helvetica, sans-serif;
  color: #111;
  background: #fff;
  padding: 18px;
  max-width: 210mm; /* largura A4 */
  box-shadow: none;
  border: none;
}

/* Remove arredondamento e sombras para ficar fiel ao papel */
.invoice-a4 {
  background: #fff;
  border: 1px solid #000;
  padding: 18px;
  margin-bottom: 20px;
}

/* Header estilo bloco */
.invoice-header {
  display: flex;
  justify-content: space-between;
  gap: 20px;
  padding-bottom: 10px;
  border-bottom: 2px solid #000;
}

.company-block {
  max-width: 60%;
}

.company-name {
  font-size: 20px;
  font-weight: 700;
  color: #000;
  letter-spacing: 0.6px;
  margin: 0 0 6px 0;
}

.company-meta {
  font-size: 11px;
  color: #000;
  line-height: 1.1;
}

/* Order block */
.order-block {
  text-align: right;
  min-width: 170px;
}

.order-title {
  font-size: 11px;
  font-weight: 700;
  letter-spacing: 1px;
}

.order-number {
  font-size: 20px;
  font-weight: 700;
  margin-top: 6px;
  color: #000;
}

.order-meta {
  font-size: 11px;
  color: #000;
  margin-top: 6px;
}

/* Client */
.client-section {
  display: flex;
  justify-content: space-between;
  gap: 20px;
  padding: 10px 0;
  border-bottom: 1px solid #000;
}

.client-left { flex: 1; }
.client-right { width: 200px; text-align: right; }

/* Labels */
.label.small { font-size: 10px; font-weight: 700; color: #000; }

/* Items table */
.items-section { margin-top: 12px; }

.items-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 12px;
}

.items-table thead th {
  border: 1px solid #000;
  padding: 6px 8px;
  font-weight: 700;
  font-size: 11px;
  text-transform: uppercase;
}

.items-table td {
  border: 1px solid #000;
  padding: 6px 8px;
  vertical-align: middle;
  font-size: 12px;
}

.items-table .col-code { width: 9%; text-align: center; }
.items-table .col-desc { width: 44%; text-align: left; }
.items-table .col-unit { width: 10%; text-align: center; }
.items-table .col-qty { width: 10%; text-align: center; }
.items-table .col-unit-price { width: 13%; text-align: right; }
.items-table .col-total { width: 14%; text-align: right; }

.cell.center { text-align: center; }
.cell.right { text-align: right; }
.font-semibold { font-weight: 700; }

/* small & muted */
.small { font-size: 11px; }
.muted { color: #333; }

/* Summary */
.summary-section { display: flex; justify-content: flex-end; margin-top: 12px; }
.summary-box {
  width: 260px;
  border: 1px solid #000;
  font-size: 12px;
  background: transparent;
}

.summary-header {
  padding: 8px 10px;
  border-bottom: 1px solid #000;
  font-weight: 700;
  text-transform: uppercase;
  font-size: 11px;
}

.summary-body { padding: 8px 10px; }
.summary-row { display: flex; justify-content: space-between; padding: 4px 0; }

.summary-total {
  padding: 8px 10px;
  border-top: 1px solid #000;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.total-amount { font-weight: 800; }

/* Notes and signatures */
.notes-section { margin-top: 16px; font-size: 12px; color: #000; }
.signatures {
  display: flex;
  gap: 30px;
  margin-top: 18px;
}
.signature-block { flex: 1; text-align: center; }
.sig-line { border-top: 1px solid #000; height: 1px; width: 80%; margin: 0 auto 6px; }
.sig-label { font-size: 10px; color: #333; text-transform: uppercase; letter-spacing: 0.6px; }

/* Actions */
.actions {
  display: flex;
  gap: 10px;
  justify-content: flex-end;
  margin-top: 14px;
}
.btn {
  padding: 8px 12px;
  border: 1px solid #000;
  background: #fff;
  font-size: 13px;
  cursor: pointer;
}
.btn.primary {
  background: #000;
  color: #fff;
  border-color: #000;
}
.btn.secondary {
  background: #444;
  color: #fff;
  border-color: #444;
}
.btn.ghost { background: transparent; }

/* Print rules - garantir A4 e cores reais */
@media print {
  .print\:hidden { display: none !important; }
  body { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
  .invoice-content, .invoice-a4 {
    box-shadow: none !important;
    border: none !important;
    margin: 0;
    padding: 0;
    width: auto;
  }
  @page { size: A4; margin: 18mm; }
}
</style>