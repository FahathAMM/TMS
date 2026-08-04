import api from "@/lib/axios";

export const orderService = {
  getAll: (params) => api.get("/orders", { params }),
  getOne: (id) => api.get(`/orders/${id}`),
  create: (data) => api.post("/orders", data),
  complete: (id) => api.post(`/orders/${id}/complete`),

  payments: (id) => api.get(`/orders/${id}/payments`),
  storePayment: (id, data) => api.post(`/orders/${id}/payments`, data),

  advanceItemStatus: (orderId, itemId, data) => api.post(`/orders/${orderId}/items/${itemId}/advance-status`, data),
  qcItem: (orderId, itemId, data) => api.post(`/orders/${orderId}/items/${itemId}/qc`, data),
  assignTailor: (orderId, itemId, data) => api.post(`/orders/${orderId}/items/${itemId}/assign-tailor`, data),

  notify: (id, data) => api.post(`/orders/${id}/notify`, data),
  notifications: (id) => api.get(`/orders/${id}/notifications`),
};
