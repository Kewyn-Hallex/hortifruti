<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { login } from '@/routes';
import { store } from '@/routes/register';
import { Form, Head } from '@inertiajs/vue3';
</script>

<template>

	<Head title="Register">
		<link rel="preconnect" href="https://rsms.me/" />
		<link rel="stylesheet" href="https://rsms.me/inter/inter.css" />
		<link rel="stylesheet"
			href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css" />
	</Head>

	<!-- Container igual ao Login.vue -->
	<div
		class="flex flex-col items-center justify-center min-h-screen bg-[#FDFDFC] text-[#1b1b18] px-6 text-center relative overflow-hidden dark:bg-[#0a0a0a]">
		<main class="relative z-10 flex flex-col items-center justify-center w-full max-w-md">
			<!-- Logo -->
			<div class="flex items-center justify-center mb-6">
				<img src="../../../images/logoH.png"
					alt="Logo Joselito Teixeira" class="w-32 h-auto object-contain" />
			</div>

			<!-- Título -->
			<h2 class="text-2xl lg:text-3xl font-extrabold text-[#2E2E2E] mb-2 dark:text-white">Crie sua conta</h2>
			<p class="text-[#777] mb-8 prioridade dark:text-gray-300">Insira seus dados abaixo para criar sua conta</p>

			<!-- Formulário (mesma estrutura do Register original) -->
			<Form v-bind="store.form()" :reset-on-success="['password', 'password_confirmation']"
				v-slot="{ errors, processing }" class="w-full">
				<div class="grid gap-6">
					<div class="grid gap-2 text-left">
						<Label for="name" class="font-medium dark:text-gray-300">Nome completo</Label>
						<Input id="name" type="text" required autofocus :tabindex="1" autocomplete="name" name="name"
							placeholder="Nome completo" />
						<InputError :message="errors.name" />
					</div>

					<div class="grid gap-2 text-left">
						<Label for="email" class="font-medium dark:text-gray-300">Endereço de e-mail</Label>
						<Input id="email" type="email" required :tabindex="2" autocomplete="email" name="email"
							placeholder="email@exemplo.com" />
						<InputError :message="errors.email" />
					</div>

					<div class="grid gap-2 text-left">
						<Label for="password" class="font-medium dark:text-gray-300">Senha</Label>
						<Input id="password" type="password" required :tabindex="3" autocomplete="new-password"
							name="password" placeholder="Senha" />
						<InputError :message="errors.password" />
					</div>

					<div class="grid gap-2 text-left">
						<Label for="password_confirmation" class="font-medium dark:text-gray-300">Confirmar
							senha</Label>
						<Input id="password_confirmation" type="password" required :tabindex="4"
							autocomplete="new-password" name="password_confirmation" placeholder="Confirmar senha" />
						<InputError :message="errors.password_confirmation" />
					</div>

					<Button type="submit" class="mt-4 w-full flex items-center justify-center gap-2 px-8 py-3 
    bg-gradient-to-r from-[#D9A84E] to-[#B58737]
    text-black font-semibold rounded-lg shadow-md hover:shadow-lg 
    hover:bg-gradient-to-l focus:ring-4 focus:ring-[#D9A84E]/50 
    shadow-[#D9A84E]/30 transition-all duration-300 dark:text-white" tabindex="5" :disabled="processing"
						data-test="register-user-button">
						<Spinner v-if="processing" />
						Criar conta
					</Button>
				</div>

				<div class="text-center text-sm text-[#777] mt-6 dark:text-gray-400">
					Já tem uma conta?
					<TextLink :href="login()" :tabindex="6" class="text-[#003366] font-semibold hover:underline">Entrar
					</TextLink>
				</div>
			</Form>
		</main>
	</div>
</template>
*** End Patch
