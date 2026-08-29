"use client";

import { useEffect, useMemo, useState } from "react";
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
  const [sidebarOpen, setSidebarOpen] = useState(false);
  const [activeModuleId, setActiveModuleId] = useState(null);

  useEffect(() => {
    if (_hasHydrated && !isAuthenticated) {
      router.replace("/login");
    }
  }, [isAuthenticated, _hasHydrated, router]);

  // Which module's sub-pages the sidebar shows. Updates whenever the URL
  // matches a module's child route; left alone otherwise (e.g. on /dashboard)
  // so the sidebar never goes blank — it just keeps showing the last module.
  useEffect(() => {
    if (!navigation) return;
    const matched = navigation.find(
      (item) => !item.route_name && item.children?.some((c) => c.route_name && pathname.startsWith(c.route_name))
    );
    if (matched) {
      setActiveModuleId(matched.id);
      return;
    }
    setActiveModuleId((prev) => prev ?? navigation.find((item) => !item.route_name && item.children?.length)?.id ?? null);
  }, [navigation, pathname]);

  // Close the mobile drawer whenever the route changes, and never leave the
  // page scroll-locked behind it.
  useEffect(() => {
    setSidebarOpen(false);
  }, [pathname]);

  useEffect(() => {
    document.body.style.overflow = sidebarOpen ? "hidden" : "";
    return () => { document.body.style.overflow = ""; };
  }, [sidebarOpen]);

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
  const activeModule = navigation?.find((item) => item.id === activeModuleId) ?? null;

  return (
    <div className="flex min-h-screen">
      <Sidebar activeModule={activeModule} navigation={navigation} open={sidebarOpen} onClose={() => setSidebarOpen(false)} />
      <div className="flex-1 flex flex-col min-w-0">
        <Header
          navigation={navigation}
          activeModuleId={activeModuleId}
          title={title}
          onMenuClick={() => setSidebarOpen(true)}
        />
        <main className="flex-1 p-6 overflow-auto bg-muted/10">
          {hasPageAccess ? children : <UnauthorizedView title={title} />}
        </main>
      </div>
    </div>
  );
}
