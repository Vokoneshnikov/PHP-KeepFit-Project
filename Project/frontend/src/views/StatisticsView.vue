<script setup>
import { computed, onMounted, ref } from 'vue'
import { Bar, Doughnut } from 'vue-chartjs'
import {
  Chart as ChartJS,
  Title,
  Tooltip,
  Legend,
  BarElement,
  ArcElement,
  CategoryScale,
  LinearScale,
} from 'chart.js'
import { statisticsService } from '@/services/statisticsService'

ChartJS.register(Title, Tooltip, Legend, BarElement, ArcElement, CategoryScale, LinearScale)

const period = ref('week')
const stats = ref(null)
const loading = ref(false)
const error = ref('')

async function loadStats() {
  error.value = ''
  loading.value = true

  try {
    if (period.value === 'week') {
      stats.value = await statisticsService.getWeeklyStats()
    } else {
      stats.value = await statisticsService.getMonthlyStats()
    }
  } catch (e) {
    error.value = e.response?.data?.error || 'Не удалось загрузить статистику'
  } finally {
    loading.value = false
  }
}

function changePeriod(newPeriod) {
  period.value = newPeriod
  loadStats()
}

function formatDate(dateString) {
  if (!dateString) {
    return ''
  }

  const date = new Date(dateString)

  return date.toLocaleDateString('ru-RU', {
    day: '2-digit',
    month: '2-digit',
  })
}

const history = computed(() => {
  return stats.value?.history ?? []
})

const summary = computed(() => {
  return (
    stats.value?.summary ?? {
      avg: {
        calories: 0,
        proteins: 0,
        fats: 0,
        carbs: 0,
      },
      target: {
        calories: 0,
        proteins: 0,
        fats: 0,
        carbs: 0,
      },
    }
  )
})

const caloriesChartData = computed(() => {
  return {
    labels: history.value.map((item) => formatDate(item.date)),
    datasets: [
      {
        label: 'Калории',
        data: history.value.map((item) => Number(item.consumed_calories ?? 0)),
        backgroundColor: '#16a34a',
        borderRadius: 8,
      },
    ],
  }
})

const caloriesChartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: {
      display: true,
    },
    title: {
      display: false,
    },
  },
  scales: {
    y: {
      beginAtZero: true,
    },
  },
}

const macroChartData = computed(() => {
  return {
    labels: ['Белки', 'Жиры', 'Углеводы'],
    datasets: [
      {
        label: 'Среднее КБЖУ',
        data: [
          Number(summary.value.avg?.proteins ?? 0),
          Number(summary.value.avg?.fats ?? 0),
          Number(summary.value.avg?.carbs ?? 0),
        ],
        backgroundColor: ['#16a34a', '#65a30d', '#84cc16'],
      },
    ],
  }
})

const macroChartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: {
      position: 'bottom',
    },
  },
}

onMounted(loadStats)
</script>

