"use client";

import { Label } from "@/components/ui/label";
import { Input } from "@/components/ui/input";
import { Button } from "@/components/ui/button";
import { cn } from "@/lib/utils";
import { Plus, Trash2 } from "lucide-react";

const EMPTY_SPEC = { group: "", label: "", value: "" };

/**
 * SpecificationsEditor — manages dynamic key-value specification rows.
 * specs:    Array<{ group, label, value }>
 * onChange: (specs) => void
 */
export function SpecificationsEditor({ specs = [], onChange, errors = {} }) {
  const add = () => onChange([...specs, { ...EMPTY_SPEC }]);

  const remove = (idx) => onChange(specs.filter((_, i) => i !== idx));

  const updateField = (idx, field, value) => {
    const updated = specs.map((s, i) => (i === idx ? { ...s, [field]: value } : s));
    onChange(updated);
  };

  return (
    <div className="space-y-2">
      <div className="flex items-center justify-between">
        <Label>Specifications</Label>
        <Button type="button" variant="outline" size="sm" onClick={add} className="h-7 text-xs gap-1">
          <Plus className="h-3 w-3" /> Add Row
        </Button>
      </div>

      {specs.length === 0 ? (
        <p className="text-xs text-muted-foreground py-2 text-center border border-dashed rounded-md">
          No specifications added. Click &quot;Add Row&quot; to start.
        </p>
      ) : (
        <div className="space-y-2">
          {/* Header */}
          <div className="grid grid-cols-[1fr_2fr_2fr_32px] gap-2 px-1">
            <span className="text-[10px] font-semibold text-muted-foreground uppercase tracking-wider">Group</span>
            <span className="text-[10px] font-semibold text-muted-foreground uppercase tracking-wider">Label *</span>
            <span className="text-[10px] font-semibold text-muted-foreground uppercase tracking-wider">Value *</span>
            <span />
          </div>

          {specs.map((spec, idx) => (
            <div key={idx} className="grid grid-cols-[1fr_2fr_2fr_32px] gap-2 items-center">
              <Input
                value={spec.group}
                onChange={(e) => updateField(idx, "group", e.target.value)}
                placeholder="General"
                className={cn("h-8 text-xs", errors[`specifications.${idx}.group`] && "border-destructive")}
              />
              <Input
                value={spec.label}
                onChange={(e) => updateField(idx, "label", e.target.value)}
                placeholder="e.g. Display"
                required
                className={cn("h-8 text-xs", errors[`specifications.${idx}.label`] && "border-destructive")}
              />
              <Input
                value={spec.value}
                onChange={(e) => updateField(idx, "value", e.target.value)}
                placeholder="e.g. 6.1 inch OLED"
                required
                className={cn("h-8 text-xs", errors[`specifications.${idx}.value`] && "border-destructive")}
              />
              <Button
                type="button"
                variant="ghost"
                size="icon"
                className="h-8 w-8 text-destructive hover:text-destructive"
                onClick={() => remove(idx)}
              >
                <Trash2 className="h-3.5 w-3.5" />
              </Button>
            </div>
          ))}
        </div>
      )}
    </div>
  );
}
