"use client";

import { useState } from "react";
import { useProducts, useCreateProduct, useUpdateProduct, useDeleteProduct } from "@/hooks/useProducts";
import { useCategories, useCreateCategory, useDeleteCategory } from "@/hooks/useCategories";
import { useTableParams } from "@/hooks/useTableParams";
import { DataTable } from "@/components/shared/DataTable";
import { Pagination } from "@/components/shared/Pagination";
import { TableToolbar } from "@/components/shared/TableToolbar";
import { ConfirmDialog } from "@/components/shared/ConfirmDialog";
import { StatusBadge } from "@/components/shared/StatusBadge";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Dialog, DialogContent, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { TextInput, TextareaInput, NumberInput, ValidationError } from "@/components/forms/FormField";
import { SelectInput } from "@/components/forms/SelectInput";
import { useAuthStore } from "@/store/authStore";
import { Plus, Pencil, Trash2, Tag, AlertTriangle } from "lucide-react";

const defaultForm = {
  name: "", sku: "", category_id: "", description: "",
  cost_price: "", selling_price: "", stock_quantity: "0",
  unit_of_measure: "meters", low_stock_threshold: "10", status: "active",
};

const UNIT_OPTIONS = [
  { value: "meters", label: "Meters" },
  { value: "yards", label: "Yards" },
  { value: "pieces", label: "Pieces" },
  { value: "rolls", label: "Rolls" },
];

