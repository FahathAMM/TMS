"use client";

import { useState, useMemo } from "react";
import { useRouter } from "next/navigation";
import { useAllCustomers } from "@/hooks/useCustomers";
import { useAllProducts } from "@/hooks/useProducts";
import { useAllGarmentPrices } from "@/hooks/useGarmentPrices";
import { useCreateOrder } from "@/hooks/useOrders";
import { PageHeader } from "@/components/shared/PageHeader";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { TextInput, TextareaInput, NumberInput, ValidationError } from "@/components/forms/FormField";
import { SelectInput } from "@/components/forms/SelectInput";
import { MeasurementsCard } from "@/app/(dashboard)/customers/_components/MeasurementsCard";
import { GarmentItemCard } from "./_components/GarmentItemCard";
import { Plus, Loader2, UserPlus, UserCheck } from "lucide-react";

const emptyItem = () => ({
  garment_type: "", fabric_source: "in_house", quantity: "1", unit_price: "", discount: "0",
  style_specifications: {}, materials: [{ product_id: "", quantity_required: "" }],
});

const emptyNewCustomer = { name: "", mobile: "", address: "", email: "" };

const PAYMENT_METHOD_OPTIONS = [
  { value: "cash", label: "Cash" },
  { value: "card", label: "Card" },
  { value: "bank_transfer", label: "Bank Transfer" },
  { value: "online", label: "Online" },
];

