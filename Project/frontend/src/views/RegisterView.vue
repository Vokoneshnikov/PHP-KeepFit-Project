<script setup>
import { ref } from 'vue'
import { useRouter, RouterLink } from 'vue-router'
import { authService } from '@/services/authService'

const router = useRouter()

const form = ref({
  email: '',
  password: '',
  name: '',
  gender: 'male',
  birthDate: '',
})

const error = ref('')
const loading = ref(false)

async function submitRegister() {
  error.value = ''
  loading.value = true

  try {
    await authService.register(form.value)
    router.push('/login')
  } catch (e) {
    error.value = e.response?.data?.error || 'Не удалось зарегистрироваться'
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="flex min-h-screen items-center justify-center bg-[#f7faf7] px-4 py-8">
    <div class="w-full max-w-lg rounded-3xl bg-white p-8 shadow-lg">
      <h1 class="mb-2 text-center text-3xl font-bold text-green-700">
        Регистрация
      </h1>

      <p class="mb-8 text-center text-gray-500">
        Создайте аккаунт KeepFit
      </p>

      <form class="space-y-5" @submit.prevent="submitRegister">
        <div>
          <label class="mb-1 block text-sm font-medium text-gray-700">
            Имя
          </label>
          <input
            v-model="form.name"
            type="text"
            class="w-full rounded-xl border border-gray-200 px-4 py-3 focus:border-green-500"
            required
          />
        </div>

        <div>
          <label class="mb-1 block text-sm font-medium text-gray-700">
            Email
          </label>
          <input
            v-model="form.email"
            type="email"
            class="w-full rounded-xl border border-gray-200 px-4 py-3 focus:border-green-500"
            required
          />
        </div>

        <div>
          <label class="mb-1 block text-sm font-medium text-gray-700">
            Пароль
          </label>
          <input
            v-model="form.password"
            type="password"
            class="w-full rounded-xl border border-gray-200 px-4 py-3 focus:border-green-500"
            required
          />
        </div>

        <div class="grid gap-4 md:grid-cols-2">
          <div>
            <label class="mb-1 block text-sm font-medium text-gray-700">
              Пол
            </label>
            <select
              v-model="form.gender"
              class="w-full rounded-xl border border-gray-200 px-4 py-3 focus:border-green-500"
            >
              <option value="male">Мужской</option>
              <option value="female">Женский</option>
            </select>
          </div>

          <div>
            <label class="mb-1 block text-sm font-medium text-gray-700">
              Дата рождения
            </label>
            <input
              v-model="form.birthDate"
              type="date"
              class="w-full rounded-xl border border-gray-200 px-4 py-3 focus:border-green-500"
              required
            />
          </div>
        </div>

        <p v-if="error" class="rounded-xl bg-red-50 px-4 py-3 text-sm text-red-600">
          {{ error }}
        </p>

        <button
          type="submit"
          class="w-full rounded-xl bg-green-600 py-3 font-semibold text-white hover:bg-green-700 disabled:opacity-60"
          :disabled="loading"
        >
          {{ loading ? 'Создаём аккаунт...' : 'Зарегистрироваться' }}
        </button>
      </form>

      <p class="mt-6 text-center text-sm text-gray-600">
        Уже есть аккаунт?
        <RouterLink to="/login" class="font-semibold text-green-700 hover:underline">
          Войти
        </RouterLink>
      </p>
    </div>
  </div>
</template>
