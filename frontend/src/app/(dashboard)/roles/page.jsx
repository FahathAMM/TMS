"use client";

import { useState, useMemo } from "react";
import { useRoles, usePermissionGroups, useCreateRole, useUpdateRole, useDeleteRole } from "@/hooks/useRoles";
import { ConfirmDialog } from "@/components/shared/ConfirmDialog";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import { Input } from "@/components/ui/input";
import { Checkbox } from "@/components/ui/checkbox";
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter } from "@/components/ui/dialog";
import { Card, CardContent, CardHeader } from "@/components/ui/card";
import { Loader2, Plus, Pencil, Trash2, ShieldCheck, Users } from "lucide-react";
import { getIcon } from "@/lib/iconMap";
import { cn } from "@/lib/utils";

// permissionGroups is now an array of { menu_id, name, slug, icon, permissions: [{id, name}] }

function actionLabel(permName, slug) {
  return permName.replace(slug, "").trim();
}
function cap(str) {
  return str.charAt(0).toUpperCase() + str.slice(1);
}

const SYSTEM_ROLES = ["admin", "staff"];

function GroupIcon({ icon }) {
  if (!icon) return null;
  const Icon = getIcon(icon);
  return <Icon className="h-3 w-3 text-muted-foreground shrink-0" />;
}

function RoleFormDialog({ open, onOpenChange, role, permissionGroups }) {
  const isEdit = !!role;
  const create = useCreateRole();
  const update = useUpdateRole();

  const [name, setName] = useState(role?.name ?? "");
  const [selected, setSelected] = useState(() => new Set(role?.permissions ?? []));

  useMemo(() => {
    setName(role?.name ?? "");
    setSelected(new Set(role?.permissions ?? []));
  }, [role]);

  const isPending = create.isPending || update.isPending;

  const togglePerm = (permName) =>
    setSelected((prev) => {
      const next = new Set(prev);
      next.has(permName) ? next.delete(permName) : next.add(permName);
      return next;
    });

  const toggleGroup = (perms) => {
    const allChecked = perms.every((p) => selected.has(p.name));
    setSelected((prev) => {
      const next = new Set(prev);
      perms.forEach((p) => (allChecked ? next.delete(p.name) : next.add(p.name)));
      return next;
    });
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    const payload = { name: name.trim(), permissions: [...selected] };
    try {
      if (isEdit) await update.mutateAsync({ id: role.id, data: payload });
      else await create.mutateAsync(payload);
      onOpenChange(false);
    } catch { /* toast handled in hook */ }
  };

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className="max-w-xl max-h-[85vh] overflow-y-auto p-4">
        <DialogHeader className="pb-1">
          <DialogTitle className="text-sm font-semibold">
            {isEdit ? `Edit Role: ${role.name}` : "Create New Role"}
          </DialogTitle>
        </DialogHeader>

        <form onSubmit={handleSubmit} className="space-y-3">
          <div className="space-y-1">
            <label className="text-xs font-medium text-foreground">Role Name</label>
            <Input
              value={name}
              onChange={(e) => setName(e.target.value)}
              placeholder="e.g. supervisor"
              required
              disabled={isEdit && SYSTEM_ROLES.includes(role.name)}
              className="h-8 text-xs"
            />
            {isEdit && SYSTEM_ROLES.includes(role.name) && (
              <p className="text-[11px] text-muted-foreground">System role names cannot be changed.</p>
            )}
          </div>

          <div className="space-y-1.5">
            <label className="text-xs font-medium text-foreground">Permissions</label>
            {!permissionGroups ? (
              <div className="flex items-center gap-2 text-xs text-muted-foreground py-3">
                <Loader2 className="h-3.5 w-3.5 animate-spin" /> Loading…
              </div>
            ) : (
              <div className="space-y-1.5 max-h-[45vh] overflow-y-auto pr-1">
                {permissionGroups.map((group) => {
                  const { slug, name: groupName, icon, permissions: perms } = group;
                  const allChecked = perms.every((p) => selected.has(p.name));
                  const someChecked = perms.some((p) => selected.has(p.name));
                  return (
                    <div key={slug} className="rounded border px-2.5 py-2 space-y-1.5">
                      <div className="flex items-center gap-1.5">
                        <Checkbox
                          id={`group-${slug}`}
                          checked={allChecked ? true : someChecked ? "indeterminate" : false}
                          onCheckedChange={() => toggleGroup(perms)}
                          className="h-3.5 w-3.5"
                        />
                        <GroupIcon icon={icon} />
                        <label
                          htmlFor={`group-${slug}`}
                          className="text-xs font-semibold capitalize cursor-pointer select-none"
                        >
                          {groupName}
                        </label>
                      </div>
                      <div className="flex flex-wrap gap-x-3 gap-y-1 pl-5">
                        {perms.map((p) => (
                          <div key={p.id} className="flex items-center gap-1">
                            <Checkbox
                              id={`perm-${p.id}`}
                              checked={selected.has(p.name)}
                              onCheckedChange={() => togglePerm(p.name)}
                              className="h-3.5 w-3.5"
                            />
                            <label
                              htmlFor={`perm-${p.id}`}
                              className="text-[11px] text-muted-foreground capitalize cursor-pointer select-none"
                            >
                              {cap(actionLabel(p.name, slug))}
                            </label>
                          </div>
                        ))}
                      </div>
                    </div>
                  );
                })}
              </div>
            )}
          </div>

          <DialogFooter className="pt-1 gap-2">
            <Button type="button" variant="outline" size="sm" onClick={() => onOpenChange(false)}>
              Cancel
            </Button>
            <Button type="submit" size="sm" disabled={isPending}>
              {isPending ? "Saving…" : isEdit ? "Save Changes" : "Create Role"}
            </Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>
  );
}

