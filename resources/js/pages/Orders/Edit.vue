<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import { Check, Trash } from 'lucide-vue-next';

const props = defineProps<{ 
  order: { id: number; client_name: string; date: string; payment: string | null; total: number },
  items: Array<{ id: number; productId: number; product_name: string; unit: string; price: number; kgPerBox?: number; kg_per_box?: number | null; qty: number; total: number }>,
  fruits: Array<{ id: number; name: string; price: number; price_box: number; price_kg: number; price_bunch: number }>
}>();

const clientName = ref(props.order.client_name);
const clientId = ref<number | null>(null);
const date = ref(props.order.date);
const payment = ref(props.order.payment || 'Pix');

// Client autocomplete
const clientSearchQuery = ref(props.order.client_name);
const clientSuggestions = ref<Array<{ id: number; name: string; phone: string | null; address: string | null }>>([]);
const showSuggestions = ref(false);
const searchTimeout = ref<ReturnType<typeof setTimeout> | null>(null);

const searchClients = async (query: string) => {
    if (query.length < 2) {
        clientSuggestions.value = [];
        showSuggestions.value = false;
        return;
    }

    try {
        const res = await fetch(`/api/clients/search?q=${encodeURIComponent(query)}`);
        if (res.ok) {
            const data = await res.json();
            clientSuggestions.value = data;
            showSuggestions.value = data.length > 0;
        }
    } catch (e) {
        console.error('Erro ao buscar clientes', e);
    }
};

const onClientInput = () => {
    if (searchTimeout.value) {
        clearTimeout(searchTimeout.value);
    }
    searchTimeout.value = setTimeout(() => {
        searchClients(clientSearchQuery.value);
    }, 300);

    // Atualiza clientName quando o usuário digita
    clientName.value = clientSearchQuery.value;

    // Se o usuário está digitando, limpa a seleção
    if (clientSearchQuery.value !== clientName.value) {
        clientId.value = null;
    }
};

const selectClient = (client: { id: number; name: string; phone: string | null; address: string | null }) => {
    clientId.value = client.id;
    clientName.value = client.name;
    clientSearchQuery.value = client.name;
    showSuggestions.value = false;
};

const onClientBlur = () => {
    // Delay para permitir clique na sugestão
    setTimeout(() => {
        showSuggestions.value = false;
    }, 200);
};

// map incoming fruits for quick lookup
const fruitMap = ref<Record<number, { id: number; name: string; price: number; price_box: number; price_kg: number; price_bunch: number }>>({});
watch(
    () => props.fruits,
    (v) => {
        fruitMap.value = {};
        (v || []).forEach((f) => (fruitMap.value[f.id] = f));
    },
    { immediate: true },
);

// Product autocomplete for each item
type ProductSuggestion = { id: number; name: string; price_box: number; price_kg: number; price_bunch: number };
const productSearchQueries = ref<Record<number, string>>({});
const productSuggestions = ref<Record<number, ProductSuggestion[]>>({});
const showProductSuggestions = ref<Record<number, boolean>>({});
const productSearchTimeouts = ref<Record<number, ReturnType<typeof setTimeout> | null>>({});

const searchProducts = (itemId: number, query: string) => {
    if (!query || query.length < 1) {
        productSuggestions.value[itemId] = [...props.fruits];
        return;
    }

    const lowerQuery = query.toLowerCase();
    productSuggestions.value[itemId] = props.fruits.filter(f =>
        f.name.toLowerCase().includes(lowerQuery)
    );
};

const onProductInput = (item: Item) => {
    const query = productSearchQueries.value[item.id] || '';

    const timeout = productSearchTimeouts.value[item.id];
    if (timeout) {
        clearTimeout(timeout);
    }

    productSearchTimeouts.value[item.id] = setTimeout(() => {
        searchProducts(item.id, query);
        showProductSuggestions.value[item.id] = true;
    }, 200);
};

const selectProduct = (item: Item, product: ProductSuggestion) => {
    item.productId = product.id;
    productSearchQueries.value[item.id] = product.name;
    showProductSuggestions.value[item.id] = false;
    // Update fruitMap if needed
    if (!fruitMap.value[product.id]) {
        fruitMap.value[product.id] = {
            ...product,
            price: 0 // price is not used, but required by fruitMap type
        };
    }
    onProductChange(item);
};

