"use client";

import { useState, useMemo } from "react";
import Link from "next/link";
import { useOrders } from "@/hooks/useOrders";
import { DataTable } from "@/components/shared/DataTable";
import { StatusBadge } from "@/components/shared/StatusBadge";
import { Button } from "@/components/ui/button";
import { cn } from "@/lib/utils";
import { Eye } from "lucide-react";

const STAGES = [
  { value: "", label: "All" },
  { value: "pending", label: "Pending" },
  { value: "cutting", label: "Cutting" },
  { value: "stitching", label: "Stitching" },
  { value: "qc", label: "QC" },
  { value: "rework", label: "Rework" },
  { value: "ready", label: "Ready" },
  { value: "delivered", label: "Delivered" },
];

export default function ProductionBoardPage() {
  const [stage, setStage] = useState("");
  const { data, isLoading } = useOrders({ per_page: 100 });

  const rows = useMemo(() => {
    const orders = data?.data ?? [];
    const flattened = orders.flatMap((order) =>
      (order.items ?? []).map((item) => ({ ...item, order_number: order.order_number, customer_name: order.customer?.name, order_id: order.id }))
    );
    return stage ? flattened.filter((r) => r.production_status === stage) : flattened;
  }, [data, stage]);

  const columns = [
    { key: "job_card_number", header: "Job Card", render: (r) => <span className="font-mono text-xs">{r.job_card_number}</span> },
    { key: "order_number", header: "Order", render: (r) => r.order_number },
    { key: "customer_name", header: "Customer", render: (r) => r.customer_name },
    { key: "garment_type", header: "Garment", render: (r) => r.garment_type },
    { key: "production_status", header: "Stage", render: (r) => <StatusBadge status={r.production_status} /> },
    {
      key: "actions", header: "Actions", render: (r) => (
        <Button variant="ghost" size="icon" asChild><Link href={`/orders/${r.order_id}`}><Eye className="h-4 w-4" /></Link></Button>
      ),
    },
  ];

  return (
    <div>
      <div className="flex flex-wrap gap-1.5 mb-4">
        {STAGES.map((s) => (
          <button
            key={s.value}
            type="button"
            onClick={() => setStage(s.value)}
            className={cn(
              "px-3 py-1.5 rounded-full text-xs font-medium border transition-colors",
              stage === s.value ? "bg-primary text-primary-foreground border-primary" : "hover:bg-muted"
            )}
          >
            {s.label}
          </button>
        ))}
      </div>
      <DataTable columns={columns} data={rows} loading={isLoading} />
    </div>
  );
}
