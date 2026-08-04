"use client";

import { useState, useRef } from "react";
import Link from "next/link";
import { useCustomers, useDeleteCustomer } from "@/hooks/useCustomers";
import { useTableParams } from "@/hooks/useTableParams";
import { DataTable } from "@/components/shared/DataTable";
import { Pagination } from "@/components/shared/Pagination";
import { ConfirmDialog } from "@/components/shared/ConfirmDialog";
import { StatusBadge } from "@/components/shared/StatusBadge";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { useAuthStore } from "@/store/authStore";
import { Plus, Pencil, Trash2, Search, X } from "lucide-react";

export default function CustomersPage() {
  const { can } = useAuthStore();
  const { search, page, perPage, sortKey, sortDir, setSearch, setPage, setPerPage, toggleSort } = useTableParams();
  const [deleteTarget, setDeleteTarget] = useState(null);
  const [inputValue, setInputValue] = useState(search ?? "");
  const timerRef = useRef(null);

  const handleSearch = (val) => {
    setInputValue(val);
    clearTimeout(timerRef.current);
    timerRef.current = setTimeout(() => { setSearch(val); setPage(1); }, 350);
  };

  const { data, isLoading } = useCustomers({ page, search, per_page: perPage,
    ...(sortKey && { sort: sortKey, dir: sortDir }) });
  const deleteCustomer = useDeleteCustomer();

  const columns = [
    { key: "name",   header: "Name",   sortable: true, render: (row) => <span className="font-medium">{row.name}</span> },
    { key: "mobile", header: "Mobile", render: (row) => row.mobile ?? <span className="text-muted-foreground">—</span> },
    { key: "email",  header: "Email",  render: (row) => row.email  ?? <span className="text-muted-foreground">—</span> },
    { key: "address",header: "Address",render: (row) => row.address
        ? <span className="text-sm truncate max-w-[180px] block">{row.address}</span>
        : <span className="text-muted-foreground">—</span> },
    { key: "is_active", header: "Status", render: (row) => <StatusBadge status={row.is_active} /> },
    {
      key: "actions", header: "Actions",
      render: (row) => (
        <div className="flex gap-1">
          {can("edit customers") && (
            <Button variant="ghost" size="icon" asChild>
              <Link href={`/customers/${row.id}/edit`}><Pencil className="h-4 w-4" /></Link>
            </Button>
          )}
          {can("delete customers") && (
            <Button variant="ghost" size="icon" className="text-destructive" onClick={() => setDeleteTarget(row)}>
              <Trash2 className="h-4 w-4" />
            </Button>
          )}
        </div>
      ),
    },
  ];

  return (
    <div>
      <div className="flex flex-wrap items-center gap-2 mb-4">
        <div className="relative flex-1 min-w-48">
          <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground pointer-events-none" />
          <Input
            value={inputValue}
            onChange={(e) => handleSearch(e.target.value)}
            placeholder="Search by name or mobile…"
            className="pl-9 h-9 text-sm"
          />
        </div>
        {search && (
          <Button variant="ghost" size="sm" onClick={() => { setInputValue(""); setSearch(""); setPage(1); }} className="h-9 gap-1.5 text-muted-foreground">
            <X className="h-3.5 w-3.5" />Clear
          </Button>
        )}
        {can("create customers") && (
          <Button size="sm" asChild className="ml-auto">
            <Link href="/customers/create"><Plus />Add Customer</Link>
          </Button>
        )}
      </div>

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

      <ConfirmDialog
        open={!!deleteTarget}
        onOpenChange={() => setDeleteTarget(null)}
        title="Delete Customer"
        description={`Delete "${deleteTarget?.name}"? This cannot be undone.`}
        onConfirm={() => deleteCustomer.mutate(deleteTarget.id, { onSuccess: () => setDeleteTarget(null) })}
        loading={deleteCustomer.isPending}
        confirmText="Delete"
      />
    </div>
  );
}
