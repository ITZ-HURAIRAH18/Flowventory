import api from './api'

export default {
  getAll(page = 1, perPage = 15) {
    return api.get('/inventory', { params: { page, per_page: perPage } })
  },
  getProductsByBranch(branchId, page = 1, perPage = 15) {
    return api.get(`/inventory/branch/${branchId}/products`, { params: { page, per_page: perPage } })
  },
  getStats() {
    return api.get('/inventory/stats')
  },
  add(data) {
    return api.post('/inventory/add', data)
  },
  adjust(data) {
    return api.post('/inventory/adjust', data)
  },
  transfer(data) {
    return api.post('/inventory/transfer', data)
  },
  history(page = 1, perPage = 20) {
    return api.get('/inventory/history', { params: { page, per_page: perPage } })
  }
}