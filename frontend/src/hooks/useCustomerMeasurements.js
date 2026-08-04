"use client";

import { useQuery, useMutation, useQueryClient } from "@tanstack/react-query";
import { customerMeasurementService } from "@/services/customerMeasurementService";
import { toast } from "sonner";

export function useCustomerMeasurements(customerId) {
  return useQuery({
    queryKey: ["customer-measurements", customerId],
    queryFn: () => customerMeasurementService.getAll(customerId).then((r) => r.data.data),
    enabled: !!customerId,
  });
}

export function useSaveCustomerMeasurements(customerId) {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (measurements) => customerMeasurementService.update(customerId, { measurements }),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ["customer-measurements", customerId] });
      toast.success("Measurements saved");
    },
    onError: (err) => toast.error(err.response?.data?.message ?? "Failed to save measurements"),
  });
}
