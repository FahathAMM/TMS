"use client";

import { useState, useMemo, useEffect } from "react";
import { useRouter } from "next/navigation";
import { toast } from "sonner";
import { useAllCustomers } from "@/hooks/useCustomers";
import { useAllProducts } from "@/hooks/useProducts";
import { useAllGarmentPrices } from "@/hooks/useGarmentPrices";
import { useAllAlterationTypes } from "@/hooks/useAlterationTypes";
import { useMeasurementTypes } from "@/hooks/useMeasurementTypes";
import { useCreateOrder } from "@/hooks/useOrders";
import { Stepper } from "@/components/shared/Stepper";
import { OptionCard } from "@/components/shared/OptionCard";
import { SectionBox, SECTION_THEME as THEME } from "@/components/shared/SectionBox";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { TextInput, TextareaInput, NumberInput, ValidationError } from "@/components/forms/FormField";
import { SelectInput } from "@/components/forms/SelectInput";
import { GarmentItemCard } from "./_components/GarmentItemCard";
import { OrderReviewStep } from "./_components/OrderReviewStep";
import { customerMeasurementService } from "@/services/customerMeasurementService";
import {
  Plus, Loader2, UserPlus, UserCheck, ChevronLeft, ChevronRight, User, Shirt, Scissors,
  ClipboardList, Wallet, CheckCircle2, Banknote, CreditCard, AlertCircle,
} from "lucide-react";

const STEPS = [
  { key: "customer", label: "Customer", icon: User },
  { key: "order_type", label: "Order Type", icon: Shirt },
  { key: "dress", label: "Dress Details", icon: Scissors },
  { key: "payment", label: "Payment", icon: Wallet },
  { key: "review", label: "Review & Confirm", icon: CheckCircle2 },
];

const emptyItem = (orderType) => (
  orderType === "alteration"
    ? { garment_type: "", fabric_source: "customer_provided", quantity: "1", unit_price: "", discount: "0",
        style_specifications: {}, materials: [] }
    : { garment_type: "", fabric_source: "in_house", quantity: "1", unit_price: "", discount: "0",
        style_specifications: [], materials: [{ product_id: "", quantity_required: "" }],
        measurement_type_id: "", measurements: [] }
);

const emptyNewCustomer = { name: "", mobile: "", address: "", email: "" };

