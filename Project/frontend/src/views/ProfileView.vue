<script setup>
import { onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { profileService } from '@/services/profileService'

const router = useRouter()

const profile = ref(null)
const loading = ref(false)
const error = ref('')

const genderLabels = {
  male: 'Мужской',
  female: 'Женский',
}

const goalLabels = {
  lose: 'Похудение',
  maintain: 'Удержание веса',
  gain: 'Набор массы',
}

const activityLabels = {
  sedentary: 'Минимальная активность',
  light: 'Лёгкая активность',
  moderate: 'Средняя активность',
  active: 'Высокая активность',
  very_active: 'Очень высокая активность',
}

async function loadProfile() {
  error.value = ''
  loading.value = true

  try {
    profile.value = await profileService.getProfile()
  } catch (e) {
    error.value = e.response?.data?.error || 'Не удалось загрузить профиль'
  } finally {
    loading.value = false
  }
}

onMounted(loadProfile)
</script>

<template>
  <div class="space-y-6">
    <div>
      <h1 class="text-3xl font-bold text-gray-800">Профиль</h1>

      <p class="mt-1 text-gray-500">Основная информация, параметры тела и дневная норма КБЖУ.</p>
    </div>

    <div v-if="loading" class="rounded-3xl bg-white p-6 text-gray-500 shadow-sm">
      Загружаем профиль...
    </div>

    <p v-if="error" class="rounded-2xl bg-red-50 p-4 text-red-600">
      {{ error }}
    </p>

    <template v-if="profile">
      <section class="grid gap-5 md:grid-cols-2">
        <div class="rounded-3xl bg-white p-6 shadow-sm">
          <h2 class="mb-4 text-xl font-bold text-gray-800">Основная информация</h2>

          <div class="space-y-3 text-sm">
            <div class="flex justify-between gap-4">
              <span class="text-gray-500">Имя</span>
              <span class="font-semibold text-gray-800">{{ profile.name }}</span>
            </div>

            <div class="flex justify-between gap-4">
              <span class="text-gray-500">Email</span>
              <span class="font-semibold text-gray-800">{{ profile.email }}</span>
            </div>

            <div class="flex justify-between gap-4">
              <span class="text-gray-500">Пол</span>
              <span class="font-semibold text-gray-800">
                {{ genderLabels[profile.gender] || profile.gender }}
              </span>
            </div>
          </div>

          <button
            type="button"
            class="mt-6 w-full rounded-xl bg-green-600 py-3 font-semibold text-white hover:bg-green-700"
            @click="router.push('/profile/edit')"
          >
            Редактировать профиль
          </button>
        </div>

        <div class="rounded-3xl bg-white p-6 shadow-sm">
          <h2 class="mb-4 text-xl font-bold text-gray-800">Параметры</h2>

          <div class="space-y-3 text-sm">
            <div class="flex justify-between gap-4">
              <span class="text-gray-500">Вес</span>
              <span class="font-semibold text-gray-800">
                {{ profile.parameters?.weight ?? 'Не указано' }} кг
              </span>
            </div>

            <div class="flex justify-between gap-4">
              <span class="text-gray-500">Рост</span>
              <span class="font-semibold text-gray-800">
                {{ profile.parameters?.height ?? 'Не указано' }} см
              </span>
            </div>

            <div class="flex justify-between gap-4">
              <span class="text-gray-500">Активность</span>
              <span class="font-semibold text-gray-800">
                {{ activityLabels[profile.parameters?.activityLevel] || 'Не указано' }}
              </span>
            </div>

            <div class="flex justify-between gap-4">
              <span class="text-gray-500">Цель</span>
              <span class="font-semibold text-gray-800">
                {{ goalLabels[profile.parameters?.goal] || 'Не указано' }}
              </span>
            </div>
          </div>

          <button
            type="button"
            class="mt-6 w-full rounded-xl border border-green-200 py-3 font-semibold text-green-700 hover:bg-green-50"
            @click="router.push('/profile/recalculate')"
          >
            Пересчитать норму КБЖУ
          </button>
        </div>
      </section>

      <section class="grid gap-5 md:grid-cols-2">
        <div class="rounded-3xl bg-white p-6 shadow-sm">
          <h2 class="mb-4 text-xl font-bold text-gray-800">Дневная норма КБЖУ</h2>

          <div class="grid gap-3 sm:grid-cols-2">
            <div class="rounded-2xl bg-green-50 p-4">
              <p class="text-sm text-gray-500">Калории</p>
              <p class="text-2xl font-bold text-green-700">
                {{ profile.dailyNorm?.calories ?? 0 }}
              </p>
            </div>

            <div class="rounded-2xl bg-gray-50 p-4">
              <p class="text-sm text-gray-500">Белки</p>
              <p class="text-2xl font-bold text-gray-800">
                {{ profile.dailyNorm?.proteins ?? 0 }}
              </p>
            </div>

            <div class="rounded-2xl bg-gray-50 p-4">
              <p class="text-sm text-gray-500">Жиры</p>
              <p class="text-2xl font-bold text-gray-800">
                {{ profile.dailyNorm?.fats ?? 0 }}
              </p>
            </div>

            <div class="rounded-2xl bg-gray-50 p-4">
              <p class="text-sm text-gray-500">Углеводы</p>
              <p class="text-2xl font-bold text-gray-800">
                {{ profile.dailyNorm?.carbs ?? 0 }}
              </p>
            </div>
          </div>
        </div>

        <div class="rounded-3xl bg-white p-6 shadow-sm">
          <h2 class="mb-4 text-xl font-bold text-gray-800">Среднее за месяц</h2>

          <div class="grid gap-3 sm:grid-cols-2">
            <div class="rounded-2xl bg-green-50 p-4">
              <p class="text-sm text-gray-500">Калории</p>
              <p class="text-2xl font-bold text-green-700">
                {{ profile.monthlyAverage?.calories ?? 0 }}
              </p>
            </div>

            <div class="rounded-2xl bg-gray-50 p-4">
              <p class="text-sm text-gray-500">Белки</p>
              <p class="text-2xl font-bold text-gray-800">
                {{ profile.monthlyAverage?.proteins ?? 0 }}
              </p>
            </div>

            <div class="rounded-2xl bg-gray-50 p-4">
              <p class="text-sm text-gray-500">Жиры</p>
              <p class="text-2xl font-bold text-gray-800">
                {{ profile.monthlyAverage?.fats ?? 0 }}
              </p>
            </div>

            <div class="rounded-2xl bg-gray-50 p-4">
              <p class="text-sm text-gray-500">Углеводы</p>
              <p class="text-2xl font-bold text-gray-800">
                {{ profile.monthlyAverage?.carbs ?? 0 }}
              </p>
            </div>
          </div>
        </div>
      </section>
    </template>
  </div>
</template>
