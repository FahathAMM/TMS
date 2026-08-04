"use client";

import { useState } from "react";
import { useGarmentPrices, useCreateGarmentPrice, useUpdateGarmentPrice, useDeleteGarmentPrice } from "@/hooks/useGarmentPrices";
import { useTableParams } from "@/hooks/useTableParams";
import { DataTable } from "@/components/shared/DataTable";
import { Pagination } from "@/components/shared/Pagination";
import { TableToolbar } from "@/components/shared/TableToolbar";
import { ConfirmDialog } from "@/components/shared/ConfirmDialog";
import { StatusBadge } from "@/components/shared/StatusBadge";
import { Button } from "@/components/ui/button";
import { Dialog, DialogContent, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { NumberInput, TextInput, ValidationError } from "@/components/forms/FormField";
import { SelectInput } from "@/components/forms/SelectInput";
import { useAuthStore } from "@/store/authStore";
import { Plus, Pencil, Trash2 } from "lucide-react";

const FABRIC_SOURCE_OPTIONS = [
  { value: "", label: "Any (applies to both)" },
  { value: "in_house", label: "In-House Inventory" },
  { value: "customer_provided", label: "Customer Provided" },
];

const defaultForm = { garment_type: "", fabric_source: "", price: "", is_active: "1" };

export default function GarmentPricesPage() {
  const { can } = useAuthStore();
  const { search, page, perPage, setSearch, setPage, setPerPage } = useTableParams();
  const [open, setOpen] = useState(false);
  const [editing, setEditing] = useState(null);
  const [form, setForm] = useState(defaultForm);
  const [errors, setErrors] = useState({});
  const [deleteTarget, setDeleteTarget] = useState(null);

  const { data, isLoading } = useGarmentPrices({ page, search, per_page: perPage });
  const create = useCreateGarmentPrice();
  const update = useUpdateGarmentPrice();
  const remove = useDeleteGarmentPrice();

  const f = (field) => (e) => setForm((p) => ({ ...p, [field]: e.target.value }));

  const openCreate = () => { setEditing(null); setForm(defaultForm); setErrors({}); setOpen(true); };
  const openEdit = (row) => {
    setEditing(row);
    setForm({
      garment_type: row.garment_type, fabric_source: row.fabric_source ?? "",
      price: String(row.price), is_active: row.is_active ? "1" : "0",
    });
    setErrors({});
    setOpen(true);
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    setErrors({});
    const payload = {
      garment_type: form.garment_type,
      fabric_source: form.fabric_source || null,
      price: Number(form.price) || 0,
      is_active: form.is_active === "1",
    };
    try {
      if (editing) await update.mutateAsync({ id: editing.id, data: payload });
      else await create.mutateAsync(payload);
      setOpen(false);
    } catch (err) {
      setErrors(err.response?.data?.errors ?? {});
    }
  };

  const columns = [
    { key: "garment_type", header: "Garment Type", render: (r) => <span className="font-medium">{r.garment_type}</span> },
    { key: "fabric_source", header: "Fabric Source", render: (r) => r.fabric_source ? <span className="capitalize">{r.fabric_source.replace("_", " ")}</span> : <span className="text-muted-foreground">Any</span> },
    { key: "price", header: "Price", render: (r) => <span className="tabular-nums">{Number(r.price).toFixed(2)}</span> },
    { key: "is_active", header: "Status", render: (r) => <StatusBadge status={r.is_active} /> },
    {
      key: "actions", header: "Actions", render: (r) => (
        <div className="flex gap-1">
          {can("edit garment_prices") && (
            <Button variant="ghost" size="icon" onClick={() => openEdit(r)}><Pencil className="h-4 w-4" /></Button>
          )}
          {can("delete garment_prices") && (
            <Button variant="ghost" size="icon" className="text-destructive" onClick={() => setDeleteTarget(r)}>
              <Trash2 className="h-4 w-4" />
            </Button>
          )}
        </div>
      ),
    },
  ];

  return (
    <div>
      <TableToolbar
        search={search} onSearchChange={(v) => { setSearch(v); setPage(1); }}
        placeholder="Search garment types..."
        action={can("create garment_prices") && <Button size="sm" onClick={openCreate}><Plus />Add Price</Button>}
      />
      <DataTable columns={columns} data={data?.data ?? []} loading={isLoading} search={search} />
      <Pagination meta={data?.meta} onPageChange={setPage} perPage={perPage} onPerPageChange={setPerPage} />

      <Dialog open={open} onOpenChange={setOpen}>
        <DialogContent className="max-w-md">
          <DialogHeader><DialogTitle>{editing ? "Edit Garment Price" : "Add Garment Price"}</DialogTitle></DialogHeader>
          <form onSubmit={handleSubmit} className="space-y-4">
            <ValidationError errors={errors} />
            <TextInput label="Garment Type" value={form.garment_type} onChange={f("garment_type")}
              placeholder="e.g. Two-Piece Suit" required error={errors.garment_type?.[0]} />
            <SelectInput label="Fabric Source" value={form.fabric_source}
              onChange={(v) => setForm((p) => ({ ...p, fabric_source: v }))}
              options={FABRIC_SOURCE_OPTIONS} />
            <NumberInput label="Price" step="0.01" min="0" value={form.price} onChange={f("price")}
              required error={errors.price?.[0]} />
            <SelectInput label="Status" value={form.is_active}
              onChange={(v) => setForm((p) => ({ ...p, is_active: v }))}
              options={[{ value: "1", label: "Active" }, { value: "0", label: "Inactive" }]} />
            <div className="flex justify-end gap-2">
              <Button type="button" variant="outline" onClick={() => setOpen(false)}>Cancel</Button>
              <Button type="submit" disabled={create.isPending || update.isPending}>Save</Button>
            </div>
          </form>
        </DialogContent>
      </Dialog>

      <ConfirmDialog
        open={!!deleteTarget} onOpenChange={() => setDeleteTarget(null)}
        title="Remove Garment Price" description={`Remove pricing for "${deleteTarget?.garment_type}"? This cannot be undone.`}
        onConfirm={() => remove.mutate(deleteTarget.id, { onSuccess: () => setDeleteTarget(null) })}
        loading={remove.isPending} confirmText="Remove"
      />
    </div>
  );
}
