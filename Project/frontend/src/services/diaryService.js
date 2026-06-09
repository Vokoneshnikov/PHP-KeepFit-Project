import api from './api'

export const diaryService = {
  async getDiary(date) {
    const response = await api.get('/diary', {
      params: { date },
    })

    return response.data
  },
}
