import api from "@/lib/axios";

export const purchaseService = {
  getAll: (params) => api.get("/purchases", { params }),
  getOne: (id) => api.get(`/purchases/${id}`),
  create: (data) => api.post("/purchases", data),
  receive: (id, data) => api.post(`/purchases/${id}/receive`, data),
  payments: (id) => api.get(`/purchases/${id}/payments`),
  storePayment: (id, data) => api.post(`/purchases/${id}/payments`, data),
};
