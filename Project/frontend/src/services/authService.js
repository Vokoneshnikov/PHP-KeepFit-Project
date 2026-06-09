import api from './api'

export const authService = {
  async login(payload) {
    const response = await api.post('/api/login', payload)

    localStorage.setItem('access_token', response.data.access_token)
    localStorage.setItem('refresh_token', response.data.refresh_token)

    return response.data
  },

  async register(payload) {
    const response = await api.post('/api/register', payload)
    return response.data
  },

  async registerAndLogin(payload) {
    await this.register(payload)

    return this.login({
      email: payload.email,
      password: payload.password,
    })
  },
  logout() {
    localStorage.removeItem('access_token')
    localStorage.removeItem('refresh_token')
  },

  isAuthenticated() {
    return Boolean(localStorage.getItem('access_token'))
  },
}
