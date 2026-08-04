import api from "@/lib/axios";

export const measurementTypeService = {
  getAll: (params) => api.get("/measurement-types", { params }),
  create: (data) => api.post("/measurement-types", data),
  update: (id, data) => api.put(`/measurement-types/${id}`, data),
  remove: (id) => api.delete(`/measurement-types/${id}`),
};