<template>
  <div class="space-y-6">
    <section class="flex flex-col justify-between gap-4 md:flex-row md:items-end">
      <div>
        <h1 class="text-3xl font-bold text-gray-800">Статистика</h1>

        <p class="mt-1 text-gray-500">Анализ калорий и средних значений КБЖУ.</p>
      </div>

      <div class="flex rounded-2xl bg-white p-2 shadow-sm">
        <button
          type="button"
          class="rounded-xl px-4 py-2 font-medium"
          :class="period === 'week' ? 'bg-green-600 text-white' : 'text-gray-600 hover:bg-green-50'"
          @click="changePeriod('week')"
        >
          Неделя
        </button>

        <button
          type="button"
          class="rounded-xl px-4 py-2 font-medium"
          :class="
            period === 'month' ? 'bg-green-600 text-white' : 'text-gray-600 hover:bg-green-50'
          "
          @click="changePeriod('month')"
        >
          Месяц
        </button>
      </div>
    </section>

    <p v-if="error" class="rounded-2xl bg-red-50 p-4 text-red-600">
      {{ error }}
    </p>

    <div v-if="loading" class="rounded-3xl bg-white p-6 text-gray-500 shadow-sm">
      Загружаем статистику...
    </div>

    <template v-else-if="stats">
      <section class="grid gap-5 md:grid-cols-4">
        <div class="rounded-3xl bg-white p-5 shadow-sm">
          <p class="text-sm text-gray-500">Средние калории</p>

          <p class="mt-2 text-3xl font-bold text-green-700">
            {{ summary.avg.calories ?? 0 }}
          </p>

          <p class="mt-1 text-sm text-gray-500">Норма: {{ summary.target.calories ?? 0 }}</p>
        </div>

        <div class="rounded-3xl bg-white p-5 shadow-sm">
          <p class="text-sm text-gray-500">Белки</p>

          <p class="mt-2 text-3xl font-bold text-gray-800">
            {{ summary.avg.proteins ?? 0 }}
          </p>

          <p class="mt-1 text-sm text-gray-500">Норма: {{ summary.target.proteins ?? 0 }}</p>
        </div>

        <div class="rounded-3xl bg-white p-5 shadow-sm">
          <p class="text-sm text-gray-500">Жиры</p>

          <p class="mt-2 text-3xl font-bold text-gray-800">
            {{ summary.avg.fats ?? 0 }}
          </p>

          <p class="mt-1 text-sm text-gray-500">Норма: {{ summary.target.fats ?? 0 }}</p>
        </div>

        <div class="rounded-3xl bg-white p-5 shadow-sm">
          <p class="text-sm text-gray-500">Углеводы</p>

          <p class="mt-2 text-3xl font-bold text-gray-800">
            {{ summary.avg.carbs ?? 0 }}
          </p>

          <p class="mt-1 text-sm text-gray-500">Норма: {{ summary.target.carbs ?? 0 }}</p>
        </div>
      </section>

      <section class="grid gap-5 lg:grid-cols-3">
        <div class="rounded-3xl bg-white p-6 shadow-sm lg:col-span-2">
          <h2 class="mb-4 text-xl font-bold text-gray-800">Калории по дням</h2>

          <div class="h-80">
            <Bar :data="caloriesChartData" :options="caloriesChartOptions" />
          </div>
        </div>

        <div class="rounded-3xl bg-white p-6 shadow-sm">
          <h2 class="mb-4 text-xl font-bold text-gray-800">Средние БЖУ</h2>

          <div class="h-80">
            <Doughnut :data="macroChartData" :options="macroChartOptions" />
          </div>
        </div>
      </section>

      <section class="rounded-3xl bg-white p-6 shadow-sm">
        <h2 class="mb-4 text-xl font-bold text-gray-800">История по дням</h2>

        <div class="overflow-x-auto">
          <table class="w-full text-left text-sm">
            <thead>
              <tr class="border-b border-gray-100 text-gray-500">
                <th class="py-3 pr-4">Дата</th>
                <th class="py-3 pr-4">Калории</th>
                <th class="py-3 pr-4">Белки</th>
                <th class="py-3 pr-4">Жиры</th>
                <th class="py-3 pr-4">Углеводы</th>
              </tr>
            </thead>

            <tbody>
              <tr v-for="item in history" :key="item.date" class="border-b border-gray-50">
                <td class="py-3 pr-4 font-medium text-gray-800">
                  {{ formatDate(item.date) }}
                </td>

                <td class="py-3 pr-4 text-green-700">
                  {{ item.consumed_calories ?? 0 }}
                </td>

                <td class="py-3 pr-4">
                  {{ Number(item.consumed_proteins ?? 0).toFixed(1) }}
                </td>

                <td class="py-3 pr-4">
                  {{ Number(item.consumed_fats ?? 0).toFixed(1) }}
                </td>

                <td class="py-3 pr-4">
                  {{ Number(item.consumed_carbs ?? 0).toFixed(1) }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </section>
    </template>
  </div>
</template>
