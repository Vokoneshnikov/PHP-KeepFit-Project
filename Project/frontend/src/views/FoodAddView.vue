<script setup>
import { computed, ref } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { foodService } from '@/services/foodService'
import FoodListItem from '@/components/food/FoodListItem.vue'

const router = useRouter()
const route = useRoute()

const activeTab = ref('search')

const searchQuery = ref('')
const searchResults = ref([])

const recentFoods = ref([])
const customFoods = ref([])

const loading = ref(false)
const error = ref('')
const success = ref('')

const currentPage = ref(1)
const pageSize = 10

const recentLoaded = ref(false)
const customLoaded = ref(false)

const customForm = ref({
  id: null,
  name: '',
  calories: '',
  proteins: '',
  fats: '',
  carbs: '',
})

const mealType = computed(() => {
  return route.query.mealType || 'other'
})

const selectedDate = computed(() => {
  return route.query.date || new Date().toISOString().slice(0, 10)
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

const paginatedSearchResults = computed(() => {
  const start = (currentPage.value - 1) * pageSize
  return searchResults.value.slice(start, start + pageSize)
})

const totalPages = computed(() => {
  return Math.max(Math.ceil(searchResults.value.length / pageSize), 1)
})

function selectFood(food) {
  router.push({
    name: 'food-product-add',
    params: {
      id: food.id,
    },
    query: {
      mealType: mealType.value,
      date: selectedDate.value,
    },
  })
}

async function searchFoods() {
  error.value = ''
  success.value = ''
  currentPage.value = 1

  if (!searchQuery.value.trim()) {
    error.value = 'Введите поисковый запрос'
    return
  }

  loading.value = true

  try {
    searchResults.value = await foodService.searchFoods(searchQuery.value.trim())
  } catch (e) {
    error.value = e.response?.data?.error || 'Ошибка поиска продуктов'
  } finally {
    loading.value = false
  }
}

async function loadRecentFoods() {
  error.value = ''
  loading.value = true

  try {
    recentFoods.value = await foodService.getRecentFoods()
    recentLoaded.value = true
  } catch (e) {
    error.value = e.response?.data?.error || 'Не удалось загрузить недавно употреблённые продукты'
  } finally {
    loading.value = false
  }
}

async function loadCustomFoods() {
  error.value = ''
  loading.value = true

  try {
    customFoods.value = await foodService.getCustomFoods()
    customLoaded.value = true
  } catch (e) {
    error.value = e.response?.data?.error || 'Не удалось загрузить свои продукты'
  } finally {
    loading.value = false
  }
}

function resetCustomForm() {
  customForm.value = {
    id: null,
    name: '',
    calories: '',
    proteins: '',
    fats: '',
    carbs: '',
  }
}

function editCustomFood(food) {
  customForm.value = {
    id: food.id,
    name: food.name,
    calories: food.calories,
    proteins: food.proteins,
    fats: food.fats,
    carbs: food.carbs,
  }

  window.scrollTo({
    top: 0,
    behavior: 'smooth',
  })
}

async function saveCustomFood() {
  error.value = ''
  success.value = ''

  const payload = {
    name: customForm.value.name,
    calories: Number(customForm.value.calories),
    proteins: Number(customForm.value.proteins),
    fats: Number(customForm.value.fats),
    carbs: Number(customForm.value.carbs),
  }

  if (
    !payload.name ||
    payload.calories < 0 ||
    payload.proteins < 0 ||
    payload.fats < 0 ||
    payload.carbs < 0
  ) {
    error.value = 'Проверьте данные продукта'
    return
  }

  loading.value = true

  try {
    if (customForm.value.id) {
      await foodService.updateCustomFood(customForm.value.id, payload)
      success.value = 'Продукт обновлён'
    } else {
      await foodService.createCustomFood(payload)
      success.value = 'Продукт создан'
    }

    resetCustomForm()
    await loadCustomFoods()
  } catch (e) {
    error.value = e.response?.data?.error || 'Не удалось сохранить продукт'
  } finally {
    loading.value = false
  }
}

async function deleteCustomFood(food) {
  const confirmed = confirm(`Удалить продукт "${food.name}"?`)

  if (!confirmed) {
    return
  }

  error.value = ''
  success.value = ''
  loading.value = true

  try {
    await foodService.deleteCustomFood(food.id)
    success.value = 'Продукт удалён'
    await loadCustomFoods()
  } catch (e) {
    error.value = e.response?.data?.error || 'Не удалось удалить продукт'
  } finally {
    loading.value = false
  }
}

function setTab(tab) {
  activeTab.value = tab
  error.value = ''
  success.value = ''

  if (tab === 'recent' && !recentLoaded.value) {
    loadRecentFoods()
  }

  if (tab === 'custom' && !customLoaded.value) {
    loadCustomFoods()
  }
}
</script>

<template>
  <div class="space-y-6">
    <div>
      <h1 class="text-3xl font-bold text-gray-800">Добавление продукта</h1>

      <p class="mt-1 text-gray-500">
        Приём пищи:
        <span class="font-semibold text-green-700">
          {{ mealTypeLabel }}
        </span>
      </p>
    </div>

    <div class="flex flex-wrap gap-2 rounded-3xl bg-white p-3 shadow-sm">
      <button
        type="button"
        class="rounded-2xl px-4 py-2 font-medium"
        :class="
          activeTab === 'search' ? 'bg-green-600 text-white' : 'text-gray-600 hover:bg-green-50'
        "
        @click="setTab('search')"
      >
        Поиск
      </button>

      <button
        type="button"
        class="rounded-2xl px-4 py-2 font-medium"
        :class="
          activeTab === 'recent' ? 'bg-green-600 text-white' : 'text-gray-600 hover:bg-green-50'
        "
        @click="setTab('recent')"
      >
        Недавно употреблённые
      </button>

      <button
        type="button"
        class="rounded-2xl px-4 py-2 font-medium"
        :class="
          activeTab === 'custom' ? 'bg-green-600 text-white' : 'text-gray-600 hover:bg-green-50'
        "
        @click="setTab('custom')"
      >
        Своя еда
      </button>
    </div>

    <p v-if="error" class="rounded-2xl bg-red-50 p-4 text-red-600">
      {{ error }}
    </p>

    <p v-if="success" class="rounded-2xl bg-green-50 p-4 text-green-700">
      {{ success }}
    </p>

    <section v-if="activeTab === 'search'" class="space-y-4">
      <div class="rounded-3xl bg-white p-5 shadow-sm">
        <label class="mb-2 block text-sm font-medium text-gray-700"> Поиск продукта </label>

        <div class="flex flex-col gap-3 md:flex-row">
          <input
            v-model="searchQuery"
            type="text"
            class="flex-1 rounded-xl border border-gray-200 px-4 py-3 focus:border-green-500"
            placeholder="Например: яблоко, рис, курица"
            @keyup.enter="searchFoods"
          />

          <button
            type="button"
            class="rounded-xl bg-green-600 px-6 py-3 font-semibold text-white hover:bg-green-700"
            @click="searchFoods"
          >
            Найти
          </button>
        </div>
      </div>

      <div v-if="loading" class="rounded-3xl bg-white p-5 text-gray-500 shadow-sm">Загрузка...</div>

      <div v-else class="space-y-3">
        <div
          v-if="searchResults.length === 0"
          class="rounded-3xl bg-white p-5 text-gray-500 shadow-sm"
        >
          Введите запрос и нажмите “Найти”.
        </div>

        <FoodListItem
          v-for="food in paginatedSearchResults"
          :key="food.id"
          :food="food"
          @select="selectFood"
        />

        <div
          v-if="searchResults.length > pageSize"
          class="flex items-center justify-center gap-3 rounded-3xl bg-white p-4 shadow-sm"
        >
          <button
            type="button"
            class="rounded-xl border border-gray-200 px-4 py-2 disabled:opacity-40"
            :disabled="currentPage === 1"
            @click="currentPage--"
          >
            Назад
          </button>

          <span class="text-sm text-gray-600">
            Страница {{ currentPage }} из {{ totalPages }}
          </span>

          <button
            type="button"
            class="rounded-xl border border-gray-200 px-4 py-2 disabled:opacity-40"
            :disabled="currentPage === totalPages"
            @click="currentPage++"
          >
            Вперёд
          </button>
        </div>
      </div>
    </section>

    <section v-if="activeTab === 'recent'" class="space-y-3">
      <div v-if="loading" class="rounded-3xl bg-white p-5 text-gray-500 shadow-sm">Загрузка...</div>

      <div
        v-else-if="recentFoods.length === 0"
        class="rounded-3xl bg-white p-5 text-gray-500 shadow-sm"
      >
        Недавно употреблённых продуктов пока нет.
      </div>

      <template v-else>
        <FoodListItem
          v-for="food in recentFoods"
          :key="food.id"
          :food="food"
          @select="selectFood"
        />
      </template>
    </section>

    <section v-if="activeTab === 'custom'" class="space-y-5">
      <div class="rounded-3xl bg-white p-5 shadow-sm">
        <h2 class="mb-4 text-xl font-bold text-gray-800">
          {{ customForm.id ? 'Редактировать свой продукт' : 'Создать свой продукт' }}
        </h2>

        <form class="grid gap-4 md:grid-cols-2" @submit.prevent="saveCustomFood">
          <div class="md:col-span-2">
            <label class="mb-1 block text-sm font-medium text-gray-700"> Название </label>

            <input
              v-model="customForm.name"
              type="text"
              class="w-full rounded-xl border border-gray-200 px-4 py-3 focus:border-green-500"
              required
            />
          </div>

          <div>
            <label class="mb-1 block text-sm font-medium text-gray-700"> Калории на 100 г </label>

            <input
              v-model="customForm.calories"
              type="number"
              min="0"
              step="0.1"
              class="w-full rounded-xl border border-gray-200 px-4 py-3 focus:border-green-500"
              required
            />
          </div>

          <div>
            <label class="mb-1 block text-sm font-medium text-gray-700"> Белки </label>

            <input
              v-model="customForm.proteins"
              type="number"
              min="0"
              step="0.1"
              class="w-full rounded-xl border border-gray-200 px-4 py-3 focus:border-green-500"
              required
            />
          </div>

          <div>
            <label class="mb-1 block text-sm font-medium text-gray-700"> Жиры </label>

            <input
              v-model="customForm.fats"
              type="number"
              min="0"
              step="0.1"
              class="w-full rounded-xl border border-gray-200 px-4 py-3 focus:border-green-500"
              required
            />
          </div>

          <div>
            <label class="mb-1 block text-sm font-medium text-gray-700"> Углеводы </label>

            <input
              v-model="customForm.carbs"
              type="number"
              min="0"
              step="0.1"
              class="w-full rounded-xl border border-gray-200 px-4 py-3 focus:border-green-500"
              required
            />
          </div>

          <div class="flex gap-3 md:col-span-2">
            <button
              type="submit"
              class="rounded-xl bg-green-600 px-6 py-3 font-semibold text-white hover:bg-green-700"
            >
              {{ customForm.id ? 'Сохранить' : 'Создать' }}
            </button>

            <button
              v-if="customForm.id"
              type="button"
              class="rounded-xl border border-gray-200 px-6 py-3 font-semibold text-gray-700 hover:bg-gray-50"
              @click="resetCustomForm"
            >
              Отмена
            </button>
          </div>
        </form>
      </div>

      <div class="space-y-3">
        <div
          v-if="customFoods.length === 0"
          class="rounded-3xl bg-white p-5 text-gray-500 shadow-sm"
        >
          Своих продуктов пока нет.
        </div>

        <FoodListItem
          v-for="food in customFoods"
          :key="food.id"
          :food="food"
          show-actions
          @select="selectFood"
          @edit="editCustomFood"
          @delete="deleteCustomFood"
        />
      </div>
    </section>
  </div>
</template>
