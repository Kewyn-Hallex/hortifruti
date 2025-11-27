<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Form, Head, usePage } from '@inertiajs/vue3';
import { type BreadcrumbItem } from '@/types';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import InputError from '@/components/InputError.vue';
import { UserPlus, Check, AlertCircle } from 'lucide-vue-next';
import { computed } from 'vue';
import ClientController from '@/actions/App/Http/Controllers/ClientController';

const page = usePage();
const successMessage = computed(() => (page.props.flash as { success?: string })?.success);

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Cadastrar Cliente',
        href: ClientController.create().url,
    },
];
</script>

<template>
    <Head title="Cadastrar Cliente" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 px-4 py-5 sm:px-6 max-w-3xl mx-auto">
            <!-- Header -->
            <div class="flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-[#D9A84E]/10 dark:bg-[#D9A84E]/20">
                    <UserPlus class="h-5 w-5 text-[#D9A84E]" aria-hidden="true" />
                </div>
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                        Cadastrar Novo Cliente
                    </h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        Adicione um novo cliente ao seu sistema
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
                        v-bind="ClientController.store.form()"
                        v-slot="{ errors, processing, recentlySuccessful }"
                        class="space-y-6"
                        :reset-on-success="['name', 'phone', 'address']"
                    >
                        <div class="grid gap-2">
                            <Label for="name">Nome do Cliente *</Label>
                            <Input
                                id="name"
                                name="name"
                                type="text"
                                placeholder="Ex: João Silva, Maria Santos..."
                                required
                                autocomplete="name"
                                class="w-full"
                                :class="{
                                    'border-red-500 focus:ring-red-500': errors.name,
                                }"
                            />
                            <InputError class="mt-2" :message="errors.name" />
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                Digite o nome completo do cliente
                            </p>
                        </div>

                        <div class="grid gap-2">
                            <Label for="phone">Número de Telefone</Label>
                            <Input
                                id="phone"
                                name="phone"
                                type="tel"
                                placeholder="Ex: (91) 99999-9999"
                                autocomplete="tel"
                                class="w-full"
                                :class="{
                                    'border-red-500 focus:ring-red-500': errors.phone,
                                }"
                            />
                            <InputError class="mt-2" :message="errors.phone" />
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                Telefone de contato do cliente (opcional)
                            </p>
                        </div>

                        <div class="grid gap-2">
                            <Label for="address">Endereço</Label>
                            <textarea
                                id="address"
                                name="address"
                                rows="3"
                                placeholder="Ex: Rua Exemplo, 123 - Bairro, Cidade/UF - CEP 00000-000"
                                class="w-full rounded-md border border-gray-300 dark:border-neutral-700 bg-white dark:bg-zinc-900 px-3 py-2 text-sm text-gray-900 dark:text-white placeholder:text-gray-400 dark:placeholder:text-gray-500 focus:outline-none focus:ring-2 focus:ring-[#D9A84E] focus:border-transparent"
                                :class="{
                                    'border-red-500 focus:ring-red-500': errors.address,
                                }"
                            ></textarea>
                            <InputError class="mt-2" :message="errors.address" />
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                Endereço completo do cliente (opcional)
                            </p>
                        </div>

                        <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-200 dark:border-neutral-800">
                            <Button
                                type="button"
                                variant="outline"
                                @click="$inertia.visit(ClientController.create().url)"
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
                                    Cadastrar Cliente
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
                                <span>Cliente cadastrado com sucesso!</span>
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
                            Após cadastrar o cliente, você poderá selecioná-lo ao criar um novo pedido. 
                            Os dados do cliente aparecerão automaticamente na nota fiscal.
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

