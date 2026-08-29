"use client";

import { useState } from "react";
import {
  useMeasurementTypes, useCreateMeasurementType, useUpdateMeasurementType,
  useUploadMeasurementTypeImage, useDeleteMeasurementType,
} from "@/hooks/useMeasurementTypes";
import { DataTable } from "@/components/shared/DataTable";
import { ConfirmDialog } from "@/components/shared/ConfirmDialog";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import { Dialog, DialogContent, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { useAuthStore } from "@/store/authStore";
import { MeasurementTypeForm } from "./_components/MeasurementTypeForm";
import { Plus, Pencil, Trash2, Ruler } from "lucide-react";

export default function MeasurementTypesPage() {
  const { can } = useAuthStore();
  const [open, setOpen] = useState(false);
  const [editing, setEditing] = useState(null);
  const [errors, setErrors] = useState({});
  const [deleteTarget, setDeleteTarget] = useState(null);

  const { data, isLoading } = useMeasurementTypes();
  const create = useCreateMeasurementType();
  const update = useUpdateMeasurementType();
  const uploadImage = useUploadMeasurementTypeImage();
  const remove = useDeleteMeasurementType();

  const submitting = create.isPending || update.isPending || uploadImage.isPending;

  const openCreate = () => { setEditing(null); setErrors({}); setOpen(true); };
  const openEdit = (row) => { setEditing(row); setErrors({}); setOpen(true); };

  const handleSubmit = async (payload, imageFile) => {
    setErrors({});
    try {
      let type;
      if (editing) {
        type = (await update.mutateAsync({ id: editing.id, data: payload })).data.data;
      } else {
        type = (await create.mutateAsync(payload)).data.data;
      }
      if (imageFile) {
        const formData = new FormData();
        formData.append("image", imageFile);
        await uploadImage.mutateAsync({ id: type.id, formData });
      }
      setOpen(false);
    } catch (err) {
      setErrors(err.response?.data?.errors ?? {});
    }
  };

  const columns = [
    {
      key: "image", header: "", render: (r) => (
        <div className="w-10 h-10 rounded border overflow-hidden bg-muted/30 flex items-center justify-center">
          {r.image_url ? <img src={r.image_url} alt={r.name} className="w-full h-full object-contain" /> : <Ruler className="h-4 w-4 text-muted-foreground" />}
        </div>
      ),
    },
    { key: "name", header: "Name", render: (r) => <span className="font-medium">{r.name}</span> },
    { key: "fields", header: "Points", render: (r) => <Badge variant="outline">{r.fields?.length ?? 0}</Badge> },
    { key: "is_active", header: "Status", render: (r) => <Badge variant={r.is_active ? "default" : "secondary"}>{r.is_active ? "Active" : "Inactive"}</Badge> },
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

      <DataTable columns={columns} data={data ?? []} loading={isLoading} />

      <Dialog open={open} onOpenChange={setOpen}>
        <DialogContent className="max-w-5xl max-h-[90vh] overflow-y-auto">
          <DialogHeader><DialogTitle>{editing ? "Edit Measurement Type" : "Add Measurement Type"}</DialogTitle></DialogHeader>
          <MeasurementTypeForm
            initial={editing}
            errors={errors}
            submitting={submitting}
            onSubmit={handleSubmit}
            onCancel={() => setOpen(false)}
          />
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
