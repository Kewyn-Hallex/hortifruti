<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { company } from '@/routes';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import {
    Check,
    Lightbulb,
    AlertCircle,
    DollarSign,
    Package,
    Trash2
} from 'lucide-vue-next';
import { router } from '@inertiajs/vue3';
import { ref, watch, computed } from 'vue';
import { ChevronDown } from 'lucide-vue-next';

// ... suas outras refs (localFruits, tempPrices, etc.)

// Adicione esta linha com as outras refs
const showFullTip = ref(false);

// Modal de confirmação de exclusão
const deleteModal = ref({ show: false, fruitId: 0, fruitName: '' });
const deleting = ref(false);

interface Fruit { 
    id: number; 
    name: string; 
    price: number;
    price_box: number;
    price_kg: number;
    price_bunch: number;
    updated_at?: string;
}

const props = defineProps<{ fruits: Fruit[] }>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Preços do dia',
        href: company().url,
    },
];

// local reactive copy so we can edit before persisting
const localFruits = ref<Fruit[]>(props.fruits ? JSON.parse(JSON.stringify(props.fruits)) : []);

// temporary prices entered by user (not yet saved)
const tempPrices = ref<Record<number, {
    box: number | null;
    kg: number | null;
    bunch: number | null;
}>>({});
const saving = ref(false);
const message = ref('');
const messageType = ref<'success' | 'error' | 'info'>('info');
// track which ids are currently being saved (for per-row indicator)
const savingRows = ref<Record<number, boolean>>({});
// track saved successfully rows for feedback
const savedRows = ref<Set<number>>(new Set());

// Search/filter
const searchQuery = ref('');

// Última atualização
const lastUpdate = computed(() => {
    if (localFruits.value.length === 0) return 'Nunca';
    
    const mostRecent = localFruits.value.reduce((latest, fruit) => {
        if (!fruit.updated_at) return latest;
        const fruitDate = new Date(fruit.updated_at);
        if (!latest || fruitDate > latest) return fruitDate;
        return latest;
    }, null as Date | null);

    if (!mostRecent) return 'Nunca';

    const now = new Date();
    const diffMs = now.getTime() - mostRecent.getTime();
    const diffMins = Math.floor(diffMs / 60000);
    const diffHours = Math.floor(diffMs / 3600000);
    const diffDays = Math.floor(diffMs / 86400000);

    if (diffMins < 1) return 'Agora mesmo';
    if (diffMins < 60) return `Há ${diffMins} minuto${diffMins > 1 ? 's' : ''}`;
    if (diffHours < 24) {
        const hours = diffHours;
        const mins = Math.floor((diffMs % 3600000) / 60000);
        if (mins === 0) return `Há ${hours} hora${hours > 1 ? 's' : ''}`;
        return `Há ${hours}h ${mins}min`;
    }
    if (diffDays === 1) return 'Ontem';
    if (diffDays < 7) return `Há ${diffDays} dia${diffDays > 1 ? 's' : ''}`;
    
    // Formato completo para datas mais antigas
    return mostRecent.toLocaleDateString('pt-BR', { 
        day: '2-digit', 
        month: '2-digit', 
        hour: '2-digit', 
        minute: '2-digit' 
    });
});

// Computed properties
const filteredFruits = computed(() => {
    if (!searchQuery.value) return localFruits.value;
    return localFruits.value.filter(f =>
        f.name.toLowerCase().includes(searchQuery.value.toLowerCase())
    );
});

const hasChanges = computed(() => {
    return localFruits.value.some(f => {
        const temp = tempPrices.value[f.id];
        if (!temp) return false;
        return (temp.box !== null && temp.box !== undefined && Number(temp.box) !== Number(f.price_box)) ||
               (temp.kg !== null && temp.kg !== undefined && Number(temp.kg) !== Number(f.price_kg)) ||
               (temp.bunch !== null && temp.bunch !== undefined && Number(temp.bunch) !== Number(f.price_bunch));
    });
});

const changedCount = computed(() => {
    return localFruits.value.filter(f => {
        const temp = tempPrices.value[f.id];
        if (!temp) return false;
        return (temp.box !== null && temp.box !== undefined && Number(temp.box) !== Number(f.price_box)) ||
               (temp.kg !== null && temp.kg !== undefined && Number(temp.kg) !== Number(f.price_kg)) ||
               (temp.bunch !== null && temp.bunch !== undefined && Number(temp.bunch) !== Number(f.price_bunch));
    }).length;
});

// Helper functions
const getPriceChange = (fruit: Fruit, type: 'box' | 'kg' | 'bunch') => {
    const temp = tempPrices.value[fruit.id];
    if (!temp) return null;
    const currentPrice = type === 'box' ? fruit.price_box : type === 'kg' ? fruit.price_kg : fruit.price_bunch;
    const newPrice = type === 'box' ? temp.box : type === 'kg' ? temp.kg : temp.bunch;
    if (newPrice === null || newPrice === undefined) return null;
    const diff = Number(newPrice) - Number(currentPrice);
    return diff === 0 ? null : diff;
};

watch(
    () => props.fruits,
    (v) => {
        if (v) {
            localFruits.value = JSON.parse(JSON.stringify(v));
            // initialize tempPrices entries
            (localFruits.value || []).forEach((f) => {
                if (!tempPrices.value[f.id]) {
                    tempPrices.value[f.id] = {
                        box: null,
                        kg: null,
                        bunch: null,
                    };
                }
                savingRows.value[f.id] = false;
            });
        }
    },
    { immediate: true, deep: true },
);

