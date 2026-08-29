import api from "@/lib/axios";

export const reportService = {
  orders: (params) => api.get("/reports/orders", { params }),
  payments: (params) => api.get("/reports/payments", { params }),
  outstandingBalances: () => api.get("/reports/outstanding-balances"),
  stock: () => api.get("/reports/stock"),
  purchases: (params) => api.get("/reports/purchases", { params }),
  expenses: (params) => api.get("/reports/expenses", { params }),
  tailorProductivity: (params) => api.get("/reports/tailor-productivity", { params }),
  alterationOrders: (params) => api.get("/reports/alteration-orders", { params }),
  alterationRevenue: (params) => api.get("/reports/alteration-revenue", { params }),
};
