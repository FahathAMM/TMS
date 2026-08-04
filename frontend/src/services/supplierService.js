import api from "@/lib/axios";

export const supplierService = {
  getAll: (params) => api.get("/suppliers", { params }),
  getOne: (id) => api.get(`/suppliers/${id}`),
  create: (data) => api.post("/suppliers", data),
  update: (id, data) => api.put(`/suppliers/${id}`, data),
  remove: (id) => api.delete(`/suppliers/${id}`),
  ledger: (id, params) => api.get(`/suppliers/${id}/ledger`, { params }),
};
