"use client";

import { useQuery, useMutation, useQueryClient } from "@tanstack/react-query";
import { orderService } from "@/services/orderService";
import { toast } from "sonner";

export function useOrders(params = {}) {
  return useQuery({
    queryKey: ["orders", params],
    queryFn: () => orderService.getAll(params).then((r) => r.data),
  });
}

export function useOrder(id) {
  return useQuery({
    queryKey: ["orders", id],
    queryFn: () => orderService.getOne(id).then((r) => r.data.data),
    enabled: !!id,
  });
}

export function useCreateOrder() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (data) => orderService.create(data),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ["orders"] });
      toast.success("Order created successfully");
    },
    onError: (err) => toast.error(err.response?.data?.message ?? "Failed to create order"),
  });
}

function useOrderMutation(fn, successMessage) {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: fn,
    onSuccess: (_res, variables) => {
      const orderId = variables?.orderId ?? variables?.id ?? variables;
      qc.invalidateQueries({ queryKey: ["orders", orderId] });
      qc.invalidateQueries({ queryKey: ["orders"] });
      qc.invalidateQueries({ queryKey: ["products"] });
      qc.invalidateQueries({ queryKey: ["accounting"] });
      if (successMessage) toast.success(successMessage);
    },
    onError: (err) => toast.error(err.response?.data?.message ?? "Action failed"),
  });
}

export function useCompleteOrder() {
  return useOrderMutation(({ id }) => orderService.complete(id), "Order completed");
}

export function useRecordOrderPayment() {
  return useOrderMutation(({ id, data }) => orderService.storePayment(id, data), "Payment recorded");
}

export function useAdvanceItemStatus() {
  return useOrderMutation(({ orderId, itemId, data }) => orderService.advanceItemStatus(orderId, itemId, data), "Production status updated");
}

export function useQcItem() {
  return useOrderMutation(({ orderId, itemId, data }) => orderService.qcItem(orderId, itemId, data), "QC recorded");
}

export function useAssignTailor() {
  return useOrderMutation(({ orderId, itemId, data }) => orderService.assignTailor(orderId, itemId, data), "Tailor assigned");
}

export function useOrderNotifications(orderId) {
  return useQuery({
    queryKey: ["orders", orderId, "notifications"],
    queryFn: () => orderService.notifications(orderId).then((r) => r.data.data),
    enabled: !!orderId,
  });
}

export function useNotifyOrder() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: ({ id, data }) => orderService.notify(id, data),
    onSuccess: (_res, { id }) => {
      qc.invalidateQueries({ queryKey: ["orders", id, "notifications"] });
      toast.success("Notification sent");
    },
    onError: (err) => toast.error(err.response?.data?.message ?? "Failed to send notification"),
  });
}
