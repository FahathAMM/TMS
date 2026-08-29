"use client";

import { useState, useEffect } from "react";
import { useMeasurementTypes } from "@/hooks/useMeasurementTypes";
import { useCustomerMeasurements, useSaveCustomerMeasurements } from "@/hooks/useCustomerMeasurements";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import { NumberInput } from "@/components/forms/FormField";
import { SelectInput } from "@/components/forms/SelectInput";
import { Ruler, Loader2, Save } from "lucide-react";

export function MeasurementsCard({ customerId }) {
  const { data: types } = useMeasurementTypes();
  const { data: existing, isLoading } = useCustomerMeasurements(customerId);
  const save = useSaveCustomerMeasurements(customerId);

  const [typeId, setTypeId] = useState("");
  const [values, setValues] = useState({});

  useEffect(() => {
    if (!typeId && types?.length) setTypeId(String(types[0].id));
  }, [types, typeId]);

  useEffect(() => {
    if (existing) {
      const map = {};
      existing.forEach((m) => { map[m.measurement_field_id] = String(m.value); });
      setValues(map);
    }
  }, [existing]);

  const selectedType = (types ?? []).find((t) => String(t.id) === typeId);
  const fields = selectedType?.fields ?? [];

  const handleSave = () => {
    const measurements = fields
      .filter((f) => values[f.id] !== "" && values[f.id] !== undefined && values[f.id] !== null)
      .map((f) => ({ measurement_field_id: f.id, value: Number(values[f.id]) }));
    if (measurements.length === 0) return;
    save.mutate(measurements);
  };

  return (
    <Card>
      <CardHeader className="pb-3">
        <CardTitle className="text-base flex items-center gap-2">
          <span className="flex h-8 w-8 items-center justify-center rounded-full bg-primary/10 text-primary shrink-0">
            <Ruler className="h-4 w-4" />
          </span>
          Body Measurements
        </CardTitle>
        <p className="text-xs text-muted-foreground">Choose what you&apos;re measuring, then fill in each number.</p>
      </CardHeader>
      <CardContent className="space-y-4">
        {(types ?? []).length === 0 ? (
          <p className="text-sm text-muted-foreground">No measurement types configured yet.</p>
        ) : (
          <>
            <SelectInput label="What are you measuring?" value={typeId} onChange={setTypeId}
              options={(types ?? []).map((t) => ({ value: String(t.id), label: t.name }))} />

            {isLoading ? (
              <div className="flex justify-center py-6"><Loader2 className="h-5 w-5 animate-spin text-muted-foreground" /></div>
            ) : fields.length === 0 ? (
              <p className="text-sm text-muted-foreground">This measurement type has no points configured yet.</p>
            ) : (
              <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                {selectedType?.image_url && (
                  <div className="rounded-md border bg-muted/20 flex items-center justify-center p-3">
                    <img src={selectedType.image_url} alt={selectedType.name} className="max-h-[32rem] w-full object-contain" />
                  </div>
                )}
                <div className="space-y-3">
                  <p className="text-xs text-muted-foreground">Match each number below to the same number on the picture.</p>
                  {fields.map((f) => (
                    <div key={f.id} className="flex items-center gap-2">
                      <Badge className="w-7 h-7 justify-center shrink-0 rounded-full p-0">{f.number}</Badge>
                      <NumberInput
                        className="flex-1"
                        label={`${f.name} (${f.unit})`}
                        step="0.01"
                        value={values[f.id] ?? ""}
                        onChange={(e) => setValues((p) => ({ ...p, [f.id]: e.target.value }))}
                      />
                    </div>
                  ))}
                </div>
              </div>
            )}

            <Button type="button" variant="success" onClick={handleSave} disabled={save.isPending || fields.length === 0}>
              <Save className="h-4 w-4" />Save Measurements
            </Button>
          </>
        )}
      </CardContent>
    </Card>
  );
}
