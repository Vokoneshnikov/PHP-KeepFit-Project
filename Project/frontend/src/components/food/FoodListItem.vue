<script setup>
defineProps({
  food: {
    type: Object,
    required: true,
  },
  showActions: {
    type: Boolean,
    default: false,
  },
})

const emit = defineEmits(['select', 'edit', 'delete'])
</script>

<template>
  <div class="rounded-2xl border border-gray-100 bg-white p-4 shadow-sm">
    <div class="flex flex-col justify-between gap-3 md:flex-row md:items-center">
      <button type="button" class="text-left" @click="emit('select', food)">
        <p class="font-semibold text-gray-800">
          {{ food.name }}
        </p>

        <p class="mt-1 text-sm text-gray-500">
          {{ food.calories }} ккал на 100 г · Б {{ food.proteins }} · Ж {{ food.fats }} · У
          {{ food.carbs }}
        </p>
      </button>

      <div class="flex gap-2">
        <button
          type="button"
          class="rounded-xl bg-green-600 px-4 py-2 text-sm font-medium text-white hover:bg-green-700"
          @click="emit('select', food)"
        >
          Выбрать
        </button>

        <template v-if="showActions">
          <button
            type="button"
            class="rounded-xl border border-gray-200 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
            @click="emit('edit', food)"
          >
            Изменить
          </button>

          <button
            type="button"
            class="rounded-xl border border-red-200 px-4 py-2 text-sm font-medium text-red-600 hover:bg-red-50"
            @click="emit('delete', food)"
          >
            Удалить
          </button>
        </template>
      </div>
    </div>
  </div>
</template>