const savePrices = () => {
    const payload: Array<{ id: number; price_box?: number; price_kg?: number; price_bunch?: number }> = [];
    for (const f of localFruits.value) {
        const temp = tempPrices.value[f.id];
        if (!temp) continue;
        
        const updateData: { id: number; price_box?: number; price_kg?: number; price_bunch?: number } = { id: f.id };
        let hasChange = false;

        if (temp.box !== null && temp.box !== undefined && Number(temp.box) !== Number(f.price_box)) {
            updateData.price_box = Number(temp.box);
            hasChange = true;
        }
        if (temp.kg !== null && temp.kg !== undefined && Number(temp.kg) !== Number(f.price_kg)) {
            updateData.price_kg = Number(temp.kg);
            hasChange = true;
        }
        if (temp.bunch !== null && temp.bunch !== undefined && Number(temp.bunch) !== Number(f.price_bunch)) {
            updateData.price_bunch = Number(temp.bunch);
            hasChange = true;
        }

        if (hasChange) {
            payload.push(updateData);
        }
    }

    if (payload.length === 0) {
        messageType.value = 'info';
        message.value = 'Nenhuma alteração para salvar.';
        setTimeout(() => (message.value = ''), 3000);
        return;
    }

    // mark rows that will be saved so we can show per-row indicators
    for (const p of payload) savingRows.value[p.id] = true;

    saving.value = true;
    router.post('/fruits/bulk-update', { prices: payload } as any, {
        preserveState: true,
        onSuccess: () => {
            // Mark rows as saved for visual feedback
            payload.forEach(p => savedRows.value.add(p.id));

            // clear temp inputs
            (localFruits.value || []).forEach((f) => {
                tempPrices.value[f.id] = {
                    box: null,
                    kg: null,
                    bunch: null,
                };
            });
            messageType.value = 'success';
            message.value = `${payload.length} ${payload.length === 1 ? 'produto atualizado' : 'produtos atualizados'} com sucesso!`;

            // clear per-row saving indicators after success
            for (const p of payload) savingRows.value[p.id] = false;
            saving.value = false;

            // Clear saved indicators after animation
            setTimeout(() => {
                savedRows.value.clear();
            }, 2000);

            // reload fresh data
            router.reload({ only: ['fruits'] });
            // fade message after a short while
            setTimeout(() => (message.value = ''), 4000);
        },
        onError: () => {
            messageType.value = 'error';
            message.value = 'Erro ao salvar preços. Tente novamente.';
            // clear per-row indicators on error as well
            for (const p of payload) savingRows.value[p.id] = false;
            saving.value = false;
            setTimeout(() => (message.value = ''), 4000);
        },
    });
}

const openDeleteModal = (fruitId: number, fruitName: string) => {
    deleteModal.value = { show: true, fruitId, fruitName };
};

const closeDeleteModal = () => {
    deleteModal.value = { show: false, fruitId: 0, fruitName: '' };
};

const confirmDelete = () => {
    const fruitId = deleteModal.value.fruitId;
    deleting.value = true;

    router.delete(`/products/${fruitId}`, {
        onSuccess: () => {
            messageType.value = 'success';
            message.value = 'Produto excluído com sucesso!';
            setTimeout(() => (message.value = ''), 3000);
            closeDeleteModal();
            deleting.value = false;
            // Remove o produto da lista local
            localFruits.value = localFruits.value.filter(f => f.id !== fruitId);
            // Recarrega os dados
            router.reload({ only: ['fruits'] });
        },
        onError: () => {
            messageType.value = 'error';
            message.value = 'Erro ao excluir produto. Tente novamente.';
            deleting.value = false;
            setTimeout(() => (message.value = ''), 4000);
        },
    });
}

const resetChanges = () => {
    (localFruits.value || []).forEach((f) => {
        tempPrices.value[f.id] = {
            box: null,
            kg: null,
            bunch: null,
        };
    });
    message.value = '';
}
</script>

