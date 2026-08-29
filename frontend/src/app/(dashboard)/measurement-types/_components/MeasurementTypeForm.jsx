"use client";

import { useState } from "react";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import { TextInput, TextareaInput, ValidationError } from "@/components/forms/FormField";
import { SelectInput } from "@/components/forms/SelectInput";
import { FileUpload } from "@/components/forms/FileUpload";
import { Plus, Trash2, ChevronUp, ChevronDown } from "lucide-react";

const emptyField = (nextNumber) => ({
  number: nextNumber, name: "", unit: "inches", required: true,
});

export function MeasurementTypeForm({ initial, errors, submitting, onSubmit, onCancel }) {
  const [name, setName] = useState(initial?.name ?? "");
  const [description, setDescription] = useState(initial?.description ?? "");
  const [isActive, setIsActive] = useState(initial ? (initial.is_active ? "1" : "0") : "1");
  const [imageFile, setImageFile] = useState(null);
  const [fields, setFields] = useState(
    initial?.fields?.length
      ? initial.fields.map((f) => ({ id: f.id, number: f.number, name: f.name, unit: f.unit, required: f.required }))
      : [emptyField(1)]
  );

  const nextNumber = () => (fields.length ? Math.max(...fields.map((f) => Number(f.number) || 0)) + 1 : 1);

  const setField = (i, patch) => setFields((p) => p.map((f, idx) => (idx === i ? { ...f, ...patch } : f)));
  const addField = () => setFields((p) => [...p, emptyField(nextNumber())]);
  const removeField = (i) => setFields((p) => p.filter((_, idx) => idx !== i));
  const moveField = (i, dir) => {
    setFields((p) => {
      const j = i + dir;
      if (j < 0 || j >= p.length) return p;
      const copy = [...p];
      [copy[i], copy[j]] = [copy[j], copy[i]];
      return copy;
    });
  };

  const handleSubmit = (e) => {
    e.preventDefault();
    const payload = {
      name,
      description: description || null,
      is_active: isActive === "1",
      fields: fields.map((f, i) => ({
        id: f.id,
        number: Number(f.number),
        name: f.name,
        unit: f.unit || "inches",
        required: !!f.required,
        sort_order: i,
      })),
    };
    onSubmit(payload, imageFile);
  };

  return (
    <form onSubmit={handleSubmit} className="space-y-4">
      <ValidationError errors={errors} />

      <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
        {/* Left: diagram + basic info */}
        <div className="space-y-4">
          <FileUpload
            label="Measurement Diagram"
            value={initial?.image_url ?? null}
            onChange={setImageFile}
          />
          <TextInput label="Name" value={name} onChange={(e) => setName(e.target.value)}
            placeholder="e.g. Thobe, Trouser, Shirt" required error={errors?.name?.[0]} />
          <TextareaInput label="Description" rows={3} value={description}
            onChange={(e) => setDescription(e.target.value)} placeholder="Optional notes about this garment type" />
          <SelectInput label="Status" value={isActive} onChange={setIsActive}
            options={[{ value: "1", label: "Active" }, { value: "0", label: "Inactive" }]} />
        </div>

        {/* Right: numbered measurement points, matched to the diagram's numbers */}
        <div className="space-y-2">
          <p className="text-xs font-medium text-muted-foreground">
            Measurement Points — the number on each row should match the number printed on the diagram
          </p>
          <div className="space-y-2 max-h-[28rem] overflow-y-auto pr-1">
            {fields.map((f, i) => (
              <div key={i} className="flex items-start gap-2 rounded-md border p-2">
                <Badge variant="outline" className="mt-1.5 shrink-0 justify-center w-7">{f.number}</Badge>
                <div className="flex-1 grid grid-cols-2 gap-2">
                  <TextInput placeholder="Number" type="number" min="1" value={f.number}
                    onChange={(e) => setField(i, { number: e.target.value })} className="col-span-2 sm:col-span-1" />
                  <TextInput placeholder="Measurement name (e.g. Shoulder)" value={f.name}
                    onChange={(e) => setField(i, { name: e.target.value })} className="col-span-2 sm:col-span-1" required />
                  <TextInput placeholder="Unit" value={f.unit} onChange={(e) => setField(i, { unit: e.target.value })} />
                  <label className="flex items-center gap-1.5 text-xs cursor-pointer">
                    <input type="checkbox" checked={f.required}
                      onChange={(e) => setField(i, { required: e.target.checked })} className="h-3.5 w-3.5" />
                    Required
                  </label>
                </div>
                <div className="flex flex-col shrink-0">
                  <Button type="button" variant="ghost" size="icon" className="h-6 w-6" disabled={i === 0} onClick={() => moveField(i, -1)}>
                    <ChevronUp className="h-3.5 w-3.5" />
                  </Button>
                  <Button type="button" variant="ghost" size="icon" className="h-6 w-6" disabled={i === fields.length - 1} onClick={() => moveField(i, 1)}>
                    <ChevronDown className="h-3.5 w-3.5" />
                  </Button>
                </div>
                <Button type="button" variant="ghost" size="icon" className="shrink-0"
                  onClick={() => removeField(i)} disabled={fields.length === 1}>
                  <Trash2 className="h-3.5 w-3.5 text-destructive" />
                </Button>
              </div>
            ))}
          </div>
          <Button type="button" variant="outline" size="sm" onClick={addField}>
            <Plus className="h-3.5 w-3.5" />Add Point
          </Button>
        </div>
      </div>

      <div className="flex justify-end gap-2 pt-2 border-t">
        <Button type="button" variant="outline" onClick={onCancel}>Cancel</Button>
        <Button type="submit" disabled={submitting}>Save</Button>
      </div>
    </form>
  );
}
