import api from '../services/api'

export default {
  fetchAll: () => api.get('/api/v1/activities'),
  create: (payload) => api.post('/api/v1/activities', payload),
  update: (id, payload) => api.patch(`/api/v1/activities/${id}`, payload),
  destroy: (id) => api.delete(`/api/v1/activities/${id}`),
  start: (id) => api.patch(`/api/v1/activities/${id}/start`),
  complete: (id) => api.patch(`/api/v1/activities/${id}/complete`),
  importCsv: (file) => {
    const formData = new FormData();
    formData.append('file', file);

    return api.post('/api/v1/csv-import', formData);
  },
}