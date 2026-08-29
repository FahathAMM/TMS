import api from "@/lib/axios";

export const measurementTypeService = {
  getAll: (params) => api.get("/measurement-types", { params }),
  getOne: (id) => api.get(`/measurement-types/${id}`),
  create: (data) => api.post("/measurement-types", data),
  update: (id, data) => api.put(`/measurement-types/${id}`, data),
  uploadImage: (id, formData) =>
    api.post(`/measurement-types/${id}/image`, formData, {
      headers: { "Content-Type": "multipart/form-data" },
    }),
  remove: (id) => api.delete(`/measurement-types/${id}`),
};
