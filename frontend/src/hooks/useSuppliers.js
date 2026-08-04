"use client";

import { useQuery, useMutation, useQueryClient } from "@tanstack/react-query";
import { supplierService } from "@/services/supplierService";
import { toast } from "sonner";

export function useSuppliers(params = {}) {
  return useQuery({
    queryKey: ["suppliers", params],
    queryFn: () => supplierService.getAll(params).then((r) => r.data),
  });
}

export function useAllSuppliers() {
  return useQuery({
    queryKey: ["suppliers", "all"],
    queryFn: () => supplierService.getAll({ per_page: 200 }).then((r) => r.data.data),
  });
}

export function useCreateSupplier() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (data) => supplierService.create(data),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ["suppliers"] });
      toast.success("Supplier added");
    },
    onError: (err) => toast.error(err.response?.data?.message ?? "Failed to add supplier"),
  });
}

export function useUpdateSupplier() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: ({ id, data }) => supplierService.update(id, data),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ["suppliers"] });
      toast.success("Supplier updated");
    },
    onError: (err) => toast.error(err.response?.data?.message ?? "Failed to update supplier"),
  });
}

export function useDeleteSupplier() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (id) => supplierService.remove(id),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ["suppliers"] });
      toast.success("Supplier deleted");
    },
    onError: (err) => toast.error(err.response?.data?.message ?? "Failed to delete supplier"),
  });
}
