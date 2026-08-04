"use client";

import { useState } from "react";
import { useAllTailors } from "@/hooks/useTailors";
import { useAdvanceItemStatus, useQcItem, useAssignTailor } from "@/hooks/useOrders";
import { Card, CardContent } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { StatusBadge } from "@/components/shared/StatusBadge";
import { SelectInput } from "@/components/forms/SelectInput";
import { TextareaInput } from "@/components/forms/FormField";
import { CheckCircle2, XCircle, Scissors } from "lucide-react";

const STATUS_OPTIONS = [
  { value: "pending", label: "Pending" },
  { value: "cutting", label: "Cutting" },
  { value: "stitching", label: "Stitching" },
  { value: "qc", label: "Quality Check" },
  { value: "ready", label: "Ready" },
  { value: "delivered", label: "Delivered" },
];

export function OrderItemRow({ orderId, item }) {
  const { data: tailors } = useAllTailors();
  const advanceStatus = useAdvanceItemStatus();
  const qc = useQcItem();
  const assignTailor = useAssignTailor();

  const [nextStatus, setNextStatus] = useState(item.production_status);
  const [tailorId, setTailorId] = useState("");
  const [qcNotes, setQcNotes] = useState("");

  const tailorOptions = (tailors ?? []).map((t) => ({ value: String(t.id), label: t.full_name }));

  return (
    <Card>
      <CardContent className="pt-4 space-y-3">
        <div className="flex items-start justify-between gap-3">
          <div>
            <p className="font-medium">{item.garment_type}</p>
            <p className="text-xs text-muted-foreground font-mono">{item.job_card_number}</p>
            <p className="text-xs text-muted-foreground">
              Qty {item.quantity} · {Number(item.total).toFixed(2)} · {item.fabric_source === "in_house" ? "In-house fabric" : "Customer-provided fabric"}
            </p>
          </div>
          <StatusBadge status={item.production_status} />
        </div>

        {item.style_specifications && Object.keys(item.style_specifications).length > 0 && (
          <div className="flex flex-wrap gap-1.5">
            {Object.entries(item.style_specifications).map(([k, v]) => (
              <span key={k} className="text-xs rounded-full border px-2 py-0.5 bg-muted/40">{k}: <span className="font-medium">{v}</span></span>
            ))}
          </div>
        )}

        {item.materials?.length > 0 && (
          <div className="text-xs text-muted-foreground space-y-0.5">
            {item.materials.map((m) => (
              <div key={m.id} className="flex items-center gap-1.5">
                <Scissors className="h-3 w-3" />
                {m.product_name}: {m.quantity_required} {m.unit_of_measure}
                <StatusBadge status={m.status} />
              </div>
            ))}
          </div>
        )}

        <div className="flex flex-wrap items-end gap-2 pt-2 border-t">
          <SelectInput className="w-40" label="Tailor" value={tailorId} onChange={setTailorId} options={tailorOptions} />
          <Button size="sm" variant="outline" disabled={!tailorId || assignTailor.isPending}
            onClick={() => assignTailor.mutate({ orderId, itemId: item.id, data: { tailor_id: Number(tailorId) } }, { onSuccess: () => setTailorId("") })}>
            Assign
          </Button>
          {item.current_tailor && <span className="text-xs text-muted-foreground mb-2">Current: {item.current_tailor.name}</span>}

          <SelectInput className="w-40" label="Production Status" value={nextStatus} onChange={setNextStatus} options={STATUS_OPTIONS} />
          <Button size="sm" variant="outline" disabled={advanceStatus.isPending || nextStatus === item.production_status}
            onClick={() => advanceStatus.mutate({ orderId, itemId: item.id, data: { production_status: nextStatus } })}>
            Update
          </Button>
        </div>

        {item.production_status === "qc" && (
          <div className="rounded-md border p-3 bg-muted/20 space-y-2">
            <TextareaInput label="QC Notes" value={qcNotes} onChange={(e) => setQcNotes(e.target.value)} rows={2} />
            <div className="flex gap-2">
              <Button size="sm" className="gap-1" disabled={qc.isPending}
                onClick={() => qc.mutate({ orderId, itemId: item.id, data: { passed: true, notes: qcNotes } }, { onSuccess: () => setQcNotes("") })}>
                <CheckCircle2 className="h-3.5 w-3.5" />Pass
              </Button>
              <Button size="sm" variant="destructive" className="gap-1" disabled={qc.isPending}
                onClick={() => qc.mutate({ orderId, itemId: item.id, data: { passed: false, notes: qcNotes } }, { onSuccess: () => setQcNotes("") })}>
                <XCircle className="h-3.5 w-3.5" />Fail — Rework
              </Button>
            </div>
          </div>
        )}
      </CardContent>
    </Card>
  );
}
