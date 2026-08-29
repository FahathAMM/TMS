"use client";

import { useQuery, useMutation, useQueryClient } from "@tanstack/react-query";
import { measurementTypeService } from "@/services/measurementTypeService";
import { toast } from "sonner";

export function useMeasurementTypes(params = {}) {
  return useQuery({
    queryKey: ["measurement-types", params],
    queryFn: () => measurementTypeService.getAll(params).then((r) => r.data.data),
  });
}

export function useMeasurementType(id) {
  return useQuery({
    queryKey: ["measurement-types", "one", id],
    queryFn: () => measurementTypeService.getOne(id).then((r) => r.data.data),
    enabled: !!id,
  });
}

export function useCreateMeasurementType() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (data) => measurementTypeService.create(data),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ["measurement-types"] });
      toast.success("Measurement type added");
    },
    onError: (err) => toast.error(err.response?.data?.message ?? "Failed to add measurement type"),
  });
}

export function useUpdateMeasurementType() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: ({ id, data }) => measurementTypeService.update(id, data),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ["measurement-types"] });
      toast.success("Measurement type updated");
    },
    onError: (err) => toast.error(err.response?.data?.message ?? "Failed to update measurement type"),
  });
}

export function useUploadMeasurementTypeImage() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: ({ id, formData }) => measurementTypeService.uploadImage(id, formData),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ["measurement-types"] });
    },
    onError: (err) => toast.error(err.response?.data?.message ?? "Failed to upload image"),
  });
}

export function useDeleteMeasurementType() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (id) => measurementTypeService.remove(id),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ["measurement-types"] });
      toast.success("Measurement type deleted");
    },
    onError: (err) => toast.error(err.response?.data?.message ?? "Failed to delete measurement type"),
  });
}
