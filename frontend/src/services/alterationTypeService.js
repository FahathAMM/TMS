import api from "@/lib/axios";

export const alterationTypeService = {
  getAll: (params) => api.get("/alteration-types", { params }),
  create: (data) => api.post("/alteration-types", data),
  update: (id, data) => api.put(`/alteration-types/${id}`, data),
  remove: (id) => api.delete(`/alteration-types/${id}`),
};
