import api from './api'

export default {
  getAll() {
    return api.get('/inventory')
  },
  getProductsByBranch(branchId) {
    return api.get(`/inventory/branch/${branchId}/products`)
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
  history() {
    return api.get('/inventory/history')
  }
}