"use client";

import Link from "next/link";
import { usePathname } from "next/navigation";
import { cn } from "@/lib/utils";
import { getIcon } from "@/lib/iconMap";
import { ChevronDown, Plus, Store, X } from "lucide-react";
import { useState } from "react";
import { useQuery } from "@tanstack/react-query";
import { storeSettingService } from "@/services/storeSettingService";
import { TILE_COLORS } from "@/lib/tileColors";

function isRouteActive(pathname, route) {
  return !!route && (pathname === route || pathname.startsWith(route + "/"));
}

function sectionHasActiveChild(item, pathname) {
  return item.children?.some((c) => isRouteActive(pathname, c.route_name));
}

// ── Start-menu-style tile — two per row in the sidebar grid ──────────────────
function NavTile({ item, pathname, onNavigate, colorClass }) {
  const Icon = getIcon(item.icon);
  const isActive = isRouteActive(pathname, item.route_name);

  return (
    <Link
      href={item.route_name}
      onClick={onNavigate}
      className={cn(
        "relative flex aspect-square flex-col items-center justify-center gap-2 rounded-md p-2 text-center text-white shadow-sm transition-transform hover:scale-[1.03]",
        colorClass,
        isActive && "ring-2 ring-white ring-offset-2 ring-offset-sidebar"
      )}
    >
      <Icon className="h-7 w-7 shrink-0" />
      <span className="text-xs font-semibold leading-tight px-1 line-clamp-2">{item.name}</span>
    </Link>
  );
}

// ── Collapsible parent (has route_name AND children) — spans both columns ────
function CollapsibleItem({ item, pathname, depth, onNavigate }) {
  const Icon = getIcon(item.icon);
  const isActive = isRouteActive(pathname, item.route_name);
  const hasActiveChild = sectionHasActiveChild(item, pathname);
  const [expanded, setExpanded] = useState(isActive || hasActiveChild);
  const indent = 12 + depth * 16;

  return (
    <div>
      <button
        type="button"
        onClick={() => setExpanded((e) => !e)}
        className={cn(
          "w-full flex items-center gap-3 min-h-[3rem] rounded-lg text-sm font-medium transition-colors",
          isActive || hasActiveChild
            ? "bg-sidebar-primary/15 text-sidebar-primary"
            : "text-sidebar-foreground/70 hover:bg-sidebar-accent hover:text-sidebar-accent-foreground"
        )}
        style={{ paddingLeft: `${indent}px`, paddingRight: "10px" }}
      >
        <span
          className={cn(
            "flex h-9 w-9 shrink-0 items-center justify-center rounded-full transition-colors",
            (isActive || hasActiveChild) ? "bg-sidebar-primary/15" : "bg-sidebar-accent/60"
          )}
        >
          <Icon className="h-6 w-6 shrink-0" />
        </span>
        <span className="flex-1 text-left truncate">{item.name}</span>
        <ChevronDown
          className={cn("h-4 w-4 shrink-0 transition-transform duration-200", expanded && "rotate-180")}
        />
      </button>
      <div
        className="grid transition-[grid-template-rows] duration-200 ease-out"
        style={{ gridTemplateRows: expanded ? "1fr" : "0fr" }}
      >
        <div className="overflow-hidden">
          <div className="space-y-1 pt-1">
            {item.children.map((child) => (
              <NavLink key={child.id} item={child} pathname={pathname} depth={depth + 1} onNavigate={onNavigate} />
            ))}
          </div>
        </div>
      </div>
    </div>
  );
}

// ── Plain list-style link — used for nested children only ────────────────────
function NavLink({ item, pathname, depth, onNavigate }) {
  const Icon = getIcon(item.icon);
  const route = item.route_name;
  const isActive = isRouteActive(pathname, route);
  const indent = 12 + depth * 16;

  return (
    <Link
      href={route}
      onClick={onNavigate}
      className={cn(
        "flex items-center gap-3 min-h-[3rem] rounded-lg text-sm font-medium transition-colors",
        isActive
          ? "bg-sidebar-primary text-sidebar-primary-foreground shadow-sm"
          : "text-sidebar-foreground/70 hover:bg-sidebar-accent hover:text-sidebar-accent-foreground"
      )}
      style={{ paddingLeft: `${indent}px`, paddingRight: "10px" }}
    >
      <span
        className={cn(
          "flex h-9 w-9 shrink-0 items-center justify-center rounded-full transition-colors",
          isActive ? "bg-sidebar-primary-foreground/20" : "bg-sidebar-accent/60"
        )}
      >
        <Icon className="h-6 w-6 shrink-0" />
      </span>
      <span className="truncate">{item.name}</span>
    </Link>
  );
}

