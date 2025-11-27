<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import AuthBase from '@/layouts/AuthLayout.vue';
import { register } from '@/routes';
import { store } from '@/routes/login';
import { request } from '@/routes/password';
import { Form, Head } from '@inertiajs/vue3';

defineProps<{
    status?: string;
    canResetPassword: boolean;
    canRegister: boolean;
}>();
</script>

<template>

    <Head title="Login">
        <link rel="preconnect" href="https://rsms.me/" />
        <link rel="stylesheet" href="https://rsms.me/inter/inter.css" />
        <link rel="stylesheet"
            href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css" />
    </Head>

    <!-- Container principal -->
    <div
        class="flex flex-col items-center justify-center min-h-screen bg-[#FDFDFC] text-[#1b1b18] px-6 text-center relative overflow-hidden dark:bg-[#0a0a0a]">
        <!-- Conteúdo central -->
        <main class="relative z-10 flex flex-col items-center justify-center w-full max-w-md">
            <!-- Logo -->
            <div class="flex items-center justify-center mb-6">
                <img src="../../../images/logoH.png"
                    alt="Logo Joselito Teixeira" class="w-32 h-auto object-contain" />
            </div>

            <!-- Título -->
            <h2 class="text-2xl lg:text-3xl font-extrabold text-[#2E2E2E] mb-2 dark:text-white">
                Faça login na sua conta
            </h2>
            <p class="text-[#777] mb-8 prioridade dark:text-gray-300">Digite seu e-mail e senha abaixo para entrar</p>

            <!-- Mensagem de status -->
            <div v-if="status" class="mb-4 text-sm font-medium text-green-600 text-center">
                {{ status }}
            </div>

            <!-- Formulário -->
            <Form v-bind="store.form()" :reset-on-success="['password']" v-slot="{ errors, processing }" class="w-full">
                <div class="grid gap-6">
                    <!-- Email -->
                    <div class="grid gap-2 text-left">
                        <Label for="email" class="font-medium dark:text-gray-300">Endereço de e-mail</Label>
                        <Input id="email" type="email" name="email" required autofocus :tabindex="1"
                            autocomplete="email" placeholder="email@exemplo.com" />
                        <InputError :message="errors.email" />
                    </div>

                    <!-- Senha -->
                    <div class="grid gap-2 text-left">
                        <div class="flex items-center justify-between">
                            <Label for="password" class="font-medium dark:text-gray-300">Senha</Label>
                            <TextLink v-if="canResetPassword" :href="request()"
                                class="text-sm text-[#003366] hover:underline" :tabindex="5">
                                Esqueceu sua senha?
                            </TextLink>
                        </div>
                        <Input id="password" type="password" name="password" required :tabindex="2"
                            autocomplete="current-password" placeholder="Senha" />
                        <InputError :message="errors.password" />
                    </div>

                    <!-- Lembrar -->
                    <div class="flex items-center justify-start gap-2">
                        <Checkbox id="remember" name="remember" :tabindex="3" />
                        <Label for="remember" class="text-sm dark:text-gray-300">Lembrar-se de mim</Label>
                    </div>

                    <!-- Botão Entrar -->
                    <Button type="submit" class="mt-4 w-full flex items-center justify-center gap-2 px-8 py-3 
    bg-gradient-to-r from-[#D9A84E] to-[#B58737]
    text-black font-semibold rounded-lg shadow-md hover:shadow-lg 
    hover:bg-gradient-to-l focus:ring-4 focus:ring-[#D9A84E]/50 
    shadow-[#D9A84E]/30 transition-all duration-300 dark:text-white" :tabindex="4" :disabled="processing"
                        data-test="login-button">

                        <Spinner v-if="processing" />
                        <i v-else class="bi bi-box-arrow-in-right"></i>
                        Entrar
                    </Button>

                </div>

                <!-- Link de registro -->
                <div class="text-center text-sm text-[#777] mt-6 dark:text-gray-400" v-if="canRegister">
                    Não tem uma conta?
                    <TextLink :href="register()" :tabindex="5" class="text-[#003366] font-semibold hover:underline">
                        Cadastre-se
                    </TextLink>
                </div>
            </Form>
        </main>
    </div>
</template>

<style>
@import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap');

.hero-title {
    font-family: 'Poppins', sans-serif;
    letter-spacing: 1px;
}

.prioridade {
    font-family: 'Poppins', sans-serif;
    letter-spacing: 0.5px;
    font-size: 1.1rem;
}
</style>
