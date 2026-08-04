import api from "@/lib/axios";

export const categoryService = {
  getAll: (params) => api.get("/categories", { params }),
  create: (data) => api.post("/categories", data),
  update: (id, data) => api.put(`/categories/${id}`, data),
  remove: (id) => api.delete(`/categories/${id}`),
};
