"use client";

import { useState, useMemo } from "react";
import { useRouter } from "next/navigation";
import { useAllCustomers } from "@/hooks/useCustomers";
import { useAllAlterationTypes } from "@/hooks/useAlterationTypes";
import { useCreateOrder } from "@/hooks/useOrders";
import { PageHeader } from "@/components/shared/PageHeader";
import { OptionCard } from "@/components/shared/OptionCard";
import { SectionBox, SECTION_THEME as THEME } from "@/components/shared/SectionBox";
import { Button } from "@/components/ui/button";
import { TextInput, TextareaInput, NumberInput, ValidationError } from "@/components/forms/FormField";
import { SelectInput } from "@/components/forms/SelectInput";
import { GarmentItemCard } from "../orders/create/_components/GarmentItemCard";
import {
  Plus, Loader2, UserPlus, UserCheck, User, Scissors, ClipboardList,
  Wallet, Banknote, CreditCard, CheckCircle2,
} from "lucide-react";

const emptyItem = () => ({
  garment_type: "", fabric_source: "customer_provided", quantity: "1", unit_price: "", discount: "0",
  style_specifications: {}, materials: [],
});
const emptyNewCustomer = { name: "", mobile: "" };

const PAYMENT_METHOD_OPTIONS = [
  { value: "cash", label: "Cash" },
  { value: "card", label: "Card" },
  { value: "bank_transfer", label: "Bank Transfer" },
  { value: "online", label: "Online" },
];

