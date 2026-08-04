"use client";

import { useState } from "react";
import Link from "next/link";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { TextInput, TextareaInput, ValidationError } from "@/components/forms/FormField";
import { SelectInput } from "@/components/forms/SelectInput";
import { Loader2 } from "lucide-react";

const ACTIVE_OPTIONS = [
  { value: "1", label: "Active"   },
  { value: "0", label: "Inactive" },
];

const DEFAULTS = {
  name:      "",
  mobile:    "",
  email:     "",
  address:   "",
  is_active: "1",
};

export function CustomerForm({
  initialValues = {},
  onSubmit,
  isSubmitting = false,
  errors = {},
  submitLabel = "Save Customer",
}) {
  const [form, setForm] = useState({ ...DEFAULTS, ...initialValues });

  const set    = (field) => (e) => setForm((p) => ({ ...p, [field]: e.target.value }));
  const setVal = (field) => (v) => setForm((p) => ({ ...p, [field]: v }));

  const handleSubmit = (e) => {
    e.preventDefault();
    onSubmit({
      name:      form.name,
      mobile:    form.mobile || null,
      email:     form.email  || null,
      address:   form.address || null,
      is_active: form.is_active === "1",
    });
  };

  return (
    <form onSubmit={handleSubmit} noValidate>
      <ValidationError errors={errors} />

      <div className="grid grid-cols-1 lg:grid-cols-3 gap-6 mt-4">
        {/* Main */}
        <div className="lg:col-span-2 space-y-4">
          <Card>
            <CardHeader className="pb-3">
              <CardTitle className="text-base">Customer Details</CardTitle>
            </CardHeader>
            <CardContent className="space-y-4">
              <TextInput
                label="Full Name"
                value={form.name}
                onChange={set("name")}
                required
                error={errors.name?.[0]}
                placeholder="e.g. Ahmed Hassan"
              />
              <div className="grid grid-cols-2 gap-4">
                <TextInput
                  label="Mobile"
                  value={form.mobile}
                  onChange={set("mobile")}
                  error={errors.mobile?.[0]}
                  placeholder="+92 300 0000000"
                />
                <TextInput
                  label="Email"
                  type="email"
                  value={form.email}
                  onChange={set("email")}
                  error={errors.email?.[0]}
                  placeholder="optional"
                />
              </div>
              <TextareaInput
                label="Address"
                value={form.address}
                onChange={set("address")}
                error={errors.address?.[0]}
                rows={3}
                placeholder="Street, city…"
              />
            </CardContent>
          </Card>
        </div>

        {/* Sidebar */}
        <div className="space-y-4">
          <Card>
            <CardHeader className="pb-3">
              <CardTitle className="text-base">Status</CardTitle>
            </CardHeader>
            <CardContent>
              <SelectInput
                label="Account Status"
                value={form.is_active}
                onChange={setVal("is_active")}
                options={ACTIVE_OPTIONS}
                error={errors.is_active?.[0]}
              />
            </CardContent>
          </Card>

          <div className="flex flex-col gap-2">
            <Button type="submit" disabled={isSubmitting} className="w-full">
              {isSubmitting ? <><Loader2 className="mr-2 h-4 w-4 animate-spin" />Saving…</> : submitLabel}
            </Button>
            <Button type="button" variant="outline" className="w-full" asChild>
              <Link href="/customers">Cancel</Link>
            </Button>
          </div>
        </div>
      </div>
    </form>
  );
}
