import api from "@/lib/axios";

export const accountingService = {
  accounts: () => api.get("/accounting/accounts"),
  journalEntries: (params) => api.get("/accounting/journal-entries", { params }),
  trialBalance: () => api.get("/accounting/reports/trial-balance"),
  profitLoss: (params) => api.get("/accounting/reports/profit-loss", { params }),
};