export default function CreateOrderPage() {
  const router = useRouter();
  const { data: customers } = useAllCustomers();
  const { data: products } = useAllProducts();
  const { data: garmentPrices } = useAllGarmentPrices();
  const createOrder = useCreateOrder();

  const [customerMode, setCustomerMode] = useState("existing"); // "existing" | "new"
  const [customerId, setCustomerId] = useState("");
  const [newCustomer, setNewCustomer] = useState(emptyNewCustomer);

  const [orderType, setOrderType] = useState("custom_stitching");
  const [expectedDeliveryDate, setExpectedDeliveryDate] = useState("");
  const [discountAmount, setDiscountAmount] = useState("0");
  const [taxAmount, setTaxAmount] = useState("0");
  const [notes, setNotes] = useState("");
  const [items, setItems] = useState([emptyItem()]);

  const [collectPayment, setCollectPayment] = useState(false);
  const [initialPaymentAmount, setInitialPaymentAmount] = useState("");
  const [initialPaymentType, setInitialPaymentType] = useState("deposit");
  const [initialPaymentMethod, setInitialPaymentMethod] = useState("cash");

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

  const subtotal = useMemo(
    () => items.reduce((sum, it) => sum + (Number(it.quantity) || 0) * (Number(it.unit_price) || 0) - (Number(it.discount) || 0), 0),
    [items]
  );
  const total = subtotal - (Number(discountAmount) || 0) + (Number(taxAmount) || 0);
  const balanceAfterInitialPayment = total - (Number(initialPaymentAmount) || 0);

  const canSubmit = customerMode === "existing" ? !!customerId : !!newCustomer.name;

  const handleSubmit = async (e) => {
    e.preventDefault();
    setErrors({});

    const payload = {
      order_type: orderType,
      expected_delivery_date: expectedDeliveryDate || null,
      discount_amount: Number(discountAmount) || 0,
      tax_amount: Number(taxAmount) || 0,
      notes: notes || null,
      items: items.map((it) => ({
        garment_type: it.garment_type,
        fabric_source: it.fabric_source,
        quantity: Number(it.quantity) || 1,
        unit_price: Number(it.unit_price) || 0,
        discount: Number(it.discount) || 0,
        style_specifications: Object.keys(it.style_specifications ?? {}).length ? it.style_specifications : null,
        materials: it.fabric_source === "in_house"
          ? it.materials.filter((m) => m.product_id).map((m) => ({
              product_id: Number(m.product_id), quantity_required: Number(m.quantity_required) || 0,
            }))
          : [],
      })),
    };

    if (customerMode === "existing") {
      payload.customer_id = Number(customerId);
    } else {
      payload.new_customer = {
        name: newCustomer.name,
        mobile: newCustomer.mobile || null,
        address: newCustomer.address || null,
        email: newCustomer.email || null,
      };
    }

    if (collectPayment && Number(initialPaymentAmount) > 0) {
      payload.initial_payment = {
        amount: Number(initialPaymentAmount),
        payment_method: initialPaymentMethod,
        payment_type: initialPaymentType,
      };
    }

    try {
      const res = await createOrder.mutateAsync(payload);
      router.push(`/orders/${res.data.data.id}`);
    } catch (err) {
      setErrors(err.response?.data?.errors ?? {});
    }
  };

  return (
    <div>
      <PageHeader title="New Tailoring Order" description="Customer → measurements → garments → quote" />
      <form onSubmit={handleSubmit} className="space-y-6">
        <ValidationError errors={errors} />

        <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
          <div className="lg:col-span-2 space-y-6">
            <Card>
              <CardHeader className="pb-3"><CardTitle className="text-base">Customer & Order Details</CardTitle></CardHeader>
              <CardContent className="space-y-4">
                <div className="flex gap-2">
                  <Button type="button" size="sm" variant={customerMode === "existing" ? "default" : "outline"}
                    onClick={() => switchCustomerMode("existing")}>
                    <UserCheck className="h-3.5 w-3.5" />Existing Customer
                  </Button>
                  <Button type="button" size="sm" variant={customerMode === "new" ? "default" : "outline"}
                    onClick={() => switchCustomerMode("new")}>
                    <UserPlus className="h-3.5 w-3.5" />New Customer
                  </Button>
                </div>

                {customerMode === "existing" ? (
                  <SelectInput label="Customer" value={customerId} onChange={setCustomerId} options={customerOptions}
                    required error={errors.customer_id?.[0]} placeholder="Select customer" />
                ) : (
                  <div className="space-y-4 rounded-md border p-3 bg-muted/20">
                    <TextInput label="Full Name" value={newCustomer.name}
                      onChange={(e) => setNewCustomer((p) => ({ ...p, name: e.target.value }))}
                      required error={errors["new_customer.name"]?.[0]} />
                    <div className="grid grid-cols-2 gap-4">
                      <TextInput label="Mobile" value={newCustomer.mobile}
                        onChange={(e) => setNewCustomer((p) => ({ ...p, mobile: e.target.value }))}
                        error={errors["new_customer.mobile"]?.[0]} />
                      <TextInput label="Email" type="email" value={newCustomer.email}
                        onChange={(e) => setNewCustomer((p) => ({ ...p, email: e.target.value }))}
                        error={errors["new_customer.email"]?.[0]} />
                    </div>
                    <TextareaInput label="Address" value={newCustomer.address} rows={2}
                      onChange={(e) => setNewCustomer((p) => ({ ...p, address: e.target.value }))}
                      error={errors["new_customer.address"]?.[0]} />
                    <p className="text-xs text-muted-foreground">Measurements can be added once the order is saved.</p>
                  </div>
                )}

                <div className="grid grid-cols-2 gap-4">
                  <SelectInput label="Order Type" value={orderType} onChange={setOrderType}
                    options={[{ value: "custom_stitching", label: "Custom Stitching" }, { value: "alteration", label: "Alteration" }]} />
                  <TextInput label="Expected Delivery Date" type="date" value={expectedDeliveryDate}
                    onChange={(e) => setExpectedDeliveryDate(e.target.value)} error={errors.expected_delivery_date?.[0]} />
                </div>
                <TextareaInput label="Notes" value={notes} onChange={(e) => setNotes(e.target.value)} rows={2} />
              </CardContent>
            </Card>

            {customerMode === "existing" && customerId && <MeasurementsCard customerId={Number(customerId)} />}

            <div className="space-y-3">
              <div className="flex items-center justify-between">
                <h3 className="text-sm font-semibold">Garment Items</h3>
                <Button type="button" variant="outline" size="sm" onClick={addItem}><Plus className="h-3.5 w-3.5" />Add Garment</Button>
              </div>
              {items.map((item, i) => (
                <GarmentItemCard key={i} item={item} index={i} products={products} garmentPrices={garmentPrices}
                  onChange={setItem} onRemove={removeItem} canRemove={items.length > 1} />
              ))}
            </div>
          </div>

          <div className="space-y-4">
            <Card>
              <CardHeader className="pb-3"><CardTitle className="text-base">Quote</CardTitle></CardHeader>
              <CardContent className="space-y-3">
                <div className="flex justify-between text-sm"><span className="text-muted-foreground">Subtotal</span><span>{subtotal.toFixed(2)}</span></div>
                <NumberInput label="Order Discount" step="0.01" value={discountAmount} onChange={(e) => setDiscountAmount(e.target.value)} />
                <NumberInput label="Tax" step="0.01" value={taxAmount} onChange={(e) => setTaxAmount(e.target.value)} />
                <div className="flex justify-between text-base font-semibold border-t pt-3"><span>Total</span><span>{total.toFixed(2)}</span></div>
              </CardContent>
            </Card>

            <Card>
              <CardHeader className="pb-3">
                <CardTitle className="text-base flex items-center justify-between">
                  Collect Payment Now
                  <input type="checkbox" checked={collectPayment} onChange={(e) => setCollectPayment(e.target.checked)} className="h-4 w-4" />
                </CardTitle>
              </CardHeader>
              {collectPayment && (
                <CardContent className="space-y-3">
                  <NumberInput label="Amount" step="0.01" min="0.01" max={total || undefined} value={initialPaymentAmount}
                    onChange={(e) => setInitialPaymentAmount(e.target.value)} error={errors["initial_payment.amount"]?.[0]} />
                  <div className="grid grid-cols-2 gap-3">
                    <SelectInput label="Type" value={initialPaymentType} onChange={setInitialPaymentType}
                      options={[{ value: "deposit", label: "Deposit" }, { value: "full", label: "Full Payment" }]} />
                    <SelectInput label="Method" value={initialPaymentMethod} onChange={setInitialPaymentMethod}
                      options={PAYMENT_METHOD_OPTIONS} />
                  </div>
                  <p className="text-xs text-muted-foreground">
                    Balance remaining after this payment: <span className="font-medium">{balanceAfterInitialPayment.toFixed(2)}</span>
                  </p>
                </CardContent>
              )}
              {!collectPayment && (
                <CardContent>
                  <p className="text-xs text-muted-foreground">Optional — payment can also be collected later from the order detail page.</p>
                </CardContent>
              )}
            </Card>

            <div className="flex flex-col gap-2">
              <Button type="submit" disabled={createOrder.isPending || !canSubmit}>
                {createOrder.isPending ? <><Loader2 className="mr-2 h-4 w-4 animate-spin" />Creating…</> : "Create Order"}
              </Button>
              <Button type="button" variant="outline" onClick={() => router.push("/orders")}>Cancel</Button>
            </div>
          </div>
        </div>
      </form>
    </div>
  );
}
