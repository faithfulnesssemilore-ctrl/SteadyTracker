import api from '../services/api';

export default {
    fetchAll: () => api.get('/api/v1/notifications'),
    markRead: (id) => api.patch(`/api/v1/notifications/${id}/read`),
    markAllRead: () => api.patch('/api/v1/notifications/read-all'),
    remove: (id) => api.delete(`/api/v1/notifications/${id}`),
};
