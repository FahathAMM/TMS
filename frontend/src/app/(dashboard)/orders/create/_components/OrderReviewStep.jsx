"use client";

import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import {
  User, Shirt, Scissors, Ruler, Calendar, StickyNote, Wallet, Banknote, CreditCard,
} from "lucide-react";

const ORDER_TYPE_LABEL = {
  custom_stitching: { label: "Custom Stitching — New Dress", icon: Shirt },
  alteration: { label: "Alteration — Existing Dress", icon: Scissors },
};

function ReviewRow({ icon: Icon, label, children }) {
  return (
    <div className="flex items-start gap-3 py-2">
      <Icon className="h-4 w-4 text-muted-foreground mt-0.5 shrink-0" />
      <div className="flex-1 min-w-0">
        <p className="text-xs text-muted-foreground">{label}</p>
        <div className="text-sm font-medium">{children}</div>
      </div>
    </div>
  );
}

/**
 * Read-only summary of the whole order draft — pure presentational, derives
 * everything from the same state `handleSubmit` in page.jsx already uses.
 * No API calls here.
 */
export function OrderReviewStep({
  customerMode, customerName, newCustomer,
  orderType, items, measurementTypes,
  expectedDeliveryDate, notes,
  subtotal, discountAmount, taxAmount, total,
  collectPayment, initialPaymentAmount, initialPaymentType, initialPaymentMethod, balanceAfterInitialPayment,
}) {
  const typeInfo = ORDER_TYPE_LABEL[orderType] ?? ORDER_TYPE_LABEL.custom_stitching;

  const measurementTypeName = (id) => (measurementTypes ?? []).find((t) => String(t.id) === String(id))?.name;

  return (
    <div className="space-y-4 max-w-3xl">
      <p className="text-sm text-muted-foreground">
        Please check everything below before creating the order. You can go back to any step to fix something.
      </p>

      <Card>
        <CardHeader className="pb-2"><CardTitle className="text-base">Customer & Order</CardTitle></CardHeader>
        <CardContent className="pt-0 divide-y">
          <ReviewRow icon={User} label="Customer">
            {customerMode === "existing" ? (customerName || "—") : (newCustomer?.name || "—")}
            {customerMode === "new" && <Badge variant="info" className="ml-2 align-middle">New Customer</Badge>}
          </ReviewRow>
          <ReviewRow icon={typeInfo.icon} label="Order Type">{typeInfo.label}</ReviewRow>
          {expectedDeliveryDate && <ReviewRow icon={Calendar} label="Expected Delivery">{expectedDeliveryDate}</ReviewRow>}
          {notes && <ReviewRow icon={StickyNote} label="Notes">{notes}</ReviewRow>}
        </CardContent>
      </Card>

      <Card>
        <CardHeader className="pb-2">
          <CardTitle className="text-base">{orderType === "alteration" ? "Alteration Items" : "Dress Items"} ({items.length})</CardTitle>
        </CardHeader>
        <CardContent className="pt-0 space-y-3">
          {items.map((it, i) => {
            const lineTotal = (Number(it.quantity) || 0) * (Number(it.unit_price) || 0) - (Number(it.discount) || 0);
            const typeName = measurementTypeName(it.measurement_type_id);
            return (
              <div key={i} className="rounded-lg border p-3">
                <div className="flex items-center justify-between gap-2">
                  <p className="text-sm font-semibold flex items-center gap-1.5">
                    <Shirt className="h-3.5 w-3.5 text-muted-foreground" />{it.garment_type || `Item ${i + 1}`}
                  </p>
                  <span className="text-sm font-semibold tabular-nums">{lineTotal.toFixed(2)}</span>
                </div>
                <p className="text-xs text-muted-foreground mt-1">
                  Qty {it.quantity || 1} · {Number(it.unit_price || 0).toFixed(2)} each
                </p>
                {typeName && (
                  <p className="text-xs text-muted-foreground mt-1 flex items-center gap-1">
                    <Ruler className="h-3 w-3" />Measurements: {typeName}
                  </p>
                )}
              </div>
            );
          })}
        </CardContent>
      </Card>

      <Card>
        <CardHeader className="pb-2"><CardTitle className="text-base">Payment</CardTitle></CardHeader>
        <CardContent className="pt-0 space-y-2">
          <div className="grid grid-cols-3 gap-2 text-center">
            <div className="rounded-lg bg-primary/10 p-3">
              <Wallet className="h-4 w-4 mx-auto text-primary mb-1" />
              <p className="text-xs text-muted-foreground">Total</p>
              <p className="text-sm font-bold">{total.toFixed(2)}</p>
            </div>
            <div className="rounded-lg bg-green-50 p-3">
              <Banknote className="h-4 w-4 mx-auto text-green-700 mb-1" />
              <p className="text-xs text-muted-foreground">Paying Now</p>
              <p className="text-sm font-bold text-green-700">
                {collectPayment ? (Number(initialPaymentAmount) || 0).toFixed(2) : "0.00"}
              </p>
            </div>
            <div className="rounded-lg bg-orange-50 p-3">
              <CreditCard className="h-4 w-4 mx-auto text-orange-700 mb-1" />
              <p className="text-xs text-muted-foreground">Balance Due</p>
              <p className="text-sm font-bold text-orange-700">
                {(collectPayment ? balanceAfterInitialPayment : total).toFixed(2)}
              </p>
            </div>
          </div>
          {collectPayment && Number(initialPaymentAmount) > 0 && (
            <p className="text-xs text-muted-foreground text-center">
              Paying {initialPaymentType === "full" ? "in full" : "a deposit"} by {initialPaymentMethod.replace("_", " ")}
            </p>
          )}
        </CardContent>
      </Card>
    </div>
  );
}
