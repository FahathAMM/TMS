"use client";

import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { useDashboard } from "@/hooks/useDashboard";
import {
  Users, ShieldCheck, LayoutList, Settings2, ShoppingCart,
  AlertTriangle, DollarSign, ClipboardList,
} from "lucide-react";
import Link from "next/link";
import { Button } from "@/components/ui/button";

function StatCard({ title, description, icon: Icon, href, value }) {
  return (
    <Card className="hover:shadow-md transition-shadow">
      <CardHeader className="flex flex-row items-center justify-between pb-2">
        <CardTitle className="text-sm font-medium text-muted-foreground">{title}</CardTitle>
        <Icon className="h-4 w-4 text-muted-foreground" />
      </CardHeader>
      <CardContent>
        {value !== undefined ? (
          <p className="text-2xl font-semibold tabular-nums">{value}</p>
        ) : (
          <p className="text-xs text-muted-foreground">{description}</p>
        )}
        {href && (
          <Button asChild variant="link" className="px-0 mt-1 h-auto text-xs">
            <Link href={href}>{value !== undefined ? "View →" : "Go →"}</Link>
          </Button>
        )}
      </CardContent>
    </Card>
  );
}

export default function DashboardPage() {
  const { data } = useDashboard();
  const t = data?.tailoring ?? {};

  return (
    <div className="space-y-6">
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <StatCard title="Orders" description="All tailoring orders" icon={ShoppingCart} href="/orders" value={data?.overall?.total_orders} />
        <StatCard title="In Production" description="Orders currently being made" icon={ClipboardList} href="/production" value={t.orders_in_production} />
        <StatCard title="Low Stock Fabrics" description="Below reorder level" icon={AlertTriangle} href="/products" value={t.low_stock_fabrics} />
        <StatCard title="Revenue Today" description="Payments collected today" icon={DollarSign} href="/accounting/reports" value={t.revenue_today !== undefined ? Number(t.revenue_today).toFixed(2) : undefined} />
        <StatCard title="Customers" description="Registered customers" icon={Users} href="/customers" value={data?.overall?.total_customers} />
      </div>

      <div>
        <h2 className="text-sm font-semibold text-muted-foreground mb-3">Administration</h2>
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
          <StatCard title="Users" description="Manage admin accounts" icon={Users} href="/users" />
          <StatCard title="Roles" description="Manage roles & permissions" icon={ShieldCheck} href="/roles" />
          <StatCard title="Menus" description="Configure navigation" icon={LayoutList} href="/menus" />
          <StatCard title="Settings" description="System configuration" icon={Settings2} href="/settings" />
        </div>
      </div>
    </div>
  );
}
