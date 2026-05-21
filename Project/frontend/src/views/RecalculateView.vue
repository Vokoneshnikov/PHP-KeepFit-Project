<script setup>
import { onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { profileService } from '@/services/profileService'

const router = useRouter()

const step = ref(1)

const form = ref({
  weight: '',
  height: '',
  activityLevel: 1.2,
  goal: 'maintain',
})

const result = ref(null)
const loading = ref(false)
const saving = ref(false)
const error = ref('')
const success = ref('')

const activityOptions = [
  {
    value: 1.2,
    label: 'Минимальная активность',
    description: 'Малоподвижный образ жизни',
  },
  {
    value: 1.375,
    label: 'Лёгкая активность',
    description: 'Лёгкие тренировки 1–3 раза в неделю',
  },
  {
    value: 1.55,
    label: 'Средняя активность',
    description: 'Тренировки 3–5 раз в неделю',
  },
  {
    value: 1.725,
    label: 'Высокая активность',
    description: 'Интенсивные тренировки 6–7 раз в неделю',
  },
  {
    value: 1.9,
    label: 'Очень высокая активность',
    description: 'Очень высокая физическая нагрузка',
  },
]

const goalOptions = [
  {
    value: 'lose',
    label: 'Похудение',
  },
  {
    value: 'maintain',
    label: 'Удержание веса',
  },
  {
    value: 'gain',
    label: 'Набор массы',
  },
]

async function loadRecalculateData() {
  error.value = ''
  loading.value = true

  try {
    const data = await profileService.getRecalculateData()

    form.value.weight = data.weight ?? ''
    form.value.height = data.height ?? ''
    form.value.goal = data.goal ?? 'maintain'

    const activityMap = {
      sedentary: 1.2,
      light: 1.375,
      moderate: 1.55,
      active: 1.725,
      very_active: 1.9,
    }

    form.value.activityLevel = activityMap[data.activityLevel] ?? 1.2
  } catch (e) {
    error.value = e.response?.data?.error || 'Не удалось загрузить параметры'
  } finally {
    loading.value = false
  }
}

function goToSecondStep() {
  error.value = ''

  if (!form.value.weight || Number(form.value.weight) <= 0) {
    error.value = 'Введите корректный вес'
    return
  }

  if (!form.value.height || Number(form.value.height) <= 0) {
    error.value = 'Введите корректный рост'
    return
  }

  step.value = 2
}

async function calculateNorm() {
  error.value = ''
  success.value = ''
  result.value = null
  saving.value = true

  try {
    const response = await profileService.recalculateNorm({
      weight: Number(form.value.weight),
      height: Number(form.value.height),
      activityLevel: Number(form.value.activityLevel),
      goal: form.value.goal,
    })

    result.value = response.norm
    success.value = response.message || 'Норма КБЖУ успешно пересчитана'
  } catch (e) {
    error.value = e.response?.data?.error || 'Не удалось пересчитать норму'
  } finally {
    saving.value = false
  }
}

onMounted(loadRecalculateData)
</script>

<template>
  <div class="mx-auto max-w-3xl space-y-6">
    <button
      type="button"
      class="text-sm font-medium text-green-700 hover:underline"
      @click="router.back()"
    >
      ← Назад
    </button>

    <div>
      <h1 class="text-3xl font-bold text-gray-800">Перерасчёт нормы КБЖУ</h1>

      <p class="mt-1 text-gray-500">Укажите текущие параметры, активность и цель.</p>
    </div>

    <div class="rounded-3xl bg-white p-6 shadow-sm">
      <div class="mb-6 flex gap-3">
        <div
          class="flex-1 rounded-2xl p-4 text-center"
          :class="step === 1 ? 'bg-green-600 text-white' : 'bg-gray-50 text-gray-500'"
        >
          Шаг 1: параметры
        </div>

        <div
          class="flex-1 rounded-2xl p-4 text-center"
          :class="step === 2 ? 'bg-green-600 text-white' : 'bg-gray-50 text-gray-500'"
        >
          Шаг 2: цель
        </div>
      </div>

      <div v-if="loading" class="text-gray-500">Загружаем параметры...</div>

      <p v-if="error" class="mb-4 rounded-2xl bg-red-50 p-4 text-red-600">
        {{ error }}
      </p>

      <p v-if="success" class="mb-4 rounded-2xl bg-green-50 p-4 text-green-700">
        {{ success }}
      </p>

      <template v-if="!loading">
        <form v-if="step === 1" class="space-y-5" @submit.prevent="goToSecondStep">
          <div>
            <label class="mb-1 block text-sm font-medium text-gray-700"> Текущий вес, кг </label>

            <input
              v-model="form.weight"
              type="number"
              min="1"
              step="0.1"
              class="w-full rounded-xl border border-gray-200 px-4 py-3 focus:border-green-500"
              required
            />
          </div>

          <div>
            <label class="mb-1 block text-sm font-medium text-gray-700"> Рост, см </label>

            <input
              v-model="form.height"
              type="number"
              min="1"
              step="1"
              class="w-full rounded-xl border border-gray-200 px-4 py-3 focus:border-green-500"
              required
            />
          </div>

          <button
            type="submit"
            class="w-full rounded-xl bg-green-600 py-3 font-semibold text-white hover:bg-green-700"
          >
            Далее
          </button>
        </form>

        <form v-if="step === 2" class="space-y-5" @submit.prevent="calculateNorm">
          <div>
            <label class="mb-1 block text-sm font-medium text-gray-700"> Уровень активности </label>

            <select
              v-model="form.activityLevel"
              class="w-full rounded-xl border border-gray-200 px-4 py-3 focus:border-green-500"
            >
              <option v-for="option in activityOptions" :key="option.value" :value="option.value">
                {{ option.label }} — {{ option.description }}
              </option>
            </select>
          </div>

          <div>
            <label class="mb-2 block text-sm font-medium text-gray-700"> Цель </label>

            <div class="grid gap-3 md:grid-cols-3">
              <label
                v-for="goal in goalOptions"
                :key="goal.value"
                class="cursor-pointer rounded-2xl border p-4 text-center"
                :class="
                  form.goal === goal.value
                    ? 'border-green-500 bg-green-50 text-green-700'
                    : 'border-gray-200 text-gray-600 hover:bg-gray-50'
                "
              >
                <input v-model="form.goal" type="radio" :value="goal.value" class="hidden" />

                <span class="font-semibold">
                  {{ goal.label }}
                </span>
              </label>
            </div>
          </div>

          <div class="flex flex-col gap-3 md:flex-row">
            <button
              type="button"
              class="flex-1 rounded-xl border border-gray-200 py-3 font-semibold text-gray-700 hover:bg-gray-50"
              @click="step = 1"
            >
              Назад
            </button>

            <button
              type="submit"
              class="flex-1 rounded-xl bg-green-600 py-3 font-semibold text-white hover:bg-green-700 disabled:opacity-60"
              :disabled="saving"
            >
              {{ saving ? 'Рассчитываем...' : 'Рассчитать' }}
            </button>
          </div>
        </form>
      </template>
    </div>

    <div v-if="result" class="rounded-3xl bg-white p-6 shadow-sm">
      <h2 class="mb-4 text-xl font-bold text-gray-800">Новая дневная норма</h2>

      <div class="grid gap-3 sm:grid-cols-2 md:grid-cols-4">
        <div class="rounded-2xl bg-green-50 p-4 text-center">
          <p class="text-sm text-gray-500">Калории</p>

          <p class="text-2xl font-bold text-green-700">
            {{ result.dailyCalories }}
          </p>
        </div>

        <div class="rounded-2xl bg-gray-50 p-4 text-center">
          <p class="text-sm text-gray-500">Белки</p>

          <p class="text-2xl font-bold text-gray-800">
            {{ result.proteins }}
          </p>
        </div>

        <div class="rounded-2xl bg-gray-50 p-4 text-center">
          <p class="text-sm text-gray-500">Жиры</p>

          <p class="text-2xl font-bold text-gray-800">
            {{ result.fats }}
          </p>
        </div>

        <div class="rounded-2xl bg-gray-50 p-4 text-center">
          <p class="text-sm text-gray-500">Углеводы</p>

          <p class="text-2xl font-bold text-gray-800">
            {{ result.carbs }}
          </p>
        </div>
      </div>

      <button
        type="button"
        class="mt-6 w-full rounded-xl bg-green-600 py-3 font-semibold text-white hover:bg-green-700"
        @click="router.push('/profile')"
      >
        Вернуться в профиль
      </button>
    </div>
  </div>
</template>
