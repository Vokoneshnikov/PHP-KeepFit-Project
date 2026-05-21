<script setup>
import { computed, onMounted, ref } from 'vue'
import { diaryService } from '@/services/diaryService'
import CaloriesProgress from '@/components/diary/CaloriesProgress.vue'
import MealCard from '@/components/diary/MealCard.vue'

function getToday() {
  return new Date().toISOString().slice(0, 10)
}

const selectedDate = ref(getToday())
const diary = ref(null)
const loading = ref(false)
const error = ref('')

const mealsConfig = [
  {
    title: 'Завтрак',
    type: 'breakfast',
  },
  {
    title: 'Обед',
    type: 'lunch',
  },
  {
    title: 'Ужин',
    type: 'dinner',
  },
  {
    title: 'Другое',
    type: 'other',
  },
]

const consumedCalories = computed(() => {
  return diary.value?.caloriesRatio?.consumed ?? 0
})

const targetCalories = computed(() => {
  return diary.value?.caloriesRatio?.target ?? 0
})

async function loadDiary() {
  error.value = ''
  loading.value = true

  try {
    diary.value = await diaryService.getDiary(selectedDate.value)
  } catch (e) {
    error.value = e.response?.data?.error || 'Не удалось загрузить дневник'
  } finally {
    loading.value = false
  }
}

onMounted(loadDiary)
</script>

<template>
  <div class="space-y-6">
    <section class="flex flex-col justify-between gap-4 md:flex-row md:items-end">
      <div>
        <h1 class="text-3xl font-bold text-gray-800">Дневник питания</h1>

        <p class="mt-1 text-gray-500">Выберите дату и посмотрите, что было съедено за день.</p>
      </div>

      <div class="rounded-2xl bg-white p-4 shadow-sm">
        <label class="mb-1 block text-sm font-medium text-gray-700"> Дата </label>

        <div class="flex gap-2">
          <input
            v-model="selectedDate"
            type="date"
            class="rounded-xl border border-gray-200 px-4 py-2 focus:border-green-500"
          />

          <button
            type="button"
            class="rounded-xl bg-green-600 px-4 py-2 font-medium text-white hover:bg-green-700"
            @click="loadDiary"
          >
            Показать
          </button>
        </div>
      </div>
    </section>

    <p v-if="error" class="rounded-2xl bg-red-50 p-4 text-red-600">
      {{ error }}
    </p>

    <div v-if="loading" class="rounded-3xl bg-white p-6 text-gray-500 shadow-sm">
      Загружаем дневник...
    </div>

    <template v-else-if="diary">
      <CaloriesProgress :consumed="consumedCalories" :target="targetCalories" />

      <section class="grid gap-5 md:grid-cols-2">
        <MealCard
          v-for="meal in mealsConfig"
          :key="meal.type"
          :title="meal.title"
          :meal-type="meal.type"
          :items="diary.meals?.[meal.type] ?? []"
        />
      </section>
    </template>
  </div>
</template>
