<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'

const props = defineProps({
  title: {
    type: String,
    required: true,
  },
  mealType: {
    type: String,
    required: true,
  },
  selectedDate: {
    type: String,
    default: () => new Date().toISOString().slice(0, 10),
  },
  items: {
    type: Array,
    default: () => [],
  },
})

const router = useRouter()
const isOpen = ref(true)

function goToAddFood() {
  router.push({
    name: 'food-add',
    query: {
      mealType: props.mealType,
      date: props.selectedDate,
    },
  })
}

function goToMeal(mealId) {
  router.push(`/meals/${mealId}`)
}
</script>

<template>
  <section class="rounded-3xl bg-white p-5 shadow-sm">
    <div class="mb-4 flex items-center justify-between gap-3">
      <div>
        <h3 class="text-lg font-bold text-gray-800">
          {{ title }}
        </h3>

        <p class="text-sm text-gray-500">{{ items.length }} записей</p>
      </div>

      <div class="flex gap-2">
        <button
          type="button"
          class="rounded-xl border border-green-200 px-3 py-2 text-sm font-medium text-green-700 hover:bg-green-50"
          @click="isOpen = !isOpen"
        >
          {{ isOpen ? 'Скрыть' : 'Открыть' }}
        </button>

        <button
          type="button"
          class="rounded-xl bg-green-600 px-3 py-2 text-sm font-medium text-white hover:bg-green-700"
          @click="goToAddFood"
        >
          Добавить
        </button>
      </div>
    </div>

    <div v-if="isOpen">
      <div v-if="items.length === 0" class="rounded-2xl bg-gray-50 p-4 text-sm text-gray-500">
        Пока нет добавленных продуктов.
      </div>

      <div v-else class="space-y-3">
        <button
          v-for="item in items"
          :key="item.mealId"
          type="button"
          class="w-full rounded-2xl border border-gray-100 p-4 text-left hover:border-green-300 hover:bg-green-50"
          @click="goToMeal(item.mealId)"
        >
          <div class="flex items-start justify-between gap-3">
            <div>
              <p class="font-semibold text-gray-800">
                {{ item.name }}
              </p>

              <p class="text-sm text-gray-500">{{ item.amountGrams }} г</p>
            </div>

            <div class="text-right text-sm">
              <p class="font-bold text-green-700">{{ item.cpfc.calories }} ккал</p>

              <p class="text-gray-500">
                Б {{ item.cpfc.proteins }} / Ж {{ item.cpfc.fats }} / У {{ item.cpfc.carbs }}
              </p>
            </div>
          </div>
        </button>
      </div>
    </div>
  </section>
</template>
