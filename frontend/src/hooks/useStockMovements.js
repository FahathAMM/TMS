"use client";

import { useQuery } from "@tanstack/react-query";
import { stockMovementService } from "@/services/stockMovementService";

export function useStockMovements(params = {}) {
  return useQuery({
    queryKey: ["stock-movements", params],
    queryFn: () => stockMovementService.getAll(params).then((r) => r.data),
  });
}
