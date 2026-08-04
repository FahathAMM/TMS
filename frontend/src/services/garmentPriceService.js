import api from "@/lib/axios";

export const garmentPriceService = {
  getAll: (params) => api.get("/garment-prices", { params }),
  create: (data) => api.post("/garment-prices", data),
  update: (id, data) => api.put(`/garment-prices/${id}`, data),
  remove: (id) => api.delete(`/garment-prices/${id}`),
};