const onProductBlur = (itemId: number) => {
    setTimeout(() => {
        showProductSuggestions.value[itemId] = false;
    }, 200);
};

type Unit = 'box' | 'kg' | 'bunch';
type Item = { id: number; productId: number | null; qty: number; price: number; unit: Unit; kgPerBox: number };

// Initialize items from props
const items = ref<Item[]>(props.items.map((it, index) => ({
    id: it.id || Date.now() + index,
    productId: it.productId || null,
    qty: it.qty || 1,
    price: it.price || 0,
    unit: (it.unit as Unit) || 'box',
    kgPerBox: (it as any).kgPerBox || (it as any).kg_per_box || 20,
})));

// Initialize product search queries for existing items
watch(() => props.fruits, () => {
    items.value.forEach(item => {
        if (item.productId) {
            const fruit = props.fruits.find(f => f.id === item.productId);
            if (fruit) {
                productSearchQueries.value[item.id] = fruit.name;
            }
        }
        if (!productSearchQueries.value[item.id]) {
            productSearchQueries.value[item.id] = '';
        }
        if (!productSuggestions.value[item.id]) {
            productSuggestions.value[item.id] = [...props.fruits];
        }
    });
}, { immediate: true });

const addItem = () => {
    const newId = Date.now();
    items.value.push({ id: newId, productId: null, qty: 1, price: 0, unit: 'box', kgPerBox: 20 });
    productSearchQueries.value[newId] = '';
    productSuggestions.value[newId] = [...props.fruits];
    showProductSuggestions.value[newId] = false;
}

const removeItem = (index: number) => {
    items.value.splice(index, 1);
}

const orderTotal = computed(() => {
    return items.value.reduce((sum, it) => {
        const unitPrice = Number(it.price || 0);
        if (it.unit === 'box') {
            // boxes: qty must be integer, price is box price
            const qty = Math.max(0, Math.floor(Number(it.qty || 0)));
            return sum + qty * unitPrice;
        } else if (it.unit === 'bunch') {
            // bunch: qty must be integer, price is bunch price
            const qty = Math.max(0, Math.floor(Number(it.qty || 0)));
            return sum + qty * unitPrice;
        } else {
            // kg: price per kg
            const qty = Number(it.qty || 0);
            return sum + qty * unitPrice;
        }
    }, 0);
});

function normalizeQtyForUnit(item: Item) {
    if (item.unit === 'box' || item.unit === 'bunch') {
        // force integer for box and bunch
        item.qty = Math.max(1, Math.floor(Number(item.qty || 0)));
    } else {
        // allow fractional for kg, keep at least 0
        item.qty = Number(item.qty || 0);
    }
}

function onUnitChange(item: Item) {
    // When switching units, normalize quantity to allowed type
    if (item.unit === 'box' || item.unit === 'bunch') {
        // convert any fractional qty to integer (floor)
        item.qty = Math.max(1, Math.floor(Number(item.qty || 0)));
    }
    // Update price based on selected unit
    onProductChange(item);
}

const handleCancel = () => {
    history.back();
}

const submitting = ref(false);

const handleFinalize = async () => {
    // build payload matching backend expectations
    const payload = {
        clientId: clientId.value,
        clientName: clientName.value,
        date: date.value,
        payment: payment.value,
        items: items.value.map(i => ({
            productId: i.productId,
            unit: i.unit,
            qty: i.unit === 'box' || i.unit === 'bunch' ? Math.max(0, Math.floor(Number(i.qty || 0))) : Number(i.qty || 0),
            price: Number(i.price || 0),
            kgPerBox: Number(i.kgPerBox || 0) || null,
        })),
    };

    submitting.value = true;
    router.put(`/pedidos/${props.order.id}`, payload as any, {
        onStart: () => {
            submitting.value = true;
        },
        onSuccess: () => {
            // Router will follow the redirect returned by the server to the invoice page.
        },
        onError: (errors) => {
            console.error('Erro ao atualizar pedido', errors);
            alert('Erro ao atualizar pedido');
        },
        onFinish: () => {
            submitting.value = false;
        }
    });
}

