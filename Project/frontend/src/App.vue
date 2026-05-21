<script setup>
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import AppNavbar from '@/components/layout/AppNavbar.vue'

const route = useRoute()

const showNavbar = computed(() => {
  return !['login', 'register'].includes(route.name)
})
</script>

<template>
  <div class="min-h-screen bg-[#f7faf7]">
    <AppNavbar v-if="showNavbar" />

    <main class="mx-auto max-w-6xl px-4 py-6">
      <RouterView />
    </main>
  </div>
</template>
