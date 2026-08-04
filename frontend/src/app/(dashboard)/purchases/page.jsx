"use client";

import { useState } from "react";
import {
  usePurchases, usePurchase, useCreatePurchase, useReceivePurchase, useStorePurchasePayment,
} from "@/hooks/usePurchases";
import { useAllSuppliers } from "@/hooks/useSuppliers";
import { useAllProducts } from "@/hooks/useProducts";
import { useTableParams } from "@/hooks/useTableParams";
import { DataTable } from "@/components/shared/DataTable";
import { Pagination } from "@/components/shared/Pagination";
import { TableToolbar } from "@/components/shared/TableToolbar";
import { StatusBadge } from "@/components/shared/StatusBadge";
import { Button } from "@/components/ui/button";
import { Dialog, DialogContent, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { TextInput, NumberInput, ValidationError } from "@/components/forms/FormField";
import { SelectInput } from "@/components/forms/SelectInput";
import { useAuthStore } from "@/store/authStore";
import { Plus, Trash2, Eye, Loader2 } from "lucide-react";

const emptyLine = { product_id: "", quantity_ordered: "1", cost_price: "" };

function CreatePurchaseDialog({ open, onOpenChange }) {
  const { data: suppliers } = useAllSuppliers();
  const { data: products } = useAllProducts();
  const create = useCreatePurchase();
  const [supplierId, setSupplierId] = useState("");
  const [purchaseDate, setPurchaseDate] = useState(new Date().toISOString().slice(0, 10));
  const [lines, setLines] = useState([{ ...emptyLine }]);
  const [errors, setErrors] = useState({});

  const supplierOptions = (suppliers ?? []).map((s) => ({ value: String(s.id), label: s.name }));
  const productOptions = (products ?? []).map((p) => ({ value: String(p.id), label: `${p.name} (${p.sku})` }));

  const setLine = (i, field, value) => setLines((p) => p.map((l, idx) => (idx === i ? { ...l, [field]: value } : l)));
  const addLine = () => setLines((p) => [...p, { ...emptyLine }]);
  const removeLine = (i) => setLines((p) => p.filter((_, idx) => idx !== i));

  const total = lines.reduce((sum, l) => sum + (Number(l.quantity_ordered) || 0) * (Number(l.cost_price) || 0), 0);

  const handleSubmit = async (e) => {
    e.preventDefault();
    setErrors({});
    const payload = {
      supplier_id: Number(supplierId),
      purchase_date: purchaseDate,
      items: lines
        .filter((l) => l.product_id)
        .map((l) => ({
          product_id: Number(l.product_id),
          quantity_ordered: Number(l.quantity_ordered) || 0,
          cost_price: Number(l.cost_price) || 0,
        })),
    };
    try {
      await create.mutateAsync(payload);
      onOpenChange(false);
      setSupplierId(""); setLines([{ ...emptyLine }]);
    } catch (err) {
      setErrors(err.response?.data?.errors ?? {});
    }
  };

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className="max-w-2xl max-h-[85vh] overflow-y-auto">
        <DialogHeader><DialogTitle>New Purchase Order</DialogTitle></DialogHeader>
        <form onSubmit={handleSubmit} className="space-y-4">
          <ValidationError errors={errors} />
          <div className="grid grid-cols-2 gap-4">
            <SelectInput label="Supplier" value={supplierId} onChange={setSupplierId} options={supplierOptions} required error={errors.supplier_id?.[0]} />
            <TextInput label="Purchase Date" type="date" value={purchaseDate} onChange={(e) => setPurchaseDate(e.target.value)} required error={errors.purchase_date?.[0]} />
          </div>

          <div className="space-y-2">
            <p className="text-sm font-medium">Line Items</p>
            {lines.map((line, i) => (
              <div key={i} className="flex gap-2 items-start">
                <SelectInput className="flex-1" value={line.product_id} onChange={(v) => setLine(i, "product_id", v)}
                  options={productOptions} placeholder="Select fabric/trim" />
                <NumberInput className="w-24" placeholder="Qty" step="0.01" value={line.quantity_ordered}
                  onChange={(e) => setLine(i, "quantity_ordered", e.target.value)} />
                <NumberInput className="w-28" placeholder="Cost" step="0.01" value={line.cost_price}
                  onChange={(e) => setLine(i, "cost_price", e.target.value)} />
                <Button type="button" variant="ghost" size="icon" className="mt-0.5" onClick={() => removeLine(i)} disabled={lines.length === 1}>
                  <Trash2 className="h-4 w-4 text-destructive" />
                </Button>
              </div>
            ))}
            <Button type="button" variant="outline" size="sm" onClick={addLine}><Plus className="h-3.5 w-3.5" />Add Line</Button>
            {errors.items && <p className="text-xs text-destructive">{errors.items[0]}</p>}
          </div>

          <div className="text-right text-sm font-medium">Estimated Total: {total.toFixed(2)}</div>

          <div className="flex justify-end gap-2">
            <Button type="button" variant="outline" onClick={() => onOpenChange(false)}>Cancel</Button>
            <Button type="submit" disabled={create.isPending}>Create Purchase Order</Button>
          </div>
        </form>
      </DialogContent>
    </Dialog>
  );
}

