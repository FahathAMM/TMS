"use client";

import { useQuery, useMutation, useQueryClient } from "@tanstack/react-query";
import { categoryService } from "@/services/categoryService";
import { toast } from "sonner";

export function useCategories(params = {}) {
  return useQuery({
    queryKey: ["categories", params],
    queryFn: () => categoryService.getAll(params).then((r) => r.data.data ?? r.data),
  });
}

export function useCreateCategory() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (data) => categoryService.create(data),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ["categories"] });
      toast.success("Category added");
    },
    onError: (err) => toast.error(err.response?.data?.message ?? "Failed to add category"),
  });
}

export function useDeleteCategory() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (id) => categoryService.remove(id),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ["categories"] });
      toast.success("Category deleted");
    },
    onError: (err) => toast.error(err.response?.data?.message ?? "Failed to delete category"),
  });
}
