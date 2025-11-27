<script setup lang="ts">
import { dashboard, login, register } from '@/routes';
import { Head, Link } from '@inertiajs/vue3';

withDefaults(
    defineProps<{ canRegister: boolean }>(),
    { canRegister: true },
);
</script>

<template>

    <Head title="HortiFácil — Gestão Inteligente">
        <link rel="stylesheet" href="https://rsms.me/inter/inter.css" />
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
        <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;800&display=swap" rel="stylesheet">

    </Head>

    <!-- CONTAINER PRINCIPAL -->
    <div class="min-h-screen flex flex-col items-center justify-center px-6 text-center transition-colors duration-500
      bg-[#fffdf7] text-[#1b1b18] dark:bg-[#0a0a0a] dark:text-[#f8f8f8]">

        <!-- Ornamentos dourados -->
        <div
            class="absolute top-0 left-0 w-40 h-40 rounded-full opacity-20 bg-gradient-to-br from-[#d4af37] to-[#9b7c23] blur-2xl">
        </div>
        <div
            class="absolute bottom-0 right-0 w-52 h-52 rounded-full opacity-25 bg-gradient-to-tr from-[#e6c453] to-[#b18f37] blur-3xl">
        </div>


        <!-- LOGO -->
        <img src="../../images/logoH.png" alt="Logo"
            class="w-32 sm:w-48 object-contain opacity-90 hover:opacity-100 transition-all duration-300 drop-shadow-lg" />

        <!-- TÍTULO -->
        <h1 class="mt-8 font-extrabold text-5xl md:text-6xl tracking-tight leading-tight font-[Outfit]">
            <span class="text-transparent bg-clip-text bg-gradient-to-r from-[#d9a84e] to-[#b4842a]">
                HortiFácil
            </span>
            <span class="block text-xl md:text-3xl font-light opacity-80">
                Gestão de Hortifruti
            </span>
        </h1>



        <!-- SUBTÍTULO -->
        <p class="mt-3 text-gray-600 dark:text-gray-300 text-lg font-medium max-w-md">
            Organize vendas, estoque e entregas — rápido, simples e intuitivo.
        </p>

        <!-- AÇÕES (Botões) -->
        <div class="mt-10 flex flex-col sm:flex-row items-center justify-center gap-4 w-full max-w-md">

            <!-- LOGIN BTN -->
            <Link :href="login()" class="flex items-center justify-center w-full sm:w-auto gap-2 px-8 py-4 text-base font-semibold
        text-white bg-gradient-to-r from-[#E8C76F] to-[#D1A64B] rounded-xl 
        hover:bg-gradient-to-l focus:ring-4 focus:ring-[#E8C76F]/40 transition-all duration-300
        shadow-md hover:shadow-xl">
            Entrar
            <i class="bi bi-arrow-right"></i>
            </Link>

            <!-- REGISTER BTN -->
            <Link v-if="canRegister" :href="register()" class="flex items-center justify-center w-full sm:w-auto gap-2 px-8 py-4 text-base font-semibold
        border border-[#C8A24A] text-[#C8A24A] bg-transparent rounded-xl
        hover:bg-[#C8A24A]/15 focus:ring-4 focus:ring-[#C8A24A]/30 transition-all duration-300
        dark:text-[#E2C06A] dark:border-[#E2C06A] dark:hover:bg-[#E2C06A]/20">
            <i class="bi bi-person-plus"></i>
            Criar Conta
            </Link>

        </div>

        <!-- FOOTER -->
        <footer class="mt-14 text-sm opacity-60 hover:opacity-100 transition font-medium font-[Inter]">
            © {{ new Date().getFullYear() }} — HortiFácil. Todos os direitos reservados.
        </footer>
    </div>
</template>


<style scoped>
h1,
p,
button {
    font-family: 'Inter', sans-serif;
}

.blob {
    position: absolute;
    border-radius: 50%;
    filter: blur(70px);
    opacity: 0.35;
    mix-blend-mode: soft-light;
}

/* Ornamento 1 */
.blob-a {
    width: 350px;
    height: 350px;
    top: 20%;
    left: -120px;
    background: radial-gradient(circle, #d4af37, #8d6b05);
}

/* Ornamento 2 */
.blob-b {
    width: 420px;
    height: 420px;
    bottom: -120px;
    right: -100px;
    background: radial-gradient(circle, #b8860b, #f0d58c);
}

/* Ornamento 3 */
.blob-c {
    width: 250px;
    height: 250px;
    top: 50%;
    right: 35%;
    background: radial-gradient(circle, #ffecb3, #a0782d);
}


/* Botão dourado */
.gold-btn {
    padding: 12px 32px;
    background: linear-gradient(90deg, #d4af37, #b8860b);
    color: #fff;
    font-weight: 600;
    border-radius: 14px;
    display: flex;
    align-items: center;
    gap: 8px;
    box-shadow: 0 6px 20px rgba(212, 175, 55, 0.45);
    transition: .25s;
}

.gold-btn:hover {
    transform: scale(1.06);
    background: linear-gradient(90deg, #b8860b, #8d6b05);
}
</style>