function RoleCard({ role, permissionGroups, onEdit, onDelete }) {
  const isSystem = SYSTEM_ROLES.includes(role.name);

  const grouped = useMemo(() => {
    if (!permissionGroups) return [];
    return permissionGroups
      .map((group) => ({
        ...group,
        permissions: group.permissions.filter((p) => role.permissions.includes(p.name)),
      }))
      .filter((g) => g.permissions.length > 0);
  }, [role.permissions, permissionGroups]);

  return (
    <Card className="flex flex-col">
      <CardHeader className="px-3 pt-3 pb-2">
        <div className="flex items-center justify-between gap-2">
          <div className="flex items-center gap-1.5 min-w-0">
            <div className="shrink-0 rounded bg-primary/10 p-1">
              <ShieldCheck className="h-3.5 w-3.5 text-primary" />
            </div>
            <div className="min-w-0">
              <p className="text-xs font-semibold capitalize truncate">{role.name}</p>
              {isSystem && (
                <p className="text-[10px] text-muted-foreground leading-none mt-0.5">System role</p>
              )}
            </div>
          </div>
          <div className="flex gap-0.5 shrink-0">
            <Button variant="ghost" size="icon" className="h-6 w-6" onClick={onEdit}>
              <Pencil className="h-3 w-3" />
            </Button>
            <Button variant="ghost" size="icon" className="h-6 w-6 text-destructive" onClick={onDelete}>
              <Trash2 className="h-3 w-3" />
            </Button>
          </div>
        </div>
        <div className="flex items-center gap-1 text-[10px] text-muted-foreground mt-1">
          <Users className="h-3 w-3" />
          {role.users_count} {role.users_count === 1 ? "user" : "users"}
        </div>
      </CardHeader>

      <CardContent className="px-3 pb-3 flex-1 space-y-1.5">
        {grouped.length === 0 ? (
          <p className="text-[11px] text-muted-foreground italic">No permissions assigned</p>
        ) : (
          grouped.map((group) => (
            <div key={group.slug}>
              <div className="flex items-center gap-1 mb-0.5">
                <GroupIcon icon={group.icon} />
                <p className="text-[10px] font-semibold text-muted-foreground uppercase tracking-wide">
                  {group.name}
                </p>
              </div>
              <div className="flex flex-wrap gap-1">
                {group.permissions.map((p) => (
                  <Badge key={p.id} variant="secondary" className="text-[10px] px-1.5 py-0 h-4 capitalize font-normal">
                    {cap(actionLabel(p.name, group.slug))}
                  </Badge>
                ))}
              </div>
            </div>
          ))
        )}
      </CardContent>
    </Card>
  );
}

export default function RolesPage() {
  const { data: roles, isLoading } = useRoles();
  const { data: permissionGroups } = usePermissionGroups();
  const deleteRole = useDeleteRole();

  const [formOpen, setFormOpen] = useState(false);
  const [editTarget, setEditTarget] = useState(null);
  const [deleteTarget, setDeleteTarget] = useState(null);

  const openCreate = () => { setEditTarget(null); setFormOpen(true); };
  const openEdit = (role) => { setEditTarget(role); setFormOpen(true); };

  return (
    <div>
      <div className="flex justify-end mb-4">
        <Button size="sm" onClick={openCreate}><Plus />New Role</Button>
      </div>

      {isLoading ? (
        <div className="flex items-center justify-center py-16">
          <Loader2 className="h-6 w-6 animate-spin text-muted-foreground" />
        </div>
      ) : (
        <div className="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4 gap-3">
          {(roles ?? []).map((role) => (
            <RoleCard
              key={role.id}
              role={role}
              permissionGroups={permissionGroups}
              onEdit={() => openEdit(role)}
              onDelete={() => setDeleteTarget(role)}
            />
          ))}
        </div>
      )}

      <RoleFormDialog
        open={formOpen}
        onOpenChange={setFormOpen}
        role={editTarget}
        permissionGroups={permissionGroups}
      />

      <ConfirmDialog
        open={!!deleteTarget}
        onOpenChange={() => setDeleteTarget(null)}
        title="Delete Role"
        description={
          SYSTEM_ROLES.includes(deleteTarget?.name)
            ? `"${deleteTarget?.name}" is a system role and cannot be deleted.`
            : `Delete role "${deleteTarget?.name}"? Users with this role will lose it.`
        }
        onConfirm={() =>
          !SYSTEM_ROLES.includes(deleteTarget?.name) &&
          deleteRole.mutate(deleteTarget.id, { onSuccess: () => setDeleteTarget(null) })
        }
        loading={deleteRole.isPending}
        confirmText="Delete"
      />
    </div>
  );
}
