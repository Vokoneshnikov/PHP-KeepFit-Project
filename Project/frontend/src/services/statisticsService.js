import api from './api'

export const statisticsService = {
  async getWeeklyStats() {
    const response = await api.get('/stats/weekly')
    return response.data
  },

  async getMonthlyStats() {
    const response = await api.get('/stats/monthly')
    return response.data
  },
}
