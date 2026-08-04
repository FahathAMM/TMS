"use client";

import Link from "next/link";
import { usePathname } from "next/navigation";
import { cn } from "@/lib/utils";
import { useAuthStore } from "@/store/authStore";
import { useNavigationMenu } from "@/hooks/useMenus";
import { getIcon } from "@/lib/iconMap";
import { Store, ChevronRight } from "lucide-react";
import { useState } from "react";
import { useQuery } from "@tanstack/react-query";
import { storeSettingService } from "@/services/storeSettingService";

// ── Section header (parent with no route_name) ────────────────────────────────
function SectionHeader({ item, pathname }) {
  return (
    <div className="mt-4 first:mt-0">
      <p className="px-3 mb-1 text-[10px] font-semibold uppercase tracking-widest text-sidebar-foreground/40 select-none">
        {item.name}
      </p>
      {item.children?.map((child) => (
        <NavItem key={child.id} item={child} pathname={pathname} depth={0} />
      ))}
    </div>
  );
}

// ── Collapsible parent (has route_name AND children) ─────────────────────────
function CollapsibleItem({ item, pathname, depth }) {
  const Icon = getIcon(item.icon);
  const isActive = pathname === item.route_name || pathname.startsWith(item.route_name + "/");
  const hasActiveChild = item.children?.some(
    (c) => c.route_name && (pathname === c.route_name || pathname.startsWith(c.route_name + "/"))
  );
  const [expanded, setExpanded] = useState(isActive || hasActiveChild);
  const indent = 12 + depth * 14;

  return (
    <div>
      <button
        onClick={() => setExpanded((e) => !e)}
        className={cn(
          "w-full flex items-center gap-2.5 py-2.5 rounded-lg text-sm font-medium transition-colors",
          isActive || hasActiveChild
            ? "bg-sidebar-primary/10 text-sidebar-primary"
            : "text-sidebar-foreground/70 hover:bg-sidebar-accent hover:text-sidebar-accent-foreground"
        )}
        style={{ paddingLeft: `${indent}px`, paddingRight: "10px" }}
      >
        <Icon className="h-4 w-4 shrink-0" />
        <span className="flex-1 text-left truncate">{item.name}</span>
        <ChevronRight
          className={cn("h-3.5 w-3.5 shrink-0 transition-transform", expanded && "rotate-90")}
        />
      </button>
      {expanded && (
        <div>
          {item.children.map((child) => (
            <NavItem key={child.id} item={child} pathname={pathname} depth={depth + 1} />
          ))}
        </div>
      )}
    </div>
  );
}

// ── Regular nav link ──────────────────────────────────────────────────────────
function NavLink({ item, pathname, depth }) {
  const Icon = getIcon(item.icon);
  const route = item.route_name;
  const isActive = pathname === route || pathname.startsWith(route + "/");
  const indent = 12 + depth * 14;

  return (
    <Link
      href={route}
      className={cn(
        "flex items-center gap-2.5 py-2.5 rounded-lg text-sm font-medium transition-colors",
        isActive
          ? "bg-sidebar-primary text-sidebar-primary-foreground"
          : "text-sidebar-foreground/70 hover:bg-sidebar-accent hover:text-sidebar-accent-foreground"
      )}
      style={{ paddingLeft: `${indent}px`, paddingRight: "10px" }}
    >
      <Icon className="h-4 w-4 shrink-0" />
      <span className="truncate">{item.name}</span>
    </Link>
  );
}

// ── Dispatcher ────────────────────────────────────────────────────────────────
function NavItem({ item, pathname, depth = 0 }) {
  const isSection = !item.route_name && item.children?.length > 0;
  const hasChildren = item.children?.length > 0;

  if (isSection) return <SectionHeader item={item} pathname={pathname} />;
  if (hasChildren) return <CollapsibleItem item={item} pathname={pathname} depth={depth} />;
  if (item.route_name) return <NavLink item={item} pathname={pathname} depth={depth} />;
  return null;
}

// ── Skeleton while loading ────────────────────────────────────────────────────
function NavSkeleton() {
  const widths = [55, 80, 65, 75, 60, 70, 55, 65, 80, 60, 70, 75];
  return (
    <div className="space-y-1 animate-pulse">
      {widths.map((w, i) => (
        <div
          key={i}
          className="h-9 rounded-lg bg-sidebar-accent/30"
          style={{ width: `${w}%`, marginLeft: i % 4 === 0 ? 0 : "8px" }}
        />
      ))}
    </div>
  );
}

// ── Sidebar ───────────────────────────────────────────────────────────────────
export function Sidebar() {
  const pathname = usePathname();
  const { user } = useAuthStore();
  const { data: navigation, isLoading } = useNavigationMenu();

  const { data: publicSettings = {} } = useQuery({
    queryKey: ["admin-settings"],
    queryFn: () => storeSettingService.getPublic().then((r) => r.data.data),
    staleTime: 60_000,
  });
  const storeName = publicSettings.store_name || "Tailor Shop";
  const logoUrl   = publicSettings.media_logo || null;

  return (
    <aside className="w-64 min-h-screen bg-sidebar text-sidebar-foreground flex flex-col border-r border-sidebar-border">
      {/* Brand */}
      <div className="border-b border-sidebar-border">
        <Link href="/dashboard" className="flex items-center gap-2 p-6 hover:bg-sidebar-accent/30 transition-colors">
          {logoUrl ? (
            <img src={logoUrl} alt={storeName} className="h-8 w-auto max-w-[120px] object-contain shrink-0" />
          ) : (
            <div className="w-8 h-8 rounded-lg bg-sidebar-primary flex items-center justify-center shrink-0">
              <Store className="h-4 w-4 text-sidebar-primary-foreground" />
            </div>
          )}
          <div>
            {!logoUrl && <p className="font-bold text-sm">{storeName}</p>}
            <p className="text-xs text-sidebar-foreground/60">Admin Panel</p>
          </div>
        </Link>
      </div>

      {/* Navigation */}
      <nav className="flex-1 px-3 py-3 overflow-y-auto space-y-0.5">
        {isLoading ? (
          <NavSkeleton />
        ) : (
          navigation?.map((item) => (
            <NavItem key={item.id} item={item} pathname={pathname} />
          ))
        )}
      </nav>

      {/* User footer */}
      <div className="p-3 border-t border-sidebar-border">
        <div className="px-3 py-2 rounded-lg bg-sidebar-accent/30">
          <p className="text-xs font-medium truncate">{user?.name}</p>
          <p className="text-xs text-sidebar-foreground/60 capitalize">{user?.role}</p>
        </div>
      </div>
    </aside>
  );
}
