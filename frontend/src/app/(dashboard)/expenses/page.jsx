"use client";

import { useState } from "react";
import { useExpenses, useCreateExpense } from "@/hooks/useExpenses";
import { useTableParams } from "@/hooks/useTableParams";
import { DataTable } from "@/components/shared/DataTable";
import { Pagination } from "@/components/shared/Pagination";
import { TableToolbar } from "@/components/shared/TableToolbar";
import { Button } from "@/components/ui/button";
import { Dialog, DialogContent, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { TextInput, TextareaInput, NumberInput, ValidationError } from "@/components/forms/FormField";
import { SelectInput } from "@/components/forms/SelectInput";
import { useAuthStore } from "@/store/authStore";
import { Plus } from "lucide-react";

const CATEGORIES = ["Rent", "Utilities", "Salaries", "Maintenance", "Supplies", "Other"];

const defaultForm = {
  category: "Rent", description: "", amount: "", expense_date: new Date().toISOString().slice(0, 10),
  payment_method: "cash",
};

export default function ExpensesPage() {
  const { can } = useAuthStore();
  const { search, page, perPage, setSearch, setPage, setPerPage } = useTableParams();
  const [open, setOpen] = useState(false);
  const [form, setForm] = useState(defaultForm);
  const [errors, setErrors] = useState({});

  const { data, isLoading } = useExpenses({ page, search, per_page: perPage });
  const create = useCreateExpense();

  const f = (field) => (e) => setForm((p) => ({ ...p, [field]: e.target.value }));

  const openCreate = () => { setForm(defaultForm); setErrors({}); setOpen(true); };

  const handleSubmit = async (e) => {
    e.preventDefault();
    setErrors({});
    try {
      await create.mutateAsync(form);
      setOpen(false);
    } catch (err) {
      setErrors(err.response?.data?.errors ?? {});
    }
  };

  const columns = [
    { key: "expense_number", header: "Expense #", render: (r) => <span className="font-mono text-xs">{r.expense_number}</span> },
    { key: "category", header: "Category" },
    { key: "description", header: "Description", render: (r) => r.description ?? <span className="text-muted-foreground">—</span> },
    { key: "amount", header: "Amount", render: (r) => <span className="tabular-nums">{Number(r.amount).toFixed(2)}</span> },
    { key: "expense_date", header: "Date" },
    { key: "payment_method", header: "Method", render: (r) => <span className="capitalize">{r.payment_method}</span> },
  ];

  return (
    <div>
      <TableToolbar
        search={search} onSearchChange={(v) => { setSearch(v); setPage(1); }}
        placeholder="Search expenses..."
        action={can("create expenses") && <Button size="sm" onClick={openCreate}><Plus />Add Expense</Button>}
      />
      <DataTable columns={columns} data={data?.data ?? []} loading={isLoading} search={search} />
      <Pagination meta={data?.meta} onPageChange={setPage} perPage={perPage} onPerPageChange={setPerPage} />

      <Dialog open={open} onOpenChange={setOpen}>
        <DialogContent className="max-w-md">
          <DialogHeader><DialogTitle>Add Expense</DialogTitle></DialogHeader>
          <form onSubmit={handleSubmit} className="space-y-4">
            <ValidationError errors={errors} />
            <SelectInput label="Category" value={form.category}
              onChange={(v) => setForm((p) => ({ ...p, category: v }))}
              options={CATEGORIES.map((c) => ({ value: c, label: c }))} />
            <TextareaInput label="Description" value={form.description} onChange={f("description")}
              error={errors.description?.[0]} />
            <div className="grid grid-cols-2 gap-4">
              <NumberInput label="Amount" value={form.amount} onChange={f("amount")}
                step="0.01" min="0.01" required error={errors.amount?.[0]} />
              <TextInput label="Date" type="date" value={form.expense_date} onChange={f("expense_date")}
                required error={errors.expense_date?.[0]} />
            </div>
            <SelectInput label="Payment Method" value={form.payment_method}
              onChange={(v) => setForm((p) => ({ ...p, payment_method: v }))}
              options={[{ value: "cash", label: "Cash" }, { value: "bank", label: "Bank" }]} />
            <div className="flex justify-end gap-2">
              <Button type="button" variant="outline" onClick={() => setOpen(false)}>Cancel</Button>
              <Button type="submit" disabled={create.isPending}>Save</Button>
            </div>
          </form>
        </DialogContent>
      </Dialog>
    </div>
  );
}