// called when a product is selected for an item to update the price
const onProductChange = async (item: Item) => {
    if (!item.productId) {
        item.price = 0;
        return;
    }

    const local = fruitMap.value[item.productId];
    let fruitData = local;

    // Fetch latest data if not in map
    if (!local) {
        try {
            const res = await fetch(`/api/fruits/${item.productId}`);
            if (res.ok) {
                const data = await res.json();
                fruitMap.value[item.productId] = data;
                fruitData = data;
            } else {
                item.price = 0;
                return;
            }
        } catch (e) {
            item.price = 0;
            return;
        }
    }

    // Set price based on selected unit (only if price hasn't been manually edited)
    // We'll check if price is 0 or matches the default to auto-update
    const currentPrice = item.price;
    let defaultPrice = 0;

    if (item.unit === 'box') {
        defaultPrice = Number(fruitData.price_box || 0);
    } else if (item.unit === 'kg') {
        defaultPrice = Number(fruitData.price_kg || 0);
    } else if (item.unit === 'bunch') {
        defaultPrice = Number(fruitData.price_bunch || 0);
    }

    // Only auto-update if price is 0 or matches previous default
    if (currentPrice === 0 || Math.abs(currentPrice - defaultPrice) < 0.01) {
        item.price = defaultPrice;
    }

    // ensure kgPerBox has a sensible default if not set yet (only needed for kg calculations)
    if (!item.kgPerBox) item.kgPerBox = 20;
}

// Update price when manually edited
const onPriceChange = (item: Item) => {
    // Price is already updated via v-model, just ensure it's a valid number
    item.price = Math.max(0, Number(item.price || 0));
}

function lineTotal(item: Item) {
    const unitPrice = Number(item.price || 0);
    if (item.unit === 'box' || item.unit === 'bunch') {
        const qty = Math.max(0, Math.floor(Number(item.qty || 0)));
        return qty * unitPrice;
    } else {
        // kg: price is already per kg
        const qty = Number(item.qty || 0);
        return qty * unitPrice;
    }
}
</script>

