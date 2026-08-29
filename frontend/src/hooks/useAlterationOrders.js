"use client";

import { useQuery, useMutation, useQueryClient } from "@tanstack/react-query";
import { alterationOrderService } from "@/services/alterationOrderService";
import { toast } from "sonner";

export function useAlterationOrders(params = {}) {
  return useQuery({
    queryKey: ["alteration-orders", params],
    queryFn: () => alterationOrderService.getAll(params).then((r) => r.data),
  });
}

export function useAlterationOrder(id) {
  return useQuery({
    queryKey: ["alteration-orders", id],
    queryFn: () => alterationOrderService.getOne(id).then((r) => r.data.data),
    enabled: !!id,
  });
}

function useAlterationOrderMutation(fn, successMessage) {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: fn,
    onSuccess: (_res, variables) => {
      const orderId = variables?.orderId;
      qc.invalidateQueries({ queryKey: ["alteration-orders", orderId] });
      qc.invalidateQueries({ queryKey: ["alteration-orders"] });
      qc.invalidateQueries({ queryKey: ["accounting"] });
      qc.invalidateQueries({ queryKey: ["reports"] });
      if (successMessage) toast.success(successMessage);
    },
    onError: (err) => toast.error(err.response?.data?.message ?? "Action failed"),
  });
}

export function useAddGarment() {
  return useAlterationOrderMutation(({ orderId, data }) => alterationOrderService.storeGarment(orderId, data), "Garment added");
}

export function useMarkGarmentDelivered() {
  return useAlterationOrderMutation(({ orderId, garmentId }) => alterationOrderService.markGarmentDelivered(orderId, garmentId), "Garment marked delivered");
}

export function useUploadGarmentPhoto() {
  return useAlterationOrderMutation(({ orderId, garmentId, formData }) => alterationOrderService.uploadPhoto(orderId, garmentId, formData), "Photo uploaded");
}

export function useAddTask() {
  return useAlterationOrderMutation(({ orderId, garmentId, data }) => alterationOrderService.storeTask(orderId, garmentId, data), "Task added");
}

export function useAdvanceTaskStatus() {
  return useAlterationOrderMutation(({ orderId, garmentId, taskId, data }) => alterationOrderService.advanceTaskStatus(orderId, garmentId, taskId, data), "Task status updated");
}

export function useAssignAlterationTailor() {
  return useAlterationOrderMutation(({ orderId, garmentId, taskId, data }) => alterationOrderService.assignTailor(orderId, garmentId, taskId, data), "Tailor assigned");
}

export function useRecordAlterationPayment() {
  return useAlterationOrderMutation(({ orderId, data }) => alterationOrderService.storePayment(orderId, data), "Payment recorded");
}

export function useCompleteAlterationOrder() {
  return useAlterationOrderMutation(({ orderId }) => alterationOrderService.complete(orderId), "Order completed");
}

export function useCancelAlterationOrder() {
  return useAlterationOrderMutation(({ orderId, data }) => alterationOrderService.cancel(orderId, data), "Order cancelled");
}

export function useAlterationOrderNotifications(orderId) {
  return useQuery({
    queryKey: ["alteration-orders", orderId, "notifications"],
    queryFn: () => alterationOrderService.notifications(orderId).then((r) => r.data.data),
    enabled: !!orderId,
  });
}

export function useNotifyAlterationOrder() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: ({ orderId, data }) => alterationOrderService.notify(orderId, data),
    onSuccess: (_res, { orderId }) => {
      qc.invalidateQueries({ queryKey: ["alteration-orders", orderId, "notifications"] });
      toast.success("Notification sent");
    },
    onError: (err) => toast.error(err.response?.data?.message ?? "Failed to send notification"),
  });
}
