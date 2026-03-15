import api from './api'   // axios instance

export default {
  getAll(params = {}) {
    // Default to paginated response with 50 items per page for faster loading
    const defaultParams = { per_page: 50, ...params }
    return api.get('/products', { params: defaultParams })
  },

  get(id) {
    return api.get(`/products/${id}`)
  },

  create(data) {
    return api.post('/products', data)
  },

  update(id, data) {
    return api.put(`/products/${id}`, data)
  },

  delete(id) {
    return api.delete(`/products/${id}`)
  },

  // Get paginated list for dropdowns/selects (more efficient)
  getPaginated(page = 1, perPage = 50, search = null, status = 'active') {
    return api.get('/products', { 
      params: { 
        page, 
        per_page: perPage, 
        search,
        status 
      } 
    })
  }
}