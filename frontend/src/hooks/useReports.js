"use client";

import { useQuery } from "@tanstack/react-query";
import { reportService } from "@/services/reportService";

export function useOrdersReport(params = {}) {
  return useQuery({
    queryKey: ["reports", "orders", params],
    queryFn: () => reportService.orders(params).then((r) => r.data.data),
  });
}

export function usePaymentsReport(params = {}) {
  return useQuery({
    queryKey: ["reports", "payments", params],
    queryFn: () => reportService.payments(params).then((r) => r.data.data),
  });
}

export function useOutstandingBalancesReport() {
  return useQuery({
    queryKey: ["reports", "outstanding-balances"],
    queryFn: () => reportService.outstandingBalances().then((r) => r.data.data),
  });
}

export function useStockReport() {
  return useQuery({
    queryKey: ["reports", "stock"],
    queryFn: () => reportService.stock().then((r) => r.data.data),
  });
}

export function usePurchasesReport(params = {}) {
  return useQuery({
    queryKey: ["reports", "purchases", params],
    queryFn: () => reportService.purchases(params).then((r) => r.data.data),
  });
}

export function useExpensesReport(params = {}) {
  return useQuery({
    queryKey: ["reports", "expenses", params],
    queryFn: () => reportService.expenses(params).then((r) => r.data.data),
  });
}

export function useTailorProductivityReport(params = {}) {
  return useQuery({
    queryKey: ["reports", "tailor-productivity", params],
    queryFn: () => reportService.tailorProductivity(params).then((r) => r.data.data),
  });
}
