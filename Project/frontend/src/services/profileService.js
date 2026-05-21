import api from './api'

export const profileService = {
  async getProfile() {
    const response = await api.get('/profile')
    return response.data
  },

  async updateProfile(payload) {
    const response = await api.post('/profile/edit', payload)
    return response.data
  },

  async getRecalculateData() {
    const response = await api.get('/profile/recalculate')
    return response.data
  },

  async recalculateNorm(payload) {
    const response = await api.post('/profile/recalculate', payload)
    return response.data
  },
}
