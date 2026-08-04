"use client";

import { useCallback, useMemo } from "react";
import { useSettings } from "./useSettings";
import { formatCurrency } from "@/lib/utils";

export function useAdminCurrencyFormat() {
  const { data: allSettings } = useSettings();
  const currency = useMemo(() => {
    const group = allSettings?.currency;
    if (!group) return null;
    const flat = {};
    group.forEach((s) => { flat[s.key] = s.value ?? ""; });
    return flat;
  }, [allSettings]);
  return useCallback(
    (amount) => formatCurrency(amount, currency),
    [currency],
  );
}

export function useAdminCurrencySymbol() {
  const { data: allSettings } = useSettings();
  return useMemo(() => {
    const group = allSettings?.currency;
    if (!group) return "$";
    const entry = group.find((s) => s.key === "currency_symbol");
    return entry?.value || "$";
  }, [allSettings]);
}
