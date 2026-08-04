"use client";

import { useQuery, useMutation, useQueryClient } from "@tanstack/react-query";
import { expenseService } from "@/services/expenseService";
import { toast } from "sonner";

export function useExpenses(params = {}) {
  return useQuery({
    queryKey: ["expenses", params],
    queryFn: () => expenseService.getAll(params).then((r) => r.data),
  });
}

export function useCreateExpense() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (data) => expenseService.create(data),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ["expenses"] });
      qc.invalidateQueries({ queryKey: ["accounting"] });
      toast.success("Expense recorded");
    },
    onError: (err) => toast.error(err.response?.data?.message ?? "Failed to record expense"),
  });
}
