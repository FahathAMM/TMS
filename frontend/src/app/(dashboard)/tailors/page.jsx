"use client";

import { useState } from "react";
import { useTailors, useCreateTailor, useUpdateTailor, useDeleteTailor } from "@/hooks/useTailors";
import { useTableParams } from "@/hooks/useTableParams";
import { DataTable } from "@/components/shared/DataTable";
import { Pagination } from "@/components/shared/Pagination";
import { TableToolbar } from "@/components/shared/TableToolbar";
import { ConfirmDialog } from "@/components/shared/ConfirmDialog";
import { StatusBadge } from "@/components/shared/StatusBadge";
import { Button } from "@/components/ui/button";
import { Dialog, DialogContent, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { TextInput, ValidationError } from "@/components/forms/FormField";
import { SelectInput } from "@/components/forms/SelectInput";
import { useAuthStore } from "@/store/authStore";
import { Plus, Pencil, Trash2 } from "lucide-react";

const defaultForm = {
  first_name: "", last_name: "", phone: "", specialization: "", is_active: "1",
};

export default function TailorsPage() {
  const { can } = useAuthStore();
  const { search, page, perPage, setSearch, setPage, setPerPage } = useTableParams();
  const [open, setOpen] = useState(false);
  const [editing, setEditing] = useState(null);
  const [form, setForm] = useState(defaultForm);
  const [errors, setErrors] = useState({});
  const [deleteTarget, setDeleteTarget] = useState(null);

  const { data, isLoading } = useTailors({ page, search, per_page: perPage });
  const create = useCreateTailor();
  const update = useUpdateTailor();
  const remove = useDeleteTailor();

  const f = (field) => (e) => setForm((p) => ({ ...p, [field]: e.target.value }));

  const openCreate = () => { setEditing(null); setForm(defaultForm); setErrors({}); setOpen(true); };
  const openEdit = (tailor) => {
    setEditing(tailor);
    setForm({
      first_name: tailor.first_name, last_name: tailor.last_name, phone: tailor.phone,
      specialization: tailor.specialization ?? "", is_active: tailor.is_active ? "1" : "0",
    });
    setErrors({});
    setOpen(true);
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    setErrors({});
    const payload = { ...form, is_active: form.is_active === "1" };
    try {
      if (editing) await update.mutateAsync({ id: editing.id, data: payload });
      else await create.mutateAsync(payload);
      setOpen(false);
    } catch (err) {
      setErrors(err.response?.data?.errors ?? {});
    }
  };

  const columns = [
    { key: "full_name", header: "Name", sortable: true, render: (r) => <span className="font-medium">{r.full_name}</span> },
    { key: "phone", header: "Phone" },
    { key: "specialization", header: "Specialization", render: (r) => r.specialization ?? <span className="text-muted-foreground">—</span> },
    { key: "is_active", header: "Status", render: (r) => <StatusBadge status={r.is_active} /> },
    {
      key: "actions", header: "Actions", render: (r) => (
        <div className="flex gap-1">
          {can("edit tailors") && (
            <Button variant="ghost" size="icon" onClick={() => openEdit(r)}><Pencil className="h-4 w-4" /></Button>
          )}
          {can("delete tailors") && (
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
        placeholder="Search tailors..."
        action={can("create tailors") && <Button size="sm" onClick={openCreate}><Plus />Add Tailor</Button>}
      />
      <DataTable columns={columns} data={data?.data ?? []} loading={isLoading} search={search} />
      <Pagination meta={data?.meta} onPageChange={setPage} perPage={perPage} onPerPageChange={setPerPage} />

      <Dialog open={open} onOpenChange={setOpen}>
        <DialogContent className="max-w-md">
          <DialogHeader><DialogTitle>{editing ? "Edit Tailor" : "Add Tailor"}</DialogTitle></DialogHeader>
          <form onSubmit={handleSubmit} className="space-y-4">
            <ValidationError errors={errors} />
            <div className="grid grid-cols-2 gap-4">
              <TextInput label="First Name" value={form.first_name} onChange={f("first_name")} required error={errors.first_name?.[0]} />
              <TextInput label="Last Name" value={form.last_name} onChange={f("last_name")} required error={errors.last_name?.[0]} />
            </div>
            <TextInput label="Phone" value={form.phone} onChange={f("phone")} required error={errors.phone?.[0]} />
            <TextInput label="Specialization" value={form.specialization} onChange={f("specialization")}
              placeholder="e.g. Master Cutter, Coat Maker" error={errors.specialization?.[0]} />
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
        title="Remove Tailor" description={`Remove "${deleteTarget?.full_name}"? This cannot be undone.`}
        onConfirm={() => remove.mutate(deleteTarget.id, { onSuccess: () => setDeleteTarget(null) })}
        loading={remove.isPending} confirmText="Remove"
      />
    </div>
  );
}
