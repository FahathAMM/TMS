"use client";

import { useMeasurementTypes } from "@/hooks/useMeasurementTypes";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { OptionCard } from "@/components/shared/OptionCard";
import { SectionBox, SECTION_THEME as THEME } from "@/components/shared/SectionBox";
import { TextInput, TextareaInput, NumberInput } from "@/components/forms/FormField";
import { SelectInput } from "@/components/forms/SelectInput";
import { Trash2, Plus, Shirt, Ruler, Scissors, Palette, Home, User, Coins, MessageSquareText } from "lucide-react";

export function GarmentItemCard({ item, index, products, garmentPrices, alterationTypes, orderType, onChange, onRemove, canRemove }) {
  const isAlteration = orderType === "alteration";
  const { data: measurementTypes } = useMeasurementTypes();
  const set = (field, value) => onChange(index, { ...item, [field]: value });

  const selectedMeasurementType = (measurementTypes ?? []).find((t) => String(t.id) === String(item.measurement_type_id));
  const measurementFields = selectedMeasurementType?.fields ?? [];

  const setMeasurementType = (value) => {
    const type = (measurementTypes ?? []).find((t) => String(t.id) === value);
    onChange(index, {
      ...item,
      measurement_type_id: value || "",
      garment_type: item.garment_type || type?.name || "",
      measurements: [],
    });
  };

  const measurementValueFor = (fieldId) => (item.measurements ?? []).find((m) => m.measurement_field_id === fieldId)?.value ?? "";
  const setMeasurementValue = (fieldId, value) => {
    const existing = item.measurements ?? [];
    const updated = existing.some((m) => m.measurement_field_id === fieldId)
      ? existing.map((m) => (m.measurement_field_id === fieldId ? { ...m, value } : m))
      : [...existing, { measurement_field_id: fieldId, value }];
    set("measurements", updated);
  };

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

  const setAlterationType = (value) => {
    const updated = { ...item, garment_type: value };

    if (!item.unit_price && alterationTypes?.length) {
      const match = alterationTypes.find((t) => t.name === value);
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

  // Kept as a list (not a plain object) while editing so two rows can share
  // the same — or both still-blank — detail name without one silently
  // overwriting the other; only collapsed into a key/value object on submit.
  const specEntries = Array.isArray(item.style_specifications) ? item.style_specifications : [];
  const setSpec = (i, key, value) => {
    set("style_specifications", specEntries.map((entry, idx) => (idx === i ? { key, value } : entry)));
  };
  const addSpec = () => set("style_specifications", [...specEntries, { key: "", value: "" }]);
  const removeSpec = (i) => set("style_specifications", specEntries.filter((_, idx) => idx !== i));

  const productOptions = (products ?? []).map((p) => ({ value: String(p.id), label: `${p.name} (${p.stock_quantity} ${p.unit_of_measure} in stock)` }));
  const alterationOptions = (alterationTypes ?? []).map((t) => ({ value: t.name, label: `${t.name} (${Number(t.price).toFixed(2)})` }));

  const lineTotal = (Number(item.quantity) || 0) * (Number(item.unit_price) || 0) - (Number(item.discount) || 0);

  return (
    <Card className="border-2">
      <CardHeader className="flex flex-row items-center justify-between gap-2 space-y-0 py-2 px-4 rounded-t-lg bg-primary text-primary-foreground">
        <CardTitle className="text-sm font-semibold flex items-center gap-2 text-primary-foreground">
          <span className="flex h-6 w-6 items-center justify-center rounded-full bg-white/20 shrink-0">
            {isAlteration ? <Scissors className="h-3.5 w-3.5" /> : <Shirt className="h-3.5 w-3.5" />}
          </span>
          {isAlteration ? "Alteration" : "Dress"} #{index + 1}
        </CardTitle>
        {canRemove && (
          <Button type="button" variant="ghost" size="icon" className="h-7 w-7 text-primary-foreground hover:bg-white/20 hover:text-white"
            onClick={() => onRemove(index)}>
            <Trash2 className="h-4 w-4" />
          </Button>
        )}
      </CardHeader>
      <CardContent className="space-y-2.5">
        <div className="grid grid-cols-1 lg:grid-cols-2 gap-2.5 items-stretch">
          <SectionBox theme={THEME.basics} icon={isAlteration ? Scissors : Shirt}
            title={isAlteration ? "Alteration Type" : "Dress Type & Fabric"}
            subtitle={isAlteration ? "Select what kind of alteration this is" : "Tell us what dress this is and whose fabric to use"}>
            {isAlteration ? (
              <SelectInput label="What needs altering?" value={item.garment_type} onChange={setAlterationType}
                options={alterationOptions} placeholder="Select alteration type" required />
            ) : (
              <>
                <TextInput label="What dress is this?" value={item.garment_type} onChange={(e) => setGarmentType(e.target.value)}
                  placeholder="e.g. Thobe, Shirt, Trouser" required />

                <div className="space-y-1.5">
                  <p className="text-xs font-medium">Where is the fabric coming from?</p>
                  <div className="grid grid-cols-1 sm:grid-cols-2 gap-2">
                    <OptionCard icon={User} title="Customer's Own Fabric" description="They brought their own material"
                      selected={item.fabric_source === "customer_provided"} onClick={() => set("fabric_source", "customer_provided")} />
                    <OptionCard icon={Home} title="Shop Fabric" description="Use fabric from our inventory"
                      selected={item.fabric_source === "in_house"} onClick={() => set("fabric_source", "in_house")} />
                  </div>
                </div>
              </>
            )}
          </SectionBox>

          <SectionBox theme={THEME.pricing} icon={Coins} title="Quantity & Price"
            subtitle="How many, and how much does it cost">
            <div className="flex-1 flex flex-col justify-between gap-3">
              <div className="grid grid-cols-3 gap-3">
                <NumberInput label="Quantity" value={item.quantity} onChange={(e) => set("quantity", e.target.value)} min="1" />
                <NumberInput label="Price (each)" step="0.01" value={item.unit_price} onChange={(e) => set("unit_price", e.target.value)} />
                <NumberInput label="Discount" step="0.01" value={item.discount} onChange={(e) => set("discount", e.target.value)} />
              </div>
              <div className="flex items-center justify-between rounded-lg bg-emerald-100/70 border border-emerald-200 px-3 py-2">
                <span className="text-xs font-semibold text-emerald-800">Total for this dress</span>
                <span className="text-base font-bold text-emerald-800 tabular-nums">{lineTotal.toFixed(2)}</span>
              </div>
            </div>
          </SectionBox>
        </div>

        {!isAlteration && (
          <SectionBox theme={THEME.measure} icon={Ruler} title="Measurements"
            subtitle="Pick the measurement chart, then fill in each number">
            <SelectInput label="What kind of measurements does this dress need?" value={item.measurement_type_id ? String(item.measurement_type_id) : ""}
              onChange={setMeasurementType} options={(measurementTypes ?? []).map((t) => ({ value: String(t.id), label: t.name }))}
              placeholder="Select a measurement type (e.g. Thobe, Trouser)" />

            {selectedMeasurementType && (
              <div className="grid grid-cols-1 lg:grid-cols-[minmax(0,24rem)_1fr] gap-4 pt-1">
                {selectedMeasurementType.image_url && (
                  <div className="rounded-md border bg-background flex items-center justify-center p-2 lg:sticky lg:top-0">
                    <img src={selectedMeasurementType.image_url} alt={selectedMeasurementType.name} className="max-h-[26rem] w-full object-contain" />
                  </div>
                )}
                {measurementFields.length === 0 ? (
                  <p className="text-xs text-muted-foreground">This measurement type has no points configured yet.</p>
                ) : (
                  <div className="space-y-1.5">
                    <p className="text-xs text-muted-foreground bg-background/70 border border-indigo-200 rounded-md px-2 py-1.5">
                      Match each number below to the same number on the picture, then type in the measurement.
                    </p>
                    <div className="grid grid-cols-1 sm:grid-cols-2 gap-x-3 gap-y-1.5">
                      {measurementFields.map((f) => (
                        <div key={f.id} className="flex items-center gap-2">
                          <Badge className="w-6 h-6 text-[11px] justify-center shrink-0 rounded-full p-0 bg-indigo-600 hover:bg-indigo-600">{f.number}</Badge>
                          <NumberInput className="flex-1" label={`${f.name} (${f.unit})${f.required ? " *" : ""}`} step="0.01"
                            value={measurementValueFor(f.id)} onChange={(e) => setMeasurementValue(f.id, e.target.value)} />
                        </div>
                      ))}
                    </div>
                  </div>
                )}
              </div>
            )}
          </SectionBox>
        )}

        <div className="grid grid-cols-1 lg:grid-cols-2 gap-2.5 items-start">
          {!isAlteration && item.fabric_source === "in_house" && (
            <SectionBox theme={THEME.fabric} icon={Scissors} title="Fabric & Trim"
              subtitle="Set aside from stock now, used when cutting starts">
              {item.materials.map((m, mIdx) => (
                <div key={mIdx} className="flex gap-2">
                  <SelectInput className="flex-1" value={m.product_id} onChange={(v) => setMaterial(mIdx, "product_id", v)}
                    options={productOptions} placeholder="Select fabric/trim" />
                  <NumberInput className="w-24" step="0.01" placeholder="Qty" value={m.quantity_required}
                    onChange={(e) => setMaterial(mIdx, "quantity_required", e.target.value)} />
                  <Button type="button" variant="ghost" size="icon" onClick={() => removeMaterial(mIdx)} disabled={item.materials.length === 1}>
                    <Trash2 className="h-3.5 w-3.5 text-destructive" />
                  </Button>
                </div>
              ))}
              <Button type="button" variant="outline" size="sm" className="bg-background" onClick={addMaterial}>
                <Plus className="h-3.5 w-3.5" />Add Fabric/Trim
              </Button>
            </SectionBox>
          )}

          {isAlteration ? (
            <SectionBox theme={THEME.notes} icon={MessageSquareText} title="Notes" subtitle="Optional — describe what needs to change"
              className="lg:col-span-2">
              <TextareaInput rows={2} placeholder="What needs to change, fit adjustments, special instructions…"
                value={item.style_specifications?.notes ?? ""}
                onChange={(e) => set("style_specifications", { notes: e.target.value })} />
            </SectionBox>
          ) : (
            <SectionBox theme={THEME.style} icon={Palette} title="Style Details" subtitle="Optional — collar style, cuff, pockets, monogram, etc."
              className={item.fabric_source === "in_house" ? undefined : "lg:col-span-2"}>
              {specEntries.map(({ key, value }, i) => (
                <div key={i} className="flex gap-2">
                  <TextInput className="flex-1" placeholder="Detail (e.g. Collar)" value={key} onChange={(e) => setSpec(i, e.target.value, value)} />
                  <TextInput className="flex-1" placeholder="Choice (e.g. Notch)" value={value} onChange={(e) => setSpec(i, key, e.target.value)} />
                  <Button type="button" variant="ghost" size="icon" onClick={() => removeSpec(i)}>
                    <Trash2 className="h-3.5 w-3.5 text-destructive" />
                  </Button>
                </div>
              ))}
              <Button type="button" variant="outline" size="sm" className="bg-background" onClick={addSpec}>
                <Plus className="h-3.5 w-3.5" />Add Style Detail
              </Button>
            </SectionBox>
          )}
        </div>
      </CardContent>
    </Card>
  );
}
