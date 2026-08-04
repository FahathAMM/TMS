import api from "@/lib/axios";

export const tailorService = {
  getAll: (params) => api.get("/tailors", { params }),
  getOne: (id) => api.get(`/tailors/${id}`),
  create: (data) => api.post("/tailors", data),
  update: (id, data) => api.put(`/tailors/${id}`, data),
  remove: (id) => api.delete(`/tailors/${id}`),
};
