"use client";

import { useRef, useState } from "react";
import { useQuery, useMutation, useQueryClient } from "@tanstack/react-query";
import { userService } from "@/services/userService";
import { useRoles } from "@/hooks/useRoles";
import { useTableParams } from "@/hooks/useTableParams";
import { DataTable } from "@/components/shared/DataTable";
import { Pagination } from "@/components/shared/Pagination";
import { TableToolbar } from "@/components/shared/TableToolbar";
import { ConfirmDialog } from "@/components/shared/ConfirmDialog";
import { StatusBadge } from "@/components/shared/StatusBadge";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import { Input } from "@/components/ui/input";
import { Checkbox } from "@/components/ui/checkbox";
import { Dialog, DialogContent, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { TextInput, TextareaInput, ValidationError } from "@/components/forms/FormField";
import { SelectInput } from "@/components/forms/SelectInput";
import { toast } from "sonner";
import { Plus, Pencil, Trash2, Camera, User as UserIcon } from "lucide-react";
import { useAuthStore } from "@/store/authStore";
import Image from "next/image";

const defaultForm = {
  name: "", email: "", password: "", phone: "", is_active: "1",
  employee_id: "", gender: "", date_of_birth: "", joining_date: "",
  address: "", emergency_contact: "",
};

export default function UsersPage() {
  const { user: me, can } = useAuthStore();
  const { search, page, perPage, sortKey, sortDir, setSearch, setPage, setPerPage, toggleSort } = useTableParams();
  const [open, setOpen] = useState(false);
  const [editing, setEditing] = useState(null);
  const [form, setForm] = useState(defaultForm);
  const [selectedRoles, setSelectedRoles] = useState([]);
  const [errors, setErrors] = useState({});
  const [deleteTarget, setDeleteTarget] = useState(null);
  const [avatarFile, setAvatarFile] = useState(null);
  const [avatarPreview, setAvatarPreview] = useState(null);
  const fileInputRef = useRef(null);
  const qc = useQueryClient();

  const queryParams = {
    page, search, per_page: perPage,
    ...(sortKey && { sort: sortKey, dir: sortDir }),
  };

  const { data, isLoading } = useQuery({
    queryKey: ["users", queryParams],
    queryFn: () => userService.getAll(queryParams).then((r) => r.data),
  });

  const { data: availableRoles } = useRoles();

  const create = useMutation({
    mutationFn: userService.create,
    onSuccess: () => { qc.invalidateQueries({ queryKey: ["users"] }); toast.success("User created"); setOpen(false); },
    onError: (err) => setErrors(err.response?.data?.errors ?? {}),
  });

  const update = useMutation({
    mutationFn: ({ id, data }) => userService.update(id, data),
    onSuccess: () => { qc.invalidateQueries({ queryKey: ["users"] }); toast.success("User updated"); setOpen(false); },
    onError: (err) => setErrors(err.response?.data?.errors ?? {}),
  });

  const remove = useMutation({
    mutationFn: userService.delete,
    onSuccess: () => { qc.invalidateQueries({ queryKey: ["users"] }); toast.success("User deleted"); setDeleteTarget(null); },
    onError: (err) => toast.error(err.response?.data?.message ?? "Failed"),
  });

  const f = (k) => (val) => setForm((p) => ({ ...p, [k]: val }));
  const fi = (k) => (e) => setForm((p) => ({ ...p, [k]: e.target.value }));

  const toggleRole = (roleName, checked) => {
    setSelectedRoles((prev) =>
      checked ? [...prev, roleName] : prev.filter((r) => r !== roleName)
    );
  };

  const handleAvatarChange = (e) => {
    const file = e.target.files?.[0];
    if (!file) return;
    setAvatarFile(file);
    setAvatarPreview(URL.createObjectURL(file));
  };

  const openCreate = () => {
    setEditing(null); setForm(defaultForm); setSelectedRoles([]);
    setAvatarFile(null); setAvatarPreview(null); setErrors({}); setOpen(true);
  };

  const openEdit = (u) => {
    setEditing(u);
    setForm({
      name: u.name, email: u.email, password: "", phone: u.phone ?? "",
      is_active: u.is_active ? "1" : "0", employee_id: u.employee_id ?? "",
      gender: u.gender ?? "", date_of_birth: u.date_of_birth ?? "",
      joining_date: u.joining_date ?? "", address: u.address ?? "",
      emergency_contact: u.emergency_contact ?? "",
    });
    setSelectedRoles(u.roles ?? []);
    setAvatarFile(null); setAvatarPreview(u.avatar ?? null);
    setErrors({}); setOpen(true);
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    setErrors({});
    const fd = new FormData();
    fd.append("name", form.name);
    fd.append("email", form.email);
    if (form.password)          fd.append("password", form.password);
    fd.append("is_active",      form.is_active);
    if (form.phone)             fd.append("phone", form.phone);
    if (form.employee_id)       fd.append("employee_id", form.employee_id);
    if (form.gender)            fd.append("gender", form.gender);
    if (form.date_of_birth)     fd.append("date_of_birth", form.date_of_birth);
    if (form.joining_date)      fd.append("joining_date", form.joining_date);
    if (form.address)           fd.append("address", form.address);
    if (form.emergency_contact) fd.append("emergency_contact", form.emergency_contact);
    if (avatarFile)             fd.append("avatar", avatarFile);
    selectedRoles.forEach((r)  => fd.append("roles[]", r));

    if (editing) update.mutate({ id: editing.id, data: fd });
    else         create.mutate(fd);
  };

  const columns = [
    {
      key: "avatar", header: "Photo",
      render: (row) => row.avatar ? (
        <div className="relative w-8 h-8 shrink-0">
          <Image src={row.avatar} alt={row.name} fill className="object-cover rounded-full" />
        </div>
      ) : (
        <div className="w-8 h-8 rounded-full bg-muted flex items-center justify-center shrink-0">
          <UserIcon className="h-4 w-4 text-muted-foreground" />
        </div>
      ),
    },
    {
      key: "name", header: "Name", sortable: true,
      render: (row) => (
        <div>
          <p className="font-medium">{row.name}</p>
          {row.employee_id && <p className="text-[10px] text-muted-foreground font-mono">{row.employee_id}</p>}
        </div>
      ),
    },
    { key: "email", header: "Email", sortable: true },
    { key: "phone", header: "Phone", render: (row) => row.phone ?? "—" },
    {
      key: "gender", header: "Gender",
      render: (row) => row.gender ? <span className="capitalize">{row.gender}</span> : "—",
    },
    {
      key: "joining_date", header: "Joined", sortable: true,
      render: (row) => row.joining_date ?? "—",
    },
    {
      key: "roles", header: "Roles",
      render: (row) => {
        const roles = row.roles ?? [];
        if (!roles.length) return <span className="text-muted-foreground italic text-[10px]">No role</span>;
        return (
          <div className="flex flex-wrap gap-1">
            {roles.map((r) => (
              <Badge key={r} variant={r === "admin" ? "default" : "secondary"} className="capitalize text-[10px] px-1.5 py-0">
                {r}
              </Badge>
            ))}
          </div>
        );
      },
    },
    { key: "is_active", header: "Status", render: (row) => <StatusBadge status={row.is_active} /> },
    {
      key: "actions", header: "Actions",
      render: (row) => (
        <div className="flex gap-1">
          {can("edit users") && (
            <Button variant="ghost" size="icon" onClick={() => openEdit(row)}>
              <Pencil className="h-4 w-4" />
            </Button>
          )}
          {can("delete users") && row.id !== me?.id && (
            <Button variant="ghost" size="icon" className="text-destructive" onClick={() => setDeleteTarget(row)}>
              <Trash2 className="h-4 w-4" />
            </Button>
          )}
        </div>
      ),
    },
  ];

  const isPending = create.isPending || update.isPending;

  return (
    <div>
      <TableToolbar
        search={search}
        onSearchChange={setSearch}
        placeholder="Search users..."
        action={can("create users") ? <Button size="sm" onClick={openCreate}><Plus />Add User</Button> : null}
      />
      <DataTable
        columns={columns}
        data={data?.data ?? []}
        loading={isLoading}
        search={search}
        sortKey={sortKey}
        sortDir={sortDir}
        onSort={toggleSort}
      />
      <Pagination meta={data?.meta} onPageChange={setPage} perPage={perPage} onPerPageChange={setPerPage} />

      <Dialog open={open} onOpenChange={setOpen}>
        <DialogContent className="max-w-2xl max-h-[90vh] overflow-y-auto">
          <DialogHeader>
            <DialogTitle>{editing ? "Edit User" : "Add User"}</DialogTitle>
          </DialogHeader>

          <form onSubmit={handleSubmit} className="space-y-4">
            <ValidationError errors={errors} />

            {/* Avatar upload */}
            <div className="flex items-center gap-4">
              <div
                className="relative w-20 h-20 rounded-full cursor-pointer overflow-hidden bg-muted border-2 border-dashed border-muted-foreground/30 hover:border-primary transition-colors group shrink-0"
                onClick={() => fileInputRef.current?.click()}
              >
                {avatarPreview ? (
                  <Image src={avatarPreview} alt="avatar" fill className="object-cover" />
                ) : (
                  <div className="w-full h-full flex items-center justify-center">
                    <UserIcon className="h-8 w-8 text-muted-foreground" />
                  </div>
                )}
                <div className="absolute inset-0 bg-black/40 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity rounded-full">
                  <Camera className="h-5 w-5 text-white" />
                </div>
              </div>
              <input ref={fileInputRef} type="file" accept="image/*" onChange={handleAvatarChange} className="hidden" />
              <div>
                <p className="text-sm font-medium">Profile Photo</p>
                <p className="text-xs text-muted-foreground mt-0.5">Click the circle to upload. Max 2 MB.</p>
              </div>
            </div>

            {/* Name + Employee ID */}
            <div className="grid grid-cols-2 gap-3">
              <TextInput label="Full Name" value={form.name} onChange={fi("name")} required error={errors.name?.[0]} />
              <TextInput label="Employee ID" value={form.employee_id} onChange={fi("employee_id")} placeholder="e.g. EMP-001" error={errors.employee_id?.[0]} />
            </div>

            {/* Email + Phone */}
            <div className="grid grid-cols-2 gap-3">
              <TextInput label="Email" type="email" value={form.email} onChange={fi("email")} required error={errors.email?.[0]} />
              <TextInput label="Phone" value={form.phone} onChange={fi("phone")} placeholder="+60 12-345 6789" error={errors.phone?.[0]} />
            </div>

            {/* Gender + DOB */}
            <div className="grid grid-cols-2 gap-3">
              <SelectInput
                label="Gender"
                value={form.gender}
                onChange={f("gender")}
                options={[
                  { value: "", label: "Select gender" },
                  { value: "male", label: "Male" },
                  { value: "female", label: "Female" },
                  { value: "other", label: "Other" },
                ]}
              />
              <div className="space-y-1.5">
                <label className="text-sm font-medium">Date of Birth</label>
                <Input type="date" value={form.date_of_birth} onChange={fi("date_of_birth")} className="h-9" />
                {errors.date_of_birth?.[0] && <p className="text-xs text-destructive">{errors.date_of_birth[0]}</p>}
              </div>
            </div>

            {/* Joining Date + Emergency Contact */}
            <div className="grid grid-cols-2 gap-3">
              <div className="space-y-1.5">
                <label className="text-sm font-medium">Joining Date</label>
                <Input type="date" value={form.joining_date} onChange={fi("joining_date")} className="h-9" />
                {errors.joining_date?.[0] && <p className="text-xs text-destructive">{errors.joining_date[0]}</p>}
              </div>
              <TextInput label="Emergency Contact" value={form.emergency_contact} onChange={fi("emergency_contact")} placeholder="Name & number" error={errors.emergency_contact?.[0]} />
            </div>

            {/* Address */}
            <TextareaInput label="Address" value={form.address} onChange={fi("address")} placeholder="Full address..." error={errors.address?.[0]} />

            {/* Roles — multi-select checkboxes */}
            <div className="space-y-1.5">
              <label className="text-sm font-medium">Roles</label>
              {availableRoles && availableRoles.length > 0 ? (
                <div className="flex flex-wrap gap-x-5 gap-y-2.5 rounded-md border bg-muted/30 px-3 py-2.5">
                  {availableRoles.map((role) => (
                    <label key={role.id} className="flex items-center gap-2 cursor-pointer select-none">
                      <Checkbox
                        checked={selectedRoles.includes(role.name)}
                        onCheckedChange={(checked) => toggleRole(role.name, !!checked)}
                      />
                      <span className="text-sm capitalize">{role.name}</span>
                    </label>
                  ))}
                </div>
              ) : (
                <p className="text-xs text-muted-foreground italic">No roles available</p>
              )}
              {errors.roles?.[0] && <p className="text-xs text-destructive">{errors.roles[0]}</p>}
            </div>

            {/* Status */}
            <SelectInput label="Status" value={form.is_active} onChange={f("is_active")} options={[{ value: "1", label: "Active" }, { value: "0", label: "Inactive" }]} />

            {/* Password */}
            <TextInput
              label={editing ? "New Password (leave blank to keep)" : "Password"}
              type="password"
              value={form.password}
              onChange={fi("password")}
              required={!editing}
              error={errors.password?.[0]}
            />

            <div className="flex justify-end gap-2 pt-1">
              <Button type="button" variant="outline" onClick={() => setOpen(false)}>Cancel</Button>
              <Button type="submit" disabled={isPending}>{isPending ? "Saving..." : "Save"}</Button>
            </div>
          </form>
        </DialogContent>
      </Dialog>

      <ConfirmDialog
        open={!!deleteTarget}
        onOpenChange={() => setDeleteTarget(null)}
        title="Delete User"
        description={`Delete "${deleteTarget?.name}"?`}
        onConfirm={() => remove.mutate(deleteTarget.id)}
        loading={remove.isPending}
        confirmText="Delete"
      />
    </div>
  );
}