<template>
    <Head title="Preços do dia" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <!-- Container principal - mobile first -->
        <div class="flex flex-col gap-6 px-3 py-4 sm:px-4 sm:py-5 lg:px-6 max-w-7xl mx-auto w-full">

            <!-- Header com estatísticas -->
            <div class="grid grid-cols-1 gap-4 md:grid-cols-3 w-full">
                <div
                    class="bg-white dark:bg-zinc-900 rounded-md border border-gray-200 dark:border-neutral-800 p-4 sm:p-5 shadow-sm w-full"
                >
                    <div class="flex items-center justify-between gap-3">
                        <div class="min-w-0">
                            <p class="text-xs sm:text-sm text-gray-500 dark:text-gray-400 truncate">
                                Total de Produtos
                            </p>
                            <p class="text-2xl sm:text-3xl font-bold text-gray-900 dark:text-white">
                                {{ localFruits.length }}
                            </p>
                        </div>
                        <Package class="w-8 h-8 sm:w-9 sm:h-9 text-[#D9A84E] opacity-80 flex-shrink-0" aria-hidden="true" />
                    </div>
                </div>

                <div
                    class="bg-white dark:bg-zinc-900 rounded-md border border-gray-200 dark:border-neutral-800 p-4 sm:p-5 shadow-sm w-full"
                >
                    <div class="flex items-center justify-between gap-3">
                        <div class="min-w-0">
                            <p class="text-xs sm:text-sm text-gray-500 dark:text-gray-400 truncate">
                                Alterações Pendentes
                            </p>
                            <p class="text-2xl sm:text-3xl font-bold"
                               :class="changedCount > 0 ? 'text-[#D9A84E]' : 'text-gray-900 dark:text-white'">
                                {{ changedCount }}
                            </p>
                        </div>
                        <DollarSign class="w-8 h-8 sm:w-9 sm:h-9 text-[#D9A84E] opacity-80 flex-shrink-0" aria-hidden="true" />
                    </div>
                </div>

                <div
                    class="bg-white dark:bg-zinc-900 rounded-md border border-gray-200 dark:border-neutral-800 p-4 sm:p-5 shadow-sm w-full"
                >
                    <div class="flex items-center justify-between gap-3">
                        <div class="min-w-0">
                            <p class="text-xs sm:text-sm text-gray-500 dark:text-gray-400 truncate">
                                Última Atualização
                            </p>
                            <p class="text-base sm:text-lg font-semibold text-gray-900 dark:text-white">
                                {{ lastUpdate }}
                            </p>
                        </div>
                        <Check class="w-8 h-8 sm:w-9 sm:h-9 text-green-500 opacity-80 flex-shrink-0" aria-hidden="true" />
                    </div>
                </div>
            </div>

            <!-- Search e Actions Bar -->
            <div class="w-full">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div class="relative w-full sm:w-80 max-w-full">
                        <!-- A11Y: label visível apenas para leitores de tela -->
                        <label for="search-products" class="sr-only">
                            Buscar produto
                        </label>
                        <input
                            id="search-products"
                            v-model="searchQuery"
                            type="search"
                            inputmode="search"
                            autocomplete="off"
                            placeholder="Buscar produto..."
                            class="w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 pl-10 text-sm shadow-sm outline-none transition focus:ring-2 focus:ring-[#D9A84E] focus:border-[#D9A84E] dark:border-neutral-700 dark:bg-zinc-900 dark:text-white"
                        />
                        <svg
                            class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400"
                            xmlns="http://www.w3.org/2000/svg"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                            aria-hidden="true"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
                            />
                        </svg>
                    </div>

                    <!-- Status de alterações -->
                    <div
                        v-if="hasChanges"
                        class="flex items-center gap-2 rounded-full bg-[#D9A84E]/10 px-3 py-1.5 w-full sm:w-auto justify-center sm:justify-start"
                        role="status"
                        aria-live="polite"
                    >
                        <span class="h-2 w-2 rounded-full bg-[#D9A84E] animate-pulse"></span>
                        <span class="text-sm font-medium text-[#D9A84E]">
                            {{ changedCount }} alterações não salvas
                        </span>
                    </div>
                </div>
            </div>

            <!-- Card tabela -->
            <div
                class="overflow-hidden rounded-md border border-gray-200 dark:border-neutral-800 bg-white shadow-sm dark:bg-zinc-900 w-full"
                role="region"
                aria-label="Tabela de preços dos produtos"
            >
                <!-- Mensagem de feedback flutuante -->
                <Transition name="slide-down">
                    <div
                        v-if="message"
                        :class="[
                            'mx-3 sm:mx-4 mt-3 sm:mt-4 flex items-center gap-2 rounded-lg p-3 text-sm font-medium transition-all',
                            messageType === 'success'
                                ? 'bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-400 border border-green-200 dark:border-green-800'
                                : messageType === 'error'
                                    ? 'bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-400 border border-red-200 dark:border-red-800'
                                    : 'bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-400 border border-blue-200 dark:border-blue-800'
                        ]"
                        :role="messageType === 'error' ? 'alert' : 'status'"
                        aria-live="polite"
                    >
                        <AlertCircle
                            v-if="messageType === 'error'"
                            class="h-4 w-4"
                            aria-hidden="true"
                        />
                        <Check
                            v-else-if="messageType === 'success'"
                            class="h-4 w-4"
                            aria-hidden="true"
                        />
                        <span class="flex-1 min-w-0 break-words">{{ message }}</span>
                    </div>
                </Transition>

                <!-- LISTAGEM DE PRODUTOS - LAYOUT RESPONSIVO SEM SCROLL LATERAL EM MOBILE -->

