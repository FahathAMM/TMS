"use client";

import { useState } from "react";
import { useRecordOrderPayment } from "@/hooks/useOrders";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { SectionBox, SECTION_THEME as THEME } from "@/components/shared/SectionBox";
import { NumberInput } from "@/components/forms/FormField";
import { SelectInput } from "@/components/forms/SelectInput";
import { Wallet, Banknote, CreditCard } from "lucide-react";

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
      <CardHeader className="pb-3"><CardTitle className="text-base flex items-center gap-2"><Wallet className="h-5 w-5 text-primary" />Payments</CardTitle></CardHeader>
      <CardContent className="space-y-3">
        <div className="grid grid-cols-3 gap-2 text-center">
          <div className="rounded-lg bg-primary/10 p-2.5">
            <Wallet className="h-4 w-4 mx-auto text-primary mb-1" />
            <p className="text-[11px] text-muted-foreground">Total</p>
            <p className="text-sm font-bold">{Number(order.total_amount).toFixed(2)}</p>
          </div>
          <div className="rounded-lg bg-green-50 p-2.5">
            <Banknote className="h-4 w-4 mx-auto text-green-700 mb-1" />
            <p className="text-[11px] text-muted-foreground">Paid</p>
            <p className="text-sm font-bold text-green-700">{Number(order.paid_amount).toFixed(2)}</p>
          </div>
          <div className="rounded-lg bg-orange-50 p-2.5">
            <CreditCard className="h-4 w-4 mx-auto text-orange-700 mb-1" />
            <p className="text-[11px] text-muted-foreground">Balance Due</p>
            <p className={`text-sm font-bold ${order.balance_due > 0 ? "text-orange-700" : "text-green-700"}`}>
              {Number(order.balance_due).toFixed(2)}
            </p>
          </div>
        </div>

        <div className="rounded-md border divide-y">
          {(order.payments ?? []).length === 0 && <p className="p-2 text-xs text-muted-foreground">No payments recorded yet.</p>}
          {(order.payments ?? []).map((p) => (
            <div key={p.id} className="flex items-center justify-between gap-2 p-2 text-sm">
              <span className="flex items-center gap-2">
                <span className="flex h-6 w-6 items-center justify-center rounded-full bg-green-100 text-green-700 shrink-0">
                  <Banknote className="h-3.5 w-3.5" />
                </span>
                <span className="capitalize text-xs">{p.payment_type} · {p.payment_method?.replace("_", " ")}</span>
              </span>
              <span className="font-semibold text-green-700">{Number(p.amount).toFixed(2)}</span>
            </div>
          ))}
        </div>

        {order.balance_due > 0 && (
          <form onSubmit={handleSubmit}>
            <SectionBox theme={THEME.money} icon={Banknote} title="Record a Payment" subtitle="Collect cash, card, or transfer from the customer">
              <NumberInput label="Amount" step="0.01" value={amount} onChange={(e) => setAmount(e.target.value)} />
              <div className="grid grid-cols-2 gap-2">
                <SelectInput label="Type" value={paymentType} onChange={setPaymentType} options={PAYMENT_TYPE_OPTIONS} />
                <SelectInput label="Method" value={method} onChange={setMethod} options={METHOD_OPTIONS} />
              </div>
              <Button type="submit" variant="success" className="w-full" disabled={recordPayment.isPending || !amount}>
                Record Payment
              </Button>
            </SectionBox>
          </form>
        )}
      </CardContent>
    </Card>
  );
}
