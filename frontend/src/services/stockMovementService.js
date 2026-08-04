import api from "@/lib/axios";

export const stockMovementService = {
  getAll: (params) => api.get("/stock-movements", { params }),
};
