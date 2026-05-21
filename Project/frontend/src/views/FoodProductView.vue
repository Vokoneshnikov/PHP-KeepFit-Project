<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { foodService } from '@/services/foodService'

const route = useRoute()
const router = useRouter()

const food = ref(null)
const grams = ref(100)
const error = ref('')
const loading = ref(false)

const mealType = computed(() => {
  return route.query.mealType || 'other'
})

const mealTypeLabel = computed(() => {
  const labels = {
    breakfast: 'Завтрак',
    lunch: 'Обед',
    dinner: 'Ужин',
    other: 'Другое',
  }

  return labels[mealType.value] || 'Другое'
})

const calculated = computed(() => {
  if (!food.value) {
    return {
      calories: 0,
      proteins: 0,
      fats: 0,
      carbs: 0,
    }
  }

  const amount = Number(grams.value) || 0

  return {
    calories: Math.round(((food.value.calories * amount) / 100) * 10) / 10,
    proteins: Math.round(((food.value.proteins * amount) / 100) * 10) / 10,
    fats: Math.round(((food.value.fats * amount) / 100) * 10) / 10,
    carbs: Math.round(((food.value.carbs * amount) / 100) * 10) / 10,
  }
})

async function loadFood() {
  error.value = ''
  loading.value = true

  try {
    food.value = await foodService.getProductById(route.params.id)
  } catch (e) {
    error.value = e.response?.data?.error || 'Не удалось загрузить продукт'
  } finally {
    loading.value = false
  }
}

async function addFood() {
  error.value = ''

  if (!grams.value || Number(grams.value) <= 0) {
    error.value = 'Введите корректную граммовку'
    return
  }

  loading.value = true

  try {
    await foodService.addFoodToDiary(route.params.id, {
      weight: Number(grams.value),
      mealType: mealType.value,
    })

    router.push('/diary')
  } catch (e) {
    error.value = e.response?.data?.error || 'Не удалось добавить продукт'
  } finally {
    loading.value = false
  }
}

onMounted(loadFood)
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

    <div class="rounded-3xl bg-white p-6 shadow-sm">
      <div v-if="loading && !food" class="text-gray-500">Загрузка продукта...</div>

      <p v-if="error" class="mb-4 rounded-2xl bg-red-50 p-4 text-red-600">
        {{ error }}
      </p>

      <template v-if="food">
        <h1 class="text-3xl font-bold text-gray-800">
          {{ food.name }}
        </h1>

        <p class="mt-2 text-gray-500">
          Приём пищи:
          <span class="font-semibold text-green-700">
            {{ mealTypeLabel }}
          </span>
        </p>

        <div class="mt-6 rounded-2xl bg-green-50 p-4">
          <p class="font-semibold text-gray-800">КБЖУ на 100 г:</p>

          <p class="mt-1 text-sm text-gray-600">
            {{ food.calories }} ккал · Б {{ food.proteins }} · Ж {{ food.fats }} · У
            {{ food.carbs }}
          </p>
        </div>

        <div class="mt-6">
          <label class="mb-1 block text-sm font-medium text-gray-700"> Граммовка </label>

          <input
            v-model="grams"
            type="number"
            min="1"
            class="w-full rounded-xl border border-gray-200 px-4 py-3 focus:border-green-500"
          />
        </div>

        <div class="mt-6 grid gap-3 md:grid-cols-4">
          <div class="rounded-2xl bg-gray-50 p-4 text-center">
            <p class="text-sm text-gray-500">Калории</p>

            <p class="text-xl font-bold text-green-700">
              {{ calculated.calories }}
            </p>
          </div>

          <div class="rounded-2xl bg-gray-50 p-4 text-center">
            <p class="text-sm text-gray-500">Белки</p>

            <p class="text-xl font-bold text-gray-800">
              {{ calculated.proteins }}
            </p>
          </div>

          <div class="rounded-2xl bg-gray-50 p-4 text-center">
            <p class="text-sm text-gray-500">Жиры</p>

            <p class="text-xl font-bold text-gray-800">
              {{ calculated.fats }}
            </p>
          </div>

          <div class="rounded-2xl bg-gray-50 p-4 text-center">
            <p class="text-sm text-gray-500">Углеводы</p>

            <p class="text-xl font-bold text-gray-800">
              {{ calculated.carbs }}
            </p>
          </div>
        </div>

        <button
          type="button"
          class="mt-6 w-full rounded-xl bg-green-600 py-3 font-semibold text-white hover:bg-green-700 disabled:opacity-60"
          :disabled="loading"
          @click="addFood"
        >
          {{ loading ? 'Добавляем...' : 'Добавить в дневник' }}
        </button>
      </template>
    </div>
  </div>
</template>
