"use client";

import { useQuery } from "@tanstack/react-query";
import { accountingService } from "@/services/accountingService";

export function useAccounts() {
  return useQuery({
    queryKey: ["accounting", "accounts"],
    queryFn: () => accountingService.accounts().then((r) => r.data.data),
  });
}

export function useJournalEntries(params = {}) {
  return useQuery({
    queryKey: ["accounting", "journal-entries", params],
    queryFn: () => accountingService.journalEntries(params).then((r) => r.data),
  });
}

export function useTrialBalance() {
  return useQuery({
    queryKey: ["accounting", "trial-balance"],
    queryFn: () => accountingService.trialBalance().then((r) => r.data.data),
  });
}

export function useProfitLoss(params = {}) {
  return useQuery({
    queryKey: ["accounting", "profit-loss", params],
    queryFn: () => accountingService.profitLoss(params).then((r) => r.data.data),
  });
}
