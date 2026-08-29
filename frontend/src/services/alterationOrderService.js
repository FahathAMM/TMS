import api from "@/lib/axios";

export const alterationOrderService = {
  getAll: (params) => api.get("/alteration-orders", { params }),
  getOne: (id) => api.get(`/alteration-orders/${id}`),

  storeGarment: (orderId, data) => api.post(`/alteration-orders/${orderId}/garments`, data),
  markGarmentDelivered: (orderId, garmentId) => api.post(`/alteration-orders/${orderId}/garments/${garmentId}/deliver`),
  uploadPhoto: (orderId, garmentId, formData) =>
    api.post(`/alteration-orders/${orderId}/garments/${garmentId}/photos`, formData, {
      headers: { "Content-Type": "multipart/form-data" },
    }),

  storeTask: (orderId, garmentId, data) => api.post(`/alteration-orders/${orderId}/garments/${garmentId}/tasks`, data),
  advanceTaskStatus: (orderId, garmentId, taskId, data) =>
    api.post(`/alteration-orders/${orderId}/garments/${garmentId}/tasks/${taskId}/advance-status`, data),
  assignTailor: (orderId, garmentId, taskId, data) =>
    api.post(`/alteration-orders/${orderId}/garments/${garmentId}/tasks/${taskId}/assign-tailor`, data),

  payments: (orderId) => api.get(`/alteration-orders/${orderId}/payments`),
  storePayment: (orderId, data) => api.post(`/alteration-orders/${orderId}/payments`, data),

  complete: (orderId) => api.post(`/alteration-orders/${orderId}/complete`),
  cancel: (orderId, data) => api.post(`/alteration-orders/${orderId}/cancel`, data),

  notify: (orderId, data) => api.post(`/alteration-orders/${orderId}/notify`, data),
  notifications: (orderId) => api.get(`/alteration-orders/${orderId}/notifications`),
};
