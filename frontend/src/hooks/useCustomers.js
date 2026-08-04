"use client";

import { useQuery, useMutation, useQueryClient } from "@tanstack/react-query";
import { customerService } from "@/services/customerService";
import { toast } from "sonner";

export function useCustomers(params = {}) {
  return useQuery({
    queryKey: ["customers", params],
    queryFn: () => customerService.getAll(params).then((r) => r.data),
  });
}

export function useAllCustomers() {
  return useQuery({
    queryKey: ["customers", "all"],
    queryFn: () => customerService.getAll({ all: 1 }).then((r) => r.data.data),
  });
}

export function useCustomer(id) {
  return useQuery({
    queryKey: ["customers", id],
    queryFn: () => customerService.getOne(id).then((r) => r.data.data),
    enabled: !!id,
  });
}

export function useCreateCustomer() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (data) => customerService.create(data),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ["customers"] });
      toast.success("Customer created");
    },
    onError: (err) => toast.error(err.response?.data?.message ?? "Failed to create customer"),
  });
}

export function useUpdateCustomer() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: ({ id, data }) => customerService.update(id, data),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ["customers"] });
      toast.success("Customer updated");
    },
    onError: (err) => toast.error(err.response?.data?.message ?? "Failed to update customer"),
  });
}

export function useDeleteCustomer() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (id) => customerService.remove(id),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ["customers"] });
      toast.success("Customer deleted");
    },
    onError: (err) => toast.error(err.response?.data?.message ?? "Failed to delete customer"),
  });
}