<!-- MOBILE: cards empilhados (até md) -->
<div class="md:hidden divide-y divide-gray-100 dark:divide-neutral-800">
    <TransitionGroup name="list">
        <div
            v-for="fruit in filteredFruits"
            :key="fruit.id"
            :class="[
                'p-3 sm:p-4 flex flex-col gap-3',
                'hover:bg-gray-50 dark:hover:bg-zinc-800/50 transition-colors',
                savedRows.has(fruit.id) ? 'bg-green-50 dark:bg-green-900/10' : ''
            ]"
        >
            <!-- Linha 1: Nome + status + ação -->
            <div class="flex items-start justify-between gap-2">
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2 flex-wrap">
                        <span class="font-medium text-gray-900 dark:text-white break-words">
                            {{ fruit.name }}
                        </span>

                        <Transition name="fade">
                            <span
                                v-if="savingRows[fruit.id]"
                                class="inline-flex items-center gap-1.5"
                                role="status"
                                aria-live="polite"
                            >
                                <svg
                                    class="h-3 w-3 text-[#D9A84E] animate-spin"
                                    xmlns="http://www.w3.org/2000/svg"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    aria-hidden="true"
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
                                <span class="text-xs text-gray-500 dark:text-gray-400">
                                    Salvando...
                                </span>
                            </span>
                            <span
                                v-else-if="savedRows.has(fruit.id)"
                                class="inline-flex items-center gap-1.5"
                            >
                                <Check
                                    class="h-4 w-4 text-green-500"
                                    aria-hidden="true"
                                />
                                <span
                                    class="text-xs font-medium text-green-600 dark:text-green-400"
                                >
                                    Salvo!
                                </span>
                            </span>
                        </Transition>
                    </div>
                </div>

                <!-- Botão excluir -->
                <button
                    @click="openDeleteModal(fruit.id, fruit.name)"
                    :disabled="saving"
                    class="inline-flex items-center justify-center rounded-full p-2 text-red-500 hover:text-red-700 hover:bg-red-50 dark:text-red-400 dark:hover:text-red-300 dark:hover:bg-red-900/20 transition disabled:opacity-50 disabled:cursor-not-allowed flex-shrink-0"
                    title="Excluir produto"
                >
                    <Trash2 class="h-4 w-4" />
                </button>
            </div>

            <!-- Linha 2: preços atuais -->
            <div class="grid grid-cols-3 gap-2 text-xs">
                <div class="flex flex-col">
                    <span class="text-[10px] uppercase tracking-wide text-gray-500 dark:text-gray-400">
                        Caixa atual
                    </span>
                    <span class="font-mono text-gray-800 dark:text-gray-200">
                        R$ {{ Number(fruit.price_box || 0).toFixed(2) }}
                    </span>
                </div>
                <div class="flex flex-col">
                    <span class="text-[10px] uppercase tracking-wide text-gray-500 dark:text-gray-400">
                        Kg atual
                    </span>
                    <span class="font-mono text-gray-800 dark:text-gray-200">
                        R$ {{ Number(fruit.price_kg || 0).toFixed(2) }}
                    </span>
                </div>
                <div class="flex flex-col">
                    <span class="text-[10px] uppercase tracking-wide text-gray-500 dark:text-gray-400">
                        Maço atual
                    </span>
                    <span class="font-mono text-gray-800 dark:text-gray-200">
                        R$ {{ Number(fruit.price_bunch || 0).toFixed(2) }}
                    </span>
                </div>
            </div>

            <!-- Linha 3: novos preços -->
            <div class="grid grid-cols-1 gap-2">
                <!-- Caixa -->
                <div class="flex flex-col gap-1">
                    <label class="text-[11px] font-medium text-gray-600 dark:text-gray-300">
                        Novo preço - Caixa
                    </label>
                    <div class="flex items-center gap-2">
                        <div class="relative flex-1">
                            <span
                                class="pointer-events-none absolute left-2 top-1/2 -translate-y-1/2 text-xs font-mono text-gray-500 dark:text-gray-400"
                            >
                                R$
                            </span>
                            <input
                                type="number"
                                step="0.01"
                                v-model.number="(tempPrices[fruit.id] || { box: null, kg: null, bunch: null }).box"
                                :placeholder="Number(fruit.price_box || 0).toFixed(2)"
                                :disabled="saving"
                                :aria-label="`Novo preço de caixa para ${fruit.name}`"
                                class="w-full rounded-lg border bg-white text-right text-xs font-mono font-semibold shadow-sm outline-none transition-all pl-8 pr-2 py-2 min-h-[40px] hover:border-[#D9A84E]/50 focus:ring-2 focus:ring-[#D9A84E]/50 focus:border-[#D9A84E] disabled:cursor-not-allowed disabled:opacity-50 dark:bg-zinc-800 dark:text-white dark:border-neutral-700"
                                :class="[
                                    tempPrices[fruit.id]?.box !== null &&
                                    tempPrices[fruit.id]?.box !== undefined &&
                                    Number(tempPrices[fruit.id]?.box) !== Number(fruit.price_box)
                                        ? 'border-[#D9A84E] bg-[#D9A84E]/5 text-gray-900 dark:text-white ring-2 ring-[#D9A84E]/20'
                                        : ''
                                ]"
                            />
                        </div>
                        <div
                            v-if="getPriceChange(fruit, 'box') !== null"
                            class="text-[11px] font-mono whitespace-nowrap"
                            :class="getPriceChange(fruit, 'box')! > 0 ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400'"
                        >
                            {{ getPriceChange(fruit, 'box')! > 0 ? '+' : '' }}R$ {{ getPriceChange(fruit, 'box')!.toFixed(2) }}
                        </div>
                    </div>
                </div>

                <!-- Kg -->
                <div class="flex flex-col gap-1">
                    <label class="text-[11px] font-medium text-gray-600 dark:text-gray-300">
                        Novo preço - Kg
                    </label>
                    <div class="flex items-center gap-2">
                        <div class="relative flex-1">
                            <span
                                class="pointer-events-none absolute left-2 top-1/2 -translate-y-1/2 text-xs font-mono text-gray-500 dark:text-gray-400"
                            >
                                R$
                            </span>
                            <input
                                type="number"
                                step="0.01"
                                v-model.number="(tempPrices[fruit.id] || { box: null, kg: null, bunch: null }).kg"
                                :placeholder="Number(fruit.price_kg || 0).toFixed(2)"
                                :disabled="saving"
                                :aria-label="`Novo preço de kg para ${fruit.name}`"
                                class="w-full rounded-lg border bg-white text-right text-xs font-mono font-semibold shadow-sm outline-none transition-all pl-8 pr-2 py-2 min-h-[40px] hover:border-[#D9A84E]/50 focus:ring-2 focus:ring-[#D9A84E]/50 focus:border-[#D9A84E] disabled:cursor-not-allowed disabled:opacity-50 dark:bg-zinc-800 dark:text-white dark:border-neutral-700"
                                :class="[
                                    tempPrices[fruit.id]?.kg !== null &&
                                    tempPrices[fruit.id]?.kg !== undefined &&
                                    Number(tempPrices[fruit.id]?.kg) !== Number(fruit.price_kg)
                                        ? 'border-[#D9A84E] bg-[#D9A84E]/5 text-gray-900 dark:text-white ring-2 ring-[#D9A84E]/20'
                                        : ''
                                ]"
                            />
                        </div>
                        <div
                            v-if="getPriceChange(fruit, 'kg') !== null"
                            class="text-[11px] font-mono whitespace-nowrap"
                            :class="getPriceChange(fruit, 'kg')! > 0 ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400'"
                        >
                            {{ getPriceChange(fruit, 'kg')! > 0 ? '+' : '' }}R$ {{ getPriceChange(fruit, 'kg')!.toFixed(2) }}
                        </div>
                    </div>
                </div>

                <!-- Maço -->
                <div class="flex flex-col gap-1">
                    <label class="text-[11px] font-medium text-gray-600 dark:text-gray-300">
                        Novo preço - Maço
                    </label>
                    <div class="flex items-center gap-2">
                        <div class="relative flex-1">
                            <span
                                class="pointer-events-none absolute left-2 top-1/2 -translate-y-1/2 text-xs font-mono text-gray-500 dark:text-gray-400"
                            >
                                R$
                            </span>
                            <input
                                type="number"
                                step="0.01"
                                v-model.number="(tempPrices[fruit.id] || { box: null, kg: null, bunch: null }).bunch"
                                :placeholder="Number(fruit.price_bunch || 0).toFixed(2)"
                                :disabled="saving"
                                :aria-label="`Novo preço de maço para ${fruit.name}`"
                                class="w-full rounded-lg border bg-white text-right text-xs font-mono font-semibold shadow-sm outline-none transition-all pl-8 pr-2 py-2 min-h-[40px] hover:border-[#D9A84E]/50 focus:ring-2 focus:ring-[#D9A84E]/50 focus:border-[#D9A84E] disabled:cursor-not-allowed disabled:opacity-50 dark:bg-zinc-800 dark:text-white dark:border-neutral-700"
                                :class="[
                                    tempPrices[fruit.id]?.bunch !== null &&
                                    tempPrices[fruit.id]?.bunch !== undefined &&
                                    Number(tempPrices[fruit.id]?.bunch) !== Number(fruit.price_bunch)
                                        ? 'border-[#D9A84E] bg-[#D9A84E]/5 text-gray-900 dark:text-white ring-2 ring-[#D9A84E]/20'
                                        : ''
                                ]"
                            />
                        </div>
                        <div
                            v-if="getPriceChange(fruit, 'bunch') !== null"
                            class="text-[11px] font-mono whitespace-nowrap"
                            :class="getPriceChange(fruit, 'bunch')! > 0 ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400'"
                        >
                            {{ getPriceChange(fruit, 'bunch')! > 0 ? '+' : '' }}R$ {{ getPriceChange(fruit, 'bunch')!.toFixed(2) }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </TransitionGroup>
</div>

<!-- DESKTOP/TABLET: tabela normal (md e acima) -->
<div class="hidden md:block overflow-x-auto max-w-full">
    <table class="min-w-full text-sm table-auto">
        <thead
            class="border-b border-gray-200 bg-gray-50 dark:border-neutral-700 dark:bg-zinc-800"
        >
            <tr>
                <th
                    scope="col"
                    class="px-3 py-3 text-left font-semibold text-gray-700 dark:text-gray-300 sm:px-6 sm:py-4 whitespace-nowrap"
                >
                    Produto
                </th>
                <th
                    scope="col"
                    class="px-3 py-3 text-left font-semibold text-gray-700 dark:text-gray-300 sm:px-6 sm:py-4 whitespace-nowrap"
                >
                    Preços Atuais
                </th>
                <th
                    scope="col"
                    colspan="3"
                    class="px-3 py-3 text-center font-semibold text-gray-700 dark:text-gray-300 sm:px-6 sm:py-4 whitespace-nowrap"
                >
                    Novos Preços
                </th>
                <th
                    scope="col"
                    class="px-3 py-3 text-center font-semibold text-gray-700 dark:text-gray-300 sm:px-6 sm:py-4 whitespace-nowrap"
                >
                    Ações
                </th>
            </tr>
            <tr class="border-b border-gray-200 dark:border-neutral-700">
                <th class="px-3 sm:px-6 py-2"></th>
                <th class="px-3 sm:px-6 py-2"></th>
                <th
                    scope="col"
                    class="px-3 py-2 text-center text-xs font-medium text-gray-600 dark:text-gray-400 whitespace-nowrap"
                >
                    Caixa
                </th>
                <th
                    scope="col"
                    class="px-3 py-2 text-center text-xs font-medium text-gray-600 dark:text-gray-400 whitespace-nowrap"
                >
                    Kg
                </th>
                <th
                    scope="col"
                    class="px-3 py-2 text-center text-xs font-medium text-gray-600 dark:text-gray-400 whitespace-nowrap"
                >
                    Maço
                </th>
                <th class="px-3 sm:px-6 py-2"></th>
            </tr>
        </thead>

        <tbody class="divide-y divide-gray-100 dark:divide-neutral-800">
            <TransitionGroup name="list">
                <tr
                    v-for="fruit in filteredFruits"
                    :key="fruit.id"
                    :class="[
                        'hover:bg-gray-50 dark:hover:bg-zinc-800/50 transition-colors',
                        savedRows.has(fruit.id) ? 'bg-green-50 dark:bg-green-900/10' : ''
                    ]"
                >
                    <!-- Produto -->
                    <td
                        class="px-3 py-3 align-top sm:px-6 sm:py-4 max-w-[14rem] sm:max-w-xs"
                    >
                        <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:gap-3">
                            <span class="font-medium text-gray-900 dark:text-white break-words">
                                {{ fruit.name }}
                            </span>

                            <!-- Status indicators -->
                            <Transition name="fade">
                                <span
                                    v-if="savingRows[fruit.id]"
                                    class="inline-flex items-center gap-1.5 mt-1 sm:mt-0"
                                    role="status"
                                    aria-live="polite"
                                >
                                    <svg
                                        class="h-3 w-3 text-[#D9A84E] animate-spin"
                                        xmlns="http://www.w3.org/2000/svg"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                        aria-hidden="true"
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
                                    <span class="text-xs text-gray-500 dark:text-gray-400">
                                        Salvando...
                                    </span>
                                </span>
                                <span
                                    v-else-if="savedRows.has(fruit.id)"
                                    class="inline-flex items-center gap-1.5 mt-1 sm:mt-0"
                                >
                                    <Check
                                        class="h-4 w-4 text-green-500"
                                        aria-hidden="true"
                                    />
                                    <span
                                        class="text-xs font-medium text-green-600 dark:text-green-400"
                                    >
                                        Salvo!
                                    </span>
                                </span>
                            </Transition>
                        </div>
                    </td>

                    <!-- Preços Atuais -->
                    <td
                        class="px-3 py-3 align-middle sm:px-6 sm:py-4"
                    >
                        <div class="flex flex-col gap-1 text-xs sm:text-sm">
                            <div class="flex items-center gap-2">
                                <span class="text-gray-500 dark:text-gray-400 whitespace-nowrap">Caixa:</span>
                                <span class="font-mono text-gray-700 dark:text-gray-300">
                                    R$ {{ Number(fruit.price_box || 0).toFixed(2) }}
                                </span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="text-gray-500 dark:text-gray-400 whitespace-nowrap">Kg:</span>
                                <span class="font-mono text-gray-700 dark:text-gray-300">
                                    R$ {{ Number(fruit.price_kg || 0).toFixed(2) }}
                                </span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="text-gray-500 dark:text-gray-400 whitespace-nowrap">Maço:</span>
                                <span class="font-mono text-gray-700 dark:text-gray-300">
                                    R$ {{ Number(fruit.price_bunch || 0).toFixed(2) }}
                                </span>
                            </div>
                        </div>
                    </td>

                    <!-- Novo Preço Caixa -->
                    <td
                        class="px-2 py-3 align-middle sm:px-3 sm:py-4 min-w-[7rem]"
                    >
                        <div class="flex flex-col gap-1">
                            <div class="relative">
                                <span
                                    class="pointer-events-none absolute left-2 top-1/2 -translate-y-1/2 text-xs font-mono text-gray-500 dark:text-gray-400"
                                >
                                    R$
                                </span>
                                <input
                                    type="number"
                                    step="0.01"
                                    v-model.number="(tempPrices[fruit.id] || { box: null, kg: null, bunch: null }).box"
                                    :placeholder="Number(fruit.price_box || 0).toFixed(2)"
                                    :disabled="saving"
                                    :aria-label="`Novo preço de caixa para ${fruit.name}`"
                                    class="w-full rounded-lg border bg-white text-right text-xs sm:text-sm font-mono font-semibold shadow-sm outline-none transition-all pl-8 pr-2 py-2 min-h-[40px] hover:border-[#D9A84E]/50 focus:ring-2 focus:ring-[#D9A84E]/50 focus:border-[#D9A84E] disabled:cursor-not-allowed disabled:opacity-50 dark:bg-zinc-800 dark:text-white dark:border-neutral-700"
                                    :class="[
                                        tempPrices[fruit.id]?.box !== null &&
                                        tempPrices[fruit.id]?.box !== undefined &&
                                        Number(tempPrices[fruit.id]?.box) !== Number(fruit.price_box)
                                            ? 'border-[#D9A84E] bg-[#D9A84E]/5 text-gray-900 dark:text-white ring-2 ring-[#D9A84E]/20'
                                            : ''
                                    ]"
                                />
                            </div>
                            <div
                                v-if="getPriceChange(fruit, 'box') !== null"
                                class="text-xs font-mono"
                                :class="getPriceChange(fruit, 'box')! > 0 ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400'"
                            >
                                {{ getPriceChange(fruit, 'box')! > 0 ? '+' : '' }}R$ {{ getPriceChange(fruit, 'box')!.toFixed(2) }}
                            </div>
                        </div>
                    </td>

                    <!-- Novo Preço Kg -->
                    <td
                        class="px-2 py-3 align-middle sm:px-3 sm:py-4 min-w-[7rem]"
                    >
                        <div class="flex flex-col gap-1">
                            <div class="relative">
                                <span
                                    class="pointer-events-none absolute left-2 top-1/2 -translate-y-1/2 text-xs font-mono text-gray-500 dark:text-gray-400"
                                >
                                    R$
                                </span>
                                <input
                                    type="number"
                                    step="0.01"
                                    v-model.number="(tempPrices[fruit.id] || { box: null, kg: null, bunch: null }).kg"
                                    :placeholder="Number(fruit.price_kg || 0).toFixed(2)"
                                    :disabled="saving"
                                    :aria-label="`Novo preço de kg para ${fruit.name}`"
                                    class="w-full rounded-lg border bg-white text-right text-xs sm:text-sm font-mono font-semibold shadow-sm outline-none transition-all pl-8 pr-2 py-2 min-h-[40px] hover:border-[#D9A84E]/50 focus:ring-2 focus:ring-[#D9A84E]/50 focus:border-[#D9A84E] disabled:cursor-not-allowed disabled:opacity-50 dark:bg-zinc-800 dark:text-white dark:border-neutral-700"
                                    :class="[
                                        tempPrices[fruit.id]?.kg !== null &&
                                        tempPrices[fruit.id]?.kg !== undefined &&
                                        Number(tempPrices[fruit.id]?.kg) !== Number(fruit.price_kg)
                                            ? 'border-[#D9A84E] bg-[#D9A84E]/5 text-gray-900 dark:text-white ring-2 ring-[#D9A84E]/20'
                                            : ''
                                    ]"
                                />
                            </div>
                            <div
                                v-if="getPriceChange(fruit, 'kg') !== null"
                                class="text-xs font-mono"
                                :class="getPriceChange(fruit, 'kg')! > 0 ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400'"
                            >
                                {{ getPriceChange(fruit, 'kg')! > 0 ? '+' : '' }}R$ {{ getPriceChange(fruit, 'kg')!.toFixed(2) }}
                            </div>
                        </div>
                    </td>

                    <!-- Novo Preço Maço -->
                    <td
                        class="px-2 py-3 align-middle sm:px-3 sm:py-4 min-w-[7rem]"
                    >
                        <div class="flex flex-col gap-1">
                            <div class="relative">
                                <span
                                    class="pointer-events-none absolute left-2 top-1/2 -translate-y-1/2 text-xs font-mono text-gray-500 dark:text-gray-400"
                                >
                                    R$
                                </span>
                                <input
                                    type="number"
                                    step="0.01"
                                    v-model.number="(tempPrices[fruit.id] || { box: null, kg: null, bunch: null }).bunch"
                                    :placeholder="Number(fruit.price_bunch || 0).toFixed(2)"
                                    :disabled="saving"
                                    :aria-label="`Novo preço de maço para ${fruit.name}`"
                                    class="w-full rounded-lg border bg-white text-right text-xs sm:text-sm font-mono font-semibold shadow-sm outline-none transition-all pl-8 pr-2 py-2 min-h-[40px] hover:border-[#D9A84E]/50 focus:ring-2 focus:ring-[#D9A84E]/50 focus:border-[#D9A84E] disabled:cursor-not-allowed disabled:opacity-50 dark:bg-zinc-800 dark:text-white dark:border-neutral-700"
                                    :class="[
                                        tempPrices[fruit.id]?.bunch !== null &&
                                        tempPrices[fruit.id]?.bunch !== undefined &&
                                        Number(tempPrices[fruit.id]?.bunch) !== Number(fruit.price_bunch)
                                            ? 'border-[#D9A84E] bg-[#D9A84E]/5 text-gray-900 dark:text-white ring-2 ring-[#D9A84E]/20'
                                            : ''
                                    ]"
                                />
                            </div>
                            <div
                                v-if="getPriceChange(fruit, 'bunch') !== null"
                                class="text-xs font-mono"
                                :class="getPriceChange(fruit, 'bunch')! > 0 ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400'"
                            >
                                {{ getPriceChange(fruit, 'bunch')! > 0 ? '+' : '' }}R$ {{ getPriceChange(fruit, 'bunch')!.toFixed(2) }}
                            </div>
                        </div>
                    </td>

                    <!-- Ações -->
                    <td
                        class="px-3 py-3 align-middle sm:px-6 sm:py-4 text-center"
                    >
                        <button
                            @click="openDeleteModal(fruit.id, fruit.name)"
                            :disabled="saving"
                            class="inline-flex items-center justify-center rounded-full p-2 text-red-500 hover:text-red-700 hover:bg-red-50 dark:text-red-400 dark:hover:text-red-300 dark:hover:bg-red-900/20 transition disabled:opacity-50 disabled:cursor-not-allowed"
                            title="Excluir produto"
                        >
                            <Trash2 class="h-4 w-4" />
                        </button>
                    </td>
                </tr>
            </TransitionGroup>
        </tbody>
    </table>
