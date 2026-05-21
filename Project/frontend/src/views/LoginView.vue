<script setup>
import { ref } from 'vue'
import { useRouter, RouterLink } from 'vue-router'
import { authService } from '@/services/authService'

const router = useRouter()

const email = ref('')
const password = ref('')
const error = ref('')
const loading = ref(false)

async function submitLogin() {
  error.value = ''
  loading.value = true

  try {
    await authService.login({
      email: email.value,
      password: password.value,
    })

    router.push('/diary')
  } catch (e) {
    error.value = e.response?.data?.error || 'Не удалось войти в аккаунт'
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="flex min-h-screen items-center justify-center bg-[#f7faf7] px-4">
    <div class="w-full max-w-md rounded-3xl bg-white p-8 shadow-lg">
      <h1 class="mb-2 text-center text-3xl font-bold text-green-700">
        KeepFit
      </h1>

      <p class="mb-8 text-center text-gray-500">
        Вход в дневник питания
      </p>

      <form class="space-y-5" @submit.prevent="submitLogin">
        <div>
          <label class="mb-1 block text-sm font-medium text-gray-700">
            Email
          </label>
          <input
            v-model="email"
            type="email"
            class="w-full rounded-xl border border-gray-200 px-4 py-3 focus:border-green-500"
            placeholder="example@mail.com"
            required
          />
        </div>

        <div>
          <label class="mb-1 block text-sm font-medium text-gray-700">
            Пароль
          </label>
          <input
            v-model="password"
            type="password"
            class="w-full rounded-xl border border-gray-200 px-4 py-3 focus:border-green-500"
            placeholder="Введите пароль"
            required
          />
        </div>

        <p v-if="error" class="rounded-xl bg-red-50 px-4 py-3 text-sm text-red-600">
          {{ error }}
        </p>

        <button
          type="submit"
          class="w-full rounded-xl bg-green-600 py-3 font-semibold text-white hover:bg-green-700 disabled:opacity-60"
          :disabled="loading"
        >
          {{ loading ? 'Входим...' : 'Войти' }}
        </button>
      </form>

      <p class="mt-6 text-center text-sm text-gray-600">
        Нет аккаунта?
        <RouterLink to="/register" class="font-semibold text-green-700 hover:underline">
          Зарегистрироваться
        </RouterLink>
      </p>
    </div>
  </div>
</template>
