import api from "@/lib/axios";

export const expenseService = {
  getAll: (params) => api.get("/expenses", { params }),
  create: (data) => api.post("/expenses", data),
};
