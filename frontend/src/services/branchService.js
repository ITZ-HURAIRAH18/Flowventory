// src/services/branchService.js
import api from './api'  // 🔹 use our configured axios instance

export default {
  getAll(params = {}) {
    return api.get('/branches', { params })
  },
  get(id) {
    return api.get(`/branches/${id}`)
  },
  create(data) {
    return api.post('/branches', data)
  },
  update(id, data) {
    return api.put(`/branches/${id}`, data)
  },
  delete(id) {
    return api.delete(`/branches/${id}`)
  },
}