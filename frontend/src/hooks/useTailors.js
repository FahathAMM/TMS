"use client";

import { useQuery, useMutation, useQueryClient } from "@tanstack/react-query";
import { tailorService } from "@/services/tailorService";
import { toast } from "sonner";

export function useTailors(params = {}) {
  return useQuery({
    queryKey: ["tailors", params],
    queryFn: () => tailorService.getAll(params).then((r) => r.data),
  });
}

export function useAllTailors() {
  return useQuery({
    queryKey: ["tailors", "all"],
    queryFn: () => tailorService.getAll({ all: 1 }).then((r) => r.data.data),
  });
}

export function useCreateTailor() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (data) => tailorService.create(data),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ["tailors"] });
      toast.success("Tailor added");
    },
    onError: (err) => toast.error(err.response?.data?.message ?? "Failed to add tailor"),
  });
}

export function useUpdateTailor() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: ({ id, data }) => tailorService.update(id, data),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ["tailors"] });
      toast.success("Tailor updated");
    },
    onError: (err) => toast.error(err.response?.data?.message ?? "Failed to update tailor"),
  });
}

export function useDeleteTailor() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (id) => tailorService.remove(id),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ["tailors"] });
      toast.success("Tailor removed");
    },
    onError: (err) => toast.error(err.response?.data?.message ?? "Failed to remove tailor"),
  });
}
