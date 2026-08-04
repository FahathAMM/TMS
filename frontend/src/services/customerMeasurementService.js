import api from "@/lib/axios";

export const customerMeasurementService = {
  getAll: (customerId) => api.get(`/customers/${customerId}/measurements`),
  update: (customerId, data) => api.put(`/customers/${customerId}/measurements`, data),
};