// Custom-stitching items edit style_specifications as a list of {key, value}
// rows (so two blank rows can coexist while typing); alterations keep it as
// a plain { notes } object. Collapse either into the object the API expects.
const buildStyleSpecifications = (styleSpecifications) => {
  if (Array.isArray(styleSpecifications)) {
    const obj = Object.fromEntries(
      styleSpecifications.filter((entry) => entry.key.trim() !== "").map((entry) => [entry.key, entry.value])
    );
    return Object.keys(obj).length ? obj : null;
  }
  return styleSpecifications && Object.keys(styleSpecifications).length ? styleSpecifications : null;
};

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
  const { data: alterationTypes } = useAllAlterationTypes();
  const { data: measurementTypes } = useMeasurementTypes();
  const createOrder = useCreateOrder();

  const [step, setStep] = useState(0);
  const [maxReached, setMaxReached] = useState(0);
  // "Confirm & Create Order" sits in the exact same spot as the "Next" button
  // on every other step. A fast double-click to advance from Payment lands
  // its second click on the freshly-rendered Confirm button and submits the
  // order before the user ever sees the review step — this briefly disables
  // it right after arriving so that can't happen.
  const [confirmReady, setConfirmReady] = useState(false);

  const [customerMode, setCustomerMode] = useState("existing"); // "existing" | "new"
  const [customerId, setCustomerId] = useState("");
  const [newCustomer, setNewCustomer] = useState(emptyNewCustomer);

  const [orderType, setOrderType] = useState("custom_stitching");
  const [expectedDeliveryDate, setExpectedDeliveryDate] = useState("");
  const [discountAmount, setDiscountAmount] = useState("0");
  const [taxAmount, setTaxAmount] = useState("0");
  const [notes, setNotes] = useState("");
  const [items, setItems] = useState([emptyItem("custom_stitching")]);

  const [collectPayment, setCollectPayment] = useState(false);
  const [initialPaymentAmount, setInitialPaymentAmount] = useState("");
  const [initialPaymentType, setInitialPaymentType] = useState("deposit");
  const [initialPaymentMethod, setInitialPaymentMethod] = useState("cash");

  const [errors, setErrors] = useState({});
  const [stepError, setStepError] = useState("");

  useEffect(() => {
    setStepError("");
  }, [step]);

  useEffect(() => {
    if (step !== STEPS.length - 1) {
      setConfirmReady(false);
      return;
    }
    const timer = setTimeout(() => setConfirmReady(true), 400);
    return () => clearTimeout(timer);
  }, [step]);

  const customerOptions = (customers ?? []).map((c) => ({ value: String(c.id), label: `${c.name}${c.mobile ? ` (${c.mobile})` : ""}` }));
  const selectedCustomerName = (customers ?? []).find((c) => String(c.id) === String(customerId))?.name;

  const setItem = (index, updated) => setItems((p) => p.map((it, i) => (i === index ? updated : it)));
  const addItem = () => setItems((p) => [...p, emptyItem(orderType)]);
  const removeItem = (index) => setItems((p) => p.filter((_, i) => i !== index));

  const switchCustomerMode = (mode) => {
    setCustomerMode(mode);
    setCustomerId("");
    setNewCustomer(emptyNewCustomer);
  };

  // Alteration items have a different shape (no fabric source/materials) —
  // switch garment lines back to a single blank item for the new mode.
  const switchOrderType = (type) => {
    setOrderType(type);
    setItems([emptyItem(type)]);
  };

  const subtotal = useMemo(
    () => items.reduce((sum, it) => sum + (Number(it.quantity) || 0) * (Number(it.unit_price) || 0) - (Number(it.discount) || 0), 0),
    [items]
  );
  const total = subtotal - (Number(discountAmount) || 0) + (Number(taxAmount) || 0);
  const balanceAfterInitialPayment = total - (Number(initialPaymentAmount) || 0);

  const canSubmit = customerMode === "existing" ? !!customerId : !!newCustomer.name;

  // Returns a plain-language error message if the given step isn't complete
  // yet, or null if it's fine to move on.
  const validateStep = (s) => {
    if (s === 0) {
      if (customerMode === "existing") {
        if (!customerId) return "Please select a customer before continuing.";
      } else if (!newCustomer.name.trim()) {
        return "Please enter the customer's full name before continuing.";
      }
      return null;
    }

    if (s === 2) {
      if (items.length === 0) return "Please add at least one dress before continuing.";
      const label = orderType === "alteration" ? "alteration type" : "dress type";
      for (let i = 0; i < items.length; i++) {
        const it = items[i];
        if (!it.garment_type || !it.garment_type.trim()) {
          return `Please choose the ${label} for Dress #${i + 1}.`;
        }
        if (!it.quantity || Number(it.quantity) < 1) {
          return `Please enter a valid quantity for Dress #${i + 1}.`;
        }
        if (it.unit_price === "" || it.unit_price === null || Number(it.unit_price) <= 0) {
          return `Please enter a price for Dress #${i + 1}.`;
        }
      }
      return null;
    }

    if (s === 3 && collectPayment) {
      const amount = Number(initialPaymentAmount);
      if (!initialPaymentAmount || amount <= 0) {
        return 'Please enter how much the customer is paying now, or choose "Not right now".';
      }
      if (amount > total) {
        return "The payment amount can't be more than the order total.";
      }
    }

    return null;
  };

  // Clear the error banner as soon as the user actually fixes the problem,
  // instead of leaving a stale "you're missing X" message on screen.
  useEffect(() => {
    if (stepError && !validateStep(step)) setStepError("");
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [customerMode, customerId, newCustomer, items, collectPayment, initialPaymentAmount, orderType]);

  const goToStep = (i) => {
    setStep(i);
    setMaxReached((p) => Math.max(p, i));
  };
  const goNext = () => {
    const error = validateStep(step);
    if (error) {
      setStepError(error);
      return;
    }
    goToStep(Math.min(step + 1, STEPS.length - 1));
  };
  const goBack = () => goToStep(Math.max(step - 1, 0));

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
        style_specifications: buildStyleSpecifications(it.style_specifications),
        materials: it.fabric_source === "in_house"
          ? it.materials.filter((m) => m.product_id).map((m) => ({
              product_id: Number(m.product_id), quantity_required: Number(m.quantity_required) || 0,
            }))
          : [],
        measurement_type_id: it.measurement_type_id ? Number(it.measurement_type_id) : null,
        measurements: (it.measurements ?? [])
          .filter((m) => m.value !== "" && m.value !== null && m.value !== undefined)
          .map((m) => ({ measurement_field_id: m.measurement_field_id, value: Number(m.value) })),
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
      const order = res.data.data;

      // The measurements entered for each dress are also useful as the
      // customer's standard body measurements for next time — push the
      // latest value per point to their profile (works for a brand-new
      // customer too, since `order.customer.id` only exists after creation).
      const profileMeasurements = new Map();
      items.forEach((it) => {
        (it.measurements ?? []).forEach((m) => {
          if (m.value !== "" && m.value !== null && m.value !== undefined) {
            profileMeasurements.set(m.measurement_field_id, Number(m.value));
          }
        });
      });

      if (profileMeasurements.size > 0 && order.customer?.id) {
        const measurements = Array.from(profileMeasurements, ([measurement_field_id, value]) => ({ measurement_field_id, value }));
        try {
          await customerMeasurementService.update(order.customer.id, { measurements });
        } catch {
          toast.error("Order created, but saving the customer's measurements failed — add them from the customer profile.");
        }
      }

      router.push(`/orders/${order.id}`);
    } catch (err) {
      setErrors(err.response?.data?.errors ?? {});
    }
  };

  return (
    <div className="flex flex-col h-full min-h-0">
      <Stepper steps={STEPS} current={step} maxReached={maxReached} onStepClick={goToStep} className="mb-2 shrink-0" />
      <p className="text-xs text-muted-foreground mb-4 shrink-0">
        Step {step + 1} of {STEPS.length} — <span className="font-medium text-foreground">{STEPS[step].label}</span>
      </p>

      <form onSubmit={handleSubmit} className="flex-1 flex flex-col min-h-0">
        <div className="flex-1 overflow-y-auto min-h-0 pb-4">
          <ValidationError errors={errors} />

          {/* Step 1 — Customer */}
          {step === 0 && (
            <div className="space-y-4 max-w-3xl">
              <div>
                <h2 className="text-base font-semibold flex items-center gap-2"><User className="h-4 w-4 text-primary" />Who is this order for?</h2>
                <p className="text-xs text-muted-foreground mt-0.5">Pick an existing customer, or add a new one.</p>
              </div>

              <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <OptionCard icon={UserCheck} title="Existing Customer" description="Search from our customer list"
                  selected={customerMode === "existing"} onClick={() => switchCustomerMode("existing")} />
                <OptionCard icon={UserPlus} title="New Customer" description="First time here — add their details"
                  selected={customerMode === "new"} onClick={() => switchCustomerMode("new")} />
              </div>

              <Card>
                <CardContent className="pt-6">
                  {customerMode === "existing" ? (
                    <SelectInput label="Select Customer" value={customerId} onChange={setCustomerId} options={customerOptions}
                      required error={errors.customer_id?.[0]} placeholder="Search or select customer" />
                  ) : (
                    <div className="space-y-4">
                      <TextInput label="Full Name" value={newCustomer.name}
                        onChange={(e) => setNewCustomer((p) => ({ ...p, name: e.target.value }))}
                        required error={errors["new_customer.name"]?.[0]} placeholder="Customer's full name" />
                      <div className="grid grid-cols-2 gap-4">
                        <TextInput label="Mobile Number" value={newCustomer.mobile}
                          onChange={(e) => setNewCustomer((p) => ({ ...p, mobile: e.target.value }))}
                          error={errors["new_customer.mobile"]?.[0]} placeholder="Optional" />
                        <TextInput label="Email" type="email" value={newCustomer.email}
                          onChange={(e) => setNewCustomer((p) => ({ ...p, email: e.target.value }))}
                          error={errors["new_customer.email"]?.[0]} placeholder="Optional" />
                      </div>
                      <TextareaInput label="Address" value={newCustomer.address} rows={2}
                        onChange={(e) => setNewCustomer((p) => ({ ...p, address: e.target.value }))}
                        error={errors["new_customer.address"]?.[0]} placeholder="Optional" />
                    </div>
                  )}
                </CardContent>
              </Card>
            </div>
          )}

          {/* Step 2 — Order Type */}
          {step === 1 && (
            <div className="space-y-4 max-w-3xl">
              <div>
                <h2 className="text-base font-semibold flex items-center gap-2"><Shirt className="h-4 w-4 text-primary" />What kind of order is this?</h2>
                <p className="text-xs text-muted-foreground mt-0.5">Choose one — this decides what information we&apos;ll ask for next.</p>
              </div>

              <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <OptionCard icon={Shirt} title="Custom Stitching" description="Make a brand-new dress from measurements"
                  selected={orderType === "custom_stitching"} onClick={() => switchOrderType("custom_stitching")} />
                <OptionCard icon={Scissors} title="Alteration" description="Fix or adjust a dress the customer already has"
                  selected={orderType === "alteration"} onClick={() => switchOrderType("alteration")} />
              </div>
            </div>
          )}

          {/* Step 3 — Dress Details */}
          {step === 2 && (
            <div className="space-y-3">
              <div className="flex items-center justify-between gap-2">
                <div>
                  <h2 className="text-base font-semibold flex items-center gap-2">
                    {orderType === "alteration" ? <Scissors className="h-4 w-4 text-primary" /> : <Shirt className="h-4 w-4 text-primary" />}
                    {orderType === "alteration" ? "What needs altering?" : "What dresses are we making?"}
                  </h2>
                  <p className="text-xs text-muted-foreground mt-0.5">Add one card for each dress in this order.</p>
                </div>
                <Button type="button" variant="outline" onClick={addItem}>
                  <Plus className="h-4 w-4" />{orderType === "alteration" ? "Add Another" : "Add Dress"}
                </Button>
              </div>
              {items.map((item, i) => (
                <GarmentItemCard key={i} item={item} index={i} products={products} garmentPrices={garmentPrices}
                  alterationTypes={alterationTypes} orderType={orderType}
                  onChange={setItem} onRemove={removeItem} canRemove={items.length > 1} />
              ))}
            </div>
          )}

          {/* Step 4 — Payment */}
          {step === 3 && (
            <div className="space-y-2.5">
              <div>
                <h2 className="text-base font-semibold flex items-center gap-2"><Wallet className="h-4 w-4 text-primary" />How much, and how is it being paid?</h2>
                <p className="text-xs text-muted-foreground mt-0.5">Taking payment now is optional — you can also collect it later.</p>
              </div>

              <div className="grid grid-cols-1 lg:grid-cols-2 gap-2.5 items-stretch">
                <SectionBox theme={THEME.pricing} icon={Wallet} title="Order Total" subtitle="Add any extra discount or tax">
                  <div className="flex-1 flex flex-col justify-between gap-3">
                    <div className="space-y-3">
                      <div className="flex justify-between text-sm"><span className="text-muted-foreground">Items Subtotal</span><span className="tabular-nums">{subtotal.toFixed(2)}</span></div>
                      <div className="grid grid-cols-2 gap-3">
                        <NumberInput label="Extra Discount" step="0.01" value={discountAmount} onChange={(e) => setDiscountAmount(e.target.value)} />
                        <NumberInput label="Tax" step="0.01" value={taxAmount} onChange={(e) => setTaxAmount(e.target.value)} />
                      </div>
                    </div>
                    <div className="flex items-center justify-between rounded-lg bg-emerald-100/70 border border-emerald-200 px-3 py-2">
                      <span className="text-xs font-semibold text-emerald-800">Order Total</span>
                      <span className="text-base font-bold text-emerald-800 tabular-nums">{total.toFixed(2)}</span>
                    </div>
                  </div>
                </SectionBox>

                <SectionBox theme={THEME.basics} icon={ClipboardList} title="Additional Details" subtitle="Delivery date and notes for the tailor">
                  <TextInput label="When does the customer need it?" type="date" value={expectedDeliveryDate}
                    onChange={(e) => setExpectedDeliveryDate(e.target.value)} error={errors.expected_delivery_date?.[0]} />
                  <TextareaInput label="Notes" value={notes} onChange={(e) => setNotes(e.target.value)} rows={2}
                    placeholder="Anything the tailor should know about this order" />
                </SectionBox>
              </div>

              <div className="grid grid-cols-1 lg:grid-cols-2 gap-2.5 items-start">
                <SectionBox theme={THEME.money} icon={Banknote} title="Payment Now?" subtitle="Is the customer paying anything right now"
                  className={collectPayment ? undefined : "lg:col-span-2"}>
                  <div className="grid grid-cols-1 sm:grid-cols-2 gap-2">
                    <OptionCard icon={Banknote} title="Yes, taking payment now" description="Record a deposit or full payment"
                      selected={collectPayment} onClick={() => setCollectPayment(true)} />
                    <OptionCard icon={Wallet} title="Not right now" description="Collect payment later instead"
                      selected={!collectPayment} onClick={() => setCollectPayment(false)} />
                  </div>
                </SectionBox>

                {collectPayment && (
                  <SectionBox theme={THEME.fabric} icon={CreditCard} title="Take Payment Now" subtitle="How much, and how they're paying">
                    <NumberInput label="Amount Being Paid" step="0.01" min="0.01" max={total || undefined} value={initialPaymentAmount}
                      onChange={(e) => setInitialPaymentAmount(e.target.value)} error={errors["initial_payment.amount"]?.[0]} />
                    <div className="grid grid-cols-2 gap-3">
                      <SelectInput label="Payment Type" value={initialPaymentType} onChange={setInitialPaymentType}
                        options={[{ value: "deposit", label: "Deposit (partial)" }, { value: "full", label: "Full Payment" }]} />
                      <SelectInput label="Payment Method" value={initialPaymentMethod} onChange={setInitialPaymentMethod}
                        options={PAYMENT_METHOD_OPTIONS} />
                    </div>
                    <div className="flex items-center justify-between rounded-lg bg-orange-100/70 border border-orange-200 px-3 py-2">
                      <span className="text-xs font-semibold text-orange-800">Balance Remaining</span>
                      <span className="text-base font-bold text-orange-800 tabular-nums">{balanceAfterInitialPayment.toFixed(2)}</span>
                    </div>
                  </SectionBox>
                )}
              </div>
            </div>
          )}

          {/* Step 5 — Review & Confirm */}
          {step === 4 && (
            <OrderReviewStep
              customerMode={customerMode} customerName={selectedCustomerName} newCustomer={newCustomer}
              orderType={orderType} items={items} measurementTypes={measurementTypes}
              expectedDeliveryDate={expectedDeliveryDate} notes={notes}
              subtotal={subtotal} discountAmount={discountAmount} taxAmount={taxAmount} total={total}
              collectPayment={collectPayment} initialPaymentAmount={initialPaymentAmount}
              initialPaymentType={initialPaymentType} initialPaymentMethod={initialPaymentMethod}
              balanceAfterInitialPayment={balanceAfterInitialPayment}
            />
          )}
        </div>

        {stepError && (
          <div className="flex items-center gap-2 rounded-lg bg-destructive/10 text-destructive px-4 py-2.5 text-sm font-medium mb-3 shrink-0">
            <AlertCircle className="h-4 w-4 shrink-0" />
            {stepError}
          </div>
        )}

        <div className="flex items-center justify-between border-t pt-4 shrink-0">
          <Button type="button" variant="outline" size="lg" onClick={() => (step === 0 ? router.push("/orders") : goBack())}>
            {step === 0 ? "Cancel" : <><ChevronLeft className="h-4 w-4" />Back</>}
          </Button>

          {step < STEPS.length - 1 ? (
            <Button type="button" size="lg" onClick={goNext}>
              Next<ChevronRight className="h-4 w-4" />
            </Button>
          ) : (
            <Button type="submit" size="lg" variant="success" disabled={createOrder.isPending || !canSubmit || !confirmReady}>
              {createOrder.isPending ? <><Loader2 className="h-4 w-4 animate-spin" />Creating Order…</> : <><CheckCircle2 className="h-4 w-4" />Confirm & Create Order</>}
            </Button>
          )}
        </div>
      </form>
    </div>
  );
}
