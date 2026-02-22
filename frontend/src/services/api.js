import axios from 'axios'

const api = axios.create({
  baseURL: 'http://localhost:8000/api',
  headers: {
    Accept: 'application/json',
  },
})

api.interceptors.request.use(config => {
  const token = localStorage.getItem('token')
  if (token && token !== 'null' && token !== 'undefined') {
    config.headers.Authorization = `Bearer ${token}`
  }
  return config
})

// ✅ Response interceptor for auth errors
api.interceptors.response.use(
  response => response,
  error => {
    // 401 means session is dead or unauthorized
    if (error.response?.status === 401) {
      const isLoginRequest = error.config.url.endsWith('/login')
      
      // Don't redirect on failed login attempt (handled by Login.vue)
      if (!isLoginRequest) {
        localStorage.removeItem('token')
        localStorage.removeItem('user')
        
        if (window.location.pathname !== '/login') {
          window.location.href = '/login'
        }
      }
    }
    
    // 429 means rate limit exceeded
    if (error.response?.status === 429) {
      const retryAfter = error.response.headers['retry-after'] || '60'
      console.warn(`Rate limit exceeded. Retry after ${retryAfter} seconds.`)
      
      // You could show a toast notification here
      if (window.dispatchEvent) {
        window.dispatchEvent(new CustomEvent('toast', {
          detail: {
            type: 'warning',
            title: 'Rate Limit Exceeded',
            message: `Please wait ${retryAfter} seconds before making more requests.`,
            duration: parseInt(retryAfter) * 1000
          }
        }))
      }
    }
    
    return Promise.reject(error)
  }
)

export default api