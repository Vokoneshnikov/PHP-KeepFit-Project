import api from './api'

export const foodService = {
  async getProductById(id) {
    const response = await api.get(`/food/${id}`)
    return response.data
  },

  async searchFoods(query) {
    const response = await api.get('/food/search', {
      params: {
        q: query,
      },
    })

    return response.data
  },

  async getRecentFoods() {
    const response = await api.get('/food/recent')
    return response.data
  },

  async createCustomFood(payload) {
    const response = await api.post('/food/add', payload)
    return response.data
  },

  async getCustomFoods() {
    const response = await api.get('/food/custom')
    return response.data
  },

  async updateCustomFood(id, payload) {
    const response = await api.post(`/food/custom/${id}`, payload)
    return response.data
  },

  async deleteCustomFood(id) {
    const response = await api.delete(`/food/custom/${id}`)
    return response.data
  },

  async addFoodToDiary(foodId, payload) {
    const response = await api.post(`/food/${foodId}`, payload)
    return response.data
  },
}