// ── Sidebar — Start-menu-style tile grid for the active module's pages ───────
export function Sidebar({ activeModule, navigation, open = false, onClose }) {
  const pathname = usePathname();
  const dashboardItem = navigation?.find((item) => item.route_name);

  const { data: publicSettings = {} } = useQuery({
    queryKey: ["admin-settings"],
    queryFn: () => storeSettingService.getPublic().then((r) => r.data.data),
    staleTime: 60_000,
  });
  const storeName = publicSettings.store_name || "Tailor Shop";
  const logoUrl   = publicSettings.media_logo || null;

  return (
    <>
      {/* Mobile/tablet backdrop */}
      {open && (
        <div
          className="fixed inset-0 z-40 bg-black/50 lg:hidden"
          onClick={onClose}
          aria-hidden="true"
        />
      )}

      <aside
        className={cn(
          "w-72 min-h-screen bg-sidebar text-sidebar-foreground flex flex-col border-r border-sidebar-border",
          "fixed inset-y-0 left-0 z-50 transition-transform duration-200 ease-out",
          "lg:static lg:translate-x-0 lg:w-64",
          open ? "translate-x-0" : "-translate-x-full"
        )}
      >
        {/* Brand */}
        <div className="border-b border-sidebar-border flex items-center justify-between">
          <Link href="/dashboard" onClick={onClose} className="flex items-center gap-2 p-6 hover:bg-sidebar-accent/30 transition-colors flex-1 min-w-0">
            {logoUrl ? (
              // eslint-disable-next-line @next/next/no-img-element
              <img src={logoUrl} alt={storeName} className="h-8 w-auto max-w-[120px] object-contain shrink-0" />
            ) : (
              <div className="w-8 h-8 rounded-lg bg-sidebar-primary flex items-center justify-center shrink-0">
                <Store className="h-4 w-4 text-sidebar-primary-foreground" />
              </div>
            )}
            <div className="min-w-0">
              {!logoUrl && <p className="font-bold text-sm truncate">{storeName}</p>}
              <p className="text-xs text-sidebar-foreground/60">Admin Panel</p>
            </div>
          </Link>
          <button type="button" onClick={onClose} className="lg:hidden p-2 mr-3 rounded-md hover:bg-sidebar-accent/50 shrink-0">
            <X className="h-5 w-5" />
          </button>
        </div>

        {/* Tile grid — New Order pinned wide at top, then Dashboard + the active module's pages, two per row */}
        <nav className="flex-1 px-3 py-3 overflow-y-auto">
          {activeModule && (
            <p className="px-1 mb-2 text-xs font-bold uppercase tracking-widest text-sidebar-foreground/40 select-none">
              {activeModule.name}
            </p>
          )}
          <div className="grid grid-cols-2 gap-2">
            <Link
              href="/orders/create"
              onClick={onClose}
              className="col-span-2 flex items-center justify-center gap-2 rounded-md bg-sidebar-primary py-4 text-sm font-semibold text-sidebar-primary-foreground shadow-sm transition-colors hover:bg-sidebar-primary/90"
            >
              <Plus className="h-5 w-5" />New Order
            </Link>

            {dashboardItem && (
              <NavTile item={dashboardItem} pathname={pathname} onNavigate={onClose} colorClass={TILE_COLORS[0]} />
            )}

            {activeModule?.children?.map((item, i) =>
              item.children?.length ? (
                <div key={item.id} className="col-span-2">
                  <CollapsibleItem item={item} pathname={pathname} depth={0} onNavigate={onClose} />
                </div>
              ) : (
                <NavTile
                  key={item.id}
                  item={item}
                  pathname={pathname}
                  onNavigate={onClose}
                  colorClass={TILE_COLORS[(i + 1) % TILE_COLORS.length]}
                />
              )
            )}
          </div>
        </nav>
      </aside>
    </>
  );
}
