"use client";

import { useAccounts } from "@/hooks/useAccounting";
import { DataTable } from "@/components/shared/DataTable";
import { Badge } from "@/components/ui/badge";

const TYPE_VARIANTS = { asset: "default", liability: "destructive", equity: "secondary", revenue: "success", expense: "warning" };

export default function ChartOfAccountsPage() {
  const { data, isLoading } = useAccounts();

  const columns = [
    { key: "code", header: "Code", render: (r) => <span className="font-mono text-xs">{r.code}</span> },
    { key: "name", header: "Account", render: (r) => <span className="font-medium">{r.name}</span> },
    { key: "type", header: "Type", render: (r) => <Badge variant={TYPE_VARIANTS[r.type] ?? "outline"}>{r.type}</Badge> },
    { key: "normal_balance", header: "Normal Balance", render: (r) => <span className="capitalize text-muted-foreground">{r.normal_balance}</span> },
    { key: "balance", header: "Balance", render: (r) => (
      <span className={`font-medium tabular-nums ${r.balance < 0 ? "text-destructive" : ""}`}>{Number(r.balance).toFixed(2)}</span>
    ) },
  ];

  return (
    <div>
      <p className="text-sm text-muted-foreground mb-4">
        Accounts are updated automatically by order payments, material consumption, and purchase receipts — there is no manual entry.
      </p>
      <DataTable columns={columns} data={data ?? []} loading={isLoading} />
    </div>
  );
}
