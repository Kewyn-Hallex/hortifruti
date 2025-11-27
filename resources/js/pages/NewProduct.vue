<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Form, Head, usePage } from '@inertiajs/vue3';
import { type BreadcrumbItem } from '@/types';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import InputError from '@/components/InputError.vue';
import { PackagePlus, Check, AlertCircle } from 'lucide-vue-next';
import { computed } from 'vue';
import FruitController from '@/actions/App/Http/Controllers/FruitController';

const page = usePage();
const successMessage = computed(() => (page.props.flash as { success?: string })?.success);

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Cadastrar Produto',
        href: FruitController.create().url,
    },
];
</script>

<template>
    <Head title="Cadastrar Produto" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 px-4 py-5 sm:px-6 max-w-3xl mx-auto">
            <!-- Header -->
            <div class="flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-[#D9A84E]/10 dark:bg-[#D9A84E]/20">
                    <PackagePlus class="h-5 w-5 text-[#D9A84E]" aria-hidden="true" />
                </div>
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                        Cadastrar Novo Produto
                    </h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        Adicione um novo produto ao seu catálogo
                    </p>
                </div>
            </div>

            <!-- Success Message -->
            <Transition name="slide-down">
                <div
                    v-if="successMessage"
                    class="flex items-center gap-2 rounded-lg bg-green-50 dark:bg-green-900/20 p-4 text-sm font-medium text-green-700 dark:text-green-400 border border-green-200 dark:border-green-800"
                    role="status"
                    aria-live="polite"
                >
                    <Check class="h-4 w-4" aria-hidden="true" />
                    <span>{{ successMessage }}</span>
                </div>
            </Transition>

            <!-- Form Card -->
            <div
                class="overflow-hidden rounded-md border border-gray-200 dark:border-neutral-800 bg-white shadow-sm dark:bg-zinc-900"
            >
                <div class="p-6">
                    <Form
                        :action="FruitController.store().url"
                        method="post"
                        v-slot="{ errors, processing, recentlySuccessful }"
                        class="space-y-6"
                        :reset-on-success="['name']"
                    >
                        <div class="grid gap-2">
                            <Label for="name">Nome do Produto *</Label>
                            <Input
                                id="name"
                                name="name"
                                type="text"
                                placeholder="Ex: Maçã, Banana, Tomate..."
                                required
                                autocomplete="off"
                                class="w-full"
                                :class="{
                                    'border-red-500 focus:ring-red-500': errors.name,
                                }"
                            />
                            <InputError class="mt-2" :message="errors.name" />
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                Digite o nome do produto que deseja cadastrar
                            </p>
                        </div>

                        <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-200 dark:border-neutral-800">
                            <Button
                                type="button"
                                variant="outline"
                                @click="$inertia.visit('/products/new')"
                                :disabled="processing"
                            >
                                Limpar
                            </Button>
                            <Button
                                type="submit"
                                :disabled="processing"
                                class="bg-gradient-to-r from-[#D9A84E] to-[#B58737] text-white hover:shadow-lg hover:shadow-[#D9A84E]/25 focus:ring-4 focus:ring-[#D9A84E]/30"
                            >
                                <template v-if="!processing">
                                    <Check class="mr-2 h-4 w-4" aria-hidden="true" />
                                    Cadastrar Produto
                                </template>
                                <template v-else>
                                    <svg
                                        class="mr-2 h-4 w-4 animate-spin"
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
                                    Cadastrando...
                                </template>
                            </Button>
                        </div>

                        <Transition name="fade">
                            <div
                                v-if="recentlySuccessful"
                                class="flex items-center gap-2 rounded-lg bg-green-50 dark:bg-green-900/20 p-3 text-sm font-medium text-green-700 dark:text-green-400"
                            >
                                <Check class="h-4 w-4" aria-hidden="true" />
                                <span>Produto cadastrado com sucesso!</span>
                            </div>
                        </Transition>
                    </Form>
                </div>
            </div>

            <!-- Info Card -->
            <div
                class="rounded-xl border border-[#D9A84E]/30 bg-gradient-to-br from-[#D9A84E]/8 via-white to-transparent p-5 shadow-sm dark:from-[#D9A84E]/12 dark:via-zinc-900 dark:to-transparent dark:border-[#D9A84E]/25"
            >
                <div class="flex items-start gap-4">
                    <div class="flex-shrink-0">
                        <div
                            class="flex h-10 w-10 items-center justify-center rounded-full bg-[#D9A84E]/10 dark:bg-[#D9A84E]/20"
                        >
                            <AlertCircle class="h-5 w-5 text-[#D9A84E]" aria-hidden="true" />
                        </div>
                    </div>
                    <div class="flex-1 space-y-2">
                        <h3 class="text-base font-semibold text-gray-900 dark:text-white">
                            Informação Importante
                        </h3>
                        <p class="text-sm leading-relaxed text-gray-700 dark:text-gray-300">
                            O produto será cadastrado com preço inicial de <strong>R$ 0,00</strong>.
                            Você poderá definir o preço do dia na página de <strong>Preços</strong>.
                        </p>
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

.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.3s ease;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}
</style>

