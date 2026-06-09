<script setup>
import { useRouter, useRoute } from 'vue-router'
import { authService } from '@/services/authService'

const router = useRouter()
const route = useRoute()

function logout() {
  authService.logout()
  router.push('/login')
}

function isActive(path) {
  return route.path === path || route.path.startsWith(path + '/')
}
</script>

<template>
  <header class="border-b border-green-100 bg-white shadow-sm">
    <div class="mx-auto flex max-w-6xl items-center justify-between px-4 py-4">
      <RouterLink to="/diary" class="text-2xl font-bold text-green-700"> KeepFit </RouterLink>

      <nav class="flex items-center gap-2 text-sm font-medium">
        <RouterLink
          to="/diary"
          class="rounded-xl px-3 py-2 transition"
          :class="
            isActive('/diary')
              ? 'bg-green-50 text-green-700'
              : 'text-gray-700 hover:bg-green-50 hover:text-green-700'
          "
        >
          Главная
        </RouterLink>

        <RouterLink
          to="/profile"
          class="rounded-xl px-3 py-2 transition"
          :class="
            isActive('/profile')
              ? 'bg-green-50 text-green-700'
              : 'text-gray-700 hover:bg-green-50 hover:text-green-700'
          "
        >
          Профиль
        </RouterLink>

        <RouterLink
          to="/statistics"
          class="rounded-xl px-3 py-2 transition"
          :class="
            isActive('/statistics')
              ? 'bg-green-50 text-green-700'
              : 'text-gray-700 hover:bg-green-50 hover:text-green-700'
          "
        >
          Статистика
        </RouterLink>

        <button
          type="button"
          class="ml-2 rounded-xl bg-green-600 px-4 py-2 text-white transition hover:bg-green-700"
          @click="logout"
        >
          Выйти
        </button>
      </nav>
    </div>
  </header>
</template>
