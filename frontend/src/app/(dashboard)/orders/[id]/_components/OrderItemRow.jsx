"use client";

import { useState } from "react";
import { useAllTailors } from "@/hooks/useTailors";
import { useAdvanceItemStatus, useAssignTailor } from "@/hooks/useOrders";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import { StatusBadge } from "@/components/shared/StatusBadge";
import { SectionBox, SECTION_THEME as THEME } from "@/components/shared/SectionBox";
import { SelectInput } from "@/components/forms/SelectInput";
import { Stepper } from "@/components/shared/Stepper";
import { Scissors, Shirt, Ruler, Palette, UserCog, Clock, PackageCheck, Truck, Check } from "lucide-react";

const PRODUCTION_STEPS = [
  { key: "pending", label: "Pending", icon: Clock },
  { key: "cutting", label: "Cutting", icon: Scissors },
  { key: "stitching", label: "Stitching", icon: Shirt },
  { key: "ready", label: "Ready", icon: PackageCheck },
  { key: "delivered", label: "Delivered", icon: Truck },
];

export function OrderItemRow({ orderId, orderType, item }) {
  const { data: tailors } = useAllTailors();
  const advanceStatus = useAdvanceItemStatus();
  const assignTailor = useAssignTailor();

  const [nextStatus, setNextStatus] = useState(item.production_status);
  const [tailorId, setTailorId] = useState("");

  const tailorOptions = (tailors ?? []).map((t) => ({ value: String(t.id), label: t.full_name }));
  const isAlteration = orderType === "alteration";

  const currentStepIndex = PRODUCTION_STEPS.findIndex((s) => s.key === item.production_status);
  const nextStepLabel = PRODUCTION_STEPS.find((s) => s.key === nextStatus)?.label;
  const statusChanged = nextStatus !== item.production_status;

  return (
    <Card className="border-2 overflow-hidden">
      <CardHeader className="flex flex-row items-center justify-between gap-2 space-y-0 py-2.5 px-4 bg-primary text-primary-foreground">
        <CardTitle className="text-sm font-semibold flex items-center gap-2 text-primary-foreground">
          <span className="flex h-7 w-7 items-center justify-center rounded-full bg-white/20 shrink-0">
            {isAlteration ? <Scissors className="h-4 w-4" /> : <Shirt className="h-4 w-4" />}
          </span>
          <span>
            {item.garment_type}
            <span className="block text-[11px] font-normal text-primary-foreground/80 font-mono">{item.job_card_number}</span>
          </span>
        </CardTitle>
        <StatusBadge status={item.production_status} />
      </CardHeader>

      <CardContent className="space-y-2.5 pt-3">
        <div className="flex flex-wrap items-center gap-2 text-sm">
          <Badge variant="outline" className="text-xs">Qty {item.quantity}</Badge>
          <Badge variant="outline" className="text-xs font-semibold">{Number(item.total).toFixed(2)}</Badge>
          <Badge variant={item.fabric_source === "in_house" ? "info" : "secondary"} className="text-xs">
            {item.fabric_source === "in_house" ? "Shop Fabric" : "Customer's Own Fabric"}
          </Badge>
        </div>

        <div className="grid grid-cols-1 lg:grid-cols-2 gap-2.5 items-start">
          {item.style_specifications && Object.keys(item.style_specifications).length > 0 && (
            <SectionBox theme={THEME.style} icon={Palette} title="Style Details" subtitle="Chosen options for this item">
              <div className="flex flex-wrap gap-1.5">
                {Object.entries(item.style_specifications).map(([k, v]) => (
                  <span key={k} className="text-xs rounded-full border border-purple-200 bg-background px-2 py-0.5">
                    {k}: <span className="font-medium">{v}</span>
                  </span>
                ))}
              </div>
            </SectionBox>
          )}

          {item.materials?.length > 0 && (
            <SectionBox theme={THEME.fabric} icon={Scissors} title="Fabric & Trim" subtitle="Reserved from stock for this item">
              <div className="space-y-1">
                {item.materials.map((m) => (
                  <div key={m.id} className="flex items-center justify-between gap-2 text-xs rounded-md border border-amber-200 bg-background px-2 py-1">
                    <span>{m.product_name} · {m.quantity_required} {m.unit_of_measure}</span>
                    <StatusBadge status={m.status} />
                  </div>
                ))}
              </div>
            </SectionBox>
          )}
        </div>

        {item.measurements?.length > 0 && (
          <SectionBox theme={THEME.measure} icon={Ruler} title={item.measurement_type?.name ? `${item.measurement_type.name} Measurements` : "Measurements"}
            subtitle="Recorded when this order was created">
            <div className="flex flex-wrap gap-1.5">
              {item.measurements.map((m) => (
                <span key={m.id} className="text-xs rounded-full border border-indigo-200 bg-background px-2 py-0.5 flex items-center gap-1">
                  <Badge className="w-4 h-4 text-[9px] justify-center shrink-0 rounded-full p-0 bg-indigo-600 hover:bg-indigo-600">{m.number}</Badge>
                  {m.name}: <span className="font-medium">{m.value ?? "—"} {m.unit}</span>
                </span>
              ))}
            </div>
          </SectionBox>
        )}

        <SectionBox theme={THEME.production} icon={UserCog} title="Tailor & Production" subtitle="Who's working on this, and how far along it is">
          <div className="flex flex-wrap items-center gap-3 rounded-lg bg-background border px-3 py-2">
            <span className={`flex h-9 w-9 shrink-0 items-center justify-center rounded-full ${item.current_tailor ? "bg-blue-100 text-blue-700" : "bg-muted text-muted-foreground"}`}>
              <UserCog className="h-5 w-5" />
            </span>
            <div className="min-w-0">
              <p className="text-[11px] text-muted-foreground">Tailor</p>
              <p className="text-sm font-semibold truncate">{item.current_tailor?.name ?? "Not assigned yet"}</p>
            </div>
            <div className="flex items-center gap-2 ml-auto">
              <SelectInput className="w-36" value={tailorId} onChange={setTailorId} options={tailorOptions} placeholder="Select tailor" />
              <Button size="sm" disabled={!tailorId || assignTailor.isPending}
                onClick={() => assignTailor.mutate({ orderId, itemId: item.id, data: { tailor_id: Number(tailorId) } }, { onSuccess: () => setTailorId("") })}>
                {item.current_tailor ? "Reassign" : "Assign"}
              </Button>
            </div>
          </div>

          <div className="rounded-lg bg-background border px-3 py-3 space-y-2.5">
            <p className="text-[11px] font-medium text-muted-foreground">Production Progress — tap a stage to select it</p>
            <Stepper steps={PRODUCTION_STEPS} current={currentStepIndex} maxReached={PRODUCTION_STEPS.length - 1}
              onStepClick={(i) => setNextStatus(PRODUCTION_STEPS[i].key)} />
            <div className="flex items-center justify-between gap-2 pt-1 border-t">
              <p className="text-xs text-muted-foreground pt-2">
                {statusChanged
                  ? <>Ready to set stage to <span className="font-semibold text-foreground">{nextStepLabel ?? nextStatus}</span></>
                  : "No change selected."}
              </p>
              <Button size="sm" variant="info" className="mt-2" disabled={advanceStatus.isPending || !statusChanged}
                onClick={() => advanceStatus.mutate({ orderId, itemId: item.id, data: { production_status: nextStatus } })}>
                <Check className="h-4 w-4" />Confirm Update
              </Button>
            </div>
          </div>
        </SectionBox>
      </CardContent>
    </Card>
  );
}
