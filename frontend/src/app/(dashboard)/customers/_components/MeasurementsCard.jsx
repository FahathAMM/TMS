"use client";

import { useState, useEffect, useMemo } from "react";
import { useMeasurementTypes } from "@/hooks/useMeasurementTypes";
import { useCustomerMeasurements, useSaveCustomerMeasurements } from "@/hooks/useCustomerMeasurements";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { NumberInput } from "@/components/forms/FormField";
import { SelectInput } from "@/components/forms/SelectInput";
import { Ruler, Loader2 } from "lucide-react";

export function MeasurementsCard({ customerId }) {
  const { data: types } = useMeasurementTypes();
  const { data: existing, isLoading } = useCustomerMeasurements(customerId);
  const save = useSaveCustomerMeasurements(customerId);

  const categories = useMemo(() => [...new Set((types ?? []).map((t) => t.category))], [types]);
  const [category, setCategory] = useState("");
  const [values, setValues] = useState({});

  useEffect(() => {
    if (!category && categories.length > 0) setCategory(categories[0]);
  }, [categories, category]);

  useEffect(() => {
    if (existing) {
      const map = {};
      existing.forEach((m) => { map[m.measurement_type_id] = String(m.value); });
      setValues(map);
    }
  }, [existing]);

  const typesInCategory = (types ?? []).filter((t) => t.category === category);

  const handleSave = () => {
    const measurements = Object.entries(values)
      .filter(([, v]) => v !== "" && v !== undefined && v !== null)
      .map(([measurement_type_id, value]) => ({ measurement_type_id: Number(measurement_type_id), value: Number(value) }));
    if (measurements.length === 0) return;
    save.mutate(measurements);
  };

  return (
    <Card>
      <CardHeader className="pb-3">
        <CardTitle className="text-base flex items-center gap-2"><Ruler className="h-4 w-4" />Measurements</CardTitle>
      </CardHeader>
      <CardContent className="space-y-4">
        {categories.length === 0 ? (
          <p className="text-sm text-muted-foreground">No measurement types configured yet.</p>
        ) : (
          <>
            <SelectInput label="Garment Category" value={category} onChange={setCategory}
              options={categories.map((c) => ({ value: c, label: c }))} />

            {isLoading ? (
              <div className="flex justify-center py-6"><Loader2 className="h-5 w-5 animate-spin text-muted-foreground" /></div>
            ) : (
              <div className="grid grid-cols-2 gap-3">
                {typesInCategory.map((t) => (
                  <NumberInput
                    key={t.id}
                    label={`${t.name} (${t.unit})`}
                    step="0.01"
                    value={values[t.id] ?? ""}
                    onChange={(e) => setValues((p) => ({ ...p, [t.id]: e.target.value }))}
                  />
                ))}
              </div>
            )}

            <Button type="button" size="sm" onClick={handleSave} disabled={save.isPending}>
              Save Measurements
            </Button>
          </>
        )}
      </CardContent>
    </Card>
  );
}
