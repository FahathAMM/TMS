"use client";

import Link from "next/link";
import { useOrders } from "@/hooks/useOrders";
import { useTableParams } from "@/hooks/useTableParams";
import { DataTable } from "@/components/shared/DataTable";
import { Pagination } from "@/components/shared/Pagination";
import { TableToolbar } from "@/components/shared/TableToolbar";
import { StatusBadge } from "@/components/shared/StatusBadge";
import { Button } from "@/components/ui/button";
import { useAuthStore } from "@/store/authStore";
import { Plus, Eye, Scissors } from "lucide-react";

export default function OrdersPage() {
  const { can } = useAuthStore();
  const { search, page, perPage, setSearch, setPage, setPerPage } = useTableParams();
  const { data, isLoading } = useOrders({ page, search, per_page: perPage });

  const columns = [
    { key: "order_number", header: "Order #", render: (r) => <span className="font-mono font-medium">{r.order_number}</span> },
    { key: "customer", header: "Customer", render: (r) => r.customer?.name },
    { key: "order_type", header: "Type", render: (r) => <span className="capitalize">{r.order_type?.replace("_", " ")}</span> },
    { key: "status", header: "Status", render: (r) => <StatusBadge status={r.status} /> },
    { key: "total_amount", header: "Total", render: (r) => Number(r.total_amount).toFixed(2) },
    { key: "balance_due", header: "Balance Due", render: (r) => (
      <span className={r.balance_due > 0 ? "text-destructive font-medium" : "text-green-700 font-medium"}>{Number(r.balance_due).toFixed(2)}</span>
    ) },
    { key: "expected_delivery_date", header: "Delivery", render: (r) => r.expected_delivery_date ?? <span className="text-muted-foreground">—</span> },
    {
      key: "actions", header: "Actions", render: (r) => (
        <Button variant="ghost" size="icon" asChild><Link href={`/orders/${r.id}`}><Eye className="h-4 w-4" /></Link></Button>
      ),
    },
  ];

  return (
    <div>
      <TableToolbar
        search={search} onSearchChange={(v) => { setSearch(v); setPage(1); }}
        placeholder="Search by order # or customer..."
        action={can("create tailoring_orders") && (
          <div className="flex gap-2">
            <Button size="sm" variant="outline" asChild><Link href="/alteration"><Scissors />Quick Alteration</Link></Button>
            <Button size="sm" asChild><Link href="/orders/create"><Plus />New Order</Link></Button>
          </div>
        )}
      />
      <DataTable columns={columns} data={data?.data ?? []} loading={isLoading} search={search} />
      <Pagination meta={data?.meta} onPageChange={setPage} perPage={perPage} onPerPageChange={setPerPage} />
    </div>
  );
}
