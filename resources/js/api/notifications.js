import api from '../services/api';

export default {
  fetchAll: () => api.get('/api/v1/notifications'),
};