"use client";

import { useState } from "react";
import { useJournalEntries } from "@/hooks/useAccounting";
import { useTableParams } from "@/hooks/useTableParams";
import { Pagination } from "@/components/shared/Pagination";
import { ChevronDown, ChevronRight } from "lucide-react";
import { cn } from "@/lib/utils";

export default function JournalPage() {
  const { page, perPage, setPage, setPerPage } = useTableParams();
  const { data, isLoading } = useJournalEntries({ page, per_page: perPage });
  const [expanded, setExpanded] = useState(new Set());

  const toggle = (id) => setExpanded((p) => {
    const next = new Set(p);
    next.has(id) ? next.delete(id) : next.add(id);
    return next;
  });

  return (
    <div>
      <div className="rounded-xl border bg-card shadow-sm divide-y">
        {isLoading && <div className="p-6 text-center text-sm text-muted-foreground">Loading…</div>}
        {!isLoading && (data?.data ?? []).length === 0 && (
          <div className="p-10 text-center text-sm text-muted-foreground">No journal entries yet.</div>
        )}
        {(data?.data ?? []).map((entry) => {
          const isOpen = expanded.has(entry.id);
          const totalDebit = entry.lines.reduce((s, l) => s + l.debit, 0);
          return (
            <div key={entry.id}>
              <button
                type="button"
                onClick={() => toggle(entry.id)}
                className="w-full flex items-center justify-between p-3 text-left hover:bg-muted/30 transition-colors"
              >
                <div className="flex items-center gap-2">
                  {isOpen ? <ChevronDown className="h-4 w-4 text-muted-foreground" /> : <ChevronRight className="h-4 w-4 text-muted-foreground" />}
                  <div>
                    <p className="text-sm font-medium">{entry.description}</p>
                    <p className="text-xs text-muted-foreground">{entry.entry_date}</p>
                  </div>
                </div>
                <span className="text-sm font-medium tabular-nums">{totalDebit.toFixed(2)}</span>
              </button>
              {isOpen && (
                <div className="px-4 pb-3">
                  <table className="w-full text-xs">
                    <thead>
                      <tr className="text-muted-foreground">
                        <th className="text-left font-medium py-1">Account</th>
                        <th className="text-right font-medium py-1">Debit</th>
                        <th className="text-right font-medium py-1">Credit</th>
                      </tr>
                    </thead>
                    <tbody>
                      {entry.lines.map((line, i) => (
                        <tr key={i} className="border-t">
                          <td className="py-1.5">{line.account_name} <span className="text-muted-foreground font-mono">({line.account_code})</span></td>
                          <td className={cn("py-1.5 text-right tabular-nums", line.debit > 0 && "font-medium")}>{line.debit > 0 ? line.debit.toFixed(2) : ""}</td>
                          <td className={cn("py-1.5 text-right tabular-nums", line.credit > 0 && "font-medium")}>{line.credit > 0 ? line.credit.toFixed(2) : ""}</td>
                        </tr>
                      ))}
                    </tbody>
                  </table>
                </div>
              )}
            </div>
          );
        })}
      </div>
      <Pagination meta={data?.meta} onPageChange={setPage} perPage={perPage} onPerPageChange={setPerPage} />
    </div>
  );
}
