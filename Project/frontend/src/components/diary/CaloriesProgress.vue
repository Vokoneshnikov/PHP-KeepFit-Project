<script setup>
import { computed } from 'vue'

const props = defineProps({
  consumed: {
    type: Number,
    default: 0,
  },
  target: {
    type: Number,
    default: 0,
  },
})

const remaining = computed(() => {
  return Math.max(props.target - props.consumed, 0)
})

const percent = computed(() => {
  if (!props.target || props.target <= 0) {
    return 0
  }

  return Math.min(Math.round((props.consumed / props.target) * 100), 100)
})
</script>

<template>
  <section class="rounded-3xl bg-white p-6 shadow-sm">
    <div class="mb-4 flex flex-col justify-between gap-3 md:flex-row md:items-center">
      <div>
        <h2 class="text-xl font-bold text-gray-800">Прогресс калорий</h2>

        <p class="text-sm text-gray-500">Сколько уже съедено за выбранный день</p>
      </div>

      <div class="text-right">
        <p class="text-2xl font-bold text-green-700">{{ consumed }} / {{ target }} ккал</p>

        <p class="text-sm text-gray-500">Осталось: {{ remaining }} ккал</p>
      </div>
    </div>

    <div class="h-4 overflow-hidden rounded-full bg-green-100">
      <div
        class="h-full rounded-full bg-green-600 transition-all"
        :style="{ width: `${percent}%` }"
      ></div>
    </div>

    <p class="mt-2 text-sm text-gray-500">Выполнено: {{ percent }}%</p>
  </section>
</template>
