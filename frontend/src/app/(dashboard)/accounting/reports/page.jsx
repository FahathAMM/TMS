"use client";

import { useState } from "react";
import { useTrialBalance, useProfitLoss } from "@/hooks/useAccounting";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { TextInput } from "@/components/forms/FormField";

export default function AccountingReportsPage() {
  const [from, setFrom] = useState("");
  const [to, setTo] = useState("");
  const { data: trialBalance } = useTrialBalance();
  const { data: pnl } = useProfitLoss({ from: from || undefined, to: to || undefined });

  return (
    <div className="space-y-6">
      <Card>
        <CardHeader className="pb-3"><CardTitle className="text-base">Profit &amp; Loss</CardTitle></CardHeader>
        <CardContent>
          <div className="flex gap-4 mb-4">
            <TextInput label="From" type="date" value={from} onChange={(e) => setFrom(e.target.value)} />
            <TextInput label="To" type="date" value={to} onChange={(e) => setTo(e.target.value)} />
          </div>
          <div className="grid grid-cols-3 gap-4">
            <div className="rounded-lg border p-4">
              <p className="text-xs text-muted-foreground">Revenue</p>
              <p className="text-2xl font-semibold tabular-nums">{Number(pnl?.revenue ?? 0).toFixed(2)}</p>
            </div>
            <div className="rounded-lg border p-4">
              <p className="text-xs text-muted-foreground">Cost of Goods Sold</p>
              <p className="text-2xl font-semibold tabular-nums">{Number(pnl?.cogs ?? 0).toFixed(2)}</p>
            </div>
            <div className="rounded-lg border p-4 bg-muted/30">
              <p className="text-xs text-muted-foreground">Gross Profit</p>
              <p className="text-2xl font-semibold tabular-nums">{Number(pnl?.gross_profit ?? 0).toFixed(2)}</p>
            </div>
          </div>
        </CardContent>
      </Card>

      <Card>
        <CardHeader className="pb-3"><CardTitle className="text-base">Trial Balance</CardTitle></CardHeader>
        <CardContent>
          <table className="w-full text-sm">
            <thead>
              <tr className="text-xs text-muted-foreground border-b">
                <th className="text-left font-medium py-2">Account</th>
                <th className="text-right font-medium py-2">Balance</th>
              </tr>
            </thead>
            <tbody>
              {(trialBalance ?? []).map((a) => (
                <tr key={a.code} className="border-b last:border-0">
                  <td className="py-2">{a.name} <span className="text-xs text-muted-foreground font-mono">({a.code})</span></td>
                  <td className={`py-2 text-right tabular-nums font-medium ${a.balance < 0 ? "text-destructive" : ""}`}>{Number(a.balance).toFixed(2)}</td>
                </tr>
              ))}
            </tbody>
          </table>
        </CardContent>
      </Card>
    </div>
  );
}