export default function AlterationPage() {
  const router = useRouter();
  const { data: customers } = useAllCustomers();
  const { data: alterationTypes } = useAllAlterationTypes();
  const createOrder = useCreateOrder();

  const [customerMode, setCustomerMode] = useState("existing"); // "existing" | "new"
  const [customerId, setCustomerId] = useState("");
  const [newCustomer, setNewCustomer] = useState(emptyNewCustomer);
  const [expectedDeliveryDate, setExpectedDeliveryDate] = useState("");
  const [notes, setNotes] = useState("");
  const [items, setItems] = useState([emptyItem()]);

  const [collectPayment, setCollectPayment] = useState(false);
  const [paymentAmount, setPaymentAmount] = useState("");
  const [paymentMethod, setPaymentMethod] = useState("cash");

  const [errors, setErrors] = useState({});

  const customerOptions = (customers ?? []).map((c) => ({ value: String(c.id), label: `${c.name}${c.mobile ? ` (${c.mobile})` : ""}` }));

  const setItem = (index, updated) => setItems((p) => p.map((it, i) => (i === index ? updated : it)));
  const addItem = () => setItems((p) => [...p, emptyItem()]);
  const removeItem = (index) => setItems((p) => p.filter((_, i) => i !== index));

  const switchCustomerMode = (mode) => {
    setCustomerMode(mode);
    setCustomerId("");
    setNewCustomer(emptyNewCustomer);
  };

  const total = useMemo(
    () => items.reduce((sum, it) => sum + (Number(it.quantity) || 0) * (Number(it.unit_price) || 0) - (Number(it.discount) || 0), 0),
    [items]
  );

  const canSubmit = (customerMode === "existing" ? !!customerId : !!newCustomer.name) && items.every((it) => it.garment_type);

  const handleSubmit = async (e) => {
    e.preventDefault();
    setErrors({});

    const payload = {
      order_type: "alteration",
      expected_delivery_date: expectedDeliveryDate || null,
      discount_amount: 0,
      tax_amount: 0,
      notes: notes || null,
      items: items.map((it) => ({
        garment_type: it.garment_type,
        fabric_source: "customer_provided",
        quantity: Number(it.quantity) || 1,
        unit_price: Number(it.unit_price) || 0,
        discount: Number(it.discount) || 0,
        style_specifications: Object.keys(it.style_specifications ?? {}).length ? it.style_specifications : null,
        materials: [],
      })),
    };

    if (customerMode === "existing") {
      payload.customer_id = Number(customerId);
    } else {
      payload.new_customer = { name: newCustomer.name, mobile: newCustomer.mobile || null, address: null, email: null };
    }

    if (collectPayment && Number(paymentAmount) > 0) {
      payload.initial_payment = { amount: Number(paymentAmount), payment_method: paymentMethod, payment_type: "full" };
    }

    try {
      const res = await createOrder.mutateAsync(payload);
      router.push(`/orders/${res.data.data.id}`);
    } catch (err) {
      setErrors(err.response?.data?.errors ?? {});
    }
  };

  const balanceRemaining = total - (Number(paymentAmount) || 0);

  return (
    <div>
      <PageHeader title="Quick Alteration" description="Fast intake for a walk-in alteration — customer, item(s), price, done." />
      <form onSubmit={handleSubmit} className="space-y-2.5">
        <ValidationError errors={errors} />

        <div className="grid grid-cols-1 lg:grid-cols-2 gap-2.5 items-stretch">
          <SectionBox theme={THEME.people} icon={User} title="Customer" subtitle="Who is this alteration for">
            <div className="grid grid-cols-1 sm:grid-cols-2 gap-2">
              <OptionCard icon={UserCheck} title="Existing Customer" description="Pick from the customer list"
                selected={customerMode === "existing"} onClick={() => switchCustomerMode("existing")} />
              <OptionCard icon={UserPlus} title="New Customer" description="First time here"
                selected={customerMode === "new"} onClick={() => switchCustomerMode("new")} />
            </div>

            {customerMode === "existing" ? (
              <SelectInput label="Select Customer" value={customerId} onChange={setCustomerId} options={customerOptions}
                required error={errors.customer_id?.[0]} placeholder="Search or select customer" />
            ) : (
              <div className="grid grid-cols-2 gap-3">
                <TextInput label="Full Name" value={newCustomer.name}
                  onChange={(e) => setNewCustomer((p) => ({ ...p, name: e.target.value }))}
                  required error={errors["new_customer.name"]?.[0]} />
                <TextInput label="Mobile Number" value={newCustomer.mobile}
                  onChange={(e) => setNewCustomer((p) => ({ ...p, mobile: e.target.value }))}
                  error={errors["new_customer.mobile"]?.[0]} placeholder="Optional" />
              </div>
            )}
          </SectionBox>

          <SectionBox theme={THEME.basics} icon={ClipboardList} title="Additional Details" subtitle="Due date and notes for the tailor">
            <TextInput label="When does the customer need it?" type="date" value={expectedDeliveryDate}
              onChange={(e) => setExpectedDeliveryDate(e.target.value)} error={errors.expected_delivery_date?.[0]} />
            <TextareaInput label="Notes" value={notes} onChange={(e) => setNotes(e.target.value)} rows={2}
              placeholder="Anything the tailor should know about this order" />
          </SectionBox>
        </div>

        <div className="space-y-2.5">
          <div className="flex items-center justify-between gap-2">
            <div>
              <h2 className="text-base font-semibold flex items-center gap-2"><Scissors className="h-4 w-4 text-primary" />What needs altering?</h2>
              <p className="text-xs text-muted-foreground mt-0.5">Add one card for each item in this alteration.</p>
            </div>
            <Button type="button" variant="outline" onClick={addItem}><Plus className="h-4 w-4" />Add Another</Button>
          </div>
          {items.map((item, i) => (
            <GarmentItemCard key={i} item={item} index={i} alterationTypes={alterationTypes} orderType="alteration"
              onChange={setItem} onRemove={removeItem} canRemove={items.length > 1} />
          ))}
        </div>

        <div className="grid grid-cols-1 lg:grid-cols-2 gap-2.5 items-start">
          <SectionBox theme={THEME.money} icon={Wallet} title="Payment Now?" subtitle="Is the customer paying anything right now"
            className={collectPayment ? undefined : "lg:col-span-2"}>
            <div className="flex items-center justify-between rounded-lg bg-teal-100/70 border border-teal-200 px-3 py-2">
              <span className="text-xs font-semibold text-teal-800">Order Total</span>
              <span className="text-base font-bold text-teal-800 tabular-nums">{total.toFixed(2)}</span>
            </div>
            <div className="grid grid-cols-1 sm:grid-cols-2 gap-2">
              <OptionCard icon={Banknote} title="Yes, taking payment now" description="Record a deposit or full payment"
                selected={collectPayment} onClick={() => setCollectPayment(true)} />
              <OptionCard icon={Wallet} title="Not right now" description="Collect payment later instead"
                selected={!collectPayment} onClick={() => setCollectPayment(false)} />
            </div>
          </SectionBox>

          {collectPayment && (
            <SectionBox theme={THEME.fabric} icon={CreditCard} title="Take Payment Now" subtitle="How much, and how they're paying">
              <NumberInput label="Amount Being Paid" step="0.01" min="0.01" max={total || undefined} value={paymentAmount}
                onChange={(e) => setPaymentAmount(e.target.value)} error={errors["initial_payment.amount"]?.[0]} />
              <SelectInput label="Payment Method" value={paymentMethod} onChange={setPaymentMethod} options={PAYMENT_METHOD_OPTIONS} />
              <div className="flex items-center justify-between rounded-lg bg-orange-100/70 border border-orange-200 px-3 py-2">
                <span className="text-xs font-semibold text-orange-800">Balance Remaining</span>
                <span className="text-base font-bold text-orange-800 tabular-nums">{balanceRemaining.toFixed(2)}</span>
              </div>
            </SectionBox>
          )}
        </div>

        <div className="flex items-center justify-end gap-2 border-t pt-4">
          <Button type="button" variant="outline" size="lg" onClick={() => router.push("/orders")}>Cancel</Button>
          <Button type="submit" size="lg" variant="success" disabled={createOrder.isPending || !canSubmit}>
            {createOrder.isPending ? <><Loader2 className="h-4 w-4 animate-spin" />Creating Order…</> : <><CheckCircle2 className="h-4 w-4" />Create Alteration</>}
          </Button>
        </div>
      </form>
    </div>
  );
}
