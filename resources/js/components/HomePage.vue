<template>
  <div class="relative min-h-screen flex flex-col justify-center items-center bg-white overflow-hidden">
    <!-- Geometric background lines -->
    <svg
      class="absolute inset-0 w-full h-full pointer-events-none select-none"
      width="100%"
      height="100%"
      viewBox="0 0 1440 900"
      fill="none"
      xmlns="http://www.w3.org/2000/svg"
      style="z-index: 0;"
    >
      <g opacity="0.13">
        <path d="M0 100L1440 0" stroke="#BFC3C9" stroke-width="2" />
        <path d="M0 300L1440 200" stroke="#BFC3C9" stroke-width="2" />
        <path d="M0 500L1440 400" stroke="#BFC3C9" stroke-width="2" />
        <path d="M0 700L1440 600" stroke="#BFC3C9" stroke-width="2" />
        <path d="M0 900L1440 800" stroke="#BFC3C9" stroke-width="2" />
        <path d="M200 0L400 900" stroke="#BFC3C9" stroke-width="2" />
        <path d="M600 0L800 900" stroke="#BFC3C9" stroke-width="2" />
        <path d="M1000 0L1200 900" stroke="#BFC3C9" stroke-width="2" />
      </g>
    </svg>
    <!-- Content -->
    <div class="relative z-10 flex flex-col items-center w-full px-4">
      <!-- Logo -->
      <div class="flex items-center mb-8 mt-2 select-none">
        <!-- Flame + Building Icon -->
        <svg width="48" height="48" viewBox="0 0 48 48" fill="none" class="mr-3">
          <g>
            <path d="M24 4C24 4 28 12 24 20C20 12 24 4 24 4Z" fill="#E67300"/>
            <rect x="19" y="20" width="10" height="18" rx="2" fill="#1A3365"/>
            <rect x="22" y="24" width="4" height="14" rx="1.5" fill="#E67300"/>
            <rect x="25" y="28" width="2" height="10" rx="1" fill="#fff"/>
          </g>
        </svg>
        <span class="text-3xl font-bold font-sans" style="font-family: 'Inter, sans-serif'">
          <span style="color: #1A3365">JOSELITO</span>
          <span style="color: #E67300"> TEIXEIRA</span>
        </span>
      </div>
      <!-- Title -->
      <h1 class="text-center font-bold" style="font-family: 'Inter, sans-serif'; color: #2E2E2E; font-size: 2.5rem; line-height: 1.1;">
        Bem-vindo ao Joselito Texiiera
      </h1>
      <!-- Subtitle -->
      <p class="mt-2 text-center font-light" style="color: #777; font-family: 'Inter, sans-serif'; font-weight: 400; font-size: 1.25rem;">
        Sua Segurança, Nossa Prioridade
      </p>
      <!-- Buttons -->
      <div class="mt-10 flex flex-col gap-5 w-full max-w-xs">
        <RouterLink
          to="/login"
          class="flex items-center justify-center gap-3 w-full py-4 rounded-lg shadow-md text-white text-lg font-semibold bg-[#D2691E] transition-all duration-300 hover:shadow-lg hover:-translate-y-0.5 focus:outline-none focus:ring-2 focus:ring-[#D2691E] focus:ring-opacity-50"
          style="border-radius: 8px;"
        >
          <font-awesome-icon :icon="['fas', 'lock']" class="text-xl" />
          Entrar
        </RouterLink>
        <RouterLink
          to="/register"
          class="flex items-center justify-center gap-3 w-full py-4 rounded-lg shadow-md text-white text-lg font-semibold bg-[#003366] transition-all duration-300 hover:shadow-lg hover:-translate-y-0.5 focus:outline-none focus:ring-2 focus:ring-[#003366] focus:ring-opacity-50"
          style="border-radius: 8px;"
        >
          <font-awesome-icon :icon="['fas', 'user-plus']" class="text-xl" />
          Cadastre-se
        </RouterLink>
      </div>
    </div>
  </div>
</template>

<script setup>
import { RouterLink } from 'vue-router';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
// Certifique-se de registrar os ícones lock e user-plus no setup do projeto
</script>

<style scoped>
@import url('https://rsms.me/inter/inter.css');
</style>
