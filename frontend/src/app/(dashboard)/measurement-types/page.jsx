"use client";

import { useState, useMemo } from "react";
import {
  useMeasurementTypes, useCreateMeasurementType, useUpdateMeasurementType, useDeleteMeasurementType,
} from "@/hooks/useMeasurementTypes";
import { DataTable } from "@/components/shared/DataTable";
import { ConfirmDialog } from "@/components/shared/ConfirmDialog";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import { Dialog, DialogContent, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { TextInput, ValidationError } from "@/components/forms/FormField";
import { useAuthStore } from "@/store/authStore";
import { Plus, Pencil, Trash2 } from "lucide-react";

const defaultForm = { name: "", category: "", unit: "inches" };

export default function MeasurementTypesPage() {
  const { can } = useAuthStore();
  const [open, setOpen] = useState(false);
  const [editing, setEditing] = useState(null);
  const [form, setForm] = useState(defaultForm);
  const [errors, setErrors] = useState({});
  const [deleteTarget, setDeleteTarget] = useState(null);

  const { data, isLoading } = useMeasurementTypes();
  const create = useCreateMeasurementType();
  const update = useUpdateMeasurementType();
  const remove = useDeleteMeasurementType();

  const grouped = useMemo(() => {
    const groups = {};
    for (const item of data ?? []) {
      (groups[item.category] ??= []).push(item);
    }
    return groups;
  }, [data]);

  const f = (field) => (e) => setForm((p) => ({ ...p, [field]: e.target.value }));

  const openCreate = () => { setEditing(null); setForm(defaultForm); setErrors({}); setOpen(true); };
  const openEdit = (item) => {
    setEditing(item);
    setForm({ name: item.name, category: item.category, unit: item.unit });
    setErrors({});
    setOpen(true);
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    setErrors({});
    try {
      if (editing) await update.mutateAsync({ id: editing.id, data: form });
      else await create.mutateAsync(form);
      setOpen(false);
    } catch (err) {
      setErrors(err.response?.data?.errors ?? {});
    }
  };

  const columns = [
    { key: "name", header: "Name", render: (r) => <span className="font-medium">{r.name}</span> },
    { key: "unit", header: "Unit", render: (r) => <span className="text-muted-foreground">{r.unit}</span> },
    {
      key: "actions", header: "Actions", render: (r) => (
        <div className="flex gap-1">
          {can("edit measurement_types") && (
            <Button variant="ghost" size="icon" onClick={() => openEdit(r)}><Pencil className="h-4 w-4" /></Button>
          )}
          {can("delete measurement_types") && (
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
      <div className="flex justify-end mb-4">
        {can("create measurement_types") && (
          <Button size="sm" onClick={openCreate}><Plus />Add Measurement Type</Button>
        )}
      </div>

      <div className="space-y-6">
        {Object.entries(grouped).map(([category, items]) => (
          <div key={category}>
            <div className="flex items-center gap-2 mb-2">
              <h2 className="text-sm font-semibold">{category}</h2>
              <Badge variant="outline">{items.length}</Badge>
            </div>
            <DataTable columns={columns} data={items} loading={isLoading} />
          </div>
        ))}
        {!isLoading && Object.keys(grouped).length === 0 && (
          <div className="text-center text-muted-foreground py-14 text-sm">No measurement types yet.</div>
        )}
      </div>

      <Dialog open={open} onOpenChange={setOpen}>
        <DialogContent className="max-w-md">
          <DialogHeader><DialogTitle>{editing ? "Edit Measurement Type" : "Add Measurement Type"}</DialogTitle></DialogHeader>
          <form onSubmit={handleSubmit} className="space-y-4">
            <ValidationError errors={errors} />
            <TextInput label="Name" value={form.name} onChange={f("name")} required
              placeholder="e.g. Chest, Waist, Inseam" error={errors.name?.[0]} />
            <TextInput label="Garment Category" value={form.category} onChange={f("category")} required
              placeholder="e.g. Shirt, Suit Jacket, Trousers, Dress" error={errors.category?.[0]} />
            <TextInput label="Unit" value={form.unit} onChange={f("unit")} placeholder="inches" error={errors.unit?.[0]} />
            <div className="flex justify-end gap-2">
              <Button type="button" variant="outline" onClick={() => setOpen(false)}>Cancel</Button>
              <Button type="submit" disabled={create.isPending || update.isPending}>Save</Button>
            </div>
          </form>
        </DialogContent>
      </Dialog>

      <ConfirmDialog
        open={!!deleteTarget} onOpenChange={() => setDeleteTarget(null)}
        title="Delete Measurement Type" description={`Delete "${deleteTarget?.name}"? This cannot be undone.`}
        onConfirm={() => remove.mutate(deleteTarget.id, { onSuccess: () => setDeleteTarget(null) })}
        loading={remove.isPending} confirmText="Delete"
      />
    </div>
  );
}
