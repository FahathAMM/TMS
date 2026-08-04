"use client";

import { useState } from "react";
import { useSuppliers, useCreateSupplier, useUpdateSupplier, useDeleteSupplier } from "@/hooks/useSuppliers";
import { useTableParams } from "@/hooks/useTableParams";
import { DataTable } from "@/components/shared/DataTable";
import { Pagination } from "@/components/shared/Pagination";
import { TableToolbar } from "@/components/shared/TableToolbar";
import { ConfirmDialog } from "@/components/shared/ConfirmDialog";
import { Button } from "@/components/ui/button";
import { Dialog, DialogContent, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { TextInput, TextareaInput, NumberInput, ValidationError } from "@/components/forms/FormField";
import { SelectInput } from "@/components/forms/SelectInput";
import { useAuthStore } from "@/store/authStore";
import { Plus, Pencil, Trash2 } from "lucide-react";

const defaultForm = {
  name: "", company: "", phone: "", email: "", address: "", city: "",
  tax_number: "", opening_balance: "0", status: "active", notes: "",
};

export default function SuppliersPage() {
  const { can } = useAuthStore();
  const { search, page, perPage, setSearch, setPage, setPerPage } = useTableParams();
  const [open, setOpen] = useState(false);
  const [editing, setEditing] = useState(null);
  const [form, setForm] = useState(defaultForm);
  const [errors, setErrors] = useState({});
  const [deleteTarget, setDeleteTarget] = useState(null);

  const { data, isLoading } = useSuppliers({ page, search, per_page: perPage });
  const create = useCreateSupplier();
  const update = useUpdateSupplier();
  const remove = useDeleteSupplier();

  const f = (field) => (e) => setForm((p) => ({ ...p, [field]: e.target.value }));

  const openCreate = () => { setEditing(null); setForm(defaultForm); setErrors({}); setOpen(true); };
  const openEdit = (s) => {
    setEditing(s);
    setForm({
      name: s.name, company: s.company ?? "", phone: s.phone ?? "", email: s.email ?? "",
      address: s.address ?? "", city: s.city ?? "", tax_number: s.tax_number ?? "",
      opening_balance: String(s.opening_balance ?? 0), status: s.status ?? "active", notes: s.notes ?? "",
    });
    setErrors({});
    setOpen(true);
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    setErrors({});
    const payload = { ...form, opening_balance: Number(form.opening_balance) || 0 };
    try {
      if (editing) await update.mutateAsync({ id: editing.id, data: payload });
      else await create.mutateAsync(payload);
      setOpen(false);
    } catch (err) {
      setErrors(err.response?.data?.errors ?? {});
    }
  };

  const columns = [
    { key: "name", header: "Supplier", sortable: true, render: (r) => (
      <div>
        <p className="font-medium">{r.name}</p>
        {r.company && <p className="text-xs text-muted-foreground">{r.company}</p>}
      </div>
    ) },
    { key: "phone", header: "Phone", render: (r) => r.phone ?? <span className="text-muted-foreground">—</span> },
    { key: "city", header: "City", render: (r) => r.city ?? <span className="text-muted-foreground">—</span> },
    { key: "current_balance", header: "Balance", render: (r) => (
      <span className={r.current_balance > 0 ? "text-destructive font-medium" : ""}>
        {Number(r.current_balance ?? 0).toFixed(2)}
      </span>
    ) },
    {
      key: "actions", header: "Actions", render: (r) => (
        <div className="flex gap-1">
          {can("edit suppliers") && (
            <Button variant="ghost" size="icon" onClick={() => openEdit(r)}><Pencil className="h-4 w-4" /></Button>
          )}
          {can("delete suppliers") && (
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
        placeholder="Search suppliers..."
        action={can("create suppliers") && <Button size="sm" onClick={openCreate}><Plus />Add Supplier</Button>}
      />
      <DataTable columns={columns} data={data?.data ?? []} loading={isLoading} search={search} />
      <Pagination meta={data?.meta} onPageChange={setPage} perPage={perPage} onPerPageChange={setPerPage} />

      <Dialog open={open} onOpenChange={setOpen}>
        <DialogContent className="max-w-lg">
          <DialogHeader><DialogTitle>{editing ? "Edit Supplier" : "Add Supplier"}</DialogTitle></DialogHeader>
          <form onSubmit={handleSubmit} className="space-y-4">
            <ValidationError errors={errors} />
            <div className="grid grid-cols-2 gap-4">
              <TextInput label="Name" value={form.name} onChange={f("name")} required error={errors.name?.[0]} />
              <TextInput label="Company" value={form.company} onChange={f("company")} error={errors.company?.[0]} />
              <TextInput label="Phone" value={form.phone} onChange={f("phone")} error={errors.phone?.[0]} />
              <TextInput label="Email" type="email" value={form.email} onChange={f("email")} error={errors.email?.[0]} />
              <TextInput label="City" value={form.city} onChange={f("city")} error={errors.city?.[0]} />
              <TextInput label="Tax Number" value={form.tax_number} onChange={f("tax_number")} error={errors.tax_number?.[0]} />
            </div>
            <TextareaInput label="Address" value={form.address} onChange={f("address")} rows={2} error={errors.address?.[0]} />
            <div className="grid grid-cols-2 gap-4">
              <NumberInput label="Opening Balance" value={form.opening_balance} onChange={f("opening_balance")} error={errors.opening_balance?.[0]} />
              <SelectInput label="Status" value={form.status}
                onChange={(v) => setForm((p) => ({ ...p, status: v }))}
                options={[{ value: "active", label: "Active" }, { value: "inactive", label: "Inactive" }, { value: "blacklisted", label: "Blacklisted" }]} />
            </div>
            <TextareaInput label="Notes" value={form.notes} onChange={f("notes")} rows={2} error={errors.notes?.[0]} />
            <div className="flex justify-end gap-2">
              <Button type="button" variant="outline" onClick={() => setOpen(false)}>Cancel</Button>
              <Button type="submit" disabled={create.isPending || update.isPending}>Save</Button>
            </div>
          </form>
        </DialogContent>
      </Dialog>

      <ConfirmDialog
        open={!!deleteTarget} onOpenChange={() => setDeleteTarget(null)}
        title="Delete Supplier" description={`Delete "${deleteTarget?.name}"? This cannot be undone.`}
        onConfirm={() => remove.mutate(deleteTarget.id, { onSuccess: () => setDeleteTarget(null) })}
        loading={remove.isPending} confirmText="Delete"
      />
    </div>
  );
}