function PurchaseDetailDialog({ purchaseId, onOpenChange }) {
  const { data: purchase, isLoading } = usePurchase(purchaseId);
  const receive = useReceivePurchase();
  const storePayment = useStorePurchasePayment();
  const [receiveQty, setReceiveQty] = useState({});
  const [payAmount, setPayAmount] = useState("");
  const [payMethod, setPayMethod] = useState("cash");

  if (isLoading || !purchase) {
    return (
      <Dialog open={!!purchaseId} onOpenChange={onOpenChange}>
        <DialogContent className="max-w-2xl">
          <div className="flex justify-center py-12"><Loader2 className="h-6 w-6 animate-spin text-muted-foreground" /></div>
        </DialogContent>
      </Dialog>
    );
  }

  const handleReceive = async () => {
    const items = Object.entries(receiveQty)
      .filter(([, qty]) => Number(qty) > 0)
      .map(([purchase_item_id, qty]) => ({ purchase_item_id: Number(purchase_item_id), quantity_received: Number(qty) }));
    if (items.length === 0) return;
    await receive.mutateAsync({ id: purchase.id, data: { items } });
    setReceiveQty({});
  };

  const handlePay = async (e) => {
    e.preventDefault();
    if (!payAmount) return;
    await storePayment.mutateAsync({
      id: purchase.id,
      data: { amount: Number(payAmount), payment_method: payMethod, payment_date: new Date().toISOString().slice(0, 10) },
    });
    setPayAmount("");
  };

  return (
    <Dialog open={!!purchaseId} onOpenChange={onOpenChange}>
      <DialogContent className="max-w-2xl max-h-[85vh] overflow-y-auto">
        <DialogHeader><DialogTitle>{purchase.reference_number}</DialogTitle></DialogHeader>
        <div className="space-y-5">
          <div className="flex flex-wrap gap-4 text-sm">
            <span><span className="text-muted-foreground">Supplier:</span> {purchase.supplier?.name}</span>
            <span><StatusBadge status={purchase.status} /></span>
            <span><StatusBadge status={purchase.payment_status} /></span>
          </div>

          <div>
            <p className="text-sm font-medium mb-2">Items</p>
            <div className="rounded-md border divide-y">
              {purchase.items.map((item) => (
                <div key={item.id} className="flex items-center justify-between p-2 text-sm">
                  <div>
                    <p className="font-medium">{item.product_name}</p>
                    <p className="text-xs text-muted-foreground">
                      Ordered {item.quantity_ordered} · Received {item.quantity_received} · Remaining {item.remaining_qty}
                    </p>
                  </div>
                  {!item.is_fully_received && (
                    <NumberInput className="w-24" placeholder="Qty" step="0.01"
                      value={receiveQty[item.id] ?? ""}
                      onChange={(e) => setReceiveQty((p) => ({ ...p, [item.id]: e.target.value }))} />
                  )}
                </div>
              ))}
            </div>
            {purchase.status !== "received" && (
              <Button size="sm" className="mt-2" onClick={handleReceive} disabled={receive.isPending}>Receive Items</Button>
            )}
          </div>

          <div>
            <p className="text-sm font-medium mb-2">Payments (Due: {Number(purchase.due_amount).toFixed(2)})</p>
            <div className="rounded-md border divide-y mb-2">
              {(purchase.payments ?? []).length === 0 && <p className="p-2 text-xs text-muted-foreground">No payments yet.</p>}
              {(purchase.payments ?? []).map((p) => (
                <div key={p.id} className="flex justify-between p-2 text-sm">
                  <span>{p.payment_method}</span>
                  <span className="font-medium">{Number(p.amount).toFixed(2)}</span>
                </div>
              ))}
            </div>
            {Number(purchase.due_amount) > 0 && (
              <form onSubmit={handlePay} className="flex gap-2">
                <NumberInput className="w-28" placeholder="Amount" step="0.01" value={payAmount} onChange={(e) => setPayAmount(e.target.value)} />
                <SelectInput className="w-40" value={payMethod} onChange={setPayMethod}
                  options={[{ value: "cash", label: "Cash" }, { value: "bank_transfer", label: "Bank Transfer" }, { value: "cheque", label: "Cheque" }, { value: "card", label: "Card" }]} />
                <Button type="submit" size="sm" disabled={storePayment.isPending}>Pay</Button>
              </form>
            )}
          </div>
        </div>
      </DialogContent>
    </Dialog>
  );
}