</div>

                <!-- Empty state -->
                <div
                    v-if="filteredFruits.length === 0"
                    class="py-10 text-center px-3 sm:px-4"
                >
                    <Package
                        class="mx-auto mb-3 h-12 w-12 text-gray-300 dark:text-gray-600"
                        aria-hidden="true"
                    />
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        Nenhum produto encontrado
                    </p>
                </div>

                <!-- Footer com ações -->
                <div
                    class="border-t border-gray-200 bg-gray-50 px-3 py-4 dark:border-neutral-700 dark:bg-zinc-800 sm:px-4 lg:px-6"
                >
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div class="text-sm text-gray-500 dark:text-gray-400">
                            <span v-if="hasChanges">
                                {{ changedCount }} {{ changedCount === 1 ? 'produto' : 'produtos' }} com alteração
                            </span>
                            <span v-else>
                                Nenhuma alteração pendente
                            </span>
                        </div>

                        <div class="flex w-full flex-col gap-3 sm:w-auto sm:flex-row sm:items-center">
                            <button
                                v-if="hasChanges"
                                type="button"
                                @click="resetChanges"
                                :disabled="saving"
                                class="w-full min-h-[44px] rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 shadow-sm transition-all hover:bg-gray-50 focus:ring-2 focus:ring-gray-300 disabled:cursor-not-allowed disabled:opacity-50 dark:border-neutral-600 dark:bg-zinc-700 dark:text-gray-300 dark:hover:bg-zinc-600 dark:focus:ring-gray-600"
                            >
                                Descartar Alterações
                            </button>

                            <button
                                type="button"
                                @click="savePrices"
                                :disabled="saving || !hasChanges"
                                :aria-disabled="saving || !hasChanges"
                                class="flex min-h-[44px] w-full items-center justify-center gap-2 rounded-lg px-5 py-2.5 text-sm font-semibold shadow-md transition-all sm:w-auto"
                                :class="[
                                    hasChanges
                                        ? 'bg-gradient-to-r from-[#D9A84E] to-[#B58737] text-white hover:shadow-lg hover:shadow-[#D9A84E]/25 focus:ring-4 focus:ring-[#D9A84E]/30'
                                        : 'cursor-not-allowed bg-gray-200 text-gray-400 dark:bg-zinc-700 dark:text-gray-500',
                                    'disabled:opacity-50 disabled:cursor-not-allowed'
                                ]"
                            >
                                <template v-if="!saving">
                                    <Check class="h-4 w-4" aria-hidden="true" />
                                    <span>Salvar {{ changedCount > 0 ? `(${changedCount})` : '' }}</span>
                                </template>
                                <template v-else>
                                    <svg
                                        class="h-4 w-4 animate-spin"
                                        xmlns="http://www.w3.org/2000/svg"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                        aria-hidden="true"
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
                                    <span>Salvando...</span>
                                </template>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Dica Premium -->
            <div
                class="group relative overflow-hidden rounded-xl border border-[#D9A84E]/30 bg-gradient-to-br from-[#D9A84E]/8 via-white to-transparent p-4 sm:p-5 shadow-sm transition-all duration-300 hover:shadow-md dark:from-[#D9A84E]/12 dark:via-zinc-900 dark:to-transparent dark:border-[#D9A84E]/25"
                @mouseenter="showFullTip = true"
                @mouseleave="showFullTip = false"
            >
                <!-- Fundo com brilho suave no hover -->
                <div
                    class="pointer-events-none absolute inset-0 opacity-0 transition-opacity duration-700 group-hover:opacity-100"
                    aria-hidden="true"
                >
                    <div class="absolute inset-0 bg-gradient-to-tr from-[#D9A84E]/10 to-transparent"></div>
                </div>

                <div class="relative z-10 flex flex-col sm:flex-row items-start gap-4">
                    <!-- Ícone com halo dourado -->
                    <div class="relative flex-shrink-0">
                        <div
                            class="absolute -inset-2 rounded-full bg-[#D9A84E]/20 blur-xl transition-transform duration-500 group-hover:scale-150"
                            aria-hidden="true"
                        ></div>
                        <div
                            class="relative flex h-10 w-10 items-center justify-center rounded-full bg-[#D9A84E]/10 dark:bg-[#D9A84E]/20"
                        >
                            <Lightbulb class="h-5 w-5 text-[#D9A84E]" aria-hidden="true" />
                        </div>
                    </div>

                    <!-- Conteúdo -->
                    <div class="flex-1 space-y-3">
                        <div class="flex flex-wrap items-center gap-2">
                            <h3 class="text-base font-semibold text-gray-900 dark:text-white">
                                Dica Profissional
                            </h3>
                            <span
                                class="rounded-full bg-[#D9A84E]/15 px-2.5 py-0.5 text-xs font-medium text-[#B58737] dark:bg-[#D9A84E]/25"
                            >
                                Novo recurso
                            </span>
                        </div>

                        <p class="text-sm leading-relaxed text-gray-700 dark:text-gray-300">
                            Os preços definidos aqui são aplicados
                            <span class="font-semibold text-[#D9A84E]">
                                automaticamente
                            </span>
                            em todos os novos pedidos —
                            você nunca mais precisa ajustar manualmente.
                        </p>

                        <!-- Dica extra -->
                        <Transition name="fade-slide">
                            <div
                                v-if="showFullTip"
                                class="space-y-2 border-t border-dashed border-[#D9A84E]/30 pt-3"
                                id="advanced-tip-panel"
                            >
                                <p class="text-sm text-gray-600 dark:text-gray-400">
                                    <span class="font-medium text-[#D9A84E]">
                                        Atalho secreto:
                                    </span>
                                    Use
                                    <kbd class="kbd">Tab</kbd>
                                    para ir ao próximo preço e
                                    <kbd class="kbd">Shift</kbd>+<kbd class="kbd">Tab</kbd>
                                    para voltar.
                                    Atualize dezenas de preços em segundos!
                                </p>
                            </div>
                        </Transition>

                        <!-- Botão "Mostrar mais" -->
                        <button
                            v-if="!showFullTip"
                            type="button"
                            @click="showFullTip = true"
                            class="mt-2 inline-flex items-center gap-1.5 text-sm font-medium text-[#D9A84E] transition-all hover:gap-2 hover:text-[#B58737]"
                            :aria-expanded="showFullTip"
                            aria-controls="advanced-tip-panel"
                        >
                            Mostrar dica avançada
                            <ChevronDown
                                class="h-4 w-4 transition-transform duration-300"
                                :class="{ 'rotate-180': showFullTip }"
                                aria-hidden="true"
                            />
                        </button>
                    </div>
                </div>
            </div>

            <!-- Modal de confirmação de exclusão -->
            <Transition name="fade">
                <div
                    v-if="deleteModal.show"
                    class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 px-4 backdrop-blur-sm"
                    @click.self="closeDeleteModal"
                >
                    <div
                        class="w-full max-w-sm rounded-md border border-white/10 bg-zinc-950/95 p-5 shadow-2xl"
                    >
                        <h3 class="text-base font-semibold text-gray-100">
                            Excluir Produto
                        </h3>
                        <p class="mt-2 text-sm text-gray-400">
                            Tem certeza de que deseja excluir o produto
                            <span class="font-semibold text-gray-100">{{ deleteModal.fruitName }}</span>?
                            Esta ação é permanente e não poderá ser desfeita.
                        </p>

                        <div class="mt-5 flex justify-end gap-2">
                            <button
                                @click="closeDeleteModal"
                                :disabled="deleting"
                                class="rounded-lg border border-white/10 bg-white/5 px-3 py-1.5 text-xs font-medium
                                     text-gray-200 transition hover:bg-white/10
                                     focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white/30
                                     disabled:opacity-50 disabled:cursor-not-allowed"
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
                                    <Trash2 class="h-3 w-3" />
                                    Excluir
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
                                    <span>Excluindo...</span>
                                </template>
                            </button>
                        </div>
                    </div>
                </div>
            </Transition>
        </div>
    </AppLayout>
