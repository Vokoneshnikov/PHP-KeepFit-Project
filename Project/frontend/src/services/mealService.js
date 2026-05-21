import api from './api'

export const mealService = {
  async getMealById(id) {
    const response = await api.get(`/meals/${id}`)
    return response.data
  },

  async updateMeal(id, payload) {
    const response = await api.post(`/meals/${id}`, payload)
    return response.data
  },

  async deleteMeal(id) {
    const response = await api.delete(`/meals/${id}`)
    return response.data
  },
}