<template>

    <Head title="Editar Pedido" />

    <AppLayout>
        <div class="p-4 md:p-6 max-w-5xl mx-auto w-full">

            <!-- Título da página -->
            <div class="mb-6">
                <h1 class="text-2xl md:text-3xl font-semibold tracking-tight text-white">
                    Editar Pedido #{{ order.id }}
                </h1>
                <p class="text-gray-400 mt-1 text-sm md:text-base">
                    Atualize os dados e itens do pedido.
                </p>
            </div>

            <!-- Informações do Pedido -->
            <div class="rounded-xl border border-neutral-800 bg-zinc-900/80 p-4 md:p-6 mb-6 shadow-sm">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="font-semibold text-gray-100 text-base md:text-lg">
                        Informações do Pedido
                    </h2>
                </div>

                <div class="grid gap-4 md:gap-5 grid-cols-1 sm:grid-cols-2 md:grid-cols-3">
                    <!-- Cliente -->
                    <div class="relative">
                        <label class="block text-xs font-medium text-gray-400 mb-1">
                            Cliente <span class="text-red-500">*</span>
                        </label>
                        <input v-model="clientSearchQuery" @input="onClientInput" @focus="onClientInput"
                            @blur="onClientBlur" type="text" placeholder="Digite o nome do cliente..."
                            class="w-full rounded-lg border border-neutral-700 bg-zinc-950/70 px-3 py-2.5 text-sm text-white placeholder-gray-500 outline-none focus:ring-2 focus:ring-[#D9A84E] focus:border-[#D9A84E]" />

                        <!-- Suggestions dropdown -->
                        <div v-if="showSuggestions && clientSuggestions.length > 0"
                            class="absolute z-50 mt-1 w-full rounded-lg border border-neutral-700 bg-zinc-900 shadow-lg max-h-60 overflow-auto">
                            <div v-for="client in clientSuggestions" :key="client.id"
                                @mousedown.prevent="selectClient(client)"
                                class="px-3 py-2 text-sm text-white hover:bg-zinc-800 cursor-pointer border-b border-neutral-800 last:border-b-0">
                                <div class="font-medium">{{ client.name }}</div>
                                <div v-if="client.phone" class="text-xs text-gray-400 mt-0.5">
                                    {{ client.phone }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Data -->
                    <div>
                        <label class="block text-xs font-medium text-gray-400 mb-1">
                            Data
                        </label>
                        <input v-model="date" type="date"
                            class="w-full rounded-lg border border-neutral-700 bg-zinc-950/70 px-3 py-2.5 text-sm text-white outline-none focus:ring-2 focus:ring-[#D9A84E] focus:border-[#D9A84E]" />
                    </div>

                    <!-- Pagamento -->
                    <div>
                        <label class="block text-xs font-medium text-gray-400 mb-1">
                            Pagamento
                        </label>
                        <select v-model="payment"
                            class="w-full rounded-lg border border-neutral-700 bg-zinc-950/70 px-3 py-2.5 text-sm text-white outline-none focus:ring-2 focus:ring-[#D9A84E] focus:border-[#D9A84E]">
                            <option>Pix</option>
                            <option>Dinheiro</option>
                            <option>Cartão</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="rounded-xl border border-neutral-800 bg-zinc-900/80 p-4 md:p-6 mb-6 shadow-sm">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="font-semibold text-gray-100 text-base md:text-lg">
                        Itens do Pedido
                    </h2>
                </div>

                <div class="space-y-4">

                    <div class="hidden md:grid grid-cols-12 gap-4 text-xs font-medium text-gray-400 px-2 pb-1">
                        <div class="col-span-4">Produto</div>
                        <div class="col-span-2">Modo</div>
                        <div class="col-span-2 text-center">Qtd</div>
                        <div class="col-span-2 text-center">Preço Unit.</div>
                        <div class="col-span-2 text-right">Total</div>
                    </div>

                    <div v-for="(item, index) in items" :key="item.id"
                        class="grid grid-cols-12 gap-3 md:gap-4 items-center px-3 py-3 rounded-lg">

                        <div class="col-span-12 md:col-span-4 relative">
                            <label class="md:hidden block text-[11px] font-medium text-gray-400 mb-1">
                                Produto
                            </label>
                            <input :value="productSearchQueries[item.id] || (item.productId ? props.fruits.find(f => f.id === item.productId)?.name || '' : '')"
                                @input="(e) => { productSearchQueries[item.id] = (e.target as HTMLInputElement).value; onProductInput(item); }"
                                @focus="onProductInput(item)" @blur="onProductBlur(item.id)" type="text"
                                placeholder="Digite o nome do produto..."
                                class="w-full rounded-lg border border-neutral-700 bg-zinc-900 px-3 py-2.5 text-sm text-white placeholder-gray-500 outline-none focus:ring-2 focus:ring-[#D9A84E] focus:border-[#D9A84E]" />

                            <div v-if="showProductSuggestions[item.id] && (productSuggestions[item.id] || []).length > 0"
                                class="absolute z-50 mt-1 w-full rounded-lg border border-neutral-700 bg-zinc-900 shadow-lg max-h-60 overflow-auto">
                                <div v-for="product in productSuggestions[item.id]" :key="product.id"
                                    @mousedown.prevent="selectProduct(item, product)"
                                    class="px-3 py-2 text-sm text-white hover:bg-zinc-800 cursor-pointer border-b border-neutral-800 last:border-b-0">
                                    <div class="font-medium">{{ product.name }}</div>
                                </div>
                            </div>
                        </div>

                        <div class="col-span-4 md:col-span-2">
                            <label class="md:hidden block text-[11px] font-medium text-gray-400 mb-1">
                                Modo
                            </label>
                            <select v-model="item.unit" @change="onUnitChange(item)"
                                class="w-full rounded-lg border border-neutral-700 bg-zinc-900 px-2.5 py-2.5 text-xs md:text-sm text-white outline-none focus:ring-2 focus:ring-[#D9A84E] focus:border-[#D9A84E]">
                                <option value="box">Caixa</option>
                                <option value="kg">Kg</option>
                                <option value="bunch">Maço</option>
                            </select>
                        </div>

                        <div class="col-span-4 md:col-span-2">
                            <label class="md:hidden block text-[11px] font-medium text-gray-400 mb-1">
                                Qtd
                            </label>
                            <input v-model.number="item.qty" :step="item.unit === 'box' || item.unit === 'bunch' ? 1 : 0.01"
                                :min="item.unit === 'box' || item.unit === 'bunch' ? 1 : 0.01" @change="normalizeQtyForUnit(item)" type="number"
                                class="w-full rounded-lg border border-neutral-700 bg-zinc-900 px-2.5 py-2.5 text-sm text-white text-center outline-none focus:ring-2 focus:ring-[#D9A84E] focus:border-[#D9A84E]" />
                        </div>

                        <div class="col-span-4 md:col-span-2">
                            <label class="md:hidden block text-[11px] font-medium text-gray-400 mb-1">
                                Preço Unit.
                            </label>
                            <input v-model.number="item.price" @change="onPriceChange(item)" type="number" step="0.01"
                                min="0"
                                class="w-full rounded-lg border border-neutral-700 bg-zinc-900 px-2.5 py-2.5 text-sm text-white text-center outline-none focus:ring-2 focus:ring-[#D9A84E] focus:border-[#D9A84E]" />
                        </div>

                        <div
                            class="col-span-12 md:col-span-2 flex items-center justify-between md:justify-end gap-2 pt-2 md:pt-0">
                            <div class="md:hidden text-[11px] font-medium text-gray-400 flex-1">Total</div>
                            <div
                                class="flex-1 md:flex-none text-right text-[#D9A84E] font-semibold text-base md:text-lg">
                                R$ {{ lineTotal(item).toFixed(2) }}
                            </div>
                            <button @click.prevent="removeItem(index)"
                                class="text-red-500 hover:text-red-400 transition ml-2 flex-shrink-0">
                                <Trash class="w-5 h-5" />
                            </button>
                        </div>
                    </div>

                    <div class="pt-2">
                        <button @click.prevent="addItem"
                            class="inline-flex items-center justify-center gap-1 rounded-lg px-4 py-2 text-sm border border-[#D9A84E] text-[#D9A84E] hover:bg-[#D9A84E]/10 transition w-full sm:w-auto">
                            + Adicionar item
                        </button>
                    </div>
                </div>
            </div>

            <!-- Total do Pedido -->
            <div
                class="rounded-xl border border-neutral-800 bg-zinc-900/80 p-4 md:p-5 mb-6 flex flex-col sm:flex-row items-center justify-between gap-3 shadow-sm">
                <div class="text-xs md:text-sm text-gray-400 uppercase tracking-[0.18em]">
                    Total do Pedido
                </div>
                <div
                    class="text-2xl md:text-3xl font-semibold bg-clip-text text-transparent bg-gradient-to-r from-[#D9A84E] to-[#B58737]">
                    R$ {{ orderTotal.toFixed(2) }}
                </div>
            </div>

            <!-- Ações -->
            <div class="flex flex-col sm:flex-row items-center justify-end gap-3">
                <button @click="handleCancel"
                    class="rounded-lg border border-neutral-700 px-6 py-2.5 text-sm text-gray-200 hover:bg-zinc-900/80 transition w-full sm:w-auto">
                    ← Cancelar
                </button>

                <button @click="handleFinalize" :disabled="submitting" class="flex items-center justify-center gap-2 px-6 py-2.5 w-full sm:w-auto
                        bg-gradient-to-r from-[#D9A84E] to-[#B58737]
                        text-black dark:text-white text-sm font-semibold rounded-lg shadow-md hover:shadow-lg transition-all duration-300
                        focus:ring-4 focus:ring-[#D9A84E]/40 disabled:opacity-50 disabled:cursor-not-allowed">
                    <template v-if="!submitting">
                        <Check class="w-4 h-4" />
                        <span>Atualizar Pedido</span>
                    </template>

                    <template v-else>
                        <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z" />
                        </svg>
                        <span>Atualizando...</span>
                    </template>
                </button>
            </div>

        </div>
    </AppLayout>
</template>