</template>

<style scoped>
/* Animações suaves */
.fade-slide-enter-active,
.fade-slide-leave-active {
    transition: all 0.35s ease-out;
}

.fade-slide-enter-from,
.fade-slide-leave-to {
    opacity: 0;
    transform: translateY(-8px);
}

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

.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.3s ease;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}

.list-enter-active,
.list-leave-active {
    transition: all 0.3s ease;
}

.list-enter-from {
    opacity: 0;
    transform: translateX(-30px);
}

.list-leave-to {
    opacity: 0;
    transform: translateX(30px);
}

/* Remove spinner do input number (WebKit) */
input[type="number"]::-webkit-inner-spin-button,
input[type="number"]::-webkit-outer-spin-button {
    -webkit-appearance: none;
    margin: 0;
}

/* Remove spinner em Firefox */
input[type="number"] {
    -moz-appearance: textfield;
}

/* Estilo acessível para <kbd> (atahos de teclado) */
.kbd {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 0.125rem 0.375rem; /* 2px 6px */
    margin-inline: 0.0625rem;
    border-radius: 0.375rem;
    border: 1px solid rgba(148, 163, 184, 0.9); /* slate-400 aprox */
    background-color: rgba(248, 250, 252, 1);   /* slate-50 aprox */
    font-size: 0.75rem;
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
    color: #111827; /* gray-900 aprox */
}

:global(.dark) .kbd {
    border-color: rgba(82, 82, 91, 0.9);       /* zinc-700 aprox */
    background-color: rgba(39, 39, 42, 1);     /* zinc-800 aprox */
    color: #e5e7eb;                            /* gray-200 aprox */
}

/* Garantir que imagens/vídeos não quebrem layout */
img,
video {
    max-width: 100%;
    height: auto;
    object-fit: cover;
}
</style>