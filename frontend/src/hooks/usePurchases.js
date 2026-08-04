"use client";

import { useQuery, useMutation, useQueryClient } from "@tanstack/react-query";
import { purchaseService } from "@/services/purchaseService";
import { toast } from "sonner";

export function usePurchases(params = {}) {
  return useQuery({
    queryKey: ["purchases", params],
    queryFn: () => purchaseService.getAll(params).then((r) => r.data),
  });
}

export function usePurchase(id) {
  return useQuery({
    queryKey: ["purchases", id],
    queryFn: () => purchaseService.getOne(id).then((r) => r.data.data),
    enabled: !!id,
  });
}

export function useCreatePurchase() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (data) => purchaseService.create(data),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ["purchases"] });
      toast.success("Purchase order created");
    },
    onError: (err) => toast.error(err.response?.data?.message ?? "Failed to create purchase order"),
  });
}

export function useReceivePurchase() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: ({ id, data }) => purchaseService.receive(id, data),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ["purchases"] });
      qc.invalidateQueries({ queryKey: ["products"] });
      toast.success("Items received — stock and accounting updated");
    },
    onError: (err) => toast.error(err.response?.data?.message ?? "Failed to receive items"),
  });
}

export function useStorePurchasePayment() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: ({ id, data }) => purchaseService.storePayment(id, data),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ["purchases"] });
      toast.success("Payment recorded");
    },
    onError: (err) => toast.error(err.response?.data?.message ?? "Failed to record payment"),
  });
}
