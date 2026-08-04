"use client";

import { useQuery, useMutation, useQueryClient } from "@tanstack/react-query";
import { productService } from "@/services/productService";
import { toast } from "sonner";

export function useProducts(params = {}) {
  return useQuery({
    queryKey: ["products", params],
    queryFn: () => productService.getAll(params).then((r) => r.data),
  });
}

export function useAllProducts(params = {}) {
  return useQuery({
    queryKey: ["products", "all", params],
    queryFn: () => productService.getAll({ ...params, per_page: 500 }).then((r) => r.data.data),
  });
}

export function useCreateProduct() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (data) => productService.create(data),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ["products"] });
      toast.success("Product added");
    },
    onError: (err) => toast.error(err.response?.data?.message ?? "Failed to add product"),
  });
}

export function useUpdateProduct() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: ({ id, data }) => productService.update(id, data),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ["products"] });
      toast.success("Product updated");
    },
    onError: (err) => toast.error(err.response?.data?.message ?? "Failed to update product"),
  });
}

export function useDeleteProduct() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (id) => productService.remove(id),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ["products"] });
      toast.success("Product deleted");
    },
    onError: (err) => toast.error(err.response?.data?.message ?? "Failed to delete product"),
  });
}
