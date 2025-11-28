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
  const invoiceElement = document.querySelector('.invoice-content') as HTMLElement;
  if (!invoiceElement) {
    alert('Erro ao exportar PDF');
    return;
  }

  try {
    // @ts-ignore
    const html2pdf = await import('html2pdf.js');
    const opt = {
      margin: [10, 10, 10, 10] as [number, number, number, number],
      filename: `pedido-${String(order.value.id).padStart(5, '0')}.pdf`,
      image: { type: 'jpeg' as const, quality: 0.98 },
      html2canvas: { scale: 2, useCORS: true },
      jsPDF: { unit: 'mm' as const, format: 'a4' as const, orientation: 'portrait' as const },
    };

    (html2pdf as any).default().set(opt).from(invoiceElement).save();
  } catch (error) {
    console.error('Erro ao carregar html2pdf:', error);
    if ((window as any).html2pdf) {
      const opt = {
        margin: [10, 10, 10, 10] as [number, number, number, number],
        filename: `pedido-${String(order.value.id).padStart(5, '0')}.pdf`,
        image: { type: 'jpeg' as const, quality: 0.98 },
        html2canvas: { scale: 2, useCORS: true },
        jsPDF: { unit: 'mm' as const, format: 'a4' as const, orientation: 'portrait' as const },
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
  <Head :title="`Pedido #${order.id} - Nota Fiscal`" />

  <AppLayout>
    <!-- Wrapper geral para centralizar -->
    <div class="flex justify-center py-6 print:py-0">
      <!-- Conteúdo a ser exportado/imprimido -->
      <div class="invoice-a4 invoice-content">
        <!-- Cabeçalho -->
        <header class="invoice-header">
          <!-- Bloco empresa (logo grande) -->
          <div class="company-block full-logo">
            <div class="company-logo-row center-logo">
              <div class="logo-wrapper large">
                <img
                  src="../../../images/image.png"
                  alt="Logomarca Comercial Pampulha"
                  class="company-logo"
                />
              </div>
            </div>
          </div>

          <!-- Bloco pedido -->
          <div class="order-block">
            <p class="order-title">NOTA FISCAL</p>
            <p class="order-number">
              {{ String(order.id).padStart(5, '0') }}
            </p>
            <div class="order-meta-group">
              <p class="order-meta">
                <span class="label">Data:</span>
                <span>{{ order.date }}</span>
              </p>
              <p class="order-meta">
                <span class="label">Pagamento:</span>
                <span>{{ order.payment || 'Não informado' }}</span>
              </p>
            </div>
          </div>
        </header>

        <!-- Cliente -->
        <section class="client-section">
          <div class="client-left">
            <p class="label-small">CLIENTE</p>
            <p class="client-name">{{ order.client_name }}</p>

            <p v-if="client?.phone" class="client-detail">
              <span class="detail-label">Telefone:</span> {{ client.phone }}
            </p>

            <p v-if="client?.address" class="client-detail">
              <span class="detail-label">Endereço:</span> {{ client.address }}
            </p>
          </div>

          <div class="client-right">
            <p class="client-note">
              Documento gerado eletronicamente.
              <br />
              Não necessita assinatura.
            </p>
          </div>
        </section>

        <!-- Itens -->
        <section class="items-section">
          <table class="items-table">
            <thead>
              <tr>
                <th class="col-code">CÓDIGO</th>
                <th class="col-desc">DESCRIÇÃO</th>
                <th class="col-unit">UN</th>
                <th class="col-qty">QTD</th>
                <th class="col-unit-price">VLR UNIT.</th>
                <th class="col-total">TOTAL</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(it, index) in items" :key="index">
                <td class="center">
                  {{ it.code || '—' }}
                </td>
                <td>
                  {{ it.product_name }}
                </td>
                <td class="center">
                  {{ it.unit === 'box' ? 'CX' : 'KG' }}
                </td>
                <td class="center">
                  {{ it.qty }}
                </td>
                <td class="right">
                  R$ {{ it.price.toFixed(2) }}
                </td>
                <td class="right item-total">
                  R$ {{ it.total.toFixed(2) }}
                </td>
              </tr>
            </tbody>
          </table>
        </section>

        <!-- Resumo -->
        <section class="summary-section">
          <div class="summary-box">
            <div class="summary-header">
              RESUMO
            </div>

            <div class="summary-body">
              <div class="summary-row">
                <span>Subtotal:</span>
                <span class="summary-value">
                  R$ {{ order.total.toFixed(2) }}
                </span>
              </div>
            </div>

            <div class="summary-total">
              <span class="summary-total-label">TOTAL</span>
              <span class="total-amount">
                R$ {{ order.total.toFixed(2) }}
              </span>
            </div>
          </div>
        </section>

        <!-- Observações -->
        <section class="notes-section">
          <p class="notes-title">Observações</p>
          <p class="notes-text">
            Preços e condições são válidos apenas para este pedido.
          </p>
        </section>

        <!-- Assinaturas -->
        <section class="signatures">
          <div class="signature-block">
            <div class="sig-line"></div>
            <p class="sig-label">ASSINATURA DO CLIENTE</p>
          </div>
          <div class="signature-block">
            <div class="sig-line"></div>
            <p class="sig-label">ASSINATURA DO REPRESENTANTE</p>
          </div>
        </section>
      </div>
    </div>

    <!-- Botões (fora da área impressa) -->
    <div class="actions print:hidden mt-4 flex justify-end gap-3 px-4 lg:px-0">
      <button @click="handleBack" class="btn btn-secondary">
        <span class="btn-icon">⬅</span>
        <span>Voltar</span>
      </button>

      <button @click="handlePrint" class="btn btn-outline">
        <span class="btn-icon">🖨</span>
        <span>Imprimir</span>
      </button>

      <button @click="handleExportPDF" class="btn btn-primary">
        <span class="btn-icon">📄</span>
        <span>Exportar PDF</span>
      </button>
    </div>
  </AppLayout>
</template>

<style scoped>
/* Layout geral */
.invoice-a4 {
  background: #ffffff;
  padding: 20px 18px;
  width: 210mm;
  margin: 0 auto;
  font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI',
    'Roboto', 'Helvetica Neue', Arial, sans-serif;
  color: #111827;
  font-size: 11px;
  line-height: 1.35;
  box-shadow: 0 5px 25px rgba(0, 0, 0, 0.08);
}

/* Cabeçalho */
.invoice-header {
  display: flex;
  justify-content: space-between;
  gap: 24px;
  padding-bottom: 12px;
  border-bottom: 1.5px solid #111827;
}

.company-block {
  flex: 1.4;
}

/* Logo grande */
.full-logo {
  display: flex;
  align-items: center;
}

.center-logo {
  width: 100%;
  display: flex;
  justify-content: flex-start;
}

.logo-wrapper.large {
  width: 250px;
  height: 110px;
  overflow: hidden;
  display: flex;
  align-items: center;
  justify-content: flex-start;
}

.company-logo {
  max-width: 100%;
  max-height: 100%;
  object-fit: contain;
}

/* Bloco Pedido */
.order-block {
  flex: 0 0 180px;
  border: 1px solid #111827;
  border-radius: 6px;
  padding: 10px 12px;
  text-align: right;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
}

.order-title {
  font-size: 11px;
  font-weight: 700;
  letter-spacing: 0.18em;
  text-transform: uppercase;
  color: #111827;
}

.order-number {
  font-size: 20px;
  font-weight: 700;
  margin: 4px 0 6px;
  letter-spacing: 0.08em;
}

.order-meta-group {
  margin-top: 2px;
}

.order-meta {
  font-size: 10px;
  color: #374151;
  line-height: 1.3;
}

.order-meta .label {
  font-weight: 600;
  margin-right: 4px;
}

/* Cliente */
.client-section {
  display: flex;
  justify-content: space-between;
  padding: 10px 0 10px;
  border-bottom: 1px solid #e5e7eb;
  margin-top: 8px;
  gap: 24px;
}

.client-left {
  flex: 2;
}

.label-small {
  font-size: 9px;
  font-weight: 700;
  letter-spacing: 0.16em;
  text-transform: uppercase;
  color: #6b7280;
  margin-bottom: 2px;
}

.client-name {
  font-size: 13px;
  font-weight: 600;
  color: #111827;
  margin-bottom: 4px;
}

.client-detail {
  font-size: 10px;
  color: #4b5563;
}

.detail-label {
  font-weight: 600;
}

.client-right {
  flex: 1;
  text-align: right;
  align-self: flex-start;
}

.client-note {
  font-size: 9px;
  color: #6b7280;
}

/* Itens */
.items-section {
  margin-top: 12px;
}

.items-table {
  width: 100%;
  border-collapse: collapse;
  border: 1px solid #d1d5db;
}

.items-table thead {
  background: #f3f4f6;
}

.items-table th,
.items-table td {
  padding: 6px 6px;
  border: 1px solid #e5e7eb;
}

.items-table th {
  font-size: 10px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.08em;
  color: #4b5563;
}

.items-table td {
  font-size: 10.5px;
  color: #111827;
}

.col-code {
  width: 11%;
}

.col-desc {
  width: 45%;
}

.col-unit {
  width: 6%;
}

.col-qty {
  width: 8%;
}

.col-unit-price {
  width: 15%;
}

.col-total {
  width: 15%;
}

.center {
  text-align: center;
}

.right {
  text-align: right;
}

.item-total {
  font-weight: 600;
}

/* Resumo */
.summary-section {
  margin-top: 14px;
  display: flex;
  justify-content: flex-end;
}

.summary-box {
  width: 240px;
  border: 1px solid #111827;
  border-radius: 6px;
  overflow: hidden;
}

.summary-header {
  font-weight: 700;
  padding: 6px 8px;
  border-bottom: 1px solid #111827;
  font-size: 10px;
  letter-spacing: 0.16em;
  text-transform: uppercase;
  background: #f9fafb;
}

.summary-body {
  padding: 4px 8px 2px 8px;
}

.summary-row {
  display: flex;
  justify-content: space-between;
  padding: 2px 0;
  font-size: 10px;
  color: #374151;
}

.summary-value {
  font-weight: 600;
}

.summary-total {
  border-top: 1px solid #111827;
  padding: 6px 8px;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.summary-total-label {
  font-size: 11px;
  font-weight: 700;
  letter-spacing: 0.16em;
  text-transform: uppercase;
}

.total-amount {
  font-weight: 700;
  font-size: 14px;
}

/* Observações */
.notes-section {
  margin-top: 14px;
  font-size: 10px;
  color: #374151;
}

.notes-title {
  font-weight: 600;
  margin-bottom: 2px;
  text-transform: uppercase;
  letter-spacing: 0.08em;
  font-size: 9.5px;
}

.notes-text {
  margin-top: 1px;
}

/* Assinaturas */
.signatures {
  margin-top: 26px;
  display: flex;
  justify-content: space-between;
  gap: 32px;
}

.signature-block {
  flex: 1;
  text-align: center;
}

.sig-line {
  width: 180px;
  border-top: 1px solid #111827;
  margin: 0 auto 4px;
}

.sig-label {
  font-size: 9px;
  font-weight: 600;
  letter-spacing: 0.12em;
  text-transform: uppercase;
  color: #4b5563;
}

/* Botões (fora da nota) */
.actions {
  margin-bottom: 12px;
}

/* Base dos botões */
.btn {
  padding: 7px 13px;
  cursor: pointer;
  font-size: 13px;
  border-radius: 6px;
  display: inline-flex;
  align-items: center;
  gap: 6px;
  border-width: 1px;
  border-style: solid;
  transition:
    background-color 0.15s ease,
    color 0.15s ease,
    border-color 0.15s ease,
    box-shadow 0.15s ease,
    transform 0.08s ease;
}

/* Ícone dentro do botão */
.btn-icon {
  font-size: 13px;
  line-height: 1;
}

/* Estados */
.btn:active {
  transform: translateY(1px);
}

/* Botão primário (PDF) */
.btn-primary {
  background: #111827;
  color: #f9fafb;
  border-color: #111827;
}

.btn-primary:hover {
  background: #000000;
  border-color: #000000;
  box-shadow: 0 0 0 1px #000000;
}

/* Botão secundário (Voltar) */
.btn-secondary {
  background: #f9fafb;
  color: #111827;
  border-color: #d1d5db;
}

.btn-secondary:hover {
  background: #e5e7eb;
  border-color: #9ca3af;
  box-shadow: 0 0 0 1px #d1d5db;
}

/* Botão contornado (Imprimir) */
.btn-outline {
  background: #ffffff;
  color: #111827;
  border-color: #9ca3af;
}

.btn-outline:hover {
  background: #111827;
  color: #ffffff;
  border-color: #111827;
  box-shadow: 0 0 0 1px #111827;
}

/* Impressão */
@media print {
  .print\:hidden {
    display: none !important;
  }

  body {
    background: #ffffff;
  }

  @page {
    size: A4;
    margin: 10mm;
  }

  .invoice-a4 {
    box-shadow: none;
    margin: 0;
    width: auto;
  }
}
</style>