export default function PurchasesPage() {
  const { can } = useAuthStore();
  const { search, page, perPage, setSearch, setPage, setPerPage } = useTableParams();
  const [createOpen, setCreateOpen] = useState(false);
  const [detailId, setDetailId] = useState(null);

  const { data, isLoading } = usePurchases({ page, search, per_page: perPage });

  const columns = [
    { key: "reference_number", header: "Reference", render: (r) => <span className="font-mono font-medium">{r.reference_number}</span> },
    { key: "supplier", header: "Supplier", render: (r) => r.supplier?.name },
    { key: "status", header: "Status", render: (r) => <StatusBadge status={r.status} /> },
    { key: "payment_status", header: "Payment", render: (r) => <StatusBadge status={r.payment_status} /> },
    { key: "total_amount", header: "Total", render: (r) => Number(r.total_amount).toFixed(2) },
    { key: "due_amount", header: "Due", render: (r) => (
      <span className={Number(r.due_amount) > 0 ? "text-destructive font-medium" : ""}>{Number(r.due_amount).toFixed(2)}</span>
    ) },
    {
      key: "actions", header: "Actions", render: (r) => (
        <Button variant="ghost" size="icon" onClick={() => setDetailId(r.id)}><Eye className="h-4 w-4" /></Button>
      ),
    },
  ];

  return (
    <div>
      <TableToolbar
        search={search} onSearchChange={(v) => { setSearch(v); setPage(1); }}
        placeholder="Search purchase orders..."
        action={can("create purchases") && <Button size="sm" onClick={() => setCreateOpen(true)}><Plus />New Purchase Order</Button>}
      />
      <DataTable columns={columns} data={data?.data ?? []} loading={isLoading} search={search} />
      <Pagination meta={data?.meta} onPageChange={setPage} perPage={perPage} onPerPageChange={setPerPage} />

      <CreatePurchaseDialog open={createOpen} onOpenChange={setCreateOpen} />
      {detailId && <PurchaseDetailDialog purchaseId={detailId} onOpenChange={() => setDetailId(null)} />}
    </div>
  );
}
