"use client";

import { useState, useEffect } from "react";
import { useMenus, useMenusFlat, useCreateMenu, useUpdateMenu, useDeleteMenu } from "@/hooks/useMenus";
import { useTableParams } from "@/hooks/useTableParams";
import { DataTable } from "@/components/shared/DataTable";
import { Pagination } from "@/components/shared/Pagination";
import { TableToolbar } from "@/components/shared/TableToolbar";
import { ConfirmDialog } from "@/components/shared/ConfirmDialog";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import { Dialog, DialogContent, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { TextInput, NumberInput, ValidationError } from "@/components/forms/FormField";
import { SelectInput } from "@/components/forms/SelectInput";
import { Plus, Pencil, Trash2, Sparkles } from "lucide-react";
import { getIcon, iconNames } from "@/lib/iconMap";
import { cn } from "@/lib/utils";

const slugify = (str) =>
  str.toLowerCase().replace(/[^a-z0-9]+/g, "_").replace(/^_|_$/g, "");

const defaultForm = {
  name: "", slug: "", route_name: "", icon: "Settings",
  parent_id: "", sort_order: "0", is_active: "1",
};

function IconPreview({ name, className }) {
  const Icon = getIcon(name);
  return <Icon className={cn("h-4 w-4", className)} />;
}

export default function MenusPage() {
  const { search, page, perPage, setSearch, setPage, setPerPage } = useTableParams();
  const [open, setOpen] = useState(false);
  const [editing, setEditing] = useState(null);
  const [form, setForm] = useState(defaultForm);
  const [errors, setErrors] = useState({});
  const [deleteTarget, setDeleteTarget] = useState(null);
  const [iconSearch, setIconSearch] = useState("");
  const [iconPickerOpen, setIconPickerOpen] = useState(false);

  const { data, isLoading } = useMenus({ page, search, per_page: perPage });
  const { data: allMenus } = useMenusFlat();
  const create = useCreateMenu();
  const update = useUpdateMenu();
  const remove = useDeleteMenu();

  const f = (field) => (e) => setForm((p) => ({ ...p, [field]: e.target.value }));

  const openCreate = () => {
    setEditing(null);
    setForm(defaultForm);
    setErrors({});
    setOpen(true);
  };

  const openEdit = (menu) => {
    setEditing(menu);
    setForm({
      name:       menu.name,
      slug:       menu.slug,
      route_name: menu.route_name ?? "",
      icon:       menu.icon ?? "Settings",
      parent_id:  menu.parent_id ? String(menu.parent_id) : "",
      sort_order: String(menu.sort_order),
      is_active:  menu.is_active ? "1" : "0",
    });
    setErrors({});
    setOpen(true);
  };

  // Auto-generate slug when name changes (create mode only)
  useEffect(() => {
    if (!editing && form.name) {
      setForm((p) => ({ ...p, slug: slugify(form.name) }));
    }
  }, [form.name, editing]);

  const handleSubmit = async (e) => {
    e.preventDefault();
    setErrors({});
    const payload = {
      ...form,
      parent_id:  form.parent_id ? parseInt(form.parent_id) : null,
      sort_order: parseInt(form.sort_order) || 0,
      is_active:  form.is_active === "1",
    };
    try {
      if (editing) await update.mutateAsync({ id: editing.id, data: payload });
      else await create.mutateAsync(payload);
      setOpen(false);
    } catch (err) {
      setErrors(err.response?.data?.errors ?? {});
    }
  };

  const parentOptions = [
    { value: "", label: "None (top-level)" },
    ...(allMenus ?? [])
      .filter((m) => !editing || m.id !== editing.id)
      .map((m) => ({ value: String(m.id), label: m.name })),
  ];

  const filteredIcons = iconNames.filter((n) =>
    n.toLowerCase().includes(iconSearch.toLowerCase())
  );

  const columns = [
    {
      key: "name", header: "Menu", render: (r) => (
        <div className="flex items-center gap-2">
          <div className="w-7 h-7 rounded-md bg-muted flex items-center justify-center shrink-0">
            <IconPreview name={r.icon} className="text-muted-foreground" />
          </div>
          <div>
            <p className="font-medium">{r.name}</p>
            <p className="text-xs text-muted-foreground font-mono">{r.slug}</p>
          </div>
        </div>
      ),
    },
    { key: "route_name", header: "Route", render: (r) => <span className="font-mono text-xs">{r.route_name ?? "—"}</span> },
    { key: "parent", header: "Parent", render: (r) => r.parent?.name ?? <span className="text-muted-foreground">—</span> },
    { key: "sort_order", header: "Order", render: (r) => <span className="tabular-nums">{r.sort_order}</span> },
    { key: "is_active", header: "Status", render: (r) => (
      <Badge variant={r.is_active ? "default" : "secondary"}>{r.is_active ? "Active" : "Inactive"}</Badge>
    )},
    {
      key: "actions", header: "Actions", render: (r) => (
        <div className="flex gap-1">
          <Button variant="ghost" size="icon" onClick={() => openEdit(r)}><Pencil className="h-4 w-4" /></Button>
          <Button variant="ghost" size="icon" className="text-destructive" onClick={() => setDeleteTarget(r)}>
            <Trash2 className="h-4 w-4" />
          </Button>
        </div>
      ),
    },
  ];

  return (
    <div>
      <div className="mb-3 p-3 rounded-lg border border-blue-200 bg-blue-50 text-blue-800 text-xs flex items-start gap-2">
        <Sparkles className="h-3.5 w-3.5 mt-0.5 shrink-0" />
        <span>Creating a menu automatically generates <strong>view, create, edit, delete</strong> permissions for it.
        Assign those permissions to roles to control who sees the menu item.</span>
      </div>

      <TableToolbar
        search={search}
        onSearchChange={setSearch}
        placeholder="Search menus..."
        action={<Button size="sm" onClick={openCreate}><Plus />Add Menu</Button>}
      />
      <DataTable columns={columns} data={data?.data ?? []} loading={isLoading} />
      <Pagination meta={data?.meta} onPageChange={setPage} perPage={perPage} onPerPageChange={setPerPage} />

      {/* Create / Edit Dialog */}
      <Dialog open={open} onOpenChange={setOpen}>
        <DialogContent className="max-w-lg">
          <DialogHeader><DialogTitle>{editing ? "Edit Menu" : "Add Menu"}</DialogTitle></DialogHeader>
          <form onSubmit={handleSubmit} className="space-y-4">
            <ValidationError errors={errors} />

            <div className="grid grid-cols-2 gap-4">
              <div className="col-span-2">
                <TextInput label="Menu Name" value={form.name} onChange={f("name")} required error={errors.name?.[0]} />
              </div>
              <TextInput label="Slug" value={form.slug} onChange={f("slug")} required
                error={errors.slug?.[0]}
                placeholder="e.g. supplier_returns"
                disabled={!!editing}
              />
              <TextInput label="Route (URL path)" value={form.route_name} onChange={f("route_name")}
                error={errors.route_name?.[0]} placeholder="/suppliers" />
            </div>

            {/* Icon picker */}
            <div>
              <label className="text-sm font-medium">Icon</label>
              <div className="mt-1 flex items-center gap-2">
                <div className="w-9 h-9 rounded-md border flex items-center justify-center bg-muted">
                  <IconPreview name={form.icon} />
                </div>
                <Button type="button" variant="outline" size="sm" onClick={() => setIconPickerOpen(true)}>
                  {form.icon || "Select icon"}
                </Button>
              </div>
              {errors.icon && <p className="text-xs text-destructive mt-1">{errors.icon[0]}</p>}
            </div>

            <div className="grid grid-cols-2 gap-4">
              <SelectInput label="Parent Menu" value={form.parent_id}
                onChange={(v) => setForm((p) => ({ ...p, parent_id: v }))}
                options={parentOptions} />
              <NumberInput label="Sort Order" value={form.sort_order} onChange={f("sort_order")} />
              <SelectInput label="Status" value={form.is_active}
                onChange={(v) => setForm((p) => ({ ...p, is_active: v }))}
                options={[{ value: "1", label: "Active" }, { value: "0", label: "Inactive" }]} />
            </div>

            {!editing && (
              <p className="text-xs text-muted-foreground bg-muted/50 rounded p-2">
                Permissions <strong>view, create, edit, delete {form.slug || "…"}</strong> will be created automatically.
              </p>
            )}

            <div className="flex justify-end gap-2">
              <Button type="button" variant="outline" onClick={() => setOpen(false)}>Cancel</Button>
              <Button type="submit" disabled={create.isPending || update.isPending}>Save</Button>
            </div>
          </form>
        </DialogContent>
      </Dialog>

      {/* Icon Picker Dialog */}
      <Dialog open={iconPickerOpen} onOpenChange={setIconPickerOpen}>
        <DialogContent className="max-w-lg">
          <DialogHeader><DialogTitle>Select Icon</DialogTitle></DialogHeader>
          <div className="space-y-3">
            <input
              type="text"
              value={iconSearch}
              onChange={(e) => setIconSearch(e.target.value)}
              placeholder="Search icons..."
              className="w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
            />
            <div className="grid grid-cols-8 gap-1 max-h-64 overflow-y-auto">
              {filteredIcons.map((name) => {
                const Icon = getIcon(name);
                return (
                  <button
                    key={name}
                    type="button"
                    title={name}
                    onClick={() => { setForm((p) => ({ ...p, icon: name })); setIconPickerOpen(false); setIconSearch(""); }}
                    className={cn(
                      "p-2 rounded-md flex items-center justify-center transition-colors hover:bg-muted",
                      form.icon === name && "bg-primary/10 ring-1 ring-primary"
                    )}
                  >
                    <Icon className="h-4 w-4" />
                  </button>
                );
              })}
            </div>
          </div>
        </DialogContent>
      </Dialog>

      <ConfirmDialog
        open={!!deleteTarget}
        onOpenChange={() => setDeleteTarget(null)}
        title="Delete Menu"
        description={`Delete "${deleteTarget?.name}"? Its permissions will also be removed and child menus will be re-parented. This cannot be undone.`}
        onConfirm={() => remove.mutate(deleteTarget.id, { onSuccess: () => setDeleteTarget(null) })}
        loading={remove.isPending}
        confirmText="Delete"
      />
    </div>
  );
}
