"use client";

import { useQuery, useMutation, useQueryClient } from "@tanstack/react-query";
import { garmentPriceService } from "@/services/garmentPriceService";
import { toast } from "sonner";

export function useGarmentPrices(params = {}) {
  return useQuery({
    queryKey: ["garment-prices", params],
    queryFn: () => garmentPriceService.getAll(params).then((r) => r.data),
  });
}

export function useAllGarmentPrices() {
  return useQuery({
    queryKey: ["garment-prices", "all"],
    queryFn: () => garmentPriceService.getAll({ all: 1 }).then((r) => r.data.data),
  });
}

export function useCreateGarmentPrice() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (data) => garmentPriceService.create(data),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ["garment-prices"] });
      toast.success("Garment price added");
    },
    onError: (err) => toast.error(err.response?.data?.message ?? "Failed to add garment price"),
  });
}

export function useUpdateGarmentPrice() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: ({ id, data }) => garmentPriceService.update(id, data),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ["garment-prices"] });
      toast.success("Garment price updated");
    },
    onError: (err) => toast.error(err.response?.data?.message ?? "Failed to update garment price"),
  });
}

export function useDeleteGarmentPrice() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (id) => garmentPriceService.remove(id),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ["garment-prices"] });
      toast.success("Garment price removed");
    },
    onError: (err) => toast.error(err.response?.data?.message ?? "Failed to remove garment price"),
  });
}