export default function ProductsPage() {
  const { can } = useAuthStore();
  const { search, page, perPage, setSearch, setPage, setPerPage } = useTableParams();
  const [open, setOpen] = useState(false);
  const [editing, setEditing] = useState(null);
  const [form, setForm] = useState(defaultForm);
  const [errors, setErrors] = useState({});
  const [deleteTarget, setDeleteTarget] = useState(null);
  const [catOpen, setCatOpen] = useState(false);
  const [catName, setCatName] = useState("");
  const [catDeleteTarget, setCatDeleteTarget] = useState(null);

  const { data, isLoading } = useProducts({ page, search, per_page: perPage });
  const { data: categories } = useCategories();
  const create = useCreateProduct();
  const update = useUpdateProduct();
  const remove = useDeleteProduct();
  const createCategory = useCreateCategory();
  const deleteCategory = useDeleteCategory();

  const f = (field) => (e) => setForm((p) => ({ ...p, [field]: e.target.value }));

  const openCreate = () => { setEditing(null); setForm(defaultForm); setErrors({}); setOpen(true); };
  const openEdit = (p) => {
    setEditing(p);
    setForm({
      name: p.name, sku: p.sku ?? "", category_id: p.category_id ? String(p.category_id) : "",
      description: p.description ?? "", cost_price: String(p.cost_price ?? 0), selling_price: String(p.selling_price ?? 0),
      stock_quantity: String(p.stock_quantity ?? 0), unit_of_measure: p.unit_of_measure ?? "meters",
      low_stock_threshold: String(p.low_stock_threshold ?? 10), status: p.status ?? "active",
    });
    setErrors({});
    setOpen(true);
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    setErrors({});
    const payload = {
      ...form,
      sku: form.sku || null,
      category_id: Number(form.category_id),
      cost_price: Number(form.cost_price) || 0,
      selling_price: Number(form.selling_price) || 0,
      stock_quantity: Number(form.stock_quantity) || 0,
      low_stock_threshold: Number(form.low_stock_threshold) || 0,
    };
    try {
      if (editing) await update.mutateAsync({ id: editing.id, data: payload });
      else await create.mutateAsync(payload);
      setOpen(false);
    } catch (err) {
      setErrors(err.response?.data?.errors ?? {});
    }
  };

  const handleAddCategory = async (e) => {
    e.preventDefault();
    if (!catName.trim()) return;
    await createCategory.mutateAsync({ name: catName.trim() });
    setCatName("");
  };

  const categoryOptions = (categories ?? []).map((c) => ({ value: String(c.id), label: c.name }));

  const columns = [
    { key: "name", header: "Name", sortable: true, render: (r) => (
      <div>
        <p className="font-medium">{r.name}</p>
        <p className="text-xs text-muted-foreground font-mono">{r.sku}</p>
      </div>
    ) },
    { key: "category", header: "Category", render: (r) => r.category?.name ?? <span className="text-muted-foreground">—</span> },
    { key: "stock_quantity", header: "Stock", render: (r) => (
      <span className="flex items-center gap-1.5">
        <span className={r.is_low_stock ? "text-destructive font-medium" : "font-medium"}>
          {Number(r.stock_quantity).toFixed(2)}
        </span>
        <span className="text-xs text-muted-foreground">{r.unit_of_measure}</span>
        {r.is_low_stock && <AlertTriangle className="h-3.5 w-3.5 text-destructive" />}
      </span>
    ) },
    { key: "cost_price", header: "Cost", render: (r) => Number(r.cost_price).toFixed(2) },
    { key: "selling_price", header: "Price", render: (r) => Number(r.selling_price).toFixed(2) },
    { key: "status", header: "Status", render: (r) => <StatusBadge status={r.status} /> },
    {
      key: "actions", header: "Actions", render: (r) => (
        <div className="flex gap-1">
          {can("edit products") && (
            <Button variant="ghost" size="icon" onClick={() => openEdit(r)}><Pencil className="h-4 w-4" /></Button>
          )}
          {can("delete products") && (
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
      <TableToolbar
        search={search} onSearchChange={(v) => { setSearch(v); setPage(1); }}
        placeholder="Search fabrics & trims..."
        action={
          <div className="flex gap-2">
            {can("view categories") && (
              <Button size="sm" variant="outline" onClick={() => setCatOpen(true)}><Tag />Categories</Button>
            )}
            {can("create products") && <Button size="sm" onClick={openCreate}><Plus />Add Item</Button>}
          </div>
        }
      />
      <DataTable columns={columns} data={data?.data ?? []} loading={isLoading} search={search} />
      <Pagination meta={data?.meta} onPageChange={setPage} perPage={perPage} onPerPageChange={setPerPage} />

      {/* Create / Edit Product */}
      <Dialog open={open} onOpenChange={setOpen}>
        <DialogContent className="max-w-lg max-h-[85vh] overflow-y-auto">
          <DialogHeader><DialogTitle>{editing ? "Edit Item" : "Add Fabric / Trim"}</DialogTitle></DialogHeader>
          <form onSubmit={handleSubmit} className="space-y-4">
            <ValidationError errors={errors} />
            <TextInput label="Name" value={form.name} onChange={f("name")} required
              placeholder="e.g. Italian Super 120s Navy Wool" error={errors.name?.[0]} />
            <div className="grid grid-cols-2 gap-4">
              <TextInput label="SKU" value={form.sku} onChange={f("sku")} placeholder="auto-generated if blank" error={errors.sku?.[0]} />
              <SelectInput label="Category" value={form.category_id}
                onChange={(v) => setForm((p) => ({ ...p, category_id: v }))}
                options={categoryOptions} required error={errors.category_id?.[0]} />
            </div>
            <TextareaInput label="Description" value={form.description} onChange={f("description")} rows={2} error={errors.description?.[0]} />
            <div className="grid grid-cols-3 gap-4">
              <NumberInput label="Cost Price" step="0.01" value={form.cost_price} onChange={f("cost_price")} required error={errors.cost_price?.[0]} />
              <NumberInput label="Selling Price" step="0.01" value={form.selling_price} onChange={f("selling_price")} required error={errors.selling_price?.[0]} />
              <SelectInput label="Unit" value={form.unit_of_measure}
                onChange={(v) => setForm((p) => ({ ...p, unit_of_measure: v }))} options={UNIT_OPTIONS} />
            </div>
            <div className="grid grid-cols-3 gap-4">
              <NumberInput label="Stock Qty" step="0.01" value={form.stock_quantity} onChange={f("stock_quantity")}
                disabled={!!editing} error={errors.stock_quantity?.[0]} />
              <NumberInput label="Reorder Level" step="0.01" value={form.low_stock_threshold} onChange={f("low_stock_threshold")} error={errors.low_stock_threshold?.[0]} />
              <SelectInput label="Status" value={form.status}
                onChange={(v) => setForm((p) => ({ ...p, status: v }))}
                options={[{ value: "active", label: "Active" }, { value: "draft", label: "Draft" }, { value: "archived", label: "Archived" }]} />
            </div>
            {editing && (
              <p className="text-xs text-muted-foreground">
                Stock quantity is adjusted via Purchase Orders and production consumption, not edited directly here.
              </p>
            )}
            <div className="flex justify-end gap-2">
              <Button type="button" variant="outline" onClick={() => setOpen(false)}>Cancel</Button>
              <Button type="submit" disabled={create.isPending || update.isPending}>Save</Button>
            </div>
          </form>
        </DialogContent>
      </Dialog>

      {/* Categories manager */}
      <Dialog open={catOpen} onOpenChange={setCatOpen}>
        <DialogContent className="max-w-sm">
          <DialogHeader><DialogTitle>Categories</DialogTitle></DialogHeader>
          <div className="space-y-3">
            <div className="flex flex-wrap gap-1.5">
              {(categories ?? []).map((c) => (
                <Badge key={c.id} variant="outline" className="gap-1 pr-1">
                  {c.name}
                  {can("delete categories") && (
                    <button type="button" className="ml-1 opacity-60 hover:opacity-100"
                      onClick={() => setCatDeleteTarget(c)}>
                      <Trash2 className="h-3 w-3" />
                    </button>
                  )}
                </Badge>
              ))}
              {(categories ?? []).length === 0 && <p className="text-xs text-muted-foreground">No categories yet.</p>}
            </div>
            {can("create categories") && (
              <form onSubmit={handleAddCategory} className="flex gap-2">
                <TextInput className="flex-1" value={catName} onChange={(e) => setCatName(e.target.value)}
                  placeholder="e.g. Fabrics, Trims & Buttons, Linings" />
                <Button type="submit" size="sm" disabled={createCategory.isPending}>Add</Button>
              </form>
            )}
          </div>
        </DialogContent>
      </Dialog>

      <ConfirmDialog
        open={!!deleteTarget} onOpenChange={() => setDeleteTarget(null)}
        title="Delete Item" description={`Delete "${deleteTarget?.name}"? This cannot be undone.`}
        onConfirm={() => remove.mutate(deleteTarget.id, { onSuccess: () => setDeleteTarget(null) })}
        loading={remove.isPending} confirmText="Delete"
      />
      <ConfirmDialog
        open={!!catDeleteTarget} onOpenChange={() => setCatDeleteTarget(null)}
        title="Delete Category" description={`Delete "${catDeleteTarget?.name}"? Items using it must be reassigned first.`}
        onConfirm={() => deleteCategory.mutate(catDeleteTarget.id, { onSuccess: () => setCatDeleteTarget(null) })}
        loading={deleteCategory.isPending} confirmText="Delete"
      />
    </div>
  );
}
