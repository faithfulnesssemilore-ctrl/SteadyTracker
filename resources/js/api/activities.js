import api from '../services/api'

export default {
  fetchAll: () => api.get('/api/activities'),
  create: (payload) => api.post('/api/activities', payload),
  start: (id) => api.patch(`/api/activities/${id}/start`),
  complete: (id) => api.patch(`/api/activities/${id}/complete`),
}