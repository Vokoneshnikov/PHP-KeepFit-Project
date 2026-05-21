<script setup>
import { onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { profileService } from '@/services/profileService'

const router = useRouter()

const form = ref({
  name: '',
  email: '',
  gender: 'male',
  birthDate: '',
})

const loading = ref(false)
const saving = ref(false)
const error = ref('')
const success = ref('')

async function loadProfile() {
  error.value = ''
  loading.value = true

  try {
    const profile = await profileService.getProfile()

    form.value = {
      name: profile.name || '',
      email: profile.email || '',
      gender: profile.gender || 'male',
      birthDate: '',
    }
  } catch (e) {
    error.value = e.response?.data?.error || 'Не удалось загрузить данные профиля'
  } finally {
    loading.value = false
  }
}

async function saveProfile() {
  error.value = ''
  success.value = ''
  saving.value = true

  const payload = {
    name: form.value.name,
    email: form.value.email,
    gender: form.value.gender,
  }

  if (form.value.birthDate) {
    payload.birthDate = form.value.birthDate
  }

  try {
    await profileService.updateProfile(payload)

    success.value = 'Профиль успешно обновлён'

    setTimeout(() => {
      router.push('/profile')
    }, 700)
  } catch (e) {
    error.value = e.response?.data?.error || 'Не удалось обновить профиль'
  } finally {
    saving.value = false
  }
}

onMounted(loadProfile)
</script>

<template>
  <div class="mx-auto max-w-2xl space-y-6">
    <button
      type="button"
      class="text-sm font-medium text-green-700 hover:underline"
      @click="router.back()"
    >
      ← Назад
    </button>

    <div>
      <h1 class="text-3xl font-bold text-gray-800">Редактирование профиля</h1>

      <p class="mt-1 text-gray-500">Измените основные данные аккаунта.</p>
    </div>

    <div class="rounded-3xl bg-white p-6 shadow-sm">
      <div v-if="loading" class="text-gray-500">Загружаем данные...</div>

      <p v-if="error" class="mb-4 rounded-2xl bg-red-50 p-4 text-red-600">
        {{ error }}
      </p>

      <p v-if="success" class="mb-4 rounded-2xl bg-green-50 p-4 text-green-700">
        {{ success }}
      </p>

      <form v-if="!loading" class="space-y-5" @submit.prevent="saveProfile">
        <div>
          <label class="mb-1 block text-sm font-medium text-gray-700"> Имя </label>

          <input
            v-model="form.name"
            type="text"
            class="w-full rounded-xl border border-gray-200 px-4 py-3 focus:border-green-500"
            required
          />
        </div>

        <div>
          <label class="mb-1 block text-sm font-medium text-gray-700"> Email </label>

          <input
            v-model="form.email"
            type="email"
            class="w-full rounded-xl border border-gray-200 px-4 py-3 focus:border-green-500"
            required
          />
        </div>

        <div>
          <label class="mb-1 block text-sm font-medium text-gray-700"> Пол </label>

          <select
            v-model="form.gender"
            class="w-full rounded-xl border border-gray-200 px-4 py-3 focus:border-green-500"
          >
            <option value="male">Мужской</option>

            <option value="female">Женский</option>
          </select>
        </div>

        <div>
          <label class="mb-1 block text-sm font-medium text-gray-700"> Дата рождения </label>

          <input
            v-model="form.birthDate"
            type="date"
            class="w-full rounded-xl border border-gray-200 px-4 py-3 focus:border-green-500"
          />

          <p class="mt-2 text-sm text-gray-500">
            Если дату рождения менять не нужно, оставьте поле пустым.
          </p>
        </div>

        <button
          type="submit"
          class="w-full rounded-xl bg-green-600 py-3 font-semibold text-white hover:bg-green-700 disabled:opacity-60"
          :disabled="saving"
        >
          {{ saving ? 'Сохраняем...' : 'Сохранить изменения' }}
        </button>
      </form>
    </div>
  </div>
</template>
