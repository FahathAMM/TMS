"use client";

import { useState } from "react";
import { useAlterationTypes, useCreateAlterationType, useUpdateAlterationType, useDeleteAlterationType } from "@/hooks/useAlterationTypes";
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

const defaultForm = { name: "", price: "", is_active: "1" };

export default function AlterationTypesPage() {
  const { can } = useAuthStore();
  const { search, page, perPage, setSearch, setPage, setPerPage } = useTableParams();
  const [open, setOpen] = useState(false);
  const [editing, setEditing] = useState(null);
  const [form, setForm] = useState(defaultForm);
  const [errors, setErrors] = useState({});
  const [deleteTarget, setDeleteTarget] = useState(null);

  const { data, isLoading } = useAlterationTypes({ page, search, per_page: perPage });
  const create = useCreateAlterationType();
  const update = useUpdateAlterationType();
  const remove = useDeleteAlterationType();

  const f = (field) => (e) => setForm((p) => ({ ...p, [field]: e.target.value }));

  const openCreate = () => { setEditing(null); setForm(defaultForm); setErrors({}); setOpen(true); };
  const openEdit = (row) => {
    setEditing(row);
    setForm({ name: row.name, price: String(row.price), is_active: row.is_active ? "1" : "0" });
    setErrors({});
    setOpen(true);
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    setErrors({});
    const payload = { name: form.name, price: Number(form.price) || 0, is_active: form.is_active === "1" };
    try {
      if (editing) await update.mutateAsync({ id: editing.id, data: payload });
      else await create.mutateAsync(payload);
      setOpen(false);
    } catch (err) {
      setErrors(err.response?.data?.errors ?? {});
    }
  };

  const columns = [
    { key: "name", header: "Alteration Type", render: (r) => <span className="font-medium">{r.name}</span> },
    { key: "price", header: "Price", render: (r) => <span className="tabular-nums">{Number(r.price).toFixed(2)}</span> },
    { key: "is_active", header: "Status", render: (r) => <StatusBadge status={r.is_active} /> },
    {
      key: "actions", header: "Actions", render: (r) => (
        <div className="flex gap-1">
          {can("edit alteration_types") && (
            <Button variant="ghost" size="icon" onClick={() => openEdit(r)}><Pencil className="h-4 w-4" /></Button>
          )}
          {can("delete alteration_types") && (
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
        placeholder="Search alteration types..."
        action={can("create alteration_types") && <Button size="sm" onClick={openCreate}><Plus />Add Type</Button>}
      />
      <DataTable columns={columns} data={data?.data ?? []} loading={isLoading} search={search} />
      <Pagination meta={data?.meta} onPageChange={setPage} perPage={perPage} onPerPageChange={setPerPage} />

      <Dialog open={open} onOpenChange={setOpen}>
        <DialogContent className="max-w-md">
          <DialogHeader><DialogTitle>{editing ? "Edit Alteration Type" : "Add Alteration Type"}</DialogTitle></DialogHeader>
          <form onSubmit={handleSubmit} className="space-y-4">
            <ValidationError errors={errors} />
            <TextInput label="Name" value={form.name} onChange={f("name")}
              placeholder="e.g. Hem Trousers" required error={errors.name?.[0]} />
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
        title="Remove Alteration Type" description={`Remove "${deleteTarget?.name}"? This cannot be undone.`}
        onConfirm={() => remove.mutate(deleteTarget.id, { onSuccess: () => setDeleteTarget(null) })}
        loading={remove.isPending} confirmText="Remove"
      />
    </div>
  );
}
