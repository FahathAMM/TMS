"use client";

import { useStockMovements } from "@/hooks/useStockMovements";
import { useTableParams } from "@/hooks/useTableParams";
import { DataTable } from "@/components/shared/DataTable";
import { Pagination } from "@/components/shared/Pagination";
import { TableToolbar } from "@/components/shared/TableToolbar";
import { Badge } from "@/components/ui/badge";
import { ArrowUpCircle, ArrowDownCircle } from "lucide-react";

export default function StockMovementsPage() {
  const { search, page, perPage, setSearch, setPage, setPerPage } = useTableParams();
  const { data, isLoading } = useStockMovements({ page, per_page: perPage, search });

  const columns = [
    { key: "date", header: "Date" },
    { key: "product", header: "Item", render: (r) => (
      <div>
        <p className="font-medium">{r.product?.name}</p>
        <p className="text-xs text-muted-foreground font-mono">{r.product?.sku}</p>
      </div>
    ) },
    { key: "type_label", header: "Type", render: (r) => (
      <Badge variant={r.is_inbound ? "success" : "destructive"} className="gap-1">
        {r.is_inbound ? <ArrowUpCircle className="h-3 w-3" /> : <ArrowDownCircle className="h-3 w-3" />}
        {r.type_label}
      </Badge>
    ) },
    { key: "quantity", header: "Qty", render: (r) => (
      <span className={r.is_inbound ? "text-green-700" : "text-destructive"}>
        {r.is_inbound ? "+" : "-"}{Number(r.quantity).toFixed(2)}
      </span>
    ) },
    { key: "quantity_after", header: "Balance After", render: (r) => Number(r.quantity_after).toFixed(2) },
    { key: "reference_type", header: "Reference", render: (r) => r.reference_type
      ? <span className="text-xs font-mono">{r.reference_type} #{r.reference_id}</span>
      : <span className="text-muted-foreground">—</span> },
    { key: "notes", header: "Notes", render: (r) => r.notes ?? <span className="text-muted-foreground">—</span> },
  ];

  return (
    <div>
      <TableToolbar search={search} onSearchChange={(v) => { setSearch(v); setPage(1); }} placeholder="Search by product..." />
      <DataTable columns={columns} data={data?.data ?? []} loading={isLoading} search={search} />
      <Pagination meta={data?.meta} onPageChange={setPage} perPage={perPage} onPerPageChange={setPerPage} />
    </div>
  );
}
