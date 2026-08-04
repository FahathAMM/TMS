"use client";

import { useState } from "react";
import { useRecordOrderPayment } from "@/hooks/useOrders";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { NumberInput } from "@/components/forms/FormField";
import { SelectInput } from "@/components/forms/SelectInput";

const PAYMENT_TYPE_OPTIONS = [
  { value: "deposit", label: "Deposit" },
  { value: "balance", label: "Balance" },
  { value: "full", label: "Full Payment" },
];
const METHOD_OPTIONS = [
  { value: "cash", label: "Cash" },
  { value: "card", label: "Card" },
  { value: "bank_transfer", label: "Bank Transfer" },
  { value: "online", label: "Online" },
];

export function PaymentsPanel({ order }) {
  const recordPayment = useRecordOrderPayment();
  const [amount, setAmount] = useState("");
  const [paymentType, setPaymentType] = useState("deposit");
  const [method, setMethod] = useState("cash");

  const handleSubmit = (e) => {
    e.preventDefault();
    if (!amount) return;
    recordPayment.mutate(
      { id: order.id, data: { amount: Number(amount), payment_type: paymentType, payment_method: method } },
      { onSuccess: () => setAmount("") }
    );
  };

  return (
    <Card>
      <CardHeader className="pb-3"><CardTitle className="text-base">Payments</CardTitle></CardHeader>
      <CardContent className="space-y-3">
        <div className="grid grid-cols-2 gap-2 text-sm">
          <div><span className="text-muted-foreground">Total</span><p className="font-semibold">{Number(order.total_amount).toFixed(2)}</p></div>
          <div><span className="text-muted-foreground">Paid</span><p className="font-semibold text-green-700">{Number(order.paid_amount).toFixed(2)}</p></div>
          <div className="col-span-2">
            <span className="text-muted-foreground">Balance Due</span>
            <p className={`font-semibold ${order.balance_due > 0 ? "text-destructive" : "text-green-700"}`}>{Number(order.balance_due).toFixed(2)}</p>
          </div>
        </div>

        <div className="rounded-md border divide-y">
          {(order.payments ?? []).length === 0 && <p className="p-2 text-xs text-muted-foreground">No payments recorded yet.</p>}
          {(order.payments ?? []).map((p) => (
            <div key={p.id} className="flex justify-between p-2 text-sm">
              <span className="capitalize">{p.payment_type} · {p.payment_method}</span>
              <span className="font-medium">{Number(p.amount).toFixed(2)}</span>
            </div>
          ))}
        </div>

        {order.balance_due > 0 && (
          <form onSubmit={handleSubmit} className="space-y-2 pt-2 border-t">
            <NumberInput label="Amount" step="0.01" value={amount} onChange={(e) => setAmount(e.target.value)} />
            <div className="grid grid-cols-2 gap-2">
              <SelectInput label="Type" value={paymentType} onChange={setPaymentType} options={PAYMENT_TYPE_OPTIONS} />
              <SelectInput label="Method" value={method} onChange={setMethod} options={METHOD_OPTIONS} />
            </div>
            <Button type="submit" size="sm" className="w-full" disabled={recordPayment.isPending}>Record Payment</Button>
          </form>
        )}
      </CardContent>
    </Card>
  );
}
