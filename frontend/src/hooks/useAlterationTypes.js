"use client";

import { useQuery, useMutation, useQueryClient } from "@tanstack/react-query";
import { alterationTypeService } from "@/services/alterationTypeService";
import { toast } from "sonner";

export function useAlterationTypes(params = {}) {
  return useQuery({
    queryKey: ["alteration-types", params],
    queryFn: () => alterationTypeService.getAll(params).then((r) => r.data),
  });
}

export function useAllAlterationTypes() {
  return useQuery({
    queryKey: ["alteration-types", "all"],
    queryFn: () => alterationTypeService.getAll({ all: 1 }).then((r) => r.data.data),
  });
}

export function useCreateAlterationType() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (data) => alterationTypeService.create(data),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ["alteration-types"] });
      toast.success("Alteration type added");
    },
    onError: (err) => toast.error(err.response?.data?.message ?? "Failed to add alteration type"),
  });
}

export function useUpdateAlterationType() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: ({ id, data }) => alterationTypeService.update(id, data),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ["alteration-types"] });
      toast.success("Alteration type updated");
    },
    onError: (err) => toast.error(err.response?.data?.message ?? "Failed to update alteration type"),
  });
}

export function useDeleteAlterationType() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (id) => alterationTypeService.remove(id),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ["alteration-types"] });
      toast.success("Alteration type removed");
    },
    onError: (err) => toast.error(err.response?.data?.message ?? "Failed to remove alteration type"),
  });
}
