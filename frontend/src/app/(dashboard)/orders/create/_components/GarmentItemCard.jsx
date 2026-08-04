"use client";

import { Button } from "@/components/ui/button";
import { Card, CardContent } from "@/components/ui/card";
import { TextInput, NumberInput } from "@/components/forms/FormField";
import { SelectInput } from "@/components/forms/SelectInput";
import { Trash2, Plus } from "lucide-react";

const FABRIC_SOURCE_OPTIONS = [
  { value: "in_house", label: "In-House Inventory" },
  { value: "customer_provided", label: "Customer Provided" },
];

export function GarmentItemCard({ item, index, products, garmentPrices, onChange, onRemove, canRemove }) {
  const set = (field, value) => onChange(index, { ...item, [field]: value });

  const setGarmentType = (value) => {
    const updated = { ...item, garment_type: value };

    // Suggest a price from the reference list, but never clobber a value the staff already typed.
    if (!item.unit_price && garmentPrices?.length) {
      const match =
        garmentPrices.find((p) => p.garment_type.toLowerCase() === value.toLowerCase() && p.fabric_source === item.fabric_source) ??
        garmentPrices.find((p) => p.garment_type.toLowerCase() === value.toLowerCase() && !p.fabric_source);
      if (match) updated.unit_price = String(match.price);
    }

    onChange(index, updated);
  };

  const setMaterial = (mIdx, field, value) => {
    const materials = item.materials.map((m, i) => (i === mIdx ? { ...m, [field]: value } : m));
    set("materials", materials);
  };
  const addMaterial = () => set("materials", [...item.materials, { product_id: "", quantity_required: "" }]);
  const removeMaterial = (mIdx) => set("materials", item.materials.filter((_, i) => i !== mIdx));

  const specEntries = Object.entries(item.style_specifications ?? {});
  const setSpec = (i, key, value) => {
    const entries = [...specEntries];
    entries[i] = [key, value];
    set("style_specifications", Object.fromEntries(entries));
  };
  const addSpec = () => set("style_specifications", { ...item.style_specifications, "": "" });
  const removeSpec = (i) => {
    const entries = specEntries.filter((_, idx) => idx !== i);
    set("style_specifications", Object.fromEntries(entries));
  };

  const productOptions = (products ?? []).map((p) => ({ value: String(p.id), label: `${p.name} (${p.stock_quantity} ${p.unit_of_measure} in stock)` }));

  return (
    <Card>
      <CardContent className="pt-4 space-y-4">
        <div className="flex items-start justify-between gap-2">
          <div className="grid grid-cols-2 gap-3 flex-1">
            <TextInput label="Garment Type" value={item.garment_type} onChange={(e) => setGarmentType(e.target.value)}
              placeholder="e.g. Two-Piece Suit, Dress Shirt" required />
            <SelectInput label="Fabric Source" value={item.fabric_source}
              onChange={(v) => set("fabric_source", v)} options={FABRIC_SOURCE_OPTIONS} />
          </div>
          {canRemove && (
            <Button type="button" variant="ghost" size="icon" className="mt-6" onClick={() => onRemove(index)}>
              <Trash2 className="h-4 w-4 text-destructive" />
            </Button>
          )}
        </div>

        <div className="grid grid-cols-3 gap-3">
          <NumberInput label="Quantity" value={item.quantity} onChange={(e) => set("quantity", e.target.value)} min="1" />
          <NumberInput label="Unit Price" step="0.01" value={item.unit_price} onChange={(e) => set("unit_price", e.target.value)} />
          <NumberInput label="Discount" step="0.01" value={item.discount} onChange={(e) => set("discount", e.target.value)} />
        </div>

        {item.fabric_source === "in_house" && (
          <div className="space-y-2 rounded-md border p-3 bg-muted/20">
            <p className="text-xs font-medium text-muted-foreground">Materials (reserved now, deducted from stock when cutting starts)</p>
            {item.materials.map((m, mIdx) => (
              <div key={mIdx} className="flex gap-2">
                <SelectInput className="flex-1" value={m.product_id} onChange={(v) => setMaterial(mIdx, "product_id", v)}
                  options={productOptions} placeholder="Select fabric/trim" />
                <NumberInput className="w-28" step="0.01" placeholder="Qty" value={m.quantity_required}
                  onChange={(e) => setMaterial(mIdx, "quantity_required", e.target.value)} />
                <Button type="button" variant="ghost" size="icon" onClick={() => removeMaterial(mIdx)} disabled={item.materials.length === 1}>
                  <Trash2 className="h-3.5 w-3.5 text-destructive" />
                </Button>
              </div>
            ))}
            <Button type="button" variant="outline" size="sm" onClick={addMaterial}><Plus className="h-3.5 w-3.5" />Add Material</Button>
          </div>
        )}

        <div className="space-y-2">
          <p className="text-xs font-medium text-muted-foreground">Style Specifications (lapel, cuff, pockets, monogram…)</p>
          {specEntries.map(([key, value], i) => (
            <div key={i} className="flex gap-2">
              <TextInput className="flex-1" placeholder="Attribute (e.g. lapel)" value={key} onChange={(e) => setSpec(i, e.target.value, value)} />
              <TextInput className="flex-1" placeholder="Value (e.g. notch)" value={value} onChange={(e) => setSpec(i, key, e.target.value)} />
              <Button type="button" variant="ghost" size="icon" onClick={() => removeSpec(i)}>
                <Trash2 className="h-3.5 w-3.5 text-destructive" />
              </Button>
            </div>
          ))}
          <Button type="button" variant="outline" size="sm" onClick={addSpec}><Plus className="h-3.5 w-3.5" />Add Detail</Button>
        </div>
      </CardContent>
    </Card>
  );
}
