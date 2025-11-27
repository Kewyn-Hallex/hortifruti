<script setup lang="ts">
import { Plus } from 'lucide-vue-next';
</script>

<template>
    <!-- Botão alinhado à direita -->
    <div class="flex w-full justify-end">
        <Button class="w-fit flex items-center justify-center gap-2 px-6 py-3
                    bg-gradient-to-r from-orange-500 to-[#E67300]
                    text-white font-semibold rounded-lg shadow-md hover:shadow-lg 
                    hover:bg-gradient-to-l focus:ring-4 focus:ring-orange-500/50 
                    shadow-orange-500/30 transition-all duration-300">

            <Plus class="w-5 h-5" />
            Adicionar
        </Button>
    </div>
</template>