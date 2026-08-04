"use client";

import { Label } from "@/components/ui/label";
import { Input } from "@/components/ui/input";
import { Textarea } from "@/components/ui/textarea";
import { cn } from "@/lib/utils";

export function FormField({ label, error, required, className, children, ...props }) {
  return (
    <div className={cn("space-y-1", className)}>
      {label && (
        <Label className={required ? "after:content-['*'] after:ml-0.5 after:text-destructive" : ""}>
          {label}
        </Label>
      )}
      {children}
      {error && <p className="text-xs text-destructive">{error}</p>}
    </div>
  );
}

export function TextInput({ label, error, required, className, ...props }) {
  return (
    <FormField label={label} error={error} required={required} className={className}>
      <Input className={cn(error && "border-destructive")} {...props} />
    </FormField>
  );
}

export function NumberInput({ label, error, required, className, ...props }) {
  return (
    <FormField label={label} error={error} required={required} className={className}>
      <Input type="number" className={cn(error && "border-destructive")} {...props} />
    </FormField>
  );
}

export function TextareaInput({ label, error, required, className, ...props }) {
  return (
    <FormField label={label} error={error} required={required} className={className}>
      <Textarea className={cn(error && "border-destructive")} {...props} />
    </FormField>
  );
}

export function ValidationError({ errors }) {
  if (!errors || Object.keys(errors).length === 0) return null;
  return (
    <div className="rounded-md bg-destructive/10 border border-destructive/20 p-2 space-y-0.5">
      {Object.entries(errors).map(([field, messages]) =>
        (Array.isArray(messages) ? messages : [messages]).map((msg, i) => (
          <p key={`${field}-${i}`} className="text-xs text-destructive">• {msg}</p>
        ))
      )}
    </div>
  );
}
