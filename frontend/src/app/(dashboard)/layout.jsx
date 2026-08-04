"use client";

import { useEffect, useMemo } from "react";
import { useRouter, usePathname } from "next/navigation";
import { useAuthStore } from "@/store/authStore";
import { useNavigationMenu } from "@/hooks/useMenus";
import { Sidebar } from "@/components/layout/Sidebar";
import { Header } from "@/components/layout/Header";
import { Loader2, ShieldOff } from "lucide-react";
import { Button } from "@/components/ui/button";
import Link from "next/link";

function UnauthorizedView({ title }) {
  return (
    <div className="flex flex-col items-center justify-center h-full py-24 gap-4 text-center">
      <div className="rounded-full bg-destructive/10 p-4">
        <ShieldOff className="h-8 w-8 text-destructive" />
      </div>
      <div>
        <h2 className="text-xl font-semibold">Access Denied</h2>
        <p className="text-sm text-muted-foreground mt-1">
          You don&apos;t have permission to view{title ? ` ${title}` : " this page"}.
        </p>
      </div>
      <Button asChild variant="outline" size="sm">
        <Link href="/dashboard">Back to Dashboard</Link>
      </Button>
    </div>
  );
}

/** Flatten a nested menu tree into a flat array of route_name strings. */
function flattenRoutes(menus) {
  const routes = [];
  const walk = (items) => {
    items?.forEach((item) => {
      if (item.route_name) routes.push(item.route_name);
      if (item.children?.length) walk(item.children);
    });
  };
  walk(menus);
  return routes;
}

/** Derive the page title from the deepest matching menu in the navigation tree. */
function getTitleFromNav(menus, pathname) {
  let best = null;
  const walk = (items) => {
    items?.forEach((item) => {
      if (item.route_name && pathname.startsWith(item.route_name)) {
        if (!best || item.route_name.length > best.route_name.length) best = item;
      }
      if (item.children?.length) walk(item.children);
    });
  };
  walk(menus);
  return best?.name ?? "Dashboard";
}

export default function DashboardLayout({ children }) {
  const { isAuthenticated, _hasHydrated } = useAuthStore();
  const router = useRouter();
  const pathname = usePathname();
  const { data: navigation, isLoading: navLoading } = useNavigationMenu();

  useEffect(() => {
    if (_hasHydrated && !isAuthenticated) {
      router.replace("/login");
    }
  }, [isAuthenticated, _hasHydrated, router]);

  const loading = (
    <div className="min-h-screen flex items-center justify-center">
      <Loader2 className="h-8 w-8 animate-spin text-muted-foreground" />
    </div>
  );

  const accessibleRoutes = useMemo(() => flattenRoutes(navigation ?? []), [navigation]);

  if (!_hasHydrated || !isAuthenticated || navLoading) return loading;

  const hasPageAccess = !accessibleRoutes.length
    ? true
    : accessibleRoutes.some((route) => pathname.startsWith(route));

  const title = getTitleFromNav(navigation, pathname);

  return (
    <div className="flex min-h-screen">
      <Sidebar />
      <div className="flex-1 flex flex-col min-w-0">
        <Header title={title} />
        <main className="flex-1 p-6 overflow-auto bg-muted/10">
          {hasPageAccess ? children : <UnauthorizedView title={title} />}
        </main>
      </div>
    </div>
  );
}
